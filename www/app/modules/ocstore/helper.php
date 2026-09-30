<?php

namespace App\Modules\OCStore;

use App\System;
use App\Helper as H;

/**
 * Вспомагательный  класс
 *
 * Модуль  работает  как  с одним, так  и с  несколькими  сайтами  OpenCart.
 * Сайты  хранятся  в  options['modules']['ocsites'] (id => запись  сайта).
 * Старые  настройки  (ocsite, ockey ...) остаются  копией  основного  сайта.
 */
class Helper
{
    /**
     * id сайта, которому  принадлежат документы без поля ocsite
     * (импортированные  когда  сайт  был  один)
     */
    public const FIRST = 1;

    /**
     * Список  сайтов  id => запись
     *
     * @param mixed $all true - вместе  с  отключенными
     */
    public static function sites($all = false) {
        $modules = System::getOptions("modules");

        $list = $modules['ocsites'] ?? null;
        if (!is_array($list) || count($list) == 0) {
            $list = array();
            if (strlen($modules['ocsite'] ?? '') > 0) { //установка  с  одним сайтом  в  старых  настройках
                $list[self::FIRST] = array(
                    'id'         => self::FIRST,
                    'name'       => '',
                    'site'       => $modules['ocsite'],
                    'apiname'    => $modules['ocapiname'] ?? '',
                    'key'        => $modules['ockey'] ?? '',
                    'ssl'        => $modules['ocssl'] ?? 0,
                    'v4'         => $modules['ocv4'] ?? 0,
                    'pricetype'  => $modules['ocpricetype'] ?? '',
                    'salesource' => $modules['ocsalesource'] ?? 0,
                    'mf'         => $modules['ocmf'] ?? 0,
                    'storeid'    => $modules['ocstoreid'] ?? 0,
                    'paytype'    => $modules['ocpaytype'] ?? 0,
                    'insertcust' => $modules['ocinsertcust'] ?? 0
                );
            }
        }

        $ret = array();
        foreach ($list as $site) {
            if (!is_array($site) || intval($site['id'] ?? 0) == 0) {
                continue;
            }
            $site['id'] = intval($site['id']);
            $site['site'] = trim($site['site'] ?? '', '/');
            $site['disabled'] = intval($site['disabled'] ?? 0);
            if (strlen($site['name'] ?? '') == 0) {
                $site['name'] = preg_replace('/^https?:\/\//i', '', $site['site']);
            }
            if ($all == false && $site['disabled'] == 1) {
                continue;
            }
            $ret[$site['id']] = $site;
        }
        ksort($ret);

        return $ret;
    }

    /**
     * Запись  сайта
     *
     * @param mixed $id id сайта, 0 - основной (первый  включенный)
     * @return  array|null
     */
    public static function site($id = 0) {
        $id = intval($id);
        if ($id == 0) {
            foreach (self::sites() as $site) {
                return $site;
            }
            return null;
        }
        $list = self::sites(true);

        return $list[$id] ?? null;
    }

    /**
     * Название  сайта
     */
    public static function siteName($id) {
        $site = self::site($id);

        return $site == null ? '' : $site['name'];
    }

    /**
     * Название  сайта  для  подписи  документа. Пусто  если  сайт  один
     */
    public static function siteLabel($id) {
        if (count(self::sites(true)) < 2) {
            return '';
        }

        return self::siteName($id);
    }

    /**
     * id сайта, с  которого  импортирован  документ. 0 если  документ  не  из  OpenCart
     *
     * @param mixed $doc
     */
    public static function siteOf($doc) {
        $id = intval($doc->headerdata['ocsite'] ?? 0);
        if ($id == 0 && strlen($doc->headerdata['ocorder'] ?? '') > 0) {
            $id = self::FIRST;
        }

        return $id;
    }

    /**
     * Условие  для  поиска  документов  сайта
     *
     * @param mixed $id
     */
    public static function docWhere($id) {
        $id = intval($id);
        $where = "content like '%<ocsite>{$id}</ocsite>%'";
        if ($id == self::FIRST) { //документы, импортированные  до  появления  нескольких  сайтов
            $where = "({$where} or (content like '%<ocorder>%' and content not like '%<ocsite>%'))";
        }

        return $where;
    }

    /**
     * Сохраняет  сайт. Поля  записи, которых  модуль  не  знает, остаются  как  есть
     * (в  записи  сайта  могут хранить  свои  настройки  другие  модули).
     *
     * @param mixed $site запись, id=0 - новый  сайт
     * @return int id сайта
     */
    public static function saveSite($site) {
        $list = self::sites(true);
        $modules = System::getOptions("modules");

        $id = intval($site['id'] ?? 0);
        if ($id == 0) { //id  не  выдается  повторно  после удаления  сайта
            $id = intval($modules['ocsitelast'] ?? 0);
            if (count($list) > 0) {
                $id = max($id, max(array_keys($list)));
            }
            $id++;
        }
        $site['id'] = $id;
        if (isset($site['site'])) {
            $site['site'] = trim($site['site'], '/');
        }

        $list[$id] = array_merge($list[$id] ?? array(), $site);

        self::store($list, max($id, intval($modules['ocsitelast'] ?? 0)));
        self::setState($id, array());

        return $id;
    }

    /**
     * Удаляет  сайт
     */
    public static function deleteSite($id) {
        $list = self::sites(true);
        $modules = System::getOptions("modules");
        unset($list[intval($id)]);

        self::store($list, intval($modules['ocsitelast'] ?? 0));
        self::setState($id, array());
    }

    /**
     * Количество документов, импортированных с  сайта
     */
    public static function docCount($id) {
        $conn = \ZDB\DB::getConnect();

        return intval($conn->GetOne("select count(*) from documents where " . self::docWhere($id)));
    }

    private static function store($list, $last) {
        $modules = System::getOptions("modules", true);

        ksort($list);
        $modules['ocsites'] = $list;
        $modules['ocsitelast'] = $last;

        //старые  настройки - копия  основного сайта  (их  может  читать сторонний  код)
        $main = null;
        foreach ($list as $site) {
            if (intval($site['disabled'] ?? 0) == 0) {
                $main = $site;
                break;
            }
        }
        $modules['ocsite'] = $main['site'] ?? '';
        $modules['ocapiname'] = $main['apiname'] ?? '';
        $modules['ockey'] = $main['key'] ?? '';
        $modules['ocssl'] = $main['ssl'] ?? 0;
        $modules['ocv4'] = $main['v4'] ?? 0;
        $modules['ocpricetype'] = $main['pricetype'] ?? '';
        $modules['ocsalesource'] = $main['salesource'] ?? 0;
        $modules['ocmf'] = $main['mf'] ?? 0;
        $modules['ocstoreid'] = $main['storeid'] ?? 0;
        $modules['ocpaytype'] = $main['paytype'] ?? 0;
        $modules['ocinsertcust'] = $main['insertcust'] ?? 0;

        System::setOptions("modules", $modules);
    }

    //состояние  подключения (токен, статусы, категории)  отдельно  для  каждого  сайта
    private static function state($id) {
        $oc = System::getSession()->oc;

        return is_array($oc[$id] ?? null) ? $oc[$id] : array();
    }

    private static function setState($id, $state) {
        $oc = System::getSession()->oc;
        if (!is_array($oc)) {
            $oc = array();
        }
        $oc[$id] = $state;
        System::getSession()->oc = $oc;

        $main = self::site();
        if ($main != null && $main['id'] == $id) { //как  раньше - для  стороннего  кода
            System::getSession()->octoken = $state['token'] ?? '';
            System::getSession()->statuses = $state['statuses'] ?? null;
            System::getSession()->cats = $state['cats'] ?? null;
        }
    }

    /**
     * Список  статусов  заказов  сайта. null если  соединение  еще  не  выполнялось
     */
    public static function statuses($id) {
        $state = self::state($id);

        return is_array($state['statuses'] ?? null) ? $state['statuses'] : null;
    }

    /**
     * Список  категорий  сайта. null если  соединение  еще  не  выполнялось
     */
    public static function cats($id) {
        $state = self::state($id);

        return is_array($state['cats'] ?? null) ? $state['cats'] : null;
    }

    /**
     * Адрес  API сайта
     *
     * @param mixed $siteId
     * @param mixed $route например  api/zstore/orders. Для  OpenCart 4 сам  превращается  в  api/zstore.orders
     */
    public static function url($siteId, $route) {
        $site = self::site($siteId);
        if ($site == null) {
            return '';
        }
        if ($site['v4'] == 1 && strpos($route, '.') === false && $route != 'api/account/login') {
            $p = strrpos($route, '/');
            if ($p !== false) {
                $route = substr($route, 0, $p) . '.' . substr($route, $p + 1);
            }
        }
        $url = $site['site'] . '/index.php?route=' . $route;

        $state = self::state($site['id']);
        if (strlen($state['token'] ?? '') > 0) {
            $url .= '&' . $state['token'];
        }

        return $url;
    }

    //запрос, возвращает  array(ответ, текст ошибки)
    private static function curl($url, $params, $ssl, $cookie) {

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_COOKIEJAR, _ROOT . 'upload/' . $cookie);
        curl_setopt($ch, CURLOPT_COOKIEFILE, _ROOT . 'upload/' . $cookie);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $ssl);

        $params_string = '';
        if (is_array($params) && count($params)) {

            $params_string = http_build_query($params);

            curl_setopt($ch, CURLOPT_POST, count($params));
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params_string);
        }

        //execute post
        $result = curl_exec($ch);
        if ($result === false) {
            $error = curl_error($ch);
            curl_close($ch);

            return array(false, $error);
        }
        curl_close($ch);

        $data = json_decode($result, true);
        if ($data === null) {
            if (strlen($result) > 0) {
                return array(false, $result);
            }

            return array(false, "Немає даних відповіді");
        }

        return array($result, '');
    }

    /**
     * Функция для  работы  с  API опенкарта
     *
     * @param mixed $url адрес  API  например <youropencartsite>/index.php?route=api/login'
     * @param mixed $params параметры например array('username' => $apiname,'key' => $key );
     */
    public static function do_curl_request($url, $params = array()) {

        $ssl = \App\System::getSession()->ocssl == 1;

        list($result, $error) = self::curl($url, $params, $ssl, 'apicookie.txt');
        if ($result === false) {
            \App\System::setErrorMsg($error, true);
        }

        return $result;
    }

    //текст  ошибки  с  названием  сайта  если  сайтов  несколько
    private static function error($site, $msg) {
        $msg = trim(strip_tags(is_array($msg) ? implode(' ', $msg) : $msg));
        $msg = str_replace(array("'", "\"", "\n", "\r"), array("`", "`", " ", " "), $msg);
        $msg = mb_substr($msg, 0, 300);
        if (count(self::sites(true)) > 1) {
            $msg = $site['name'] . ': ' . $msg;
        }
        System::setErrorMsg($msg, true);

        return false;
    }

    private static function call($site, $url, $params) {
        $cookie = $site['id'] == self::FIRST ? 'apicookie.txt' : 'apicookie' . $site['id'] . '.txt';

        return self::curl($url, $params, $site['ssl'] == 1, $cookie);
    }

    /**
     * Соединение  с  сайтом: получает  токен, список  статусов и  категорий
     *
     * @param mixed $siteId id сайта, 0 - основной
     * @param mixed $silent не  выводить  сообщение об  успешном  соединении
     * @return bool
     */
    public static function connect($siteId = 0, $silent = false) {
        $site = self::site($siteId);
        if ($site == null) {
            System::setErrorMsg('Не задано сайт OpenCart');
            return false;
        }
        $id = $site['id'];
        self::setState($id, array());
        if (self::site() != null && self::site()['id'] == $id) {
            System::getSession()->ocssl = $site['ssl'];
        }

        $url = $site['site'] . '/index.php?route=api/login';
        if ($site['v4'] == 1) {
            $url = $site['site'] . '/index.php?route=api/account/login';
        }
        $fields = array(
            'username' => $site['apiname'],
            'key'      => $site['key']
        );

        list($json, $error) = self::call($site, $url, $fields);
        if ($json === false) {
            return self::error($site, $error);
        }

        $data = json_decode($json, true);
        if (!is_array($data) || count($data) == 0) {
            return self::error($site, 'Немає даних відповіді');
        }

        $state = array();
        if (strlen($data['api_token'] ?? '') > 0) { //версия 3
            $state['token'] = "api_token=" . $data['api_token'];
        }
        if (strlen($data['token'] ?? '') > 0) { //версия 2.3
            $state['token'] = "token=" . $data['token'];
        }
        if (strlen($data['success'] ?? '') == 0 || strlen($state['token'] ?? '') == 0) {
            $error = $data['error'] ?? '';
            if (is_array($error)) {
                $error = implode(' ', $error);
            }

            return self::error($site, strlen($error) > 0 ? $error : 'Помилка з`єднання');
        }
        self::setState($id, $state);

        //загружаем список статусов
        list($json, $error) = self::call($site, self::url($id, 'api/zstore/statuses'), array());
        $data = $json === false ? array('error' => $error) : json_decode($json, true);
        if (($data['error'] ?? '') != "") {
            return self::error($site, $data['error']);
        }
        $state['statuses'] = $data['statuses'] ?? array();

        //загружаем список категорий
        list($json, $error) = self::call($site, self::url($id, 'api/zstore/cats'), array());
        $data = $json === false ? array('error' => $error) : json_decode($json, true);
        if (($data['error'] ?? '') != "") {
            return self::error($site, $data['error']);
        }
        $state['cats'] = $data['cats'] ?? array();

        self::setState($id, $state);

        if ($silent == false) {
            System::setSuccessMsg("Успішне з`єднання" . (count(self::sites(true)) > 1 ? ': ' . $site['name'] : ''));
        }

        return true;
    }

    /**
     * Запрос  к  API сайта. Сам  выполняет  соединение  если его  еще  не  было или  токен  устарел,
     * поэтому  работает  и  без  сессии  пользователя (планировщик).
     *
     * @param mixed $siteId id сайта, 0 - основной
     * @param mixed $route  например  api/zstore/orders
     * @param mixed $params параметры  POST
     * @return array|false ответ  сайта  или  false (текст  ошибки  в  System::getErrorMsg())
     */
    public static function request($siteId, $route, $params = array()) {
        $site = self::site($siteId);
        if ($site == null) {
            System::setErrorMsg('Не задано сайт OpenCart');
            return false;
        }
        if ($site['disabled'] == 1) {
            return self::error($site, 'Сайт вимкнено');
        }
        $id = $site['id'];

        $connected = false;
        if (strlen(self::state($id)['token'] ?? '') == 0) {
            if (self::connect($id, true) == false) {
                return false;
            }
            $connected = true;
        }

        list($json, $error) = self::call($site, self::url($id, $route), $params);
        $data = $json === false ? null : json_decode($json, true);

        $noaccess = is_array($data) && in_array($data['error'] ?? '', array('No access', 'Нет доступа'));
        if ($noaccess && $connected == false) { //токен  устарел
            if (self::connect($id, true) == false) {
                return false;
            }
            list($json, $error) = self::call($site, self::url($id, $route), $params);
            $data = $json === false ? null : json_decode($json, true);
        }

        if ($json === false) {
            return self::error($site, $error);
        }
        if (!is_array($data)) {
            return self::error($site, 'Невірна відповідь');
        }
        if (($data['error'] ?? '') != "") {
            return self::error($site, $data['error']);
        }

        return $data;
    }

    /**
     * Отправляет  на  сайт  статусы  заказов
     *
     * @param mixed $siteId
     * @param mixed $list  номер  заказа  на  сайте => id статуса
     * @param mixed $extra дополнительные  поля запроса (например  comments => array(номер  заказа => текст)).
     *                     Если  не  заданы, запрос  такой  же  как  раньше
     * @return bool
     */
    public static function sendStatuses($siteId, $list, $extra = array()) {
        $fields = array(
            'data' => json_encode($list)
        );
        foreach ($extra as $key => $value) {
            $fields[$key] = is_array($value) ? json_encode($value) : $value;
        }

        return self::request($siteId, 'api/zstore/updateorder', $fields) !== false;
    }

    /**
     * Отправляет  на  сайт  статус  одного заказа
     *
     * @param mixed $siteId
     * @param mixed $ocorder номер  заказа  на  сайте
     * @param mixed $status  id статуса  на  сайте
     * @param mixed $extra   дополнительные  данные  заказа  (например comments => номер  ТТН)
     * @return bool
     */
    public static function sendStatus($siteId, $ocorder, $status, $extra = array()) {
        $fields = array();
        foreach ($extra as $key => $value) {
            $fields[$key] = array($ocorder => $value);
        }

        return self::sendStatuses($siteId, array($ocorder => $status), $fields);
    }

    /**
     * Части  примечания  заказа. Какие  из  них  писать - задается  в  настройках  сайта
     */
    public static function noteParts() {
        return array(
            'number'   => 'Номер замовлення в магазині',
            'client'   => 'Клієнт',
            'phone'    => 'Телефон',
            'email'    => 'Email',
            'address'  => 'Адреса доставки',
            'delivery' => 'Спосіб доставки',
            'pay'      => 'Спосіб оплати',
            'comment'  => 'Коментар клієнта'
        );
    }

    //текст  из  магазина  без  тегов, спецсимволов  HTML и  лишних  пробелов
    private static function clean($text) {
        $text = html_entity_decode(strip_tags((string)$text), ENT_QUOTES, 'UTF-8');

        return trim(preg_replace('/\s+/u', ' ', $text));
    }

    /**
     * Имя  или  фамилия: если  набрано  одним  регистром (иван, ИВАН) - с  большой  буквы
     */
    public static function personName($name) {
        $name = self::clean($name);
        if ($name != '' && ($name === mb_strtolower($name) || $name === mb_strtoupper($name))) {
            $name = preg_replace_callback('/(^|[\s\-])(\p{L})/u', function ($m) {
                return $m[1] . mb_strtoupper($m[2]);
            }, mb_strtolower($name));
        }

        return $name;
    }

    /**
     * Вид  доставки  Zippy по  коду  и  названию  способа  доставки  магазина
     *
     * @return array array(вид  доставки, название)
     */
    public static function deliveryType($text) {
        $text = mb_strtolower($text);
        $modules = System::getOptions("modules");
        $types = \App\Entity\Doc\Document::getDeliveryTypes(($modules['np'] ?? 0) == 1);

        $type = 0;
        if (preg_match('/nova.?posh|novapost|нова пошта|новая почта/u', $text)) {
            $type = \App\Entity\Doc\Document::DEL_NP;
        } elseif (preg_match('/ukr.?posh|укрпошт|укр\. ?пошт|укрпочт/u', $text)) {
            $type = \App\Entity\Doc\Document::DEL_UP;
        } elseif (preg_match('/meest|міст експрес|мист экспресс/u', $text)) {
            $type = \App\Entity\Doc\Document::DEL_MEEST;
        } elseif (preg_match('/rozetka|розетк/u', $text)) {
            $type = \App\Entity\Doc\Document::DEL_ROZ;
        } elseif (preg_match('/pickup|самовив|самовыв/u', $text)) {
            $type = \App\Entity\Doc\Document::DEL_SELF;
        } elseif (preg_match('/courier|кур.?єр|курьер/u', $text)) {
            $type = \App\Entity\Doc\Document::DEL_BOY;
        } elseif (trim($text) != '') {
            $type = \App\Entity\Doc\Document::DEL_SERVICE;
        }
        if ($type > 0 && isset($types[$type]) == false) {
            $type = \App\Entity\Doc\Document::DEL_SERVICE;
        }

        return array($type, $types[$type] ?? '');
    }

    /**
     * Данные  покупателя  и  доставки  из  заказа  магазина  в  разобранном  виде:
     * firstname, lastname, name, recipient, phone, email, address, shipping, delivery, delivery_name, payment, comment
     *
     * @param mixed $shoporder заказ  как  его  отдал  магазин
     */
    public static function orderInfo($shoporder) {
        $info = array();
        $info['firstname'] = self::personName($shoporder->firstname);
        $info['lastname'] = self::personName($shoporder->lastname);
        $info['name'] = trim($info['lastname'] . ' ' . $info['firstname']);

        //получатель - если  это  не  сам  покупатель
        $recipient = trim(self::personName($shoporder->shipping_lastname) . ' ' . self::personName($shoporder->shipping_firstname));
        $info['recipient'] = mb_strtolower($recipient) == mb_strtolower($info['name']) ? '' : $recipient;

        $info['phone'] = self::clean($shoporder->telephone);
        $info['email'] = mb_strtolower(self::clean($shoporder->email));

        //адрес: область, город, улица  или  отделение. Если  адреса  доставки  нет - платежный
        $prefix = strlen(self::clean($shoporder->shipping_city) . self::clean($shoporder->shipping_address_1)) > 0 ? 'shipping_' : 'payment_';
        $address = array();
        foreach (array('zone', 'city', 'address_1', 'address_2') as $field) {
            $value = self::clean($shoporder->{$prefix . $field});
            if ($value != '' && in_array(mb_strtolower($value), array_map('mb_strtolower', $address)) == false) {
                $address[] = $value;
            }
        }
        $info['address'] = implode(', ', $address);

        $info['shipping'] = self::clean($shoporder->shipping_method);
        list($info['delivery'], $info['delivery_name']) = self::deliveryType(self::clean($shoporder->shipping_code) . ' ' . $info['shipping']);
        $info['payment'] = self::clean($shoporder->payment_method);
        $info['comment'] = self::clean($shoporder->comment);

        return $info;
    }

    /**
     * Примечание  заказа. Состав - по  настройкам  сайта (noteparts), пустые  части  пропускаются
     *
     * @param mixed $site      запись  сайта
     * @param mixed $shoporder заказ  как  его  отдал  магазин
     */
    public static function notes($site, $shoporder) {
        $info = self::orderInfo($shoporder);
        $label = self::siteLabel($site['id']);

        $parts = array();
        $parts['number'] = 'OC номер: ' . $shoporder->order_id . ($label != '' ? " ({$label})" : '');
        $parts['client'] = $info['name'] . ($info['recipient'] != '' ? ', отримувач ' . $info['recipient'] : '');
        if ($parts['client'] != '') {
            $parts['client'] = 'Клієнт: ' . ltrim($parts['client'], ', ');
        }
        $parts['phone'] = $info['phone'] != '' ? 'Тел: ' . $info['phone'] : '';
        $parts['email'] = $info['email'] != '' ? 'Email: ' . $info['email'] : '';
        $parts['address'] = $info['address'] != '' ? 'Адреса: ' . $info['address'] : '';
        $parts['delivery'] = $info['shipping'] != '' ? 'Доставка: ' . $info['shipping'] : '';
        $parts['pay'] = $info['payment'] != '' ? 'Оплата: ' . $info['payment'] : '';
        $parts['comment'] = $info['comment'] != '' ? 'Коментар: ' . $info['comment'] : '';

        $enabled = $site['noteparts'] ?? null;
        if (is_array($enabled) == false) { //не  настроено - пишем  все
            $enabled = array_keys(self::noteParts());
        }
        $notes = array();
        foreach ($parts as $code => $text) {
            if ($text != '' && in_array($code, $enabled)) {
                $notes[] = $text;
            }
        }

        return count($notes) > 0 ? implode('; ', $notes) . ';' : '';
    }

    /**
     * Контрагент  для  заказа  магазина: ищет  по  id покупателя (только  первый  сайт - на  разных  сайтах  id
     * не  совпадают), телефону, а  если  телефона  нет - по  email. Не  найден - создает.
     * У  найденного  дополняет  только  пустые  поля, адрес  доставки - из  последнего  заказа.
     *
     * @return \App\Entity\Customer|null null если  в  заказе  нет  ни  телефона  ни  email
     */
    public static function customer($site, $shoporder) {
        $info = self::orderInfo($shoporder);
        $phone = $info['phone'] != '' ? \App\Util::handlePhone($info['phone']) : '';
        if ($phone == '' && $info['email'] == '') {
            return null;
        }
        $shopid = $site['id'] == self::FIRST ? intval($shoporder->customer_id) : 0;

        $cust = null;
        if ($shopid > 0) {
            $cust = \App\Entity\Customer::getFirst("detail like '%<shopcust_id>{$shopid}</shopcust_id>%'");
        }
        if ($cust == null && $phone != '') {
            $cust = \App\Entity\Customer::getByPhone($phone);
        }
        if ($cust == null && $phone == '') {
            $cust = \App\Entity\Customer::getByEmail($info['email']);
        }

        $changed = false;
        if ($cust == null) {
            $label = self::siteLabel($site['id']);

            $cust = new \App\Entity\Customer();
            $cust->customer_name = $info['name'] != '' ? $info['name'] : ($phone != '' ? $phone : $info['email']);
            $cust->type = \App\Entity\Customer::TYPE_BAYER;
            $cust->phone = $phone;
            $cust->comment = 'Клієнт OpenCart' . ($label != '' ? ' (' . $label . ')' : '');
            $changed = true;
        }
        foreach (array('firstname', 'lastname', 'email', 'address') as $field) {
            if (strlen((string)$cust->{$field}) == 0 && $info[$field] != '') {
                $cust->{$field} = $info[$field];
                $changed = true;
            }
        }
        if ($info['address'] != '' && (string)$cust->addressdel != $info['address']) {
            $cust->addressdel = $info['address'];
            $changed = true;
        }
        if ($shopid > 0 && intval($cust->shopcust_id) != $shopid) {
            $cust->shopcust_id = $shopid;
            $changed = true;
        }
        if ($changed) {
            $cust->save();
        }

        return $cust;
    }

    /**
     * Вызывается  после  импорта заказа. Обработчики  других  модулей  задаются  в
     * options['modules']['ocafterimport'] как  список  'Класс::метод' и  получают
     * документ, id сайта и  заказ  в  том  виде, как  его  отдал  сайт (доставка, адрес, оплата).
     *
     * @param mixed $doc       созданный  документ
     * @param mixed $siteId
     * @param mixed $shoporder
     */
    public static function afterImport($doc, $siteId, $shoporder) {
        $modules = System::getOptions("modules");
        $handlers = $modules['ocafterimport'] ?? array();
        if (!is_array($handlers)) {
            return;
        }
        foreach ($handlers as $handler) {
            if (is_callable($handler) == false) {
                continue;
            }
            try {
                call_user_func($handler, $doc, $siteId, $shoporder);
            } catch (\Throwable $e) {
                global $logger;
                $logger->error($e->getMessage() . " OCStore afterImport");
            }
        }
    }

}
