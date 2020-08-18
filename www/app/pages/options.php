<?php

namespace App\Pages;

use App\Application as App;
use App\Helper as H;
use App\System;
use Zippy\Html\DataList\DataView;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\File;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Panel;

class Options extends \App\Pages\Base
{

    private $metadatads;
    public $pricelist = array();

    public function __construct() {
        parent::__construct();
        if (System::getUser()->userlogin != 'admin') {
            System::setErrorMsg(H::l('onlyadminpage'));
            App::RedirectHome();
            return false;
        }

        $this->add(new Form('common'))->onSubmit($this, 'saveCommonOnClick');
        $this->common->add(new DropDownChoice('qtydigits'));
        $this->common->add(new DropDownChoice('amdigits'));
        $this->common->add(new DropDownChoice('dateformat'));
        $this->common->add(new DropDownChoice('curr', array('gr' => 'Гривна', 'ru' => 'Рубль'), 'gr'));
        $pt = array(
            "1" => H::l('opt_lastprice'),
            "2" => H::l('opt_partion')
        );
        $this->common->add(new DropDownChoice('partiontype', $pt, "1"));

        $this->common->add(new CheckBox('autoarticle'));
        $this->common->add(new CheckBox('usesnumber'));
        $this->common->add(new CheckBox('useset'));
        $this->common->add(new CheckBox('useval'));
        $this->common->add(new TextInput('shopname'));
        
        $this->common->add(new CheckBox('useimages'));
        $this->common->add(new CheckBox('usescanner'));
        $this->common->add(new CheckBox('usebranch'));
        $this->common->add(new CheckBox('allowminus'));
        $this->common->add(new CheckBox('capcha'));
        $this->common->add(new TextInput('price1'));
        $this->common->add(new TextInput('price2'));
        $this->common->add(new TextInput('price3'));
        $this->common->add(new TextInput('price4'));
        $this->common->add(new TextInput('price5'));
        $this->common->add(new TextInput('defprice'));
       
        $this->common->add(new TextInput('ts_break'));
        $this->common->add(new TextInput('ts_start' ));
        $this->common->add(new TextInput('ts_end' ));
    
        $common = System::getOptions("common");
        if (!is_array($common)) {
            $common = array();
        }

        $this->common->qtydigits->setValue($common['qtydigits']);
        $this->common->amdigits->setValue($common['amdigits']);
        $this->common->dateformat->setValue($common['dateformat']);
        $this->common->partiontype->setValue($common['partiontype']);
        $this->common->curr->setValue($common['curr']);

        $this->common->price1->setText($common['price1']);
        $this->common->price2->setText($common['price2']);
        $this->common->price3->setText($common['price3']);
        $this->common->price4->setText($common['price4']);
        $this->common->price5->setText($common['price5']);
        $this->common->defprice->setText($common['defprice']);
        $this->common->shopname->setText($common['shopname']);

        $this->common->autoarticle->setChecked($common['autoarticle']);
        $this->common->useset->setChecked($common['useset']);

        $this->common->usesnumber->setChecked($common['usesnumber']);

        
        $this->common->usescanner->setChecked($common['usescanner']);
        $this->common->useimages->setChecked($common['useimages']);
        $this->common->usebranch->setChecked($common['usebranch']);
        $this->common->allowminus->setChecked($common['allowminus']);
        $this->common->capcha->setChecked($common['capcha']);
        $this->common->useval->setChecked($common['useval']);

        $this->common->ts_break->setText($common['ts_break'] == null ? '60' : $common['ts_break']);
        $this->common->ts_start->setText($common['ts_start']== null ? '09:00' : $common['ts_start']);
        $this->common->ts_end->setText($common['ts_end']== null ? '18:00' : $common['ts_end']);

        $this->add(new Form('valform'))->onSubmit($this, 'saveValOnClick');
        $this->valform->add(new TextInput('valuan'));
        $this->valform->add(new TextInput('valusd'));
        $this->valform->add(new TextInput('valeuro'));
        $this->valform->add(new TextInput('valrub'));
        $this->valform->add(new CheckBox('valprice'));

        $val = System::getOptions("val");
        if (!is_array($val)) {
            $val = array();
        }
        $this->valform->valuan->setText($val['valuan']);
        $this->valform->valusd->setText($val['valusd']);
        $this->valform->valeuro->setText($val['valeuro']);
        $this->valform->valrub->setText($val['valrub']);
        $this->valform->valprice->setChecked($val['valprice']);

        $this->add(new Form('printer'))->onSubmit($this, 'savePrinterOnClick');
        $this->printer->add(new TextInput('pwidth'));
        $this->printer->add(new DropDownChoice('pricetype', \App\Entity\Item::getPriceTypeList()));
        $this->printer->add(new DropDownChoice('barcodetype', array('EAN13' => 'EAN-13', 'EAN8' => 'EAN-8', 'C128' => 'Code128', 'C39' => 'Code39'), 'EAN13'));
        $this->printer->add(new DropDownChoice('pfontsize', array('12'=>'12','14'=>'14','16'=>'16','20'=>'20','24'=>'24','28'=>'28','36'=>'36',), '16'));
        $this->printer->add(new CheckBox('pname'));
        $this->printer->add(new CheckBox('pcode'));
        $this->printer->add(new CheckBox('pbarcode'));
        $this->printer->add(new CheckBox('pprice'));

        $printer = System::getOptions("printer");
        if (!is_array($printer)) {
            $printer = array();
        }

        $this->printer->pwidth->setText($printer['pwidth']);
        $this->printer->pricetype->setValue($printer['pricetype']);
        $this->printer->barcodetype->setValue($printer['barcodetype']);
        $this->printer->pfontsize->setValue($printer['pfontsize']);
        $this->printer->pname->setChecked($printer['pname']);
        $this->printer->pcode->setChecked($printer['pcode']);
        $this->printer->pbarcode->setChecked($printer['pbarcode']);
        $this->printer->pprice->setChecked($printer['pprice']);

        $this->metadatads = new \ZCL\DB\EntityDataSource("\\App\\Entity\\MetaData", "", "description");

        $this->add(new Panel('listpan'));
        $this->listpan->add(new Form('filter'))->onSubmit($this, 'filterOnSubmit');
        $this->listpan->filter->add(new CheckBox('fdoc'))->setChecked(true);
        $this->listpan->filter->add(new CheckBox('fref'))->setChecked(true);
        $this->listpan->filter->add(new CheckBox('frep'))->setChecked(true);
        $this->listpan->filter->add(new CheckBox('freg'))->setChecked(true);
        $this->listpan->filter->add(new CheckBox('fser'))->setChecked(true);

        $this->listpan->add(new ClickLink('addnew'))->onClick($this, 'addnewOnClick');
        $this->listpan->add(new DataView('metarow', $this->metadatads, $this, 'metarowOnRow'))->Reload();

        $this->add(new Panel('editpan'))->setVisible(false);
        $this->editpan->add(new Form('editform'))->onSubmit($this, 'editformOnSubmit');
        $this->editpan->editform->add(new TextInput('meta_id'));
        $this->editpan->editform->add(new TextInput('edit_description'));
        $this->editpan->editform->add(new TextInput('edit_meta_name'));
        $this->editpan->editform->add(new TextInput('edit_menugroup'));

        $this->editpan->editform->add(new CheckBox('edit_disabled'));

        $this->editpan->editform->add(new DropDownChoice('edit_meta_type', \App\Entity\MetaData::getNames()));
        $this->editpan->add(new ClickLink('mcancel'))->onClick($this, 'mcancelOnClick');
    }


    public function saveCommonOnClick($sender) {
        $common = array();

        $common['qtydigits'] = $this->common->qtydigits->getValue();
        $common['amdigits'] = $this->common->amdigits->getValue();
        $common['dateformat'] = $this->common->dateformat->getValue();
        $common['partiontype'] = $this->common->partiontype->getValue();
        $common['curr'] = $this->common->curr->getValue();

        $common['price1'] = $this->common->price1->getText();
        $common['price2'] = $this->common->price2->getText();
        $common['price3'] = $this->common->price3->getText();
        $common['price4'] = $this->common->price4->getText();
        $common['price5'] = $this->common->price5->getText();
        $common['shopname'] = $this->common->shopname->getText();
        $common['ts_break'] = $this->common->ts_break->getText();
        $common['ts_start'] = $this->common->ts_start->getText();
        $common['ts_end'] = $this->common->ts_end->getText();

        $common['autoarticle'] = $this->common->autoarticle->isChecked() ? 1 : 0;
        $common['useset'] = $this->common->useset->isChecked() ? 1 : 0;

        $common['usesnumber'] = $this->common->usesnumber->isChecked() ? 1 : 0;
        $common['usescanner'] = $this->common->usescanner->isChecked() ? 1 : 0;
        $common['useimages'] = $this->common->useimages->isChecked() ? 1 : 0;
        
        $common['usebranch'] = $this->common->usebranch->isChecked() ? 1 : 0;
        $common['allowminus'] = $this->common->allowminus->isChecked() ? 1 : 0;
        $common['useval'] = $this->common->useval->isChecked() ? 1 : 0;
        $common['capcha'] = $this->common->capcha->isChecked() ? 1 : 0;


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
        $val['valprice'] = $this->valform->valprice->isChecked() ? 1 : 0;

        System::setOptions("val", $val);
        $this->setSuccess('saved');
    }

    public function savePrinterOnClick($sender) {
        $printer = array();
        $printer['pwidth'] = $this->printer->pwidth->getText();
        $printer['pricetype'] = $this->printer->pricetype->getValue();
        $printer['barcodetype'] = $this->printer->barcodetype->getValue();
        $printer['pfontsize'] = $this->printer->pfontsize->getValue();
        $printer['pname'] = $this->printer->pname->isChecked() ? 1 : 0;
        $printer['pcode'] = $this->printer->pcode->isChecked() ? 1 : 0;
        $printer['pbarcode'] = $this->printer->pbarcode->isChecked() ? 1 : 0;
        $printer['pprice'] = $this->printer->pprice->isChecked() ? 1 : 0;

        System::setOptions("printer", $printer);
        $this->setSuccess('saved');
    }

    public function filterOnSubmit($sender) {

        $where = "1<>1 ";
        if ($this->listpan->filter->fdoc->isChecked()) {
            $where .= " or meta_type = 1";
        }
        if ($this->listpan->filter->frep->isChecked()) {
            $where .= " or meta_type = 2";
        }
        if ($this->listpan->filter->freg->isChecked()) {
            $where .= " or meta_type = 3";
        }
        if ($this->listpan->filter->fref->isChecked()) {
            $where .= " or meta_type = 4";
        }
        if ($this->listpan->filter->fser->isChecked()) {
            $where .= " or meta_type = 5";
        }


        $this->metadatads->setWhere($where);

        $this->listpan->metarow->Reload();
    }

    public function addnewOnClick($sender) {
        $this->listpan->setVisible(false);
        $this->editpan->setVisible(true);
        //   $this->editpan->editform->eipan->setVisible(false);
        $this->editpan->editform->meta_id->setText(0);
        $this->editpan->editform->edit_description->setText('');
        $this->editpan->editform->edit_menugroup->setText('');

        $this->editpan->editform->edit_disabled->setChecked(0);
    }

    public function mcancelOnClick($sender) {
        $this->listpan->setVisible(true);
        $this->editpan->setVisible(false);
    }

    public function metarowOnRow($row) {
        $item = $row->getDataItem();
        $row->setAttribute('style', $item->disabled == 1 ? 'color: #aaa' : null);
        switch ($item->meta_type) {
            case 1:
                $title = H::l('md_doc');
                break;
            case 2:
                $title = H::l('md_rep');
                break;
            case 3:
                $title = H::l('md_reg');
                break;
            case 4:
                $title = H::l('md_ref');
                break;
            case 5:
                $title = H::l('md_ser');
                break;
        }

        $row->add(new Label('description', $item->description));
        $row->add(new Label('meta_name', $item->meta_name));
        $row->add(new Label('menugroup', $item->menugroup));
        $row->add(new Label('type', $title));
        $row->add(new ClickLink('rowedit'))->onClick($this, 'roweditOnClick');
        $row->add(new ClickLink('rowdelete'))->onClick($this, 'rowdeleteOnClick');
    }

    public function roweditOnClick($sender) {

        $item = $sender->getOwner()->getDataItem();
        $form = $this->editpan->editform;
        $form->meta_id->setText($item->meta_id);
        $form->edit_description->setText($item->description);
        $form->edit_meta_name->setText($item->meta_name);
        $form->edit_menugroup->setText($item->menugroup);
        $form->edit_meta_type->setValue($item->meta_type);
        $form->edit_disabled->setChecked($item->disabled == 1);


        $this->listpan->setVisible(false);
        $this->editpan->setVisible(true);
    }

    public function rowdeleteOnClick($sender) {
        $item = $sender->getOwner()->getDataItem();
        \App\Entity\MetaData::delete($item->meta_id);

        $this->listpan->metarow->Reload();
    }

    public function editformOnSubmit($sender) {

        $meta_id = $this->editpan->editform->meta_id->getText();
        if ($meta_id > 0) {
            $item = \App\Entity\MetaData::load($meta_id);
        } else {
            $item = new \App\Entity\MetaData();
        }

        $item->description = $this->editpan->editform->edit_description->getText();
        $item->menugroup = trim($this->editpan->editform->edit_menugroup->getText());
        $item->meta_name = trim(ucfirst($this->editpan->editform->edit_meta_name->getText()));
        $item->meta_type = $this->editpan->editform->edit_meta_type->getValue();
        $item->disabled = $this->editpan->editform->edit_disabled->isChecked() ? 1 : 0;


        $item->save();

        $this->listpan->setVisible(true);
        $this->editpan->setVisible(false);

        $this->listpan->metarow->Reload();
        //обнуляем  поля   формы
        $this->editpan->editform->edit_description->setText('');
        $this->editpan->editform->edit_meta_name->setText('');
        $this->editpan->editform->edit_menugroup->setText('');
    }

}
