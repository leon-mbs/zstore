<?php

namespace App\Pages\Reference;

use App\Entity\Position;
use Zippy\Html\DataList\DataView;
use Zippy\Html\Form\Button;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Panel;

//Должности
class PositionList extends \App\Pages\Base
{
    private $_pos;

    public function __construct() {
        parent::__construct();
        if (false == \App\ACL::checkShowRef('PositionList')) {
            return;
        }

        $this->add(new Panel('patable'))->setVisible(true);
        $this->patable->add(new DataView('palist', new \ZCL\DB\EntityDataSource('\App\Entity\Position', '', 'position_name'), $this, 'palistOnRow'))->Reload();
        $this->patable->add(new ClickLink('addnew'))->onClick($this, 'addOnClick');
        $this->add(new Form('padetail'))->setVisible(false);
        $this->padetail->add(new TextInput('editposition_name'));
        $this->padetail->add(new SubmitButton('save'))->onClick($this, 'saveOnClick');
        $this->padetail->add(new Button('cancel'))->onClick($this, 'cancelOnClick');
    }

    public function palistOnRow($row) {
        $item = $row->getDataItem();
      
        $conn = \ZDB\DB::getConnect() ;
        $item->cnt=  intval( $conn->Execute('select count(*) from employees where  position_id = '. $item->position_id) ); ;

        $row->add(new Label('position_name', $item->position_name));
        $row->add(new Label("cnt", $item->cnt));
        
        $row->add(new ClickLink('edit'))->onClick($this, 'editOnClick');
        $row->add(new ClickLink('delete'))->onClick($this, 'deleteOnClick');
        if ($item->cnt > 0) {
            $row->delete->setVisible(false);
        }
        if ($item->cnt == 0) {
            $row->cnt->setVisible(false);
        }
    }

    public function deleteOnClick($sender) {
        if (false == \App\ACL::checkDelRef('PositionList')) {
            return;
        }
        $pos = $sender->owner->getDataItem();

        $del = Position::delete($pos->position_id);
        if (strlen($del) > 0) {
            $this->setError($del);
            return;
        }
        $this->patable->palist->Reload();
    }

    public function editOnClick($sender) {
        $this->_pos = $sender->owner->getDataItem();
        $this->patable->setVisible(false);
        $this->padetail->setVisible(true);
        $this->padetail->editposition_name->setText($this->_pos->position_name);
    }

    public function addOnClick($sender) {
        $this->patable->setVisible(false);
        $this->padetail->setVisible(true);
        // Очищаем  форму
        $this->padetail->clean();

        $this->_pos = new Position();
    }

    public function saveOnClick($sender) {
        if (false == \App\ACL::checkEditRef('PositionList')) {
            return;
        }


        $this->_pos->position_name = $this->padetail->editposition_name->getText();
        if ($this->_pos->position_name == '') {
            $this->setError("Не введено назву");
            return;
        }

        $this->_pos->save();
        $this->padetail->setVisible(false);
        $this->patable->setVisible(true);
        $this->patable->palist->Reload();
    }

    public function cancelOnClick($sender) {
        $this->patable->setVisible(true);
        $this->padetail->setVisible(false);
    }

}
