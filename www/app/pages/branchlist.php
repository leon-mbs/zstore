<?php

namespace App\Pages;

use App\Application as App;
use App\Entity\Branch;
use App\System;
use App\Helper as H;
use Zippy\Html\DataList\DataView;
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

//Филиалы
class BranchList extends \App\Pages\Base
{
    private $_branch;

    public function __construct() {
        parent::__construct();
        if (System::getUser()->userlogin != 'admin') {
            System::setErrorMsg('До сторінки має доступ тільки адміністратор');
            \App\Application::RedirectError();
            return  ;
        }


        $this->add(new Panel('branchtable'))->setVisible(true);
        $this->branchtable->add(new DataView('branchlist', new \ZCL\DB\EntityDataSource('\App\Entity\Branch', '', 'disabled asc,branch_name asc'), $this, 'branchlistOnRow'));
        $this->branchtable->add(new ClickLink('addnew'))->onClick($this, 'addOnClick');
     

        $this->add(new Form('branchdetail'))->setVisible(false);
        $this->branchdetail->add(new TextInput('editbranchname'));
        $this->branchdetail->add(new TextInput('editaddress'));
        $this->branchdetail->add(new TextInput('editphone'));
        $this->branchdetail->add(new TextArea('editcomment'));
        $this->branchdetail->add(new CheckBox('editdisabled'));
        $this->branchdetail->add(new SubmitButton('save'))->onClick($this, 'saveOnClick');
        $this->branchdetail->add(new Button('cancel'))->onClick($this, 'cancelOnClick');
        
        $this->branchtable->add(new Form('formmove')) ;        
        $this->branchtable->formmove->add(new DropDownChoice('selmove'));
        $this->branchtable->formmove->add(new SubmitButton('btnmove'))->onClick($this, 'moveOnClick');

        
        $this->update();
    }

    public function branchlistOnRow($row) {
        $item = $row->getDataItem();

        $row->add(new Label('branch_name', $item->branch_name));

        $row->add(new Label('address', $item->address));

        $row->add(new ClickLink('edit'))->onClick($this, 'editOnClick');
        $row->add(new ClickLink('delete'))->onClick($this, 'deleteOnClick');
        $row->setAttribute('style', $item->disabled == 1 ? 'color: #aaa' : null);
    }

    public function deleteOnClick($sender) {

        $branch = $sender->owner->getDataItem();

        $del = Branch::delete($branch->branch_id);
        if (strlen($del) > 0) {
            $this->setError($del);
            return;
        }
        $this->update();
    }

    public function editOnClick($sender) {
        $this->_branch = $sender->owner->getDataItem();
        $this->branchtable->setVisible(false);
        $this->branchdetail->setVisible(true);
        $this->branchdetail->editbranchname->setText($this->_branch->branch_name);

        $this->branchdetail->editphone->setText($this->_branch->phone);

        $this->branchdetail->editaddress->setText($this->_branch->address);

        $this->branchdetail->editcomment->setText($this->_branch->comment);
        $this->branchdetail->editdisabled->setChecked($this->_branch->disabled);
    }

    public function addOnClick($sender) {
        $this->branchtable->setVisible(false);
        $this->branchdetail->setVisible(true);
        // Очищаем  форму
        $this->branchdetail->clean();

        $this->_branch = new Branch();
    }

    public function saveOnClick($sender) {


        $this->_branch->branch_name = $this->branchdetail->editbranchname->getText();
        if ($this->_branch->branch_name == '') {
            $this->setError("Не введено назву");
            return;
        }

        $this->_branch->address = $this->branchdetail->editaddress->getText();
        $this->_branch->phone = $this->branchdetail->editphone->getText();

        $this->_branch->comment = $this->branchdetail->editcomment->getText();
        $this->_branch->disabled = $this->branchdetail->editdisabled->isChecked() ? 1 : 0;

        $this->_branch->save();
        $this->branchdetail->setVisible(false);
        $this->branchtable->setVisible(true);
        $this->update();
    }

    public function cancelOnClick($sender) {
        $this->branchtable->setVisible(true);
        $this->branchdetail->setVisible(false);
    }
  
    private function update() {
        $this->branchtable->branchlist->Reload();
        
        $list=Branch::findArray("branch_name", "disabled <>1", "branch_name") ;
        
        $this->branchtable->formmove->selmove->setOptionList($list);  
        $this->branchtable->formmove->selmove->setValue(0);  
        
    }
    public function moveOnClick($sender) {
        $id= intval($this->branchtable->formmove->selmove->getValue()); 
        if($id==0) {
            $this->setError('Не вибрана  фiлiя');
            return;    
        }
        $conn = \ZDB\DB::getConnect();
        $conn->BeginTrans();
        try {
 
            $conn->Execute("update documents set branch_id={$id} where  coalesce(branch_id,0) = 0 ");
            $conn->Execute("update stores set branch_id={$id} where  coalesce(branch_id,0) = 0 ");
            $conn->Execute("update employees set branch_id={$id} where  coalesce(branch_id,0) = 0 ");
            $conn->Execute("update equipments set branch_id={$id} where  coalesce(branch_id,0) = 0 ");
            $conn->Execute("update mfund set branch_id={$id} where  coalesce(branch_id,0) = 0 ");
            $conn->Execute("update parealist set branch_id={$id} where  coalesce(branch_id,0) = 0 ");
            $conn->Execute("update poslist set branch_id={$id} where  coalesce(branch_id,0) = 0 ");
            $conn->Execute("update timesheet set branch_id={$id} where  coalesce(branch_id,0) = 0 ");
            
            
            $conn->CommitTrans();
            
            $this->setSuccess('Виконано');
  
        } catch(\Throwable $ee) {
            global $logger;
            $conn->RollbackTrans();
  
            $this->setError($ee->getMessage());
            $logger->error( $ee->getMessage()  );


        }        
        
        
    
       $this->branchtable->formmove->selmove->setValue(0);  
       
       
         
    }

}
