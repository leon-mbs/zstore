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
    }

    protected function afterLoad() {


        $xml = @simplexml_load_string($this->detail);

        $this->hours = (string)($xml->hours[0]);
        $this->price = (string)($xml->price[0]);
        $this->cost = (string)($xml->cost[0]);
        $this->actionprice = doubleval($xml->actionprice[0]);
        $this->todate = intval($xml->todate[0]);
        $this->fromdate = intval($xml->fromdate[0]);

        parent::afterLoad();
    }

    protected function beforeSave() {
        parent::beforeSave();
        $this->detail = "<detail>";
        //упаковываем  данные в detail
        $this->detail .= "<cost>{$this->cost}</cost>";
        $this->detail .= "<price>{$this->price}</price>";
        $this->detail .= "<hours>{$this->hours}</hours>";
        if ($this->actionprice > 0) {
            $this->detail .= "<actionprice>{$this->actionprice}</actionprice>";
        }
        $this->detail .= "<todate>{$this->todate}</todate>";
        $this->detail .= "<fromdate>{$this->fromdate}</fromdate>";

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
        return ($cnt > 0) ? \App\Helper::l('nodelservice') : "";
    }

    public static function getCategoryList() {
        $conn = \Zdb\DB::getConnect();

        $list = $conn->GetCol("select distinct  category from services where  category  is not null order by category ");
        if (is_array($list)) {
            return $list;
        }
        return array();
    }

    public function hasAction() {
        if (doubleval($this->actionprice) > 0) {

            if ($this->fromdate < time() && $this->todate > time()) {
                return true;
            }

        }

        return false;
    }

    public function getPrice() {

        $price = $this->price;
        if ($this->hasAction()) {
            $price = $this->actionprice;

        }

        return \App\Helper::fa($price);
    }

}
