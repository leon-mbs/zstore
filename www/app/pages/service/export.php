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
        $form->add(new CheckBox("itemxml"));

        $form->onSubmit($this, "onExport");

        $this->onType($form->itype);

        $form = $this->add(new Form("cform"));

        $form->add(new DropDownChoice("ctype", array(), 0));
        $form->add(new CheckBox("custxml"));

        $form->onSubmit($this, "onCExport");

        $form = $this->add(new Form("dform"));

        $form->add(new DropDownChoice("dtype", array('GoodsReceipt' => Document::getDesc('GoodsReceipt'), 'GoodsIssue' => Document::getDesc('GoodsIssue')), 'GoodsReceipt'));

        $form->add(new Date('dfrom', time() - (7 * 24 * 3600)));
        $form->add(new Date('dto', time() + (1 * 24 * 3600)));
 
        $form->onSubmit($this, "onDPreview");

        $form = $this->add(new Form("dformlist"));
        $form->add(new DataView('doclist', new ArrayDataSource(new Prop($this, '_docs')), $this, 'expDRow'));
      $form->add(new CheckBox("docxml"));
        
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

        $header['A1'] = "Найменування";
        $header['B1'] = "Телефон";
        $header['C1'] = "Email";
        $header['D1'] = "Місто";
        $header['E1'] = "Адреса";
        $root="<root>";
        $i = 1;
        foreach ($list as $item) {
            $i++;
            $data['A' . $i] = $item->customer_name;
            $data['B' . $i] = $item->phone;
            $data['C' . $i] = $item->email;
            $data['D' . $i] = $item->city;
            $data['E' . $i] = $item->address;
            
            $root.="<item>";
            $root.="<name><![CDATA[" . $item->customer_name . "]]></name>";
            $root.="<phone>" . $item->phone . "</phone>";
            $root.="<email>" . $item->email . "</email>";
            $root.="<city><![CDATA[" . $item->city . "]]></city>";
            $root.="<address><![CDATA[" . $item->address . "]]></address>";
                  
            $root.="</item>";
            
            
            
        }
        $root.="</root>";
        
        $isxml = $this->cform->custxml->isChecked();

        if($isxml)
          H::exportXML($root, 'customers_' . date('Y_m_d', time()) . '.xml');
        else 
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

        $header['A1'] = "Найменуванння";
        $header['B1'] = "Од.";
        $header['C1'] = "Група";
        $header['D1'] = "Бренд";
        $header['E1'] = "Артикул";
        $header['F1'] = "Штрих код";
        $header['G1'] = "Ціна";
        
        if ($t == 1) {
            $header['H1'] = "Комірка";
            $header['I1'] = "Кіл.";
        }

        $root="<root>";

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
            $data['G' . $i] = array('value' => H::fa(doubleval($price)), 'format' => 'number', 'align' => 'right');
            
            if ($t == 1) {
                $data['H' . $i] = $item->cell;
                $qty = H::fqty($item->getQuantity($store));
                $data['I' . $i] = array('value' => H::fqty(doubleval($qty)), 'format' => 'number', 'align' => 'right');
            }
            
            $root.="<item>";
            $root.="<name><![CDATA[" . $item->itemname . "]]></name>";
            $root.="<msr>" . $item->msr . "</msr>";
            $root.="<cat_name>" . $item->cat_name . "</cat_name>";
            $root.="<item_code>" . $item->item_code . "</item_code>";
            $root.="<bar_code>" . $item->bar_code . "</bar_code>";
            $root.="<brand><![CDATA[" . $item->manufacturer . "]]></brand>";
            if ($t == 1)    $root.="<quantity>" .$qty. "</quantity>";         
            $root.="<price>" .  $price  . "</price>";
            $root.="</item>";            
            
        }
       $root.="</root>";
       $isxml = $this->iform->itemxml->isChecked();

       if($isxml)
          H::exportXML($root,   'items_' . date('Y_m_d', time()) . '.xml');
       else 
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
           $root="<root>";

        $i = 0;
        foreach ($this->_docs as $doc) {
            $i++;
            $data['A' . $i] = array('value' => $doc->document_number, 'bold' => true);
            $data['B' . $i] = array('value' =>   $doc->document_date , 'format' => 'date', 'bold' => true);
            $data['C' . $i] = array('value' => $doc->customer_name, 'bold' => true);
            $n = 1;
            
            $root.="<doc>";
            $root.="<customer_name><![CDATA[" . $doc->customer_name . "]]></customer_name>";
            $root.="<document_date>" . H::fd($doc->document_date) . "</document_date>";
            $root.="<document_number>" . $doc->document_number . "</document_number>";
            
            
            foreach ($doc->unpackDetails('detaildata') as $item) {
                $root.="<item>";
                 
                $i++;
                $data['B' . $i] = $n++;
                $data['C' . $i] = $item->itemname;
                $data['D' . $i] = $item->item_code;
                $data['E' . $i] = array('value' => H::fqty(doubleval($item->quantity)), 'format' => 'number', 'align' => 'right');
                $data['F' . $i] = array('value' => H::fa(doubleval($item->price)), 'format' => 'number', 'align' => 'right');
             
                $root.="<itemname><![CDATA[" . $doc->itemname . "]]></itemname>";
                $root.="<item_code>" . $item->item_code . "</item_code>";
                $root.="<quantity>" .   H::fqty($item->quantity) . "</quantity>";
                $root.="<price>" . H::fa($item->price) . "</price>";
                 
                $root.="</item>";
                
            }

            $i++;
            $data['A' . $i] = array('value' => H::l("total") . ": ", 'bold' => true, 'align' => 'right');
            $data['B' . $i] = array('value' => H::fa(doubleval($item->amount)), 'format' => 'number', 'bold' => true, 'align' => 'right');
            $i++;
            
            $root.="</doc>";  
            
        }
        $root.="</root>";
       $isxml = $this->dformlist->docxml->isChecked();

       if($isxml)
          H::exportXML($root,   'exportdoc_' . date('Y_m_d', time()) . '.xml');
       else 
          H::exportExcel($data, $header, 'exportdoc_' . date('Y_m_d', time()) . '.xlsx');
    }

}
