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
        $form->add(new DropDownChoice("price", Item::getPriceTypeList()));
        $form->add(new DropDownChoice("store", Store::getList(), H::getDefStore()));
        $form->add(new DropDownChoice("item_type", Item::getTypes(), H::getDefStore()));

        $form->onSubmit($this, "onExport");

        $this->onType($form->itype);

        $form = $this->add(new Form("cform"));

        $form->add(new DropDownChoice("ctype", array(), 0));

        $form->onSubmit($this, "onCExport");

        $form = $this->add(new Form("dform"));

        $form->add(new DropDownChoice("dtype", array('GoodsReceipt' => Document::getDesc('GoodsReceipt'), 'GoodsIssue' => Document::getDesc('GoodsIssue')), 'GoodsReceipt'));

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

        $sql = "  status=" . Customer::STATUS_ACTUAL;
        if ($t > 0) {

            $sql .= " and detail like '%<type>{$t}</type>%'    ";
        }
        $list = Customer::find($sql, "customer_name asc");

        $header = array();
        $data = array();

        $header['A1'] = "Наименование";
        $header['B1'] = "Телефон";
        $header['C1'] = "Email";
        $header['D1'] = "Город";
        $header['E1'] = "Адрес";

        $i = 1;
        foreach ($list as $item) {
            $i++;
            $data['A' . $i] = $item->customer_name;
            $data['B' . $i] = $item->phone;
            $data['C' . $i] = $item->email;
            $data['D' . $i] = $item->city;
            $data['E' . $i] = $item->address;
        }

        H::exportExcel($data, $header, 'customers_' . date('Y_m_d', time()) . '.xlsx');
    }

    public function onExport($sender) {
        $t = $this->iform->itype->getValue();
        $tp = $this->iform->item_type->getValue();
        $store = $this->iform->store->getValue();
        $pt = $this->iform->price->getValue();

        $sql = "disabled <> 1 ";
        if ($tp > 0) {
            $sql .= " and item_type=" . $tp;
        }
        $list = Item::find($sql, "itemname asc");

        $header = array();
        $data = array();

        $header['A1'] = "Наименование";
        $header['B1'] = "Ед.";
        $header['C1'] = "Группа";
        $header['D1'] = "Бренд";
        $header['E1'] = "Артикул";
        $header['F1'] = "Штрих код";
        $header['G1'] = "Цена";
        if ($t == 1) {
            $header['H1'] = "Кол.";
        }


        $i = 1;
        foreach ($list as $item) {
            $i++;
            $data['A' . $i] = $item->itemname;
            $data['B' . $i] = $item->msr;
            $data['C' . $i] = $item->cat_name;
            $data['D' . $i] = $item->manufacturer;
            $data['E' . $i] = $item->item_code;
            $data['F' . $i] = $item->bar_code;
            $price = H::fa($item->getPrice($pt));
            $data['G' . $i] = array('value' => H::fa($price), 'format' => 'number', 'align' => 'right');

            if ($t == 1) {
                $qty = H::fqty($item->getQuantity($store));
                $data['H' . $i] = array('value' => H::fqty($qty), 'format' => 'number', 'align' => 'right');
            }
        }

        H::exportExcel($data, $header, 'items_' . date('Y_m_d', time()) . '.xlsx');
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

        $header = array();
        $data = array();

        $i = 0;
        foreach ($this->_docs as $doc) {
            $i++;
            $data['A' . $i] = array('value' => $doc->document_number, 'bold' => true);
            $data['B' . $i] = array('value' => $doc->document_date, 'format' => 'date', 'bold' => true);
            $data['C' . $i] = array('value' => $doc->customer_name, 'bold' => true);
            $n = 1;
            foreach ($doc->unpackDetails('detaildata') as $item) {
                $i++;
                $data['B' . $i] = $n++;
                $data['C' . $i] = $item->itemname;
                $data['D' . $i] = $item->item_code;
                $data['E' . $i] = array('value' => H::fqty($item->quantity), 'format' => 'number', 'align' => 'right');
                $data['F' . $i] = array('value' => H::fa($item->price), 'format' => 'number', 'align' => 'right');
            }

            $i++;
            $data['A' . $i] = array('value' => H::l("total") . ": ", 'bold' => true, 'align' => 'right');
            $data['B' . $i] = array('value' => H::fa($item->amount), 'format' => 'number', 'bold' => true, 'align' => 'right');
            $i++;
        }

        H::exportExcel($data, $header, 'exportdoc_' . date('Y_m_d', time()) . '.xlsx');
    }

}
