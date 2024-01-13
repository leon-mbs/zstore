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

        parent::__construct($modules['npapikey']);
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

        return $this->getAreaList();
    }

    public function getCityListCache($arearef) {
        $cities = @file_get_contents(_ROOT . "upload/citylist.dat");
        $cities = @unserialize($cities);
        if (is_array($cities) == false) {
            
            $a=$this->getAreaList() ;
            return $this->getCityList($a[$arearef]  );
        }
        $ret = array();
        foreach ($cities as $c) {
            if ($c['Area'] == $arearef) {
                $ret[$c['Ref']] = $c['Description'];
            }
        }
        return $ret;
    }

    public function getPointListCache($cityref,$pm=false) {
        $points = @file_get_contents(_ROOT . "upload/pointlist.dat");
        $points = @unserialize($points);
        if (is_array($points) == false) {
            $points = $this->getPointList($cityref);;
        }
        $ret = array();
        foreach ($points as $r=>$p) {
            
            //из кеша
            if ( is_array($p) && $p['City'] == $cityref) {
                
                $ispm = strpos($p['Description'],'Поштомат') !== false;
                if($pm===$ispm) {
                    $ret[$p['Ref']] = $p['Description'];                    
                }
                

            }
            // из  API
            if ( !is_array($p) ) {
                
                $ispm = strpos($p,'Поштомат') !== false;
                if($pm===$ispm) {
                    $ret[$r] = $p;                    
                }
                

            }
        }
        return $ret;
    }
    
    //обновление  кеща  списков
    public function updatetCache() {
        
         @unlink(_ROOT . "upload/arealist.dat");
        @unlink(_ROOT . "upload/citylist.dat");
        @unlink(_ROOT . "upload/pointlist.dat");

        @mkdir(_ROOT . "upload") ;

        $ret=[]  ;

        $areas = array();
        $tmplist = $this->getAreas();
        if($tmplist['success']==false) {
            if(count($tmplist['errors'] ??[])>0) {
                $this->setError(array_pop($tmplist['errors'])) ;
                $ret['error']=array_pop($tmplist['errors']);
                return $ret;                  
            }
            if(count($tmplist['warnings']??[])>0) {
                $ret['warn']=array_pop($tmplist['warnings']);

            }

        }
        foreach ($tmplist['data'] as $a) {
            $areas[$a['Ref']] = trim($a['Description']);
        }

        $d = serialize($areas);

        file_put_contents(_ROOT . "upload/arealist.dat", $d);
        unset($d);
    
        $cities = array();

        $tmplist = $this->getCities(0);

        foreach ($tmplist['data'] as $a) {
            $cities[] = array('Ref' => $a['Ref'], 'Area' => $a['Area'], 'Description' => trim($a['Description']).' ('.trim($a['DescriptionRu']) .')'  );

        }

        $d = serialize($cities);

        file_put_contents(_ROOT . "upload/citylist.dat", $d);
        unset($tmplist);
        unset($cities);
        unset($d);
        gc_collect_cycles() ;

        $wlist = array();
        $tmplist = $this->getWarehouses('');

        foreach ($tmplist['data'] as $a) {
            $wlist[] = array('Ref' => $a['Ref'], 'City' => $a['CityRef'], 'Description' => trim($a['Description']) );
        }
        unset($tmplist) ;
        gc_collect_cycles() ;

        $d = serialize($wlist);
        file_put_contents(_ROOT . "upload/pointlist.dat", $d);
        unset($wlist) ;
        unset($d);    
        
        return $ret;
        
    }
}
