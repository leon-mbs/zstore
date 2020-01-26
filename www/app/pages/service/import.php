<?php

namespace App\Pages\Service;

use \Zippy\Html\DataList\DataView;
use \App\Entity\User;
use \App\Entity\Item;
use \App\Entity\Store;
use \App\Entity\Category;
use \App\Helper as H;
use \App\System;
use \Zippy\WebApplication as App;
use \ZCL\DB\EntityDataSource;
use \Zippy\Html\Label;
use \Zippy\Html\Link\ClickLink;
use \Zippy\Html\Panel;
use \Zippy\Html\Form\DropDownChoice;
use \Zippy\Html\Form\Form;
use \Zippy\Html\Form\CheckBox;
use \Zippy\Html\Form\SubmitButton;
use \Zippy\Html\Form\TextInput;

class Import extends \App\Pages\Base {

    public function __construct() {
        parent::__construct();
        if (false == \App\ACL::checkShowSer('Import'))
            return;

        $form = $this->add(new Form("iform"));

        $form->add(new DropDownChoice("itype", array('Только справочник', 'С оприходованием на склад'), 0))->onChange($this, "onType");
        $form->add(new DropDownChoice("encode", array(1 => 'UTF8', 2 => 'win1251'), 0));
        $form->add(new DropDownChoice("price", Item::getPriceTypeList()));
        $form->add(new DropDownChoice("store", Store::getList(), H::getDefStore()));
        $form->add(new TextInput("sep", ';'));
        $form->add(new \Zippy\Html\Form\File("filename"));
        $cols = array(0 => '-', 1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5, 6 => 6, 7 => 7, 8 => 8, 9 => 9, 10 => 10);
        $form->add(new DropDownChoice("colname", $cols));
        $form->add(new DropDownChoice("colcode", $cols));
        $form->add(new DropDownChoice("colgr", $cols));
        $form->add(new DropDownChoice("colqty", $cols));
        $form->add(new DropDownChoice("colprice", $cols));
        $form->add(new DropDownChoice("colinprice", $cols));
        $form->add(new DropDownChoice("colmsr", $cols));
        $form->add(new CheckBox("preview"));
        $form->add(new SubmitButton("load"))->onClick($this, "onImport");

        $this->onType($form->itype);

        $this->_tvars['preview'] = false;
    }

    public function onType($sender) {
        $t = $sender->getValue();

        $this->iform->colqty->setVisible($t == 1);
        $this->iform->store->setVisible($t == 1);
        $this->iform->colinprice->setVisible($t == 1);
    }

    public function onImport($sender) {
        $t = $this->iform->itype->getValue();
        $store = $this->iform->store->getValue();
        $pt = $this->iform->price->getValue();
        $encode = $this->iform->encode->getValue();
        $preview = $this->iform->preview->isChecked();
        $this->_tvars['preview'] = false;

        $colname = $this->iform->colname->getValue();
        $colcode = $this->iform->colcode->getValue();
        $colgr = $this->iform->colgr->getValue();
        $colqty = $this->iform->colqty->getValue();
        $colprice = $this->iform->colprice->getValue();
        $colinprice = $this->iform->colinprice->getValue();
        $colmsr = $this->iform->colmsr->getValue();
        $sep = $this->iform->sep->getText();

        if ($encode == 0) {
            $this->setError('Не выбрана  кодировка');
            return;
        }
        if ($colname == 0) {
            $this->setError('Не указан столбец  с  наименованием');
            return;
        }
        if ($t == 1 && $colqty == 0) {
            $this->setError('Не указан столбец  с  количеством');
            return;
        }
        $file = $this->iform->filename->getFile();
        if (strlen($file['tmp_name']) == 0) {
            $this->setError('Не  выбран  файл');
            return;
        }

        $data = array();
        if (($handle = fopen($file['tmp_name'], "r")) !== FALSE) {
            while (($row = fgetcsv($handle, 0, $sep)) !== FALSE) {
                $data[] = $row;
            }
        }
        fclose($handle);

        if ($preview) {

            $this->_tvars['preview'] = true;
            $this->_tvars['list'] = array();
            foreach ($data as $row) {
                $itemname = $row[$colname - 1];
                if ($encode == 2)
                    $itemname = mb_convert_encoding($itemname, "utf-8", "windows-1251");

                $this->_tvars['list'][] = array(
                    'colname' => $itemname,
                    'colcode' => $row[$colcode - 1],
                    'colgr' => $row[$colgr - 1],
                    'colqty' => $row[$colqty - 1],
                    'colmsr' => $row[$colmsr - 1],
                    'colinprice' => $row[$colinprice - 1],
                    'colprice' => $row[$colprice - 1]
                );
            }
            return;
        }



        $newitems = array();
        foreach ($data as $row) {


            $catname = $row[$colgr - 1];
            if (strlen($catname) > 0) {
                $cat = Category::getFirst('cat_name=' . Category::qstr($catname));
                if ($cat == null) {
                    $cat = new Category();
                    $cat->cat_name = $catname;
                    $cat->save();
                }
            }
            $itemname = $row[$colname - 1];
            if (strlen($itemname) > 0) {
                if ($encode == 2)
                    $itemname = mb_convert_encoding($itemname, "utf-8", "windows-1251");

                $item = Item::getFirst('itemname=' . Item::qstr($itemname));
                if ($item == null) {
                    $price = str_replace(',', '.', $row[$colprice - 1]);
                    $inprice = str_replace(',', '.', $row[$colinprice - 1]);
                    $qty = str_replace(',', '.', $row[$colqty - 1]);
                    $item = new Item();
                    $item->itemname = $itemname;
                    if (strlen($row[$colcode - 1]) > 0)
                        $item->item_code = $row[$colcode - 1];
                    if (strlen($row[$colmsr - 1]) > 0)
                        $item->msr = $row[$colmsr - 1];
                    if ($price > 0)
                        $item->{$pt} = $price;
                    if ($inprice > 0)
                        $item->price = $inprice;
                    if ($qty > 0)
                        $item->quantity = $qty;
                    if ($cat->cat_id > 0)
                        $item->cat_id = $cat->cat_id;

                    $item->amount = $item->quantity * $item->price;
                    $item->save();

                    if ($item->quantity > 0) {
                        $newitems[] = $item; //для склада   
                    }
                }
            }
        }
        if (count($newitems) > 0) {
            $doc = \App\Entity\Doc\Document::create('IncomeItem');
            $doc->document_number = $doc->nextNumber();
            if (strlen($doc->document_number) == 0)
                $doc->document_number = "ПТ00001";
            $doc->document_date = time();

            $amount = 0;
            foreach ($newitems as $item) {
                $doc->detaildata[] = $item->getData();
                $amount = $amount + ($item->quantity * $item->price);
            }
            $doc->amount = H::fa($amount);
            $doc->headerdata['store'] = $store;

            $doc->save();
            $doc->updateStatus(\App\Entity\Doc\Document::STATE_NEW);
            $doc->updateStatus(\App\Entity\Doc\Document::STATE_EXECUTED);
        }
        $this->setSuccess('Импорт завершен');

        $this->iform->clean();
    }

}
