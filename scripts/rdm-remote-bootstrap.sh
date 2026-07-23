#!/bin/bash
# Bootstrap VPS remote untuk RDM Online multi-server deploy.
# Jalankan di VPS BARU (Biznet/SumoPod/dll) sebagai root.

set -euo pipefail

DEPLOY_BASE="${DEPLOY_BASE:-/var/www/rdm-online}"
OPT_ROOT="${OPT_ROOT:-/opt/rdm-online}"
DOMAIN="${DOMAIN:-rdm-online.id}"
CERTBOT_EMAIL="${CERTBOT_EMAIL:-admin@rdm-online.id}"

log() { echo "[rdm-bootstrap] $*"; }

log "Update paket & install stack..."
export DEBIAN_FRONTEND=noninteractive
apt-get update -qq
apt-get install -y nginx mysql-server certbot python3-certbot-nginx unzip rsync

if ! command -v php7.4 >/dev/null 2>&1; then
    apt-get install -y software-properties-common
    add-apt-repository -y ppa:ondrej/php
    apt-get update -qq
    apt-get install -y php7.4-fpm php7.4-mysql php7.4-mbstring php7.4-xml php7.4-curl php7.4-zip php7.4-gd
fi

log "Buat direktori deploy..."
mkdir -p "${DEPLOY_BASE}" "${OPT_ROOT}/scripts" "${OPT_ROOT}/storage/deploy/suspended"
chown -R www-data:www-data "${DEPLOY_BASE}"

DEPLOY_USER="root"
if id ubuntu >/dev/null 2>&1; then
    DEPLOY_USER="ubuntu"
fi

chown -R "${DEPLOY_USER}:www-data" "${OPT_ROOT}"
chmod -R ug+rwx "${OPT_ROOT}"

if [[ ! -f /etc/nginx/snippets/block-storage-php.conf ]]; then
    cat > /etc/nginx/snippets/block-storage-php.conf <<'EOF'
location ~ ^/storage/.*\.php$ {
    deny all;
}
EOF
fi

if [[ ! -f "${OPT_ROOT}/storage/deploy/suspended/index.html" ]]; then
    cat > "${OPT_ROOT}/storage/deploy/suspended/index.html" <<'EOF'
<!DOCTYPE html>
<html lang="id"><head><meta charset="utf-8"><title>Layanan Ditangguhkan</title></head>
<body style="font-family:sans-serif;text-align:center;padding:4rem;">
<h1>Layanan Ditangguhkan</h1>
<p>Hubungi administrator RDM Online untuk memperpanjang langganan.</p>
</body></html>
EOF
fi

SUDOERS="/etc/sudoers.d/rdm-deploy"
if [[ ! -f "${SUDOERS}" ]]; then
    cat > "${SUDOERS}" <<EOF
# RDM Online remote deploy
root ALL=(ALL) NOPASSWD: ${OPT_ROOT}/scripts/rdm-deploy.sh
${DEPLOY_USER} ALL=(ALL) NOPASSWD: ${OPT_ROOT}/scripts/rdm-deploy.sh
EOF
    chmod 440 "${SUDOERS}"
    visudo -cf "${SUDOERS}"
fi

log "Selesai bootstrap dasar."
log ""
log "Langkah berikutnya dari server panel rdm-online.id:"
log "  1. Tambah SSH public key panel ke VPS ini (authorized_keys)"
log "  2. Simpan private key di: storage/deploy/keys/rdm-deploy (chmod 600, owner www-data)"
log "  3. Daftar server di Admin → VPS (IP, SSH user, path key)"
log "  4. Jalankan: php artisan rdm:sync-vps ID-02"
log ""
log "Path remote default:"
log "  Script : ${OPT_ROOT}/scripts/rdm-deploy.sh"
log "  ZIP    : ${OPT_ROOT}/RDMhosting.zip"
log "  SQL    : ${OPT_ROOT}/storage/deploy/rdm-base.sql"
log "  RDM    : ${DEPLOY_BASE}/{subdomain}"
