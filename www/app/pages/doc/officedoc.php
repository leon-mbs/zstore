<?php

namespace App\Pages\Doc;

use App\Application as App;
use App\System;
use App\Entity\Doc\Document;
use App\Helper as H;
use Zippy\Html\Label;
use Zippy\Html\Panel;
use Zippy\Html\Form\Button;
use Zippy\Html\Form\Date;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Form\File;
use Zippy\Html\Form\TextArea;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Form\AutocompleteTextInput;
use App\Entity\Customer;
use App\Entity\Employee;
use Zippy\Html\Link\SubmitLink;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\DataList\DataView;
use Zippy\Html\DataList\ArrayDataSource;

/**
 * Страница   офисный документ
 */
class OfficeDoc extends \App\Pages\Base
{
    private $_doc;
    public  $_accshowlist = [];
    public  $_acceditlist = [];
    public  $_accapprlist = [];

    /**
    * @param mixed $docid     редактирование
    * @param mixed $copyid    копия
    * @param mixed $access    открыть страницу  доступов
    * @return mixed
    */
    public function __construct($docid = 0, $copyid = 0, $access = false) {
        parent::__construct();

        $conn = \ZDB\DB::getConnect();
        $names = $conn->GetCol("select distinct notes from documents_view where  meta_name='OfficeDoc' order  by notes");


        $this->add(new Form('docform'));
        $this->docform->add(new TextInput('edittitle'));

        $this->docform->edittitle->setDataList($names);
        $this->docform->add(new TextArea('doccontent'));
        $this->docform->add(new TextArea('editcomment'));
        $this->docform->add(new TextInput('document_number'));
        $this->docform->add(new File('editfile'));
        $this->docform->add(new TextInput('bonus'))->setVisible(false);

        $this->docform->add(new TextInput('fine'))->setVisible(false);
        $this->docform->add(new CheckBox('editaccess'));
        $this->docform->add(new Date('document_date', time()));
        $this->docform->add(new \ZCL\BT\Tags("doctags"));

        $this->docform->add(new DropDownChoice("user", [], 0));



        $emplist = \App\Entity\Employee::findArray("emp_name", "disabled<>1", "emp_name");
        $this->docform->add(new DropDownChoice("emp", $emplist, 0))->onChange($this, 'onEmp');

      
        $this->docform->add(new AutocompleteTextInput('customer'))->onText($this, 'OnAutoCustomer');
        $this->docform->customer->onChange($this, 'OnChangeCustomer');

        $this->docform->add(new SubmitLink('addcust'))->onClick($this, 'addcustOnClick');

        $this->docform->add(new \Zippy\Html\Link\BookmarkableLink('cinfo'))->setVisible(false);


        $this->docform->add(new SubmitButton('savedoc'))->onClick($this, 'savedocOnClick');
        $this->docform->add(new SubmitButton('execdoc'))->onClick($this, 'savedocOnClick');
        $this->docform->add(new SubmitButton('inprocdoc'))->onClick($this, 'savedocOnClick');
        $this->docform->add(new Button('backtolist'))->onClick($this, 'backtolistOnClick');

        //добавление нового контрагента
        $this->add(new Form('editcust'))->setVisible(false);
        $this->editcust->add(new TextInput('editcustname'));
        $this->editcust->add(new TextInput('editphone'));
        $this->editcust->add(new TextInput('editemail'));
        $this->editcust->add(new DropDownChoice('edittype'));
        $this->editcust->add(new Button('cancelcust'))->onClick($this, 'cancelcustOnClick');
        $this->editcust->add(new SubmitButton('savecust'))->onClick($this, 'savecustOnClick');


        //права  доступа
        $this->add(new Form('accessform'))->setVisible(false);
        $this->accessform->add(new Button('cancelaccess'))->onClick($this, 'cancelaccessOnClick');
        $this->accessform->add(new SubmitButton('saveaccess'))->onClick($this, 'saveaccessOnClick');
        $this->accessform->add(new Label('accdocname'));

        $this->accessform->add(new DropDownChoice('accshowusers', [], 0));
        $this->accessform->add(new SubmitButton('addaccshow'))->onClick($this, 'addaccshowOnClick');
        $this->accessform->add(new DataView('accshowlist', new ArrayDataSource(new \Zippy\Binding\PropertyBinding($this, '_accshowlist')), $this, 'accshowOnRow'));
        $this->accessform->add(new DropDownChoice('acceditusers', [], 0));
        $this->accessform->add(new SubmitButton('addaccedit'))->onClick($this, 'addacceditOnClick');
        $this->accessform->add(new DataView('acceditlist', new ArrayDataSource(new \Zippy\Binding\PropertyBinding($this, '_acceditlist')), $this, 'acceditOnRow'));
        $this->accessform->add(new DropDownChoice('accapprusers', [], 0));
        $this->accessform->add(new SubmitButton('addaccappr'))->onClick($this, 'addaccapprOnClick');
        $this->accessform->add(new DataView('accapprlist', new ArrayDataSource(new \Zippy\Binding\PropertyBinding($this, '_accapprlist')), $this, 'accapprOnRow'));


        $user = System::getUser()->user_id;

        if ($docid > 0) {    //загружаем   содержимое  документа на страницу
            $this->_doc = Document::load($docid)->cast();
            $this->docform->document_number->setText($this->_doc->document_number);
            $this->docform->document_date->setDate($this->_doc->document_date);
            $this->docform->edittitle->setText($this->_doc->notes);
            $this->docform->bonus->setText($this->_doc->headerdata['bonus']);
            $this->docform->fine->setText($this->_doc->headerdata['fine']);


            $customer_id = $this->_doc->headerdata['customer'] ?? 0;
            if ($customer_id > 0) {
                $this->docform->customer->setKey($this->_doc->headerdata['customer'] ?? 0);
                $this->docform->customer->setText($this->_doc->headerdata['customer_name'] ?? '');
                $this->OnChangeCustomer($this->docform->customer);

            }
            $emp = $this->_doc->headerdata['employee'] ?? 0;
            if ($emp > 0) {
                $this->docform->emp->setValue($emp);
                $this->onEmp($this->docform->emp);
            }

            $d = $this->_doc->unpackDetails('detaildata');
            $this->docform->doccontent->setText($d['data'] ?? '');
            $this->docform->user->setValue($this->_doc->user_id);


        } else {
            if ($copyid > 0) {
                $cdoc = Document::load($copyid)->cast();
                $this->docform->edittitle->setText($cdoc->notes);
                $d = $cdoc->unpackDetails('detaildata');
                $this->docform->doccontent->setText($d['data'] ?? '');
                $this->docform->user->setValue($user);
                $this->docform->document_number->setText($this->_doc->nextNumber());
                $this->_doc = Document::create('OfficeDoc');
                

            } else {
                $this->_doc = Document::create('OfficeDoc');
                $this->docform->document_number->setText($this->_doc->nextNumber());
                $this->docform->user->setValue($user);

            }
        }
        
        
        $u = array();
    //    $mn = (int)$conn->GetOne("select meta_id from metadata where  meta_name='OfficeDoc' ");

        foreach (\App\Entity\User::find("disabled <> 1", "username asc") as $_u) {
            if ($_u->rolename == 'admins') {
                $u[$_u->user_id] = $_u->username;
            } else {

                if (\App\ACL::checkEditDoc($this->_doc, true, false, $_u->user_id) == true || \App\ACL::checkExeDoc($this->_doc, true, false, $_u->user_id) == true || \App\ACL::checkChangeStateDoc($this->_doc, true, false, $_u->user_id) == true) {
                    $u[$_u->user_id] = $_u->username;
                }


            }
        }
        $this->docform->user->setOptionList($u);        
        
        if ($this->_doc->document_id > 0) {
            $this->docform->doctags->setTags(\App\Entity\Tag::getTags(\App\Entity\Tag::TYPE_OFFICEDCO, (int)$this->_doc->document_id));
        }
        $this->docform->doctags->setSuggestions(\App\Entity\Tag::getSuggestions(\App\Entity\Tag::TYPE_OFFICEDCO));


        $this->docform->editcomment->setVisible($this->_doc->document_id == 0);
        $this->docform->editfile->setVisible($this->_doc->document_id == 0);


        if (false == \App\ACL::checkShowDoc($this->_doc)) {
            return;
        }

        if ($docid > 0 && $access) {
            $this->access();
        }


    }

    public function savedocOnClick($sender) {
        if (false == \App\ACL::checkEditDoc($this->_doc)) {
            return;
        }
        $this->_doc->notes = $this->docform->edittitle->getText();
        if (strlen($this->_doc->notes) == 0) {
            $this->setError('Не введено назву');
            return;
        }

        $this->_doc->document_number = trim($this->docform->document_number->getText());
        $this->_doc->document_date = strtotime($this->docform->document_date->getText());
        $data = $this->docform->doccontent->getText();
        if (strlen($data) == 0)   {
            $this->setError('Не введено текст');
            return;
        }
        $this->_doc->packDetails('detaildata', array('data' => $data));
        $this->_doc->headerdata['bonus'] = $this->docform->bonus->getDouble();
        $this->_doc->headerdata['fine'] = $this->docform->fine->getDouble();




        $customer_id = $this->docform->customer->getKey();
        if ($customer_id > 0) {
            $this->_doc->headerdata['customer'] = $customer_id;
            $this->_doc->headerdata['customer_name'] = $this->docform->customer->getText();
        }


        $emp_id = $this->docform->emp->getValue();
        if ($emp_id > 0) {
            $this->_doc->headerdata['employee'] = $emp_id;
            $this->_doc->headerdata['employee_name'] = $this->docform->emp->getValueName();
        }

        $file = $this->docform->editfile->getFile();
        if ($file['size'] > 10000000) {
            $this->setError("Файл більше 10 МБ!");
            return;
        }

        $isEdited = $this->_doc->document_id > 0;
        if (!$isEdited) {
            $this->_doc->headerdata['author'] = System::getUser()->user_id;
        }
        $this->_doc->user_id = $this->docform->user->getValue();

        $conn = \ZDB\DB::getConnect();
        $conn->BeginTrans();
        try {

            $this->_doc->save();

            if ($sender->id == 'execdoc' || $sender->id == 'inprocdoc') {
                if (!$isEdited) {
                    $this->_doc->updateStatus(Document::STATE_NEW);
                }
                if ($sender->id == 'inprocdoc') {
                    $this->_doc->updateStatus(Document::STATE_INPROCESS);
                }
                if ($sender->id == 'execdoc') {
                    $this->_doc->updateStatus(Document::STATE_FINISHED);
                }
            } else {
                $this->_doc->updateStatus($isEdited ? Document::STATE_EDITED : Document::STATE_NEW);
            }

            $tags = $this->docform->doctags->getTags();

            \App\Entity\Tag::updateTags($tags, \App\Entity\Tag::TYPE_OFFICEDCO, (int)$this->_doc->document_id);


            if (!$isEdited) {   //новый
                $comment = trim($this->docform->editcomment->getText());
                if ($comment != '') {
                    $user = System::getUser();

                    $msg = new \App\Entity\Message();
                    $msg->message = $comment;
                    $msg->created = time();
                    $msg->user_id = $user->user_id;
                    $msg->item_id = $this->_doc->document_id;
                    $msg->item_type = \App\Entity\Message::TYPE_DOC;

                    $msg->save();
                }

                if ($file['size'] > 0) {
                    $id = H::addFile($file, $this->_doc->document_id, 'Оригiнал', \App\Entity\Message::TYPE_DOC);

                }

                if ($this->_doc->user_id != $this->_doc->headerdata['author'])  //не  себе
                {
                    $this->_doc->insertLog($this->_doc->state, $this->_doc->user_id);
                }

            }


            $conn->CommitTrans();

            if ($this->docform->editaccess->isChecked()) {
                $this->access();
            } else {

                $doc = $this->_doc->cast();
                list($s, $wa) = $doc->signed();

                if (in_array($doc->state, [7, 19]) && count($wa) > 0) {
                    $doc->updateStatus(8, true);
                }

                App::Redirect("\\App\\Pages\\Register\\OfficeList");
            }


        } catch(\Throwable $ee) {
            global $logger;
            $conn->RollbackTrans();
            if ($isEdited == false) {
                $this->_doc->document_id = 0;
            }
            $this->setError($ee->getMessage());
            $logger->error('Line '. $ee->getLine().' '.$ee->getFile().'. '.$ee->getMessage()  );


        }
    }


    public function backtolistOnClick($sender) {
        App::RedirectBack();
    }

    public function OnAutoCustomer($sender) {
        return \App\Entity\Customer::getList($sender->getText());
    }

    public function OnChangeCustomer($sender) {

        $customer_id = $this->docform->customer->getKey();

        $this->docform->addcust->setVisible(false);
        $this->docform->cinfo->setVisible(true);
        $this->docform->cinfo->setAttribute('onclick', "customerInfo({$customer_id});");



    }

    //добавление нового контрагента
    public function addcustOnClick($sender) {
        $this->editcust->setVisible(true);
        $this->docform->setVisible(false);
        $this->editcust->clean();
    }

    public function savecustOnClick($sender) {
        $custname = trim($this->editcust->editcustname->getText());
        if (strlen($custname) == 0) {
            $this->setError("Не введено назву");
            return;
        }
        $cust = new \App\Entity\Customer();
        $cust->customer_name = $custname;
        $cust->email = $this->editcust->editemail->getText();
        $cust->phone = $this->editcust->editphone->getText();
        $cust->phone = \App\Util::handlePhone($cust->phone);

        if (strlen($cust->phone) > 0 && strlen($cust->phone) != H::PhoneL()) {
            $this->setError("Довжина номера телефона повинна бути " . \App\Helper::PhoneL() . " цифр");
            return;
        }

        $c = \App\Entity\Customer::getByPhone($cust->phone);
        if ($c != null) {
            if ($c->customer_id != $cust->customer_id) {

                $this->setError("Вже існує контрагент з таким телефоном");
                return;
            }
        }
        $cust->type = $this->editcust->edittype->getValue();
        if ($cust->type == 3) {
            $cust->type = 0;
            $cust->status = 2;
        }
        $cust->save();
        $this->docform->customer->setText($cust->customer_name);
        $this->docform->customer->setKey($cust->customer_id);

        $this->editcust->setVisible(false);
        $this->docform->setVisible(true);

    }

    public function cancelcustOnClick($sender) {
        $this->editcust->setVisible(false);
        $this->docform->setVisible(true);
    }

    public function onEmp($sender) {
        $emp = $sender->getValue();
        $this->docform->bonus->setVisible($emp > 0);
        $this->docform->fine->setVisible($emp > 0);
    }

    //права  доступа
    public function access() {
        $this->docform->setVisible(false);
        $this->accessform->setVisible(true);

        $this->accessform->accdocname->setText(H::fd($this->_doc->document_date) . ' ' . $this->_doc->notes);
        $usersshow = [];
        $usersedit = [];
        $usersappr = [];

        foreach (\App\Entity\User::findYield('disabled<>1', 'username') as $user) {

            if (true == \App\ACL::checkExeDoc($this->_doc, true, false, $user->user_id)) {
                $usersappr[$user->user_id] = $user->username;
            }

            if ($user->user_id == System::getUser()->user_id) {
                continue;
            }
            if ($user->user_id == $this->_doc->user_id) {
                continue;
            }
            if ($user->user_id == $this->_doc->headerdata['author']) {
                continue;
            }


            if ($user->rolename == 'admins') {
                continue;
            }

            if (true == \App\ACL::checkShowDoc($this->_doc, true, false, $user->user_id)) {
                $usersshow[$user->user_id] = $user->username;
            }

            if (true == \App\ACL::checkEditDoc($this->_doc, true, false, $user->user_id) || true == \App\ACL::checkExeDoc($this->_doc, true, false, $user->user_id)) {
                $usersedit[$user->user_id] = $user->username;
            }


        }

        $this->accessform->accshowusers->setOptionList($usersshow);
        $this->accessform->acceditusers->setOptionList($usersedit);
        $this->accessform->accapprusers->setOptionList($usersappr);

        $this->_accshowlist = [];
        $this->_acceditlist = [];
        $this->_accapprlist = [];

        $d = $this->_doc->unpackDetails('accessdata');
        if (is_array($d['showlist'])) {
            $this->_accshowlist = $d['showlist'];
        }
        if (is_array($d['editlist'])) {
            $this->_acceditlist = $d['editlist'];
        }
        if (is_array($d['apprlist'])) {
            $this->_accapprlist = $d['apprlist'];
        }


        $this->accessform->accshowlist->Reload();
        $this->accessform->acceditlist->Reload();
        $this->accessform->accapprlist->Reload();


    }

    public function accshowOnRow($row) {
        $item = $row->getDataItem();
        $row->add(new Label('accshowuser', $item->username));
        $row->add(new ClickLink('delshowuser'))->onClick($this, 'delaccshowOnClick');

    }

    public function addaccshowOnClick($sender) {
        $user_id = $this->accessform->accshowusers->getValue();
        if ($user_id > 0) {
            $username = $this->accessform->accshowusers->getValueName();

            $user = new  \App\DataItem();
            $user->user_id = $user_id;
            $user->username = $username;
            $this->_accshowlist[$user->user_id] = $user;


            $this->accessform->accshowlist->Reload();
        }


    }

    public function delaccshowOnClick($sender) {
        $item = $sender->getOwner()->getDataItem();

        $tmp = [];

        foreach ($this->_accshowlist as $u) {
            if ($u->user_id == $item->user_id) {
                continue;
            }
            $tmp[$u->user_id] = $u;
        }

        $this->_accshowlist = $tmp;

        $this->accessform->accshowlist->Reload();
    }

    public function acceditOnRow($row) {
        $item = $row->getDataItem();
        $row->add(new Label('accedituser', $item->username));
        $row->add(new ClickLink('deledituser'))->onClick($this, 'delacceditOnClick');

    }

    public function addacceditOnClick($sender) {
        $user_id = $this->accessform->acceditusers->getValue();
        if ($user_id > 0) {
            $username = $this->accessform->acceditusers->getValueName();

            $user = new  \App\DataItem();
            $user->user_id = $user_id;
            $user->username = $username;
            $this->_acceditlist[$user->user_id] = $user;


            $this->accessform->acceditlist->Reload();
        }


    }

    public function delacceditOnClick($sender) {
        $item = $sender->getOwner()->getDataItem();

        $tmp = [];

        foreach ($this->_acceditlist as $u) {
            if ($u->user_id == $item->user_id) {
                continue;
            }
            $tmp[$u->user_id] = $u;
        }

        $this->_acceditlist = $tmp;

        $this->accessform->acceditlist->Reload();
    }

    public function accapprOnRow($row) {
        $item = $row->getDataItem();
        $row->add(new Label('accappruser', $item->username));
        $row->add(new ClickLink('delappruser'))->onClick($this, 'delaccapprOnClick');

    }

    public function addaccapprOnClick($sender) {
        $user_id = $this->accessform->accapprusers->getValue();
        if ($user_id > 0) {
            $username = $this->accessform->accapprusers->getValueName();

            $user = new  \App\DataItem();
            $user->user_id = $user_id;
            $user->username = $username;
            $user->signed = false;
            $this->_accapprlist[$user->user_id] = $user;


            $this->accessform->accapprlist->Reload();
        }


    }

    public function delaccapprOnClick($sender) {
        $item = $sender->getOwner()->getDataItem();

        $tmp = [];

        foreach ($this->_accapprlist as $u) {
            if ($u->user_id == $item->user_id) {
                continue;
            }
            $tmp[$u->user_id] = $u;
        }

        $this->_accapprlist = $tmp;

        $this->accessform->accapprlist->Reload();
    }


    public function saveaccessOnClick($sender) {
        if (false == \App\ACL::checkEditDoc($this->_doc)) {
            return;
        }

        $this->_doc->packDetails('accessdata', array(
            'showlist' => $this->_accshowlist,
            'editlist' => $this->_acceditlist,
            'apprlist' => $this->_accapprlist
        ));

        $this->_doc->save();
        $doc = $this->_doc->cast();
        list($s, $wa) = $doc->signed();

        if (in_array($doc->state, [7, 19]) && count($wa) > 0) {
            $doc->updateStatus(8, true);
        }


        App::Redirect("\\App\\Pages\\Register\\OfficeList");
    }

    public function cancelaccessOnClick($sender) {
        App::Redirect("\\App\\Pages\\Register\\OfficeList");
    }


}

