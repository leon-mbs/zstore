<?php

namespace App\Entity\Doc;

use App\Helper as H;

/**
 * Класс-сущность  документ счет фактура
 *
 */
class Invoice extends \App\Entity\Doc\Document
{
    public function generateReport() {

        $firm = H::getFirmData(  $this->branch_id);
        $mf = \App\Entity\MoneyFund::load($this->headerdata["payment"]);
        $iban=$mf->iban??'';
        
        if(strlen($mf->payname ??'') > 0) $firm['firm_name']   = $mf->payname;
        if(strlen($mf->address ??'') > 0) $firm['address']   = $mf->address;
        if(strlen($mf->tin ??'') > 0) $firm['fedrpou']   = $mf->tin;
        if(strlen($mf->inn ??'') > 0) $firm['finn']   = $mf->inn;
        
 
        $i = 1;
        $detail = array();

        foreach ($this->unpackDetails('detaildata') as $item) {

              $detail[] = array("no"         => $i++,
                              "tovar_name" => strlen($item->itemname) > 0 ? $item->itemname : $item->service_name,
                              "tovar_code" => $item->item_code,
                              "quantity"   => H::fqty($item->quantity),
                              "price"      => H::fa($item->price),
                              "pricenonds"      => H::fa($item->pricenonds),
                              "msr"        => $item->msr,
                              "amount"     => H::fa($item->quantity * $item->price)
              );
        }

        $totalstr =  \App\Util::money2str_ua($this->payamount);

        $header = array('date'            => H::fd($this->document_date),
                        "_detail"         => $detail,
                        "customer_name"   => $this->customer_name,
                        "firm_name"       => $firm['firm_name'],
                       
                        "logo"            => _BASEURL . $firm['logo'],
                        "islogo"          => strlen($firm['logo']) > 0,
                        "stamp"           => _BASEURL . $firm['stamp'],
                        "isstamp"         => strlen($firm['stamp']) > 0,
                        "sign"            => _BASEURL . $firm['sign'],
                        "issign"          => strlen($firm['sign']) > 0,
                        "isfirm"          => strlen($firm["firm_name"]) > 0,
                        "iscontract"      => $this->headerdata["contract_id"] > 0,
                        "iscustaddress"    => false,
                        "phone"           => $this->headerdata["phone"],
                        "customer_print"  => $this->headerdata["customer_print"],
                        "bank"            => $mf->bank ?? "",
                        "bankacc"         => $mf->bankacc ?? "",
                        "isbank"          => (strlen($mf->bankacc??'') > 0 && strlen($mf->bank) > 0),
                        "iban"      => strlen($iban) > 0 ? $iban : false,
                 
                        "notes"           => nl2br($this->notes),
                        "document_number" => $this->document_number,
                        "totalstr"        => $totalstr,
                        "total"           => H::fa($this->amount),
                        "payed"           => $this->payed > 0 ? H::fa($this->payed) : false,
                        "totaldisc"           => $this->headerdata["totaldisc"] > 0 ? H::fa($this->headerdata["totaldisc"]) : false,
                        "payamount"       => $this->payamount > 0 ? H::fa($this->payamount) : false

        );
        if (strlen($this->headerdata["customer_print"]) > 0) {
            $header['customer_name'] = $this->headerdata["customer_print"];
        }

 
        $header["nds"] = false;
        $header["phone"] = false;
        $header["fphone"] = false;
        $header["isfop"] = false;
        $header["edrpou"] = false;
        $header["fedrpou"] = false;
        $header["finn"] = false;
        $cust = \App\Entity\Customer::load($this->customer_id);

        if ($this->getHD('nds',0) > 0) {
            $header["nds"] = H::fa($this->getHD('nds' )) ;
        }
        if (strlen($cust->phone) > 0) {
            $header["phone"] = $cust->phone;
        }
        if (strlen($cust->address) > 0) {
            $header["iscustaddress"] = true;
            $header["custaddress"] = $cust->address;
        }
     
        if (strlen($cust->edrpou) > 0) {
            $header["edrpou"] = $cust->edrpou;
        }
        if (strlen($firm['tin']) > 0) {
            $header["fedrpou"] = $firm['tin'];
        }
        if (strlen($firm['phone']) > 0) {
            $header["fphone"] = $firm['phone'];
        }
                                           
        $header["address"] = $firm['address'];        
        
        if ( ($this->headerdata["fop"] ??0) > 0) {
            $header["isfirm"] = false;
            $header["isfop"] = true;
            
            $fops=$firm['fops']??[];
            $fop = $fops[$this->headerdata["fop"]] ;
            $header["fop_name"] = $fop->name ??'';
            $header["fop_edrpou"] = $fop->edrpou ??'';
            $header["address"] = $fop->address ??'';
        }
     


        if ($this->headerdata["contract_id"] > 0) {
            $contract = \App\Entity\Contract::load($this->headerdata["contract_id"]);
            $header['contract'] = $contract->contract_number;
            $header['createdon'] = H::fd($contract->createdon);
        }

        $report = new \App\Report('doc/invoice.tpl');

        $html = $report->generate($header);

        return $html;
    }

    public function Execute() {
        //списываем бонусы
        if ($this->headerdata['paydisc'] > 0) {
            $customer = \App\Entity\Customer::load($this->customer_id);
            if ($customer->getDiscount() > 0) {
                return; //процент
            } else {
                $customer->bonus = $customer->bonus - ($this->headerdata['paydisc'] > 0 ? $this->headerdata['paydisc'] : 0);
                $customer->save();
            }
        }

        $this->DoBalans() ;

        return true;
    }

    protected function getNumberTemplate() {
        return 'РО-000000';
    }

    public function getRelationBased() {
        $list = array();
        $list['GoodsIssue'] = self::getDesc('GoodsIssue');
        //  $list['Invoice'] = self::getDesc('Invoice');
        $list['TTN'] = self::getDesc('TTN');
    //    $list['ServiceAct'] = self::getDesc('ServiceAct');
   //     $list['POSCheck'] = self::getDesc('POSCheck');

        return $list;
    }

    protected function getEmailBody() {
        $firm = H::getFirmData( $this->branch_id);

        $header = array();
        $header['customer_name'] = $this->customer_name;
        $header['firm_name'] = $firm["firm_name"];
        $header['number'] = $this->document_number;
        $header['date'] = H::fd($this->document_date);
        $header['amount'] = H::fa($this->amount);

        $report = new \App\Report('emails/invoice.tpl');

        $html = $report->generate($header);

        return $html;
    }

    protected function getEmailSubject() {
        return  "Рахунок до оплати номер ".$this->document_number ;
    }

    public function supportedExport() {
        return array(self::EX_EXCEL, self::EX_PDF, self::EX_MAIL);
    }

    /**
    * @override
    */
    public function DoBalans() {
         $conn = \ZDB\DB::getConnect();
         $conn->Execute("delete from custacc where optype in (2,3) and document_id =" . $this->document_id);

         if(($this->customer_id??0) == 0) {
            return;
         }
               
       //платежи       
        foreach($conn->Execute("select abs(amount) as amount ,paydate from paylist  where paytype < 1000 and   coalesce(amount,0) <> 0 and document_id = {$this->document_id}  ") as $p){
            $b = new \App\Entity\CustAcc();
            $b->customer_id = $this->customer_id;
            $b->document_id = $this->document_id;
            $b->amount = $p['amount'];
            $b->createdon = strtotime($p['paydate']);
            $b->optype = \App\Entity\CustAcc::BUYER;
            $b->save();
        }
        $this->DoAcc();             
    }
   public   function DoAcc() {
         if(\App\System::getOption("common",'useacc')!=1 ) return;
         parent::DoAcc()  ;
    
    
         $this->DoAccPay('36'); 
      
                       
    } 
        
}
