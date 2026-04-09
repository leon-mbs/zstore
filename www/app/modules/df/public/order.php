<?php

namespace App\Modules\DF\Public;

use App\Application as App;
use App\Entity\Customer;
use App\Entity\Category;
use App\Entity\Doc\Document;
use App\Entity\Item;
use App\System ;
use App\Helper as H;
use Zippy\Html\DataList\DataView;
use Zippy\Html\Form\AutocompleteTextInput;
use Zippy\Html\Form\Button;
use Zippy\Html\Form\Date;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\TextArea;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Label;
use Zippy\Html\Panel;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Link\SubmitLink;
use Zippy\Html\DataList\DataTable;

/**
 * Страница  ввода  заказа
 */
class Order extends  Base
{
    public $_tovarlist = array();
    private $_doc;
 
    private $_rowid     = -1;
      

    /**
    * @param mixed $docid     редактирование
 
    */
    public function __construct($docid = 0 ) {
        parent::__construct();
       
        $common =  System::getOptions("common");
        $modules = System::getOptions('modules');

 
        $this->_tvars["np"] = $modules['np'] == 1;
    
     
        $this->add(new Form('docform'));
        $this->docform->add(new TextInput('document_number'));

        $this->docform->add(new Date('document_date'))->setDate(time());
 
       
        $this->docform->add(new TextArea('notes'));
           
        $this->docform->add(new DropDownChoice('delivery', Document::getDeliveryTypes($this->_tvars['np'] == 1),1))->onChange($this, 'OnDelivery');
        $this->docform->add(new DropDownChoice('deliverynp', [],0))->onChange($this, 'OnDeliverynp');
      
     
        
        $this->docform->add(new SubmitLink('addrow'))->onClick($this, 'addrowOnClick');
        $this->docform->add(new SubmitButton('savedoc'))->onClick($this, 'savedocOnClick');
        $this->docform->add(new SubmitButton('execdoc'))->onClick($this, 'savedocOnClick');

       
        $this->docform->add(new Label('total'));
        $this->docform->add(new Label('totalfrom'));
        
        $this->docform->add(new AutocompleteTextInput('baycity'))->onText($this, 'onTextBayCity');
        $this->docform->baycity->onChange($this, 'onBayCity');
        $this->docform->add(new AutocompleteTextInput('baypoint'))->onText($this, 'onTextBayPoint');;
        
      
        $this->docform->add(new TextInput('bayhouse'));
        $this->docform->add(new TextInput('bayflat'));
        $this->docform->add(new TextInput('address'));
        $this->docform->add(new TextInput('outnumber'));
  
       

        $this->add(new Form('editdetail'))->setVisible(false);
        $this->editdetail->add(new TextInput('editquantity'))->setText("1");
        $this->editdetail->add(new TextInput('editprice'));
        $this->editdetail->add(new TextInput('editdesc'));

        $this->editdetail->add(new AutocompleteTextInput('edittovar'))->onText($this, 'OnAutoItem');
        $this->editdetail->edittovar->onChange($this, 'OnChangeItem' );
        $this->editdetail->add(new ClickLink('openitemsel', $this, 'onOpenItemSel'));
        
   
        $this->editdetail->add(new Label('pricefrom'));

  
        $this->editdetail->add(new Button('cancelrow'))->onClick($this, 'cancelrowOnClick');
        $this->editdetail->add(new SubmitButton('submitrow'))->onClick($this, 'saverowOnClick');
       
        $this->add(new Panel('witempan'))->setVisible(false);  
        $this->witempan->add(new Form('wisfilter'))->onSubmit($this, 'ReloadData');

        $this->witempan->wisfilter->add(new CheckBox('wissearchonstore'));
        $this->witempan->wisfilter->add(new TextInput('wissearchkey'));
        $this->witempan->wisfilter->add(new DropDownChoice('wissearchcat', Category::getList(false, false), 0));
        $this->witempan->wisfilter->add(new TextInput('wissearchmanufacturer'));
        $this->witempan->wisfilter->wissearchmanufacturer->setDataList(Item::getManufacturers());


        $table = $this->witempan->add(new DataTable('witemselt', new WISDataSource($this ), true, true));
        $table->setPageSize(H::getPG());
        $table->AddColumn(new \Zippy\Html\DataList\Column('itemname', "Назва", true, true, true));
        $table->AddColumn(new \Zippy\Html\DataList\Column('item_code', "Артикул", true, true, false));
        $table->AddColumn(new \Zippy\Html\DataList\Column('bar_code', "Штрих-код", true, true, false));
        $table->AddColumn(new \Zippy\Html\DataList\Column('manufacturer', "Бренд", true, true, false));

        $table->setCellClickEvent($this, 'OnSelect');

       

        if ($docid > 0) {    //загружаем   содержимое  документа настраницу
            $this->_doc = Document::load($docid)->cast();
            $this->docform->document_number->setText($this->_doc->document_number);

            $this->docform->document_date->setDate($this->_doc->document_date);
         

            $this->docform->delivery->setValue($this->_doc->headerdata['delivery']);
            $this->OnDelivery($this->docform->delivery);
            $this->docform->deliverynp->setValue($this->_doc->headerdata['deliverynp']);
            $this->OnDeliverynp($this->docform->deliverynp);

            
            $this->docform->baycity->setKey($this->_doc->headerdata['baycity'] ?? '');
            $this->docform->baypoint->setKey($this->_doc->headerdata['baypoint'] ?? '');
            $this->docform->baycity->setText($this->_doc->headerdata['baycityname'] ?? '');
            $this->docform->baypoint->setText($this->_doc->headerdata['baypointname'] ?? '');
            
            $this->docform->bayhouse->setText($this->_doc->headerdata['bayhouse'] ?? '');
            $this->docform->bayflat->setText($this->_doc->headerdata['bayflat']?? '' );
              
            
           
            $this->docform->total->setText($this->_doc->amount);
            $this->docform->outnumber->setText($this->_doc->headerdata['outnumber'] ??'');

         
            $this->docform->notes->setText($this->_doc->notes);
        
           

            $this->_tovarlist = $this->_doc->unpackDetails('detaildata')  ;
       
            $this->calcTotal(); 

        } else {
            $this->_doc = Document::create('Order');
            $this->docform->document_number->setText($this->_doc->nextNumber());
           
            
        }

        $this->docform->add(new DataView('detail', new \Zippy\Html\DataList\ArrayDataSource(new \Zippy\Binding\PropertyBinding($this, '_tovarlist')), $this, 'detailOnRow'))->Reload();
       
        $this->OnDelivery($this->docform->delivery);
  
    }

    public function detailOnRow($row) {
        $item = $row->getDataItem();

        $row->add(new Label('tovar', $item->itemname));

        $row->add(new Label('code', $item->item_code));
        $row->add(new Label('msr', $item->msr));
        $row->add(new Label('desc', $item->desc));

        $row->add(new Label('quantity', H::fqty($item->quantity)));
        $row->add(new Label('pricefrom', H::fa($item->pricefrom)));
        $row->add(new Label('price', H::fa($item->price)));

        $row->add(new Label('amount', H::fa($item->quantity * $item->price)));
        $row->add(new ClickLink('delete'))->onClick($this, 'deleteOnClick');
        $row->add(new ClickLink('edit'))->onClick($this, 'editOnClick');
    }

    public function deleteOnClick($sender) {
        if (false == \App\ACL::checkEditDoc($this->_doc)) {
            return;
        }
        $item = $sender->owner->getDataItem();
        $rowid =  array_search($item, $this->_tovarlist, true);

        $this->_tovarlist = array_diff_key($this->_tovarlist, array($rowid => $this->_tovarlist[$rowid]));

        $this->docform->detail->Reload();
        $this->calcTotal();
         
    }

    public function addrowOnClick($sender) {
        $this->editdetail->setVisible(true);
        $this->editdetail->editquantity->setText("1");
        $this->editdetail->editprice->setText("0");
        $this->editdetail->editdesc->setText("");
       
        $this->editdetail->pricefrom->setText("");
        
        $this->docform->setVisible(false);
        $this->_rowid = -1;
    }

    public function editOnClick($sender) {
        $item = $sender->getOwner()->getDataItem();
        $this->editdetail->setVisible(true);
        $this->docform->setVisible(false);

        $this->editdetail->editquantity->setText($item->quantity);
        $this->editdetail->editprice->setText($item->price);
        $this->editdetail->pricefrom->setText($item->pricefrom);
        $this->editdetail->editdesc->setText($item->desc);

        $this->editdetail->edittovar->setKey($item->item_id);
        $this->editdetail->edittovar->setText($item->itemname);

        $this->_rowid =  array_search($item, $this->_tovarlist, true);

    }

    public function saverowOnClick($sender) {
        if (false == \App\ACL::checkEditDoc($this->_doc)) {
            return;
        }
        $id = $this->editdetail->edittovar->getKey();
        if ($id == 0) {
            $this->setError("Не обрано товар");
            return;
        }

        $item = Item::load($id);

        $item->quantity = $this->editdetail->editquantity->getDouble();

        $item->price = $this->editdetail->editprice->getDouble();
        
        if($item->price==0){
           $this->setError('Не задано відпускну ціну');
           return;
        }
        $item->pricefrom = $this->editdetail->pricefrom->getText();

    
        $item->desc = $this->editdetail->editdesc->getText();

        if($this->_rowid == -1) {    //новая  позиция
            $found=false;
            
            foreach ($this->_tovarlist as $ri => $_item) {
                if ($_item->item_id == $item->item_id ) {
                    $this->_tovarlist[$ri]->quantity += $item->quantity;
                    $found = true;
                }
            }        
        
            if(!$found) {
               $this->_tovarlist[] = $item;    
            }
            
            $this->addrowOnClick(null);
            $this->setInfo("Позиція додана") ;
            //очищаем  форму
            $this->editdetail->edittovar->setKey(0);
            $this->editdetail->edittovar->setText('');

            $this->editdetail->editquantity->setText("1");

        } else {
            $this->_tovarlist[$this->_rowid] = $item;
            $this->cancelrowOnClick(null);

        }


        $this->docform->detail->Reload();
        $this->calcTotal();
          

    }

    public function cancelrowOnClick($sender) {
        $this->editdetail->setVisible(false);
        $this->docform->setVisible(true);
        //очищаем  форму
        $this->editdetail->edittovar->setKey(0);
        $this->editdetail->edittovar->setText('');

        $this->editdetail->editquantity->setText("1");

        $this->editdetail->editprice->setText("");
        
        $this->witempan->setVisible(false); 
    }
   
    public function savedocOnClick($sender) {
        
     
        $this->_doc->headerdata['dsff'] = $this->_tvars["isds"]  ? 1:0;  
        $this->_doc->headerdata['dsff'] = $this->_tvars["isff"]  ? 2:0;  
      
         
        $this->_doc->document_number = $this->docform->document_number->getText();
        $this->_doc->document_date = strtotime($this->docform->document_date->getText());
        $this->_doc->notes = $this->docform->notes->getText();
        $this->_doc->customer_id = $this->_customer->customer_id;
        $this->_doc->headerdata['ship_address'] = $this->docform->address->getText();
       
        
        $this->_doc->headerdata['delivery'] = $this->docform->delivery->getValue();
        $this->_doc->headerdata['delivery_name'] = $this->docform->delivery->getValueName();
        $this->_doc->headerdata['deliverynp'] = $this->docform->deliverynp->getValue();

        $this->_doc->headerdata['baycity'] = $this->docform->baycity->getKey();
        $this->_doc->headerdata['baycityname'] = $this->docform->baycity->getText();
        $this->_doc->headerdata['baypoint'] = $this->docform->baypoint->getKey();
        $this->_doc->headerdata['baypointname'] = $this->docform->baypoint->getText();
        
        $this->_doc->headerdata['bayhouse'] = $this->docform->bayhouse->getText();
        $this->_doc->headerdata['bayflat'] = $this->docform->bayflat->getText();
        $this->_doc->headerdata['npaddress'] = $this->docform->address->getText();
        $this->_doc->headerdata['npaddressfull'] ='';

       
        if(strlen($this->_doc->headerdata['baycity'])>1) {
           $this->_doc->headerdata['npaddressfull']  .= (' '. $this->docform->baycity->getText() );   
        }
        if(strlen($this->_doc->headerdata['baypoint'])>1) {
           $this->_doc->headerdata['npaddressfull']  .= (' '. $this->docform->baypoint->getText() );   
        }
        if(strlen($this->_doc->headerdata['bayhouse'])>0) {
           $this->_doc->headerdata['npaddressfull']  .= (' буд '. $this->docform->bayhouse->getText() );   
        }
        if(strlen($this->_doc->headerdata['bayflat'])>0) {
           $this->_doc->headerdata['npaddressfull']  .= (' кв '. $this->docform->bayflat->getText() );   
        }
        if(strlen($this->_doc->headerdata['npaddressfull'])==0) {
           $this->_doc->headerdata['npaddressfull']  = $this->_doc->headerdata['npaddress'];   
        }
        
        
    

        $this->_doc->packDetails('detaildata', $this->_tovarlist);

        $this->_doc->headerdata['outnumber'] = $this->docform->outnumber->getText();
        $this->_doc->amount = $this->docform->total->getText();

     
        if ($this->checkForm() == false) {
            return;
        }
        $isEdited = $this->_doc->document_id > 0;

        $conn = \ZDB\DB::getConnect();
        $conn->BeginTrans();


        try {
      
            $this->_doc->payed = 0;
            $this->_doc->headerdata['payed'] = 0;
          
          //  $this->_doc->headerdata['store'] = $this->docform->store->getValue() ;
          //  $this->_doc->headerdata['storename'] = $this->docform->store->getValueName() ;
         
            $this->_doc->setHD('delayinprocess',1);  
            if ($sender->id == 'execdoc'  ) {
                $this->_doc->setHD('delayinprocess',null);  //пявится  в  журнале  заказов
            }
            
            $this->_doc->user_id=null;            
            $this->_doc->save();

            if ($sender->id == 'savedoc') {
                $this->_doc->updateStatus($isEdited ? Document::STATE_EDITED : Document::STATE_NEW);
            }

                                         
            if ($sender->id == 'execdoc'  ) {
             //   $this->_doc->updateStatus(Document::STATE_NEW);
                
            }
         
            
         
            $conn->CommitTrans();
          

                          
            App::Redirect("\\App\\Modules\\DF\\Public\Main");
          



        } catch(\Throwable $ee) {
            global $logger;
            $conn->RollbackTrans();
            if ($isEdited == false) {
                $this->_doc->document_id = 0;
            }
            $this->setError($ee->getMessage());

            $logger->error('Line '. $ee->getLine().' '.$ee->getFile().'. '.$ee->getMessage()  );
            return;
        }
    }

    /**
     * Расчет  итого
     *
     */
    private function calcTotal() {

        $total = 0;
        $totalfrom = 0;


        foreach ($this->_tovarlist as $item) {
            $item->amount = H::fa($item->price * $item->quantity);

            $total = $total + $item->amount;
            $totalfrom = $totalfrom +   H::fa($item->pricefrom * $item->quantity);
        }
        $this->docform->total->setText(H::fa($total));
        $this->docform->totalfrom->setText(H::fa($totalfrom));

        $this->_doc->headerdata['totalfrom'] = $totalfrom;
 
    }

    /**
     * Валидация   формы
     *
     */
    private function checkForm() {
        if (strlen($this->_doc->document_number) == 0) {
            $this->setError('Введіть номер документа');
        }
        if (false == $this->_doc->checkUniqueNumber()) {
            $next = $this->_doc->nextNumber();
            $this->docform->document_number->setText($next);
            $this->_doc->document_number = $next;
            if (strlen($next) == 0) {
                $this->setError('Не створено унікальный номер документа');
            }
        }
        if (count($this->_tovarlist) == 0) {
            $this->setError("Не введено товар");
        }

       
      

       return !$this->isError();
    }
 
    public function OnChangeItem($sender) {
        $id = $sender->getKey();
        $item = Item::load($id);
        $modules = System::getOptions('modules');

        $pricefrom="";
     
        
        if($this->_tvars['isds']) {  //дропшипинг
             $pricefrom = $item->getPriceEx(array(
               'pricetype'=>$modules['dfpricetype'] ??'' 
               
             ));   
             if($modules['dfdiscprice'] > 0){ //процент
                $pricefrom = $pricefrom * (1 -  $modules['dfdiscprice']/100) ; 
             }   
        }
        if($this->_tvars['isff']) {   //фулфилмент
            
        }      
      
        $this->editdetail->pricefrom->setText(H::fa($pricefrom));
 
    }
   
    public function OnAutoItem($sender) {
        return Item::findArrayAC($sender->getText());
    }
   
    public function OnDelivery($sender) {
        $dt = $sender->getValue() ;
        if ($dt > 1) {
            $this->docform->address->setVisible(true);
        } else {
            $this->docform->address->setVisible(false);
        }

        $this->docform->deliverynp->setVisible($dt == Document::DEL_NP);

        $this->docform->baycity->setVisible($dt  == Document::DEL_NP ) ;
        $this->docform->baypoint->setVisible($dt == Document::DEL_NP ) ;
        $this->docform->bayhouse->setVisible($dt == Document::DEL_NP ) ;
        $this->docform->bayflat->setVisible($dt == Document::DEL_NP ) ;
        if ($dt == Document::DEL_NP) {
            $this->docform->deliverynp->setValue(0);
            $this->OnDeliverynp($this->docform->deliverynp) ;
        }

    }

    public function OnDeliverynp($sender) {
      $dt = $sender->getValue() ;        
      $this->docform->baypoint->setKey('') ;   
      $this->docform->baypoint->setText('') ;   

      $this->docform->baycity->setKey('');   
      $this->docform->baycity->setText('')  ;   

     
      $this->docform->address->setVisible($dt ==2) ;   
      $this->docform->bayhouse->setVisible($dt ==2) ;   
      $this->docform->bayflat->setVisible($dt ==2) ;     
      
    }

  
    public function onSelectItem($item_id, $itemname) {
        $this->editdetail->edittovar->setKey($item_id);
        $this->editdetail->edittovar->setText($itemname);
        $this->OnChangeItem($this->editdetail->edittovar);
    }

    public function onOpenItemSel($sender) {
           $this->witempan->setVisible(true); 
           $this->witempan->witemselt->Reload();            
             
    }
 
    public function ReloadData($sender) {
         $this->witempan->witemselt->Reload();
    }
      
    public function OnSelect($sender, $data) {
         
        $item = Item::load($data['dataitem']->item_id) ;
        $this->onSelectItem($item->item_id,$item->itemname);     
    } 

    public function onTextBayCity($sender) {
        $text = $sender->getText()  ;
        $api = new \App\Modules\NP\Helper();
        $list = $api->searchCity($text);

        if($list['success']!=true) return;
        $opt=[];  
        foreach($list['data'] as $d ) {
            foreach($d['Addresses'] as $c) {
               $opt[$c['Ref']]=$c['Present']; 
            }
        }
        
        return $opt;
       
    }

    public function onBayCity($sender) {
     
        $this->docform->baypoint->setKey('');
        $this->docform->baypoint->setText('');
    }
  
    public function onTextBayPoint($sender) {
        $text = $sender->getText()  ;
        $ref=  $this->docform->baycity->getKey();
        $api = new \App\Modules\NP\Helper();
        $list = $api->searchPoints($ref,$text);
       
        if($list['success']!=true) return;
        
        $opt=[];  
        foreach($list['data'] as $d ) {
           $opt[$d['WarehouseIndex']]=$d['Description']; 
        }
        
        return $opt;        
    }

    
}
class WISDataSource implements \Zippy\Interfaces\DataSource
{
    private $page;

    public function __construct($page) {
        $this->page = $page;
    }

    private function getWhere($p = false) {

        $where = "disabled <> 1";
              
        if($this->page->witempan->wisfilter->wissearchonstore->isChecked()) {
            $where = "   disabled <> 1 and  ( select coalesce(sum(st1.qty),0 ) from store_stock st1 where st1.item_id= items_view.item_id ) >0 ";
        
            $br = \App\ACL::getBranchConstraint();
            if (strlen($br) > 0) {
               $where .= " and  item_id in (select item_id from store_stock where  store_id in (select store_id from stores where {$br} ))  "; 
            }
        
        }
       


        $text = trim($this->page->witempan->wisfilter->wissearchkey->getText());
        $man = trim($this->page->witempan->wisfilter->wissearchmanufacturer->getText());
        $cat = $this->page->witempan->wisfilter->wissearchcat->getValue();

        if ($cat > 0) {
            $where = $where . " and cat_id=" . $cat;
        }

        if (strlen($text) > 0) {
            $det = Item::qstr('%' . "<cflist>%{$text}%</cflist>" . '%');

            $text = Item::qstr('%' . $text . '%');
            $where = $where . " and (itemname like {$text} or item_code like {$text} or bar_code like {$text}   or description like {$text}  or detail like {$det}  )  ";
        }
        if (strlen($man) > 0) {

            $man = Item::qstr($man);
            $where = $where . " and  manufacturer like {$man}      ";
        }


     
        return $where;
    }

    public function getItemCount() {
        return Item::findCnt($this->getWhere());
    }

    public function getItems($start, $count, $sortfield = null, $asc = null) {
        if($sortfield==null)  $sortfield='itemname';
        $list = array();
        
        $modules = System::getOptions('modules');
        
        
        foreach (Item::findYield($this->getWhere(), $sortfield, $count, $start) as $item) {

    
             $pricefrom="";
     
        
             if($this->page->_tvars['isds']??false) {  //дропшипинг
                 $pricefrom = $item->getPriceEx(array(
                   'pricetype'=>$modules['dfpricetype'] ??'' 
                   
                 ));   
                 if($modules['dfdiscprice'] > 0){ //процент
                    $pricefrom = $pricefrom * (1 -  $modules['dfdiscprice']/100) ; 
                 }   
            }  
            $item->price=$pricefrom;
            $list[] = $item;
        }
        return $list;
    }

    public function getItem($id) {
        return Item::load($id);
    }

}