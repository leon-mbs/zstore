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
