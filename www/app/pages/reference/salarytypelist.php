<?php

namespace App\Pages\Reference;

use App\Entity\SalType;
use Zippy\Html\DataList\DataView;
use Zippy\Html\Form\Button;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\TextArea;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Label;
use Zippy\Html\Link\SubmitLink;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Panel;
use App\Helper as H;
use App\System;

//начисления  удержания
class SalaryTypeList extends \App\Pages\Base
{
    private $_st;

    public function __construct() {
        parent::__construct();
        if (false == \App\ACL::checkShowRef('SalaryTypeList')) {
            return;
        }

        $this->add(new Panel('tablepan'));
        $this->tablepan->add(new DataView('stlist', new \ZCL\DB\EntityDataSource('\App\Entity\SalType', '', 'salcode'), $this, 'listOnRow'))->Reload();
        $this->tablepan->add(new ClickLink('addnew'))->onClick($this, 'addOnClick');


        $this->add(new Form('editform'))->setVisible(false);
        $this->editform->add(new TextInput('editstname'));
        $this->editform->add(new TextInput('editshortname'));
        $this->editform->add(new TextInput('editcode'));

        $this->editform->add(new CheckBox('editdisabled'));
        $this->editform->add(new SubmitButton('save'))->onClick($this, 'saveOnClick');
        $this->editform->add(new Button('cancel'))->onClick($this, 'cancelOnClick');
        $this->editform->add(new Button('delete'))->onClick($this, 'deleteOnClick');

        $opt = System::getOptions("salary");

        $this->add(new Form('calcform'));
        $this->calcform->add(new TextArea('calc', $opt['calc']??''));
        $this->calcform->add(new TextArea('calcbase', $opt['calcbase']??''));
        $this->calcform->add(new SubmitLink('savecalc'))->onClick($this, "onSaveCalc", true);


        $this->add(new Form('optform'));
        $this->optform->add(new DropDownChoice('optbaseincom', SalType::getList(), $opt['codebaseincom']??''));
        $this->optform->add(new DropDownChoice('optadvance', SalType::getList(), $opt['codeadvance']??''));
        $this->optform->add(new DropDownChoice('optresult', SalType::getList(), $opt['coderesult']??''));
        $this->optform->add(new DropDownChoice('optfine', SalType::getList(), $opt['codefine']??''));
        $this->optform->add(new DropDownChoice('optbonus', SalType::getList(), $opt['codebonus']??''));
        $this->optform->add(new SubmitLink('saveopt'))->onClick($this, "onSaveOpt", true);


    }

    public function listOnRow($row) {
        $item = $row->getDataItem();

        $row->add(new Label('stname', $item->salname));
        $row->add(new Label('shortname', $item->salshortname));
        $row->add(new Label('code', $item->salcode));
        $row->add(new ClickLink('edit'))->onClick($this, 'editOnClick');
        $row->setAttribute('style', $item->disabled == 1 ? 'color: #aaa' : null);
        
    }

    public function deleteOnClick($sender) {
        if (false == \App\ACL::checkDelRef('SalaryTypeList')) {
            return;
        }

        $del = SalType::delete($this->_st->st_id);
        if (strlen($del) > 0) {
            $this->setError($del);
            return;
        }
        $this->tablepan->stlist->Reload();
        $this->tablepan->setVisible(true);
        $this->editform->setVisible(false);

    }

    public function editOnClick($sender) {
        $this->_st = $sender->owner->getDataItem();
        $this->tablepan->setVisible(false);
        $this->editform->setVisible(true);
        $this->editform->editstname->setText($this->_st->salname);
        $this->editform->editshortname->setText($this->_st->salshortname);
        $this->editform->editdisabled->setChecked($this->_st->disabled);
        $this->editform->editcode->setText($this->_st->salcode);
    }

    public function addOnClick($sender) {
        $this->tablepan->setVisible(false);
        $this->editform->setVisible(true);
        // Очищаем  форму
        $this->editform->clean();

        $this->_st = new SalType();
    }

    public function saveOnClick($sender) {
        if (false == \App\ACL::checkEditRef('SalaryTypeList')) {
            return;
        }

        $isnew = $this->_st->salcode == 0;
        $this->_st->salname = $this->editform->editstname->getText();
        $this->_st->salshortname = $this->editform->editshortname->getText();
        $this->_st->salcode = $this->editform->editcode->getText();
        $this->_st->disabled = $this->editform->editdisabled->ischecked() ? 1 : 0;

        $code = intval($this->_st->salcode);
        if ($code < 100 || $code > 999) {
            $this->setError('Невірний код');
            return;
        }
        $c = SalType::getFirst("salcode=" . $this->_st->salcode);
        if ($c != null && $isnew) {
            $this->setError('Код вже існує');
            return;

        }

        $this->_st->save();
        $this->editform->setVisible(false);
        $this->tablepan->setVisible(true);
        $this->tablepan->stlist->Reload();


        $sl = SalType::getList();
        $codebaseincom = $this->optform->optbaseincom->getValue();
        $codeadvance = $this->optform->optadvance->getValue();
        $coderesult = $this->optform->optresult->getValue();

        if($codebaseincom==0) {
           $this->setError('Не вказано поле  основної зарплати') ;
           return;
        }
        
        $this->optform->optbaseincom->setOptionList($sl);
        $this->optform->optresult->setOptionList($sl);
        $this->optform->optadvance->setOptionList($sl);
        //восстанавливаем значение
        $this->optform->optbaseincom->setValue($codebaseincom);
        $this->optform->optresult->setValue($coderesult);
        $this->optform->optadvance->setValue($codeadvance);


    }

    public function cancelOnClick($sender) {
        $this->tablepan->setVisible(true);
        $this->editform->setVisible(false);
    }

    public function onSaveOpt($sender) {
        $opt = System::getOptions("salary");
        $opt['codebaseincom'] = $this->optform->optbaseincom->getValue();
        $opt['coderesult'] = $this->optform->optresult->getValue();
        $opt['codeadvance'] = $this->optform->optadvance->getValue();
        $opt['codefine'] = $this->optform->optfine->getValue();
        $opt['codebonus'] = $this->optform->optbonus->getValue();
        if($opt['codebaseincom']==0) {
           $this->addAjaxResponse("toastr.error('Не вказано поле  основної зарплати')");
           
           return;
        }
        System::setOptions('salary', $opt);

        $this->addAjaxResponse("toastr.success('Збережено')");

    }

    public function onSaveCalc($sender) {
        $opt = System::getOptions("salary");
        $opt['calc'] = $this->calcform->calc->getText();
        $opt['calcbase'] = $this->calcform->calcbase->getText();
        System::setOptions('salary', $opt);


        $this->addAjaxResponse("toastr.success('Збережено')");

    }

}
