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

        $firm = H::getFirmData($this->firm_id, $this->branch_id);
        $mf = \App\Entity\MoneyFund::load($this->headerdata["payment"]);

        $i = 1;
        $detail = array();

        foreach ($this->unpackDetails('detaildata') as $item) {

            if (isset($detail[$item->item_id])) {
                $detail[$item->item_id]['quantity'] += $item->quantity;
            } else {
                $detail[] = array("no"         => $i++,
                                  "tovar_name" => strlen($item->itemname) > 0 ? $item->itemname : $item->service_name,
                                  "tovar_code" => $item->item_code,
                                  "quantity"   => H::fqty($item->quantity),
                                  "price"      => H::fa($item->price),
                                  "msr"        => $item->msr,
                                  "amount"     => H::fa($item->quantity * $item->price)
                );
            }
        }

        $totalstr = H::sumstr($this->payamount);

        $header = array('date'            => H::fd($this->document_date),
                        "_detail"         => $detail,
                        "customer_name"   => $this->customer_name,
                        "firm_name"       => $firm['firm_name'],
                        "firm_address"    => $firm['address'],
                        "logo"            => _BASEURL . $firm['logo'],
                        "islogo"          => strlen($firm['logo']) > 0,
                        "stamp"           => _BASEURL . $firm['stamp'],
                        "isstamp"         => strlen($firm['stamp']) > 0,
                        "sign"            => _BASEURL . $firm['sign'],
                        "issign"          => strlen($firm['sign']) > 0,
                        "isfirm"          => strlen($firm["firm_name"]) > 0,
                        "iscontract"      => $this->headerdata["contract_id"] > 0,
                        "phone"           => $this->headerdata["phone"],
                        "customer_print"  => $this->headerdata["customer_print"],
                        "bank"            => @$mf->bank,
                        "bankacc"         => @$mf->bankacc,
                        "isbank"          => (strlen($mf->bankacc) > 0 && strlen($mf->bank) > 0),
                        "email"           => $this->headerdata["email"],
                        "notes"           => nl2br($this->notes),
                        "document_number" => $this->document_number,
                        "totalstr"        => $totalstr,
                        "total"           => H::fa($this->amount),
                        "payed"           => $this->payed > 0 ? H::fa($this->payed) : false,
                        "payamount"       => $this->payamount > 0 ? H::fa($this->payamount) : false,
                        "paydisc"         => H::fa($this->headerdata["paydisc"])
        );
        if (strlen($this->headerdata["customer_print"]) > 0) {
            $header['customer_name'] = $this->headerdata["customer_print"];
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
            if ($customer->discount > 0) {
                return; //процент
            } else {
                $customer->bonus = $customer->bonus - ($this->headerdata['paydisc'] > 0 ? $this->headerdata['paydisc'] : 0);
                $customer->save();
            }
        }

        if ($this->headerdata['payment'] > 0 && $this->payed > 0) {
            $payed = \App\Entity\Pay::addPayment($this->document_id, $this->document_date, $this->payed, $this->headerdata['payment'], \App\Entity\IOState::TYPE_BASE_INCOME);
            if ($payed > 0) {
                $this->payed = $payed;
            }
            \App\Entity\IOState::addIOState($this->document_id, $this->payed, \App\Entity\IOState::TYPE_BASE_INCOME);

        }
        return true;
    }

    protected function getNumberTemplate() {
        return 'СФ-000000';
    }

    public function getRelationBased() {
        $list = array();
        $list['GoodsIssue'] = self::getDesc('GoodsIssue');
        $list['Invoice'] = self::getDesc('Invoice');
        $list['TTN'] = self::getDesc('TTN');
        $list['ServiceAct'] = self::getDesc('ServiceAct');

        return $list;
    }

    protected function getEmailBody() {
        $firm = H::getFirmData($this->firm_id, $this->branch_id);

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
        return H::l('emailinvsub', $this->document_number);
    }

    public function supportedExport() {
        return array(self::EX_EXCEL, self::EX_PDF, self::EX_MAIL);
    }

}
