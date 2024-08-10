<?php

namespace App\Entity;

/**
 *  Класс  инкапсулирующий   сущность  файл изображений
 * @table=images
 * @keyfield=image_id
 */
class Image extends \ZCL\DB\Entity
{
    protected function init() {

    }

    public  function getUrlData(){
        $data = $this->thumb;
        $data = strlen($this->thumb ?? '') > 0 ? $this->thumb : $this->content ;

        return "data:" . $this->mime . ";base64," . base64_encode($data);
       
    }
    
}
