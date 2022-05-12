<?php

namespace App\Modules\NP;

/**
 * Вспомагательный  класс
 */
class Helper extends \LisDev\Delivery\NovaPoshtaApi2
{

    private $api;

    public function __construct() {

      
        $modules = \App\System::getOptions("modules");

        parent::__construct($modules['npapikey'] );
    }

    public function getAreaList() {
        $list = $this->getAreas();
        $areas = array();
        foreach ($list['data'] as $a) {
            $areas[$a['Ref']] = $a['Description'];
        }

        return $areas;
    }

    public function getCityList($areaname) {
        $list = $this->findCityByRegion($this->getCities(), $areaname);
        $cities = array();
        foreach ($list as $a) {
            $cities[$a['Ref']] = $a['Description'];
        }
        return $cities;
    }

    public function getPointList($cityref) {

        $list = $this->getWarehouses($cityref);
        $cities = array();
        foreach ($list['data'] as $a) {
            $cities[$a['Ref']] = $a['Description'];
            // $cities[$a['CityID']]  = $a['Description'] ;
        }
        return $cities;
    }

    //проверка  экспрес накладной
    public function check($docs) {
        $ar = array();
        foreach ($docs as $track) {
            $ar[] = array('DocumentNumber' => $track);
        }
        if (count($ar) == 0) {
            return array();
        }

        $params = array('Documents' => $ar);
        $list = array();

        $res = $this
            ->model('TrackingDocument')
            ->method('getStatusDocuments')
            ->params($params)
            ->execute();

        if ($res['success'] == true) {
            foreach ($res['data'] as $row) {
                $list[$row['Number']] = array('StatusCode' => $row['StatusCode'], 'Status' => $row['Status']);
            }
        }
        return $list;

        /*
          1    Нова пошта очікує надходження від відправника
          2    Видалено
          3    Номер не знайдено
          4    Відправлення у місті ХХXХ. (Статус для межобластных отправлений)
          NEW - 41    Відправлення у місті ХХXХ. (Статус для услуг локал стандарт и локал экспресс - доставка в пределах города)
          5    Відправлення прямує до міста YYYY.
          6    Відправлення у місті YYYY, орієнтовна доставка до ВІДДІЛЕННЯ-XXX dd-mm.Очікуйте додаткове повідомлення про прибуття.
          7, 8    Прибув на відділення
          9    Відправлення отримано
          10    Відправлення отримано %DateReceived%.Протягом доби ви одержите SMS-повідомлення про надходження грошового переказута зможете отримати його в касі відділення «Нова пошта».
          11    Відправлення отримано %DateReceived%.Грошовий переказ видано одержувачу.
          14    Відправлення передано до огляду отримувачу
          101    На шляху до одержувача
          102, 103, 108    Відмова одержувача
          104    Змінено адресу
          105    Припинено зберігання
          106    Одержано і створено ЄН зворотньої доставки

         */
    }

    public function getAreaListCache() {
        $areas = @file_get_contents(_ROOT . "upload/arealist.dat");
        $areas = @unserialize($areas);
        if (is_array($areas)) {
            return $areas;
        }

        return array();
    }

    public function getCityListCache($arearef) {
        $cities = @file_get_contents(_ROOT . "upload/citylist.dat");
        $cities = @unserialize($cities);
        if (is_array($cities) == false) {
            return array();
        }
        $ret = array();
        foreach ($cities as $c) {
            if ($c['Area'] == $arearef) {
                $ret[$c['Ref']] = $c['Description'];
            }
        }
        return $ret;
    }

    public function getPointListCache($cityref) {
        $points = @file_get_contents(_ROOT . "upload/pointlist.dat");
        $points = @unserialize($points);
        if (is_array($points) == false) {
            return array();
        }
        $ret = array();
        foreach ($points as $p) {
            if ($p['City'] == $cityref) {
                $ret[$p['Ref']] = $p['Description'];
            }
        }
        return $ret;
    }

}
