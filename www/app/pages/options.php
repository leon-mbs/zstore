<?php

namespace App\Pages;

use App\Application as App;
use App\Helper as H;
use App\System;
use Zippy\Binding\PropertyBinding as Bind;
use Zippy\Html\DataList\ArrayDataSource;
use Zippy\Html\DataList\DataView;

use Zippy\Html\Form\CheckBox;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\File;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Link\SubmitLink;

use Zippy\Html\Panel;

class Options extends \App\Pages\Base
{

    private $metadatads;
    public  $pricelist        = array();
    public  $_salesourceslist = array();

    public function __construct() {
        parent::__construct();
        if (System::getUser()->userlogin != 'admin') {
            System::setErrorMsg(H::l('onlyadminpage'));
            App::RedirectError();
            return false;
        }

        $this->add(new Form('common'))->onSubmit($this, 'saveCommonOnClick');
        $this->common->add(new DropDownChoice('qtydigits'));
        $this->common->add(new DropDownChoice('amdigits'));
        $this->common->add(new DropDownChoice('dateformat'));
        $this->common->add(new DropDownChoice('curr', array('gr' => 'Гривня', 'ru' => 'Рубль', 'eu' => 'EURO', 'us' => 'USD'), 'gr'));
        $this->common->add(new DropDownChoice('phonel', array('10' => '10', '12' => '12'), '10'));
        $pt = array(
            "1" => H::l('opt_lastprice'),
            "2" => H::l('opt_partion')
        );
        $this->common->add(new DropDownChoice('partiontype', $pt, "1"));

        $this->common->add(new CheckBox('autoarticle'));
        $this->common->add(new CheckBox('usesnumber'));

        $this->common->add(new CheckBox('useval'));
        $this->common->add(new TextInput('shopname'));

        $this->common->add(new CheckBox('useimages'));
        $this->common->add(new CheckBox('usescanner'));
        $this->common->add(new CheckBox('usemobilescanner'));
        $this->common->add(new CheckBox('usebranch'));
        $this->common->add(new CheckBox('usecattree'));
        $this->common->add(new CheckBox('showactiveusers'));
        $this->common->add(new CheckBox('showchat'));


        
        $this->common->add(new CheckBox('printoutqrcode'));
        $this->common->add(new CheckBox('nocheckarticle'));
        $this->common->add(new CheckBox('exportxlsx'));
        $this->common->add(new CheckBox('allowminus'));
        $this->common->add(new CheckBox('noallowfiz'));
        $this->common->add(new CheckBox('capcha'));
        $this->common->add(new CheckBox('numberttn'));
        $this->common->add(new TextInput('price1'));
        $this->common->add(new TextInput('price2'));
        $this->common->add(new TextInput('price3'));
        $this->common->add(new TextInput('price4'));
        $this->common->add(new TextInput('price5'));
        $this->common->add(new TextInput('defprice'));

        $this->common->add(new TextInput('ts_break'));
        $this->common->add(new TextInput('ts_start'));
        $this->common->add(new TextInput('ts_end'));
        $this->common->add(new TextInput('checkslogan'));

        $common = System::getOptions("common");
        if (!is_array($common)) {
            $common = array();
        }

        $this->common->qtydigits->setValue($common['qtydigits']);
        $this->common->amdigits->setValue($common['amdigits']);
        $this->common->dateformat->setValue($common['dateformat']);
        $this->common->partiontype->setValue($common['partiontype']);
        $this->common->curr->setValue($common['curr']);
        $this->common->phonel->setValue($common['phonel']);

        $this->common->price1->setText($common['price1']);
        $this->common->price2->setText($common['price2']);
        $this->common->price3->setText($common['price3']);
        $this->common->price4->setText($common['price4']);
        $this->common->price5->setText($common['price5']);
        $this->common->defprice->setText($common['defprice']);
        $this->common->shopname->setText($common['shopname']);

        $this->common->autoarticle->setChecked($common['autoarticle']);

        $this->common->usesnumber->setChecked($common['usesnumber']);


        
        $this->common->printoutqrcode->setChecked($common['printoutqrcode']);
        $this->common->nocheckarticle->setChecked($common['nocheckarticle']);
        $this->common->exportxlsx->setChecked($common['exportxlsx']);
        $this->common->showactiveusers->setChecked($common['showactiveusers']);
        $this->common->showchat->setChecked($common['showchat']);
        $this->common->usecattree->setChecked($common['usecattree']);
        $this->common->usescanner->setChecked($common['usescanner']);
        $this->common->usemobilescanner->setChecked($common['usemobilescanner']);
        $this->common->useimages->setChecked($common['useimages']);
        $this->common->usebranch->setChecked($common['usebranch']);
        $this->common->noallowfiz->setChecked($common['noallowfiz']);
        $this->common->allowminus->setChecked($common['allowminus']);
        $this->common->capcha->setChecked($common['capcha']);
        $this->common->numberttn->setChecked($common['numberttn']);
        $this->common->useval->setChecked($common['useval']);

        $this->common->ts_break->setText($common['ts_break'] == null ? '60' : $common['ts_break']);
        $this->common->ts_start->setText($common['ts_start'] == null ? '09:00' : $common['ts_start']);
        $this->common->ts_end->setText($common['ts_end'] == null ? '18:00' : $common['ts_end']);
        $this->common->checkslogan->setText($common['checkslogan']);

        //валюты
        $this->add(new Form('valform'))->onSubmit($this, 'saveValOnClick');
        $this->valform->add(new TextInput('valuan'));
        $this->valform->add(new TextInput('valusd'));
        $this->valform->add(new TextInput('valeuro'));
        $this->valform->add(new TextInput('valrub'));
        $this->valform->add(new TextInput('valmdl'));
        $this->valform->add(new CheckBox('valprice'));

        $val = System::getOptions("val");
        if (!is_array($val)) {
            $val = array();
        }
        $this->valform->valuan->setText($val['valuan']);
        $this->valform->valusd->setText($val['valusd']);
        $this->valform->valeuro->setText($val['valeuro']);
        $this->valform->valrub->setText($val['valrub']);
        $this->valform->valmdl->setText($val['valmdl']);
        $this->valform->valprice->setChecked($val['valprice']);

        //печать
        $this->add(new Form('printer'))->onSubmit($this, 'savePrinterOnClick');
        $this->printer->add(new TextInput('pa4width'));
        $this->printer->add(new TextInput('pwidth'));
        $this->printer->add(new TextInput('pdocwidth'));
        $this->printer->add(new TextInput('pheight'));
        $this->printer->add(new TextInput('pmaxname'));
        $this->printer->add(new DropDownChoice('pricetype', \App\Entity\Item::getPriceTypeList()));
        $this->printer->add(new DropDownChoice('barcodetype', array('EAN13' => 'EAN-13', 'EAN8' => 'EAN-8', 'C128' => 'Code128', 'C39' => 'Code39'), 'Code128'));
        $this->printer->add(new DropDownChoice('pdocfontsize', array('12' => '12', '14' => '14', '16' => '16', '20' => '20', '24' => '24', '28' => '28', '36' => '36',), '16'));
        $this->printer->add(new DropDownChoice('pfontsize', array('12' => '12', '14' => '14', '16' => '16', '20' => '20', '24' => '24', '28' => '28', '36' => '36',), '16'));
        $this->printer->add(new CheckBox('pname'));
        $this->printer->add(new CheckBox('pcode'));
        $this->printer->add(new CheckBox('pbarcode'));
        $this->printer->add(new CheckBox('pprice'));
        $this->printer->add(new CheckBox('pqrcode'));
        $this->printer->add(new CheckBox('pcolor'));

        $printer = System::getOptions("printer");
        if (!is_array($printer)) {
            $printer = array();
        }

        $this->printer->pa4width->setText($printer['pa4width']);
        $this->printer->pwidth->setText($printer['pwidth']);
        $this->printer->pdocwidth->setText($printer['pdocwidth']);
        $this->printer->pheight->setText($printer['pheight']);
        $this->printer->pmaxname->setText($printer['pmaxname']);
        $this->printer->pricetype->setValue($printer['pricetype']);
        $this->printer->barcodetype->setValue($printer['barcodetype']);
        $this->printer->pfontsize->setValue($printer['pfontsize']);
        $this->printer->pdocfontsize->setValue($printer['pdocfontsize']);
        $this->printer->pname->setChecked($printer['pname']);
        $this->printer->pcode->setChecked($printer['pcode']);
        $this->printer->pbarcode->setChecked($printer['pbarcode']);
        $this->printer->pqrcode->setChecked($printer['pqrcode']);
        $this->printer->pprice->setChecked($printer['pprice']);
        $this->printer->pcolor->setChecked($printer['pcolor']);

        //API
        $this->add(new Form('api'))->onSubmit($this, 'saveApiOnClick');

        $this->api->add(new TextInput('akey'));
        $this->api->add(new TextInput('aexp'));
        $this->api->add(new DropDownChoice('atype', array('1' => H::l('apijwt'), '2' => H::l('apibasic'), '3' => H::l('apinologin')), 1))->onChange($this, 'onApiType');
        $api = System::getOptions("api");
        if (!is_array($api)) {
            $api = array('exp' => 60, 'key' => 'qwerty', 'atype' => 1);
        }
        $this->api->akey->setText($api['key']);
        $this->api->aexp->setValue($api['exp']);
        $this->api->atype->setValue($api['atype']);

        $this->onApiType($this->api->atype);

        //SMS
        $this->add(new Form('sms'));

        $this->sms->add(new SubmitButton('smssubmit'))->onClick($this, 'saveSMSOnClick');
        $this->sms->add(new SubmitButton('smstest'))->onClick($this, 'testSMSOnClick');
        $this->sms->add(new Label('semysmssite'));
        $this->sms->add(new Label('turbosmssite'));
        $this->sms->add(new Label('smsflysite'));
        $this->sms->add(new TextInput('turbosmstoken'));
        $this->sms->add(new TextInput('smssemytoken'));
        $this->sms->add(new TextInput('smssemydevid'));
        $this->sms->add(new TextInput('smstestphone'));
        $this->sms->add(new TextInput('smstesttext'));
        $this->sms->add(new TextInput('flysmslogin'));
        $this->sms->add(new TextInput('flysmspass'));
        $this->sms->add(new TextInput('flysmsan'));
        $this->sms->add(new DropDownChoice('smstype', array('1' => "SemySMS", /* '2' => "TurboSMS", */ '3' => 'SMS-Fly'), 0))->onChange($this, 'onSMSType');
        $sms = System::getOptions("sms");

        $this->sms->smssemytoken->setText($sms['smssemytoken']);
        $this->sms->smssemydevid->setValue($sms['smssemydevid']);
        $this->sms->flysmslogin->setText($sms['flysmslogin']);
        $this->sms->flysmspass->setValue($sms['flysmspass']);
        $this->sms->flysmsan->setValue($sms['flysmsan']);
        $this->sms->turbosmstoken->setValue($sms['turbosmstoken']);

        $this->sms->smstype->setValue($sms['smstype']);

        $this->onSMSType($this->sms->smstype);

        //общепит
        $food = System::getOptions("food");
        if (!is_array($food)) {
            $food = array();
        }
        $this->add(new Form('food'))->onSubmit($this, 'onFood');
        $this->food->add(new DropDownChoice('foodpricetype', \App\Entity\Item::getPriceTypeList(), $food['pricetype']));
        $this->food->add(new DropDownChoice('foodworktype', array(), $food['worktype']));
        $this->food->add(new CheckBox('fooddelivery', $food['delivery']));
        $this->food->add(new CheckBox('foodtables', $food['tables']));
        $this->food->add(new CheckBox('foodpack', $food['pack']));

        //источники  продаж
        $this->add(new Form('salesourcesform'));
        $this->salesourcesform->add(new SubmitButton('salesourcesave'))->onClick($this, 'OnSaveSaleSource');
        $this->salesourcesform->add(new SubmitLink('addnewsalesource'))->onClick($this, 'OnAddSaleSource');

        $this->salesourcesform->add(new DataView('salesourceslist', new ArrayDataSource(new Bind($this, '_salesourceslist')), $this, 'salesourceListOnRow'));

        $this->_salesourceslist = $common['salesources'];
        if (is_array($this->_salesourceslist) == false) {
            $this->_salesourceslist = array();
        }

        $this->salesourcesform->salesourceslist->Reload();

    }

    public function saveCommonOnClick($sender) {
        $common = System::getOptions("common");
        if (!is_array($common)) {
            $common = array();
        }

        $common['qtydigits'] = $this->common->qtydigits->getValue();
        $common['amdigits'] = $this->common->amdigits->getValue();
        $common['dateformat'] = $this->common->dateformat->getValue();
        $common['partiontype'] = $this->common->partiontype->getValue();
        $common['curr'] = $this->common->curr->getValue();
        $common['phonel'] = $this->common->phonel->getValue();

        $common['price1'] = $this->common->price1->getText();
        $common['price2'] = $this->common->price2->getText();
        $common['price3'] = $this->common->price3->getText();
        $common['price4'] = $this->common->price4->getText();
        $common['price5'] = $this->common->price5->getText();
        $common['defprice'] = $this->common->defprice->getText();
        $common['shopname'] = $this->common->shopname->getText();
        $common['ts_break'] = $this->common->ts_break->getText();
        $common['ts_start'] = $this->common->ts_start->getText();
        $common['ts_end'] = $this->common->ts_end->getText();
        $common['checkslogan'] = $this->common->checkslogan->getText();

        $common['autoarticle'] = $this->common->autoarticle->isChecked() ? 1 : 0;

        $common['usesnumber'] = $this->common->usesnumber->isChecked() ? 1 : 0;
        $common['usescanner'] = $this->common->usescanner->isChecked() ? 1 : 0;
        $common['usemobilescanner'] = $this->common->usemobilescanner->isChecked() ? 1 : 0;
        $common['useimages'] = $this->common->useimages->isChecked() ? 1 : 0;

        $common['printoutqrcode'] = $this->common->printoutqrcode->isChecked() ? 1 : 0;
        
        $common['nocheckarticle'] = $this->common->nocheckarticle->isChecked() ? 1 : 0;
        $common['exportxlsx'] = $this->common->exportxlsx->isChecked() ? 1 : 0;


        $common['showactiveusers'] = $this->common->showactiveusers->isChecked() ? 1 : 0;
        $common['showchat'] = $this->common->showchat->isChecked() ? 1 : 0;
        $common['usecattree'] = $this->common->usecattree->isChecked() ? 1 : 0;
        $common['usebranch'] = $this->common->usebranch->isChecked() ? 1 : 0;
        $common['noallowfiz'] = $this->common->noallowfiz->isChecked() ? 1 : 0;
        $common['allowminus'] = $this->common->allowminus->isChecked() ? 1 : 0;
        $common['useval'] = $this->common->useval->isChecked() ? 1 : 0;
        $common['capcha'] = $this->common->capcha->isChecked() ? 1 : 0;
        $common['numberttn'] = $this->common->numberttn->isChecked() ? 1 : 0;

        System::setOptions("common", $common);

        $this->_tvars["useval"] = $common['useval'] == 1;

        $this->setSuccess('saved');
        System::setCache('labels', null);
    }

    public function saveValOnClick($sender) {
        $val = array();
        $val['valuan'] = $this->valform->valuan->getText();
        $val['valusd'] = $this->valform->valusd->getText();
        $val['valeuro'] = $this->valform->valeuro->getText();
        $val['valrub'] = $this->valform->valrub->getText();
        $val['valmdl'] = $this->valform->valmdl->getText();
        $val['valprice'] = $this->valform->valprice->isChecked() ? 1 : 0;

        System::setOptions("val", $val);
        $this->setSuccess('saved');
    }

    public function savePrinterOnClick($sender) {
        $printer = array();
        $printer['pheight'] = $this->printer->pheight->getText();
        $printer['pa4width'] = $this->printer->pa4width->getText();
        $printer['pwidth'] = $this->printer->pwidth->getText();
        $printer['pdocwidth'] = $this->printer->pdocwidth->getText();
        $printer['pmaxname'] = $this->printer->pmaxname->getText();
        $printer['pricetype'] = $this->printer->pricetype->getValue();
        $printer['barcodetype'] = $this->printer->barcodetype->getValue();
        $printer['pfontsize'] = $this->printer->pfontsize->getValue();
        $printer['pdocfontsize'] = $this->printer->pdocfontsize->getValue();
        $printer['pname'] = $this->printer->pname->isChecked() ? 1 : 0;
        $printer['pcode'] = $this->printer->pcode->isChecked() ? 1 : 0;
        $printer['pbarcode'] = $this->printer->pbarcode->isChecked() ? 1 : 0;
        $printer['pqrcode'] = $this->printer->pqrcode->isChecked() ? 1 : 0;
        $printer['pprice'] = $this->printer->pprice->isChecked() ? 1 : 0;
        $printer['pcolor'] = $this->printer->pcolor->isChecked() ? 1 : 0;

        System::setOptions("printer", $printer);
        $this->setSuccess('saved');
    }

    public function onApiType($sender) {
        $type = $this->api->atype->getValue();
        $this->api->aexp->setVisible($type == 1);
        $this->api->akey->setVisible($type == 1);

        $this->goAnkor('atype');
    }

    public function saveApiOnClick($sender) {
        $api = array();
        $api['exp'] = $this->api->aexp->getText();
        $api['key'] = $this->api->akey->getText();
        $api['atype'] = $this->api->atype->getValue();

        System::setOptions("api", $api);
        $this->setSuccess('saved');
    }

    public function onSMSType($sender) {
        $type = $this->sms->smstype->getValue();
        $this->sms->smssemytoken->setVisible($type == 1);
        $this->sms->smssemydevid->setVisible($type == 1);
        $this->sms->turbosmstoken->setVisible($type == 2);
        $this->sms->flysmslogin->setVisible($type == 3);
        $this->sms->flysmspass->setVisible($type == 3);
        $this->sms->flysmsan->setVisible($type == 3);

        $this->sms->semysmssite->setVisible($type == 1);
        $this->sms->turbosmssite->setVisible($type == 2);
        $this->sms->smsflysite->setVisible($type == 3);

        //  $this->goAnkor('atype');
    }

    public function saveSMSOnClick($sender) {
        $sms = array();
        $sms['turbosmstoken'] = $this->sms->turbosmstoken->getText();
        $sms['smssemytoken'] = $this->sms->smssemytoken->getText();
        $sms['smssemydevid'] = $this->sms->smssemydevid->getText();
        $sms['flysmslogin'] = $this->sms->flysmslogin->getText();
        $sms['flysmspass'] = $this->sms->flysmspass->getText();
        $sms['flysmsan'] = $this->sms->flysmsan->getText();
        $sms['smstype'] = $this->sms->smstype->getValue();

        System::setOptions("sms", $sms);
        $this->setSuccess('saved');
    }


    public function testSMSOnClick($sender) {

        $res = \App\Entity\Subscribe::sendSMS($this->sms->smstestphone->getText(), $this->sms->smstesttext->getText());
        if (strlen($res) == 0) {
            $this->setSuccess('success');
        } else {
            $this->setError($res);
        }
    }

    public function onFood($sender) {
        $food = array();
        $food['worktype'] = $sender->foodworktype->getValue();
        $food['pricetype'] = $sender->foodpricetype->getValue();
        $food['delivery'] = $sender->fooddelivery->isChecked() ? 1 : 0;
        $food['tables'] = $sender->foodtables->isChecked() ? 1 : 0;

        $food['pack'] = $sender->foodpack->isChecked() ? 1 : 0;


        System::setOptions("food", $food);
        $this->setSuccess('saved');
    }


    public function OnAddSaleSource($sender) {
        $ls = new \App\DataItem();
        $ls->name = '';
        $ls->id = time();
        $this->_salesourceslist[$ls->id] = $ls;
        $this->salesourcesform->salesourceslist->Reload();
        $this->goAnkor('salesourcesform');
    }


    public function salesourceListOnRow($row) {
        $item = $row->getDataItem();
        $row->add(new TextInput('salesourcename', new Bind($item, 'name')));
        $row->add(new ClickLink('delsalesource', $this, 'onDelSaleSource'));
    }

    public function onDelSaleSource($sender) {
        $item = $sender->getOwner()->getDataItem();

        $this->_salesourceslist = array_diff_key($this->_salesourceslist, array($item->id => $this->_salesourceslist[$item->id]));

        $this->salesourcesform->salesourceslist->Reload();
        $this->goAnkor('salesourcesform');
    }

    public function OnSaveSaleSource($sender) {

        $options = System::getOptions('common');
        $options['salesources'] = $this->_salesourceslist;
        System::setOptions('common', $options);

        $this->setSuccess('saved');
    }
}
