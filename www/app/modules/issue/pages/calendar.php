<?php

namespace App\Modules\Issue\Pages;

use App\Helper as H;
use App\Modules\Issue\Entity\Issue;
use App\Modules\Issue\Entity\Project;
use App\Modules\Issue\Entity\TimeLine;
use App\System;
use ZCL\DB\EntityDataSource;
use Zippy\Html\DataList\DataView;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Form\Date;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Panel;

class Calendar extends \App\Pages\Base
{

    private $timerow = null;
    private $_tl     = null;

    public function __construct() {
        parent::__construct();
        $user = System::getUser();
        $allow = (strpos($user->modules, 'issue') !== false || $user->rolename == 'admins');
        if (!$allow) {
            System::setErrorMsg(H::l('noaccesstopage'));
            App::RedirectError();
            return;
        }
        $this->add(new Panel('listpan'));
        $this->add(new ClickLink('tabc', $this, 'onTab'));
        $this->add(new ClickLink('tabs', $this, 'onTab'));
        $this->listpan->add(new Panel('tasktab'))->setVisible(false);

        $this->listpan->add(new ClickLink('addtime', $this, 'OnAdd'));

        $this->listpan->tasktab->add(new DataView('timelist', new EntityDataSource("\\App\\Modules\\Issue\\Entity\\TimeLine", 'user_id=' . $user->user_id, 'id desc'), $this, 'OnTimeRow'));
        $this->listpan->tasktab->add(new \Zippy\Html\DataList\Paginator('pag', $this->listpan->tasktab->timelist));
        $this->listpan->tasktab->timelist->setPageSize(H::getPG());
        $this->listpan->tasktab->timelist->Reload();

        $this->listpan->add(new Panel('caltab'));

        $this->listpan->caltab->add(new \ZCL\Calendar\Calendar('calendar', $this->lang))->setEvent($this, 'OnCal');
        $this->updateCal();

        $this->add(new Form('editform'))->onSubmit($this, 'OnSave');
        $this->editform->setVisible(false);
        $this->editform->add(new ClickLink('cancel', $this, 'OnCancel'));
        $this->editform->add(new Date('edate', time()));
        $this->editform->add(new \Zippy\Html\Form\Time('etime', time()));

        $this->editform->add(new TextInput('ehours'));
        $this->editform->add(new TextInput('enotes'));
        $this->editform->add(new DropDownChoice('eproject', Project::findArray('project_name', '', 'project_id desc')))->onChange($this, 'OnProject');
        $this->editform->add(new DropDownChoice('eissue'));

        $this->onTab($this->tabc);
    }

    public function onTab($sender) {

        $this->_tvars['tabcbadge'] = $sender->id == 'tabc' ? "badge badge-dark  badge-pill " : "badge badge-light  badge-pill  ";
        $this->_tvars['tabsbadge'] = $sender->id == 'tabs' ? "badge badge-dark  badge-pill " : "badge badge-light  badge-pill  ";;

        $this->listpan->caltab->setVisible($sender->id == 'tabc');
        $this->listpan->tasktab->setVisible($sender->id == 'tabs');
        $this->listpan->tasktab->timelist->Reload();
        $this->updateCal();
    }

    public function OnTimeRow($row) {
        $item = $row->getDataItem();
        $row->add(new Label('date', \App\Helper::fd($item->createdon)));
        $row->add(new Label('time', $item->duration));
        $row->add(new Label('issue', '#' . $item->issue_id . ' ' . $item->issue_name));
        $row->add(new Label('project', $item->project_name));
        $row->add(new Label('notes', $item->notes));
        $row->add(new ClickLink('edit', $this, 'OnEdit'));
        $row->add(new ClickLink('delete', $this, 'OnDelete'));
    }

    public function OnCancel($sender) {
        $this->listpan->setVisible(true);
        $this->editform->setVisible(false);
        $this->editform->clean();
    }

    public function OnAdd($sender) {
        $this->listpan->setVisible(false);
        $this->editform->setVisible(true);
        $this->_tl = new TimeLine();
        $this->editform->edate->setDate(time());
        $this->editform->etime->setDateTime(time());
    }

    public function OnDelete($sender) {
        $item = $sender->getOwner()->getDataItem();
        TimeLine::delete($item->id);
        $this->resetURL();
        $this->listpan->tasktab->timelist->Reload();
        $this->updateCal();
    }

    public function OnEdit($sender) {
        $this->_tl = $sender->getOwner()->getDataItem();
        $this->Open();
    }

    public function Open() {

        $this->editform->eproject->setValue($this->_tl->project_id);
        $this->OnProject($this->editform->eproject);
        $this->editform->eissue->setValue($this->_tl->issue_id);
        $this->editform->ehours->setValue($this->_tl->duration);
        $this->editform->edate->setDate($this->_tl->createdon);
        $this->editform->etime->setDateTime($this->_tl->createdon);
        $this->editform->enotes->setText($this->_tl->notes);

        $this->listpan->setVisible(false);
        $this->editform->setVisible(true);
    }

    public function OnSave($sender) {
        $this->listpan->setVisible(false);
        $this->editform->setVisible(true);

        $issue = $sender->eissue->getValue();
        $h = $sender->ehours->getText();
        if ($issue == 0) {

            $this->setError('nosetissue');
            return;
        }
        if (($h > 0) == false) {

            $this->setError('nosettime');
            return;
        }


        $this->_tl->issue_id = $issue;
        $this->_tl->user_id = System::getUser()->user_id;
        $this->_tl->duration = $h;
        $this->_tl->createdon = $sender->edate->getDate();
        $this->_tl->createdon = $sender->etime->getDateTime($this->_tl->createdon);
        $this->_tl->notes = $sender->enotes->getText();
        $this->_tl->save();

        $sender->eissue->setValue(0);
        $sender->etime->setText('');
        $this->listpan->tasktab->timelist->Reload();
        $this->updateCal();

        $this->listpan->setVisible(true);
        $this->editform->setVisible(false);
    }

    public function OnProject($sender) {
        $id = $sender->getValue();
        $list = Issue::findArray('issue_name', 'project_id=' . $id, 'issue_id desc');
        $opt = array();
        $opt[0] = 'Не выбрана';
        foreach ($list as $k => $v) {
            $opt[$k] = '#' . $k . ' ' . $v;
        }
        $this->editform->eissue->setOptionList($opt);
        $this->editform->eissue->setValue(0);
    }

    public function updateCal() {

        $list = array();
        $items = TimeLine::find('user_id=' . System::getUser()->user_id);

        foreach ($items as $item) {

            $col = 'black';

            $list[] = new \ZCL\Calendar\CEvent($item->id, $item->issue_name, $item->createdon, $item->createdon + (3600 * $item->duration), $col);
        }


        $this->listpan->caltab->calendar->setData($list);
    }

    public function OnCal($sender, $action) {
        if ($action['action'] == 'click') {

            $this->_tl = TimeLine::load($action['id']);

            $this->Open();
            return;
        }
        if ($action['action'] == 'add') {
            $this->_tl = new TimeLine();
            $start = strtotime($action['id'] . ' 09:00');

            $this->OnAdd(null);
            $this->_tl->createdOn = $start;
            return;
        }
        if ($action['action'] == 'move') {
            $tl = TimeLine::load($action['id']);
            $tl->createdon = $tl->createdon + $action['delta'];
            if ($action['years'] <> 0) {
                $tl->createdon = strtotime($action['years'] . ' years', $tl->createdon);
            }
            if ($action['months'] <> 0) {
                $tl->createdon = strtotime($action['months'] . ' months', $tl->createdon);
            }
            if ($action['days'] <> 0) {
                $tl->createdon = strtotime($action['days'] . ' days', $tl->createdon);
            }
            if ($action['ms'] <> 0) {
                $tl->createdon = $tl->createdon + $action['ms'];
            }
            $tl->save();
        }
        if ($action['action'] == 'resize') {
            $tl = TimeLine::load($action['id']);

            if ($action['startdelta'] != 0) {

                $tl->createdon = $tl->createdon + ($action['startdelta'] / 3600);
                $tl->duration = $tl->duration - ($action['startdelta'] / 3600);
            }

            if ($action['enddelta'] != 0) {

                $tl->duration = $tl->duration + ($action['enddelta'] / 3600);
            }
            $tl->save();
        }
        //        $this->updateCal();
        //        $this->listpan->timelist->Reload();
    }

}
