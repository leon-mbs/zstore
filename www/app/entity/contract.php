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
    public const STATE_NEW=0 ;
    public const STATE_NEGOTIATE=2 ;
    public const STATE_SHIFTED=4 ;
    public const STATE_INWORK=6 ;
    public const STATE_CLODED=8 ;
    
    protected function init() {
        $this->contract_id = 0;
        $this->state = 0;
        $this->createdon = time();
    }

    protected function afterLoad() {

        $this->createdon = strtotime($this->createdon);

        $xml = @simplexml_load_string($this->details);

        $this->shortdesc = (string)($xml->shortdesc[0]);
        $this->desc = (string)($xml->desc[0]);
        $this->username = (string)($xml->username[0]);
    
        $this->user_id = (int)($xml->user_id[0]);
        $this->creator_id = (int)($xml->creator_id[0]);
     
        $this->enddate = (int)($xml->enddate[0]);

        parent::afterLoad();
    }

    protected function beforeDelete() {

        $docs = $this->getDocs();
        if (count($docs) > 0) {
            return "Є документи з цим договором";
        }
        return "";
    }

    protected function beforeSave() {
        parent::beforeSave();
        $this->details = "<details>";
        //упаковываем  данные
        $this->details .= "<shortdesc><![CDATA[{$this->shortdesc}]]></shortdesc>";
        $this->details .= "<desc><![CDATA[{$this->desc}]]></desc>";
        $this->details .= "<username><![CDATA[{$this->username}]]></username>";
         
        $this->details .= "<creator_id>{$this->creator_id}</creator_id>";
        $this->details .= "<user_id>{$this->user_id}</user_id>";
       
        $this->details .= "<enddate>{$this->enddate}</enddate>";
        $this->details .= "</details>";

        return true;
    }

    public static function getList($c ) {

        $ar = array();

        if ($c > 0) {
            $where = " state=6 and  customer_id={$c} ";
     
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

        
        foreach (\App\Entity\Doc\Document::findYield($where, 'document_id asc') as $k => $v) {
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

    public  static function getStates() {
        $ret=[];
        $ret[Contract::STATE_NEW]='Новий';
        $ret[Contract::STATE_NEGOTIATE]='Перемовини';
        $ret[Contract::STATE_SHIFTED]='Вiдкдалений';
        $ret[Contract::STATE_INWORK]='В роботi';
        $ret[Contract::STATE_CLODED]='Закритий';
        
        return  $ret;
    }

}
