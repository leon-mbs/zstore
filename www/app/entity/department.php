<?php

namespace App\Entity;

/**
 * Класс-сущность отдел
 *
 * @table=departments
 * @keyfield=department_id
 */
class Department extends \ZCL\DB\Entity
{
    protected function init() {
        $this->department_id = 0;

    }

}
