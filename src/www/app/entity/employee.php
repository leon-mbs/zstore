<?php

namespace App\Entity;

/**
 * Клас-сущность  сотрудник
 *
 * @table=employees
 * @keyfield=employee_id
 */
class Employee extends \ZCL\DB\Entity
{

  
    protected function init() {
        $this->employee_id = 0;
    }

    protected function beforeSave() {
        parent::beforeSave();
        //упаковываем  данные в detail
        $this->detail = "<detail><login>{$this->login}</login>";
        $this->detail .= "<email>{$this->email}</email>";
        $this->detail .= "<comment>{$this->comment}</comment>";

        $this->detail .= "</detail>";

        return true;
    }

    protected function afterLoad() {
        //распаковываем  данные из detail
        $xml = simplexml_load_string($this->detail);
        $this->login = (string) ($xml->login[0]);
        $this->email = (string) ($xml->email[0]);
        $this->comment = (string) ($xml->comment[0]);


        parent::afterLoad();
    }

}
