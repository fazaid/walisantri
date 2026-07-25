#!/usr/bin/env bash
#
# restore.sh — Restore Walisantri dari backup (database + file upload)
#
# Mengutamakan KESELAMATAN: sebelum menimpa apa pun, skrip membuat snapshot
# pengaman dari kondisi SAAT INI (pg_dump + tar), memverifikasi checksum
# artefak, lalu meminta konfirmasi eksplisit. Restore yang salah tetap bisa
# dibalik dari snapshot pengaman.
#
# Pemakaian:
#   scripts/restore.sh latest                  # restore backup lokal terbaru
#   scripts/restore.sh 20260725-020000         # restore timestamp tertentu (lokal)
#   scripts/restore.sh --from-offsite latest   # unduh dari rclone dulu, lalu restore
#   scripts/restore.sh --list                  # tampilkan backup yang tersedia
#   scripts/restore.sh --db-only latest        # hanya restore database
#   scripts/restore.sh --yes latest            # lewati prompt konfirmasi (hati-hati)
#
# Konfigurasi env: APP_DIR, BACKUP_DIR, RCLONE_REMOTE (lihat backup.sh)
#
set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/walisantri}"
BACKUP_DIR="${BACKUP_DIR:-/home/fazaweb/backups/walisantri}"
RCLONE_REMOTE="${RCLONE_REMOTE:-}"
WORKER="${WORKER:-walisantri-worker}"

FROM_OFFSITE=0
DB_ONLY=0
ASSUME_YES=0
LIST_ONLY=0
SELECTOR=""

while [[ $# -gt 0 ]]; do
    case "$1" in
        --from-offsite) FROM_OFFSITE=1 ;;
        --db-only)      DB_ONLY=1 ;;
        --yes|-y)       ASSUME_YES=1 ;;
        --list|-l)      LIST_ONLY=1 ;;
        -h|--help)      grep '^#' "$0" | sed 's/^# \{0,1\}//'; exit 0 ;;
        -*)             echo "Argumen tak dikenal: $1" >&2; exit 2 ;;
        *)              SELECTOR="$1" ;;
    esac
    shift
done

log()  { echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*"; }
die()  { echo "[$(date '+%Y-%m-%d %H:%M:%S')] ERROR: $*" >&2; exit 1; }
step() { echo; echo "==> $*"; }

env_get() {
    local v
    v="$(grep -E "^$1=" "$APP_DIR/.env" | tail -n1 | cut -d= -f2- || true)"
    v="${v%$'\r'}"
    v="$(echo "$v" | sed -e 's/^[[:space:]]*//' -e 's/[[:space:]]*$//')"
    v="${v%\"}"; v="${v#\"}"; v="${v%\'}"; v="${v#\'}"
    echo "$v"
}

[[ -f "$APP_DIR/.env" ]] || die "File .env tidak ditemukan di $APP_DIR"
DB_DATABASE="$(env_get DB_DATABASE)"; [[ -n "$DB_DATABASE" ]] || DB_DATABASE="$(env_get CENTRAL_DB_DATABASE)"
DB_USERNAME="$(env_get DB_USERNAME)"; [[ -n "$DB_USERNAME" ]] || DB_USERNAME="$(env_get CENTRAL_DB_USERNAME)"
DB_PASSWORD="$(env_get DB_PASSWORD)"; [[ -n "$DB_PASSWORD" ]] || DB_PASSWORD="$(env_get CENTRAL_DB_PASSWORD)"
DB_HOST="$(env_get DB_HOST)"; [[ -n "$DB_HOST" ]] || DB_HOST="127.0.0.1"
DB_PORT="$(env_get DB_PORT)"; [[ -n "$DB_PORT" ]] || DB_PORT="5432"
export PGPASSWORD="$DB_PASSWORD"
PSQL=(psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USERNAME" -d "$DB_DATABASE")

mkdir -p "$BACKUP_DIR"

list_local() {
    find "$BACKUP_DIR" -maxdepth 1 -type f -name 'db-*.dump' -printf '%f\n' 2>/dev/null \
        | sed -E 's/^db-(.*)\.dump$/\1/' | sort -r
}

if [[ "$LIST_ONLY" -eq 1 ]]; then
    step "Backup lokal tersedia di $BACKUP_DIR:"
    list_local || true
    if [[ -n "$RCLONE_REMOTE" ]] && command -v rclone >/dev/null; then
        step "Backup offsite ($RCLONE_REMOTE):"
        rclone lsf --include 'db-*.dump' -R "${RCLONE_REMOTE%/}/" 2>/dev/null | sort -r || true
    fi
    exit 0
fi

[[ -n "$SELECTOR" ]] || die "Sebutkan timestamp atau 'latest'. Lihat: $0 --list"

# Unduh dari offsite bila diminta.
if [[ "$FROM_OFFSITE" -eq 1 ]]; then
    command -v rclone >/dev/null || die "rclone tidak terpasang"
    [[ -n "$RCLONE_REMOTE" ]] || die "RCLONE_REMOTE kosong"
    step "Mengunduh artefak dari offsite ($SELECTOR)"
    if [[ "$SELECTOR" == "latest" ]]; then
        SELECTOR="$(rclone lsf --include 'db-*.dump' -R "${RCLONE_REMOTE%/}/" 2>/dev/null \
            | sed -E 's#.*/##; s/^db-(.*)\.dump$/\1/' | sort -r | head -n1)"
        [[ -n "$SELECTOR" ]] || die "Tidak ada backup di offsite"
        log "Timestamp offsite terbaru: $SELECTOR"
    fi
    rclone copy --include "*-${SELECTOR}.*" --include "SHA256SUMS-${SELECTOR}*" \
        -R "${RCLONE_REMOTE%/}/" "$BACKUP_DIR/" || die "Gagal mengunduh dari offsite"
fi

if [[ "$SELECTOR" == "latest" ]]; then
    SELECTOR="$(list_local | head -n1)"
    [[ -n "$SELECTOR" ]] || die "Tidak ada backup lokal di $BACKUP_DIR"
fi

DB_FILE="$BACKUP_DIR/db-${SELECTOR}.dump"
FILES_FILE="$BACKUP_DIR/files-${SELECTOR}.tar.gz"
SUMS_FILE="$BACKUP_DIR/SHA256SUMS-${SELECTOR}"

[[ -f "$DB_FILE" ]] || die "Artefak DB tidak ditemukan: $DB_FILE"

step "Verifikasi integritas (checksum)"
if [[ -f "$SUMS_FILE" ]]; then
    ( cd "$BACKUP_DIR" && sha256sum -c --ignore-missing "$(basename "$SUMS_FILE")" ) \
        || die "Checksum TIDAK cocok — artefak rusak, restore dibatalkan"
    log "Checksum OK"
else
    log "WARN: file SHA256SUMS tidak ada — lewati verifikasi"
fi

echo
echo "  Target restore : DB '$DB_DATABASE' @ $DB_HOST:$DB_PORT"
echo "  Sumber DB      : $(basename "$DB_FILE")"
[[ "$DB_ONLY" -eq 0 && -f "$FILES_FILE" ]] && echo "  Sumber file    : $(basename "$FILES_FILE")"
echo "  App dir        : $APP_DIR"
echo
echo "  ⚠️  Operasi ini akan MENIMPA database & file saat ini."
echo "     Snapshot pengaman kondisi saat ini akan dibuat lebih dulu."

if [[ "$ASSUME_YES" -ne 1 ]]; then
    read -r -p "Ketik nama database ('$DB_DATABASE') untuk melanjutkan: " CONFIRM
    [[ "$CONFIRM" == "$DB_DATABASE" ]] || die "Konfirmasi tidak cocok — dibatalkan"
fi

# --- Snapshot pengaman kondisi SAAT INI (selalu, sebelum apa pun diubah) ---
SAFETY_DIR="$BACKUP_DIR/pre-restore-$(date '+%Y%m%d-%H%M%S')"
mkdir -p "$SAFETY_DIR"
step "Membuat snapshot pengaman → $SAFETY_DIR"
PGPASSWORD="$DB_PASSWORD" pg_dump -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USERNAME" \
    -Fc --no-owner --no-privileges -f "$SAFETY_DIR/db-current.dump" "$DB_DATABASE" \
    || die "Snapshot pengaman DB gagal — restore dibatalkan (belum ada perubahan)"
if [[ "$DB_ONLY" -eq 0 ]]; then
    tar czf "$SAFETY_DIR/files-current.tar.gz" -C "$APP_DIR" \
        $( [[ -d "$APP_DIR/storage/app/public" ]]  && echo storage/app/public ) \
        $( [[ -d "$APP_DIR/storage/app/private" ]] && echo storage/app/private ) || true
fi
log "Snapshot pengaman siap. Untuk membatalkan hasil restore nanti:"
log "  pg_restore --clean --if-exists --no-owner -d $DB_DATABASE $SAFETY_DIR/db-current.dump"

# --- Maintenance mode + hentikan worker ---
step "Mengaktifkan maintenance mode & menghentikan worker"
( cd "$APP_DIR" && php artisan down --render="errors::503" ) || log "WARN: artisan down gagal (lanjut)"
sudo supervisorctl stop "$WORKER" 2>/dev/null || log "WARN: tidak bisa stop worker '$WORKER' (lanjut)"

restore_failed() {
    log "Restore GAGAL. App tetap di maintenance mode."
    log "Pulihkan kondisi semula dari snapshot: $SAFETY_DIR"
}
trap 'restore_failed' ERR

# --- Restore database ---
step "Restore database (pg_restore --clean --if-exists)"
PGPASSWORD="$DB_PASSWORD" pg_restore \
    -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USERNAME" -d "$DB_DATABASE" \
    --clean --if-exists --no-owner --no-privileges \
    "$DB_FILE"
log "Database ter-restore"

# --- Restore file ---
if [[ "$DB_ONLY" -eq 0 && -f "$FILES_FILE" ]]; then
    step "Restore file upload (storage/app)"
    tar xzf "$FILES_FILE" -C "$APP_DIR"
    log "File ter-restore"
fi

trap - ERR

# --- Pemulihan aplikasi ---
step "Menyegarkan cache & menghidupkan kembali aplikasi"
( cd "$APP_DIR" && php artisan optimize:clear \
    && php artisan config:cache && php artisan route:cache && php artisan view:cache ) || true
sudo supervisorctl start "$WORKER" 2>/dev/null || log "WARN: tidak bisa start worker '$WORKER'"
( cd "$APP_DIR" && php artisan up ) || log "WARN: artisan up gagal — jalankan manual"

# --- Sanity check ---
step "Sanity check"
( cd "$APP_DIR" && php artisan migrate:status | tail -n 5 ) || true
"${PSQL[@]}" -Atc "SELECT 'pesantrens='||count(*) FROM pesantrens;" 2>/dev/null || true
"${PSQL[@]}" -Atc "SELECT 'users='||count(*) FROM users;" 2>/dev/null || true

echo
log "RESTORE SELESAI dari '$SELECTOR'."
log "Snapshot pengaman kondisi sebelumnya: $SAFETY_DIR (hapus setelah yakin)."
