<?php


namespace App\Entity;

/**
 * Класс-сущность  оплата
 *
 * @table=iostate
 * @keyfield=id
 * @view=iostate_view
 */
class IOState extends \ZCL\DB\Entity
{
    //доход платежи
    const TYPE_BASE_INCOME  = 1;     //операционные доходы
    const TYPE_OTHER_INCOME = 2;   //прочие доходы
    const TYPE_FIN          = 3;   //доходы от  фин.  деятельности
    const TYPE_CANCEL_CUST  = 5;    //отмена  платежа  покупки

    //доход ТМЦ
    const TYPE_OVER = 30;     //излишки при инвентаризации

    //расход платежи
    const TYPE_BASE_OUTCOME     = 50;    //операционные расходы
    const TYPE_COMMON_OUTCOME   = 51;    //общепроизводственные  расходы
    const TYPE_ADMIN_OUTCOME    = 52;    //административные  расходы
    const TYPE_SALE_OUTCOME     = 53;    //расходы на сбыт
    const TYPE_SALARY_OUTCOME   = 54;    //выплата зарплат
    const TYPE_TAX_OUTCOME      = 55;    //уплата  налогов  и сборов
    const TYPE_BILL_OUTCOME     = 56;    //расходы на  аренду и комуналку
    const TYPE_OTHER_OUTCOME    = 57;   //прочие расходы
    const TYPE_DIVIDEND_OUTCOME = 58;   //распределение прибыли
    const TYPE_INV              = 59;   //Инвестиции
    const TYPE_BANK             = 60;   //Банковское  обслуживание
    const TYPE_CANCEL           = 61;    //отмена  платежа  продажи
    const TYPE_NAKL           = 62;    //накладные  расходы

    //потери ТМЦ
    const TYPE_LOST  = 80;     //потери при инвентаризации
    const TYPE_TRASH = 81;     //отходы


    protected function init() {
        $this->id = 0;
        $this->iotype = 0;

    }

    public static function addIOState($document_id, $amount, $type) {
        if (0 == doubleval($amount) || 0 == intval($document_id) || 0 == intval($type) ) {
            return;
        }
        
        $amount = abs($amount) ;
        if( intval($type) >=50) { //расходы
          $amount = 0- $amount;    
        }
        
        
        $io = new  IOState();
        $io->document_id = $document_id;
        $io->amount = $amount;
        $io->iotype = $type;
        $io->save();
    }


    protected function afterLoad() {
        $this->document_date = strtotime($this->document_date);

        parent::afterLoad();
    }

    /**
     * типы  доходов  и расходов
     *
     * @param mixed $type 1= деньги  доход,2-деньги  расход, 3-ТМЦ доход,4 ТМЦ  расход
     */
    public static function getTypeList($type = 0) {
        $list = array();
        if ($type == 1 || $type == 0 || $type == 13) {
            $list[self::TYPE_BASE_INCOME] = "Доходи основної діяльності";

            $list[self::TYPE_FIN] = "Доходи від фінансових операцій";
            $list[self::TYPE_CANCEL_CUST] = "Скасування платежу закупівлі";
            $list[self::TYPE_OTHER_INCOME] = "Інші доходи";
        }

        if ($type == 2 || $type == 0 || $type == 24) {
            $list[self::TYPE_BASE_OUTCOME] = "Операційні витрати";
            $list[self::TYPE_COMMON_OUTCOME] = "Загальновиробничі витрати";
            $list[self::TYPE_ADMIN_OUTCOME] = "Адміністративні витрати";
            $list[self::TYPE_SALE_OUTCOME] = "Витрати на збут";
            $list[self::TYPE_SALARY_OUTCOME] = "Виплата зарплати";
            $list[self::TYPE_TAX_OUTCOME] =  "Податки та збори";
            $list[self::TYPE_BILL_OUTCOME] = "Витрати на оренду та комуналку";
            $list[self::TYPE_DIVIDEND_OUTCOME] = "Розподіл прибутку";
            $list[self::TYPE_INV] = "Інвестиції";
            $list[self::TYPE_BANK] = "Послуги банку";
            $list[self::TYPE_CANCEL] =  "Скасування платежу продажу";
            $list[self::TYPE_NAKL] =  "Накладні витрати";
            $list[self::TYPE_OTHER_OUTCOME] = "Інші витрати";
        }

        if ($type == 3 || $type == 0 || $type == 13) {
            $list[self::TYPE_OVER] = "Надлишки при інвентаризації";
            $list[self::TYPE_OTHER_INCOME] = "Інші доходи";


        }

        if ($type == 4 || $type == 0 || $type == 24) {
            $list[self::TYPE_LOST] = "Втрати при інвентаризації";
            $list[self::TYPE_TRASH] = "Відходи";
            $list[self::TYPE_BASE_OUTCOME] = "Операційні витрати";
            $list[self::TYPE_COMMON_OUTCOME] = "Загальновиробничі витрати";
            $list[self::TYPE_ADMIN_OUTCOME] = "Адміністративні витрати";
            $list[self::TYPE_OTHER_OUTCOME] = "Інші витрати";

        }
        if ($type == 5 ) {
            $list[self::TYPE_BASE_OUTCOME] = "Операційні витрати";
            $list[self::TYPE_COMMON_OUTCOME] = "Загальновиробничі витрати";
            $list[self::TYPE_ADMIN_OUTCOME] = "Адміністративні витрати";
            $list[self::TYPE_OTHER_OUTCOME] = "Інші витрати";
            $list[self::TYPE_SALE_OUTCOME] = "Витрати на збут";
 
        }

        return $list;
    }


}
