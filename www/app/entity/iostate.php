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
    public const TYPE_BASE_INCOME  = 1;     //операционные доходы
    public const TYPE_OTHER_INCOME = 2;     //прочие доходы
    public const TYPE_FIN          = 3;     //доходы от  фин.  деятельности
    public const TYPE_CANCEL_CUST  = 5;     //отмена  платежа  покупки
    public const TYPE_INEQ         = 6;     //ввод в  экплуатацию ОС
    public const TYPE_INVEQ        = 7;     //ремонт и восстановдение ОС

    //внебалансовые доходы (для  статистики)
    public const TYPE_OVER      = 30;     //излишки при инвентаризации
 
    //расход платежи
    public const TYPE_BASE_OUTCOME     = 50;    //операционные расходы
    public const TYPE_COMMON_OUTCOME   = 51;    //общепроизводственные  расходы
    public const TYPE_ADMIN_OUTCOME    = 52;    //административные  расходы
    public const TYPE_SALE_OUTCOME     = 53;    //расходы на сбыт
    public const TYPE_SALARY_OUTCOME   = 54;    //выплата зарплат
    public const TYPE_TAX_OUTCOME      = 55;    //уплата  налогов  и сборов
    public const TYPE_BILL_RENT        = 56;    //расходы на  аренду
    public const TYPE_OTHER_OUTCOME    = 57;   //прочие расходы
    public const TYPE_DIVIDEND_OUTCOME = 58;   //распределение прибыли
    public const TYPE_INV              = 59;   //Инвестиции
    public const TYPE_BANK             = 60;   //Банковское  обслуживание
    public const TYPE_CANCEL           = 61;    //отмена  платежа  продажи
    public const TYPE_NAKL             = 62;    //накладные  расходы
    public const TYPE_ADS              = 63;    //   расходы на  маркетинг
    public const TYPE_BILL_OUTCOME     = 64;    //расходы на  комуналку
    public const TYPE_OUTSERVICE       = 65;    //расходы на услуги
    public const TYPE_OUTEQ            = 66;    // списание ОС
    public const TYPE_AMOR             = 67;    // амортизация ОС

    //внебалансовые расходы (для  статичтики)   
    public const TYPE_LOST             = 80;     //потери при инвентаризации
    public const TYPE_TRASH            = 81;     //отходы производства
 


    protected function init() {
        $this->id = 0;
        $this->iotype = 0;

    }

    /**
    * Добавление  записи  о расходах-доходах
    * 
    * @param mixed $document_id
    * @param mixed $amount
    * @param mixed $type
    * @param mixed $storno   для возвратов
    * @return mixed
    */
    public static function addIOState($document_id, $amount, $type,$storno=false) {
        if (0 == doubleval($amount) || 0 == intval($document_id) || 0 == intval($type)) {
            return;
        }

        $amount = abs($amount) ;
        if(intval($type) >= 50) { //расходы
            $amount = 0 - $amount;
        }

        if($storno) {  
            $amount = 0 - $amount;
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
     * @param mixed $type 1=    доход (для списков),2-   расход (для списков),   0 - все 
     */
    public static function getTypeList($type = 0) {
        $list = array();
        if ($type == 1 ||   $type == 0  ) {
            $list[self::TYPE_BASE_INCOME] = "Доходи основної діяльності";

            $list[self::TYPE_FIN] = "Доходи від фінансових операцій";
            $list[self::TYPE_CANCEL_CUST] = "Скасування платежу закупівлі";
            $list[self::TYPE_OTHER_INCOME] = "Інші доходи";
            $list[self::TYPE_INEQ] = "Інші доходи";
        }

        if ($type == 2 ||   $type == 0  ) {
            $list[self::TYPE_BASE_OUTCOME] = "Операційні витрати";
            $list[self::TYPE_COMMON_OUTCOME] = "Загальновиробничі витрати";
            $list[self::TYPE_ADMIN_OUTCOME] = "Адміністративні витрати";
            $list[self::TYPE_SALE_OUTCOME] = "Витрати на збут";
            $list[self::TYPE_SALARY_OUTCOME] = "Виплата зарплати";
            $list[self::TYPE_TAX_OUTCOME] =  "Податки та збори";
            $list[self::TYPE_BILL_OUTCOME] = "Витрати на комуналку";
            $list[self::TYPE_BILL_RENT] = "Витрати на оренду";
            $list[self::TYPE_DIVIDEND_OUTCOME] = "Розподіл прибутку";
            $list[self::TYPE_INV] = "Інвестиції";
            $list[self::TYPE_BANK] = "Послуги банку";
            $list[self::TYPE_CANCEL] =  "Скасування платежу продажу";
            $list[self::TYPE_NAKL] =  "Накладні витрати";
            $list[self::TYPE_ADS] =  "Витрати на маркетинг та  рекламу";
            $list[self::TYPE_OTHER_OUTCOME] = "Інші витрати";
            $list[self::TYPE_OUTSERVICE] = "Витрати на   послуги";
        }

  
 
 
        if (  $type == 0) {
            $list[self::TYPE_INEQ] = "Ввод в  експлуатацію ОЗ";
            $list[self::TYPE_INVEQ] = "Ремонт та відновлення ОЗ";
            $list[self::TYPE_OUTEQ] = "Списання ОЗ";
            $list[self::TYPE_AMOR] = "Амортизація ОЗ";
            $list[self::TYPE_LOST] = "Втрати при інвентаризації";
            $list[self::TYPE_TRASH] = "Відходи виробництва";
            $list[self::TYPE_OVER] = "Надлишки при інвентаризації";
   
        }

        return $list;
    }


}
