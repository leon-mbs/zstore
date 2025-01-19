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
    
    protected function beforeSave() {
        parent::beforeSave();

        if(strlen($this->content) > (1024*1024) ) {
            throw new \Exception('Розмір файлу більше 1M');
        }    
    }
    
}
