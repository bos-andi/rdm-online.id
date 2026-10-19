#!/bin/bash
set -euo pipefail

ACTION="${1:-}"
SUBDOMAIN="${2:-}"
DB_NAME="${3:-}"
DB_USER="${4:-}"
DB_PASS="${5:-}"
DEPLOY_PATH="${6:-}"
SOURCE_ZIP="${7:-}"
CERTBOT_EMAIL="${8:-}"
SQL_TEMPLATE="${9:-${DEPLOY_SQL_TEMPLATE:-/var/www/rdm-online/rdm-online.id/storage/deploy/rdm-base.sql}}"

DOMAIN="rdm-online.id"
BASE_PATH="/var/www/rdm-online"
FQDN="${SUBDOMAIN}.${DOMAIN}"
NGINX_AVAILABLE="/etc/nginx/sites-available/${FQDN}"
NGINX_ENABLED="/etc/nginx/sites-enabled/${FQDN}"
PHP_FPM_SOCKET="${DEPLOY_PHP_FPM_SOCKET:-/run/php/php7.4-fpm.sock}"

log() { echo "[rdm-deploy] $*"; }
die() { echo "[rdm-deploy] $*" >&2; exit 1; }

# Dijalankan sebagai root via sudo oleh www-data, jadi semua argumen wajib divalidasi.
validate_args() {
    [[ "$ACTION" =~ ^(extract|database|import|config|nginx|ssl|suspend|restore|destroy)$ ]] || die "Unknown action: ${ACTION}"
    [[ "$SUBDOMAIN" =~ ^[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?$ ]] || die "Subdomain tidak valid: ${SUBDOMAIN}"
    if [[ -n "$DEPLOY_PATH" && "$DEPLOY_PATH" != "${BASE_PATH}/${SUBDOMAIN}" ]]; then
        die "Deploy path harus ${BASE_PATH}/${SUBDOMAIN}"
    fi
    [[ -L "${BASE_PATH}/${SUBDOMAIN}" ]] && die "Deploy path tidak boleh symlink"
    if [[ -n "$DB_NAME" && ! "$DB_NAME" =~ ^rdm_[a-z0-9_]{1,60}$ ]]; then die "Nama database tidak valid"; fi
    if [[ -n "$DB_USER" && ! "$DB_USER" =~ ^rdm_[a-z0-9_]{1,28}$ ]]; then die "User database tidak valid"; fi
    if [[ -n "$DB_PASS" && ! "$DB_PASS" =~ ^[A-Za-z0-9]{12,128}$ ]]; then die "Password database tidak valid"; fi
    if [[ -n "$CERTBOT_EMAIL" && ! "$CERTBOT_EMAIL" =~ ^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$ ]]; then die "Email certbot tidak valid"; fi
    if [[ "$ACTION" == "extract" ]]; then
        [[ "$SOURCE_ZIP" == *.zip && -f "$SOURCE_ZIP" ]] || die "Source zip tidak ditemukan: ${SOURCE_ZIP}"
    fi
}

ensure_fqdn_not_used_elsewhere() {
    local f
    for f in /etc/nginx/sites-enabled/*; do
        [[ "$(basename "$f")" == "${FQDN}" ]] && continue
        if grep -Eq "^[[:space:]]*server_name[^;]*[[:space:]]${FQDN//./\\.}([[:space:];]|$)" "$f"; then
            die "Domain ${FQDN} sudah dipakai situs lain di server ini."
        fi
    done
}

validate_args

require_args() {
    if [[ -z "$SUBDOMAIN" || -z "$DEPLOY_PATH" ]]; then
        echo "Missing required arguments" >&2
        exit 1
    fi
}

action_extract() {
    require_args
    log "Extracting to ${DEPLOY_PATH}"
    mkdir -p "${DEPLOY_PATH}"
    unzip -oq "${SOURCE_ZIP}" -d "${DEPLOY_PATH}"
    chown -R www-data:www-data "${DEPLOY_PATH}"
    chmod -R 755 "${DEPLOY_PATH}"
    find "${DEPLOY_PATH}" -type f -exec chmod 644 {} \;
    find "${DEPLOY_PATH}" -type d -exec chmod 755 {} \;
}

action_database() {
    if [[ -z "$DB_NAME" || -z "$DB_USER" || -z "$DB_PASS" ]]; then
        echo "Missing database arguments" >&2
        exit 1
    fi
    log "Creating database ${DB_NAME}"
    mysql -e "CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    mysql -e "CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';"
    mysql -e "GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';"
    mysql -e "FLUSH PRIVILEGES;"
}

action_import() {
    if [[ -z "$DB_NAME" ]]; then
        echo "Missing database name" >&2
        exit 1
    fi

    local sql_template="${SQL_TEMPLATE:-}"
    if [[ -z "$sql_template" || ! -f "$sql_template" ]]; then
        echo "SQL template not found: ${sql_template}" >&2
        exit 1
    fi

    local table_count
    table_count="$(mysql -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='${DB_NAME}';")"
    if [[ "${table_count}" -gt 0 ]]; then
        log "Database ${DB_NAME} already has ${table_count} tables, skipping import"
        return
    fi

    log "Importing RDM schema into ${DB_NAME} from ${sql_template}"
    if [[ -n "$DB_USER" && -n "$DB_PASS" ]]; then
        MYSQL_PWD="${DB_PASS}" mysql -h localhost -u "${DB_USER}" "${DB_NAME}" < "${sql_template}"
    else
        die "Import membutuhkan user & password database instance"
    fi
}

action_config() {
    if [[ -z "$DB_NAME" || -z "$DB_USER" || -z "$DB_PASS" ]]; then
        echo "Missing database arguments" >&2
        exit 1
    fi
    log "Writing config.php"
    cat > "${DEPLOY_PATH}/config.php" <<EOF
<?php
defined('BASEPATH') OR exit('No direct script access allowed');
\$host = "localhost";
\$databaseuser = "${DB_USER}";
\$databasename = "${DB_NAME}";
\$dbpassword = "${DB_PASS}";
define("DB_SERVER", \$host);
define("DB_USERNAME", \$databaseuser);
define("DB_PASSWORD", \$dbpassword);
define("DB_DATABASE", \$databasename);
?>
EOF
    chown www-data:www-data "${DEPLOY_PATH}/config.php"
    chmod 640 "${DEPLOY_PATH}/config.php"
}

action_nginx() {
    require_args
    log "Creating nginx config for ${FQDN}"
    ensure_fqdn_not_used_elsewhere
    cat > "${NGINX_AVAILABLE}" <<EOF
server {
    listen 80;
    server_name ${FQDN};

    root ${DEPLOY_PATH};
    index index.php index.html;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    include snippets/block-storage-php.conf;

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:${PHP_FPM_SOCKET};
    }

    location ~ /\.ht {
        deny all;
    }
}
EOF
    ln -sf "${NGINX_AVAILABLE}" "${NGINX_ENABLED}"
    nginx -t
    systemctl reload nginx
}

action_ssl() {
    require_args
    log "Installing SSL via certbot for ${FQDN}"
    certbot --nginx -d "${FQDN}" --non-interactive --agree-tos -m "${CERTBOT_EMAIL}" --redirect
}

action_suspend() {
    require_args
    local suspended_root="${SUSPENDED_ROOT:-/var/www/rdm-online/rdm-online.id/storage/deploy/suspended}"
    log "Suspending ${FQDN}"
    ensure_fqdn_not_used_elsewhere

    if [[ -f "/etc/letsencrypt/live/${FQDN}/fullchain.pem" ]]; then
        cat > "${NGINX_AVAILABLE}" <<EOF
server {
    listen 80;
    server_name ${FQDN};
    return 301 https://\$host\$request_uri;
}

server {
    listen 443 ssl;
    server_name ${FQDN};

    ssl_certificate /etc/letsencrypt/live/${FQDN}/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/${FQDN}/privkey.pem;
    include /etc/letsencrypt/options-ssl-nginx.conf;
    ssl_dhparam /etc/letsencrypt/ssl-dhparams.pem;

    root ${suspended_root};
    index index.html;

    location / {
        try_files /index.html =403;
    }
}
EOF
    else
        cat > "${NGINX_AVAILABLE}" <<EOF
server {
    listen 80;
    server_name ${FQDN};

    root ${suspended_root};
    index index.html;

    location / {
        try_files /index.html =403;
    }
}
EOF
    fi

    ln -sf "${NGINX_AVAILABLE}" "${NGINX_ENABLED}"
    nginx -t
    systemctl reload nginx
}

action_restore() {
    require_args
    log "Restoring ${FQDN}"
    action_nginx

    if [[ -f "/etc/letsencrypt/live/${FQDN}/fullchain.pem" ]]; then
        certbot --nginx -d "${FQDN}" --non-interactive --agree-tos -m "${CERTBOT_EMAIL:-admin@rdm-online.id}" --redirect || true
    fi
}

action_destroy() {
    require_args
    log "Destroying ${FQDN}"

    if [[ -L "${NGINX_ENABLED}" || -f "${NGINX_ENABLED}" ]]; then
        rm -f "${NGINX_ENABLED}"
    fi
    if [[ -f "${NGINX_AVAILABLE}" ]]; then
        rm -f "${NGINX_AVAILABLE}"
    fi

    if [[ -f "/etc/letsencrypt/live/${FQDN}/fullchain.pem" ]]; then
        certbot delete --cert-name "${FQDN}" --non-interactive || true
    fi

    if [[ -n "${DEPLOY_PATH}" && -d "${DEPLOY_PATH}" ]]; then
        rm -rf "${DEPLOY_PATH}"
    fi

    if [[ -n "${DB_NAME}" ]]; then
        mysql -e "DROP DATABASE IF EXISTS \`${DB_NAME}\`;" || true
    fi

    if [[ -n "${DB_USER}" ]]; then
        mysql -e "DROP USER IF EXISTS '${DB_USER}'@'localhost';" || true
        mysql -e "FLUSH PRIVILEGES;" || true
    fi

    if command -v nginx >/dev/null 2>&1; then
        nginx -t && systemctl reload nginx
    fi

    log "Destroy complete for ${FQDN}"
}

case "${ACTION}" in
    extract) action_extract ;;
    database) action_database ;;
    import) action_import ;;
    config) action_config ;;
    nginx) action_nginx ;;
    ssl) action_ssl ;;
    suspend) action_suspend ;;
    restore) action_restore ;;
    destroy) action_destroy ;;
    *)
        echo "Unknown action: ${ACTION}" >&2
        exit 1
        ;;
esac
