<?php

namespace App\Pages\Register;

 
use App\Entity\SubstItem ;
use App\Entity\Item ;
use App\Entity\CustItem ;
use App\Helper as H;
use App\System;
use Zippy\Html\DataList\ArrayDataSource;
use Zippy\Html\DataList\DataView;
use Zippy\Html\Form\AutocompleteTextInput;
use Zippy\Html\Form\Button;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\TextArea;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Panel;
use Zippy\Html\Link\SubmitLink;

/**
* Журнал замены  и аналоги
*/
class SubstItems extends \App\Pages\Base
{
    private $_item;
    private $_edit;


    public function __construct( ) {
        parent::__construct();
        if (false == \App\ACL::checkShowReg('SubstItems')) {
            return;
        }

        $this->add(new Form('filter'))->onSubmit($this, 'OnFilter');

        $this->filter->add(new TextInput('searchkey'));
         
        $this->add(new Panel('itemtable'))->setVisible(true);
        $this->itemtable->add(new ClickLink('addnew'))->onClick($this, 'addOnClick');
        $this->itemtable->add(new ClickLink('imports'))->onClick($this, 'onImport');

        $this->itemtable->add(new ClickLink('csv', $this, 'oncsv'));
      
        $this->itemtable->add(new Form('listform'));

        $this->itemtable->listform->add(new DataView('itemlist', new SubstItemDataSource($this), $this, 'itemlistOnRow'));
        $this->itemtable->listform->itemlist->setPageSize(H::getPG());
        $this->itemtable->listform->add(new \Zippy\Html\DataList\Pager('pag', $this->itemtable->listform->itemlist));
        $this->itemtable->listform->add(new SubmitLink('deleteall'))->onClick($this, 'OnDelAll');


        $this->add(new Form('itemdetail'))->setVisible(false);
        $this->itemdetail->add(new TextInput('editname'));
        $this->itemdetail->add(new TextInput('editorigcode'));
        $this->itemdetail->add(new TextInput('editorigbrand'));
        $this->itemdetail->add(new TextInput('editsubstcode'));
        $this->itemdetail->add(new TextInput('editsubstbrand'));
   

        $this->itemdetail->add(new SubmitButton('save'))->onClick($this, 'OnSubmit');
        $this->itemdetail->add(new Button('cancel'))->onClick($this, 'cancelOnClick');
  

       
        $this->add(new Form('importform'))->setVisible(false);
        $this->importform->add(new Button('back'))->onClick($this, 'cancelOnClick');
        $this->importform->add(new \Zippy\Html\Form\File("filename"));
        $cols = array(0=>'-','A'=>'A','B'=>'B','C'=>'C','D'=>'D','E'=>'E' );
        
        $this->importform->add(new DropDownChoice("colname", $cols));
        $this->importform->add(new DropDownChoice("colorigcode", $cols));
        $this->importform->add(new DropDownChoice("colorigbrand", $cols));
        $this->importform->add(new DropDownChoice("colsubstcode", $cols));
        $this->importform->add(new DropDownChoice("colsubstbrand", $cols));
        $this->importform->add(new SubmitButton('loadimport'))->onClick($this, 'onLoad');
        $this->importform->add(new CheckBox("passfirst"));
        $this->importform->add(new CheckBox("preview"));
                                            
     //   $this->itemtable->listform->itemlist->Reload();

    }

    public function itemlistOnRow(\Zippy\Html\DataList\DataRow $row) {
        $item = $row->getDataItem();
        $row->add(new CheckBox('seldel', new \Zippy\Binding\PropertyBinding($item, 'seldel')));
      
        $row->add(new Label('itemname', $item->itemname));
        $row->add(new Label('origcode', $item->origcode));
        $row->add(new Label('origbrand', $item->origbrand));
        $row->add(new Label('substcode', $item->substcode));
        $row->add(new Label('substbrand', $item->substbrand));
        $row->add(new Label('initems', ''));
        $row->add(new Label('incust', ''));
       
        $it =  Item::getFirst("  item_code= ".Item::qstr($item->substcode) ."  and  coalesce(manufacturer,'') = coalesce( ".Item::qstr($item->substbrand) .",'') ");
          
        if($it != null) {
            $qty = $it->getQuantity() ;
            $t="<small>";
            $t = $t . $it->itemname.". Кiл. ".H::fqty($qty);
            if($qty>0) {
               $price = $it->getPrice() ;
               $t .= ". Цiна. ".H::fa($price)  ;
            }
            $t.="</small>";
            $row->initems->setText($t,true);
        }  
        
        $ci =  CustItem::find("  cust_code= ".Item::qstr($item->substcode) ."  and  coalesce(brand,'') = coalesce( ".Item::qstr($item->substbrand) .",'') ");
      
      
        if(count($ci) >0) {
            $t="";
            foreach($ci as $c) {
                $t.="<small style=\"display:block\">";  
                $t=$t . $c->cust_name.". Кiл. ".H::fqty($c->quantity);
                if($c->quantity <0) {
                   $t .= ". Цiна. ".H::fa($c->price)  ;  
                }  
                $t.="</small>";
            }
           
           
            $row->incust->setText($t,true);
        }
    }

    public function addOnClick($sender) {
        $this->_edit = false;
        $this->itemtable->setVisible(false);
        $this->itemdetail->setVisible(true);
        // Очищаем  форму
        $this->itemdetail->clean();

   


    }

   
    public function cancelOnClick($sender) {
        $this->itemtable->setVisible(true);
        $this->itemdetail->setVisible(false);
        $this->importform->setVisible(false);
       
        $this->itemtable->listform->itemlist->Reload();
        
    }

    public function OnSubmit($sender) {
        if (false == \App\ACL::checkEditRef('SubstItems')) {
            return;
        }
        $this->_item = new SubstItem(); 
        $this->_item->itemname = trim($this->itemdetail->editname->getText());
        $this->_item->origcode = trim($this->itemdetail->editorigcode->getText());
        $this->_item->origbrand = trim($this->itemdetail->editorigbrand->getText());
        $this->_item->substcode = trim($this->itemdetail->editsubstcode->getText());
        $this->_item->substbrand = trim($this->itemdetail->editsubstbrand->getText());
        $this->_item->save();
        //обратное
        $this->_item = new SubstItem(); 
        $this->_item->itemname = trim($this->itemdetail->editname->getText());
        $this->_item->substcode = trim($this->itemdetail->editorigcode->getText());
        $this->_item->substbrand = trim($this->itemdetail->editorigbrand->getText());
        $this->_item->origcode = trim($this->itemdetail->editsubstcode->getText());
        $this->_item->origbrand = trim($this->itemdetail->editsubstbrand->getText());
        $this->_item->save();
      
 
        $this->itemdetail->clean()  ;
   
     

        
    }
    
    public function OnFilter($sender) {
        $this->itemtable->listform->itemlist->Reload();
    }
    
    public function OnDelAll($sender) {

        $ids = array();
        foreach ($this->itemtable->listform->itemlist->getDataRows() as $row) {
            $item = $row->getDataItem();
            if ($item->seldel == true) {
                $ids[] = $item->id;
            }
        }
        if (count($ids) == 0) {
            return;
        }

        $conn = \ZDB\DB::getConnect();

        foreach ($ids as $id) {

            $conn->Execute("delete from substitems  where  id={$id}");


        }


        $this->itemtable->listform->itemlist->Reload();
  
    }
  
    public function onImport($sender) {
        $this->itemtable->setVisible(false);
        $this->itemdetail->setVisible(false);
        $this->importform->setVisible(true);
        $this->importform->clean();
    }

    public function onLoad($sender) {
 
        $passfirst =  $this->importform->passfirst->isChecked();
        $this->_tvars['ispreview'] =  $this->importform->preview->isChecked();
   
        $colname =  $this->importform->colname->getValue();
        $colorigcode =  $this->importform->colorigcode->getValue();
        $colorigbrand =  $this->importform->colorigbrand->getValue();
        $colsubstcode =  $this->importform->colsubstcode->getValue();
        $colsubstbrand =  $this->importform->colsubstbrand->getValue();
   
   
        if ( $colname === '0' 
             || $colorigcode  === '0'
             || $colorigbrand === '0'
             || $colsubstcode === '0'
             || $colsubstbrand === '0'
         ) {
            $this->setError('Не вказанi колонки ');
            return;
        }
         

        $file =  $this->importform->filename->getFile();
        if (strlen($file['tmp_name']) == 0) {

            $this->setError('Не обрано файл');
            return;
        }
        
        $oSpreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file['tmp_name']);

        $data = array();

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
        
        
        if( $this->_tvars['ispreview'] ) {
            $this->_tvars['preview'] =[];
            
            foreach ($data as $row) {
                $this->_tvars['preview'][] =array(
                'name' =>  trim($row[$colname])   ,
                'origcode'   =>  trim($row[$colorigcode])   ,
                'origbrand'  =>  trim($row[$colorigbrand])   ,
                'substcode'  =>  trim($row[$colsubstcode])   ,
                'substbrand' =>  trim($row[$colsubstbrand]) 
                )  ;
                
                if(count($this->_tvars['preview']) >= 3) break;
            }
                
            
            
            return;
        }        
        
        
        
        $cnt=0;
        foreach ($data as $row) {
          
            $name =  trim($row[$colname])   ;
            $origcode  =  trim($row[$colorigcode])   ;
            $origbrand    =  trim($row[$colorigbrand])   ;
            $substcode    =  trim($row[$colsubstcode])   ;
            $substbrand =  trim($row[$colsubstbrand])   ;
            

            $item = new SubstItem();
            
            $item->itemname = $name;
            $item->origcode = $origcode;
            $item->origbrand = $origbrand;
            $item->substcode = $substcode;
            $item->substbrand = $substbrand;
           
         
            $item->save();
            $item = new SubstItem();
            
            $item->itemname = $name;
            $item->origcode = $substcode;
            $item->origbrand = $substbrand;
            $item->substcode = $origcode;
            $item->substbrand = $origbrand;
           
         
            $item->save();
            
            
            $cnt++;

        }
        
        
       
        
        $this->setSuccess("Імпортовано {$cnt} ТМЦ");
        $this->itemtable->listform->itemlist->Reload();

        $this->itemtable->setVisible(true);
        $this->itemdetail->setVisible(false);
        $this->importform->setVisible(false);

        $this->itemtable->listform->itemlist->Reload();     

    }
     
    public function oncsv($sender) {
 
       $tempDir = sys_get_temp_dir(); 
       $prefix = 'zstore_tmp_';
       $tempFilePath = tempnam($tempDir, $prefix);

       $fh = fopen($tempFilePath, 'w');
      
      
       $line ="Найменування;Код оригiнала;Бренд оригiнала;Код замiни;Бренд замiни;  ";
       $line = mb_convert_encoding($line, "windows-1251", "utf-8");
       fwrite($fh, $line . PHP_EOL);      
       
       $ds = new SubstItemDataSource($this);
      
       foreach(SubstItem::findYield($ds->getWhere(), "", -1, -1) as $item){
            $line ="";
            $line .= $item->itemname.';';
            $line .= $item->origcode.';';
            $line .= $item->origbrand.';';
            $line .= $item->substcode.';';
            $line .= $item->substbrand.';';
//            $line .= str_replace(';','.', $item->comment) .';';
            $line = mb_convert_encoding($line, "windows-1251", "utf-8");
     
            fwrite($fh, $line . PHP_EOL);                
       }
    

        H::exportCSV($tempFilePath, 'substitems.csv');
 
    }
     
}


class SubstItemDataSource implements \Zippy\Interfaces\DataSource
{
    private $page;

    public function __construct($page) {
        $this->page = $page;
    }

    public function getWhere( ) {

        $form = $this->page->filter;
        $where = "1=1";
        $key = $form->searchkey->getText();
       
      

        if (strlen($key) > 0) {

            $skey = SubstItem::qstr('%' . $key . '%');
            $key = SubstItem::qstr($key);
            $where  = "   (itemname like {$skey} or origcode = {$key}  or coalesce(origbrand,'') = {$key} )  ";

        }



        return $where;
    }

    public function getItemCount() {
        return SubstItem::findCnt($this->getWhere());
    }

    public function getItems($start, $count, $sortfield = null, $asc = null) {
        $sortfield = "itemname asc,origcode asc";
        $w=trim($this->getWhere() );
        if($w=='1=1') $sortfield='';
   
        $l = SubstItem::find($this->getWhere(), $sortfield, $count, $start);

        return $l;
    }

    public function getItem($id) {
        return CustItem::load($id);
    }

}

 