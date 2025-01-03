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
        $this->disabled = 0;
        $this->_baseval = 0;
    }

    protected function beforeSave() {
        parent::beforeSave();
        //упаковываем  данные в detail
      //  $this->detail = "<detail><login>{$this->login}</login>";
        //  $this->detail .= "<balance>{$this->balance}</balance>";
        $this->detail = "<detail>";
        $this->detail .= "<email>{$this->email}</email>";
        $this->detail .= "<phone>{$this->phone}</phone>";
        $this->detail .= "<baseval>{$this->_baseval}</baseval>";
        $this->detail .= "<hiredate>{$this->hiredate}</hiredate>";
        $this->detail .= "<ztype>{$this->ztype}</ztype>";
        $this->detail .= "<zmon>{$this->zmon}</zmon>";
        $this->detail .= "<zhour>{$this->zhour}</zhour>";
        $this->detail .= "<advance>{$this->advance}</advance>";
        $this->detail .= "<children>{$this->children}</children>";
        $this->detail .= "<invalid>{$this->invalid}</invalid>";
        $this->detail .= "<coworker>{$this->coworker}</coworker>";
        $this->detail .= "<comment><![CDATA[{$this->comment}]]></comment>";
        $this->detail .= "<department><![CDATA[{$this->department}]]></department>";
        $this->detail .= "<position><![CDATA[{$this->position}]]></position>";

        $this->detail .= "</detail>";

        return true;
    }

    protected function afterLoad() {
        //распаковываем  данные из detail
        $xml = simplexml_load_string($this->detail);
        //  $this->balance = (int)($xml->balance[0]);
      //  $this->login = (string)($xml->login[0]);
        $this->email = (string)($xml->email[0]);
        $this->phone = (string)($xml->phone[0]);
        $this->comment = (string)($xml->comment[0]);
        $this->department = (string)($xml->department[0]);
        $this->position = (string)($xml->position[0]);
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
        $this->_baseval = doubleval($xml->baseval[0]);

     
        
        parent::afterLoad();
    }

    public function beforeDelete() {

      /*  $conn = \ZDB\DB::getConnect();

        $sql = "  select count(*)  from  documents where   customer_id = {$this->customer_id}  ";
        $cnt = $conn->GetOne($sql);
        if ($cnt > 0) {
            return  "Контрагент використовується в документах";
        }
        */
        return "";
    }    
  
    protected function afterDelete() {

        $conn = \ZDB\DB::getConnect();
        $conn->Execute("delete from messages where item_type=" . \App\Entity\Message::TYPE_EMP . " and item_id=" . $this->employee_id);

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
  
    /**
    * список отделов и должностей
    * 
    */
    public static function getDP() {
        $p=[];
        $d=[];
        
        foreach(Employee::findYield("disabled<> 1") as $e ){
            if(strlen($e->department)>0) {
                if(!in_array($e->department,$d)) {
                    $d[]=$e->department;
                }
            }
            if(strlen($e->position)>0) {
                if(!in_array($e->position,$p)) {
                    $p[]=$e->position;
                }
            }
                         
        }
        natsort($p) ;
        natsort($d) ;
        
        return array('p'=>$p,'d'=>$d);
        
    }
    

}
