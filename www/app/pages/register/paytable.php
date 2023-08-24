<?php

namespace App\Pages\Register;

use App\Entity\Event;
use App\Helper as H;
use App\System;
use App\Application as App;
use Zippy\Html\DataList\DataView;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\Button;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Form\TextArea;
use Zippy\Html\Form\AutocompleteTextInput;
use Zippy\Html\Form\Date;
use Zippy\Html\Panel;
use Zippy\Html\Label;

use Zippy\Html\Link\ClickLink;
use Zippy\Html\Link\RedirectLink;

/**
* Платежный календарь
*/
class PayTable extends \App\Pages\Base
{
    public $_list =[];
    public $_tp =0;
    public $_event  ;
    public $_am =0 ;

    public function __construct() {
        parent::__construct();
        if (false == \App\ACL::checkShowReg('PayTable')) {
            \App\Application::RedirectHome() ;
        }


        $this->add(new Panel('listpan'));


        $this->listpan->add(new  ClickLink('addnew', $this, 'onAddNew'));

        $this->listpan->add(new DataView('nlist', new \Zippy\Html\DataList\ArrayDataSource($this, "_list"), $this, 'OnRow'));


        $this->add(new  Form('addeventform'))->setVisible(false);
        $this->addeventform->onSubmit($this, 'onSave');
        $this->addeventform->add(new Date('addeventdate', time()));
        $this->addeventform->add(new TextInput('addeventamount', 0));

        $bind = new  \Zippy\Binding\PropertyBinding($this, '_tp');

        $this->addeventform->add(new \Zippy\Html\Form\RadioButton('addeventin', $bind, 1));
        $this->addeventform->add(new \Zippy\Html\Form\RadioButton('addeventout', $bind, 2));


        $this->addeventform->add(new AutocompleteTextInput('addeventcust'))->onText($this, 'OnAutoCustomer');
        $this->addeventform->add(new TextInput('addeventtitle'));
        $this->addeventform->add(new TextArea('addeventdesc'));
        $this->addeventform->add(new ClickLink('cancel', $this, 'onCancel'));

        $this->add(new  Form('editeventform'))->setVisible(false);
        $this->editeventform->onSubmit($this, 'onSaveEdited');

        $this->editeventform->add(new Date('editeventdate', time()));
        $this->editeventform->add(new TextInput('editeventamount'));
        $this->editeventform->add(new TextArea('editeventdesc'));

        $this->editeventform->add(new ClickLink('canceledit', $this, 'onCancel'));

        $brids = \App\ACL::getBranchIDsConstraint();
        $brf="";
        if (strlen($brids) > 0) {
            $brf = " and   branch_id in ({$brids}) ";
        }
        $conn =   \ZDB\DB::getConnect();
        $sql = "select coalesce(sum(amount),0)  from paylist_view where  paytype <=1000 and mf_id  in (select mf_id  from mfund where 1=1  {$brf})";

        $this->_am = H::fa($conn->GetOne($sql));

        $this->_tvars['allcost']   = $this->_am;

        $this->update();

    }

    public function update() {

        $am =  $this->_am;

        $this->_list = [];
        foreach(Event::find("event_type=3  and (isdone <> 1 or eventdate >= NOW() ) ", "eventdate asc") as $event) {

            if($event->eventdate < time()) {
                $event->past=true;
            }
            if($event->paytype==2) {

                if($event->amount > $am) {
                    $event->nopay=true;
                }

                $am = H::fa($am) - H::fa($event->amount);
            }


            $this->_list[]=$event;
        }

        $this->listpan->nlist->Reload();
    }
    public function OnRow($row) {
        $event = $row->getDataItem();

        $row->add(new Label("date"))->setText(date('Y-m-d', $event->eventdate));
        if($event->past) {
            $row->date->setAttribute('class', 'text-danger');
        }

        $row->add(new Label("title"))->setText($event->title);
        $row->add(new Label("amountin"))->setText(H::fa($event->amount)) ;
        $row->add(new Label("amountout"))->setText(H::fa($event->amount)) ;
        if($event->nopay) {
            $row->amountout->setAttribute('class', 'text-warning');
        }
        $row->amountin->setVisible($event->paytype==1);
        $row->amountout->setVisible($event->paytype==2);

        $row->add(new Label("customer"))->setText($event->customer_name);
        $row->add(new Label('hasnotes'))->setVisible(strlen(trim($event->description))> 0);
        $row->hasnotes->setAttribute('title', $event->description);

        $row->add(new ClickLink('delete'))->onClick($this, 'deleteOnClick');
        $row->add(new ClickLink('edit'))->onClick($this, 'editOnClick');


    }

    public function onAddNew($sender) {

        $this->listpan->setVisible(false);
        $this->addeventform->setVisible(true);
        $this->addeventform->clean();
        $this->addeventform->addeventdate->setDate(strtotime('+7 days'));
        $this->addeventform->addeventcust->setText('');
        $this->addeventform->addeventcust->setKey(0);


    }

    public function onSave($sender) {
        $event = new Event();
        $event->title = $this->addeventform->addeventtitle->getText();
        $event->description = $this->addeventform->addeventdesc->getText();
        $event->eventdate = $this->addeventform->addeventdate->getDate();
        $event->amount = H::fa($this->addeventform->addeventamount->getText());
        $event->paytype = $this->_tp ?? 0;
        $event->customer_id =$this->addeventform->addeventcust->getKey();

        if ($this->_tp == 0) {
            return;
        }
        if ($event->amount==0) {
            return;
        }
        if ($event->amount<0) {
            return;
        }
        $event->event_type = Event::TYPE_PAYMENT ;
        $event->save();

        $this->update();

        $this->listpan->setVisible(true);
        $this->addeventform->setVisible(false);

    }



    public function onCancel($sender) {

        $this->listpan->setVisible(true);
        $this->addeventform->setVisible(false);
        $this->editeventform->setVisible(false);
    }

    public function onSaveEdited($sender) {

        $this->_event->description = $this->editeventform->editeventdesc->getText();
        $this->_event->eventdate = $this->editeventform->editeventdate->getDate();
        $this->_event->amount = H::fa($this->editeventform->editeventamount->getText());

        if ($this->_event->amount==0) {
            return;
        }
        if ($this->_event->amount < 0) {
            return;
        }

        $this->_event->save();

        $this->update();

        $this->listpan->setVisible(true);
        $this->editeventform->setVisible(false);
    }

    public function editOnClick($sender) {
        $this->_event = $sender->getOwner()->getDataItem();


        $this->editeventform->editeventamount->setText(H::fa($this->_event->amount));
        $this->editeventform->editeventdate->setDate($this->_event->eventdate);
        $this->editeventform->editeventdesc->setText($this->_event->description);


        $this->listpan->setVisible(false);
        $this->editeventform->setVisible(true);


    }



    public function deleteOnClick($sender) {
        $event = $sender->getOwner()->getDataItem();

        Event::delete($event->event_id);

        $this->update();


    }

    public function OnAutoCustomer($sender) {
        return \App\Entity\Customer::getList($sender->getText(), 0, true);
    }


}
