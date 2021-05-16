<?php
  
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
    const TYPE_BASE_INCOME      = 1;     //операционные доходы
    const TYPE_OTHER_INCOME     = 2;   //прочие доходы
    const TYPE_FIN              = 3;   //доходы от  фин.  деятельности
    const TYPE_CANCEL_CUST      = 5;    //отмена  платежа  покупки
   
    //доход ТМЦ
    const TYPE_OVER      = 30;     //излишки при инвентаризации
    
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
    const TYPE_CANCEL           = 58;    //отмена  платежа  продажи

    //потери ТМЦ
    const TYPE_LOST       = 80;     //потери при инвентаризации
    const TYPE_TRASH      = 81;     //отходы
     
    
    protected function init() {
        $this->id = 0;
        $this->iotype = 0;
        
    }
  
      protected function afterLoad() {
        $this->document_date = strtotime($this->document_date);
        
        parent::afterLoad();
    }  
}
