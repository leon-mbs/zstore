<?php

namespace App\Pages\Reference;

use App\Entity\Category;
use App\Entity\Item;
use App\Entity\ItemSet;
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
use Zippy\Binding\PropertyBinding as Bind;

class ItemList extends \App\Pages\Base
{
    private $_item;
    private $_copy     = 0;
    private $_pitem_id = 0;
    public $_itemset  = array();
    public $_serviceset  = array();
    public $_tag = '' ; 
    public $_cflist = array();
    public $_cflistv = array();
    public $_itemca = array();
  
    public function __construct($add = false) {
        parent::__construct();
        if (false == \App\ACL::checkShowRef('ItemList')) {
            return;
        }

  
        
        $this->add(new Form('filter'))->onSubmit($this, 'OnFilter');

        $this->filter->add(new TextInput('searchbrand'));
        $this->filter->searchbrand->setDataList(Item::getManufacturers());


        $this->filter->add(new TextInput('searchkey'));
        $catlist = array();
        $catlist[-1] = "Без категорії";
        foreach (Category::getList() as $k => $v) {
            $catlist[$k] = $v;
        }
        $this->filter->add(new DropDownChoice('searchcat', $catlist, 0));
        $this->filter->add(new DropDownChoice('searchsort', array(), 0));
        $this->filter->add(new DropDownChoice('searchtype', array( ), 0));

        $this->add(new Panel('itemtable'))->setVisible(true);
        $this->itemtable->add(new ClickLink('addnew'))->onClick($this, 'addOnClick');
        $this->itemtable->add(new ClickLink('options'))->onClick($this, 'optionsOnClick');

        $this->itemtable->add(new Form('listform'));

        $this->itemtable->listform->add(new DataView('itemlist', new ItemDataSource($this), $this, 'itemlistOnRow'));
        $this->itemtable->listform->itemlist->setPageSize(H::getPG());
        $this->itemtable->listform->add(new \Zippy\Html\DataList\Pager('pag', $this->itemtable->listform->itemlist));
        $this->itemtable->listform->itemlist->setSelectedClass('table-success');
        $this->itemtable->listform->add(new SubmitLink('deleteall'))->onClick($this, 'OnDelAll');
        $this->itemtable->listform->add(new SubmitLink('printall'))->onClick($this, 'OnPrintAll', true);
        $this->itemtable->listform->add(new SubmitLink('priceall'))->onClick($this, 'OnPriceAll' );

        $catlist = Category::findArray("cat_name", "childcnt = 0", "cat_name");


        $this->itemtable->listform->add(new DropDownChoice('allcat', $catlist, 0))->onChange($this, 'onAllCat');
        $this->itemtable->add(new \Zippy\Html\Link\LinkList("taglist"))->onClick($this, 'OnTagList');        
       
        $this->add(new Form('itemdetail'))->setVisible(false);
        $this->itemdetail->add(new TextInput('editname'));
        $this->itemdetail->add(new TextInput('editshortname'));
        $this->itemdetail->add(new TextInput('editprice1'));
        $this->itemdetail->add(new TextInput('editprice2'));
        $this->itemdetail->add(new TextInput('editprice3'));
        $this->itemdetail->add(new TextInput('editprice4'));
        $this->itemdetail->add(new TextInput('editprice5'));
        $this->itemdetail->add(new TextInput('editmanufacturer'));
        $this->itemdetail->add(new TextInput('editcountry'));
        $this->itemdetail->add(new TextInput('editurl'));
      
        $common = System::getOptions('common');
        if (strlen($common['price1']) > 0) {
            $this->itemdetail->editprice1->setVisible(true);
            $this->itemdetail->editprice1->setAttribute('placeholder', $common['price1']);
        } else {
            $this->itemdetail->editprice1->setVisible(false);
        }
        if (strlen($common['price2']) > 0) {
            $this->itemdetail->editprice2->setVisible(true);
            $this->itemdetail->editprice2->setAttribute('placeholder', $common['price2']);
        } else {
            $this->itemdetail->editprice2->setVisible(false);
        }
        if (strlen($common['price3']) > 0) {
            $this->itemdetail->editprice3->setVisible(true);
            $this->itemdetail->editprice3->setAttribute('placeholder', $common['price3']);
        } else {
            $this->itemdetail->editprice3->setVisible(false);
        }
        if (strlen($common['price4']) > 0) {
            $this->itemdetail->editprice4->setVisible(true);
            $this->itemdetail->editprice4->setAttribute('placeholder', $common['price4']);
        } else {
            $this->itemdetail->editprice4->setVisible(false);
        }
        if (strlen($common['price5']) > 0) {
            $this->itemdetail->editprice5->setVisible(true);
            $this->itemdetail->editprice5->setAttribute('placeholder', $common['price5']);
        } else {
            $this->itemdetail->editprice5->setVisible(false);
        }
        
             
        $this->_tvars['usecf'] = count($common['cflist']??[]) >0;
             
        
        $this->itemdetail->add(new TextInput('editbarcode'));
        $this->itemdetail->add(new TextInput('editbarcode1'));
        $this->itemdetail->add(new TextInput('editbarcode2'));
        $this->itemdetail->add(new TextInput('editminqty'));
        $this->itemdetail->add(new TextInput('editzarp'));
        $this->itemdetail->add(new TextInput('editcostprice'));
        $this->itemdetail->add(new TextInput('editweight'));
        $this->itemdetail->add(new TextInput('editmaxsize'));
        $this->itemdetail->add(new TextInput('editvolume'));
        $this->itemdetail->add(new TextInput('editcustomsize'));
        $this->itemdetail->add(new TextInput('editwarranty'));
        $this->itemdetail->add(new TextInput('editlost'));
        $this->itemdetail->add(new TextInput('editimageurl'));

        $this->itemdetail->add(new TextInput('editcell'));
        $this->itemdetail->add(new TextInput('edituktz'));
        $this->itemdetail->add(new TextInput('editmsr'));
        $this->itemdetail->add(new TextInput('editnotes'));

        $this->itemdetail->add(new DropDownChoice('editcat', Category::findArray("cat_name", "childcnt=0", "cat_name"), 0))->onChange($this,"onCat");
        $this->itemdetail->add(new TextInput('editcode'));
        $this->itemdetail->add(new TextArea('editdescription'));
        $this->itemdetail->add(new CheckBox('editdisabled'));
        $this->itemdetail->add(new CheckBox('edituseserial'));
        $this->itemdetail->add(new CheckBox('editnoprice'));
        $this->itemdetail->add(new CheckBox('editisweight'));
        $this->itemdetail->add(new CheckBox('editnoshop'));
        $this->itemdetail->add(new CheckBox('editautooutcome'));
        $this->itemdetail->add(new CheckBox('editautoincome'));
        $this->itemdetail->add(new \Zippy\Html\Image('editimage' ));
        $this->itemdetail->add(new \Zippy\Html\Form\File('editaddfile'));
        $this->itemdetail->add(new CheckBox('editdelimage'));
        $this->itemdetail->add(new DropDownChoice('edittype', Item::getTypes(),Item::TYPE_TOVAR));
        $this->itemdetail->add(new DropDownChoice('editprintqty', array(), 1));
        $this->itemdetail->add(new DropDownChoice('editisnds',[],0))->onChange($this, 'onNds');;
        $this->itemdetail->add(new TextInput('editnds'))->setVisible(false);
  

        $this->itemdetail->add(new SubmitButton('save'))->onClick($this, 'save');
        $this->itemdetail->add(new Button('cancel'))->onClick($this, 'cancelOnClick');
        $this->itemdetail->add(new \ZCL\BT\Tags("edittags"));
        $this->itemdetail->add(new DataView('cflistv', new ArrayDataSource(new Bind($this, '_cflistv')), $this, 'cfvOnRow'));

        $this->add(new Form('changeall'))->setVisible(false);
        $this->changeall->add(new SubmitButton('saveca'))->onClick($this, 'saveall');
        $this->changeall->add(new Button('cancelca'))->onClick($this, 'cancelOnClick');
        $this->changeall->add(new DataView('itemcalist', new ArrayDataSource(new Bind($this, '_itemca')), $this, 'caOnRow'));

        $this->add(new Panel('setpanel'))->setVisible(false);
        $this->setpanel->add(new DataView('setlist', new ArrayDataSource($this, '_itemset'), $this, 'itemsetlistOnRow'));
        $this->setpanel->add(new Form('setform')) ;
        $this->setpanel->setform->add(new AutocompleteTextInput('editsname'))->onText($this, 'OnAutoSet');
        $this->setpanel->setform->add(new TextInput('editsqty', 1));
        $this->setpanel->setform->add(new SubmitButton('setformbtn'))->onClick($this, 'OnAddSet');

        $this->setpanel->add(new DataView('ssetlist', new ArrayDataSource($this, '_serviceset'), $this, 'itemsetslistOnRow'));
        $this->setpanel->add(new Form('setsform')) ;
        $this->setpanel->setsform->add(new DropDownChoice('editssname', \App\Entity\Service::findArray("service_name", "disabled<>1", "service_name")))->onChange($this,'onService',true);
        $this->setpanel->setsform->add(new TextInput('editscost'));
        $this->setpanel->setsform->add(new SubmitButton('setsformbtn'))->onClick($this, 'OnAddSSet');

        $this->setpanel->add(new Form('cardform'))->onSubmit($this, 'OnCardSet');
        $this->setpanel->cardform->add(new TextArea('editscard'));

        $this->setpanel->add(new Label('stitle'));
        $this->setpanel->add(new Label('stotal'));
        $this->setpanel->add(new ClickLink('backtolist', $this, "onback"));

        
        $this->add(new Form('optionsform'))->setVisible(false);        
        $this->optionsform->add(new SubmitButton('savec'))->onClick($this, 'saveopt');
        $this->optionsform->add(new Button('cancelc'))->onClick($this, 'cancelOnClick');
        $this->optionsform->add(new DataView('cflist', new ArrayDataSource(new Bind($this, '_cflist')), $this, 'cfOnRow'));
        $this->optionsform->add(new SubmitLink('addnewcf'))->onClick($this, 'OnAddCF');
        $this->optionsform->add(new CheckBox('autoarticle'));
        $this->optionsform->add(new CheckBox('nocheckarticle'));
        $this->optionsform->add(new CheckBox('allowchange'));
        $this->optionsform->add(new CheckBox('usecattree'));
        $this->optionsform->add(new CheckBox('useimages'));
        $this->optionsform->add(new CheckBox('branchprice'));
        $this->optionsform->add(new TextInput('articleprefix'));
          
     
        $this->_tvars['hp1'] = strlen($common['price1']) > 0 ? $common['price1'] : false;
        $this->_tvars['hp2'] = strlen($common['price2']) > 0 ? $common['price2'] : false;
        $this->_tvars['hp3'] = strlen($common['price3']) > 0 ? $common['price3'] : false;
        $this->_tvars['hp4'] = strlen($common['price4']) > 0 ? $common['price4'] : false;
        $this->_tvars['hp5'] = strlen($common['price5']) > 0 ? $common['price5'] : false;

        if ($add == false) {
           $this->Reload() ;
        } else {
            $this->addOnClick(null);
        }
        
    }

    public function itemlistOnRow(\Zippy\Html\DataList\DataRow $row) {
        $item = $row->getDataItem();
        $row->setAttribute('style', $item->disabled == 1 ? 'color: #aaa' : null);


        $row->add(new ClickLink('itemname', $this, 'editOnClick'))->setValue($item->itemname);

        $row->add(new Label('code', $item->item_code));
        $row->add(new Label('msr', $item->msr));
        $row->add(new Label('cat_name', $item->cat_name));
        $row->add(new Label('manufacturer', $item->manufacturer));

        $row->add(new Label('price1', $item->price1));
        $row->add(new Label('price2', $item->price2));
        $row->add(new Label('price3', $item->price3));
        $row->add(new Label('price4', $item->price4));
        $row->add(new Label('price5', $item->price5));

        $row->setAttribute('style', $item->disabled == 1 ? 'color: #aaa' : null);

        $row->add(new Label('onstore'))->setVisible($item->qty > 0);
        $row->add(new Label('cell', $item->cell));
        $row->add(new Label('inseria'))->setVisible($item->useserial);
        $row->add(new Label('inprice'))->setVisible($item->noprice!=1);
      
        $row->add(new Label('hasaction'))->setVisible($item->hasAction());
        if($item->hasAction()) {
            $title="";
            if(doubleval($item->actionprice) > 0) {
                $title= "Акційна ціна " . H::fa($item->actionprice);
            }
            if(doubleval($item->actiondisc) > 0) {
                $title= "Акційна знижка ". H::fa($item->actiondisc) ."%";
            }
            $row->hasaction->setAttribute('title', $title)  ;
        }
        $row->add(new ClickLink('shownotes'))->onClick($this, 'shownotesOnClick',true);
        $row->shownotes->setVisible(strlen($item->description ?? '') > 0);
        
        

        $row->add(new ClickLink('copy'))->onClick($this, 'copyOnClick');
        $row->add(new ClickLink('edit'))->onClick($this, 'editOnClick');

        $row->add(new ClickLink('set'))->onClick($this, 'setOnClick');
        $row->set->setVisible($item->item_type == Item::TYPE_PROD || $item->item_type == Item::TYPE_HALFPROD || $item->item_type == Item::TYPE_MAT );

        $row->add(new ClickLink('printqr'))->onClick($this, 'printQrOnClick', true);
        $row->printqr->setVisible(strlen($item->url ?? '') > 0);
        $row->add(new ClickLink('printst'))->onClick($this, 'printStOnClick', true);
        $row->printst->setVisible($item->isweight ==1 );


        $url=$item->getImageUrl();
    
        $row->add(new \Zippy\Html\Link\BookmarkableLink('imagelistitem'))->setValue($url);
        $row->imagelistitem->setAttribute('href', $url);
        $row->imagelistitem->setAttribute('data-gallery', $item->item_id);
        
        if (  strlen($url)==0) {
            $row->imagelistitem->setVisible(false);
        }

        $row->add(new CheckBox('seldel', new \Zippy\Binding\PropertyBinding($item, 'seldel')));
        $row->add(new Label('cfval'))->setText("") ;
        if($this->_tvars['usecf'] ?? false) {
           $cf="";
           foreach($item->getcf() as $f){
               if( strlen($f->val??'')>0){
                    $cf=$cf. "<small style=\"display:block\">". $f->name.": ".$f->val."</small>" ; 
               }
           }
           if(strlen($cf) >0) {
               $row->cfval->setText($cf,true) ;
           }
        }
         

    }

    public function copyOnClick($sender) {
        $this->editOnClick($sender);
        $this->_copy = $this->_item->item_id;
        $this->_item->item_id = 0;
        $this->_item->extdata = "";
        $this->itemdetail->editname->setText($this->_item->itemname.'_copy');

        $this->itemdetail->editcode->setText(Item::getNextArticle());
        $this->itemdetail->editbarcode->setText('');
        $this->itemdetail->editbarcode1->setText('');
        $this->itemdetail->editbarcode2->setText('');
        

    }

    public function editOnClick($sender) {
        $this->_copy = 0;
        $item = $sender->owner->getDataItem();
        $this->_item = Item::load($item->item_id);

        $this->itemtable->setVisible(false);
        $this->itemdetail->setVisible(true);

        $this->itemdetail->editname->setText($this->_item->itemname);
        $this->itemdetail->editshortname->setText($this->_item->shortname);
        $this->itemdetail->editprice1->setText($this->_item->price1);
        $this->itemdetail->editprice2->setText($this->_item->price2);
        $this->itemdetail->editprice3->setText($this->_item->price3);
        $this->itemdetail->editprice4->setText($this->_item->price4);
        $this->itemdetail->editprice5->setText($this->_item->price5);
        $this->itemdetail->editcat->setValue($this->_item->cat_id);

        $this->itemdetail->editmanufacturer->setText($this->_item->manufacturer);
        $this->itemdetail->editcountry->setText($this->_item->country);
        $this->itemdetail->editmanufacturer->setDataList(Item::getManufacturers());
        $this->itemdetail->editisnds->setValue($this->_item->isnds ??0);
        $this->itemdetail->editnds->setText($this->_item->nds);
 

        $this->itemdetail->editdescription->setText($this->_item->description);
        $this->itemdetail->editcode->setText($this->_item->item_code);
        $this->itemdetail->editbarcode->setText($this->_item->bar_code);
        $this->itemdetail->editbarcode1->setText($this->_item->bar_code1);
        $this->itemdetail->editbarcode2->setText($this->_item->bar_code2);
        $this->itemdetail->editmsr->setText($this->_item->msr);
        $this->itemdetail->editnotes->setText($this->_item->notes);
        $this->itemdetail->editmaxsize->setText($this->_item->maxsize);
        $this->itemdetail->editvolume->setText($this->_item->volume);
        $this->itemdetail->editlost->setText($this->_item->lost);
        $this->itemdetail->editcustomsize->setText($this->_item->customsize);
        $this->itemdetail->editwarranty->setText($this->_item->warranty);
        $this->itemdetail->edittype->setValue($this->_item->item_type);
        $this->itemdetail->editprintqty->setValue($this->_item->printqty);

        $this->itemdetail->editimageurl->setText($this->_item->imageurl);
        $this->itemdetail->editurl->setText($this->_item->url);
        $this->itemdetail->editweight->setText($this->_item->weight);
        $this->itemdetail->editcell->setText($this->_item->cell);
        $this->itemdetail->edituktz->setText($this->_item->uktz);
        $this->itemdetail->editminqty->setText(\App\Helper::fqty($this->_item->minqty));
        $this->itemdetail->editzarp->setText(\App\Helper::fa($this->_item->zarp));
        $this->itemdetail->editcostprice->setText(\App\Helper::fa($this->_item->costprice));
        $this->itemdetail->editdisabled->setChecked($this->_item->disabled);
        $this->itemdetail->edituseserial->setChecked($this->_item->useserial);
        $this->itemdetail->editnoshop->setChecked($this->_item->noshop);
        $this->itemdetail->editnoprice->setChecked($this->_item->noprice);
        $this->itemdetail->editisweight->setChecked($this->_item->isweight);
        $this->itemdetail->editautooutcome->setChecked($this->_item->autooutcome);
        $this->itemdetail->editautoincome->setChecked($this->_item->autoincome);
        if ($this->_item->image_id > 0) {
            $this->itemdetail->editdelimage->setChecked(false);
            $this->itemdetail->editdelimage->setVisible(true);
            $this->itemdetail->editimage->setVisible(true);
            $this->itemdetail->editimage->setUrl(  $this->_item->getImageUrl());
        } else {
            $this->itemdetail->editdelimage->setVisible(false);
            $this->itemdetail->editimage->setVisible(false);
        }

        $this->itemtable->listform->itemlist->setSelectedRow($sender->getOwner());
        $this->itemtable->listform->itemlist->Reload(false);

        $this->filter->searchbrand->setDataList(Item::getManufacturers());
        if (strlen($this->_item->item_code)==0  ) {
            $this->itemdetail->editcode->setText(Item::getNextArticle());
        }
        
        $this->itemdetail->edittags->setTags(\App\Entity\Tag::getTags(\App\Entity\Tag::TYPE_ITEM,(int)$this->_item->item_id));
        $this->itemdetail->edittags->setSuggestions(\App\Entity\Tag::getSuggestions(\App\Entity\Tag::TYPE_ITEM));
         
        $this->onCat($this->itemdetail->editcat);                 
    }

    public function onCat($sender) {
        $id = $sender->getValue();
        $this->_item->cat_id= $id;//подставляем выбраную
        $this->_cflistv =  $this->_item->getcf();
        
        $this->itemdetail->cflistv->Reload(); 
        $this->_tvars['cflist'] = count($this->_cflistv) > 0 ;
   
    }

    public function addOnClick($sender) {
        $this->_copy = 0;
        $this->itemtable->setVisible(false);
        $this->itemdetail->setVisible(true);
        // Очищаем  форму
        $this->itemdetail->clean();
        $this->itemdetail->editmsr->setText('шт');
        $this->itemdetail->editnotes->setText('');
        $this->itemdetail->editimage->setVisible(false);
        $this->itemdetail->editdelimage->setVisible(false);
        $this->itemdetail->editnoprice->setChecked(false);
        $this->itemdetail->editisweight->setChecked(false);
        $this->itemdetail->editnoshop->setChecked(false);
        $this->itemdetail->editautooutcome->setChecked(false);
        $this->itemdetail->editautoincome->setChecked(false);
        $this->_item = new Item();

        $this->itemdetail->editcode->setText(Item::getNextArticle());
        $this->itemdetail->editmanufacturer->setDataList(Item::getManufacturers());

    }

    public function cancelOnClick($sender) {
        $this->itemtable->setVisible(true);
        $this->itemdetail->setVisible(false);
        $this->changeall->setVisible(false);
        $this->optionsform->setVisible(false);
        
    }

    public function OnFilter($sender) {
        $this->_tag ='';  
        $this->Reload()  ;
     }

    public function Reload($f=true) {
          $this->itemtable->listform->itemlist->Reload($f);
        
       
          $this->itemtable->taglist->Clear();
          $tags = \App\Entity\Tag::getTags(\App\Entity\Tag::TYPE_ITEM ) ;
          foreach ($tags as $tag) {
             $this->itemtable->taglist->addClickLink($tag, '#'.$tag);
          }           
          
          
    }    
    

    public function save($sender) {
        if (false == \App\ACL::checkEditRef('ItemList')) {
            return;
        }
        $options = System::getOptions('common');
        
        $this->_item->itemname = $this->itemdetail->editname->getText();
        $this->_item->itemname = trim($this->_item->itemname);

        if (strlen($this->_item->itemname) == 0) {
            $this->setError('Не введено назву');
            return;
        }

        $itemcode =trim($this->itemdetail->editcode->getText());
        
        if($options['allowchange'] != 1) {
            //проверка  на использование
            if (strlen($this->_item->item_code) > 0 &&  $itemcode !== $this->_item->item_code ) {
                $code = Item::qstr($this->_item->item_code);
                $cnt =  \App\Entity\Entry::findCnt("item_id = {$this->_item->item_id}  ");
                if ($cnt > 0) {
                    $this->setError('Не можна міняти  артикул вже використовуваного  ТМЦ');
                    return;
                }
            }        
        }
        
        $this->_item->shortname = $this->itemdetail->editshortname->getText();
        $this->_item->cat_id = $this->itemdetail->editcat->getValue();
        $this->_item->price1 = $this->itemdetail->editprice1->getText();
        $this->_item->price2 = $this->itemdetail->editprice2->getText();
        $this->_item->price3 = $this->itemdetail->editprice3->getText();
        $this->_item->price4 = $this->itemdetail->editprice4->getText();
        $this->_item->price5 = $this->itemdetail->editprice5->getText();

        $this->_item->item_code = $itemcode;
        $this->_item->manufacturer = trim($this->itemdetail->editmanufacturer->getText());
        $this->_item->country = trim($this->itemdetail->editcountry->getText());

        $this->_item->bar_code = trim($this->itemdetail->editbarcode->getText());
        $this->_item->bar_code1 = trim($this->itemdetail->editbarcode1->getText());
        $this->_item->bar_code2 = trim($this->itemdetail->editbarcode2->getText());
        $this->_item->url = trim($this->itemdetail->editurl->getText());
        $this->_item->msr = $this->itemdetail->editmsr->getText();
        $this->_item->notes = $this->itemdetail->editnotes->getText();
        $this->_item->weight = $this->itemdetail->editweight->getText();
        $this->_item->maxsize = $this->itemdetail->editmaxsize->getText();
        $this->_item->volume = $this->itemdetail->editvolume->getText();
        $this->_item->lost = $this->itemdetail->editlost->getText();
        $this->_item->customsize = $this->itemdetail->editcustomsize->getText();
        $this->_item->warranty = $this->itemdetail->editwarranty->getText();
        $this->_item->item_type = $this->itemdetail->edittype->getValue();
        $this->_item->printqty = $this->itemdetail->editprintqty->getValue();

        $this->_item->imageurl = $this->itemdetail->editimageurl->getText();
        $this->_item->cell = $this->itemdetail->editcell->getText();
        $this->_item->uktz = $this->itemdetail->edituktz->getText();
        $this->_item->minqty = $this->itemdetail->editminqty->getText();
        $this->_item->zarp = $this->itemdetail->editzarp->getText();
        $this->_item->costprice = $this->itemdetail->editcostprice->getText();
        $this->_item->description = $this->itemdetail->editdescription->getText();
        $this->_item->disabled = $this->itemdetail->editdisabled->isChecked() ? 1 : 0;
        $this->_item->useserial = $this->itemdetail->edituseserial->isChecked() ? 1 : 0;
        $this->_item->nds = $this->itemdetail->editnds->getText();
        $this->_item->isnds = $this->itemdetail->editisnds->getValue();

        $this->_item->isweight = $this->itemdetail->editisweight->isChecked() ? 1 : 0;
        $this->_item->noprice = $this->itemdetail->editnoprice->isChecked() ? 1 : 0;
        $this->_item->noshop = $this->itemdetail->editnoshop->isChecked() ? 1 : 0;
        $this->_item->autooutcome = $this->itemdetail->editautooutcome->isChecked() ? 1 : 0;
        $this->_item->autoincome = $this->itemdetail->editautoincome->isChecked() ? 1 : 0;

        //проверка  уникальности артикула
        if ($this->_item->checkUniqueArticle()==false) {
            $this->setError('Такий артикул вже існує');
            return;
        }


        if (strlen($this->_item->item_code) == 0  ) {
            $this->_item->item_code = Item::getNextArticle();
        }
   
        
        if (\App\System::getOption("common", "autoarticle") == 1) {
            $digits = intval( preg_replace('/[^0-9]/', '', $this->_item->item_code) );
             
            if (strlen($digits) > ( strlen(''.PHP_INT_MAX)-1)  ) {    
                $this->setError('Надто велике  число в артикулі');
                return;
            }
        }          
        
  
        //проверка  уникальности штрих кода
        if (strlen($this->_item->bar_code) > 0) {
            $code = Item::qstr($this->_item->bar_code);
            $cnt = Item::findCnt("item_id <> {$this->_item->item_id} and bar_code={$code} ");
            if ($cnt > 0) {
                $this->setWarn('Такий штрих код вже існує"');
            }
        }
        $printer = System::getOptions('printer');

        if (intval($printer['pmaxname']) > 0 && mb_strlen($this->_item->shortname) > intval($printer['pmaxname'])) {

            $this->setWarn("Коротка назва має бути не більше {$printer['pmaxname']} символів");

        }


        $itemname = Item::qstr($this->_item->itemname);
        $code = Item::qstr($this->_item->item_code);
        $cnt = Item::findCnt("item_id <> {$this->_item->item_id} and itemname={$itemname} and item_code={$code} ");
        if ($cnt > 0) {
            $this->setError('ТМЦ з такою назвою і артикулом вже існує');
            return;
        }

        //delete image
        if ($this->itemdetail->editdelimage->isChecked()) {
            if ($this->_item->image_id > 0) {
                \App\Entity\Image::delete($this->_item->image_id);
            }
            $this->_item->image_id = 0;
            $this->_item->thumb = "";
        }

        if ($this->_item->image_id > 0 && $this->_copy >0) {
            $image = \App\Entity\Image::load($this->_item->image_id);
            $image->image_id = 0;
            $image->save();
            $this->_item->image_id = $image->image_id;
            $this->_item->thumb="";
        }

        $v=[];
        foreach($this->_cflistv as $r) {
             if(strlen($r->val)>0) {
                 $v[$r->code]=$r->val;
             }
        }
        $this->_item->savecf($v);        
        
        $this->_item->save();

        $file = $this->itemdetail->editaddfile->getFile();
        if (strlen($file["tmp_name"] ??'') > 0) {
            
            if (filesize($file["tmp_name"])  > 1024*1024*4) {

                    $this->setError('Розмір файлу більше 4M');
                    return;
            }
           
            $imagedata = getimagesize($file["tmp_name"]);
 
            if (preg_match('/(png|jpeg)$/', $imagedata['mime']) == 0) {
                $this->setError('Невірний формат зображення');
                return;
            }


            $image = new \App\Entity\Image();
            $image->content = file_get_contents($file['tmp_name']);
            $image->mime = $imagedata['mime'];

            if ($imagedata[0] != $imagedata[1]) {
                $thumb = new \App\Thumb($file['tmp_name']);
                if ($imagedata[0] > $imagedata[1]) {
                    $thumb->cropFromCenter($imagedata[1], $imagedata[1]);
                }
                if ($imagedata[0] < $imagedata[1]) {
                    $thumb->cropFromCenter($imagedata[0], $imagedata[0]);
                }


                $image->content = $thumb->getImageAsString();
                $thumb->resize(512, 512);
                $image->thumb = $thumb->getImageAsString();
                $thumb->resize(128, 128);

                $this->_item->thumb = "data:{$image->mime};base64," . base64_encode($thumb->getImageAsString());
            }
        
          

            $image->save();
            $this->_item->image_id = $image->image_id;
            $this->_item->save();


        }

        $this->filter->searchbrand->setDataList(Item::getManufacturers());

        $tags = $this->itemdetail->edittags->getTags() ;
        \App\Entity\Tag::updateTags($tags,\App\Entity\Tag::TYPE_ITEM,(int)$this->_item->item_id) ;
        
        
        if($this->_copy > 0) {  //комплекты
            $itemset = ItemSet::find("item_id > 0  and pitem_id=" . $this->_copy, "itemname");
            $serviceset = ItemSet::find("service_id > 0  and pitem_id=" . $this->_copy, "service_name");

            foreach($itemset as $s) {
                $set = new ItemSet();
                $set->pitem_id = $this->_item->item_id;
                $set->item_id = $s->item_id;
                $set->qty = $s->qty;
                $set->save();
            }

            foreach($serviceset as $s) {
                $set = new ItemSet();
                $set->pitem_id = $this->_item->item_id;
                $set->service_id = $s->service_id;
                $set->cost = $s->cost;

                $set->save();
            }

        }

        if($this->_item->disabled == 1) {
            $conn = \ZDB\DB::getConnect();
            $conn->Execute("delete from  item_set where item_id=".$this->_item->item_id) ;
        }

        $this->Reload(false);

        $this->itemtable->setVisible(true);
        $this->itemdetail->setVisible(false);
    }

    
   
    //комплекты
    public function onback($sender) {
        $this->setpanel->setVisible(false);
        $this->itemtable->setVisible(true);
    }

    public function setOnClick($sender) {
        $item = $sender->owner->getDataItem();
        $item = Item::load($item->item_id);

        $this->_pitem_id = $item->item_id;
        $this->setpanel->setVisible(true);
        $this->itemtable->setVisible(false);

        $this->setpanel->stitle->setText($item->itemname);

        $this->setupdate() ;

        $this->setpanel->cardform->editscard->setText($item->techcard)  ;

        $this->_tvars['complin']  = $item->item_type== Item::TYPE_PROD  || $item->item_type== Item::TYPE_HALFPROD;
        $this->_tvars['complout']  = $item->item_type== Item::TYPE_MAT  || $item->item_type== Item::TYPE_HALFPROD ;
        $this->_tvars['conploutlist']  = [];
        
        $conn = \ZDB\DB::getConnect()  ;
        
        $sql="SELECT s.qty,i.item_code,i.itemname  FROM 
            items i JOIN item_set s ON i.item_id=s.pitem_id 
            WHERE s.item_id = ".$item->item_id ;

        foreach($conn->Execute($sql) as $ii){
           $this->_tvars['conploutlist'][]=array(
              "iname"=>$ii['itemname'],
              "iqty"=> H::fqty( $ii['qty'] ),
              "icode"=>$ii['item_code']
           );     
        }
   
      foreach(\App\Entity\Service::find("") as $s){
           if(is_array($s->itemset)) {
               foreach($s->itemset as $is) {
                   if($is->item_id==$item->item_id) {
                       $this->_tvars['conploutlist'][]=array(
                          "iname"=>$s->service_name,
                          "iqty"=>$is->qty,
                          "icode"=>""
                       );     
                       
                   }
                   
               }
           }
        } 
        
        
    }

    private function setupdate() {
        $this->_itemset = ItemSet::find("item_id > 0  and pitem_id=" . $this->_pitem_id, "itemname");
        $this->_serviceset = ItemSet::find("service_id > 0  and pitem_id=" . $this->_pitem_id, "service_name");

        $this->setpanel->setlist->Reload();
        $this->setpanel->ssetlist->Reload();

        $total = 0;
        foreach($this->_itemset as $i) {
            $item = Item::load($i->item_id);
            if($item != null) {
                $total += doubleval($i->qty  * $item->getPartion());
            }

        }
        foreach($this->_serviceset as $s) {
            $total  += doubleval($s->cost);
        }

        $this->setpanel->stotal->setText(H::fa($total));

    }

    public function itemsetlistOnRow(\Zippy\Html\DataList\DataRow $row) {
        $item = $row->getDataItem();
        $row->add(new Label('sname', $item->itemname));
        $row->add(new Label('scode', $item->item_code));
        $row->add(new Label('sqty', H::fqty($item->qty)));
        //   $it= Item::load($item->item_id) ;
        //  $row->add(new Label('sprice', H::fa($it->getProdprice())));
        $row->add(new ClickLink('sdel'))->onClick($this, 'ondelset');
    }

    public function OnAutoSet($sender) {
        $text = Item::qstr('%' . $sender->getText() . '%');
        $in = "(" . $this->_pitem_id;
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

        $qty = $form->editsqty->getText();

        $set = new ItemSet();
        $set->pitem_id = $this->_pitem_id;
        $set->item_id = $id;
        $set->qty = $qty;

        $set->save();
        $this->setupdate() ;
        $form->clean();
    }

    public function ondelset($sender) {
        $item = $sender->owner->getDataItem();

        ItemSet::delete($item->set_id);

        $this->setupdate() ;
    }

    public function itemsetslistOnRow(\Zippy\Html\DataList\DataRow $row) {
        $item = $row->getDataItem();
        $row->add(new Label('ssname', $item->service_name));
        $row->add(new Label('sscost', H::fa($item->cost)));
        $row->add(new ClickLink('ssdel'))->onClick($this, 'ondelset');
    }

    public function OnAddSSet($sender) {
        $form= $this->setpanel->setsform;
        $id = $form->editssname->getValue();
        if ($id == 0) {
            $this->setError("Не обрано послугу або роботу");
            return;
        }

        $cost = $form->editscost->getText();

        $set = new ItemSet();
        $set->pitem_id = $this->_pitem_id;
        $set->service_id = $id;
        $set->cost = $cost;

        $set->save();


        $this->setupdate() ;
        $form->clean();
    }

    public function OnCardSet($sender) {

        $item = Item::load($this->_pitem_id);
        $item->techcard = $sender->editscard->getText();
        $item->save() ;


    }

    public function printQrOnClick($sender) {

        $printer = \App\System::getOptions('printer') ;
        $user = \App\System::getUser() ;

        $item = $sender->getOwner()->getDataItem();
        $header = [];
        if(intval($user->prtypelabel) == 0) {
            $urldata = \App\Util::generateQR($item->url, 100, 5)  ;
            $report = new \App\Report('item_qr.tpl');
            $header['src'] = $urldata;

            $html =  $report->generate($header);                  

            $this->addAjaxResponse("  $('#tag').html('{$html}') ; $('#pform').modal()");
            return;
        }
       
        try {
            $buf=[];
            if(intval($user->prtypelabel) == 1) {
                
               $report = new \App\Report('item_qr_ps.tpl');
               $header['qrcode'] = $item->url;

                $html =  $report->generate($header);              
                
                $buf = \App\Printer::xml2comm($html);
            }
            if(intval($user->prtypelabel) == 2) {
                $rows=[];
              
                $report = new \App\Report('item_qr_ts.tpl');
                $header['qrcode'] = $item->url;

                $text = $report->generate($header, false);
                $r = explode("\n", $text);
                foreach($r as $row) {
                    $row = str_replace("\n", "", $row);
                    $row = str_replace("\r", "", $row);
                    $row = trim($row);
                    if($row != "") {
                       $rows[] = $row;  
                    }
                   
                }           
                
                $buf = \App\Printer::arr2comm($rows);
            }
       
            $b = json_encode($buf) ;
            $this->addAjaxResponse(" sendPSlabel('{$b}') ");

        } catch(\Exception $e) {
            $message = $e->getMessage()  ;
            $message = str_replace(";", "`", $message)  ;
            $this->addAjaxResponse(" toastr.error( '{$message}' )         ");

        }


    }

    /*
    public function printOnClick($sender) {
        $item = $sender->getOwner()->getDataItem();
        $printer = \App\System::getOptions('printer');
        $pwidth = 'style="width:40mm;"';
        $pfs = 'style="font-size:16px;"';
        $pfsp = 'style="font-size:24px;"';

        if (strlen($printer['pwidth']) > 0) {
            $pwidth = 'style="width:' . $printer['pwidth'] . ' ";';
        }
        if (strlen($printer['pfontsize']) > 0) {
            $pfs = 'style="font-size:' . $printer['pfontsize'] . 'px";';
            $pfsp = 'style="font-size:' . intval(($printer['pfontsize'] * 1.5)) . 'px";';
        }


        $report = new \App\Report('item_tag.tpl');
        $header = array('width' => $pwidth, 'fsize' => $pfs, 'fsizep' => $pfsp);
        if ($printer['pname'] == 1) {

            if (strlen($item->shortname) > 0) {
                $header['name'] = $item->shortname;
            } else {
                $header['name'] = $item->itemname;
            }
            $header['name'] = str_replace("'","`", $header['name'])  ;
            $header['name'] = str_replace("\"'","`", $header['name'])  ;
        }


        $header['action'] = $item->actionprice > 0;
        $header['actionprice'] = $item->actionprice;
        $header['isap'] = false;
        if ($printer['pprice'] == 1) {
            $header['price'] = number_format($item->getPrice($printer['pricetype']), 2, '.', '');
            $header['isap'] = true;
        }
        if ($printer['pcode'] == 1) {
            $header['article'] = $item->item_code;
            $header['isap'] = true;
        }

        if ($printer['pqrcode'] == 1 && strlen($item->url) > 0) {
            $qrCode = new \Endroid\QrCode\QrCode($item->url);
            $qrCode->setSize(100);
            $qrCode->setMargin(5);
            $qrCode->setWriterByName('png');

            $dataUri = $qrCode->writeDataUri();
            $header['qrcode'] = "<img src=\"{$dataUri}\"  />";

        }
        if ($printer['pbarcode'] == 1) {
            $barcode = $item->bar_code;
            if (strlen($barcode) == 0) {
                $barcode = $item->item_code;
            }
           try{
            $generator = new \Picqer\Barcode\BarcodeGeneratorPNG();
            $img = '<img src="data:image/png;base64,' . base64_encode($generator->getBarcode($barcode, $printer['barcodetype'])) . '">';
            $header['img'] = $img;
            $header['barcode'] = \App\Util::addSpaces($barcode);
        } catch (\Throwable $e) {
           \App\Helper::logerror("barcode: ".$e->getMessage()) ;
           return '';
        }
            
            
        }
        $header['iscolor'] = $printer['pcolor'] == 1;

        $html = $report->generate($header);



    }

    */
    public function OnPrintAll($sender) {
        $buf=[];
        $items = array();
        foreach ($this->itemtable->listform->itemlist->getDataRows() as $row) {
            $item = $row->getDataItem();
            if ($item->seldel == true) {
                $items[] = $item;
            }
        }
        if (count($items) == 0) {
           $this->addAjaxResponse(" toastr.warning( 'Нема  данних для  друку ' )   ");
          
            return;
        }
        
        $user = \App\System::getUser() ;
        $ret = H::printItems($items);   
      
        if(intval($user->prtypelabel) == 0) {

            if(\App\System::getUser()->usemobileprinter == 1) {
                \App\Session::getSession()->printform =  $ret;

                $this->addAjaxResponse("   $('.seldel').prop('checked',null); window.open('/index.php?p=App/Pages/ShowReport&arg=print')");
            } else {
                $this->addAjaxResponse("  $('#tag').html('{$ret}') ;$('.seldel').prop('checked',null); $('#pform').modal()");

            }
            return;
        }

        try {

         
            if(intval($user->prtypelabel) == 1) {
                if(strlen($ret)==0) {
                   $this->addAjaxResponse(" toastr.warning( 'Нема  данних для  друку ' )   ");
                   return; 
                }
                $buf = \App\Printer::xml2comm($ret);
        
            }            
            if(intval($user->prtypelabel) == 2) {
                if(count($ret)==0) {
                   $this->addAjaxResponse(" toastr.warning( 'Нема  данних для  друку ' )   ");
                   return; 
                }
                $buf = \App\Printer::arr2comm($ret);
        
            }            
            $b = json_encode($buf) ;

            $this->addAjaxResponse("$('.seldel').prop('checked',null); sendPSlabel('{$b}') ");
        } catch(\Exception $e) {
            $message = $e->getMessage()  ;
            $message = str_replace(";", "`", $message)  ;
            $message = str_replace("'", "`", $message)  ;
            $this->addAjaxResponse(" toastr.error( '{$message}' )         ");

        }

    }

    public function onAllCat($sender) {
        $cat_id = $sender->getValue();
        if ($cat_id == 0) {
            return;
        }

        $items = array();
        foreach ($this->itemtable->listform->itemlist->getDataRows() as $row) {
            $item = $row->getDataItem();
            if ($item->seldel == true) {
                $items[] = $item;
            }
        }
        if (count($items) == 0) {
            return;
        }
        $conn = \ZDB\DB::getConnect();


        foreach ($items as $item) {

            $conn->Execute("update items set  cat_id={$cat_id} where  item_id={$item->item_id}");
        }

        $this->Reload();
        $sender->setValue(0);
    }

    public function OnDelAll($sender) {
        if (false == \App\ACL::checkDelRef('ItemList')) {
            return;
        }

        $items = array();
        foreach ($this->itemtable->listform->itemlist->getDataRows() as $row) {
            $item = $row->getDataItem();
            if ($item->seldel == true) {
                $items[] = $item;
            }
        }
        if (count($items) == 0) {
            return;
        }

        $conn = \ZDB\DB::getConnect();
        $d = 0;
        $u = 0;
        $onstore=[] ;
        foreach ($items as $it) {

            $cnt = $it->getQuantity();
            if($cnt != 0) {
                $onstore[]=$it->itemname;
                continue;
            }

            $sql = "  select count(*)  from  store_stock where   item_id = {$it->item_id}  ";
            $cnt = $conn->GetOne($sql);
            if ($cnt > 0) {
                $u++;

                $it->disabled=1;
                $it->save();
            } else {
                $d++;
                Item::delete($it->item_id) ;


            }


            $conn->Execute("delete from  item_set where item_id=".$it->item_id) ;
        }


        $this->setSuccess("Видалено {$d}, деактивовано {$u}");

        if(count($onstore)>0) {
            $w = "Товари ";
            $w .=  implode(",", $onstore)  ;

            $w .= " ще є на складі";
            $w = str_replace("'", "`", $w) ;
            $w = str_replace("\"", "`", $w) ;
            $this->setWarn($w);


        }

        $this->Reload();


    }

    
   public function OnPriceAll($sender) {
       if (false == \App\ACL::checkEditRef('ItemList')) {
            return;
       }   
        $this->_itemca = array();
        foreach ($this->itemtable->listform->itemlist->getDataRows() as $row) {
            $item = $row->getDataItem();
            if ($item->seldel == true) {
                
                $it = new \App\DataItem(intval($item->item_id)) ;
                $it->name = $item->itemname;
                $it->price = $item->price1;
              
                $this->_itemca[] = $it;
            }
        }
        if (count($this->_itemca) == 0) {
            return;
        }      
      
       $this->changeall->itemcalist->Reload(); 
       $this->itemtable->setVisible(false);
       $this->changeall->setVisible(true);        
 
 
   }    
   
   public function caOnRow(\Zippy\Html\DataList\DataRow $row) {
        $item = $row->getDataItem();
        $row->add(new Label('editallname', $item->name));
        $row->add(new TextInput('editallprice', new \Zippy\Binding\PropertyBinding($item, 'price')));
         
   } 
   
    public function saveall($sender) {
    
       foreach( $this->_itemca as $it) {
           $item = Item::load($it->id) ;
           $item->price1 = $it->price;
           $item->save();
       }
       
       
        $this->_itemca=[];
        $this->Reload(false);

        $this->itemtable->setVisible(true);
        $this->changeall->setVisible(false);        
    }    
       
    
    public function  shownotesOnClick($sender){
        $item = $sender->getOwner()->getDataItem();
        $desc = str_replace("'","`",$item->description);
        $desc = str_replace("\"","`",$desc);
      //  $desc = nl2br ($desc);        
        $desc = str_replace ("\n","",$desc);
        $desc = str_replace ("\r","",$desc);
        
        $this->updateAjax([],"$('#idesc').modal('show'); $('#idesccontent').html('{$desc}'); ")  ;
        
    }
    
    public function onService($sender) {
       $price=''; 
       $ser =  \App\Entity\Service::load($sender->getValue());
       if($ser != null) {
           $price = $ser->price;
       }
       $this->setpanel->setsform->editscost->setText($price);
        
    }
    
    public function OnTagList($sender) {
        $this->_tag  = $sender->getSelectedValue();

        $this->itemtable->setVisible(true);
        $this->itemdetail->setVisible(false);
       
        $this->itemtable->listform->itemlist->Reload();
         
    }
    
    public function printStOnClick($sender) {
         $item = $sender->getOwner()->getDataItem();
         $price= H::fa($item->getPrice() );
         $this->addAjaxResponse("   $('#stsum').text('') ; $('#tagsticker').html('') ;  $('#stitemid').val('{$item->item_id}') ;  $('#stqty').val('') ; $('#stprice').val('{$price}') ; $('#pscale').modal()");
      
    }
 
    public function getSticker($args, $post) {
        $printer = \App\System::getOptions('printer') ;
        $user = \App\System::getUser() ;
        
     
        $item =   Item::load($post["stitemid"]) ;
     
        $header = [];  
              
        if(strlen($item->shortname) > 0) {
            $header['name'] = $item->shortname;
        } else {
            $header['name'] = $item->itemname;
        }

        $header['code'] = $item->item_code;
       
        $header['price'] = H::fa($post["stprice"]);
        $header['qty'] = H::fqty($post["stqty"]);
        $header['sum'] = H::fa(doubleval($post["stprice"]) * doubleval( $post["stqty"] ) );
     
 
        $price= str_replace(',','.',$header['price'] )  ;
        $qty= str_replace(',','.', $header['qty'] ) ;
   
        $barcode= Item::packStBC($price,$qty,$post["stitemid"]);
        $header['barcode'] = $barcode;
         
      
        if(intval($user->prtypelabel) == 0) {
            $report = new \App\Report('item_sticker.tpl');
         
            $header['turn'] = $user->prturn ??'';
            if($user->prturn == 1) {
                $header['turn'] = 'transform: rotate(90deg);';
            }
            if($user->prturn == 2) {
                $header['turn'] = 'transform: rotate(-90deg);';
            }
 
            $generator = new \Picqer\Barcode\BarcodeGeneratorPNG();
            $header['dataUri']  = "data:image/png;base64," . base64_encode($generator->getBarcode($barcode, 'C128'))  ;

            $html =  $report->generate($header);
            $html = str_replace("'", "`", $html);
            return $this->jsonOK(array('data'=>$html,"printer"=>0),) ;
           
        }
       
        try {

            if(intval($user->prtypelabel) == 1) {
                
                $report = new \App\Report('item_sticker_ps.tpl');
             
                $html =  $report->generate($header);              
                   
                $buf = \App\Printer::xml2comm($html);
                return $this->jsonOK(array('data'=>$buf,"printer"=>1)); 
                
            
            }
            if(intval($user->prtypelabel) == 2) {
                $rows=[]; 
               
                $report = new \App\Report('item_sticker_ts.tpl');
                $header['qrcode'] = $item->url;

                $text = $report->generate($header, false);
                $r = explode("\n", $text);
                foreach($r as $row) {
                    $row = str_replace("\n", "", $row);
                    $row = str_replace("\r", "", $row);
                    $row = trim($row);
                    if($row != "") {
                       $rows[] = $row;  
                    }
                   
                }           
                
                $buf = \App\Printer::arr2comm($rows);
                return $this->jsonOK(array('data'=>$buf,"printer"=>2));
               
          }
     

        } catch(\Exception $e) {
            $message = $e->getMessage()  ;
            $message = str_replace(";", "`", $message)  ;
            $this->addAjaxResponse(" toastr.error( '{$message}' )         ");

        }        
    }
  
    public function optionsOnClick($sender) {
        $options = System::getOptions('common');
        
        $this->optionsform->articleprefix->setText($options['articleprefix'] ?? "ID");
        $this->optionsform->usecattree->setChecked($options['usecattree']);
        $this->optionsform->nocheckarticle->setChecked($options['nocheckarticle']);
        $this->optionsform->allowchange->setChecked($options['allowchange']);
        $this->optionsform->useimages->setChecked($options['useimages']);
        $this->optionsform->branchprice->setChecked($options['branchprice']);
        $this->optionsform->autoarticle->setChecked($options['autoarticle']);
        
        
        $this->_cflist = $options['cflist'] ?? '' ;
        if (is_array($this->_cflist) == false) {
            $this->_cflist = [];
        }        
                 
        $this->optionsform->cflist->Reload();        
        $this->itemtable->setVisible(false);
        $this->optionsform->setVisible(true);
    }  
    
    public function OnAddCF($sender) {
        $ls = new \App\DataItem();
        $ls->code = '';
        $ls->name = '';
        $ls->id = time();
        $this->_cflist[$ls->id] = $ls;
        $this->optionsform->cflist->Reload();
    }    
    
    public function cfOnRow($row) {
        $item = $row->getDataItem();
        $row->add(new TextInput('cfcode', new Bind($item, 'code')));
        $row->add(new TextInput('cfname', new Bind($item, 'name')));
        $row->add(new ClickLink('delcf', $this, 'onDelCF'));
        
    }  
   
    public function onDelCF($sender) {
        $item = $sender->getOwner()->getDataItem();

        $this->_cflist = array_diff_key($this->_cflist, array($item->id => $this->_cflist[$item->id]));

        $this->optionsform->cflist->Reload();
      
        
    }    
 
    public function saveopt($sender) {
        $options = System::getOptions('common');
        
        $options['useimages'] = $this->optionsform->useimages->isChecked() ? 1 : 0;
        $this->_tvars["useimages"] = $options['useimages'] == 1;        
        
        $options['nocheckarticle'] = $this->optionsform->nocheckarticle->isChecked() ? 1 : 0;
        $options['allowchange'] = $this->optionsform->allowchange->isChecked() ? 1 : 0;
        $options['usecattree'] = $this->optionsform->usecattree->isChecked() ? 1 : 0;
        $options['autoarticle'] = $this->optionsform->autoarticle->isChecked() ? 1 : 0;
        $options['branchprice'] = $this->optionsform->branchprice->isChecked() ? 1 : 0;
        $options['articleprefix'] = $this->optionsform->articleprefix->getText() ;
        
        
        $options['cflist'] = $this->_cflist;
        System::setOptions('common', $options);        
        $this->_tvars['usecf'] = count($options['cflist']) >0;
        $this->itemtable->setVisible(true);
        $this->optionsform->setVisible(false);
        $this->Reload(false);
        
    }  
 
    public function cfvOnRow($row) {
        $item = $row->getDataItem();
        $row->add(new Label('cfd', $item->name));
        $row->add(new TextInput('cfval', new Bind($item, 'val')));
         
    }
    
    public function onNds($sender) {
        $id = $sender->getValue();
        $this->itemdetail->editnds->setVisible($id==2);
   
    }
     
}

class ItemDataSource implements \Zippy\Interfaces\DataSource
{
    private $page;

    public function __construct($page) {
        $this->page = $page;
    }

    private function getWhere($p = false) {

        $form = $this->page->filter;
        $where = "1=1";
        $text = trim($form->searchkey->getText());
        $brand = trim($form->searchbrand->getText());
        $type = trim(''.$form->searchtype->getValue());
        $cat = $form->searchcat->getValue();


        if ($cat != 0) {
            if ($cat == -1) {
                $where = $where . " and cat_id=0";
            } else {


                $c = Category::load($cat) ;
                $ch = $c->getChildren();
                $ch[]=$cat;

                $cats = implode(",", $ch)  ;
                $where = $where . " and cat_id in ({$cats}) " ;
            }
        }

        if (strlen($brand) > 0) {

            $brand = Item::qstr($brand);
            $where = $where . " and  manufacturer like {$brand}      ";
        }

        if($type == 10) {
            $where = $where . " and disabled = 1";
        }
        if($type == 10) {
            $where = $where . " and disabled = 1";
        }
 
        if($type < 10) {
            $where = $where . " and disabled <> 1";
            if($type >0 && $type < 9) {
                $where = $where . " and item_type = {$type}";
            }
            if($type ==9 ) {
                $where = $where . " and detail like '%<isweight>1</isweight>%' ";
            }
        }
        if(strlen($this->page->_tag)>0) {
                
               $tag   = Item::qstr($this->page->_tag) ;
               $where = "disabled <> 1 and item_id in (select item_id from taglist where  tag_type=3 and tag_name={$tag} )"; 
        }

        if (strlen($text) > 0) {
           
            if ($p == false) {
                $det = Item::qstr('%' . "<cflist>%{$text}%</cflist>" . '%');
                $text = Item::qstr('%' . $text . '%');
                $where = $where . " and (itemname like {$text} or item_code like {$text}  or bar_code like {$text}  or detail like {$det} )  ";
            } else {
                $text = Item::qstr($text);
                $text_ = trim($text,"'") ;
                $where = $where . " and (itemname = {$text} or item_code = {$text}  or bar_code = {$text}   or detail like '%<bar_code1><![CDATA[{$text_}]]></bar_code1>%'   or detail like '%<bar_code2><![CDATA[{$text_}]]></bar_code2>%'  )  ";
            }
        }
        return $where;
    }

    public function getItemCount() {
        return Item::findCnt($this->getWhere());
    }

    public function getItems($start, $count, $sortfield = null, $asc = null) {
        $sortfield = "itemname asc";
        $sort = $this->page->filter->searchsort->getValue();

        if($sort==1) {
            $sortfield = "item_code asc";
        }
        if($sort==2) {
            $sortfield = "item_type asc";
        }
        if($sort==3) {
            $sortfield = "item_id desc";
        }

        $l = Item::find($this->getWhere(true), $sortfield, $count, $start);
        
        $fst="";
        $br=   \App\System::getBranch()  ;
        if($br >0){
           $fst = " store_stock.store_id in(select store_id from stores where  stores.branch_id = {$br}  )  and "; 
        }
           
        foreach (Item::findYield($this->getWhere(), $sortfield, $count, $start,"*,(select coalesce(sum(qty),0) from store_stock where {$fst} items_view.item_id = store_stock.item_id) as qty") as $k => $v) {
            $l[$k] = $v;
        }
        return $l;
    }

    public function getItem($id) {
        return Item::load($id);
    }

}
