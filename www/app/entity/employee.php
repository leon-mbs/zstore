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
        $this->balance = 0;
        $this->ztype = 1;
        $this->zmon = 0;
        $this->advance = 0;
        $this->zhour = 0;
        $this->branch_id = 0;
        $this->children = 0;
    }

    protected function beforeSave() {
        parent::beforeSave();
        //упаковываем  данные в detail
        $this->detail = "<detail><login>{$this->login}</login>";
        //  $this->detail .= "<balance>{$this->balance}</balance>";
        $this->detail .= "<email>{$this->email}</email>";
        $this->detail .= "<phone>{$this->phone}</phone>";
        $this->detail .= "<hiredate>{$this->hiredate}</hiredate>";
        $this->detail .= "<ztype>{$this->ztype}</ztype>";
        $this->detail .= "<zmon>{$this->zmon}</zmon>";
        $this->detail .= "<zhour>{$this->zhour}</zhour>";
        $this->detail .= "<advance>{$this->advance}</advance>";
        $this->detail .= "<children>{$this->children}</children>";
        $this->detail .= "<invalid>{$this->invalid}</invalid>";
        $this->detail .= "<coworker>{$this->coworker}</coworker>";
        $this->detail .= "<comment><![CDATA[{$this->comment}]]></comment>";

        $this->detail .= "</detail>";

        return true;
    }

    protected function afterLoad() {
        //распаковываем  данные из detail
        $xml = simplexml_load_string($this->detail);
        //  $this->balance = (int)($xml->balance[0]);
        $this->login = (string)($xml->login[0]);
        $this->email = (string)($xml->email[0]);
        $this->phone = (string)($xml->phone[0]);
        $this->comment = (string)($xml->comment[0]);
        $this->hiredate = (int)($xml->hiredate[0]);
        $this->ztype = (int)($xml->ztype[0]);
        if ($this->ztype == 0) {
            $this->ztype = 1;
        }
        $this->zmon = (int)($xml->zmon[0]);
        $this->advance = (int)($xml->advance[0]);
        $this->zhour = (int)($xml->zhour[0]);
        $this->children = (int)($xml->children[0]);
        $this->coworker = (int)($xml->coworker[0]);
        $this->invalid = (int)($xml->invalid[0]);

        parent::afterLoad();
    }

    //найти  по  логину
    public static function getByLogin($login) {
        if (strlen($login) == 0) {
            return null;
        }
        $login = Employee::qstr($login);
        return Employee::getFirst("login=" . $login);
    }

    public static function getFreeLogins($include = "") {
        $conn = \ZDB\DB::getConnect();
        $sql = "select distinct  userlogin from users where userlogin not in (select  login  from  employees)";

        if (strlen($include) > 0) {
            $sql .= "  or userlogin=" . $conn->qstr($include);
        }
        $sql .= "  order  by  userlogin ";

        $list = array();
        foreach ($conn->GetCol($sql) as $login) {
            $list[$login] = $login;
        }
        return $list;
    }

    public static function getConstraint() {
        $br = \App\ACL::getBranchConstraint();
        if (strlen($br) > 0) {
            return "({$br}  or branch_id=0)";
        }
    }

}
