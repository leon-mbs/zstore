<?php

namespace App\Modules\Tecdoc;

class APIHelper
{

    private $type;
    private $api;

    public function __construct($type = 'passenger') {
        $this->type = $type;
        $modules = \App\System::getOptions("modules");

        $this->token = $modules['td_code'];
        $this->email = $modules['td_email'];

        $this->api = rtrim($modules['td_host'], '/') . '/api';
    }

    private function fetch($plist) {
        $ask = "";

        foreach ($plist as $k => $v) {
            $ask = $ask . '&' . $k . '=' . $v;
        }
        $ask = trim($ask, '&');

        $url = $this->api . "?" . $ask;
        try {
            $ret = \Fetch\fetch($this->api . "?" . $ask, [
                'headers' => [
                    'Accept' => 'application/json',
                ],
            ])->json(true);
        } catch(\Exception $e) {
            return array('success' => false, 'error' => $e->getMessage());
        }

        if (is_array($ret)) {
            return $ret;
        } else {
            return array('success' => false, 'error' => 'API service  error');
        }
    }

    public function getAllBrands() {


        return $this->fetch(array('cmd' => 'getAllBrands'));
    }

    public function getManufacturers() {


        return $this->fetch(array('cmd' => 'getManufacturers', 'type' => $this->type));
    }

    public function getModels($brand_id) {


        return $this->fetch(array('cmd' => 'getModels', 'type' => $this->type, 'brand_id' => $brand_id));
    }

    public function getModifs($model_id) {
        return $this->fetch(array('cmd' => 'getModifs', 'type' => $this->type, 'model_id' => $model_id));
    }

    public function getModifDetail($modif_id) {
        return $this->fetch(array('cmd' => 'getModifDetail', 'type' => $this->type, 'modif_id' => $modif_id));
    }

    public function getTree($modif_id) {
        return $this->fetch(array('cmd' => 'getTree', 'type' => $this->type, 'modif_id' => $modif_id));
    }

    public function searchByCategory($id, $modif_id) {
        return $this->fetch(array('cmd' => 'searchByCategory', 'type' => $this->type, 'modif_id' => $modif_id, 'node_id' => $id));
    }

    public function searchByBrandAndCode($code, $brand) {

        return $this->fetch(array('cmd' => 'searchByBrandAndCode', 'partnumber' => $code, 'brand' => $brand));
    }

    public function searchByBarCode($barcode) {

        return $this->fetch(array('cmd' => 'searchByBarCode', 'ean' => $barcode));
    }

    public function getAttributes($number, $brand_id) {
        return $this->fetch(array('cmd' => 'getAttributes', 'partnumber' => $number, 'brand_id' => $brand_id));
    }

    public function getImage($number, $brand_id) {
        return $this->fetch(array('cmd' => 'getImage', 'partnumber' => $number, 'brand_id' => $brand_id));
    }

    //Оригинальные  номера
    public function getOemNumbers($number, $brand_id) {
        return $this->fetch(array('cmd' => 'getOemNumbers', 'partnumber' => $number, 'brand_id' => $brand_id));
    }

    //Замены
    public function getReplace($number, $brand_id) {

        return $this->fetch(array('cmd' => 'getReplace', 'partnumber' => $number, 'brand_id' => $brand_id));
    }

    //Составные части
    public function getArtParts($number, $brand_id) {

        return $this->fetch(array('cmd' => 'getArtParts', 'partnumber' => $number, 'brand_id' => $brand_id));
    }

    //Аналоги
    public function getArtCross($number, $brand_id) {

        return $this->fetch(array('cmd' => 'getArtCross', 'partnumber' => $number, 'brand_id' => $brand_id));
    }

    //Применимость
    public function getArtVehicles($number, $brand_id) {

        return $this->fetch(array('cmd' => 'getArtVehicles', 'partnumber' => $number, 'brand_id' => $brand_id));
    }

}
