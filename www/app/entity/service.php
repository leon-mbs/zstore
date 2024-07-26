<?php

namespace App\Entity;

/**
 * Клас-сущность  работа, услуга
 *
 * @table=services
 * @keyfield=service_id
 */
class Service extends \ZCL\DB\Entity
{
    protected function init() {
        $this->service_id = 0;
        $this->itemset = [];
    }

    protected function afterLoad() {


        $xml = @simplexml_load_string($this->detail);

        $this->hours = (string)($xml->hours[0]);
        $this->price = (string)($xml->price[0]);
        $this->cost = (string)($xml->cost[0]);
        $this->msr = (string)($xml->msr[0]);
        $this->techcard = (string)($xml->techcard[0]);
        $this->actionprice = doubleval($xml->actionprice[0]);
        $this->todate = intval($xml->todate[0]);
        $this->fromdate = intval($xml->fromdate[0]);
        
        $itemset = (string)($xml->itemset[0]);
        if(strlen($itemset)>0) {
           $this->itemset = unserialize( base64_decode( $itemset) )  ;
        }  else {
           $this->itemset = [];
        }


        parent::afterLoad();
    }

    protected function beforeSave() {
        parent::beforeSave();
        $this->detail = "<detail>";
        //упаковываем  данные в detail
        $this->detail .= "<cost>{$this->cost}</cost>";
        $this->detail .= "<price>{$this->price}</price>";
        $this->detail .= "<hours>{$this->hours}</hours>";
        $this->detail .= "<msr>{$this->msr}</msr>";
        if ($this->actionprice > 0) {
            $this->detail .= "<actionprice>{$this->actionprice}</actionprice>";
        }
        $this->detail .= "<todate>{$this->todate}</todate>";
        $this->detail .= "<fromdate>{$this->fromdate}</fromdate>";
     
        $this->detail .= "<techcard><![CDATA[{$this->techcard}]]></techcard>";
        
        $itemset = $this->itemset ?? [] ;
        $itemset = base64_encode( serialize($itemset) ) ;
        
        $this->detail .= "<itemset><![CDATA[{$itemset}]]></itemset>";
     
        $this->detail .= "</detail>";

        if (strlen($this->category) == 0) {
            $this->category = null;
        }

        return true;
    }

    protected function beforeDelete() {

        $conn = \ZDB\DB::getConnect();
        $sql = "  select count(*)  from  entrylist where   service_id = {$this->service_id}";
        $cnt = $conn->GetOne($sql);
        return ($cnt > 0) ? 'Не можна видаляти послугу, яка використовується' : "";
    }

    public static function getCategoryList() {
        $conn = \ZDB\DB::getConnect();

        $list = $conn->GetCol("select distinct  category from services where  category  is not null order by category ");
        if (is_array($list)) {
            return $list;
        }
        return [];
    }
   public static function getMsrList() {
        $conn = \ZDB\DB::getConnect();

        $msrl=[];
        
        foreach(Service::find("disabled<>1" ) as $s) {
            if(strlen($s->msr)>0) {
               $msrl[$s->msr]=$s->msr;
            }

        }
        return array_values($msrl);
    }

    public function hasAction() {
        if (doubleval($this->actionprice) > 0) {

            if ($this->fromdate < time() && $this->todate > time()) {
                return true;
            }

        }

        return false;
    }

    public function getPurePrice() {
        return $this->price;
    }
    public function getActionPrice() {
        return $this->actionprice;
    }

    public function getPrice($customer_id =0) {

        $pureprice = $this->getPurePrice();
        $price = $pureprice;
        if ($this->hasAction()) {
            $price = $this->getActionPrice();

        }

        //если  нет скидок  проверям  по  контрагенту
        if($pureprice == $price &&  $customer_id  >0) {
            $c = \App\Entity\Customer::load($customer_id) ;
            $d = $c->getDiscount();
            if($d >0) {
                $price = \App\Helper::fa($pureprice - ($pureprice*$d/100)) ;
            }
        }

        return \App\Helper::fa($price);
    }

    public static function getList() {

        $list=[];

        foreach(Service::find("disabled<>1", "service_name") as $s) {
            $name=$s->service_name;
            if(strlen($s->category)>0) {
                $name  = $name . ', '. $s->category;
            }

            $list[$s->service_id]=$name;
        }

        return  $list;
    }

}
