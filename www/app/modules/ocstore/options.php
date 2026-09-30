<?php

namespace App\Modules\OCStore;

use App\System;
use Zippy\Binding\PropertyBinding as Prop;
use Zippy\Html\DataList\ArrayDataSource;
use Zippy\Html\DataList\DataView;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\TextArea;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use App\Application as App;

class Options extends \App\Pages\Base
{
    public $_sites  = array();
    public $_siteid = 0;   //сайт  в  форме, 0 - новый

    public function __construct() {
        parent::__construct();

        if (strpos(System::getUser()->modules, 'ocstore') === false && System::getUser()->rolename != 'admins') {
            System::setErrorMsg("Немає права доступу до сторінки");

            App::RedirectError();
            return;
        }

        $form = $this->add(new Form("cform"));
        $form->add(new TextInput('name'));
        $form->add(new CheckBox('sitedisabled'));
        $form->add(new TextInput('site'));
        $form->add(new TextInput('apiname'));
        $form->add(new CheckBox('ssl'));

        $form->add(new CheckBox('insertcust'));

        $form->add(new CheckBox('v4'));
        $form->add(new TextArea('key'));

        $form->add(new DropDownChoice('defpricetype', \App\Entity\Item::getPriceTypeList()));
        $form->add(new DropDownChoice('salesource', \App\Helper::getSaleSources()));
        $form->add(new DropDownChoice('defmf',\App\Entity\MoneyFund::getList()));
        $form->add(new DropDownChoice('defstore',\App\Entity\Store::getList()));

        $pt=[];
        $pt[1] = 'Оплата на стороні IM ';
        $pt[2] = 'Постоплата';
        $pt[3] = 'Оплата касовим  чеком, РФ або ВН';
        $pt[4] = 'Тільки списати зі складу';

        $form->add(new DropDownChoice('defpaytype',$pt));

        $form->add(new SubmitButton('save'))->onClick($this, 'saveOnClick');

        //список  сайтов  показывается  если  их  больше  одного
        $this->add(new ClickLink('addsite'))->onClick($this, 'addOnClick');
        $this->add(new DataView('sitelist', new ArrayDataSource(new Prop($this, '_sites')), $this, 'siteOnRow'));

        $sites = Helper::sites(true);
        $main = Helper::site();
        if ($main == null && count($sites) > 0) {
            $main = reset($sites);
        }
        $this->showSite($main['id'] ?? 0);
    }

    //заполняет  форму  настройками  сайта
    private function showSite($id) {
        $site = Helper::site($id);
        if ($id == 0 || $site == null) {
            $id = 0;
            $site = array();
        }
        $this->_siteid = $id;

        $form = $this->cform;
        $form->name->setText($id > 0 ? $site['name'] : '');
        $form->sitedisabled->setChecked($site['disabled'] ?? 0);
        $form->site->setText($site['site'] ?? '');
        $form->apiname->setText($site['apiname'] ?? '');
        $form->key->setText($site['key'] ?? '');
        $form->ssl->setChecked($site['ssl'] ?? 0);
        $form->v4->setChecked($site['v4'] ?? 0);
        $form->insertcust->setChecked($site['insertcust'] ?? 0);
        $form->defpricetype->setValue($site['pricetype'] ?? 0);
        $form->salesource->setValue($site['salesource'] ?? 0);
        $form->defmf->setValue($site['mf'] ?? 0);
        $form->defstore->setValue($site['storeid'] ?? 0);
        $form->defpaytype->setValue($site['paytype'] ?? 0);

        $this->updateList();
    }

    private function updateList() {
        $this->_sites = array();
        foreach (Helper::sites(true) as $site) {
            $this->_sites[] = new \App\DataItem(array(
                'site_id'  => $site['id'],
                'sitename' => $site['name'],
                'siteaddr' => $site['site'],
                'disabled' => $site['disabled']
            ));
        }
        $this->sitelist->Reload();

        $this->_tvars['ocmulti'] = $this->isMulti();
        $this->_tvars['ocnew'] = count($this->_sites) > 0 && $this->_siteid == 0;
    }

    //сайтов  несколько  или  добавляется  второй
    private function isMulti() {
        $cnt = count(Helper::sites(true));

        return $cnt > 1 || ($cnt == 1 && $this->_siteid == 0);
    }

    public function siteOnRow($row) {
        $site = $row->getDataItem();

        $row->add(new Label('sitename', $site->sitename));
        $row->add(new Label('siteaddr', $site->siteaddr));
        $row->add(new Label('sitestate', $site->disabled == 1 ? 'Вимкнено' : ''));
        $row->add(new ClickLink('siteedit'))->onClick($this, 'editOnClick');
        $row->add(new ClickLink('sitecheck'))->onClick($this, 'checkOnClick');
        $row->add(new ClickLink('sitedel'))->onClick($this, 'delOnClick');
        if ($site->site_id == $this->_siteid) {
            $row->setAttribute('class', 'table-active');
        }
    }

    public function addOnClick($sender) {
        $this->showSite(0);
    }

    public function editOnClick($sender) {
        $this->showSite($sender->owner->getDataItem()->site_id);
    }

    public function checkOnClick($sender) {
        Helper::connect($sender->owner->getDataItem()->site_id);
    }

    public function delOnClick($sender) {
        $id = $sender->owner->getDataItem()->site_id;

        if (Helper::docCount($id) > 0) {
            $this->setError('З цього сайту вже є імпортовані документи. Сайт можна вимкнути');
            return;
        }
        Helper::deleteSite($id);

        $main = Helper::site();
        $this->showSite($this->_siteid == $id ? ($main['id'] ?? 0) : $this->_siteid);
    }

    public function saveOnClick($sender) {
        $site = $this->cform->site->getText();
        $apiname = $this->cform->apiname->getText();
        $key = $this->cform->key->getText();

        $pricetype = $this->cform->defpricetype->getValue();
        $salesource = $this->cform->salesource->getValue();
        $mf = $this->cform->defmf->getValue();
        $store = $this->cform->defstore->getValue();
        $paytype = intval($this->cform->defpaytype->getValue() );

        $ssl = $this->cform->ssl->isChecked() ? 1 : 0;
        $v4 = $this->cform->v4->isChecked() ? 1 : 0;
        $insertcust = $this->cform->insertcust->isChecked() ? 1 : 0;


        if (strlen($pricetype) < 2) {

            $this->setError('Не вказано тип ціни');
            return;
        }

        if ( $paytype==0) {

            $this->setError('Не вказано тип оплати');
            return;
        }
        if ( $paytype==1 && $mf==0) {

            $this->setError('Не вказано касу');
            return;
        }
        if ( $paytype==4 && $store==0) {

            $this->setError('Не вказано склад');
            return;
        }

        $site = trim($site, '/');

        $rec = array();
        $rec['id'] = $this->_siteid;
        $rec['site'] = $site;
        $rec['apiname'] = $apiname;
        $rec['key'] = $key;

        $rec['pricetype'] = $pricetype;
        $rec['salesource'] = $salesource;
        $rec['ssl'] = $ssl;
        $rec['v4'] = $v4;
        $rec['insertcust'] = $insertcust;

        $rec['mf'] = $mf;
        $rec['storeid'] = $store;
        $rec['paytype'] = $paytype;

        if ($this->isMulti()) { //название и  отключение  есть  в  форме  только  если  сайтов  несколько
            $rec['name'] = trim($this->cform->name->getText());
            $rec['disabled'] = $this->cform->sitedisabled->isChecked() ? 1 : 0;
        }

        $id = Helper::saveSite($rec);

        $this->setSuccess('Збережено');

        $this->showSite($id);

        if (($rec['disabled'] ?? 0) == 0) {
            \App\Modules\OCStore\Helper::connect($id);
        }

    }

}
//2Ru8ToJTb4ZoH8qgk1oh64mSRVC2chloDDSeD2SMY8g1n1JJ8dlXGUwF06FZl2qUmrQF0H8Kru7gSpW7O4kHRd2zX2wUGUUqBd2joQQbS0cP8frArUFxgNBCBppRUjlbZqbhaAhBaIPQUA24ykK7DjjsVKALcaYXr6RqmPCcmAEvHqMRwE088O00hx8F2ANoUrxCVHifygaTh4K2bdXCkVTVefiaDdeEaBCsAIW4ctrXZmLhtUUF8kmFdvVnXeTh