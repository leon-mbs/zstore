<?php

namespace App\Entity\Doc;

use App\Entity\Entry;
use App\Helper as H;

/**
 * Класс-сущность  документ приходная  накладая
 *
 */
class GoodsReceipt extends Document
{

    public function generateReport() {


        $i = 1;

        $detail = array();
        foreach ($this->detaildata as $value) {
            $detail[] = array("no" => $i++,
                "itemname" => $value['itemname'],
                "itemcode" => $value['item_code'],
                "quantity" => H::fqty($value['quantity']),
                "price" => $value['price'],
                "msr" => $value['msr'],
                "amount" => $value['amount']
            );
        }

        $header = array('date' => date('d.m.Y', $this->document_date),
            "_detail" => $detail,
            "customer_name" => $this->customer_name,
            "document_number" => $this->document_number,
            "total" => $this->headerdata["total"]
        );


        $report = new \App\Report('goodsreceipt.tpl');

        $html = $report->generate($header );

        return $html;
    }

    public function Execute() {
        $types = array();
        $common = \App\System::getOptions("common");

        //аналитика
        foreach ($this->detaildata as $row) {
            $stock = \App\Entity\Stock::getStock($this->headerdata['store'], $row['item_id'], $row['price'], true);


            $sc = new Entry($this->document_id, $row['amount'], $row['quantity']);
            $sc->setStock($stock->stock_id);
            $sc->setExtCode($row['amount']  ); //Для АВС 
            $sc->setCustomer($this->customer_id);

            $sc->save();


            if ($common['useval'] == true) {
                // if($row['old']==true)continue;  //не  меняем для  предыдущих строк
                //запоминаем курс  последней покупки
                $it = \App\Entity\Item::load($row['item_id']);
                $it->curname = $row['curname'];
                $it->currate = $row['currate'];
                $it->save();
            }
        }

        //$total = $this->headerdata['total'];

        return true;
    }

    public function getRelationBased() {
        $list = array();

        // $list['ReturnGoodsReceipt'] = 'Возврат  поставщику';

        return $list;
    }

}
