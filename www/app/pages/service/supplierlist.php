<?php

namespace App\Pages\Service;

use App\Entity\Supplier;
use App\Helper;
use App\System;
use Zippy\Binding\PropertyBinding as Bind;
use Zippy\Html\DataList\ArrayDataSource;
use Zippy\Html\DataList\DataView;
use Zippy\Html\Form\Button;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\Date;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\TextArea;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Link\BookmarkableLink;
use Zippy\Html\Link\SubmitLink;
use Zippy\Html\Panel;
use Zippy\Html\Link\SortLink;
use \Zippy\Html\DataList\DataRow;

/**
 * Страница контрагентов
 */
class SupplierList extends \App\Pages\Base
{

    private $_supplier        = null;
    

    public function __construct($id = 0) {
        parent::__construct();
        if (false == \App\ACL::checkShowRef('SupplierList')) {
            return;
        }
 
   
        $this->add(new Panel('listp'))->setVisible(true);
         
        $this->listp->add(new DataView('list', new \ZCL\DB\EntityDataSource('\App\Entity\Supplier', '', 'sup_name'), $this, 'listOnRow'));
        $this->listp->list->setPageSize(Helper::getPG());
        $this->listp->add(new \Zippy\Html\DataList\Paginator('pag', $this->listp->list));

        $this->listp->list->Reload();
        $this->listp->add(new ClickLink('addnew'))->onClick($this, 'addOnClick');


        $this->add(new Form('detail'))->setVisible(false);
        $this->detail->add(new TextInput('editname'));
        $this->detail->add(new TextInput('editsite'));
        $this->detail->add(new TextArea('editcomment'));
        $this->detail->add(new TextArea('editcontact'));
        $this->detail->add(new CheckBox('editdisabled'));

 
        $this->detail->add(new SubmitButton('save'))->onClick($this, 'saveOnClick');
        $this->detail->add(new Button('cancel'))->onClick($this, 'cancelOnClick');

    }
   

    public function listOnRow($row) {
        $item = $row->getDataItem();

        $row->add(new Label('name', $item->sup_name));
        $row->add(new BookmarkableLink('site', $item->site))->setValue($item->site);
        $row->site->setVisible(strlen($item->site)>0) ;
        $row->add(new Label('contact', $item->contact));
        $row->add(new Label('comment', $item->comment));
       
        $row->setAttribute('style', $item->disabled == 1 ? 'color: #aaa' : null);
        $row->add(new ClickLink('edit', $this,'editOnClick'));
        $row->add(new ClickLink('delete', $this,'deleteOnClick'));
     
    }

 
    public function editOnClick($sender) {
        $this->_supplier = $sender->owner->getDataItem();
 
        $this->listp->setVisible(false);
        $this->detail->setVisible(true);
 
        $this->detail->editname->setText($this->_supplier->sup_name);
        $this->detail->editsite->setValue($this->_supplier->site);
        $this->detail->editcontact->setText($this->_supplier->contact);
        $this->detail->editcomment->setText($this->_supplier->comment);
      
        $this->detail->editdisabled->setChecked($this->_supplier->disabled);
    }

    public function deleteOnClick($sender) {
        if (false == \App\ACL::checkDelRef('SupplierList')) {
            return;
        }


        $del = Supplier::delete($sender->owner->getDataItem()->sup_id);
        if (strlen($del) > 0) {
            $this->setError($del);
            return;
        }


        $this->listp->list->Reload();
    }

    public function addOnClick($sender) {
        $this->listp->setVisible(false);
        $this->detail->setVisible(true);
     
        $this->detail->clean();
      
        $this->_supplier = new Supplier();
    }

    public function saveOnClick($sender) {
        if (false == \App\ACL::checkEditRef('SupplierList')) {
            return;
        }

        $this->_supplier->sup_name = trim($this->detail->editname->getText() );
        $this->_supplier->site = trim($this->detail->editsite->getText() );
         
        if ($this->_supplier->sup_name == '') {
            $this->setError("entername");
            return;
        }
        $this->_supplier->contact = $this->detail->editcontact->getText();
        $this->_supplier->comment = $this->detail->editcomment->getText();
        $this->_supplier->disabled = $this->detail->editdisabled->isChecked() ? : 0;
   
  
        $this->_supplier->save();
        $this->detail->setVisible(false);
        $this->listp->setVisible(true);
        $this->listp->list->Reload();
       
    }

    public function cancelOnClick($sender) {
        $this->listp->setVisible(true);
        $this->detail->setVisible(false);
        
    }
   

}
