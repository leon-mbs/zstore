<?php

namespace App\Entity\Doc;

use \App\Entity\Entry;
use \App\Helper as H;
use \App\Util;

/**
 * Класс-сущность  документ  списание в  производство 
 *
 */
class ProdIssue extends Document {

    public function generateReport() {


        $i = 1;
        $detail = array();

        foreach ($this->detaildata as $value) {

            if (isset($detail[$value['item_id']])) {
                $detail[$value['item_id']]['quantity'] += $value['quantity'];
            } else {
                $name =$value['itemname'];
                if(strlen($value['snumber'])>0){
                   $name .= ' ('.$value['snumber'].','.date('Y-m-d',$value['sdate']).')';  
                }
                
                
                $detail[] = array("no" => $i++,
                    "tovar_name" => $name,
                    "tovar_code" => $value['item_code'],
                    "quantity" => H::fqty($value['quantity']),
                    "price" => $value['price'],
                    "msr" => $value['msr'],
                    "amount" => round($value['quantity'] * $value['price'])
                );
            }
        }

        $firm = \App\System::getOptions("common");


        $header = array('date' => date('d.m.Y', $this->document_date),
            "_detail" => $detail,
            "firmname" => $firm['firmname'],
            "pareaname" => $this->headerdata["pareaname"],
            "document_number" => $this->document_number,
            "total" => $this->headerdata["total"],
            "notes" => $this->notes,
            "summa" => Util::ucfirst(Util::money2str($this->headerdata["total"]))
        );

        $report = new \App\Report('prodissue.tpl');

        $html = $report->generate($header);

        return $html;
    }

    public function Execute() {
        $conn = \ZDB\DB::getConnect();
        $conn->StartTrans();

        foreach ($this->detaildata as $row) {

            $sc = new Entry($this->document_id, 0 - $row['amount'], 0 - $row['quantity']);
            $sc->setStock($row['stock_id']);
            $sc->save();
        }

        $conn->CompleteTrans();
        return true;
    }

    public function getRelationBased() {
        $list = array();

        return $list;
    }

}
