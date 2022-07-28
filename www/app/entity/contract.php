<?php

namespace App\Entity;

/**
 * Клас-сущность  договор
 *
 * @table=contracts
 * @view=contracts_view
 * @keyfield=contract_id
 */
class Contract extends \ZCL\DB\Entity
{

    protected function init() {
        $this->contract_id = 0;
        $this->createdon = time();
    }

    protected function afterLoad() {

        $this->createdon = strtotime($this->createdon);

        $xml = @simplexml_load_string($this->details);

        $this->shortdesc = (string)($xml->shortdesc[0]);
        $this->desc = (string)($xml->desc[0]);
        $this->emp_name = (string)($xml->emp_name[0]);
//        $this->pay = (int)($xml->pay[0]);
        $this->file_id = (int)($xml->file_id[0]);
        $this->emp_id = (int)($xml->emp_id[0]);
        $this->ctype = (int)($xml->ctype[0]);
        $this->enddate = (int)($xml->enddate[0]);

        parent::afterLoad();
    }

    protected function beforeDelete() {

        $docs = $this->getDocs();
        if (count($docs) > 0) {
            return \App\Helper::l("iscontractdocs");
        }
        return "";
    }

    protected function beforeSave() {
        parent::beforeSave();
        $this->details = "<details>";
        //упаковываем  данные  
        $this->details .= "<shortdesc><![CDATA[{$this->shortdesc}]]></shortdesc>";
        $this->details .= "<desc><![CDATA[{$this->desc}]]></desc>";
        $this->details .= "<emp_name><![CDATA[{$this->emp_name}]]></emp_name>";
    //    $this->details .= "<pay>{$this->pay}</pay>";
        $this->details .= "<file_id>{$this->file_id}</file_id>";
        $this->details .= "<emp_id>{$this->emp_id}</emp_id>";
        $this->details .= "<ctype>{$this->ctype}</ctype>";
        $this->details .= "<enddate>{$this->enddate}</enddate>";
        $this->details .= "</details>";

        return true;
    }

    public static function getList($c, $f = 0) {

        $ar = array();

        if ($c > 0) {
            $where = "disabled <> 1 and customer_id={$c} ";
            if ($f > 0) {
                $where .= " and firm_id =  " . $f;
            }
            $res = Contract::find($where, 'contract_number');
            foreach ($res as $k => $v) {
                $ar[$k] = $v->contract_number . ' ' . $v->shortdesc;
            }
        }

        return $ar;
    }

    /**
     * список  документов
     *
     */
    public function getDocs() {
        $br = "";
        $c = \App\ACL::getBranchConstraint();
        if (strlen($c) > 0) {
            $br = " {$c} and ";
        }    

        $ar = array();

        $where = "  customer_id={$this->customer_id}  and {$br}     content like '%<contract_id>{$this->contract_id}</contract_id>%'  ";

        $res = \App\Entity\Doc\Document::find($where, 'document_id asc');
        foreach ($res as $k => $v) {
            $ar[$k] = $v;
        }


        return $ar;
    }

    /**
     * список  платежей
     *
     */
    public function getPayments() {
        $brd = "";
        $brmf = "";
        $c = \App\ACL::getBranchConstraint();
        if (strlen($c) > 0) {
            $brd = " {$c} and ";
            $brmf = "  mf_id in( select mf_id from mfund  where {$c} )  and ";
        }    

        $ar = array();   

        $where = " {$brmf} document_id in (select document_id from  documents where {$brd} customer_id={$this->customer_id} and  content like '%<contract_id>{$this->contract_id}</contract_id>%'  )";

        $res = \App\Entity\Pay::find($where, 'pl_id asc');
        foreach ($res as $k => $v) {
            $ar[$k] = $v;
        }


        return $ar;
    }

 

}
