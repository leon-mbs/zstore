<?php

namespace App\Entity;

/**
 * Клас-сущность  филиал
 *
 * @table=branches
 * @keyfield=branch_id
 */
class Branch extends \ZCL\DB\Entity
{

    protected function init() {
        $this->branch_id = 0;
        $this->disabled = 0;
    }

    protected function beforeSave() {
        parent::beforeSave();
        //упаковываем  данные в detail
        $this->details = "<details>";

        $this->details .= "<address><![CDATA[{$this->address}]]></address>";
        $this->details .= "<comment><![CDATA[{$this->comment}]]></comment>";
        $this->details .= "<company_id>{$this->company_id}</company_id>";

        $this->details .= "</details>";

        return true;
    }

    protected function afterLoad() {
        //распаковываем  данные из detail
        $xml = simplexml_load_string($this->details);

        $this->address = (string)($xml->address[0]);

        $this->company_id = (int)($xml->company_id[0]);
        $this->comment = (string)($xml->comment[0]);

        parent::afterLoad();
    }

    public function beforeDelete() {

        $conn = \ZDB\DB::getConnect();
        $sql = "  select count(*)  from  documents where   branch_id = {$this->branch_id}";
        $cnt = $conn->GetOne($sql);
        if ($cnt > 0) {
            return "На  филиал есть  ссылки  в  документах";
        }

        $sql = "  select count(*)  from  stores where   branch_id = {$this->branch_id}";
        $cnt = $conn->GetOne($sql);
        if ($cnt > 0) {
            return "Филиал используется в складах";
        }

        $sql = "  select count(*)  from  mfund where   branch_id = {$this->branch_id}";
        $cnt = $conn->GetOne($sql);
        if ($cnt > 0) {
            return "Филиал используется в денежных счетах";
        }
        $sql = "  select count(*)  from  employees where   branch_id = {$this->branch_id}";
        $cnt = $conn->GetOne($sql);
        if ($cnt > 0) {
            return "Филиал используется в сотрудниках";
        }

        return "";
    }

    /**
     * список  филиалов
     *
     * @param mixed $user_id
     */
    public static function getList($user_id = 0) {
        $where = "disabled<>1";
        if ($user_id > 0) {
            $user = \App\Entity\User::load($user_id);
            if ($user->rolename != 'admins') {
                if (strlen($user->aclbranch) == 0) {
                    return array();
                } else {
                    $where .= " and branch_id in ({$user->aclbranch}) ";
                }
            }
        }

        return Branch::findArray("branch_name", $where, "branch_name");
    }

}
