<?php

namespace App\Pages;

use App\Application as App;
use App\Helper as H;
use App\System;
use Zippy\Binding\PropertyBinding as Bind;
use Zippy\Html\DataList\DataView;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\TextArea;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Label;

class UserProfile extends \App\Pages\Base
{
    public $user;

    public function __construct() {
        parent::__construct();

        $this->user = System::getUser();

        if ($this->user->user_id == 0) {
            App::Redirect("\\App\\Pages\\Userlogin");
            return;
        }

        $form = new Form('profileform');
        $form->onSubmit($this, 'onsubmit');
        $form->add(new Label('userlogin', $this->user->userlogin));
        $form->add(new TextInput('email', $this->user->email));
        $form->add(new TextInput('phone', $this->user->phone));
        $form->add(new TextInput('viber', $this->user->viber));

        $form->add(new CheckBox('darkmode', $this->user->darkmode));
        $form->add(new CheckBox('emailnotify', $this->user->emailnotify));
        $form->add(new CheckBox('botnotify', $this->user->botnotify))->setVisible(strlen($this->user->chat_id)>0);
        $form->add(new CheckBox('usemobileprinter', $this->user->usemobileprinter));
        $form->add(new CheckBox('hidesidebar', $this->user->hidesidebar));
        $form->add(new DropDownChoice('deffirm', \App\Entity\Firm::getList(), $this->user->deffirm));
        $form->add(new DropDownChoice('defstore', \App\Entity\Store::getList(), $this->user->defstore));
        $form->add(new DropDownChoice('defmf', \App\Entity\MoneyFund::getList(), $this->user->defmf));
        $form->add(new DropDownChoice('pagesize', array(15 => 15, 25 => 25, 50 => 50, 100 => 100), $this->user->pagesize));

        $form->add(new DropDownChoice('defsalesource', H::getSaleSources(), $this->user->defsalesource));

        $pages = array();

        foreach (\App\Entity\MetaData::find("meta_type<>1", "meta_type,description") as $m) {
            $p = "";
            $n = "";
            switch($m->meta_type) {


                case 1:
                    $p = "\\App\\Pages\\Doc\\";
                    $n = "Документ";
                    break;
                case 2:
                    $p = "\\App\\Pages\\Report\\";
                    $n = 'Звіт';
                    break;
                case 3:
                    $p = "\\App\\Pages\\Register\\";
                    $n = 'Журнал';
                    break;
                case 4:
                    $p = "\\App\\Pages\\Reference\\";
                    $n = "Довідник";
                    break;
                case 5:
                    $p = "\\App\\Pages\\Service\\";
                    $n = 'Сервісна сторінка';
                    break;
            }

            $p = $p . $m->meta_name;
            $pages[$p] = $n . ' - ' . $m->description;
        }

        $form->add(new DropDownChoice('mainpage', $pages, $this->user->mainpage));

        $this->add($form);

        //форма   пароля

        $form = new Form('passwordform');
        $form->add(new TextInput('userpassword'));
        $form->add(new TextInput('confirmpassword'));
        $form->onSubmit($this, 'onsubmitpass');
        $this->add($form);


        if(strlen($this->user->prtype) == 0) {
            $this->user->prtype = 0 ;
            $this->user->pserver   = "http://127.0.0.1:8080";
            $this->user->pwsym     = 32;
        }

        $form = new Form('printer');
        $form->add(new DropDownChoice('prtype',[], 0))->onChange($this, "onPSType");
        $form->prtype->setValue($this->user->prtype);
        $form->add(new DropDownChoice('pcp',[], $this->user->pcp));


        $form->add(new TextInput('pserver', $this->user->pserver));
        $form->add(new ClickLink('pstest'))->onClick($this, 'onPSTest', true);
        $form->add(new TextInput('pwsym', $this->user->pwsym));
        $form->add(new SubmitButton('savep'))->onClick($this, 'savePrinterOnClick');
        $this->add($form);




        $this->onPSType(null);

        $form = new Form('printerlabel');
        $form->add(new DropDownChoice('prtypelabel', 0))->onChange($this, "onPSTypelabel");
        $form->prtypelabel->setValue($this->user->prtypelabel);
        $form->add(new DropDownChoice('prturn'));
        $form->prturn->setValue($this->user->prturn);
        $form->add(new DropDownChoice('pcplabel',[], $this->user->pcplabel));

        $form->add(new TextInput('pserverlabel', $this->user->pserverlabel));
        $form->add(new ClickLink('pstestlabel'))->onClick($this, 'onPSTestlabel', true);
        $form->add(new TextInput('pwsymlabel', $this->user->pwsymlabel));
        $form->add(new SubmitButton('saveplabel'))->onClick($this, 'savePrinterlabelOnClick');
        $this->add($form);

        $this->onPSTypelabel(null);

    }

    public function onsubmit($sender) {

        $this->user->email = $sender->email->getText();
        $this->user->phone = $sender->phone->getText();
        $this->user->viber = $sender->viber->getText();

        $this->user->darkmode = $sender->darkmode->isChecked() ? 1 : 0;
        $this->user->botnotify = $sender->botnotify->isChecked() ? 1 : 0;
        $this->user->emailnotify = $sender->emailnotify->isChecked() ? 1 : 0;
        $this->user->hidesidebar = $sender->hidesidebar->isChecked() ? 1 : 0;

        $this->user->deffirm = $sender->deffirm->getValue();
        $this->user->defstore = $sender->defstore->getValue();
        $this->user->defmf = $sender->defmf->getValue();
        $this->user->defsalesource = $sender->defsalesource->getValue();
        $this->user->pagesize = $sender->pagesize->getValue();
        $this->user->mainpage = $sender->mainpage->getValue();
        $this->user->usemobileprinter = $this->profileform->usemobileprinter->isChecked() ? 1 : 0;

        if (!$this->isError()) {

            $this->user->save();
            $this->setSuccess('Збережено');
            System::setUser($this->user);
        }
    }

    //записать  пароль
    public function onsubmitpass($sender) {

        $pass = $sender->userpassword->getText();
        $confirm = $sender->confirmpassword->getText();

        if ($pass == '') {
            $this->setError('Введіть пароль');
        } else {
            if ($confirm == '') {
                $this->setError('Підтвердіть пароль');
            } else {
                if ($confirm != $pass) {

                    $this->setError('Невірне підтвердження');
                }
            }
        }


        if (!$this->isError()) {
            $this->user->userpass = (\password_hash($pass, PASSWORD_DEFAULT));
            //  $this->user->userpass = $pass;
            $this->user->save();

            if ($this->user->userlogin != 'admin') {


                \App\Entity\Notify::toSystemLog("Користувач <b>{$this->user->username}</b> змінив пароль на <b>{$pass}</b>") ;

            }

            \App\Helper::logout();


        }


        $sender->userpassword->setText('');
        $sender->confirmpassword->setText('');
    }

    public function onPSType($sender) {
        $prtype = (int)$this->printer->prtype->getValue();
        $this->printer->pserver->setVisible($prtype==1) ;
        $this->printer->pwsym->setVisible($prtype==1) ;
        $this->printer->pstest->setVisible($prtype==1) ;
        $this->printer->pcp->setVisible($prtype!=0) ;


    }
    public function onPSTest($sender) {

        try {

            $pr = new \App\Printer() ;
            $pr->text("Printer test");
            $pr->text("Тест принтера");

            $buf = $pr->getBuffer() ;
            $b = json_encode($buf) ;
            $this->addAjaxResponse(" sendPS('{$b}') ");

        } catch(\Exception $e) {
            $message = $e->getMessage()  ;
            $message = str_replace(";", "`", $message)  ;
            $this->addAjaxResponse(" toastr.error( '{$message}' )         ");

        }

    }

    public function savePrinterOnClick($sender) {



        $this->user->prtype = $this->printer->prtype->getValue() ;
        $this->user->pcp = $this->printer->pcp->getValue() ;
        $this->user->pwsym = trim($this->printer->pwsym->getText());
        $this->user->pserver = trim($this->printer->pserver->getText());
        $this->user->pserver  = rtrim($this->user->pserver, "/") ;

        $this->user->save();
        $this->setSuccess('Збережено');
        System::setUser($this->user);

    }

    public function onPSTypelabel($sender) {
        $prtype = (int)$this->printerlabel->prtypelabel->getValue();
        $this->printerlabel->pserverlabel->setVisible($prtype!=0) ;
        $this->printerlabel->pstestlabel->setVisible($prtype!=0) ;
        $this->printerlabel->pcplabel->setVisible($prtype!=0) ;
        $this->printerlabel->pwsymlabel->setVisible($prtype>0) ;
        $this->printerlabel->prturn->setVisible($prtype==0) ;


    }
    public function onPSTestlabel($sender) {
        $prtype = (int)$this->printerlabel->prtypelabel->getValue();
 
        try {

            $pr = new \App\Printer(true) ;
            if($prtype==1) {
              $pr->text("Printer test");
              $pr->text("Тест принтера");
            }
            if($prtype==2) {
              $pr->labelrow("CLS");
//              $pr->text("CODEPAGE 866");
              $pr->text("DIRECTION 0");
              $pr->labelrow("TEXT 10,10,\"3\",0,1,1,\"Printer test\"");
              $pr->labelrow("FEED 50");
              $pr->labelrow("PRINT 1,1");
            }
            $buf = $pr->getBuffer() ;
            $b = json_encode($buf) ;
            $this->addAjaxResponse(" sendPSlabel('{$b}') ");

        } catch(\Exception $e) {
            $message = $e->getMessage()  ;
            $message = str_replace(";", "`", $message)  ;
            $this->addAjaxResponse(" toastr.error( '{$message}' )         ");

        }

    }

    public function savePrinterlabelOnClick($sender) {



        $this->user->prtypelabel = $this->printerlabel->prtypelabel->getValue() ;
        $this->user->pcplabel = $this->printerlabel->pcplabel->getValue() ;
        $this->user->prturn = $this->printerlabel->prturn->getValue() ;
        $this->user->pwsymlabel = trim($this->printerlabel->pwsymlabel->getText());
        $this->user->pserverlabel = trim($this->printerlabel->pserverlabel->getText());
        $this->user->pserverlabel  = rtrim($this->user->pserverlabel, "/") ;

        $this->user->save();
        $this->setSuccess('Збережено');
        System::setUser($this->user);

    }

}
