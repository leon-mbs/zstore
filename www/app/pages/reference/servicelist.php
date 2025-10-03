<?php

namespace App\Pages\Reference;

use App\Entity\Item;
use App\Entity\Service;
use App\Helper as H;
use Zippy\Html\DataList\ArrayDataSource;
use Zippy\Html\DataList\DataView;
use Zippy\Html\Form\AutocompleteTextInput;
use Zippy\Html\Form\Button;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Form\TextArea;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Panel;

class ServiceList extends \App\Pages\Base
{
    private $_service;
    public $_itemset;

    public function __construct() {
        parent::__construct();
        if (false == \App\ACL::checkShowRef('ServiceList')) {
            return;
        }

        $this->add(new Form('filter'))->onSubmit($this, 'OnFilter');
        $this->filter->add(new CheckBox('showdis'));
        $this->filter->add(new TextInput('searchkey'));
        $this->filter->add(new TextInput('searchcat'))->setDataList(Service::getCategoryList());

        $this->add(new Panel('servicetable'))->setVisible(true);
        $this->servicetable->add(new DataView('servicelist', new ServiceDataSource($this), $this, 'servicelistOnRow'))->Reload();
        $this->servicetable->add(new ClickLink('addnew'))->onClick($this, 'addOnClick');
        $this->servicetable->add(new ClickLink('toexport'))->onClick($this, 'ExportOnClick');
        $this->servicetable->add(new ClickLink('toimport'))->onClick($this, 'ImportOnClick');
        $this->servicetable->servicelist->setPageSize(H::getPG());
        $this->servicetable->add(new \Zippy\Html\DataList\Paginator('pag', $this->servicetable->servicelist));

        $this->add(new Form('servicedetail'))->setVisible(false);
        $this->servicedetail->add(new TextInput('editservice_name'));
        $this->servicedetail->add(new TextInput('editprice'));
        $this->servicedetail->add(new TextInput('editcat'));
        $this->servicedetail->add(new TextInput('editcost'));
        $this->servicedetail->add(new TextInput('edithours'));
        $this->servicedetail->add(new TextInput('editmsr'));
        $this->servicedetail->add(new TextInput('editnotes'));
        $this->servicedetail->add(new TextInput('editnds'));
        $this->servicedetail->add(new CheckBox('editdisabled'));
        $this->servicedetail->add(new CheckBox('editnoprice'));

        $this->servicedetail->add(new SubmitButton('save'))->onClick($this, 'saveOnClick');
        $this->servicedetail->add(new Button('cancel'))->onClick($this, 'cancelOnClick');
        
        
        $this->add(new Panel('setpanel'))->setVisible(false);
        $this->setpanel->add(new DataView('setlist', new ArrayDataSource($this, '_itemset'), $this, 'itemsetlistOnRow'));
        $this->setpanel->add(new Form('setform')) ;
        $this->setpanel->setform->add(new AutocompleteTextInput('editsname'))->onText($this, 'OnAutoSet');
        $this->setpanel->setform->add(new TextInput('editsqty', 1));
        $this->setpanel->setform->add(new SubmitButton('setformbtn'))->onClick($this, 'OnAddSet');
  
        $this->setpanel->add(new Form('cardform'))->onSubmit($this, 'OnCardSet');
        $this->setpanel->cardform->add(new TextArea('editscard'));

        $this->setpanel->add(new Label('stitle'));

        $this->setpanel->add(new ClickLink('backtolist', $this, "onback"));

        $this->add(new Panel('exportform'))->setVisible(false);        
        $this->exportform->add(new ClickLink('eback', $this, "onback"));
        $this->exportform->add(new Label('preview' ));
    
        $cols = array(0 => '-', 'A' => 'A', 'B' => 'B', 'C' => 'C', 'D' => 'D', 'E' => 'E', 'F' => 'F'  );
           
        $this->add(new Panel('importform'))->setVisible(false);        
        $this->importform->add(new ClickLink('iback', $this, "onback"));
        $form= $this->importform->add(new Form('cform' ));
        $form->add(new CheckBox("cpreview"));
        $form->add(new CheckBox("cpassfirst"));
        $form->add(new DropDownChoice("colname", $cols));
        $form->add(new DropDownChoice("colcat", $cols));
        $form->add(new DropDownChoice("colprice", $cols));
        $form->add(new DropDownChoice("colnotes", $cols));
        
        $form->add(new \Zippy\Html\Form\File("cfilename"));

        $form->onSubmit($this, "onImport");
       
    }

    public function servicelistOnRow(\Zippy\Html\DataList\DataRow $row) {
        $item = $row->getDataItem();
        $row->add(new ClickLink('service_name', $this, 'editOnClick'))->setValue($item->service_name);

        $row->add(new Label('hasaction'))->setVisible($item->hasAction());
        $row->add(new Label('price', $item->price));
        $row->add(new Label('cost', $item->cost));
        $row->add(new Label('hours', $item->hours));
        $row->add(new Label('msr', $item->msr));
        $row->add(new Label('notes', $item->notes));
        $row->add(new ClickLink('edit'))->onClick($this, 'editOnClick');
        $row->add(new ClickLink('delete'))->onClick($this, 'deleteOnClick');

        $row->add(new ClickLink('itemset'))->onClick($this, 'setOnClick');

        $row->add(new ClickLink('hascard'))->onClick($this, 'showcardOnClick',true);
        $row->hascard->setVisible(strlen($item->techcard ?? '') > 0);
        
        
    }

    public function deleteOnClick($sender) {
        if (false == \App\ACL::checkDelRef('ServiceList')) {
            return;
        }

        $service_id = $sender->owner->getDataItem()->service_id;

        $del = Service::delete($service_id);
        if (strlen($del) > 0) {
            $this->setError($del);
            return;
        }
        $this->servicetable->servicelist->Reload();
    }

    public function editOnClick($sender) {
        $this->_service = $sender->owner->getDataItem();
        $this->servicetable->setVisible(false);
        $this->servicedetail->setVisible(true);
        $this->servicedetail->editservice_name->setText($this->_service->service_name);
        $this->servicedetail->editprice->setText($this->_service->price);
        $this->servicedetail->editcost->setText($this->_service->cost);
        $this->servicedetail->edithours->setText($this->_service->hours);
        $this->servicedetail->editmsr->setText($this->_service->msr);
        $this->servicedetail->editnotes->setText($this->_service->notes);
        $this->servicedetail->editnds->setText($this->_service->nds);
        $this->servicedetail->editdisabled->setChecked($this->_service->disabled);
        $this->servicedetail->editnoprice->setChecked($this->_service->noprice);
        $this->servicedetail->editcat->setText($this->_service->category);
        $this->servicedetail->editcat->setDataList(Service::getCategoryList());
        $this->servicedetail->editmsr->setDataList(Service::getMsrList());
   }

    public function addOnClick($sender) {
        $this->servicetable->setVisible(false);
        $this->servicedetail->setVisible(true);
        // Очищаем  форму
        $this->servicedetail->clean();
        $this->servicedetail->editcat->setDataList(Service::getCategoryList());
        $this->servicedetail->editmsr->setDataList(Service::getMsrList());

        $this->_service = new Service();
    }

    public function saveOnClick($sender) {
        if (false == \App\ACL::checkEditRef('ServiceList')) {
            return;
        }

        $this->_service->service_name = $this->servicedetail->editservice_name->getText();
        $this->_service->price = $this->servicedetail->editprice->getDouble();
        $this->_service->category = $this->servicedetail->editcat->getText();
        $this->_service->cost = $this->servicedetail->editcost->getDouble();
        $this->_service->hours = $this->servicedetail->edithours->getText();
        $this->_service->msr = $this->servicedetail->editmsr->getText();
        $this->_service->nds = $this->servicedetail->editnds->getText();
        $this->_service->notes = $this->servicedetail->editnotes->getText();
        if ($this->_service->service_name == '') {
            $this->setError("Не введено назву");
            return;
        }
        $this->_service->disabled = $this->servicedetail->editdisabled->isChecked() ? 1 : 0;
        $this->_service->noprice = $this->servicedetail->editnoprice->isChecked() ? 1 : 0;

        $this->_service->save();
        $this->servicedetail->setVisible(false);
        $this->servicetable->setVisible(true);
        $this->servicetable->servicelist->Reload();

        $this->filter->searchcat->setDataList(Service::getCategoryList());

    }

    public function cancelOnClick($sender) {
        $this->servicetable->setVisible(true);
        $this->servicedetail->setVisible(false);
    }

    public function OnFilter($sender) {
        $this->servicetable->servicelist->Reload();
    }
   
    public function onback($sender) {
        $this->setpanel->setVisible(false);
        $this->exportform->setVisible(false);
        $this->importform->setVisible(false);
        $this->servicetable->setVisible(true);
    }  
    public function ExportOnClick($sender) {
        $this->exportform->setVisible(true);
        $this->importform->setVisible(false);
        $this->servicetable->setVisible(false);

        foreach (Service::findYield("disabled <> 1 and  detail not like '%<noprice>1</noprice>%' ", "category,service_name") as $item) {
       
       
             $detail[] = array(
                
                "name"   => $item->service_name,
                "cat"    => $item->category,
                "price"   =>  H::fa($item->price),
                "notes"   => $item->notes 

               );
        } 

        $header = array(
            "_detail"    => $detail,
   
            'date'       => H::fd(time())
        );
        $report = new \App\Report('report/priceser.tpl');

        $html = $report->generate($header);
        
      
        $this->exportform->preview->setText($html, true);
        \App\Session::getSession()->printform = "<html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\"></head><body>" . $html . "</body></html>";

        
        
    }  
    
    public function ImportOnClick($sender) {
        $this->exportform->setVisible(false);
        $this->importform->setVisible(true);
        $this->servicetable->setVisible(false);
    }  
    
    
  public function onImport($sender) {
        
        $preview = $this->importform->cform->cpreview->isChecked();
        $passfirst = $this->importform->cform->cpassfirst->isChecked();
        $this->_tvars['preview2'] = false;

        $colname = $this->importform->cform->colname->getValue();
        $colcat = $this->importform->cform->colcat->getValue();
        $colprice = $this->importform->cform->colprice->getValue();
        $colnotes  = $this->importform->cform->colnotes->getValue();
       
        if ($colname === '0') {
            $this->setError('Не вказано колонку з назвою');
            return;
        }

        $file = $this->importform->cform->cfilename->getFile();
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
                    'colname'    => $row[$colname] ?? '',
                    'colcat'   => $row[$colcat] ?? '',
                    'colprice'   => $row[$colprice] ?? '',
                    'colnotes'   => $row[$colnotes] ?? ''
                );
            }
            return;
        }

        $cnt = 0;
        $newitems = array();
        foreach ($data as $row) {

            $s = null;
            $name = $row[$colname] ?? '';
           
            if (strlen(trim($name)) == 0) {
                continue;
            }

          
            $s = Service::getFirst('service_name=' . Service::qstr($name));
           

            if ($s == null) {
                $s = new Service();
                $s->service_name= $name;
            }    
            
            $s->price = H::fa( $row[$colprice] ?? 0);
            $s->category = $row[$colcat] ?? '';
            $s->notes = $row[$colnotes] ?? '';
        

            $s->save();
            $cnt++;
             
        }

       $this->servicetable->servicelist->Reload();
       $this->setSuccess("Імпортовано {$cnt} позицiй ");
    }
    
    
    
      //комплекты  
    public function setOnClick($sender) {
        $this->_service = $sender->owner->getDataItem();

        $this->setpanel->setVisible(true);
        $this->servicetable->setVisible(false);

        $this->setpanel->stitle->setText($this->_service->service_name);

        $this->_itemset = $this->_service->itemset;
        $this->setpanel->setlist->Reload();

        $this->setpanel->cardform->editscard->setText($this->_service->techcard)  ;

    }

    private function setupdate() {
        $this->setpanel->setform->clean();
        $this->_service->itemset =  $this->_itemset ;
        $this->_service->save() ;
        $this->setpanel->setlist->Reload();
        $this->servicetable->servicelist->Reload();
    
    }

    public function itemsetlistOnRow(\Zippy\Html\DataList\DataRow $row) {
        $item = $row->getDataItem();
        $row->add(new Label('sname', $item->itemname));
        $row->add(new Label('scode', $item->item_code));
        $row->add(new Label('sqty', H::fqty($item->qty)));
        $row->add(new ClickLink('sdel'))->onClick($this, 'ondelset');
    }

    public function OnAutoSet($sender) {
        $text = Item::qstr('%' . $sender->getText() . '%');
        $in = "(0" ;
        foreach ($this->_itemset as $is) {
            $in .= "," . $is->item_id;
        }

        $in .= ")";
        return Item::findArray('itemname', " item_type    in (2,5) and  item_id not in {$in} and (itemname like {$text} or item_code like {$text}) and disabled <> 1", 'itemname');
    }

    public function OnAddSet($sender) {
        $form=  $this->setpanel->setform;
        $id = $form->editsname->getKey();
        if ($id == 0) {
            $this->setError("Не обрано ТМЦ");
            return;
        }

        $item = Item::load($id);
        
        $qty = $form->editsqty->getText();

        $set = new \App\DataItem() ;
        $set->itemname = $item->itemname;
        $set->item_code = $item->item_code;
        $set->item_id = $id;
        $set->qty = $qty;
        $this->_itemset[]=$set;

        $this->setupdate() ;
        $form->clean();
    }

    public function ondelset($sender) {
        $item = $sender->owner->getDataItem();

        $tmp=[];

        foreach($this->_itemset as $s) {
            if($s->item_id!=$item->item_id) {
               $tmp[]=$s;
            }
        }
        $this->_itemset = $tmp;
        $this->setupdate() ;
    }

 
    public function OnCardSet($sender) {


        $this->_service->techcard = $sender->editscard->getText();
        $this->_service->save() ;
        $this->servicetable->servicelist->Reload();

    }
      
    public function  showcardOnClick($sender){
        $item = $sender->getOwner()->getDataItem();
        $desc = str_replace("'","`",$item->techcard);
        $desc = str_replace("\"","`",$desc);
      //  $desc = nl2br ($desc);        
        $desc = str_replace ("\n","",$desc);
        $desc = str_replace ("\r","",$desc);
        
        $this->updateAjax([],"$('#idesc').modal('show'); $('#idesccontent').html('{$desc}'); ")  ;
        
    }
 
}

class ServiceDataSource implements \Zippy\Interfaces\DataSource
{
    private $page;

    public function __construct($page) {
        $this->page = $page;
    }

    private function getWhere() {

        $form = $this->page->filter;
        $where = "1=1";
        $text = trim($form->searchkey->getText());
        $cat = trim($form->searchcat->getText());
        $showdis = $form->showdis->isChecked();

        if ($showdis > 0) {

        } else {
            $where = $where . " and disabled <> 1";
        }
        if (strlen($cat) > 0) {
            $cat = Service::qstr('%' . $cat . '%');
            $where = $where . " and category like {$cat}   ";
        }
        if (strlen($text) > 0) {
            $text = Service::qstr('%' . $text . '%');
            $where = $where . " and service_name like {$text}   ";
        }
        return $where;
    }

    public function getItemCount() {
        return Service::findCnt($this->getWhere());
    }

    public function getItems($start, $count, $sortfield = null, $asc = null) {
        return Service::find($this->getWhere(), "service_name asc", $count, $start);
    }

    public function getItem($id) {
        return Service::load($id);
    }

}
