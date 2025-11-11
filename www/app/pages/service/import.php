<?php

namespace App\Pages\Service;

use App\Entity\Category;
use App\Entity\Customer;
use App\Entity\Item;
use App\Entity\Store;
use App\Helper as H;
use Zippy\Html\Form\AutocompleteTextInput;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Label;
use App\Application as App;

class Import extends \App\Pages\Base
{
    public function __construct() {
        parent::__construct();
        if (false == \App\ACL::checkShowSer('Import')) {
            return;
        }

        //ТМЦ
        $sc=[];
        $v= H::getKeyVal('importcols','') ;
        if(strlen($v)>0) {
           $sc =     @unserialize($v) ;
        }
        
        $form = $this->add(new Form("iform"));

        $form->add(new DropDownChoice("itype", array(), 0))->onChange($this, "onType");

        $form->add(new DropDownChoice("icompare", array(), 0));

        $form->add(new DropDownChoice("item_type", Item::getTypes(), Item::TYPE_TOVAR));

        $form->add(new DropDownChoice("store", Store::getList(), H::getDefStore()));

        $form->add(new \Zippy\Html\Form\File("filename"));
        $cols = array(0 => '-', 'A' => 'A', 'B' => 'B', 'C' => 'C', 'D' => 'D', 'E' => 'E', 'F' => 'F', 'G' => 'G', 'H' => 'H', 'I' => 'I', 'J' => 'J', 'K' => 'K', 'L' => 'L', 'M' => 'M', 'N' => 'N', 'O' => 'O','P' => 'P','Q' => 'Q' );
        $form->add(new DropDownChoice("colname", $cols,$sc['colname'] ?? 0));
        $form->add(new DropDownChoice("colcode", $cols,$sc['colcode'] ?? 0));
        $form->add(new DropDownChoice("colbarcode", $cols,$sc['colbarcode'] ?? 0));
        $form->add(new DropDownChoice("colcat", $cols,$sc['colcat'] ?? 0));
        $form->add(new DropDownChoice("colqty", $cols,$sc['colqty'] ?? 0));
        $form->add(new DropDownChoice("colcell", $cols,$sc['colcell'] ?? 0));
        $form->add(new DropDownChoice("coluktz", $cols,$sc['coluktz'] ?? 0));
        $form->add(new DropDownChoice("colshortname", $cols,$sc['colshortname'] ?? 0));
        $form->add(new DropDownChoice("colimage", $cols,$sc['colimage'] ?? 0));
        $form->add(new DropDownChoice("colwar", $cols,$sc['colwar'] ?? 0));
        $form->add(new DropDownChoice("colnotes", $cols,$sc['colnotes'] ?? 0));
        $form->add(new DropDownChoice("colminqty", $cols,$sc['colminqty'] ?? 0));

        $pt = \App\Entity\Item::getPriceTypeList();

        $form->add(new Label('pricename1', $pt['price1'] ??''));
        $form->add(new Label('pricename2', $pt['price2'] ??''));
        $form->add(new Label('pricename3', $pt['price3'] ??''));
        $form->add(new Label('pricename4', $pt['price4'] ??''));
        $form->add(new Label('pricename5', $pt['price5'] ??''));

        $form->add(new DropDownChoice("colprice1", $cols,$sc['colprice1'] ?? 0))->setVisible(strlen($pt['price1'] ??'')>0);
        $form->add(new DropDownChoice("colprice2", $cols,$sc['colprice2'] ?? 0))->setVisible(strlen($pt['price2'] ??'')>0);
        $form->add(new DropDownChoice("colprice3", $cols,$sc['colprice3'] ?? 0))->setVisible(strlen($pt['price3'] ??'')>0);
        $form->add(new DropDownChoice("colprice4", $cols,$sc['colprice4'] ?? 0))->setVisible(strlen($pt['price4'] ??'')>0);
        $form->add(new DropDownChoice("colprice5", $cols,$sc['colprice5'] ?? 0))->setVisible(strlen($pt['price5'] ??'')>0);



        $form->add(new DropDownChoice("colinprice", $cols,$sc['colinprice'] ?? 0));
        $form->add(new DropDownChoice("colmsr", $cols,$sc['colmsr'] ?? 0));
        $form->add(new DropDownChoice("colbrand", $cols,$sc['colbrand'] ?? 0));
        $form->add(new DropDownChoice("coldesc", $cols,$sc['coldesc'] ?? 0));
        $form->add(new CheckBox("passfirst"));
        $form->add(new CheckBox("preview"));

        $form->add(new CheckBox("noshowprice"));
        $form->add(new CheckBox("noshowshop"));


        $form->onSubmit($this, "onImport");

        $this->onType($form->itype);

        //накладная
        
        $sc=[];
        $v= H::getKeyVal('nimportcols','') ;
        if(strlen($v)>0) {
           $sc =     @unserialize($v) ;
        }
         
         
      
        
        $form = $this->add(new Form("nform"));

        $form->add(new DropDownChoice("nstore", Store::getList(), H::getDefStore()));

        $form->add(new AutocompleteTextInput("ncust"))->onText($this, 'OnAutoCustomer');
        $form->add(new \Zippy\Html\Form\File("nfilename"));

        $form->add(new DropDownChoice("ncolname", $cols,$sc['colname'] ?? 0));
        $form->add(new DropDownChoice("ncolcode", $cols,$sc['colcode'] ?? 0));
        $form->add(new DropDownChoice("ncolbarcode", $cols,$sc['colbarcode'] ?? 0));
        $form->add(new DropDownChoice("ncolqty", $cols,$sc['colqty'] ?? 0));
        $form->add(new DropDownChoice("ncolprice", $cols,$sc['colprice'] ?? 0));
        $form->add(new DropDownChoice("ncolmsr", $cols,$sc['colmsr'] ?? 0));
        $form->add(new DropDownChoice("ncoldesc", $cols,$sc['coldesc'] ?? 0));
        $form->add(new DropDownChoice("ncolbrand", $cols,$sc['colbrand'] ?? 0));
        $form->add(new CheckBox("npassfirst"));
        $form->add(new CheckBox("npreview"));

        $form->onSubmit($this, "onNImport");

        //контрагенты
        $form = $this->add(new Form("cform"));

        $form->add(new DropDownChoice("ctype", array(), 0));

        $form->add(new CheckBox("cpreview"));
        $form->add(new CheckBox("cpassfirst"));
        $form->add(new DropDownChoice("colcname", $cols));
        $form->add(new DropDownChoice("colphone", $cols));
        $form->add(new DropDownChoice("colemail", $cols));
        $form->add(new DropDownChoice("colcity", $cols));
        $form->add(new DropDownChoice("coledrpou", $cols));
        $form->add(new DropDownChoice("coladdress", $cols));
        $form->add(new \Zippy\Html\Form\File("cfilename"));

        $form->onSubmit($this, "onCImport");


        //заказ
        $form = $this->add(new Form("zform"));


        $form->add(new AutocompleteTextInput("zcust"))->onText($this, 'OnAutoCustomer');
        $form->add(new \Zippy\Html\Form\File("zfilename"));

        $form->add(new DropDownChoice("zcolname", $cols));
        $form->add(new DropDownChoice("zcolcode", $cols));
        $form->add(new DropDownChoice("zcolqty", $cols));
        $form->add(new DropDownChoice("zcolprice", $cols));
        $form->add(new CheckBox("zpassfirst"));
        $form->add(new CheckBox("zpreview"));

        $form->onSubmit($this, "oZImport");



        $form = $this->add(new Form("oform"));
        $form->add(new \Zippy\Html\Form\File("ofilename"));
        $form->onSubmit($this, "onOImport");



        $this->_tvars['preview'] = false;
        $this->_tvars['preview2'] = false;
        $this->_tvars['preview3'] = false;
    }

    public function OnAutoCustomer($sender) {
        return Customer::getList($sender->getText());
    }

    public function onType($sender) {
        $t = $sender->getValue();

        $this->iform->colqty->setVisible($t == 1);
        $this->iform->store->setVisible($t == 1);
        $this->iform->colinprice->setVisible($t == 1);



    }

    public function onImport($sender) {
        $t = $this->iform->itype->getValue();
        $this->_tvars['isstore']  = $t==1;
        $cmp = $this->iform->icompare->getValue();
        $store = $this->iform->store->getValue();
        $item_type = $this->iform->item_type->getValue();

        $preview = $this->iform->preview->isChecked();
        $passfirst = $this->iform->passfirst->isChecked();

        //$checkname = $this->iform->checkname->isChecked();
        $this->_tvars['preview'] = false;

        $colname = $this->iform->colname->getValue();
        $colcode = $this->iform->colcode->getValue();
        $colbarcode = $this->iform->colbarcode->getValue();
        $colcat = $this->iform->colcat->getValue();
        $colqty = $this->iform->colqty->getValue();
        $colinprice = $this->iform->colinprice->getValue();
        $colmsr = $this->iform->colmsr->getValue();
        $colcell = $this->iform->colcell->getValue();
        $coluktz = $this->iform->coluktz->getValue();
        $colbrand = $this->iform->colbrand->getValue();
        $coldesc = $this->iform->coldesc->getValue();
        $colimage = $this->iform->colimage->getValue();
        $colshortname = $this->iform->colshortname->getValue();
        $colwar = $this->iform->colwar->getValue();
        $colnotes = $this->iform->colnotes->getValue();
        $colprice1 = $this->iform->colprice1->getValue();
        $colprice2 = $this->iform->colprice2->getValue();
        $colprice3 = $this->iform->colprice3->getValue();
        $colprice4 = $this->iform->colprice4->getValue();
        $colprice5 = $this->iform->colprice5->getValue();
        $colminqty = $this->iform->colminqty->getValue();


        if ($t == 1 && $colqty === '0') {
            $this->setError('Не вказано колонку з кількістю');
            return;
        }
        $file = $this->iform->filename->getFile();
        if (strlen($file['tmp_name']) == 0) {

            $this->setError('Не вибраний файл');
            return;
        }

        
        $save = array();
        $save['colname']=$colname;
        $save['colcode']=$colcode;
        $save['colbarcode']=$colbarcode;
        $save['colcat']=$colcat;
        $save['colqty']=$colqty;
        $save['colprice1']=$colprice1;
        $save['colprice2']=$colprice2;
        $save['colprice3']=$colprice3;
        $save['colprice4']=$colprice4;
        $save['colprice5']=$colprice5;
        $save['colinprice']=$colinprice;
        $save['colmsr']=$colmsr;
        $save['colcell']=$colcell;
        $save['coluktz']=$coluktz;
        $save['colbrand']=$colbrand;
        $save['coldesc']=$coldesc;
        $save['colimage']=$colimage;
        $save['colshortname']=$colshortname;
        $save['colminqty']=$colminqty;
      
  
        $letters=[];
        foreach($save as $s){
            if($s != '0') {
               $letters[]=$s; 
            }
        }
  
            
        H::setKeyVal('importcols',serialize($save)) ;
           
        
        
        $data = array();
        $oSpreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file['tmp_name']); // Вариант и для xls и xlsX


        $oCells = $oSpreadsheet->getActiveSheet()->getCellCollection();

        for ($iRow = ($passfirst ? 2 : 1); $iRow <= $oCells->getHighestRow(); $iRow++) {

            $row = array();
         //   for ($iCol = 'A'; $iCol <= $oCells->getHighestColumn(); $iCol++) {
            foreach ($letters as $iCol) {
                $oCell = $oCells->get($iCol . $iRow);
                if ($oCell) {
                    $row[$iCol] = $oCell->getValue();
                }   
            }
            $data[$iRow] = $row; 
        }

        unset($oSpreadsheet);

        $catlist=array();

        foreach(Category::findArray('cat_name', '') as $cid =>$cname) {
            $catlist[$cname]= $cid;
        }

        if ($preview) {

            $this->_tvars['preview'] = true;
            $this->_tvars['list'] = array();
            foreach ($data as $row) {

                $this->_tvars['list'][] = array(
                    'colname'    => $row[$colname] ?? '',
                    'colcode'    => $row[$colcode] ?? '',
                    'colbarcode' => $row[$colbarcode] ?? '',
                    'colcat'     => $row[$colcat] ?? '',
                    'colqty'     => $row[$colqty] ?? '',
                    'colmsr'     => $row[$colmsr] ?? '',
                    'colinprice' => $row[$colinprice] ?? '',
                    'colprice1'  => $row[$colprice1] ?? '',
                    'colprice2'  => $row[$colprice2] ?? '',
                    'colprice3'  => $row[$colprice3] ?? '',
                    'colprice4'  => $row[$colprice4] ?? '',
                    'colprice5'  => $row[$colprice5] ?? '',
                    'colbrand'   => $row[$colbrand] ?? '',
                    'colcell'    => $row[$colcell] ?? '',
                    'coluktz'    => $row[$coluktz] ?? '',
                    'coldesc'    => $row[$coldesc] ?? ''
                );
            }
            return;
        }

        $cnt = 0;
        $newitems = array();
        foreach ($data as $row) {

            $price1 =doubleval( str_replace(',', '.', trim($row[$colprice1] ?? ''))) ;
            $price2 =doubleval( str_replace(',', '.', trim($row[$colprice2] ?? '')));
            $price3 =doubleval( str_replace(',', '.', trim($row[$colprice3] ?? '')));
            $price4 =doubleval( str_replace(',', '.', trim($row[$colprice4] ?? '')));
            $price5 =doubleval( str_replace(',', '.', trim($row[$colprice5] ?? '')));
            
            $itemcode = ''.trim($row[$colcode] ?? '');
            $brand = trim($row[$colbrand] ?? '');
            $itemname = trim($row[$colname] ?? '');
            $itembarcode = trim($row[$colbarcode] ?? '');
            $cell = trim($row[$colcell] ?? '');
            $uktz = trim($row[$coluktz] ?? '');
            $msr = trim($row[$colmsr] ?? '');
            $desc = trim($row[$coldesc] ?? '');
            $catname = trim($row[$colcat] ?? '');
            $image = trim($row[$colimage] ?? '');
            $warranty = trim($row[$colwar]);
            $notes = trim($row[$colnotes]);
            $shortname = trim($row[$colshortname] ?? '');
            $minqty = trim($row[$colminqty] ?? '');
            $inprice = doubleval( str_replace(',', '.', trim($row[$colinprice] ?? '')) );
            $qty = doubleval(str_replace(',', '.', trim($row[$colqty] ?? '')));

               
            
            $cat_id = 0;

            if (strlen($catname) > 0) {

                if ($catlist[$catname] >0) {
                    $cat_id = $catlist[$catname] ;
                } else {
                    $cat = new Category();
                    $cat->cat_name = $catname;
                    $cat->save();
                    $cat_id = $cat->cat_id;
                    $catlist[$catname]  = $cat_id;
                }
            }
            $item = null;

            //поиск существующих
            if($cmp==0 && strlen($itemcode) > 0) {
                $item = Item::getFirst('item_code=' . Item::qstr($itemcode));
            }
            if($cmp==1 && strlen($itemname) > 0) {
                $item = Item::getFirst('itemname=' . Item::qstr($itemname));
            }
            if($cmp==2 && strlen($itemname) > 0 && strlen($brand) > 0) {
                $item = Item::getFirst('itemname=' . Item::qstr($itemname). ' and manufacturer='. Item::qstr($brand));
            }

            if($cmp==3 && strlen($itemcode) > 0 && strlen($brand) > 0) {
                $item = Item::getFirst('item_code=' . Item::qstr($itemcode). ' and manufacturer='. Item::qstr($brand));
            }


            if ($item == null) {
                if(strlen($itemname) == 0) {
                    continue;
                }
                $item = new Item();
            }
            if($colname !='0')    $item->itemname = $itemname;
            if($colcode !='0')    $item->item_code = $itemcode;
            if($colbarcode !='0') $item->bar_code = $itembarcode;
            if($colmsr !='0')         $item->msr = $msr;
            if($colcell !='0')        $item->cell = $cell;
            if($coluktz !='0')        $item->uktz = $uktz;
            if($colbrand   !='0')     $item->manufacturer = $brand;
            if($coldesc !='0')        $item->description = $desc;
            if($colshortname !='0')   $item->shortname = $shortname;
            if($colwar !='0')    $item->warranty = $warranty;
            if($colnotes !='0')    $item->notes = $notes;

            
            if ($colprice1 !='0') $item->price1 = doubleval($price1) ;
            if ($colprice2 !='0') $item->price2 = doubleval($price2) ;
            if ($colprice3 !='0') $item->price3 = doubleval($price3) ;
            if ($colprice4 !='0') $item->price4 = doubleval($price4) ;
            if ($colprice5 !='0') $item->price5 = doubleval($price5) ;
           
            if($colinprice !='0')    $item->price = $inprice;
            if($colminqty !='0')     $item->minqty = $minqty;
            if($colqty !='0')        $item->quantity = $qty;

           

            if ($cat_id > 0) {
                $item->cat_id = $cat_id;
            }
            if ($item_type > 0) {
                $item->item_type = $item_type;
            }

            $item->amount = doubleval($item->quantity) * doubleval($item->price);
            if($this->iform->noshowprice->isChecked()) $item->noprice  = 1;
            if($this->iform->noshowshop->isChecked()) $item->noshop  = 1;
      
            $item->save();
            $cnt++;
            if ($item->quantity > 0) {
                $newitems[] = $item; //для склада
            }

       
             
            if (strlen($image) > 0) {
                $item->saveImage($image);

                $item->save();

            }


        }
        if (count($newitems) > 0 && $t==1) {
            $doc = \App\Entity\Doc\Document::create('IncomeItem');
            $doc->document_number = $doc->nextNumber();
            if (strlen($doc->document_number) == 0) {
                $doc->document_number = "ПТ00001";
            }
            $doc->document_date = time();

            $amount = 0;
            $itlist = array();
            foreach ($newitems as $item) {
                $itlist[$item->item_id] = $item;
                $amount = $amount + (doubleval($item->quantity) * doubleval($item->price ));
            }
            $doc->packDetails('detaildata', $itlist);
            $doc->amount = H::fa($amount);
            $doc->payamount = $amount;
          
            $doc->headerdata['payed'] = 0;
            $doc->notes = 'Импорт с Excel';
            $doc->headerdata['store'] = $store;

            $doc->headerdata['payed'] = 0;

            $doc->save();
            $doc->updateStatus(\App\Entity\Doc\Document::STATE_NEW);
            $doc->updateStatus(\App\Entity\Doc\Document::STATE_EXECUTED);
        }

        $this->setSuccess("Імпортовано {$cnt} ТМЦ");
    }

    public function onCImport($sender) {
        $t = $this->cform->ctype->getValue();

        $preview = $this->cform->cpreview->isChecked();
        $passfirst = $this->cform->cpassfirst->isChecked();
        $this->_tvars['preview2'] = false;

        $colcname = $this->cform->colcname->getValue();
        $colphone = $this->cform->colphone->getValue();
        $colemail = $this->cform->colemail->getValue();
        $colcity = $this->cform->colcity->getValue();
        $coledrpou = $this->cform->coledrpou->getValue();
        $coladdress = $this->cform->coladdress->getValue();

        if ($colcname === '0') {
            $this->setError('Не вказано колонку з назвою');
            return;
        }

        $file = $this->cform->cfilename->getFile();
        if (strlen($file['tmp_name']) == 0) {
            $this->setError('Не вибраний файл');
            return;
        }


        $data = array();

        $oSpreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file['tmp_name']); // Вариант и для xls и xlsX


        $oCells = $oSpreadsheet->getActiveSheet()->getCellCollection();

        for ($iRow = ($passfirst ? 2 : 1); $iRow <= $oCells->getHighestRow(); $iRow++) {

            $row = array();
            for ($iCol = 'A'; $iCol <= $oCells->getHighestColumn(); $iCol++) {
                $oCell = $oCells->get($iCol . $iRow);
                if ($oCell) {
                    $row[$iCol] = $oCell->getValue();
                }
            }
            $data[$iRow] = $row;
        }

        unset($oSpreadsheet);

        if ($preview) {

            $this->_tvars['preview2'] = true;
            $this->_tvars['list2'] = array();
            foreach ($data as $row) {

                $this->_tvars['list2'][] = array(
                    'colname'    => $row[$colcname] ?? '',
                    'colphone'   => $row[$colphone] ?? '',
                    'colemail'   => $row[$colemail] ?? '',
                    'coledrpou'   => $row[$coledrpou] ?? '',
                    'colcity'    => $row[$colcity] ?? '',
                    'coladdress' => $row[$coladdress]  ?? ''
                );
            }
            return;
        }

        $cnt = 0;
        $newitems = array();
        foreach ($data as $row) {

            $c = null;
            $name = $row[$colcname] ?? '';
            $phone = $row[$colphone] ?? '';

            if (strlen(trim($name)) == 0) {
                continue;
            }

            if (strlen(trim($phone)) > 0) {
                $c = Customer::getFirst('phone=' . Customer::qstr($phone));
            }

            if ($c == null) {

                $c = new Customer();
                $c->type = $t;
                $c->customer_name = $name;

                if (strlen($row[$colphone] ?? '') > 0) {
                    $c->phone = $row[$colphone];
                }
                if (strlen($row[$colemail] ?? '') > 0) {
                    $c->email = $row[$colemail];
                }
                if (strlen($row[$coledrpou] ?? '') > 0) {
                    $c->edrpou = $row[$coledrpou];
                }
                if (strlen($row[$colcity] ?? '') > 0) {
                    $c->city = $row[$colcity];
                }
                if (strlen($row[$coladdress] ?? '') > 0) {
                    $c->address = $row[$coladdress];
                }


                $c->save();
                $cnt++;
            }
        }

        $this->setSuccess("Імпортовано {$cnt} контрагентів ");
    }

    public function onNImport($sender) {
        $store = $this->nform->nstore->getValue();
        $c = $this->nform->ncust->getKey();
        //$checkname = $this->nform->ncheckname->isChecked();

        $preview = $this->nform->npreview->isChecked();
        $passfirst = $this->nform->npassfirst->isChecked();
        $this->_tvars['preview3'] = false;

        $colname = $this->nform->ncolname->getValue();
        $colcode = $this->nform->ncolcode->getValue();
        $colbarcode = $this->nform->ncolbarcode->getValue();
        $colqty = $this->nform->ncolqty->getValue();
        $colprice = $this->nform->ncolprice->getValue();
        $colmsr = $this->nform->ncolmsr->getValue();
        $coldesc = $this->nform->ncoldesc->getValue();
        $colbrand = $this->nform->ncolbrand->getValue();

        if ($colname === '0') {
            $this->setError('Не вказано колонку з назвою');
            return;
        }
        if ($colqty === '0') {
            $this->setError('Не вказано колонку з кількістю');
            return;
        }

        if ($c == 0) {
            $this->setError('Не обрано постачальника');
            return;
        }

        $file = $this->nform->nfilename->getFile();
        if (strlen($file['tmp_name']) == 0) {

            $this->setError('Не вибраний файл');
            return;
        }

        $save = array();
        $save['colname']=$colname;
        $save['colcode']=$colcode;
        $save['colbarcode']=$colbarcode;
        $save['colqty']=$colqty;
        $save['colprice']=$colprice;
        $save['colmsr']=$colmsr;
        $save['coldesc']=$coldesc;
        $save['colbrand']=$colbrand;
        
        H::setKeyVal('nimportcols',serialize($save)) ;
        
        $data = array();
        $oSpreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file['tmp_name']); // Вариант и для xls и xlsX


        $oCells = $oSpreadsheet->getActiveSheet()->getCellCollection();

        for ($iRow = ($passfirst ? 2 : 1); $iRow <= $oCells->getHighestRow(); $iRow++) {

            $row = array();
            for ($iCol = 'A'; $iCol <= $oCells->getHighestColumn(); $iCol++) {
                $oCell = $oCells->get($iCol . $iRow);
                if ($oCell) {
                    $row[$iCol] = $oCell->getValue();
                }
            }
            $data[$iRow] = $row;
        }

        unset($oSpreadsheet);

        if ($preview) {

            $this->_tvars['preview3'] = true;
            $this->_tvars['list'] = array();
            foreach ($data as $row) {

                $this->_tvars['list'][] = array(
                    'colname'    => $row[$colname] ?? '',
                    'colcode'    => $row[$colcode] ?? '',
                    'colbarcode' => $row[$colbarcode] ?? '',
                    'colqty'     => $row[$colqty] ?? '',
                    'colbrand'   => $row[$colbrand] ?? '',
                    'colmsr'     => $row[$colmsr] ?? '',
                    'colprice'   => $row[$colprice]  ?? ''
                );
            }
            return;
        }

        $cnt = 0;
        $items = array();
        foreach ($data as $row) {


            $item = null;
            $itemname = trim($row[$colname] ?? '');
            $itemcode = trim($row[$colcode] ?? '');
            if (strlen($itemname) > 0) {

                if (strlen($itemname) > 0) {
                    $item = Item::getFirst('itemname=' . Item::qstr($itemname));
                }
                if (strlen($itemcode) > 0) {
                    $item = Item::getFirst('item_code=' . Item::qstr($itemcode));
                }


                $price = doubleval( str_replace(',', '.', trim($row[$colprice] ?? '')) );
                $qty = doubleval(str_replace(',', '.', trim($row[$colqty] ?? '')) );

                if ($item == null) {
                    $item = new Item();
                    $item->itemname = $itemname;
                    $item->item_code = trim($row[$colcode] ?? ''  );
                    $item->msr = trim($row[$colmsr]  ?? '');
                    $item->description = trim($row[$coldesc]  ?? '');
                    $item->manufacturer = trim($row[$colbrand]  ?? '');

                    $item->save();
                }
                if ($qty > 0) {
                    $item->price = $price;
                    $item->quantity = $qty;

                    $items[] = $item;
                }
            }
        }
        if (count($items) > 0) {
            $doc = \App\Entity\Doc\Document::create('GoodsReceipt');
            $doc->document_number = $doc->nextNumber();
            if (strlen($doc->document_number) == 0) {
                $doc->document_number = "ПН00001";
            }
            $doc->document_date = time();

            $amount = 0;
            $itlist = array();
            foreach ($items as $item) {
                $itlist[$item->item_id] = $item;
                $amount = $amount + ($item->quantity * $item->price);
            }
            $doc->packDetails('detaildata', $itlist);
            $doc->amount = H::fa($amount);
            $doc->payamount = $amount;
            $doc->headerdata['payamount'] = $amount;

            $doc->headerdata['payed'] = 0;
            $doc->notes = 'Імпорт з Excel';
            $doc->headerdata['store'] = $store;
            $doc->customer_id = $c;
            $doc->headerdata['customer_name'] = $this->nform->ncust->getText();

            $doc->save();
            $doc->updateStatus(\App\Entity\Doc\Document::STATE_NEW);
            App::Redirect("\\App\\Pages\\Doc\\GoodsReceipt", $doc->document_id);
        }
    }


    public function oZImport($sender) {

        $c = $this->zform->zcust->getKey();
        //$checkname = $this->nform->ncheckname->isChecked();

        $preview = $this->zform->zpreview->isChecked();
        $passfirst = $this->zform->zpassfirst->isChecked();
        $this->_tvars['preview4'] = false;

        $colname = $this->zform->zcolname->getValue();
        $colcode = $this->zform->zcolcode->getValue();

        $colqty = $this->zform->zcolqty->getValue();
        $colprice = $this->zform->zcolprice->getValue();

        if ($colname === '0') {
            $this->setError('Не вказано колонку з назвою');
            return;
        }
        if ($colqty === '0') {
            $this->setError('Не вказано колонку з кількістю');
            return;
        }

        if ($c == 0) {
            $this->setError('Не обрано покупця');
            return;
        }

        $file = $this->zform->zfilename->getFile();
        if (strlen($file['tmp_name']) == 0) {

            $this->setError('Не вибраний файл');
            return;
        }

        $data = array();
        $oSpreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file['tmp_name']); // Вариант и для xls и xlsX


        $oCells = $oSpreadsheet->getActiveSheet()->getCellCollection();

        for ($iRow = ($passfirst ? 2 : 1); $iRow <= $oCells->getHighestRow(); $iRow++) {

            $row = array();
            for ($iCol = 'A'; $iCol <= $oCells->getHighestColumn(); $iCol++) {
                $oCell = $oCells->get($iCol . $iRow);
                if ($oCell) {
                    $row[$iCol] = $oCell->getValue();
                }
            }
            $data[$iRow] = $row;
        }

        unset($oSpreadsheet);

        if ($preview) {

            $this->_tvars['preview4'] = true;
            $this->_tvars['list'] = array();
            foreach ($data as $row) {

                $this->_tvars['list'][] = array(
                    'colname'    => $row[$colname] ?? '',
                    'colcode'    => $row[$colcode] ?? '',

                    'colqty'     => $row[$colqty] ?? '',
                    'colprice'   => $row[$colprice]  ?? ''
                );
            }
            return;
        }

        $cnt = 0;
        $items = array();
        foreach ($data as $row) {


            $item = null;
            $itemname = trim($row[$colname] ?? '');
            $itemcode = trim($row[$colcode] ?? '');

            if (strlen($itemname) > 0) {

                if (strlen($itemname) > 0) {
                    $item = Item::getFirst('itemname=' . Item::qstr($itemname));
                }
                if (strlen($itemcode) > 0) {
                    $code = Item::qstr($itemcode) ;
                    $item = Item::getFirst("item_code={$code} or bar_code={$code}");
                }


                $price = str_replace(',', '.', trim($row[$colprice] ?? ''));
                $qty = str_replace(',', '.', trim($row[$colqty] ?? ''));

                if ($item == null) {
                    $this->setError("Не знайдоно товар {$itemname} {$itemcode}");
                    return;
                }
                if ($qty > 0) {
                    $item->price = $price;
                    $item->quantity = $qty;

                    $items[] = $item;
                }
            }
        }
        if (count($items) > 0) {
            $doc = \App\Entity\Doc\Document::create('Order');
            $doc->document_number = $doc->nextNumber();
            if (strlen($doc->document_number) == 0) {
                $doc->document_number = "З00001";
            }
            $doc->document_date = time();

            $amount = 0;
            $itlist = array();
            foreach ($items as $item) {
                $itlist[$item->item_id] = $item;
                $amount = $amount + ($item->quantity * $item->price);
            }
            $doc->packDetails('detaildata', $itlist);
            $doc->amount = H::fa($amount);
            $doc->payamount = $doc->amount;
      
            $doc->headerdata['payed'] = 0;
            $doc->notes = 'Імпорт з Excel';
            $doc->customer_id = $c;
            $doc->headerdata['customer_name'] = $this->zform->zcust->getText();

            $doc->save();
            $doc->updateStatus(\App\Entity\Doc\Document::STATE_NEW);
            App::Redirect("\\App\\Pages\\Doc\\Order", $doc->document_id);
        }
    }


    public function onOImport($sender) {

        $file = $this->oform->ofilename->getFile();
        if (strlen($file['tmp_name']) == 0) {
            $this->setError('Не вибраний файл');
            return;
        }

        $conn= \ZDB\DB::getConnect() ;

        $xml = @simplexml_load_file($file['tmp_name']) ;
        if($xml==false) {

            $this->setError("Невірний  контент");

            return;
        }

        $list=[];

        foreach ($xml->children() as $row) {


            $name= (string)$row->optname[0] ;
            $value= (string)$row->optvalue[0] ;
            $list[$name]=$value;

        }


        $conn->BeginTrans();


        try {

            foreach($list as $n=>$v) {
                $n = $conn->qstr($n);
                $v = $conn->qstr($v);

                $conn->Execute("delete from options where  optname={$n}")  ;
                $conn->Execute("insert into options (optname,optvalue) values({$n},{$v}) ")  ;

            }

            $conn->CommitTrans();

        } catch(\Throwable $ee) {
            $conn->RollbackTrans();
            $this->setError($ee->getMessage());

            return;
        }


        $this->setSuccess("Імпортовано  ");
    }


}
