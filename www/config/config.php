<?php

/**
 * Конфігурація застосунку.
 *
 * У Docker оточенні значення беруться з змінних середовища,
 * які задаються через `.env` → `docker-compose.yml` → контейнер.
 *
 * Локально (без Docker) можна не задавати змінні середовища –
 * будуть використані дефолтні значення нижче.
 */

$_config = array('common' => [], 'db' => [], 'smtp' => []);

// Загальні налаштування
// DEBUG = 100, INFO = 200, WARNING = 300, ERROR = 400;
$envLogLevel = getenv('APP_LOG_LEVEL');
$_config['common']['loglevel'] = $envLogLevel !== false ? (int)$envLogLevel : 100;

$envTimezone = getenv('APP_TIMEZONE');
$_config['common']['timezone'] = $envTimezone !== false && $envTimezone !== ''
    ? $envTimezone
    : 'Europe/Kiev';

// З'єднання з сервером БД
// У Docker за замовчуванням DB_HOST = "db:3306"
$_config['db']['host'] = getenv('DB_HOST') !== false ? getenv('DB_HOST') : 'localhost:3306';
$_config['db']['name'] = getenv('DB_NAME') !== false ? getenv('DB_NAME') : 'zstore';
$_config['db']['user'] = getenv('DB_USER') !== false ? getenv('DB_USER') : 'root';
$_config['db']['pass'] = getenv('DB_PASS') !== false ? getenv('DB_PASS') : 'root';

// Параметри поштового сервера
// Якщо usesmtp = false – використовується sendmail (заповнюється лише поле user).
$envUseSmtp = getenv('SMTP_USE_SMTP');
if ($envUseSmtp === false || $envUseSmtp === '') {
    $_config['smtp']['usesmtp'] = false;
} else {
    $_config['smtp']['usesmtp'] = in_array(strtolower($envUseSmtp), ['1', 'true', 'yes', 'on'], true);
}

$_config['smtp']['host'] = getenv('SMTP_HOST') !== false ? getenv('SMTP_HOST') : 'smtp.google.com';

$envSmtpPort = getenv('SMTP_PORT');
$_config['smtp']['port'] = $envSmtpPort !== false ? (int)$envSmtpPort : 587;

$_config['smtp']['user'] = getenv('SMTP_USER') !== false ? getenv('SMTP_USER') : 'admin.google.com';
$_config['smtp']['emailfrom'] = getenv('SMTP_EMAIL_FROM') !== false ? getenv('SMTP_EMAIL_FROM') : 'admin.google.com';
$_config['smtp']['pass'] = getenv('SMTP_PASS') !== false ? getenv('SMTP_PASS') : 'пароль';

$envSmtpTls = getenv('SMTP_TLS');
if ($envSmtpTls === false || $envSmtpTls === '') {
    $_config['smtp']['tls'] = true;
} else {
    $_config['smtp']['tls'] = in_array(strtolower($envSmtpTls), ['1', 'true', 'yes', 'on'], true);
}
