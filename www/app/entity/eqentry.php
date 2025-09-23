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
    public const OP_REPAIR = 4;
    public const OP_MOVE = 5;
    public const OP_BUY = 6;
    public const OP_PROD = 7;
    public const OP_STORE = 8;
    public const OP_SELL = 9;
    public const OP_TOSTORE = 10;
    public const OP_LOST = 11;

    public function __construct(   ) {
        parent::__construct();
        
    }

    protected function init() {
        $this->amount;
    }

    protected function afterLoad() {
        $this->document_date = strtotime($this->document_date);
    }

    public static function getOpList(){
        
        $list=[];
        $list[self::OP_INCOME] = 'Ввод в експлуатацію' ;
        $list[self::OP_OUTCOME] = 'Виведення з експлуатації' ;
        $list[self::OP_AMOR] = 'Нарахування амортизвції' ;
        $list[self::OP_REPAIR] = 'Ремонт та відновлення' ;
        $list[self::OP_MOVE] = 'Переміщення між вир. ділянками ' ;
        $list[self::OP_BUY] = 'Закупка' ;
        $list[self::OP_PROD] = 'Оприходування з виробництва' ;
        $list[self::OP_STORE] = 'Оприходування зі складу' ;
        $list[self::OP_SELL] = 'Продажа' ;
        $list[self::OP_TOSTORE] = 'Оприходування на склад' ;
        $list[self::OP_LOST] = 'Списання' ;
        
        return $list ;
   }   
}
