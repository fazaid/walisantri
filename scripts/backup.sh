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

log() { echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*"; }
die() { echo "[$(date '+%Y-%m-%d %H:%M:%S')] ERROR: $*" >&2; exit 1; }

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
    tar czf "$FILES_FILE" -C "$APP_DIR" \
        $( [[ -d "$APP_DIR/storage/app/public" ]]  && echo storage/app/public ) \
        $( [[ -d "$APP_DIR/storage/app/private" ]] && echo storage/app/private ) \
        || die "tar storage gagal"
    chmod 600 "$FILES_FILE"
    artifacts+=("$FILES_FILE")

    # 3) .env (berisi APP_KEY & rahasia — bucket WAJIB privat / rclone crypt)
    cp "$APP_DIR/.env" "$ENV_FILE"
    chmod 600 "$ENV_FILE"
    artifacts+=("$ENV_FILE")
fi

# 4) Checksum integritas
( cd "$DEST" && sha256sum "$(basename "$DB_FILE")" \
    $( [[ "$DB_ONLY" -eq 0 ]] && basename "$FILES_FILE" ) \
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
