<?php

namespace App\Modules\PPO;

 
use App\Entity\Pos;
use App\Entity\Branch;
use App\Helper as H;
use App\Modules\PPO\PPOHelper;
use App\DataItem;
use App\System;
use Zippy\Binding\PropertyBinding as Prop;
use Zippy\Html\DataList\ArrayDataSource;
use Zippy\Html\DataList\DataView;
use Zippy\Html\DataList\Paginator;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Panel;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use App\Application as App;

class PPOList extends \App\Pages\Base
{
    public $_ppolist = array();
    public $_shlist  = array();
    public $_doclist = array();
    public $_ppo;

    public function __construct() {
        parent::__construct();

        if (strpos(System::getUser()->modules ?? '', 'ppo') === false && System::getUser()->rolename != 'admins') {
            System::setErrorMsg("Немає права доступу до сторінки");

            App::RedirectError();
            return;
        }
        $modules = System::getOptions("modules");

        $this->add(new Panel('opan'));

        $this->opan->add(new Form('filter'))->onSubmit($this, 'filterOnSubmit');
        $this->opan->filter->add(new DropDownChoice('searchcomp', \App\Entity\Pos::findArray('pos_name', '', 'pos_name'), 0));

        $this->opan->add(new DataView('ppolist', new ArrayDataSource(new Prop($this, '_ppolist')), $this, 'ppoOnRow'));

        $this->opan->add(new Paginator('pagp', $this->opan->ppolist));

        $this->opan->ppolist->setPageSize(100); //H::getPG()

        $this->add(new Panel('shpan'))->setVisible(false);
        $this->shpan->add(new ClickLink('backo', $this, 'onBacko'));
        $this->shpan->add(new DataView('shlist', new ArrayDataSource(new Prop($this, '_shlist')), $this, 'shOnRow'));

        $this->add(new Panel('docpan'))->setVisible(false);
        $this->docpan->add(new ClickLink('backsh', $this, 'onBacksh'));
        $this->docpan->add(new Label('docshow'))->setVisible(false);
        $this->docpan->add(new DataView('doclist', new ArrayDataSource(new Prop($this, '_doclist')), $this, 'docOnRow'));
        $this->docpan->doclist->setSelectedClass('table-success');
    }

    public function filterOnSubmit($sender) {
        $this->_ppolist = array();
        $modules = System::getOptions("modules");
        $cid = $this->opan->filter->searchcomp->getValue();
        if ($cid == 0) {
            return;
        }
        $pos = Pos::load($cid);
        $res = PPOHelper::send(json_encode(array('Command' => 'Objects')), 'cmd', $pos);
        if ($res['success'] == false) {
            $this->setErrorTopPage($res['data']);
            return;
        }
        $this->_ppolist = array();
        $res = json_decode($res['data']);
        if (is_array($res->TaxObjects)) {

            foreach ($res->TaxObjects as $item) {
                foreach ($item->TransactionsRegistrars as $tr) {
                    $it = new DataItem(array('org' => $item));
                    $it->tr = $tr;
                    $this->_ppolist[] = $it;
                }
            }

            $this->opan->ppolist->Reload();
        }
    }

    public function ppoOnRow($row) {


        $item = $row->getDataItem();

        $row->add(new Label('name', $item->org->Name));

        $row->add(new Label('org', $item->org->OrgName));
        $row->add(new Label('address', $item->org->Address));
        $row->add(new Label('tin', $item->org->Tin));
        $row->add(new Label('ipn', $item->org->Ipn));
        $row->add(new Label('fn', $item->tr->NumFiscal));
        $row->add(new Label('ln', $item->tr->NumLocal));
        $row->add(new Label('rn', $item->tr->Name));

        $row->add(new ClickLink('objdet', $this, 'onObj'));
    }

    public function onObj($sender) {
        $this->ppo = $sender->getOwner()->getDataItem();

        $this->updateShifts();

        $this->opan->setVisible(false);
        $this->shpan->setVisible(true);
    }

    public function updateShifts() {
        $this->_shlist = array();
        $this->shpan->shlist->Reload();     
   
        $dt = new \App\DateTime();


        $dt = new \App\DateTime();

        $to = $dt->getISO();
        $to = $dt->subMonth(1)->endOfMonth()->getISO();
 
        $from = $dt->subDay(10)->getISO();
 
        $cid = $this->opan->filter->searchcomp->getValue();
        $pos = Pos::load($cid);

        $res = PPOHelper::send(json_encode(array('Command' => 'Shifts', 'NumFiscal' => $this->ppo->tr->NumFiscal, 'From' => $from, 'To' => $to)), 'cmd', $pos);
        if ($res['success'] == false) {
            $this->setErrorTopPage($res['data']);
            return;
        }
        $res = json_decode($res['data']);
        foreach ($res->Shifts as $sh) {
            $it = new DataItem(array('openname'  => $sh->OpenName,
                                     'closename' => $sh->CloseName,
                                     'opened'    => $sh->Opened,
                                     'closed'    => $sh->Closed,
                                     'ShiftId'   => $sh->ShiftId
            ));

            $this->_shlist[] = $it;
        }

        $dt = new \App\DateTime();

        $from = $dt->startOfMonth()->getISO();

        $dt = new \App\DateTime();

        $to = $dt->getISO();

        
        $res = PPOHelper::send(json_encode(array('Command' => 'Shifts', 'NumFiscal' => $this->ppo->tr->NumFiscal, 'From' => $from, 'To' => $to)), 'cmd', $pos);
        if ($res['success'] == false) {
            $this->setErrorTopPage($res['data']);
            return;
        }
        $res = json_decode($res['data']);
        foreach ($res->Shifts as $sh) {
            $it = new DataItem(array('openname'  => $sh->OpenName,
                                     'closename' => $sh->CloseName,
                                     'opened'    => $sh->Opened,
                                     'closed'    => $sh->Closed,
                                     'ShiftId'   => $sh->ShiftId
            ));

            $this->_shlist[] = $it;
        }
        
        
        $this->shpan->shlist->Reload();
    }

    public function onBacko($sender) {
        $this->opan->setVisible(true);
        $this->shpan->setVisible(false);
    }

    public function shOnRow($row) {


        $item = $row->getDataItem();

        $row->add(new Label('openname', $item->openname));
        $row->add(new Label('closename', $item->closename));
        $row->add(new Label('opened', date('Y-m-d H:i', strtotime($item->opened))));
        $cl = strtotime($item->closed);
        $row->add(new Label('closed', $cl > 0 ? date('Y-m-d H:i', $cl) : ''));

        $row->add(new ClickLink('shdet', $this, 'onSh'));
    }

    public function onSh($sender) {
        $sh = $sender->getOwner()->getDataItem();
        $this->_doclist = array();
        $cid = $this->opan->filter->searchcomp->getValue();
        $pos = Pos::load($cid);

        $res = PPOHelper::send(json_encode(array('Command' => 'Documents', 'NumFiscal' => $this->ppo->tr->NumFiscal, 'ShiftId' => $sh->ShiftId)), 'cmd', $pos);
        if ($res['success'] == false) {
            $this->setErrorTopPage($res['data']);
            return;
        }
        $res = json_decode($res['data']);
        foreach ($res->Documents as $doc) {
            $it = new DataItem(array('NumFiscal'    => $doc->NumFiscal,
                                     'NumLocal'     => $doc->NumLocal,
                                     'DocClass'     => $doc->DocClass,
                                     'CheckDocType' => $doc->CheckDocType
            ));

            $this->_doclist[] = $it;
        }

        $this->docpan->doclist->Reload();

        $this->shpan->setVisible(false);
        $this->docpan->setVisible(true);
    }

    public function onBacksh($sender) {
        $this->shpan->setVisible(true);
        $this->docpan->setVisible(false);
        $this->docpan->docshow->setVisible(false);
        $this->updateShifts();
    }

    public function docOnRow($row) {


        $item = $row->getDataItem();

        $row->add(new Label('NumFiscal', $item->NumFiscal));
        $row->add(new Label('NumLocal', $item->NumLocal));
        $row->add(new Label('DocClass', $item->DocClass));
        $row->add(new Label('CheckDocType', $item->CheckDocType));

        $row->add(new ClickLink('docdet', $this, 'onDoc'));
    }

    public function onDoc($sender) {
        $doc = $sender->getOwner()->getDataItem();
        $this->docpan->doclist->setSelectedRow($sender->getOwner());
        $this->docpan->doclist->Reload();

        $cid = $this->opan->filter->searchcomp->getValue();
        $pos = Pos::load($cid);

        $res = PPOHelper::send(json_encode(array('Command' => $doc->DocClass, 'RegistrarNumFiscal' => $this->ppo->tr->NumFiscal, 'NumFiscal' => $doc->NumFiscal)), 'cmd', $pos);
        if ($res['success'] == false) {
            $this->setErrorTopPage($res['data']);
            return;
        }

        $decrypted  = PPOHelper::decrypt($res['data']) ;

        $decrypted = mb_convert_encoding($decrypted, "utf-8", "windows-1251")  ;
        $this->docpan->docshow->setText($decrypted);
        $this->docpan->docshow->setVisible(true);
        $this->goAnkor('docshow');
    }

}
