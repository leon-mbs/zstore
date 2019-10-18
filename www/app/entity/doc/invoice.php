<?php

namespace App\Entity\Doc;

use \App\Entity\Entry;
use \App\Helper as H;
use \App\Util;

/**
 * Класс-сущность  документ счет фактура
 *
 */
class Invoice extends \App\Entity\Doc\Document {

    public function generateReport() {


        $i = 1;
        $detail = array();

        foreach ($this->detaildata as $value) {

            if (isset($detail[$value['item_id']])) {
                $detail[$value['item_id']]['quantity'] += $value['quantity'];
            } else {
                $detail[] = array("no" => $i++,
                    "tovar_name" => $value['itemname'],
                    "tovar_code" => $value['item_code'],
                    "quantity" => H::fqty($value['quantity']),
                    "price" => $value['price'],
                    "msr" => $value['msr'],
                    "amount" => round($value['quantity'] * $value['price'])
                );
            }
        }

        //$firm = \App\System::getOptions("common");


        $header = array('date' => date('d.m.Y', $this->document_date),
            "_detail" => $detail,
            "customername" => $this->customer_name,
            "phone" => $this->headerdata["phone"],
            "email" => $this->headerdata["email"],
            "notes" => $this->notes,
            "document_number" => $this->document_number,
            "total" => $this->amount,
            "payamount" => $this->payamount,
            "paydisc" => $this->headerdata["paydisc"]
        );
        
 
        $report = new \App\Report('invoice.tpl');

        $html = $report->generate($header);

        return $html;
    }

    public function Execute() {
        //списываем бонусы
        if ($this->headerdata['paydisc'] > 0) {
            $customer = \App\Entity\Customer::load($this->customer_id);
            if($customer->discount > 0){
                 return; //процент
            }
            else {
                $customer->bonus = $customer->bonus - ($this->headerdata['paydisc'] >0 ? $this->headerdata['paydisc']  : 0 );
                $customer->save();
            }
        }
        
        return true;
    }

    public function getRelationBased() {
        $list = array();
        $list['GoodsIssue'] = 'Расходная накладная';
 
        return $list;
    }

    protected function getNumberTemplate(){
         return  'ЗК-000000';
    }      

}
