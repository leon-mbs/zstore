<?php

namespace App\Pages\Doc;

use App\Application as App;
use App\Entity\Doc\Document;
use App\Entity\Employee;
use App\Entity\Equipment;
use App\Entity\Prodarea;
use App\Entity\Service;
use Zippy\Html\DataList\DataView;
use Zippy\Html\Form\Button;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\TextArea;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Link\SubmitLink;

/**
 * Страница  ввода  наряда  на  работу
 */
class Task extends \App\Pages\Base
{

    private $_doc;
    public $_servicelist = array();
    public $_eqlist = array();
    public $_emplist = array();
    private $_basedocid = 0;

    public function __construct($docid = 0, $basedocid = 0, $date = null) {
        parent::__construct();


        $this->add(new Form('docform'));
        $this->docform->add(new TextInput('document_number'));
        $this->docform->add(new \ZCL\BT\DatePicker('document_date'))->setDate(time());

        $this->docform->add(new TextArea('notes'));
        $this->docform->add(new TextInput('taskhours', "0"));

        $this->docform->add(new DropDownChoice('parea', Prodarea::findArray("pa_name", ""), 0));

        $this->docform->add(new SubmitLink('addservice'))->onClick($this, 'addserviceOnClick');

        $this->docform->add(new SubmitLink('addeq'))->onClick($this, 'addeqOnClick');
        $this->docform->add(new SubmitLink('addemp'))->onClick($this, 'addempOnClick');
        $this->docform->add(new Button('backtolist'))->onClick($this, 'backtolistOnClick');
        $this->docform->add(new SubmitButton('savedoc'))->onClick($this, 'savedocOnClick');
        $this->docform->add(new SubmitButton('execdoc'))->onClick($this, 'savedocOnClick');

        //service
        $this->add(new Form('editdetail'))->setVisible(false);
        $this->editdetail->add(new DropDownChoice('editservice', Service::findArray("service_name", "disabled<>1", "service_name")))->onChange($this, 'OnChangeServive', true);

        $this->editdetail->add(new TextInput('edithours'));
        $this->editdetail->add(new Button('cancelrow'))->onClick($this, 'cancelrowOnClick');
        $this->editdetail->add(new SubmitButton('saverow'))->onClick($this, 'saverowOnClick');

        //employer
        $this->add(new Form('editdetail3'))->setVisible(false);
        $this->editdetail3->add(new DropDownChoice('editemp', Employee::findArray("emp_name", "disabled<>1", "emp_name")));
        $this->editdetail3->add(new Button('cancelrow3'))->onClick($this, 'cancelrowOnClick');
        $this->editdetail3->add(new SubmitButton('saverow3'))->onClick($this, 'saverow3OnClick');


        //equipment
        $this->add(new Form('editdetail4'))->setVisible(false);
        $this->editdetail4->add(new DropDownChoice('editeq', Equipment::findArray("eq_name", "disabled<>1", "eq_name")));
        $this->editdetail4->add(new Button('cancelrow4'))->onClick($this, 'cancelrowOnClick');
        $this->editdetail4->add(new SubmitButton('saverow4'))->onClick($this, 'saverow4OnClick');


        if ($docid > 0) {    //загружаем   содержимок  документа настраницу
            $this->_doc = Document::load($docid)->cast();
            $this->docform->document_number->setText($this->_doc->document_number);
            $this->docform->notes->setText($this->_doc->notes);
            $this->docform->taskhours->setText($this->_doc->headerdata['taskhours']);

      
            $this->docform->document_date->setDate($this->_doc->document_date);
            $this->docform->parea->setValue($this->_doc->headerdata['parea']);


            $this->_servicelist = $this->_doc->unpackDetails('detaildata');
            $this->_eqlist = $this->_doc->unpackDetails('eqlist');
            $this->_emplist = $this->_doc->unpackDetails('emplist');
        } else {
            $this->_doc = Document::create('Task');
            $this->docform->document_date->setDate(time());
            if ($date > 0) { //с календаря
                $this->docform->start_date->setDate($date);
                $this->docform->taskhours->setText(7);
            }
            $this->docform->document_number->setText($this->_doc->nextNumber());
            if ($basedocid > 0) { //создание на  основании
                $basedoc = Document::load($basedocid);
                if ($basedoc instanceof Document) {
                    $this->_basedocid = $basedocid;
                    if ($basedoc->meta_name == 'ServiceAct') {


                        $this->docform->notes->setText("Заказ " . $basedoc->document_number);
                        $this->_servicelist = $basedoc->unpackDetails('detaildata');
                    }
                }
            }
        }

        $this->docform->add(new DataView('detail', new \Zippy\Html\DataList\ArrayDataSource(new \Zippy\Binding\PropertyBinding($this, '_servicelist')), $this, 'detailOnRow'))->Reload();
        $this->docform->add(new DataView('detail3', new \Zippy\Html\DataList\ArrayDataSource(new \Zippy\Binding\PropertyBinding($this, '_emplist')), $this, 'detail3OnRow'))->Reload();
        $this->docform->add(new DataView('detail4', new \Zippy\Html\DataList\ArrayDataSource(new \Zippy\Binding\PropertyBinding($this, '_eqlist')), $this, 'detail4OnRow'))->Reload();


        if (false == \App\ACL::checkShowDoc($this->_doc)) {
            return;
        }
    }

    public function cancelrowOnClick($sender) {
        $this->editdetail->setVisible(false);

        $this->editdetail3->setVisible(false);
        $this->editdetail4->setVisible(false);

        $this->docform->setVisible(true);
    }

    public function detailOnRow($row) {
        $service = $row->getDataItem();

        $row->add(new Label('service', $service->service_name));


        $row->add(new Label('hours', $service->hours));

        $row->add(new ClickLink('edit'))->onClick($this, 'editOnClick');
        $row->add(new ClickLink('delete'))->onClick($this, 'deleteOnClick');
    }

    public function addserviceOnClick($sender) {
        $this->editdetail->setVisible(true);
        $this->docform->setVisible(false);


        $this->editdetail->editservice->setValue(0);


        $this->editdetail->edithours->setText('');
    }

    public function editOnClick($sender) {
        $service = $sender->getOwner()->getDataItem();
        $this->editdetail->setVisible(true);
        $this->docform->setVisible(false);


        $this->editdetail->edithours->setText($service->hours);

        $this->editdetail->editservice->setValue($service->service_id);
    }

    public function deleteOnClick($sender) {
        if (false == \App\ACL::checkEditDoc($this->_doc)) {
            return;
        }
        $service = $sender->owner->getDataItem();

        $this->_servicelist = array_diff_key($this->_servicelist, array($service->service_id => $this->_servicelist[$service->service_id]));
        $this->docform->detail->Reload();
    }

    public function saverowOnClick($sender) {
        $id = $this->editdetail->editservice->getValue();
        if ($id == 0) {
            $this->setError("noseljob");
            return;
        }
        $service = Service::load($id);


        $service->hours = $this->editdetail->edithours->getText();


        $this->_servicelist[$service->service_id] = $service;
        $this->editdetail->setVisible(false);
        $this->docform->setVisible(true);


        $this->docform->detail->Reload();

        //очищаем  форму
        $this->editdetail->editservice->setValue(0);

        $this->editdetail->edithours->setText("0");
    }

    //employee
    public function addempOnClick($sender) {
        $this->editdetail3->setVisible(true);
        $this->docform->setVisible(false);

        $this->editdetail3->editemp->setValue(0);
    }

    public function saverow3OnClick($sender) {
        $id = $this->editdetail3->editemp->getValue();
        if ($id == 0) {

            $this->setError("noselexecutor");
            return;
        }
        $emp = Employee::load($id);

        $this->_emplist[$emp->employee_id] = $emp;
        $this->editdetail3->setVisible(false);
        $this->docform->setVisible(true);
        $this->docform->detail3->Reload();
    }

    public function detail3OnRow($row) {
        $emp = $row->getDataItem();

        $row->add(new Label('empname', $emp->emp_name));
        $row->add(new ClickLink('delete3'))->onClick($this, 'delete3OnClick');
    }

    public function delete3OnClick($sender) {
        $emp = $sender->owner->getDataItem();
        $this->_emplist = array_diff_key($this->_emplist, array($emp->employee_id => $this->_emplist[$emp->employee_id]));
        $this->docform->detail3->Reload();
    }

    //equipment
    public function addeqOnClick($sender) {
        $this->editdetail4->setVisible(true);
        $this->docform->setVisible(false);

        $this->editdetail4->editeq->setValue(0);
    }

    public function saverow4OnClick($sender) {
        $id = $this->editdetail4->editeq->getValue();
        if ($id == 0) {

            $this->setError("noseleq");
            return;
        }
        $eq = Equipment::load($id);

        $this->_eqlist[$eq->eq_id] = $eq;
        $this->editdetail4->setVisible(false);
        $this->docform->setVisible(true);
        $this->docform->detail4->Reload();
    }

    public function detail4OnRow($row) {
        $eq = $row->getDataItem();

        $row->add(new Label('eq_name', $eq->eq_name));
        $row->add(new ClickLink('delete4'))->onClick($this, 'delete4OnClick');
    }

    public function delete4OnClick($sender) {
        $eq = $sender->owner->getDataItem();
        $this->_eqlist = array_diff_key($this->_eqlist, array($eq->eq_id => $this->_eqlist[$eq->eq_id]));
        $this->docform->detail4->Reload();
    }

    public function savedocOnClick($sender) {
        if (false == \App\ACL::checkEditDoc($this->_doc)) {
            return;
        }
        $this->_doc->document_number = $this->docform->document_number->getText();
        $this->_doc->document_date = strtotime($this->docform->document_date->getText());
        $this->_doc->notes = $this->docform->notes->getText();

        $this->_doc->headerdata['parea'] = $this->docform->parea->getValue();
        $this->_doc->headerdata['pareaname'] = $this->docform->parea->getValueName();
        $this->_doc->headerdata['taskhours'] = $this->docform->taskhours->getText();
        $this->_doc->document_date = $this->docform->document_date->getDate();


        if ($this->checkForm() == false) {
            return;
        }


        $this->_doc->packDetails('detaildata', $this->_servicelist);
        $this->_doc->packDetails('eqlist', $this->_eqlist);
        $this->_doc->packDetails('emplist', $this->_emplist);

        $isEdited = $this->_doc->document_id > 0;


        $conn = \ZDB\DB::getConnect();
        $conn->BeginTrans();
        try {
            if ($this->_basedocid > 0) {
                $this->_doc->parent_id = $this->_basedocid;
                $this->_basedocid = 0;
            }

            $this->_doc->save();

            if ($sender->id == 'execdoc') {
                if (!$isEdited) {
                    $this->_doc->updateStatus(Document::STATE_NEW);
                }

                //  $this->_doc->updateStatus(Document::STATE_EXECUTED);
                $this->_doc->updateStatus(Document::STATE_INPROCESS);

                $this->_doc->save();
            } else {
                $this->_doc->updateStatus($isEdited ? Document::STATE_EDITED : Document::STATE_NEW);
            }

            $conn->CommitTrans();
            if ($isEdited) {
                App::RedirectBack();
            } else {
                App::Redirect("\\App\\Pages\\Register\\TaskList");
            }
        } catch (\Exception $ee) {
            global $logger;
            $conn->RollbackTrans();
            $this->setError($ee->getMessage());

            $logger->error($ee->getMessage() . " Документ " . $this->_doc->meta_desc);
            return;
        }
    }

    /**
     * Валидация   формы
     *
     */
    private function checkForm() {
        if (strlen($this->_doc->document_number) == 0) {
            $this->setError('enterdocnumber');
        }
        if (false == $this->_doc->checkUniqueNumber()) {
            $this->docform->document_number->setText($this->_doc->nextNumber());
            $this->setError('nouniquedocnumber_created');
        }
        if (strlen($this->_doc->document_date) == 0) {

            $this->setError('enterdatedoc');
        }
        if (count($this->_servicelist) == 0) {
            $this->setError("noenterpos");
        }


        return !$this->isError();


    }

    public function backtolistOnClick($sender) {
        App::RedirectBack();
    }

    public function OnChangeServive($sender) {
        $id = $sender->getValue();

        $item = Service::load($id);


        //   $this->editdetail->editprice->setText($price);
        $this->editdetail->edithours->setText($item->hours);
        $this->updateAjax(array('edithours'));
    }

}
