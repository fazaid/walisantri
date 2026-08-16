#!/usr/bin/env bash
#
# Pasang/segarkan daftar rentang IP Cloudflare untuk modul real_ip Nginx.
#
# KENAPA ADA: seluruh trafik production lewat proxy Cloudflare, sehingga tanpa
# konfigurasi ini REMOTE_ADDR yang dilihat PHP adalah IP edge Cloudflare — bukan
# pengunjung. Akibatnya setiap rate limiter di AppServiceProvider (register 5/jam,
# check-slug 30/menit, magic-link 30/menit, throttle login) mengunci per edge dan
# dipakai bersama semua pengunjung yang lewat edge itu, dan activity_logs mencatat
# IP yang tidak menunjuk siapa pun. Terukur di production 16 Agustus 2026 (PRD §6.1).
#
# Sengaja diselesaikan di Nginx, BUKAN dengan TrustProxies di Laravel: dengan
# real_ip, REMOTE_ADDR sudah benar sejak lapisan bawah, sehingga tidak perlu
# mempercayai header X-Forwarded-For yang bisa dipalsukan.
#
# Pemakaian (sebagai root di VPS):
#     /var/www/walisantri/scripts/cloudflare-realip.sh
#
# Dijadwalkan bulanan lewat crontab root — rentang Cloudflare jarang berubah,
# tapi tidak pernah tetap.

set -euo pipefail

TUJUAN=${TUJUAN:-/etc/nginx/conf.d/cloudflare-realip.conf}
SEMENTARA=$(mktemp)
trap 'rm -f "$SEMENTARA"' EXIT

{
    echo "# Dihasilkan oleh scripts/cloudflare-realip.sh — JANGAN disunting manual."
    echo "# Sumber: https://www.cloudflare.com/ips-v4 dan https://www.cloudflare.com/ips-v6"
    echo "# Diperbarui: $(date -Is)"
    echo

    for sumber in https://www.cloudflare.com/ips-v4 https://www.cloudflare.com/ips-v6; do
        curl -fsS --max-time 20 "$sumber" | while read -r rentang; do
            [ -n "$rentang" ] && echo "set_real_ip_from ${rentang};"
        done
    done

    echo
    echo "real_ip_header CF-Connecting-IP;"
} >"$SEMENTARA"

# Jaring pengaman: unduhan yang gagal/terpotong menghasilkan daftar pendek. Menimpa
# konfigurasi yang sudah benar dengan berkas rusak jauh lebih buruk daripada tidak
# memperbarui sama sekali — dan kalau ini jalan dari cron, tidak ada yang menonton.
JUMLAH=$(grep -c '^set_real_ip_from' "$SEMENTARA" || true)

if [ "${JUMLAH:-0}" -lt 10 ]; then
    echo "GAGAL: hanya ${JUMLAH:-0} rentang terunduh — konfigurasi lama dipertahankan." >&2
    exit 1
fi

install -m 0644 "$SEMENTARA" "$TUJUAN"

nginx -t
systemctl reload nginx

echo "OK: ${JUMLAH} rentang Cloudflare dipasang di ${TUJUAN}, Nginx di-reload."
