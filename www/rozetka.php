<?php
/**
 * XML-прайс для Rozetka. Адреса з ключем: /rozetka.php?key=...
 * Rozetka забирає файл сама за статичною адресою, тому сесія не потрібна;
 * доступ захищено ключем із налаштувань модуля.
 */

require_once 'init.php';

$modules = \App\System::getOptions('modules');
$key = $modules['rozfeedkey'] ?? '';

if (intval($modules['rozetka'] ?? 0) != 1 || strlen($key) == 0 || !hash_equals($key, strval($_GET['key'] ?? ''))) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'not found';
    exit;
}

try {
    $xml = \App\Modules\Rozetka\Helper::feed();
} catch (\Throwable $e) {
    // Rozetka має отримати помилку, а не HTML-сторінку з трасою
    \App\Helper::log('Rozetka feed: ' . $e->getMessage());
    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'feed error';
    exit;
}

header('Content-Type: application/xml; charset=UTF-8');
header('Cache-Control: no-cache');
header('Content-Length: ' . strlen($xml));
echo $xml;
exit;
