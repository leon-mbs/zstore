<?php

namespace App\Pages\Doc;

use App\Application as App;
use App\Entity\Doc\Document;
use App\Entity\Employee;
use App\Entity\Equipment;
use App\Entity\ProdArea;
use App\Entity\Service;
use App\Entity\Item;
use Zippy\Html\DataList\DataView;
use Zippy\Html\Form\Button;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\TextArea;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Form\Date;
use Zippy\Html\Form\AutocompleteTextInput;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Link\SubmitLink;
use App\Helper as H;

/**
 * Страница  ввода  наряда  на  работу
 */
class Task extends \App\Pages\Base
{
    private $_doc;
    public $_prodlist    = array();
    public $_servicelist = array();
    public $_eqlist      = array();
    public $_emplist     = array();
    private $_basedocid  = 0;


    /**
    * @param mixed $docid      редактирование
    * @param mixed $basedocid  создание на  основании
    * @param mixed $date       дата  с  календаря

    */
    public function __construct($docid = 0, $basedocid = 0, $date = 0 ) {
        parent::__construct();

        $this->add(new Form('docform'));
        $this->docform->add(new TextInput('document_number'));
        $this->docform->add(new Date('document_date'))->setDate(time());
        $this->docform->add(new \Zippy\Html\Form\Time('document_time'))->setDateTime(time());

        $this->docform->add(new TextArea('notes'));
        $this->docform->add(new TextInput('taskhours', "0"));

        $this->docform->add(new AutocompleteTextInput('customer'))->onText($this, 'OnAutoCustomer');

        $this->docform->add(new DropDownChoice('parea', ProdArea::findArray("pa_name", "disabled<>1","pa_name"), 0));

        $this->docform->add(new Button('backtolist'))->onClick($this, 'backtolistOnClick');
        $this->docform->add(new SubmitButton('savedoc'))->onClick($this, 'savedocOnClick');
        $this->docform->add(new SubmitButton('execdoc'))->onClick($this, 'savedocOnClick');

        //service
        $this->add(new Form('editdetail'));
        $this->editdetail->add(new DropDownChoice('editservice', Service::getList()));

        $this->editdetail->add(new TextInput('editqty'));
        $this->editdetail->add(new TextInput('editdesc'));
        $this->editdetail->add(new SubmitButton('saverow'))->onClick($this, 'saverowOnClick');
        //prod
        $this->add(new Form('editdetailprod'));
        $this->editdetailprod->add(new DropDownChoice('editprod', Item::findArray("itemname", "item_type in(4,5) and disabled<>1", "itemname")));

        $this->editdetailprod->add(new TextInput('editqtyprod'));
        $this->editdetailprod->add(new TextInput('editdescprod'));
        $this->editdetailprod->add(new SubmitButton('saverowprod'))->onClick($this, 'saverowprodOnClick');

        //employer
        $this->add(new Form('editdetail3'));
        $this->editdetail3->add(new DropDownChoice('editemp', Employee::findArray("emp_name", "disabled<>1", "emp_name")));
        $this->editdetail3->add(new TextInput('editktu'));
        $this->editdetail3->add(new SubmitButton('saverow3'))->onClick($this, 'saverow3OnClick');

        //equipment
        $this->add(new Form('editdetail4'));
        $this->editdetail4->add(new DropDownChoice('editeq', Equipment::getList('',true)));
        $this->editdetail4->add(new SubmitButton('saverow4'))->onClick($this, 'saverow4OnClick');

        if ($docid > 0) {
            $this->_doc = Document::load($docid)->cast();
            $this->docform->document_number->setText($this->_doc->document_number);
            $this->docform->notes->setText($this->_doc->notes);
            $this->docform->taskhours->setText($this->_doc->headerdata['taskhours']);
            $this->docform->customer->setKey($this->_doc->customer_id);
            $this->docform->customer->setText($this->_doc->customer_name);

            $this->docform->document_date->setDate($this->_doc->document_date);
            $this->docform->document_time->setDateTime($this->_doc->headerdata['start']);
            $this->docform->parea->setValue($this->_doc->headerdata['pa_id']);
                                                                            
            $this->_servicelist = $this->_doc->unpackDetails('detaildata');
            $this->_eqlist = $this->_doc->unpackDetails('eqlist');
            $this->_emplist = $this->_doc->unpackDetails('emplist');
            $this->_prodlist = $this->_doc->unpackDetails('prodlist');
        } else {
            $this->_doc = Document::create('Task');
            $this->docform->document_date->setDate(time());
            if ($date > 0) { //с календаря
                $this->docform->document_date->setDate($date);
                $this->docform->document_time->setDateTime($date);
                $this->docform->taskhours->setText(8);
            }
            $this->docform->document_number->setText($this->_doc->nextNumber());
            if ($basedocid > 0) { //создание на  основании
                $basedoc = Document::load($basedocid);
                if ($basedoc instanceof Document) {
                    $this->_basedocid = $basedocid;
                 //   $this->_doc->customer_id = $basedoc->customer_id;
                    $this->docform->customer->setKey($basedoc->customer_id);
                    $this->docform->customer->setText($basedoc->customer_name);

                    if ($basedoc->meta_name == 'ServiceAct') {
                        $this->docform->notes->setText('Підстава '. $basedoc->document_number);
                        $this->_servicelist = $basedoc->unpackDetails('detaildata');
                    }
                    if ($basedoc->meta_name == 'Order') {
                        $this->docform->notes->setText('Підстава '. $basedoc->document_number);
                        $this->_prodlist = $basedoc->unpackDetails('detaildata');
                    }
                }
            }
       

        
        
        }

        $this->add(new DataView('detail', new \Zippy\Html\DataList\ArrayDataSource(new \Zippy\Binding\PropertyBinding($this, '_servicelist')), $this, 'detailOnRow'))->Reload();
        $this->add(new DataView('detail3', new \Zippy\Html\DataList\ArrayDataSource(new \Zippy\Binding\PropertyBinding($this, '_emplist')), $this, 'detail3OnRow'))->Reload();
        $this->add(new DataView('detail4', new \Zippy\Html\DataList\ArrayDataSource(new \Zippy\Binding\PropertyBinding($this, '_eqlist')), $this, 'detail4OnRow'))->Reload();
        $this->add(new DataView('detailprod', new \Zippy\Html\DataList\ArrayDataSource(new \Zippy\Binding\PropertyBinding($this, '_prodlist')), $this, 'detailprodOnRow'))->Reload();

        if (false == \App\ACL::checkShowDoc($this->_doc)) {
            return;
        }
    }


    public function detailOnRow($row) {
        $service = $row->getDataItem();

        $row->add(new Label('service', $service->service_name));
        $row->add(new Label('category', $service->category));

        $row->add(new Label('quantity', $service->quantity));
        $row->add(new Label('desc', $service->desc));


        $row->add(new ClickLink('delete'))->onClick($this, 'deleteOnClick');
    }


    public function deleteOnClick($sender) {
        if (false == \App\ACL::checkEditDoc($this->_doc)) {
            return;
        }
        $service = $sender->owner->getDataItem();
        $rowid =  array_search($service, $this->_servicelist, true);

        $this->_servicelist = array_diff_key($this->_servicelist, array($rowid => $this->_servicelist[$rowid]));
        $this->detail->Reload();

    }

    public function saverowOnClick($sender) {
        $id = $this->editdetail->editservice->getValue();
        if ($id == 0) {
            $this->setError("Не обрано роботу");
            return;
        }
        $service = Service::load($id);

        $service->quantity = $this->editdetail->editqty->getDouble();
        $service->desc = $this->editdetail->editdesc->getText();
        $service->price = $service->cost;
        if (strlen($service->price) == 0) {
            $service->price = 0;
        }

        $this->_servicelist[] = $service;

        $this->detail->Reload();

        $this->editdetail->clean();
        $this->editdetail->editqty->setText("1");
    }

    //prod

    public function detailprodOnRow($row) {
        $item = $row->getDataItem();

        $row->add(new Label('prod', $item->itemname));
        $row->add(new Label('item_code', $item->item_code));

        $row->add(new Label('quantityprod', $item->quantity));
        $row->add(new Label('descprod', $item->desc));


        $row->add(new ClickLink('deleteprod'))->onClick($this, 'deleteprodOnClick');
    }


    public function deleteprodOnClick($sender) {
        if (false == \App\ACL::checkEditDoc($this->_doc)) {
            return;
        }
        $item = $sender->owner->getDataItem();
        $rowid =  array_search($item, $this->_prodlist, true);

        $this->_prodlist = array_diff_key($this->_prodlist, array($rowid => $this->_prodlist[$rowid]));

        $this->detailprod->Reload();
    }

    public function saverowprodOnClick($sender) {
        $id = $this->editdetailprod->editprod->getValue();
        if ($id == 0) {
            $this->setError("Не обрано продукцію");
            return;
        }
        $item = Item::load($id);

        $item->quantity = $this->editdetailprod->editqtyprod->getDouble();
        $item->desc = $this->editdetailprod->editdescprod->getText();

        if ( doubleval($item->quantity) == 0) {
            $this->setError("Не вказано кількість");
            return;
        }

        $this->_prodlist[ ] = $item;



        $this->detailprod->Reload();

        //очищаем  форму
        $this->editdetailprod->clean();

        $this->editdetailprod->editqtyprod->setText("1");
    }

    //employee

    public function saverow3OnClick($sender) {
        $id = $this->editdetail3->editemp->getValue();
        if ($id == 0) {

            $this->setError("Не обрано виконавця");
            return;
        }
        $emp = Employee::load($id);
        $emp->ktu = $this->editdetail3->editktu->getDouble();
        $this->_emplist[$emp->employee_id] = $emp;
        $this->detail3->Reload();
        $this->editdetail3->clean();
    }

    public function detail3OnRow($row) {
        $emp = $row->getDataItem();

        $row->add(new Label('empname', $emp->emp_name));
        $row->add(new Label('empktu', $emp->ktu));
        $row->add(new ClickLink('delete3'))->onClick($this, 'delete3OnClick');
    }

    public function delete3OnClick($sender) {
        $emp = $sender->owner->getDataItem();
        $this->_emplist = array_diff_key($this->_emplist, array($emp->employee_id => $this->_emplist[$emp->employee_id]));
        $this->detail3->Reload();
    }

    //equipment

    public function saverow4OnClick($sender) {
        $id = $this->editdetail4->editeq->getValue();
        if ($id == 0) {

            $this->setError("Не обрано обладнання");
            return;
        }
        $eq = Equipment::load($id);

        $this->_eqlist[$eq->eq_id] = $eq;
        $this->editdetail4->clean();
        $this->detail4->Reload();
    }

    public function detail4OnRow($row) {
        $eq = $row->getDataItem();

        $row->add(new Label('eq_name', $eq->eq_name));
        $row->add(new Label('eq_code', $eq->serial));
        $row->add(new ClickLink('delete4'))->onClick($this, 'delete4OnClick');
    }

    public function delete4OnClick($sender) {
        $eq = $sender->owner->getDataItem();
        $this->_eqlist = array_diff_key($this->_eqlist, array($eq->eq_id => $this->_eqlist[$eq->eq_id]));
        $this->detail4->Reload();
    }

    public function savedocOnClick($sender) {
        if (false == \App\ACL::checkEditDoc($this->_doc)) {
            return;
        }
        $this->_doc->document_number = $this->docform->document_number->getText();
        $this->_doc->document_date = strtotime($this->docform->document_date->getText());
        $this->_doc->notes = $this->docform->notes->getText();


        $this->_doc->headerdata['pa_id'] = $this->docform->parea->getValue();
        $this->_doc->headerdata['pa_name'] = $this->docform->parea->getValueName();
        $this->_doc->headerdata['start'] = $this->docform->document_time->getDateTime($this->_doc->document_date);
        $this->_doc->headerdata['taskhours'] = $this->docform->taskhours->getText();
        $this->_doc->document_date = $this->docform->document_date->getDate();
        $this->_doc->customer_id = $this->docform->customer->getKey();
        if ($this->_doc->customer_id > 0) {
            $customer = \App\Entity\Customer::load($this->_doc->customer_id);
            $this->_doc->headerdata['customer_name'] = $this->docform->customer->getText();
        }

        if ($this->checkForm() == false) {
            return;
        }

        $this->_doc->packDetails('detaildata', $this->_servicelist);
        $this->_doc->packDetails('eqlist', $this->_eqlist);
        $this->_doc->packDetails('emplist', $this->_emplist);
        $this->_doc->packDetails('prodlist', $this->_prodlist);
        //для поиска
        $this->_doc->headerdata['searchemp'] ='';
        foreach($this->_emplist as $e){
           $this->_doc->headerdata['searchemp'] .= "#{$e->employee_id}#" ;
        }
        
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

            App::Redirect("\\App\\Pages\\Register\\TaskList");

        } catch(\Throwable $ee) {
            global $logger;
            $conn->RollbackTrans();
            if ($isEdited == false) {
                $this->_doc->document_id = 0;
            }
            $this->setError($ee->getMessage());

            $logger->error('Line '. $ee->getLine().' '.$ee->getFile().'. '.$ee->getMessage()  );
            return;
        }
    }

    /**
     * Валидация   формы
     *
     */
    private function checkForm() {
        if (strlen($this->_doc->document_number) == 0) {
            $this->setError('Введіть номер документа');
        }
        if (false == $this->_doc->checkUniqueNumber()) {
            $next = $this->_doc->nextNumber();
            $this->docform->document_number->setText($next);
            $this->_doc->document_number = $next;
            if (strlen($next) == 0) {
                $this->setError('Не створено унікальный номер документа');
            }
        }
        if (strlen($this->_doc->document_date) == 0) {

            $this->setError('Введіть дату документа');
        }
        if (count($this->_servicelist) == 0 && count($this->_prodlist) == 0) {
            $this->setError("Мають бути введені роботи та/або продукція");
        }
        if (count($this->_emplist) > 0) {
            $ktu = 0;
            foreach ($this->_emplist as $emp) {
                $ktu += doubleval($emp->ktu);
            }
            if ($ktu != 1) {
                $this->setError("Сумарний КТУ повинен бути 1");
            }

        }

        return !$this->isError();
    }

    public function backtolistOnClick($sender) {
        App::RedirectBack();
    }

    public function OnAutoCustomer($sender) {
        return \App\Entity\Customer::getList($sender->getText(), 1);
    }

}
