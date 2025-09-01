<?php

namespace App\Pages\Doc;

use App\Application as App;
use App\Entity\Doc\Document;
use App\Entity\Item;
use App\Entity\Stock;
use App\Entity\Store;
use App\Helper as H;
use Zippy\Html\Form\AutocompleteTextInput;
use Zippy\Html\Form\Button;
use Zippy\Html\Form\Date;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Form\TextArea;
use Zippy\Html\Label;
use Zippy\Html\DataList\DataView;
use Zippy\Html\DataList\ArrayDataSource;
use Zippy\Html\Link\ClickLink;
use Zippy\Binding\PropertyBinding as Bind;

/**
 * Страница  ввода перекомплектация товаров
 */
class TransItem extends \App\Pages\Base
{
    public $_itemlist = array();
    private $_doc;
    private $_rowid     = -1;
    private $_fromtotal  = 0;
    private $_tototal  = 0;
  
 
    public $_fromlist  =  [];
    public $_tolist    =  [];

    /**
    * @param mixed $docid     редактирование
    */
    public function __construct($docid = 0) {
        parent::__construct();

        $this->add(new Form('docform'));
        $this->docform->add(new TextInput('document_number'));
        $this->docform->add(new Date('document_date', time()));

        $this->docform->add(new DropDownChoice('store', Store::getList(), H::getDefStore()));
        $this->docform->add(new DropDownChoice('tostore', Store::getList(), H::getDefStore()));
        $this->docform->add(new AutocompleteTextInput('fromitem'))->onText($this, 'OnAutocompleteItem');
        $this->docform->add(new AutocompleteTextInput('toitem'))->onText($this, 'OnAutocompleteItem');

        $this->docform->add(new TextInput('fromquantity'));
        $this->docform->add(new TextInput('toquantity'));
        $this->docform->add(new TextArea('notes'));

        $this->docform->add(new SubmitButton('savedoc'))->onClick($this, 'savedocOnClick');
        $this->docform->add(new SubmitButton('execdoc'))->onClick($this, 'savedocOnClick');
        $this->docform->add(new Button('backtolist'))->onClick($this, 'backtolistOnClick');

        $this->docform->add(new DataView('fromlist', new  ArrayDataSource(new Bind($this, '_fromlist')), $this, 'fromlistOnRow'));
        $this->docform->add(new DataView('tolist', new  ArrayDataSource(new Bind($this, '_tolist')), $this, 'tolistOnRow'));
        $this->docform->add(new Label('fromtotal' ));
        $this->docform->add(new Label('tototal' ));

        $this->docform->add(new SubmitButton('addto'))->onClick($this, 'addTo');
        $this->docform->add(new SubmitButton('addfrom'))->onClick($this, 'addFrom');
        $this->docform->add(new ClickLink('autoprice'))->onClick($this, 'onAutoPrice');
        
        
        if ($docid > 0) {    //загружаем   содержимое  документа на страницу
            $this->_doc = Document::load($docid)->cast();
            $this->docform->document_number->setText($this->_doc->document_number);
            $this->docform->document_date->setDate($this->_doc->document_date);
            $this->docform->store->setValue($this->_doc->headerdata['store']);
            $this->docform->tostore->setValue($this->_doc->headerdata['tostore']);
            $this->_fromlist = $this->_doc->unpackDetails('detaildata');
            $this->_tolist = $this->_doc->unpackDetails('detaildata2');
           
            $this->docform->notes->setText($this->_doc->notes);
        } else {
            $this->_doc = Document::create('TransItem');
            $this->docform->document_number->setText($this->_doc->nextNumber());
        }

        
     
        if (false == \App\ACL::checkShowDoc($this->_doc)) {
            return;
        }
        
        $this->Reload() ;
    }

    public function backtolistOnClick($sender) {
        App::RedirectBack();
    }

    public function Reload( ) {
        $this->_fromtotal=0;
        $this->_tototal=0;
       
        $this->docform->fromlist->Reload();
        $this->docform->tolist->Reload();
        
        $this->docform->fromtotal->setText($this->_fromtotal);
        $this->docform->tototal->setText($this->_tototal);
             
    }

    
    public function fromlistOnRow( $row) {
        $it=$row->getDataItem();
        $row->add(new Label('fromname',$it->itemname))  ;
        $row->add(new Label('fromcode',$it->item_code))  ;
        $row->add(new Label('fromqty', H::fqty($it->qty)))  ;
        $row->add(new Label('fromprice', H::fa($it->partion))  ) ;
        $row->add(new ClickLink('fromdel', $this,'deleteFrom')  ) ;
        
        $this->_fromtotal += (H::fa($it->partion * $it->qty ));
    }    
        
    public function tolistOnRow( $row) {
        $it=$row->getDataItem();
        $row->add(new Label('toname',$it->itemname))  ;
        $row->add(new Label('tocode',$it->item_code))  ;
        $row->add(new Label('toqty', H::fqty($it->qty)) ) ;
        $row->add(new TextInput('toprice', new  Bind($it,'price') ) )->onChange($this,'onToPrice',true )  ;
        $row->add(new ClickLink('todel', $this,'deleteTo')  ) ;
        
        $this->_tototal += (H::fa($it->price * $it->qty ));
          
    }    
    
    public function addFrom( $sender) {
        $fi=intval( $this->docform->fromitem->getKey() );
        $fqty=doubleval( $this->docform->fromquantity->getText());
        if ($fi == 0 ) {
            $this->setError("  Не вибрано ТМЦ ");
            return;
        } 
        $st = Stock::load($fi) ;
        if ($fqty > $st->qty ) {
            $this->setError(" Недостатньо ТМЦ на складі");
            return;
        }                  
        if ($fqty == 0 ) {
            $this->setError(" Не вказана  кількість ");
            return;
        }   
        $st->qty= $fqty;
         $this->_fromlist[$st->stock_id]  = $st; 
        
        $this->docform->fromitem->setKey(0)  ;   
        $this->docform->fromitem->setText('')  ;   
        $this->docform->fromquantity->setText('')  ;   
                     
        $this->Reload() ;      
    }    
  
    public function addTo( $sender) {
        $fi=intval( $this->docform->toitem->getKey() );
        $fqty=doubleval( $this->docform->toquantity->getText());
        if ($fi == 0 ) {
            $this->setError("  Не вибрано ТМЦ ");
            return;
        } 
                     
        if ($fqty == 0 ) {
            $this->setError(" Не вказана  кількість ");
            return;
        }   
        $it = Item::load($fi) ;
          
        $it->qty= $fqty;
        $it->price = 0;
        
        if(count($this->_tolist)==0  && $it->qty >0) {  //для  одной позиции
            $from = doubleval( $this->docform->fromtotal->getText() );
            $it->price = H::fa($from/$it->qty);
        }
     
        
        $this->_tolist[$it->item_id]  = $it; 
        
        $this->docform->toitem->setKey(0)  ;   
        $this->docform->toitem->setText('')  ;   
        $this->docform->toquantity->setText('')  ;   
                     
        $this->Reload() ;       
    }    
    
    public function deleteFrom( $sender) {
        $it=$sender->getOwner()->getDataItem();
        unset($this->_fromlist[$it->stock_id] ) ;
      
        $this->Reload() ;      
      
    }    
    public function deleteTo( $sender) {
      
        $it=$sender->getOwner()->getDataItem();
        unset($this->_tolist[$it->item_id] ) ;
        
      
        $this->Reload() ;      
     
    }    
    
   public function onAutoPrice( $sender) {
      
        $tmp=[];
       
        foreach($this->_tolist as $i=>$it)  {  
            $it->price = $it->getLastPartion();
            $tmp[$i]= $it;
        }
        $this->_tolist  = $tmp; 
        
        $this->Reload() ;      
     
    }    
    
    
    public function savedocOnClick($sender) {
        if (false == \App\ACL::checkEditDoc($this->_doc)) {
            return;
        }

        $this->_doc->notes = $this->docform->notes->getText();

        $this->_doc->headerdata['store'] = $this->docform->store->getValue();
        $this->_doc->headerdata['tostore'] = $this->docform->tostore->getValue();
        $this->_doc->headerdata['fromamount'] = $this->_fromtotal;
        $this->_doc->headerdata['toamount'] = $this->_tototal;
    
        if ($this->checkForm() == false) {
            return;
        }


        $this->_doc->packDetails('detaildata', $this->_fromlist);
        $this->_doc->packDetails('detaildata2', $this->_tolist);
   
        $this->_doc->amount = H::fa($this->_tototal);
        $this->_doc->document_number = $this->docform->document_number->getText();
        $this->_doc->document_date = strtotime($this->docform->document_date->getText());
        $isEdited = $this->_doc->document_id > 0;

        $conn = \ZDB\DB::getConnect();
        $conn->BeginTrans();
        try {

            $this->_doc->save();
            if ($sender->id == 'execdoc') {
                if (!$isEdited) {
                    $this->_doc->updateStatus(Document::STATE_NEW);
                }
                $this->_doc->updateStatus(Document::STATE_EXECUTED);
            } else {
                $this->_doc->updateStatus($isEdited ? Document::STATE_EDITED : Document::STATE_NEW);
            }
            $conn->CommitTrans();
            App::Redirect("\\App\\Pages\\Register\\StockList");

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
     * Валидация   формы
     *
     */
    private function checkForm() {

        if (strlen(trim($this->docform->document_number->getText())) == 0) {
            $this->setError("Введіть номер документа");
        }
        if (false == $this->_doc->checkUniqueNumber()) {
            $next = $this->_doc->nextNumber();
            $this->docform->document_number->setText($next);
            $this->_doc->document_number = $next;
            if (strlen($next) == 0) {
                $this->setError('Не створено унікальный номер документа');
            }
        }
        
        if (count( $this->_fromlist)==0 || count( $this->_tolist)==0  ) {
            $this->setError(" Не введено ТМЦ ");
        }        
        
        foreach($this->_tolist as $it){
           if( doubleval($it->price)==0  ) {
                $this->setError(" Не введена  ціна   ");
                break;
           }
        }
        
        
        
        return !$this->isError();
    }


    public function onToPrice($sender) {
        $sum=0;        
        foreach( $this->_tolist as $item) {
           $sum +=  ( doubleval($item->qty)*doubleval($item->price)); 
        }
        $this->docform->tototal->setText($sum);
         
    }
    
    public function OnAutocompleteItem($sender) {
        $store_id = $this->docform->store->getValue();
        $text = trim($sender->getText());
        if ($sender->id == 'fromitem') {
            return Stock::findArrayAC($store_id, $text);
        } else {
            $text = Item::qstr('%' . $sender->getText() . '%');
            return Item::findArray("concat(itemname,', ',item_code)", "(itemname like {$text} or item_code like {$text})  and disabled <> 1");
        }
    }

}
