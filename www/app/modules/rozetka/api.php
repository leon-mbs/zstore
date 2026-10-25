<?php

namespace App\Modules\Rozetka;

use App\System;

/**
 * Клієнт Rozetka Seller API (https://api-seller.rozetka.com.ua/apidoc/).
 *
 * Токен живе добу; тримаємо його в налаштуваннях модуля і оновлюємо
 * заздалегідь або коли сервер відповість 401. Транспорт винесено в
 * окремий метод, який можна підмінити (self::$transport) — так модуль
 * тестується без облікового запису Rozetka.
 */
class Api
{
    public const BASE = 'https://api-seller.rozetka.com.ua/';

    // токен дійсний 24 години; оновлюємо трохи раніше
    public const TOKEN_TTL = 23 * 3600;

    // orders/search?types=  (документація 1.3)
    public const TYPES_ALL        = 1;
    public const TYPES_INPROCESS  = 2;
    public const TYPES_DONE       = 3;
    public const TYPES_NEW        = 4;
    public const TYPES_SHIPPING   = 5;
    public const TYPES_FAILED     = 6;

    // поля, які нам потрібні в замовленні
    public const EXPAND_ORDER = 'delivery,user,purchases,status_data,payment_type_name,is_payed';

    /**
     * Підмінний транспорт для тестів:
     *   function(string $method, string $url, array $headers, ?string $body): array ['code'=>int, 'body'=>string]
     * @var callable|null
     */
    public static $transport = null;

    // ---------------------------------------------------------------- auth

    /**
     * Повертає дійсний токен, за потреби отримує новий (POST /sites)
     */
    public static function token($force = false) {
        $m = System::getOptions('modules');
        $tok = $m['rozaccesstoken'] ?? '';
        $ts  = intval($m['roztokentime'] ?? 0);
        if (!$force && strlen($tok) > 0 && (time() - $ts) < self::TOKEN_TTL) {
            return $tok;
        }

        $login = trim($m['rozlogin'] ?? '');
        $pass  = $m['rozpassword'] ?? '';
        if (strlen($login) == 0 || strlen($pass) == 0) {
            throw new \Exception('Не вказано логін або пароль Rozetka');
        }

        // за документацією пароль передається в base64
        $res = self::raw('POST', 'sites', [], json_encode(['username' => $login, 'password' => base64_encode($pass)]), false);
        $tok = $res['content']['access_token'] ?? '';
        if (strlen($tok) == 0) {
            throw new \Exception('Rozetka не повернула токен: ' . self::errorText($res));
        }

        $m = System::getOptions('modules');
        $m['rozaccesstoken'] = $tok;
        $m['roztokentime'] = time();
        System::setOptions('modules', $m);

        return $tok;
    }

    /**
     * Скидає збережений токен (наприклад, після зміни пароля)
     */
    public static function forgetToken() {
        $m = System::getOptions('modules');
        unset($m['rozaccesstoken'], $m['roztokentime']);
        System::setOptions('modules', $m);
    }

    // ------------------------------------------------------------ requests

    /**
     * Авторизований запит. Повертає розібраний JSON (масив).
     * На 401 один раз оновлює токен і повторює.
     */
    public static function request($method, $path, array $params = [], $json = null) {
        $body = $json === null ? null : json_encode($json, JSON_UNESCAPED_UNICODE);
        try {
            return self::raw($method, $path, $params, $body, self::token());
        } catch (ApiAuthException $e) {
            return self::raw($method, $path, $params, $body, self::token(true));
        }
    }

    /**
     * Перевірка з'єднання: новий токен і кількість нових замовлень
     */
    public static function ping() {
        self::token(true);
        $r = self::request('GET', 'orders/search', ['types' => self::TYPES_NEW, 'page' => 1]);
        return intval($r['content']['_meta']['totalCount'] ?? 0);
    }

    // -------------------------------------------------------------- orders

    /**
     * Сторінка замовлень заданої групи (types=)
     * @return array ['orders'=>[], 'pages'=>int, 'total'=>int]
     */
    public static function orders($types, $page = 1) {
        $r = self::request('GET', 'orders/search', [
            'types'  => intval($types),
            'page'   => intval($page),
            'sort'   => '-id',
            'expand' => self::EXPAND_ORDER,
        ]);
        return [
            'orders' => $r['content']['orders'] ?? [],
            'pages'  => intval($r['content']['_meta']['pageCount'] ?? 1),
            'total'  => intval($r['content']['_meta']['totalCount'] ?? 0),
        ];
    }

    /**
     * Усі замовлення групи (з обмеженням сторінок, щоб не зациклитись)
     */
    public static function allOrders($types, $maxPages = 20) {
        $list = [];
        for ($p = 1; $p <= $maxPages; $p++) {
            $r = self::orders($types, $p);
            foreach ($r['orders'] as $o) {
                $list[] = $o;
            }
            if ($p >= $r['pages'] || count($r['orders']) == 0) {
                break;
            }
        }
        return $list;
    }

    public static function order($id) {
        $r = self::request('GET', 'orders/' . intval($id), ['expand' => self::EXPAND_ORDER . ',status_available']);
        return $r['content'] ?? [];
    }

    /**
     * Зміна статусу / ТТН / коментаря продавця (PUT /orders/{id}, 1.4.1).
     * ТТН обов'язкова для статусів 3 і 61.
     */
    public static function updateOrder($id, $status, $ttn = '', $sellerComment = '') {
        $fields = ['status' => intval($status)];
        if (strlen($ttn ?? '') > 0) {
            $fields['ttn'] = $ttn;
        }
        if (strlen($sellerComment ?? '') > 0) {
            $fields['seller_comment'] = $sellerComment;
        }
        $r = self::request('PUT', 'orders/' . intval($id), [], $fields);
        return $r['content'] ?? [];
    }

    /**
     * Довідник статусів замовлень (GET /order-statuses/search)
     * @return array id => назва (укр.)
     */
    public static function orderStatuses() {
        $list = [];
        for ($p = 1; $p <= 10; $p++) {
            $r = self::request('GET', 'order-statuses/search', ['page' => $p]);
            foreach ($r['content']['orderStatus'] ?? [] as $st) {
                $list[intval($st['id'])] = $st['name_uk'] ?? $st['name_ua'] ?? $st['title'] ?? $st['name'] ?? ('#' . $st['id']);
            }
            if ($p >= intval($r['content']['_meta']['pageCount'] ?? 1)) {
                break;
            }
        }
        return $list;
    }

    // --------------------------------------------------------------- items

    /**
     * Сторінка товарів продавця (GET /goods/all)
     * @return array ['items'=>[], 'pages'=>int]
     */
    public static function goods($page = 1, $pageSize = 100) {
        $r = self::request('GET', 'goods/all', ['page' => intval($page), 'pageSize' => intval($pageSize)]);
        $c = $r['content'] ?? [];
        // у відповіді товари можуть лежати в items або goods
        $items = $c['items'] ?? $c['goods'] ?? (isset($c[0]) ? $c : []);
        return [
            'items' => is_array($items) ? $items : [],
            'pages' => intval($c['_meta']['pageCount'] ?? 1),
        ];
    }

    public static function allGoods($maxPages = 100) {
        $list = [];
        for ($p = 1; $p <= $maxPages; $p++) {
            $r = self::goods($p);
            foreach ($r['items'] as $it) {
                $list[] = $it;
            }
            if ($p >= $r['pages'] || count($r['items']) == 0) {
                break;
            }
        }
        return $list;
    }

    /**
     * Масова зміна ціни і наявності (PUT /items/mass-update, 4.0).
     * $items: [['item_rz_id'=>int, 'price'=>float?, 'stock_quantity'=>int?], ...]
     */
    public static function massUpdate(array $items, $chunk = 100) {
        $sent = 0;
        foreach (array_chunk($items, $chunk) as $part) {
            self::request('PUT', 'items/mass-update', [], ['isIgnoreCheck' => 0, 'items' => array_values($part)]);
            $sent += count($part);
        }
        return $sent;
    }

    // ----------------------------------------------------------- low level

    /**
     * Один HTTP-запит до API без повторів. Кидає ApiAuthException на 401.
     * $token === false — запит без авторизації (логін).
     */
    public static function raw($method, $path, array $params, $body, $token) {
        $url = self::BASE . ltrim($path, '/');
        if (count($params) > 0) {
            $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($params);
        }
        $headers = ['Content-Type: application/json', 'Accept: application/json'];
        if ($token !== false && strlen($token ?? '') > 0) {
            $headers[] = 'Authorization: Bearer ' . $token;
        }

        $resp = self::transport($method, $url, $headers, $body);
        $code = intval($resp['code'] ?? 0);
        $data = json_decode($resp['body'] ?? '', true);

        if ($code == 401) {
            throw new ApiAuthException('Rozetka: 401 ' . self::errorText($data));
        }
        if ($code == 0) {
            throw new \Exception('Rozetka: немає відповіді від сервера');
        }
        if ($code >= 400) {
            throw new \Exception('Rozetka: HTTP ' . $code . ' ' . self::errorText($data));
        }
        if (!is_array($data)) {
            throw new \Exception('Rozetka: відповідь не JSON');
        }
        // API відповідає 200 і success:false при помилках (див. «Опис помилок»)
        if (array_key_exists('success', $data) && $data['success'] === false) {
            $msg = self::errorText($data);
            if (stripos($msg, 'token') !== false || stripos($msg, 'incorrect_enter') !== false) {
                throw new ApiAuthException('Rozetka: ' . $msg);
            }
            throw new \Exception('Rozetka: ' . $msg);
        }
        return $data;
    }

    /**
     * Власне HTTP. Або підмінний $transport, або curl.
     */
    protected static function transport($method, $url, array $headers, $body) {
        if (is_callable(self::$transport)) {
            return call_user_func(self::$transport, $method, $url, $headers, $body);
        }
        $m = System::getOptions('modules');
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST  => strtoupper($method),
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_SSL_VERIFYPEER => intval($m['rozssl'] ?? 1) == 1,
        ]);
        if ($body !== null && $body !== '') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }
        $out = curl_exec($ch);
        if ($out === false) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new \Exception('Rozetka: ' . $err);
        }
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return ['code' => $code, 'body' => $out];
    }

    /**
     * Людський текст помилки з відповіді API.
     * errors — об'єкт {message, code, details} або масив таких.
     */
    public static function errorText($data) {
        if (!is_array($data)) {
            return '';
        }
        $e = $data['errors'] ?? null;
        if (is_string($e)) {
            return $e;
        }
        if (!is_array($e)) {
            return is_string($data['message'] ?? null) ? $data['message'] : '';
        }
        $list = isset($e['message']) ? [$e] : $e;
        $parts = [];
        foreach ($list as $k => $err) {
            if (!is_array($err)) {
                $parts[] = (is_string($k) ? $k . ': ' : '') . strval($err);
                continue;
            }
            $s = strval($err['message'] ?? '');
            $d = $err['details'] ?? null;
            if (is_array($d)) {
                $dd = [];
                foreach ($d as $dk => $dv) {
                    $dd[] = (is_string($dk) ? $dk . ': ' : '') . (is_array($dv) ? implode(', ', array_map('strval', $dv)) : strval($dv));
                }
                $d = implode('; ', $dd);
            }
            if (strlen($d ?? '') > 0) {
                $s .= ' (' . $d . ')';
            }
            $parts[] = $s;
        }
        return implode('; ', array_filter($parts));
    }
}

class ApiAuthException extends \Exception
{
}
