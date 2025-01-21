<?php

namespace App\Entity;

/**
 * сущность для хранения движения по  ОС
 *
 * @view=eqentry_view
 * @table=eqentry
 * @keyfield=id
 */
class EqEntry extends \ZCL\DB\Entity
{  
    public const OP_INCOME = 1;
    public const OP_OUTCOME = 2;
    public const OP_AMOR = 3;
    public const OP_REM = 4;
    public const OP_MOVE = 5;

    public function __construct(   ) {
        parent::__construct();
        
    }

    protected function init() {
        $this->amount;
    }

    protected function afterLoad() {
        $this->document_date = strtotime($this->document_date);
    }

    public static function getOpName(int $t){
        if($t==self::OP_INCOME) return 'Ввод в  експлуатацію' ;
        if($t==self::OP_OUTCOME) return 'Виведення з експлуатації' ;
        if($t==self::OP_AMOR) return 'Амортизація' ;
        if($t==self::OP_REM) return 'Ремонт та  відновлення' ;
        if($t==self::OP_MOVE) return 'Переміщення' ;
        return 'N/A' ;
   }   
}
