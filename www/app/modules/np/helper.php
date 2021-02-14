<?php

namespace App\Modules\NP;

/**
 * Вспомагательный  класс
 */
class Helper extends  \LisDev\Delivery\NovaPoshtaApi2
{
    private $api ;
    
 
    public function __construct() {
 
        global $_config;
        $modules = \App\System::getOptions("modules");
       
        parent::__construct($modules['npapikey'],$_config['common']['lang']);
        
 
        
     }
  
     public function getAreaList( ){
          $list = $this->getAreas()  ;
          $areas = array();
          foreach($list['data'] as $a) {
             $areas[$a['Ref']]  = $a['Description'] ;
          }
          
          return $areas;
     }
    
     public function getCityList($areaname){
          $list = $this->findCityByRegion($this->getCities(), $areaname) ;
          $cities = array();
          foreach($list  as $a) {
             $cities[$a['Ref']]  = $a['Description'] ;
              
          }
          return $cities   ;
     }
    
    public function getPointList($cityref){
        
          $list = $this->getWarehouses(  $cityref) ;
          $cities = array();
          foreach($list['data']  as $a) {
              $cities[$a['Ref']]  =  $a['Description'] ;
            // $cities[$a['CityID']]  = $a['Description'] ;
          }
          return $cities   ;
     }
    
     //проверка  экспрес накладной
     public function check($dec){
         
     }
     
}
