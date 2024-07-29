<?php

namespace App\Pages;

use App\Entity\Event;
use App\Helper as H;
use App\System;
use App\Application as App;
use Zippy\Html\DataList\DataView;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\Button;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Form\TextArea;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Date;
use Zippy\Html\Panel;
use Zippy\Html\Label;

use Zippy\Html\Link\ClickLink;
use Zippy\Html\Link\RedirectLink;

/**
* мои  задания
*/
class Jobs extends \App\Pages\Base
{
    public $_event;

    public function __construct() {
        parent::__construct();
        $user = System::getUser();
        if ($user->user_id == 0) {
            App::Redirect("\\App\\Pages\\Userlogin");
            return;
        }

        $this->add(new Panel('listpan'));

        $this->listpan->add(new Form('filter'))->onSubmit($this, 'filterOnSubmit');
        $this->listpan->filter->add(new TextInput('searchtext'));

        $this->listpan->add(new  ClickLink('addnew', $this, 'onAddNew'));


        $this->ds = new \ZCL\DB\EntityDataSource("\\App\\Entity\\Event", " event_type in(0,2) and ( user_id={$user->user_id} or createdby={$user->user_id}  ) ", " isdone asc,  (date(eventdate) > date(now( )))  asc, eventdate desc");

        $this->listpan->add(new DataView("nlist", $this->ds, $this, 'OnRow'));
        $this->listpan->nlist->setPageSize(H::getPG());
        $this->listpan->add(new \Zippy\Html\DataList\Pager("pag", $this->listpan->nlist));

        $this->add(new  Form('addeventform'))->setVisible(false);
        $this->addeventform->onSubmit($this, 'onSave');
        $this->addeventform->add(new Date('addeventdate', time()));
        $this->addeventform->add(new \Zippy\Html\Form\Time('addeventtime', time()));
        $this->addeventform->add(new TextInput('addeventtitle'));
        $this->addeventform->add(new TextArea('addeventdesc'));
        $this->addeventform->add(new DropDownChoice('addeventnotify', array(), 0));
        $this->addeventform->add(new DropDownChoice('adduser', \App\Entity\User::findArray("username", "disabled<>1 and  user_id<>" . $user->user_id, "username"), 0));
        $this->addeventform->add(new ClickLink('cancel', $this, 'onCancel'));

        $this->add(new  Form('editeventform'))->setVisible(false);
        $this->editeventform->onSubmit($this, 'onSaveEdited');
        $this->editeventform->add(new Date('editeventdate', time()));
        $this->editeventform->add(new \Zippy\Html\Form\Time('editeventtime', time()));
        $this->editeventform->add(new TextInput('editeventtitle'));
        $this->editeventform->add(new TextArea('editeventdesc'));

        $this->editeventform->add(new ClickLink('canceledit', $this, 'onCancel'));
        $this->editeventform->add(new ClickLink('delete', $this, 'onDelete'));


        $this->listpan->nlist->Reload();
    }

    public function OnRow($row) {
        $event = $row->getDataItem();


        $row->add(new RedirectLink("customer", "\\App\\Pages\\Reference\\CustomerList", array($event->customer_id)))->setValue($event->customer_name);

        $row->add(new Label("description"))->setText($event->description);
        $row->add(new Label("title"))->setText($event->title);
        $row->add(new Label("createdby"))->setText($event->createdname);
        $row->add(new Label("username"))->setText($event->username);
        $row->add(new Label("date", \App\Helper::fdt($event->eventdate)));
        $row->add(new ClickLink('edit', $this, 'onEditClick'))->setVisible(($event->createdby ?? 0) ==0  ||  $event->createdby==System::getUser()->user_id);
        $row->add(new ClickLink('done', $this, 'onDoneClick'));
        $row->add(new Label("stwait"))->setVisible(false);
        $row->add(new Label("sttoday"))->setVisible(false);
        $row->add(new Label("stpast"))->setVisible(false);
        $row->add(new Label("stdone"))->setVisible(false);
        $date = new \App\DateTime();
        $start = $date->addDay(1)->startOfDay()->getTimestamp();


        if ($start <= $event->eventdate) {
            $row->stwait->setVisible(true);
        } else {
            if ($start > $event->eventdate && time() < $event->eventdate) {
                $row->sttoday->setVisible(true);
            } else {
                if (time() > $event->eventdate) {
                    $row->stpast->setVisible(true);
                }
            }
        }
        if ($event->isdone == 1) {
            $row->stdone->setVisible(true);
            $row->stwait->setVisible(false);
            $row->sttoday->setVisible(false);
            $row->stpast->setVisible(false);
        }


    }

    public function onDoneClick($sender) {
        $item = $sender->getOwner()->getDataItem();

        $item->isdone = 1;
        $item->save();
        $this->listpan->nlist->Reload();


    }

    public function onAddNew($sender) {

        $this->listpan->setVisible(false);
        $this->addeventform->setVisible(true);
        $this->addeventform->clean();
        $this->addeventform->addeventdate->setDate(time());
        $this->addeventform->addeventtime->setDateTime(time());


    }

    public function onSave($sender) {
        $event = new Event();
        $event->title = $this->addeventform->addeventtitle->getText();
        $event->description = $this->addeventform->addeventdesc->getText();
        $event->eventdate = $this->addeventform->addeventdate->getDate();
        $event->eventdate = $this->addeventform->addeventtime->getDateTime($event->eventdate);
        $event->user_id = System::getUser()->user_id;
        $event->createdby = System::getUser()->user_id;
        $user = $this->addeventform->adduser->getValue();
        if ($user > 0) {
            $event->user_id = $user;
        }

        if (strlen($event->title) == 0) {
            return;
        }
        $event->event_type=Event::TYPE_JOB ;
        $event->save();

        $nt = $this->addeventform->addeventnotify->getValue();
        if ($nt > 0) {

            $n = new \App\Entity\Notify();
            $n->user_id = System::getUser()->user_id;
            $n->dateshow = $event->eventdate - ($nt * 3600);
            $n->message = "<b>" . $event->title . "</b>" . "<br>" . $event->description;
            $n->sender_id = \App\Entity\Notify::EVENT;

            $n->save();
        }

        $this->listpan->nlist->Reload();
        $this->listpan->setVisible(true);
        $this->addeventform->setVisible(false);

    }

    public function onEditClick($sender) {
        $this->_event = $sender->getOwner()->getDataItem();
        $this->_event->isdone = 0 ;       
        $this->editeventform->editeventtitle->setText($this->_event->title);
        $this->editeventform->editeventdesc->setText($this->_event->description);
        $this->editeventform->editeventdate->setDate($this->_event->eventdate);
        $this->editeventform->editeventtime->setDateTime($this->_event->eventdate);

        $this->listpan->setVisible(false);
        $this->editeventform->setVisible(true);

    }

    public function onCancel($sender) {

        $this->listpan->setVisible(true);
        $this->addeventform->setVisible(false);
        $this->editeventform->setVisible(false);
    }

    public function onSaveEdited($sender) {
        $this->_event->title = $this->editeventform->editeventtitle->getText();
        $this->_event->description = $this->editeventform->editeventdesc->getText();
        $this->_event->eventdate = $this->editeventform->editeventdate->getDate();
        $this->_event->eventdate = $this->editeventform->editeventtime->getDateTime($this->_event->eventdate);


        if (strlen($this->_event->title) == 0) {
            return;
        }
        $this->_event->save();


        $this->listpan->nlist->Reload();
        $this->listpan->setVisible(true);
        $this->addeventform->setVisible(false);
        $this->editeventform->setVisible(false);
    }

    public function onDelete($sender) {

        Event::delete($this->_event->event_id);

        $this->listpan->nlist->Reload();
        $this->listpan->setVisible(true);
        $this->addeventform->setVisible(false);
        $this->editeventform->setVisible(false);
    }


    public function filterOnSubmit($sender) {
        $user_id =  System::getUser()->user_id;
        $where = " event_type in(0,2) and ( user_id={$user_id} or createdby={$user_id}  ) ";
        $text = trim($sender->searchtext->getText());
        if (strlen($text) > 0) {
            $text = Event::qstr('%' . $text . '%');
            $where .= " and  ( description like {$text} or title like {$text} )  " ;
        }

        $this->ds->setWhere($where);
        $this->listpan->nlist->Reload();
    }




}
