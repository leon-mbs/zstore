<?php

namespace App\Pages\Service;

use App\Entity\Customer;
use App\Entity\Item;
use App\Entity\Doc\Document;
use App\Entity\Store;
use App\Helper as H;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Form\Date;
use Zippy\Binding\PropertyBinding as Prop;
use Zippy\Html\DataList\ArrayDataSource;
use Zippy\Html\DataList\DataView;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Label;

class Export extends \App\Pages\Base
{
    public $_docs = array();

    public function __construct() {
        parent::__construct();
        if (false == \App\ACL::checkShowSer('Export')) {
            return;
        }

        $form = $this->add(new Form("iform"));

        $form->add(new DropDownChoice("itype", array(), 0))->onChange($this, "onType");
        $form->add(new DropDownChoice("encode", array(1 => 'UTF8', 2 => 'win1251'), 0));
        $form->add(new DropDownChoice("price", Item::getPriceTypeList()));
        $form->add(new DropDownChoice("store", Store::getList(), H::getDefStore()));
        $form->add(new TextInput("sep", ';'));

        $form->onSubmit($this, "onExport");

        $this->onType($form->itype);

        $form = $this->add(new Form("cform"));

        $form->add(new DropDownChoice("ctype", array(), 0));
        $form->add(new DropDownChoice("cencode", array(1 => 'UTF8', 2 => 'win1251'), 0));
        $form->add(new TextInput("csep", ';'));

        $form->onSubmit($this, "onCExport");

        $form = $this->add(new Form("dform"));

        $form->add(new DropDownChoice("dtype", array('GoodsReceipt' => Document::getDesc('GoodsReceipt'), 'GoodsIssue' => Document::getDesc('GoodsIssue')), 'GoodsReceipt'));
        $form->add(new DropDownChoice("dencode", array(1 => 'UTF8', 2 => 'win1251'), 0));
        $form->add(new TextInput("dsep", ';'));
        $form->add(new Date('dfrom', time() - (7 * 24 * 3600)));
        $form->add(new Date('dto', time() + (1 * 24 * 3600)));

        $form->onSubmit($this, "onDPreview");

        $form = $this->add(new Form("dformlist"));
        $form->add(new DataView('doclist', new ArrayDataSource(new Prop($this, '_docs')), $this, 'expDRow'));
        $form->onSubmit($this, "onDExport");
    }

    public function onType($sender) {
        $t = $sender->getValue();

        $this->iform->store->setVisible($t == 1);

    }

    public function onCExport($sender) {
        $t = $this->cform->ctype->getValue();
        $encode = $this->cform->cencode->getValue();

        $sep = $this->cform->csep->getText();

        if ($encode == 0) {
            $this->setError('noselencode');
            return;
        }

        $csv = "Наименование{$sep}Телефон{$sep}Email{$sep}Город{$sep}Адрес{$sep}";

        $csv .= "\n\n";

        $sql = "  status=" . Customer::STATUS_ACTUAL;
        if ($t > 0) {

            $sql .= " and detail like '%<type>{$t}</type>%'    ";
        }
        $list = Customer::find($sql, "customer_name asc");
        foreach ($list as $item) {

            $csv .= $item->customer_name . $sep;
            $csv .= $item->phone . $sep;
            $csv .= $item->email . $sep;
            $csv .= $item->city . $sep;
            $csv .= $item->address . $sep;

            $csv .= "\n";
        }
        if ($encode == 2) {
            $csv = mb_convert_encoding($csv, "windows-1251", "utf-8");
        }


        header("Content-type: text/csv");
        header("Content-Disposition: attachment;Filename=customers_" . date('Y_m_d', time()) . ".csv");
        header("Content-Transfer-Encoding: binary");

        echo $csv;
        flush();
        die;
    }

    public function onExport($sender) {
        $t = $this->iform->itype->getValue();
        $store = $this->iform->store->getValue();
        $pt = $this->iform->price->getValue();
        $encode = $this->iform->encode->getValue();

        $sep = $this->iform->sep->getText();

        if ($encode == 0) {
            $this->setError('noselencode');
            return;
        }


        $csv = "Наименование{$sep}Группа{$sep}Артикул{$sep}Штрих код{$sep}Цена{$sep}";
        if ($t == 1) {
            $csv = "Наименование{$sep}Группа{$sep}Артикул{$sep}Штрих код{$sep}Кол{$sep}Цена{$sep}";
        }
        $csv .= "\n\n";

        $sql = "disabled <> 1 ";

        $list = Item::find($sql, "itemname asc");

        foreach ($list as $item) {
            $price = H::fa($item->getPrice($pt));

            $csv .= $item->itemname . $sep;
            $csv .= $item->cat_name . $sep;
            $csv .= $item->item_code . $sep;
            $csv .= $item->bar_code . $sep;
            if ($t == 1) {
                $qty = H::fqty($item->getQuantity($store));
                $csv .= $qty . $sep;
            }
            $csv .= "\n";
        }
        if ($encode == 2) {
            $csv = mb_convert_encoding($csv, "windows-1251", "utf-8");
        }


        header("Content-type: text/csv");
        header("Content-Disposition: attachment;Filename=items_" . date('Y_m_d', time()) . ".csv");
        header("Content-Transfer-Encoding: binary");

        echo $csv;
        flush();
        die;


    }


    public function onDPreview($sender) {
        $dt = $sender->dtype->getValue();

        $conn = \ZDB\DB::getConnect();

        $sql = "meta_name='{$dt}' and date(document_date) >= " . $conn->DBDate($sender->dfrom->getDate()) . " and  date(document_date) <= " . $conn->DBDate($sender->dto->getDate());
        $this->_docs = Document::find($sql);
        $this->dformlist->doclist->Reload();

    }

    public function expDRow($row) {
        $doc = $row->getDataItem();
        $row->add(new CheckBox('dch', new Prop($doc, 'ch')));
        $row->add(new Label('dnumber', $doc->document_number));
        $row->add(new Label('ddate', \App\Helper::fd($doc->document_date)));
        $row->add(new Label('damount', \App\Helper::fa($doc->amount)));
        $row->add(new Label('dcustomer', $doc->customer_name));

    }

    public function onDExport($sender) {
        $encode = $this->dform->dencode->getValue();

        $sep = $this->dform->dsep->getText();

        if ($encode == 0) {
            $this->setError('noselencode');
            return;
        }
        $csv = "";

        foreach ($this->_docs as $doc) {
            if ($doc->ch == false) {
                continue;
            }

            $csv .= $doc->document_number . $sep;
            $csv .= H::fd($doc->document_date) . $sep;
            $csv .= $doc->customer_name . $sep;
            $csv .= "\n";
            $n = 1;

            foreach ($doc->unpackDetails('detaildata') as $item) {
                $csv .= $sep . $n . $sep;
                $csv .= $item->itemname . $sep;
                $csv .= $item->item_code . $sep;
                $csv .= H::fqty($item->quantity) . $sep;
                $csv .= H::fa($item->price) . $sep;
                $csv .= "\n";
            }
            $csv .= "Итого: " . $sep;
            $csv .= H::fa($doc->amount) . $sep . $sep . $sep . $sep . $sep;
            $csv .= "\n";

        }
        if ($encode == 2) {
            $csv = mb_convert_encoding($csv, "windows-1251", "utf-8");
        }
        header("Content-type: text/csv");
        header("Content-Disposition: attachment;Filename=exportdoc_" . date('Y_m_d', time()) . ".csv");
        header("Content-Transfer-Encoding: binary");

        echo $csv;
        flush();
        die;
    }


}
