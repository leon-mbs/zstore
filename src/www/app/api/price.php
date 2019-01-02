<?php

namespace App\API;

/**
 * Класс  для  экспорта-импорта  прайсв   \
 * пример  вызова /api/Price/1/2
 */
class Price extends \App\RestFul
{

    //Демо
    public function post($params) {
        $this->OKAnswer();
    }

    public function get($params) {

        $xml = "<price>";
        /*
          $conn = \ZDB\DB::getConnect();
          $groups = array();

          $rs = $conn->Execute('select distinct *  from  erp_item_group where  group_id in (select  group_id  from  erp_item where  price  > 0)  order  by  group_name');
          foreach ($rs as $row) {
          $groups[$row['group_id']] = $row['group_name'];
          }



          foreach ($groups as $id => $name) {
          $xml .= "<group>";
          $xml .= "<name><![CDATA[{$name}]]></name>";
          $rs = $conn->Execute("select *  from  erp_item_view where  group_id ={$id} order  by  itemname");
          foreach ($rs as $row) {
          $price = number_format($row['price'] / 100, 2, '.', "");
          $xml .= "<item>";
          $xml .= "<name><![CDATA[{$row['itemname']}]]></name>";
          $xml .= "<price>{$price}</price>";
          $xml .= "<measure>{$row['measure_name']}</measure>";
          $xml .= "<description><![CDATA[{$row['description']}]]></description>";
          $xml .= "</item>";
          }

          $xml .= "</group>";
          }
         */
        $this->XMLAnswer($xml . "</price>");
    }

}
