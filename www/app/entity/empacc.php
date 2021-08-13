<?php

namespace App\Entity;

/**
 * Клас-сущность  личный счет  сотрудника
 *
 * @table=empacc
 * @view=empacc_view
 * @keyfield=ea_id
 */
class EmpAcc extends \ZCL\DB\Entity
{
    const  INCOME_FROM_MF =1; // перечисление  со  счета
    const  OUTCOME_TO_MF =2; //  перечисление на  счет
    const  SALARY = 3; //  начисления  зарплаты
    const  SALARY_PAY = 4; //  выплата  зарплаты
    const  ADVANCE_ACC = 5; // авансовый отчет
    const  ADVANCE = 6; // аванс
    
    
    protected function init() {

        $this->ea_id = 0;
        
    }

 

}
