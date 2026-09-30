# Розгортання Zippy CRM (zstore) у LXC на Proxmox

Дві дії: на **хості Proxmox** створити контейнер і залити код, потім
всередині **контейнера** запустити `lxc-install.sh`.

## 1. На хості Proxmox

Приклад для Ubuntu 24.04 (шаблон уже має бути завантажений — інакше
`pveam update && pveam available | grep ubuntu-24.04` і `pveam download local <файл>`):

```bash
CTID=200
TEMPLATE=local:vztmpl/ubuntu-24.04-standard_24.04-2_amd64.tar.zst
STORAGE=local-lvm
BRIDGE=vmbr0

pct create $CTID $TEMPLATE \
  --hostname zstore \
  --cores 2 --memory 2048 --swap 512 \
  --rootfs ${STORAGE}:8 \
  --net0 name=eth0,bridge=${BRIDGE},ip=dhcp \
  --features nesting=1 \
  --unprivileged 1 \
  --password 'RootPass!' \
  --onboot 1 --start 1

# Залити код у контейнер (варіант 1: з локальної копії репо)
pct push $CTID /root/zstore.tar.gz /root/zstore.tar.gz
pct exec $CTID -- bash -c 'mkdir -p /root/zstore && tar -xzf /root/zstore.tar.gz -C /root/zstore --strip-components=1'

# Залити код у контейнер (варіант 2: git)
pct exec $CTID -- bash -c 'apt-get update && apt-get install -y git && git clone <url> /root/zstore'
```

Спакувати локально:
```bash
cd /Users/marian/Desktop
tar -czf /root/zstore.tar.gz zstore
scp /root/zstore.tar.gz root@<proxmox-host>:/root/
```

## 2. Всередині контейнера

```bash
pct exec $CTID -- bash /root/zstore/deploy/lxc-install.sh
```

За замовчуванням:
- БД:     `zstore`
- Юзер:   `zstore` / `zstore_pw`
- Сайт:   `/var/www/zstore`
- URL:    `http://<ip-контейнера>/`
- Логін:  `admin` / `admin` (обов'язково змінити)

Перевизначити:
```bash
pct exec $CTID -- env DB_PASS='StrongPass123' bash /root/zstore/deploy/lxc-install.sh
```

## Що робить `lxc-install.sh`

1. Ставить apache2, mariadb, php8+ з потрібними розширеннями, composer.
2. Вмикає `mod_rewrite`, створює vhost на 80 порт з `AllowOverride All`.
3. Стартує MariaDB, створює БД + користувача.
4. Імпортує `db/db.sql` і `db/update/enable_retail.sql` (вмикає «Касовий чек»).
5. Копіює `www/` → `/var/www/zstore`, виставляє права.
6. Перезаписує `config/config.php` під локальну БД.
7. Запускає `composer install --no-dev`.
8. Кладе крон `/etc/cron.d/zstore`, що дьоргає `crontab.php` кожні 5 хв
   (для автозакриття зміни ВчасноКаса — див. `crontask.php:187-189`).
9. Перезапускає Apache.

## Далі — налаштування в UI (одноразово)

1. **Адмін → Модулі** → Тип фіскалізації = ВчасноКаса.
2. **Довідники → Каси, рахунки** → «Готівка» (`beznal=0`), «Термінал» (`beznal=1`).
3. **POS-термінали** → додати: `pointname`, `address`, `vktoken`, `usefisc=1`,
   `autoshift` = 23:00 (або ваш час), реквізити фірми.
4. **Сервіс → Програма лояльності** → східці бонусів, промокоди.
5. **Ролі** → касиру дозволити `POSCheck`, `Warranty`, `ReturnIssue`, `PayList`,
   `EndDay` і модуль `vkassa`.
