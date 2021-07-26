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
        $this->payname = (string)($xml->payname[0]);
        $this->pay = (int)($xml->pay[0]);
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
        $this->details .= "<payname><![CDATA[{$this->payname}]]></payname>";
        $this->details .= "<emp_name><![CDATA[{$this->emp_name}]]></emp_name>";
        $this->details .= "<pay>{$this->pay}</pay>";
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

        $ar = array();

        $where = "  customer_id={$this->customer_id} and  content like '%<contract_id>{$this->contract_id}</contract_id>%'  ";

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

        $ar = array();

        $where = " document_id in (select document_id from  documents where  customer_id={$this->customer_id} and  content like '%<contract_id>{$this->contract_id}</contract_id>%'  )";

        $res = \App\Entity\Pay::find($where, 'pl_id asc');
        foreach ($res as $k => $v) {
            $ar[$k] = $v;
        }


        return $ar;
    }

    /**
     * к оплате
     *
     */
    public function getDolg() {

        $amount = 0;

        $where = "  customer_id={$this->customer_id} and   content like '%<contract_id>{$this->contract_id}</contract_id>%'  ";

        if ($this->ctype == 1) {
            $_docs = " and ( meta_name in('GoodsIssue','Invoice','RetCustIssue','PosCheck','ServiceAct','Order')  or  (meta_name='IncomeMoney'  and content like '%<detail>1</detail>%'  )  or  (meta_name='OutcomeMoney'  and content like '%<detail>2</detail>%'  ))  ";

            $sql .= "
        select   sum((case when   meta_name='OutcomeMoney' then  (payed - payamount )   else  (payamount - payed)  end) ) as sam 
            from `documents_view`  
            where   {$where} and   (payamount >0  or  payed >0) {$_docs}  and state not in (1,2,3,17,8)   and  ( (meta_name <>'POSCheck' and payamount <> payed) or(meta_name = 'POSCheck' and payamount > payed  ))
            
            ";
        } else {
            if ($this->ctype == 2) {
                $_docs = " and ( meta_name in('GoodsReceipt','InvoiceCust','ReturnIssue')  or  (meta_name='OutcomeMoney'  and content like '%<detail>1</detail>%'  )  or  (meta_name='IncomeMoney'  and content like '%<detail>2</detail>%'  ))  ";

                $sql .= "
        select   sum((case when   meta_name='IncomeMoney' then  (payed - payamount )   else  (payamount - payed)  end) ) as sam 
            from `documents_view`  
            where   {$where} and   (payamount >0  or  payed >0) {$_docs}  and state not in (1,2,3,17,8)   and payamount <> payed 
            
            ";
            } else {
                return 0;
            }
        }
        $conn = \ZDB\DB::getConnect();
        $amount = $conn->GetOne($sql);

        return $amount;
    }

}
