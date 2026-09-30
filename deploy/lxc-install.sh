#!/usr/bin/env bash
# Розгортання Zippy CRM (zstore) в LXC-контейнері (Ubuntu 22.04/24.04 або Debian 12).
# Запускати ВСЕРЕДИНІ контейнера від root.
#
# Параметри (можна перевизначити через env):
#   DB_NAME    = zstore
#   DB_USER    = zstore
#   DB_PASS    = zstore_pw  (замінити на свій)
#   SITE_DIR   = /var/www/zstore
#   SRC_DIR    = /root/zstore
#   REPO_URL   = https://github.com/leon-mbs/zstore.git (клонується, якщо SRC_DIR пустий)
#
# Якщо в SRC_DIR ще немає репо — воно буде склоноване з REPO_URL, після чого
# автоматично застосуються два патчі: fix VK autoshift + enable_retail.sql.

set -euo pipefail

DB_NAME="${DB_NAME:-zstore}"
DB_USER="${DB_USER:-zstore}"
DB_PASS="${DB_PASS:-zstore_pw}"
SITE_DIR="${SITE_DIR:-/var/www/zstore}"
SRC_DIR="${SRC_DIR:-/root/zstore}"
REPO_URL="${REPO_URL:-https://github.com/leon-mbs/zstore.git}"

echo "==> 1/8 Оновлення пакетів + залежності"
export DEBIAN_FRONTEND=noninteractive
apt-get update -y
apt-get install -y \
    apache2 mariadb-server \
    php php-cli php-mysql php-mbstring php-xml php-curl php-gd php-zip php-intl php-bcmath php-gmp \
    libapache2-mod-php \
    composer unzip curl ca-certificates cron git python3 rsync

echo "==> 2/8 Клон репо (якщо ще не витягнуто)"
if [[ ! -d "$SRC_DIR/www" || ! -f "$SRC_DIR/db/db.sql" ]]; then
    rm -rf "$SRC_DIR"
    git clone --depth 1 "$REPO_URL" "$SRC_DIR"
fi

echo "==> 2.1/8 Патч VK autoshift у crontask.php"
CRONFILE="$SRC_DIR/www/app/entity/crontask.php"
if [[ -f "$CRONFILE" ]] && ! grep -q "type.*==.*'vk'" "$CRONFILE"; then
    python3 - "$CRONFILE" <<'PY'
import sys
p = sys.argv[1]
s = open(p).read()
needle = "if($msg['type']=='cb') {\n                       $b=  \\App\\Modules\\CB\\CheckBox::autoshift($msg['pos_id']) ;\n                    }"
add    = needle + "\n                    if($msg['type']=='vk') {\n                       $b=  \\App\\Modules\\VK\\VK::autoshift($msg['pos_id']) ;\n                    }"
if needle in s and "type']=='vk'" not in s:
    open(p, 'w').write(s.replace(needle, add))
    print("patched")
else:
    print("skip (already patched or pattern not found)")
PY
fi

echo "==> 2.2/8 SQL: увімкнути документ POSCheck"
mkdir -p "$SRC_DIR/db/update"
cat >"$SRC_DIR/db/update/enable_retail.sql" <<'SQL'
SET NAMES 'utf8mb4';
UPDATE metadata SET disabled=0 WHERE meta_name='POSCheck';
SQL

echo "==> 3/8 Apache: mod_rewrite + vhost"
a2enmod rewrite
cat >/etc/apache2/sites-available/zstore.conf <<CONF
<VirtualHost *:80>
    ServerName zstore.local
    DocumentRoot ${SITE_DIR}

    <Directory ${SITE_DIR}>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    DirectoryIndex index.php index.html

    ErrorLog  /var/log/apache2/zstore.error.log
    CustomLog /var/log/apache2/zstore.access.log combined
</VirtualHost>
CONF
a2dissite 000-default.conf >/dev/null 2>&1 || true
a2ensite zstore.conf

echo "==> 4/8 MariaDB: старт + база + користувач"
systemctl enable --now mariadb
# Якщо БД вже існує з попереднього запуску — перестворюємо чисту,
# бо db.sql використовує CREATE TABLE без IF NOT EXISTS.
mysql -uroot <<SQL
DROP DATABASE IF EXISTS \`${DB_NAME}\`;
CREATE DATABASE \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
SQL

echo "==> 5/8 Імпорт схеми та міграції"
mysql -uroot "${DB_NAME}" < "${SRC_DIR}/db/db.sql"
mysql -uroot "${DB_NAME}" < "${SRC_DIR}/db/update/enable_retail.sql"

echo "==> 6/8 Копіювання коду в ${SITE_DIR}"
mkdir -p "${SITE_DIR}"
rsync -a --delete "${SRC_DIR}/www/" "${SITE_DIR}/"

mkdir -p "${SITE_DIR}/upload" "${SITE_DIR}/logs"
chown -R www-data:www-data "${SITE_DIR}"
find "${SITE_DIR}" -type d -exec chmod 755 {} \;
find "${SITE_DIR}" -type f -exec chmod 644 {} \;
chmod -R ug+rwX "${SITE_DIR}/upload" "${SITE_DIR}/logs"

echo "==> 7/8 config.php + composer install + cron"
cat >"${SITE_DIR}/config/config.php" <<PHPCFG
<?php
\$_config = array('common'=>[], 'db'=>[], 'smtp'=>[]);
\$_config['common']['loglevel'] = 200;
\$_config['db']['host'] = 'localhost:3306';
\$_config['db']['name'] = '${DB_NAME}';
\$_config['db']['user'] = '${DB_USER}';
\$_config['db']['pass'] = '${DB_PASS}';
\$_config['smtp']['usesmtp'] = false;
\$_config['smtp']['host'] = '';
\$_config['smtp']['port'] = 587;
\$_config['smtp']['user'] = '';
\$_config['smtp']['emailfrom'] = '';
\$_config['smtp']['pass'] = '';
\$_config['smtp']['tls'] = true;
PHPCFG
chown www-data:www-data "${SITE_DIR}/config/config.php"

if [[ ! -d "${SITE_DIR}/vendor" ]]; then
    # runuser є в coreutils; sudo у чистому Debian LXC може бути відсутній
    runuser -u www-data -- env COMPOSER_ALLOW_SUPERUSER=1 composer install \
        --no-dev --no-interaction --optimize-autoloader \
        -d "${SITE_DIR}"
fi

cat >/etc/cron.d/zstore <<CRON
*/5 * * * * www-data /usr/bin/php ${SITE_DIR}/crontab.php >/dev/null 2>&1
CRON
chmod 644 /etc/cron.d/zstore
systemctl restart cron

echo "==> 8/8 Restart Apache"
systemctl enable --now apache2
systemctl restart apache2

IP=$(hostname -I | awk '{print $1}')
cat <<DONE

===============================================
Готово. Zippy CRM піднято.
   URL:     http://${IP}/
   Логін:   admin
   Пароль:  admin  (обов'язково змінити після входу)

Далі в UI:
  1. Адмін → Модулі: тип фіскалізації = ВчасноКаса.
  2. Довідники → Каси, рахунки: створити "Готівка" (beznal=0) і "Термінал" (beznal=1).
  3. POS-термінали: додати термінал (vktoken, autoshift=23:00 тощо).
  4. Сервіс → Програма лояльності: налаштувати бонуси/промокоди.
  5. Ролі: касиру дати доступ до POSCheck, Warranty, ReturnIssue,
     PayList, EndDay і модуль vkassa.

Крон: /etc/cron.d/zstore (кожні 5 хв). Автозакриття зміни ВчасноКаса
працює через VK::autoshift (правка crontask.php застосована автоматично).
===============================================
DONE
