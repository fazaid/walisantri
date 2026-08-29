#!/usr/bin/env bash
#
# backup.sh — Backup Walisantri (database Postgres + file upload + .env)
#
# Sederhana & aman: pg_dump -Fc + tar storage/app + salinan .env, dengan
# checksum SHA-256, rotasi lokal, dan salinan offsite via rclone.
#
# Pemakaian:
#   scripts/backup.sh                 # backup penuh (DB + file + .env) + offsite
#   scripts/backup.sh --db-only       # hanya database (dipakai hook pre-deploy)
#   scripts/backup.sh --tag pre-deploy# beri label pada nama artefak
#   scripts/backup.sh --no-offsite    # lewati unggah rclone (uji lokal)
#
# Konfigurasi via environment (punya default masuk akal untuk production):
#   APP_DIR                default /var/www/walisantri
#   BACKUP_DIR             default /home/fazaweb/backups/walisantri
#   RCLONE_REMOTE          default "" (kosong = offsite dilewati). Contoh: "b2crypt:walisantri-backup"
#   LOCAL_RETENTION_DAYS   default 7
#   OFFSITE_RETENTION_DAYS default 30
#
# Penjadwalan (crontab user deploy) — perhatikan TIDAK ada `2>&1`:
#
#     MAILTO="…@…"
#     RCLONE_REMOTE=odcrypt:walisantri-backup
#     0 2 * * * /var/www/walisantri/scripts/backup.sh >> /home/fazaweb/backups/walisantri.log
#
# stdout (jejak langkah) masuk berkas log; stderr (WARN/ERROR) dibiarkan
# mengalir ke cron supaya dikirim sebagai surel. Menambahkan `2>&1` membuat
# cron tidak pernah melihat keluaran apa pun, sehingga kegagalan berulang
# tidak memicu apa-apa — 15 hari tanpa backup offsite lewat begitu saja pada
# 15–29 Agustus 2026 karena ini.
#
set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/walisantri}"
BACKUP_DIR="${BACKUP_DIR:-/home/fazaweb/backups/walisantri}"
RCLONE_REMOTE="${RCLONE_REMOTE:-}"
LOCAL_RETENTION_DAYS="${LOCAL_RETENTION_DAYS:-7}"
OFFSITE_RETENTION_DAYS="${OFFSITE_RETENTION_DAYS:-30}"

DB_ONLY=0
TAG=""
DO_OFFSITE=1

while [[ $# -gt 0 ]]; do
    case "$1" in
        --db-only)     DB_ONLY=1 ;;
        --no-offsite)  DO_OFFSITE=0 ;;
        --tag)         TAG="${2:-}"; shift ;;
        --tag=*)       TAG="${1#*=}" ;;
        -h|--help)     grep '^#' "$0" | sed 's/^# \{0,1\}//'; exit 0 ;;
        *) echo "Argumen tak dikenal: $1" >&2; exit 2 ;;
    esac
    shift
done

# Ringkasan kegagalan non-fatal. Terisi = backup selesai tapi TIDAK utuh,
# dan skrip keluar dengan status 1 di baris terakhir.
DEGRADASI=()

log()  { echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*"; }

# WARN dan ERROR sengaja ditulis ke stdout DAN stderr: stdout masuk berkas log,
# stderr ditangkap cron dan dikirim ke MAILTO. Crontab karena itu harus TANPA
# `2>&1` — dengan `2>&1`, stderr ikut tertelan berkas log, cron tidak pernah
# melihat keluaran apa pun, dan kegagalan jadi tak terlihat. Persis itu yang
# menyembunyikan 15 hari tanpa backup offsite (15–29 Agustus 2026).
warn() { echo "[$(date '+%Y-%m-%d %H:%M:%S')] WARN: $*"; echo "[$(date '+%Y-%m-%d %H:%M:%S')] WARN: $*" >&2; }
die()  { echo "[$(date '+%Y-%m-%d %H:%M:%S')] ERROR: $*"; echo "[$(date '+%Y-%m-%d %H:%M:%S')] ERROR: $*" >&2; exit 1; }

[[ -f "$APP_DIR/.env" ]] || die "File .env tidak ditemukan di $APP_DIR"

# Ambil nilai dari .env (baris terakhir yang cocok, buang kutip & spasi).
env_get() {
    local v
    v="$(grep -E "^$1=" "$APP_DIR/.env" | tail -n1 | cut -d= -f2- || true)"
    v="${v%$'\r'}"                        # buang CR bila file CRLF
    v="$(echo "$v" | sed -e 's/^[[:space:]]*//' -e 's/[[:space:]]*$//')"
    v="${v%\"}"; v="${v#\"}"              # buang kutip ganda
    v="${v%\'}"; v="${v#\'}"              # buang kutip tunggal
    echo "$v"
}

DB_DATABASE="$(env_get DB_DATABASE)"; [[ -n "$DB_DATABASE" ]] || DB_DATABASE="$(env_get CENTRAL_DB_DATABASE)"
DB_USERNAME="$(env_get DB_USERNAME)"; [[ -n "$DB_USERNAME" ]] || DB_USERNAME="$(env_get CENTRAL_DB_USERNAME)"
DB_PASSWORD="$(env_get DB_PASSWORD)"; [[ -n "$DB_PASSWORD" ]] || DB_PASSWORD="$(env_get CENTRAL_DB_PASSWORD)"
DB_HOST="$(env_get DB_HOST)"; [[ -n "$DB_HOST" ]] || DB_HOST="127.0.0.1"
DB_PORT="$(env_get DB_PORT)"; [[ -n "$DB_PORT" ]] || DB_PORT="5432"

[[ -n "$DB_DATABASE" ]] || die "DB_DATABASE tidak terbaca dari .env"

command -v pg_dump >/dev/null || die "pg_dump tidak terpasang"

TS="$(date '+%Y%m%d-%H%M%S')"
SUFFIX="${TAG:+-$TAG}"
DEST="$BACKUP_DIR"
mkdir -p "$DEST"

DB_FILE="$DEST/db-${TS}${SUFFIX}.dump"
FILES_FILE="$DEST/files-${TS}${SUFFIX}.tar.gz"
ENV_FILE="$DEST/env-${TS}${SUFFIX}.txt"
SUMS_FILE="$DEST/SHA256SUMS-${TS}${SUFFIX}"

artifacts=()

log "Mulai backup (db_only=$DB_ONLY, tag='${TAG:-}') → $DEST"

# 1) Database — pg_dump custom format (terkompresi, mendukung pg_restore selektif)
log "pg_dump database '$DB_DATABASE' → $(basename "$DB_FILE")"
PGPASSWORD="$DB_PASSWORD" pg_dump \
    -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USERNAME" \
    -Fc --no-owner --no-privileges \
    -f "$DB_FILE" "$DB_DATABASE" \
    || die "pg_dump gagal"
chmod 600 "$DB_FILE"
artifacts+=("$DB_FILE")

if [[ "$DB_ONLY" -eq 0 ]]; then
    # 2) File upload — storage/app (public + private)
    log "tar storage/app → $(basename "$FILES_FILE")"

    # SENGAJA tidak `die`. Sebelumnya langkah ini fatal, dan konsekuensinya jauh
    # lebih besar daripada kelihatannya: satu direktori yang tak terbaca membuat
    # seluruh skrip berhenti di sini, sehingga langkah 5 (unggah offsite) tidak
    # pernah tercapai — dump database yang sudah jadi pun ikut tidak pernah keluar
    # dari server. Terjadi 15–29 Agustus 2026: PHP-FPM (www-data) membuat
    # storage/app/private/bukti-transfer/<id> dengan mode 0700, backup berjalan
    # sebagai fazaweb, dan 15 hari berlalu tanpa satu pun salinan offsite.
    # Arsip parsial lebih baik daripada tidak ada arsip, tapi HARUS mustahil
    # tertukar dengan yang utuh — karena itu diberi nama -PARSIAL.
    if tar czf "$FILES_FILE" -C "$APP_DIR" \
        $( [[ -d "$APP_DIR/storage/app/public" ]]  && echo storage/app/public ) \
        $( [[ -d "$APP_DIR/storage/app/private" ]] && echo storage/app/private )
    then
        chmod 600 "$FILES_FILE"
    elif [[ -s "$FILES_FILE" ]]; then
        PARSIAL="$DEST/files-${TS}${SUFFIX}-PARSIAL.tar.gz"
        mv -f "$FILES_FILE" "$PARSIAL"
        FILES_FILE="$PARSIAL"
        chmod 600 "$FILES_FILE"
        DEGRADASI+=("arsip storage/app TIDAK lengkap — $(basename "$FILES_FILE")")
        warn "tar storage gagal membaca sebagian berkas. Backup DILANJUTKAN; arsip disimpan & diunggah sebagai $(basename "$FILES_FILE"). Periksa kepemilikan/mode di $APP_DIR/storage/app."
    else
        # tar gagal tanpa menghasilkan apa pun yang berguna. Jangan biarkan berkas
        # kosong ikut terdaftar sebagai artefak — sha256sum dan rclone di bawah
        # akan `die` karenanya, dan kita kembali ke perilaku lama yang justru
        # membatalkan unggahan offsite dump database.
        rm -f "$FILES_FILE"
        FILES_FILE=""
        DEGRADASI+=("arsip storage/app GAGAL TOTAL — tidak ada berkas untuk diunggah")
        warn "tar storage gagal total. Backup DILANJUTKAN tanpa arsip berkas; dump database tetap diunggah. Periksa kepemilikan/mode di $APP_DIR/storage/app."
    fi
    if [[ -n "$FILES_FILE" ]]; then artifacts+=("$FILES_FILE"); fi

    # 3) .env (berisi APP_KEY & rahasia — bucket WAJIB privat / rclone crypt)
    cp "$APP_DIR/.env" "$ENV_FILE"
    chmod 600 "$ENV_FILE"
    artifacts+=("$ENV_FILE")
fi

# 4) Checksum integritas
( cd "$DEST" && sha256sum "$(basename "$DB_FILE")" \
    $( [[ "$DB_ONLY" -eq 0 && -n "$FILES_FILE" ]] && basename "$FILES_FILE" ) \
    $( [[ "$DB_ONLY" -eq 0 ]] && basename "$ENV_FILE" ) > "$(basename "$SUMS_FILE")" )
chmod 600 "$SUMS_FILE"
artifacts+=("$SUMS_FILE")
log "Checksum ditulis → $(basename "$SUMS_FILE")"

TOTAL_SIZE="$(du -ch "${artifacts[@]}" | tail -n1 | cut -f1)"
log "Artefak lokal selesai (total $TOTAL_SIZE)"

# 5) Offsite via rclone
if [[ "$DO_OFFSITE" -eq 1 && -n "$RCLONE_REMOTE" ]]; then
    if command -v rclone >/dev/null; then
        REMOTE_PATH="${RCLONE_REMOTE%/}/$(date '+%Y/%m')"
        log "rclone copy → $REMOTE_PATH"
        for f in "${artifacts[@]}"; do
            rclone copy "$f" "$REMOTE_PATH/" || die "rclone copy gagal untuk $(basename "$f")"
        done
        log "Membersihkan offsite lebih tua dari ${OFFSITE_RETENTION_DAYS}d"
        rclone delete --min-age "${OFFSITE_RETENTION_DAYS}d" "${RCLONE_REMOTE%/}/" || true
        rclone rmdirs --leave-root "${RCLONE_REMOTE%/}/" 2>/dev/null || true
    else
        log "WARN: rclone tidak terpasang — offsite dilewati"
    fi
elif [[ "$DO_OFFSITE" -eq 1 ]]; then
    log "WARN: RCLONE_REMOTE kosong — offsite dilewati (set env RCLONE_REMOTE untuk mengaktifkan)"
fi

# 6) Rotasi lokal — hapus artefak lebih tua dari retensi
log "Membersihkan backup lokal lebih tua dari ${LOCAL_RETENTION_DAYS}d"
find "$DEST" -maxdepth 1 -type f \
    \( -name 'db-*.dump' -o -name 'files-*.tar.gz' -o -name 'env-*.txt' -o -name 'SHA256SUMS-*' \) \
    -mtime "+${LOCAL_RETENTION_DAYS}" -print -delete || true

log "Backup SELESAI: ${TS}${SUFFIX}"

# Keluar tidak-nol bila ada langkah yang terdegradasi. Backup yang "selesai"
# tapi tidak utuh harus berbunyi, bukan lewat diam-diam sebagai sukses.
if [[ ${#DEGRADASI[@]} -gt 0 ]]; then
    for catatan in "${DEGRADASI[@]}"; do
        warn "TIDAK UTUH: $catatan"
    done
    exit 1
fi
