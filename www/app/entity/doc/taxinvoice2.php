<?php

namespace ZippyERP\ERP\Entity\Doc;

use ZippyERP\ERP\Helper as H;
use ZippyERP\ERP\Util;
use ZippyERP\System\System;

/**
 * Класс-сущность  документ налоговая  накладая
 *
 */
class TaxInvoice2 extends Document
{

    public function generateReport() {


        $i = 1;
        $detail = array();
        $total = 0;
        foreach ($this->detaildata as $value) {
            $detail[] = array("no" => $i++,
                "date" => "",
                "tovar_name" => $value['itemname'],
                "tovar_code" => "",
                "measure_name" => $value['measure_name'],
                "measure_code" => $value['measure_code'],
                "quantity" => $value['quantity'] / 1000,
                "price" => H::famt($value['price']),
                "pricends" => H::famt($value['pricends']),
                "amount" => H::famt($value['quantity'] * $value['price'])
            );
            $total += ($value['quantity'] / 1000) * $value['price'];
        }

        $firm = System::getOptions("firmdetail");
        $customer = \ZippyERP\ERP\Entity\Customer::load($this->headerdata["customer"]);
        $contract = Document::load($this->headerdata["contract"]);
        if ($contract instanceof Document) {
            $contractnumber = $contract->document_number;
            $contractname = $contract->meta_desc;
            $contractdate = date('dmY', $contract->document_date);
        }
        $header = array('date' => date('dmY', $this->document_date),
            "firmname" => $firm['name'],
            "firmcode" => Util::addSpaces($firm['inn']),
            "customername" => $customer->customer_name,
            "customercode" => Util::addSpaces($customer->inn),
            "saddress" => $firm['street'] . ',' . $firm['city'],
            "baddress" => $customer->street . ',' . $customer->city,
            "sphone" => Util::addSpaces($firm['phone']),
            "bphone" => Util::addSpaces($customer->phone),
            "contractname" => $contractname,
            "contractdate" => Util::addSpaces($contractdate),
            "contractnumber" => $contractnumber,
            "paytype" => $this->headerdata["paytype"],
            "document_number" => $this->document_number,
            "totalnds" => H::famt($this->headerdata["totalnds"]),
            "total" => H::famt($total),
            "totalall" => H::famt($total + $this->headerdata["totalnds"])
        );

        $report = new \ZippyERP\ERP\Report('taxinvoice2.tpl');

        $html = $report->generate($header, $detail);

        return $html;
    }

    public function Execute() {
        
    }

    /**
     * Експорт  в  ГНАУ  формат XML
     */
    public function exportGNAU() {


        $common = System::getOptions("common");
        $firm = System::getOptions("firmdetail");
        $jf = ($common['juridical'] == true ? "J" : "F") . "1201004";

        $edrpou = (string) sprintf("%10d", $firm['edrpou']);
        //2301 0011111111 F1201004 1 00 0000045 1 03 2015 2301.xml
        $number = (string) sprintf('%07d', 1);
        $filename = $firm['gni'] . $edrpou . $jf . "100{$number}1" . date('mY', $this->document_date) . $firm['gni'] . ".xml";
        $filename = str_replace(' ', '0', $filename);
        $customer = \ZippyERP\ERP\Entity\Customer::load($this->headerdata["customer"]);

        $xml = '<?xml version="1.0" encoding="windows-1251"?>';
        $xml .= '<DECLAR xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="' . $jf . '.xsd">';
        $xml .= "<DECLARHEAD>";
        $xml .= "<TIN>{$firm['edrpou']}</TIN>";
        $xml .= "<C_DOC>J12</C_DOC>";
        $xml .= "<C_DOC_SUB>010</C_DOC_SUB> ";
        $xml .= "<C_DOC_VER>4</C_DOC_VER>";
        $xml .= "<C_DOC_TYPE>0</C_DOC_TYPE>";
        $xml .= "<C_DOC_CNT>1</C_DOC_CNT>";
        $xml .= "<C_REG>" . substr($firm['gni'], 0, 2) . "</C_REG> ";
        $xml .= "<C_RAJ>" . substr($firm['gni'], 2, 2) . "</C_RAJ>";

        $xml .= "<PERIOD_MONTH>" . date(m, $this->document_date) . "</PERIOD_MONTH>";
        $xml .= "<PERIOD_TYPE>1</PERIOD_TYPE>";
        $xml .= "<PERIOD_YEAR>" . date('Y', $this->document_date) . "</PERIOD_YEAR>";
        $xml .= "<C_STI_ORIG>{$firm['gni']}</C_STI_ORIG>";
        $xml .= "<C_DOC_STAN>1</C_DOC_STAN>";
        $xml .= "<D_FILL>" . (string) date('dmY') . "</D_FILL>";
        $xml .= "<SOFTWARE>Zippy ERP</SOFTWARE>";

        $xml .= "</DECLARHEAD>";
        $xml .= "<DECLARBODY>";
        $xml .= "<HORIG>1</HORIG>";
        $xml .= "<HFILL>" . (string) date('dmY', $this->document_date) . "</HFILL>";
        $xml .= "<HNUM>" . $this->document_number . "</HNUM>";
        $xml .= "<HNAMESEL>{$firm['name']}</HNAMESEL>";
        $xml .= "<HKSEL>{$firm['inn']}</HKSEL>";
        $xml .= "<HLOCSEL>" . $firm['city'] . " " . $firm['street'] . "</HLOCSEL>";
        if (strlen($firm['phone']) > 0)
            $xml .= "<HTELSEL>{$firm['phone']}</HTELSEL>";
        $xml .= "<HNAMEBUY>{$customer->customer_name}</HNAMEBUY>";
        $xml .= "<HKBUY>{$customer->inn}</HKBUY>";
        $address = strlen($customer->laddress) > 0 ? $customer->laddress : $customer->faddress;
        $xml .= "<HLOCBUY>{$address}</HLOCBUY>";
        if (strlen($this->headerdata['based']) > 0)
            $xml .= "<H01G1S>{$this->headerdata['based']}</H01G1S>";
        if (strlen($this->headerdata['ernn']) == true)
            $xml .= "<HERPN>true</HERPN>";

        $num = 0;

        foreach ($this->detaildata as $value) {
            $num++;
            $xml .= "<RXXXXG4S ROWNUM=\"{$num}\">{$value['measure_name']}</RXXXXG4S>";
            $xml .= "<RXXXXG105_2S  ROWNUM=\"{$num}\">{$value['measure_code']}</RXXXXG105_2S>";
            if (strlen($value['uktzed'] > 0))
                $xml .= "<RXXXXG4  ROWNUM=\"{$num}\">{$value['uktzed']}</RXXXXG4>";
            $xml .= "<RXXXXG3S ROWNUM=\"{$num}\">{$value['itemname']}</RXXXXG3S>";
            $xml .= "<RXXXXG5  ROWNUM=\"{$num}\">{$value['quantity']}</RXXXXG5>";
            $xml .= "<RXXXXG6  ROWNUM=\"{$num}\">" . H::famt($value['price']) . "</RXXXXG6>";
            $xml .= "<RXXXXG7  ROWNUM=\"{$num}\">" . H::famt($value['quantity'] * $value['price']) . "</RXXXXG7>";
        }
        $total = H::famt($this->headerdata["total"]);
        $totalnds = H::famt($this->headerdata["totalnds"]);
        //$all = $total + $totalnds;
        $xml .= "<R01G7>" . ($total - $totalnds) . "</R01G7>";
        $xml .= "<R01G11>{$total}</R01G11>";
        $xml .= "<R03G7>{$totalnds}</R03G7>";
        $xml .= "<R03G11>{$totalnds}</R03G11>";
        $xml .= "<R04G7>" . ($total) . "</R04G7>";
        $xml .= "<R04G11>" . ($total) . "</R04G11>";
        $xml .= "</DECLARBODY>";
        $xml .= "</DECLAR>";

        $xml = iconv("UTF-8", "WINDOWS-1251", $xml);

        return array("filename" => $filename, "content" => $xml);
    }

    /**
     * @see Document
     */
    public function supportedExport() {
        return array(self::EX_EXCEL, self::EX_XML_GNAU);
    }

}
