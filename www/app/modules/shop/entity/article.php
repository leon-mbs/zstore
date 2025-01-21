<?php
 

namespace App\Modules\Shop\Entity;

//класс-сущность статья

/**
 * @keyfield=id
 * @table=shop_articles
 */
class Article extends \ZCL\DB\Entity
{
    protected function init() {
        $this->id = 0;
    }
  
    protected function afterLoad() {
        $this->createdon = strtotime($this->createdon);
    }
}

