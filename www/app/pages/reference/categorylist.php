<?php

namespace App\Pages\Reference;

use App\Entity\Category;
use App\System;
use Zippy\Html\DataList\DataView;
use Zippy\Html\DataList\ArrayDataSource;
use Zippy\Html\Form\Button;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Panel;
use Zippy\Binding\PropertyBinding as Bind;
use Zippy\Html\Link\SubmitLink;

/**
 * справочник категорийтоваров
 */
class CategoryList extends \App\Pages\Base
{
    private $_rn=0;
    private $_category;
    public $_catlist = [];
    public $_cplist = [];
    public $_cflist = [];

    public function __construct() {
        parent::__construct();
        if (false == \App\ACL::checkShowRef('CategoryList')) {
            return;
        }

        $this->add(new Panel('categorytable'))->setVisible(true);
        $this->categorytable->add(new DataView('categorylist', new ArrayDataSource($this, '_catlist'), $this, 'categorylistOnRow'));
        $this->categorytable->categorylist->setPageSize(\App\Helper::getPG());
        $this->categorytable->add(new \Zippy\Html\DataList\Paginator('pag', $this->categorytable->categorylist));
        
        $this->categorytable->add(new ClickLink('addnew'))->onClick($this, 'addOnClick');
        $this->add(new Form('categorydetail'))->setVisible(false);
        $this->categorydetail->add(new TextInput('editcat_name'));
        $this->categorydetail->add(new TextInput('editcat_desc'));
        $this->categorydetail->add(new DropDownChoice('editparent', 0));

        $this->categorydetail->add(new TextInput('editprice1'));
        $this->categorydetail->add(new TextInput('editprice2'));
        $this->categorydetail->add(new TextInput('editprice3'));
        $this->categorydetail->add(new TextInput('editprice4'));
        $this->categorydetail->add(new TextInput('editprice5'));
        $this->categorydetail->add(new TextInput('editnds'));
        
        $this->add(new Form('categoryprice'))->setVisible(false);
        $this->categoryprice->add(new Label('catprname')) ;
        $this->categoryprice->add(new SubmitButton('savecp'))->onClick($this, 'savepriceOnClick');
        $this->categoryprice->add(new SubmitButton('calccp'))->onClick($this, 'calcpriceOnClick');
        $this->categoryprice->add(new  ClickLink("backprice",$this,"cancelOnClick"));
        $this->categoryprice->add(new TextInput('chprice'));
        $this->categoryprice->add(new CheckBox('rnd' ));
        $this->categoryprice->add(new DataView('cplist', new ArrayDataSource($this, '_cplist'), $this, 'pricelistOnRow'));
        
        $ptype=[];
        
        $common = System::getOptions('common');
        if (strlen($common['price1']) > 0) {
            $this->categorydetail->editprice1->setVisible(true);
            $this->categorydetail->editprice1->setAttribute('placeholder', $common['price1']);
            $ptype[1] = $common['price1'];
        } else {
            $this->categorydetail->editprice1->setVisible(false);
        }
        if (strlen($common['price2']) > 0) {
            $this->categorydetail->editprice2->setVisible(true);
            $this->categorydetail->editprice2->setAttribute('placeholder', $common['price2']);
            $ptype[2] = $common['price2'];
        } else {
            $this->categorydetail->editprice2->setVisible(false);
        }
        if (strlen($common['price3']) > 0) {
            $this->categorydetail->editprice3->setVisible(true);
            $this->categorydetail->editprice3->setAttribute('placeholder', $common['price3']);
            $ptype[3] = $common['price3'];
        } else {
            $this->categorydetail->editprice3->setVisible(false);
        }
        if (strlen($common['price4']) > 0) {
            $this->categorydetail->editprice4->setVisible(true);
            $this->categorydetail->editprice4->setAttribute('placeholder', $common['price4']);
            $ptype[4] = $common['price4'];
       } else {
            $this->categorydetail->editprice4->setVisible(false);
        }
        if (strlen($common['price5']) > 0) {
            $this->categorydetail->editprice5->setVisible(true);
            $this->categorydetail->editprice5->setAttribute('placeholder', $common['price5']);
            $ptype[5] = $common['price5'];
       } else {
            $this->categorydetail->editprice5->setVisible(false);
        }

        $this->categoryprice->add(new DropDownChoice('ptype',$ptype,1 ));


        $this->categorydetail->add(new \Zippy\Html\Image('editimage' ));
        $this->categorydetail->add(new \Zippy\Html\Form\File('editaddfile'));
        $this->categorydetail->add(new CheckBox('editdelimage'));
        $this->categorydetail->add(new CheckBox('editnoshop'));
        $this->categorydetail->add(new CheckBox('editnofastfood'));
        $this->categorydetail->add(new CheckBox('editnoprice'));

        
        
        $this->categorydetail->add(new SubmitButton('save'))->onClick($this, 'saveOnClick');
        $this->categorydetail->add(new Button('cancel'))->onClick($this, 'cancelOnClick');

        $this->add(new Form('cfform'))->setVisible(false);        
        $this->cfform->add(new SubmitButton('savec'))->onClick($this, 'savecf');
        $this->cfform->add(new Button('cancelc'))->onClick($this, 'cancelOnClick');
        $this->cfform->add(new DataView('cflist', new ArrayDataSource(new Bind($this, '_cflist')), $this, 'cfOnRow'));
        $this->cfform->add(new SubmitLink('addnewcf'))->onClick($this, 'OnAddCF');
        $this->cfform->add(new Label('catprname2')) ;          
        
        
        $this->Reload();
    }

    public function Reload() {
        $this->_catlist = Category::find('', 'cat_name', -1, -1 );
        foreach (Category::findFullData() as $c) {
            $this->_catlist[$c->cat_id]->full_name = $c->full_name;
            $this->_catlist[$c->cat_id]->parents = $c->parents;
        }

        usort($this->_catlist, function ($a, $b) {
            return $a->order > $b->order;
        });      
        $this->_rn=0;

        $this->categorytable->categorylist->Reload();
    }

    public function updateParentList($id = 0) {
        $plist = array();
        foreach ($this->_catlist as $c) {
            if ($c->cat_id == $id) {
                continue;
            }
            if ($c->qty > 0) {
                continue;
            }
            if (in_array($id, $c->parents)) {
                continue;
            }
            $plist[$c->cat_id] = $c->full_name;
        }

        $this->categorydetail->editparent->setOptionList($plist);
    }

    public function categorylistOnRow(\Zippy\Html\DataList\DataRow $row) {
        $item = $row->getDataItem();

        $row->add(new Label('cat_name', $item->cat_name));
        
        $parent ="";
        if($item->parent_id >0) {
            $pcat = $this->getById($item->parent_id) ;                    
            $parent = $pcat->full_name;
        }

        $row->add(new Label('p_name', $parent));
        $row->add(new Label('qty', $item->itemscnt))->setVisible(($item->itemscnt ?? 0) > 0);
        $row->add(new ClickLink('edit'))->onClick($this, 'editOnClick');
        $row->add(new ClickLink('delete'))->onClick($this, 'deleteOnClick');

        $row->add(new \Zippy\Html\Link\BookmarkableLink('imagelistitem'))->setValue($item->getImageUrl());
        $row->imagelistitem->setAttribute('href', $item->getImageUrl());
        $row->imagelistitem->setAttribute('data-gallery', $item->image_id);
        if ($item->image_id == 0) {
            $row->imagelistitem->setVisible(false);
        }

        $row->add(new ClickLink("up", $this, "OnMove"))->setVisible($this->_rn>0)   ;
        $row->add(new ClickLink("down", $this, "OnMove"))->setVisible($this->_rn<count($this->_catlist)-1)   ;
        $this->_rn++;
        $row->add(new ClickLink('prices',$this, 'pricesOnClick'))->setVisible(($item->itemscnt ?? 0) > 0);
        $row->add(new ClickLink('cfields',$this, 'cfieldsOnClick'))->setVisible($item->childcnt==0);

    }

    public function deleteOnClick($sender) {
        if (false == \App\ACL::checkDelRef('CategoryList')) {
            return;
        }
        
        $cat_id = $sender->owner->getDataItem()->cat_id;
        $cat = $this->getById($cat_id) ;
        if ($cat->qty > 0) {
            $this->setError('Не можна видалити категорію з ТМЦ');
            return;
        }
        if ($cat->hasChild()) {
            $this->setError('Категорія має дочірні категорії');
            return;
        }


        Category::delete($cat_id);

        $this->Reload();
        $this->resetURL() ;
    }

    public function editOnClick($sender) {
        $this->_category = $sender->owner->getDataItem();
        $this->updateParentList($this->_category->cat_id);

        $this->categorytable->setVisible(false);
        $this->categorydetail->setVisible(true);
        $this->categorydetail->editcat_name->setText($this->_category->cat_name);
        $this->categorydetail->editcat_desc->setText($this->_category->cat_desc);
        $this->categorydetail->editparent->setValue($this->_category->parent_id);
        $this->categorydetail->editnoshop->setChecked($this->_category->noshop);
        $this->categorydetail->editnofastfood->setChecked($this->_category->nofastfood);
        $this->categorydetail->editnoprice->setChecked($this->_category->noprice);
        $this->categorydetail->editnoprice->setVisible(true);
        if($this->_category->hasChild()) {
            $this->categorydetail->editnoprice->setChecked(0);
            $this->categorydetail->editnoprice->setVisible(false);
        }
        $this->categorydetail->editprice1->setText($this->_category->price1);
        $this->categorydetail->editprice2->setText($this->_category->price2);
        $this->categorydetail->editprice3->setText($this->_category->price3);
        $this->categorydetail->editprice4->setText($this->_category->price4);
        $this->categorydetail->editprice5->setText($this->_category->price5);
        $this->categorydetail->editnds->setText($this->_category->nds);

        if ($this->_category->image_id > 0) {
            $this->categorydetail->editdelimage->setChecked(false);
            $this->categorydetail->editdelimage->setVisible(true);
            $this->categorydetail->editimage->setVisible(true);
            $this->categorydetail->editimage->setUrl(  $this->_category->getImageUrl());
        } else {
            $this->categorydetail->editdelimage->setVisible(false);
            $this->categorydetail->editimage->setVisible(false);
        }
    }

    public function addOnClick($sender) {
        $this->_category = new Category();
 
        $this->updateParentList($this->_category->cat_id);
        $this->categorytable->setVisible(false);
        $this->categorydetail->setVisible(true);
        // Очищаем  форму
        $this->categorydetail->clean();
        $this->categorydetail->editimage->setVisible(false);
        $this->categorydetail->editdelimage->setVisible(false);
        $this->updateParentList();
     }

    public function saveOnClick($sender) {
        if (false == \App\ACL::checkEditRef('CategoryList')) {
            return;
        }

        $pid=$this->categorydetail->editparent->getValue() ;
        $this->_category->parent_id = $pid >0 ? $pid  :0;
        $this->_category->cat_name = $this->categorydetail->editcat_name->getText();
        $this->_category->cat_desc = $this->categorydetail->editcat_desc->getText();
        $this->_category->noshop = $this->categorydetail->editnoshop->isChecked() ? 1 : 0;
        $this->_category->nofastfood = $this->categorydetail->editnofastfood->isChecked() ? 1 : 0;
        $this->_category->noprice = $this->categorydetail->editnoprice->isChecked() ? 1 : 0;
        if ($this->_category->cat_name == '') {
            $this->setError("Не введено назву");
            return;
        }

        $this->_category->price1 = $this->categorydetail->editprice1->getText();
        $this->_category->price2 = $this->categorydetail->editprice2->getText();
        $this->_category->price3 = $this->categorydetail->editprice3->getText();
        $this->_category->price4 = $this->categorydetail->editprice4->getText();
        $this->_category->price5 = $this->categorydetail->editprice5->getText();
        $this->_category->nds = $this->categorydetail->editnds->getText();

        //delete image
        if ($this->categorydetail->editdelimage->isChecked()) {
            if ($this->_category->image_id > 0) {
                 \App\Entity\Image::delete($this->_category->image_id);
            }
            $this->_category->image_id = 0;
        }

        $this->_category->save();

        $file = $this->categorydetail->editaddfile->getFile();
        if (strlen($file["tmp_name"]??'') > 0) {
            
            if (filesize($file["tmp_name"])  > 1024*1024) {

                    $this->setError('Розмір файлу більше 1M');
                    return;
            }            
            
            $imagedata = getimagesize($file["tmp_name"]);

            if (preg_match('/(gif|png|jpeg)$/', $imagedata['mime']) == 0) {
                $this->setError('Невірний формат  зображення');
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
                $thumb->resize(512, 512);
                $image->content = $thumb->getImageAsString();
            }
       

            $image->save();
            $this->_category->image_id = $image->image_id;
            $this->_category->save();
        }

        $this->categorydetail->setVisible(false);
        $this->categorytable->setVisible(true);
        $this->Reload();
    }

    public function cancelOnClick($sender) {
        $this->categorytable->setVisible(true);
        $this->categorydetail->setVisible(false);
        $this->categoryprice->setVisible(false);
        $this->cfform->setVisible(false);
        
    }

    public function OnMove($sender) {
        $c = $sender->getOwner()->getDataItem();
        $pos=  array_search($c, $this->_catlist, true) ;

        if(strpos($sender->id, 'up')===0) {

            $c->order--  ;

            $p= $this->_catlist[$pos-1] ;
            $p->order++;

            $this->_catlist[$pos]  = $p;
            $this->_catlist[$pos-1]  = $c;



        }


        if(strpos($sender->id, 'down')===0) {

            $c->order++;


            $n= $this->_catlist[$pos+1] ;
            $n->order--;

            $this->_catlist[$pos]  = $n;
            $this->_catlist[$pos+1]  = $c;


        }

        for($i=0;$i<count($this->_catlist);$i++) {
            $this->_catlist[$i]->order=$i;
            $this->_catlist[$i]->save() ;
        }

        $this->Reload();


    }

    //изза сортировки
    private   function getById($id){
        foreach( $this->_catlist as $c){
            if($c->cat_id == $id) {
                return $c;
            }
        }
        return null;        
    }

    
    public function pricesOnClick($sender) {
        $this->_category = $sender->owner->getDataItem();
        $this->categoryprice->catprname->setText($this->_category->cat_name);        
        $this->categorytable->setVisible(false);
        $this->categoryprice->setVisible(true);
        $this->categoryprice->chprice->setText('');  
        $this->_cplist=[];
         
        $this->categoryprice->cplist->Reload() ;       
        
        $this->categoryprice->savecp->setVisible(false);       

    }

    public function savepriceOnClick($sender) {
        $pt =intval($this->categoryprice->ptype->getValue());
        if($pt < 1)  return;
       
        foreach($this->_cplist as $it ) {
            
            if(round($it->newp)==0) continue;
            
            $item= \App\Entity\Item::load($it->item_id);
            $item->{'price'.$pt}   = round($it->newp);
            $item->save();
        }
        
        
        $this->categorytable->setVisible(true);
        $this->categoryprice->setVisible(false);       
    }
    

    public function calcpriceOnClick($sender) {
        $this->_cplist=[];
       
         
        $pt =intval($this->categoryprice->ptype->getValue());
        if($pt < 1)  return;
        
        $v =trim($this->categoryprice->chprice->getText());
        $isper = strpos($v,'%') > 0;
        $v = doubleval(str_replace('%','',$v) );
       
        $rnd= $this->categoryprice->rnd->isChecked();
        
        foreach( \App\Entity\Item::find("disabled <> 1 and  cat_id=". $this->_category->cat_id,'itemname') as $item ) {
        
            $ip=$item->{'price'.$pt} ;
            if(strpos($ip,'%') > 0) continue;
            if(strlen($ip)== 0) continue;
            
            if($isper) {
               $ipp=  $ip * ($v/100) ;
               $ip = $ip+$ipp;  
                
            }   else {
               $ip = $ip + $v;    
            }            
            $ip = round($ip);  
         
            if($rnd) {
              
               $ld = $ip % 10;
               if($ld==0)  $ip = $ip-1;
               if($ld==1)  $ip = $ip-2;
               if($ld==2)  $ip = $ip-3;
               if($ld==3)  $ip = $ip-4;
               if($ld==4)  $ip = $ip-5;
               if($ld==5)  $ip = $ip+4;
               if($ld==6)  $ip = $ip+3;
               if($ld==7)  $ip = $ip+2;
               if($ld==8)  $ip = $ip+1;
                
            }
            
 
           
           $di = new \App\DataItem() ;
           $di->item_id=$item->item_id;
           $di->name=$item->itemname;
           $di->code=$item->item_code;
           $di->oldp=$item->{'price'.$pt} ;
           $di->newp=$ip ;
           
           $this->_cplist[$item->item_id] = $di;
           
           
        }
            
        $this->categoryprice->cplist->Reload() ;       

        $this->categoryprice->savecp->setVisible(true);       

        
    }

    public function pricelistOnRow($row){
        $item = $row->getDataItem();

        $row->add(new Label('cplname', $item->name));
        $row->add(new Label('cplcode', $item->code));
        $row->add(new Label('cplold', $item->oldp));
        $row->add(new TextInput('cplnew',new \Zippy\Binding\PropertyBinding($item, 'newp')));
    }
    
    public function cfieldsOnClick($sender) {
        $this->_category = $sender->owner->getDataItem();
        $this->cfform->catprname2->setText($this->_category->cat_name);        

        $this->_category = Category::load($this->_category->cat_id);
        $this->_cflist = [];
        $i=0;
        foreach($this->_category->cflist as $k=>$v){
            $ls = new \App\DataItem();
            $ls->code = $k;
            $ls->name = $v;
            $ls->id = $i++;
            $this->_cflist[$ls->id] = $ls;          
                
        }
      
      
                 
        $this->cfform->cflist->Reload();        

        $this->cfform->setVisible(true);       
        $this->categorytable->setVisible(false);       

    }
  
    public function OnAddCF($sender) {
        $ls = new \App\DataItem();
        $ls->code = '';
        $ls->name = '';
        $ls->id = time();
        $this->_cflist[$ls->id] = $ls;
        $this->cfform->cflist->Reload();
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

        $this->cfform->cflist->Reload();
      
        
    }    
 
    public function savecf($sender) {
        $cflist=[]; 
        foreach($this->_cflist  as $v){
            $cflist[$v->code]=$v->name;
        }   
        $this->_category->cflist=$cflist; 
        $this->_category->save();
        $this->categorytable->setVisible(true);
        $this->cfform->setVisible(false);
        $this->Reload( );
        
    }    
}
