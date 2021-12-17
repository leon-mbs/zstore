<?php

namespace App\Entity\Doc;

use App\Entity\Entry;
use App\Entity\Item;
use App\Helper as H;

/**
 * Класс-сущность  документ приходная  накладая
 *
 */
class GoodsReceipt extends Document
{

    public function generateReport() {
        $firm = H::getFirmData($this->firm_id, $this->branch_id);

        $i = 1;

        $detail = array();
        foreach ($this->unpackDetails('detaildata') as $item) {
            $name = $item->itemname;
            if (strlen($item->snumber) > 0) {
                $name .= ' (' . $item->snumber . ',' . H::fd($item->sdate) . ')';
            }

            $detail[] = array("no"       => $i++,
                              "itemname" => $name,
                              "snumber"  => $item->snumber,
                              "itemcode" => $item->item_code,
                              "barcode" => $item->bar_code,
                              "quantity" => H::fqty($item->quantity),
                              "price"    => H::fa($item->price),
                              "msr"      => $item->msr,
                              "amount"   => H::fa($item->quantity * $item->price)
            );
        }

        $header = array('date'            => H::fd($this->document_date),
                        "_detail"         => $detail,
                        "basedoc"         => $this->headerdata["basedoc"],
                        "firm_name"       => $firm['firm_name'],
                        "isfirm"          => strlen($firm["firm_name"]) > 0,
                        "iscontract"      => $this->headerdata["contract_id"] > 0,
                        "isval"           => ($this->_doc->headerdata['val']) > 1,
                        "customer_name"   => $this->customer_name,
                        "document_number" => $this->document_number,
                        "total"           => H::fa($this->amount),
                        "payed"           => $this->payed > 0 ? H::fa($this->payed) : false,
                        "payamount"       => $this->payamount > 0 ? H::fa($this->payamount) : false
        );
        if ($this->headerdata["contract_id"] > 0) {
            $contract = \App\Entity\Contract::load($this->headerdata["contract_id"]);
            $header['contract'] = $contract->contract_number;
            $header['createdon'] = H::fd($contract->createdon);
        }

        $header['isdisc'] = $this->headerdata["disc"] > 0;
        $header['isnds'] = $this->headerdata["nds"] > 0;
        $header['isval'] = strlen($this->headerdata['val']) > 1;

        $header['disc'] = H::fa($this->headerdata["disc"]);
        $header['nds'] = H::fa($this->headerdata["nds"]);
        $header['rate'] = $this->headerdata["rate"];
        if ($header['rate'] == 0 || $header['rate'] == 1) {
            $header['isval'] = false;
        }
        $val = H::getValList();
        $header['val'] = $val[$this->headerdata['val']];

        $report = new \App\Report('doc/goodsreceipt.tpl');

        $html = $report->generate($header);

        return $html;
    }

    public function Execute() {
        $types = array();
        $common = \App\System::getOptions("common");
        if ($this->amount == 0) {
            // return;
        }
        //аналитика
        foreach ($this->unpackDetails('detaildata') as $item) {


            $total = $this->amount;
            if ($total > 0) {
                if ($this->headerdata["disc"] > 0) {
                    $total = $total - $this->headerdata["disc"];
                }
                if ($this->headerdata["nds"] > 0) {
                    $total = $total + $this->headerdata["nds"];
                }
                if (($this->headerdata["rate"] != 0) && ($this->headerdata["rate"] != 1)) {
                    $total = $total * $this->headerdata["rate"];
                }
                $k = $total / $this->amount;
                $item->price = H::fa($item->price * $k); //пересчитываем  учетную цену
            } else {
                $item->price = 0;
            }
            $item->amount = $item->price * $item->quantity;
            $stock = \App\Entity\Stock::getStock($this->headerdata['store'], $item->item_id, $item->price, $item->snumber, $item->sdate, true);

            $sc = new Entry($this->document_id, $item->price * $item->quantity, $item->quantity);
            $sc->setStock($stock->stock_id);
            // $sc->setExtCode($item->price); //Для АВС
            $sc->setOutPrice($item->price);

            $sc->save();

            //запоминаем  курс
            if (strlen($this->headerdata['val']) > 1 && $this->headerdata['rate'] != 0 && $this->headerdata['rate'] != 1) {
                $it = Item::load($item->item_id);
                $it->val = $this->headerdata['val'];
                $it->rate = $this->headerdata['rate'];
                $it->save();
            }
        }


        if ($this->headerdata['payment'] > 0 && $this->payed > 0) {
            $payed = \App\Entity\Pay::addPayment($this->document_id, $this->document_date, 0 - $this->payed, $this->headerdata['payment'], \App\Entity\IOState::TYPE_BASE_OUTCOME);
            if ($payed > 0) {
                $this->payed = $payed;
            }

            \App\Entity\IOState::addIOState($this->document_id, 0 - $this->payed, \App\Entity\IOState::TYPE_BASE_OUTCOME);


        }

        return true;
    }

    protected function getNumberTemplate() {
        return 'ПН-000000';
    }

    public function getRelationBased() {
        $list = array();

        $list['RetCustIssue'] = self::getDesc('RetCustIssue');
        $list['GoodsReceipt'] = self::getDesc('GoodsReceipt');
        $list['ProdIssue'] = self::getDesc('ProdIssue');

        return $list;
    }

}
