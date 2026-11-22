#!/usr/bin/env bash
# Provision a Work Atlas server (PLAN M22, A12): Ubuntu 24.04 LTS on a Hetzner Cloud VPS.
#
# Idempotent: safe to run again after a change to this script or the files in ops/. Run as root from
# a checkout of the repository, after copying your SSH public key to root:
#
#   DOMAIN=example.org ./ops/provision.sh            # production
#   DOMAIN=staging.example.org ./ops/provision.sh    # staging (fake data only, basic auth)
#
# What it does: system updates and unattended upgrades, a `deploy` user with root's SSH keys,
# SSH keys only (no passwords, no root login), a firewall (22, 80, 443), PHP 8.4-FPM, Nginx,
# PostgreSQL 18, Redis, Node.js 22, Composer, supervisor (Horizon, SSR), cron (scheduler, backup),
# age and rclone for the backups, and fail2ban. It never touches the app's .env or any secret: those
# are placed by hand (docs/ops/server.md).
set -euo pipefail

: "${DOMAIN:?Set DOMAIN, e.g. DOMAIN=example.org}"
[ "$(id -u)" -eq 0 ] || { echo "Run as root." >&2; exit 1; }

OPS_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
APP_DIR=/var/www/work-atlas
DB_NAME="${DB_NAME:-work_atlas}"
DB_USER="${DB_USER:-work_atlas}"
PHP=8.4
NODE_MAJOR=22
PG_MAJOR=18
export DEBIAN_FRONTEND=noninteractive

log() { printf '\n==> %s\n' "$*"; }

log "Time zone UTC (the app stores UTC and shows Asia/Dhaka)"
timedatectl set-timezone UTC

log "Package sources: PHP (ondrej), PostgreSQL (PGDG), Node.js (NodeSource)"
apt-get update -q
apt-get install -y -q ca-certificates curl gnupg software-properties-common lsb-release
install -d -m 0755 /etc/apt/keyrings
if ! grep -rq "ondrej/php" /etc/apt/sources.list.d/ 2>/dev/null; then
    add-apt-repository -y ppa:ondrej/php
fi
if [ ! -f /etc/apt/keyrings/postgresql.gpg ]; then
    curl -fsSL https://www.postgresql.org/media/keys/ACCC4CF8.asc | gpg --dearmor -o /etc/apt/keyrings/postgresql.gpg
fi
echo "deb [signed-by=/etc/apt/keyrings/postgresql.gpg] https://apt.postgresql.org/pub/repos/apt $(lsb_release -cs)-pgdg main" \
    > /etc/apt/sources.list.d/pgdg.list
if [ ! -f /etc/apt/keyrings/nodesource.gpg ]; then
    curl -fsSL https://deb.nodesource.com/gpgkey/nodesource-repo.gpg.key | gpg --dearmor -o /etc/apt/keyrings/nodesource.gpg
fi
echo "deb [signed-by=/etc/apt/keyrings/nodesource.gpg] https://deb.nodesource.com/node_${NODE_MAJOR}.x nodistro main" \
    > /etc/apt/sources.list.d/nodesource.list
apt-get update -q

log "Packages"
apt-get upgrade -y -q
apt-get install -y -q \
    nginx supervisor redis-server ufw fail2ban unattended-upgrades git unzip age rclone \
    "postgresql-${PG_MAJOR}" "postgresql-client-${PG_MAJOR}" nodejs \
    "php${PHP}-fpm" "php${PHP}-cli" "php${PHP}-pgsql" "php${PHP}-redis" "php${PHP}-intl" \
    "php${PHP}-mbstring" "php${PHP}-xml" "php${PHP}-curl" "php${PHP}-zip" "php${PHP}-bcmath" \
    "php${PHP}-gd" "php${PHP}-opcache"
dpkg-reconfigure -f noninteractive unattended-upgrades

log "Composer (checksum verified)"
if ! command -v composer >/dev/null; then
    expected="$(curl -fsSL https://composer.github.io/installer.sig)"
    curl -fsSL https://getcomposer.org/installer -o /tmp/composer-setup.php
    actual="$(sha384sum /tmp/composer-setup.php | cut -d' ' -f1)"
    [ "${expected}" = "${actual}" ] || { echo "Composer installer checksum mismatch" >&2; exit 1; }
    php /tmp/composer-setup.php --quiet --install-dir=/usr/local/bin --filename=composer
    rm -f /tmp/composer-setup.php
fi

log "The deploy user (SSH keys copied from root)"
if ! id deploy >/dev/null 2>&1; then
    adduser --disabled-password --gecos "" deploy
fi
install -d -m 0700 -o deploy -g deploy /home/deploy/.ssh
# Only the first time: a later run must not wipe keys added since (the GitHub deploy key).
if [ -f /root/.ssh/authorized_keys ] && [ ! -s /home/deploy/.ssh/authorized_keys ]; then
    install -m 0600 -o deploy -g deploy /root/.ssh/authorized_keys /home/deploy/.ssh/authorized_keys
fi
# Deployer reloads PHP-FPM after a release; nothing else runs as root.
cat > /etc/sudoers.d/work-atlas <<SUDO
deploy ALL=(root) NOPASSWD: /usr/bin/systemctl reload php${PHP}-fpm
SUDO
chmod 0440 /etc/sudoers.d/work-atlas
visudo -cf /etc/sudoers.d/work-atlas

log "SSH: keys only, no root login"
# Never lock everyone out: the deploy user must have a key before root login is turned off.
[ -s /home/deploy/.ssh/authorized_keys ] || { echo "No SSH key for deploy; add one to /root/.ssh/authorized_keys first." >&2; exit 1; }
cat > /etc/ssh/sshd_config.d/10-work-atlas.conf <<'SSHD'
PasswordAuthentication no
KbdInteractiveAuthentication no
PermitRootLogin no
PubkeyAuthentication yes
SSHD
sshd -t
systemctl reload ssh

log "Firewall"
ufw default deny incoming
ufw default allow outgoing
ufw allow 22/tcp
ufw allow 80/tcp
ufw allow 443/tcp
ufw --force enable

log "PostgreSQL ${PG_MAJOR}: role and database (password set by hand, see docs/ops/server.md)"
if ! sudo -u postgres psql -tAc "select 1 from pg_roles where rolname = '${DB_USER}'" | grep -q 1; then
    sudo -u postgres createuser "${DB_USER}"
fi
if ! sudo -u postgres psql -tAc "select 1 from pg_database where datname = '${DB_NAME}'" | grep -q 1; then
    sudo -u postgres createdb --owner="${DB_USER}" "${DB_NAME}"
fi
sudo -u postgres psql -d "${DB_NAME}" -c "create extension if not exists pg_trgm" >/dev/null

log "Redis: local only"
sed -i 's/^#\? *bind .*/bind 127.0.0.1 -::1/' /etc/redis/redis.conf
systemctl restart redis-server

log "PHP-FPM pool for the app (runs as deploy)"
cat > "/etc/php/${PHP}/fpm/pool.d/work-atlas.conf" <<POOL
[work-atlas]
user = deploy
group = deploy
listen = /run/php/php${PHP}-fpm-work-atlas.sock
listen.owner = www-data
listen.group = www-data
pm = dynamic
pm.max_children = 10
pm.start_servers = 3
pm.min_spare_servers = 2
pm.max_spare_servers = 5
pm.max_requests = 500
php_admin_value[expose_php] = Off
POOL
cat > "/etc/php/${PHP}/fpm/conf.d/90-work-atlas.ini" <<'INI'
opcache.enable=1
opcache.memory_consumption=192
opcache.validate_timestamps=0
expose_php=Off
INI
systemctl restart "php${PHP}-fpm"

log "App directories and logs"
install -d -m 0755 -o deploy -g deploy "${APP_DIR}" "${APP_DIR}/shared" "${APP_DIR}/shared/storage"
install -d -m 0755 -o deploy -g deploy /var/log/work-atlas
install -d -m 0750 -o root -g postgres /etc/work-atlas
touch /var/log/work-atlas/backup.log
chown postgres:deploy /var/log/work-atlas/backup.log

log "Nginx"
install -d /etc/ssl/cloudflare
{
    echo "# Cloudflare's address ranges, refreshed by ops/provision.sh."
    for list in ips-v4 ips-v6; do
        curl -fsSL "https://www.cloudflare.com/${list}" | sed 's/^\(.*\)$/set_real_ip_from \1;/'
    done
    echo "real_ip_header CF-Connecting-IP;"
} > /etc/nginx/snippets/cloudflare-real-ip.conf
install -m 0644 "${OPS_DIR}/nginx/work-atlas-app.conf" /etc/nginx/snippets/work-atlas-app.conf
sed "s/__DOMAIN__/${DOMAIN}/g" "${OPS_DIR}/nginx/work-atlas.conf" > /etc/nginx/sites-available/work-atlas
ln -sf /etc/nginx/sites-available/work-atlas /etc/nginx/sites-enabled/work-atlas
rm -f /etc/nginx/sites-enabled/default
if [ -f /etc/ssl/cloudflare/origin.pem ] && [ -f /etc/ssl/cloudflare/origin.key ]; then
    nginx -t
    systemctl reload nginx
else
    echo "!! Place the Cloudflare origin certificate in /etc/ssl/cloudflare/origin.{pem,key}, then: nginx -t && systemctl reload nginx"
fi

log "Supervisor (Horizon, SSR), cron (scheduler, backup), backup scripts"
install -m 0644 "${OPS_DIR}/supervisor/work-atlas.conf" /etc/supervisor/conf.d/work-atlas.conf
install -m 0644 "${OPS_DIR}/cron/work-atlas" /etc/cron.d/work-atlas
install -m 0755 "${OPS_DIR}/backup/work-atlas-backup" /usr/local/bin/work-atlas-backup
install -m 0755 "${OPS_DIR}/backup/work-atlas-restore-test" /usr/local/bin/work-atlas-restore-test
supervisorctl reread >/dev/null
if [ -L "${APP_DIR}/current" ]; then
    supervisorctl update
fi

log "fail2ban (SSH)"
systemctl enable --now fail2ban

log "Done. Next steps by hand: docs/ops/server.md"
