<?php

namespace App\Pages;

use App\Application as App;
use App\Helper;
use App\Session;
use App\System;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;

class Base extends \Zippy\Html\WebPage
{

    public $branch_id = 0;
     
    
    public function __construct($params = null) {
        global $_config;
 

        \Zippy\Html\WebPage::__construct();

        $user = System::getUser();
        if ($user->user_id == 0) {
            App::Redirect("\\App\\Pages\\Userlogin");
            return;
        }

        $this->_tvars['curversion'] = System::CURR_VERSION ;
     
 
        $options = System::getOptions('common');

        //опции
        $this->_tvars["usesnumber"] = $options['usesnumber'] == 1;
        $this->_tvars["usescanner"] = $options['usescanner'] == 1 || $options['usemobilescanner'] == 1;
        $this->_tvars["usemobilescanner"] = $options['usemobilescanner'] == 1;
        $this->_tvars["useimages"] = $options['useimages'] == 1;
        $this->_tvars["usebranch"] = $options['usebranch'] == 1;
        $this->_tvars["useval"] = $options['useval'] == 1;
        $this->_tvars["usecattree"] = $options['usecattree'] == 1;
        $this->_tvars["usemobileprinter"] = $user->usemobileprinter == 1;
        $this->_tvars["noshowpartion"] = System::getUser()->noshowpartion;
        $this->_tvars["showsidemenu"] = !(System::getUser()->hidemenu == true);
        $this->_tvars["twodigit"] = round($options['amdigits']) > 0;


        $blist = array();
        if ($this->_tvars["usebranch"] == true) {
            $this->branch_id = System::getBranch();
            $blist = \App\Entity\Branch::getList(System::getUser()->user_id);
            if (count($blist) == 1) {
                $k = array_keys($blist); //если  одна
                $this->branch_id = array_pop($k);
                System::setBranch($this->branch_id);
            }

            //куки  после  логина
            if (System::getSession()->defbranch > 0 && $this->branch_id === null) {
                $this->branch_id = System::getSession()->defbranch;
                System::setBranch($this->branch_id);
            }
            if ($this->branch_id == null) {
                $this->branch_id = 0;
            }

        }
        //форма  филиалов       
        $this->add(new \Zippy\Html\Form\Form('nbform'));
        $this->nbform->add(new \Zippy\Html\Form\DropDownChoice('nbbranch', $blist, $this->branch_id))->onChange($this, 'onnbFirm');

        $this->add(new ClickLink('logout', $this, 'LogoutClick'));
        $this->add(new Label('username', $user->username));
        //меню
        $this->_tvars["docmenu"] = Helper::generateMenu(1);
        $this->_tvars["repmenu"] = Helper::generateMenu(2);
        $this->_tvars["regmenu"] = Helper::generateMenu(3);
        $this->_tvars["refmenu"] = Helper::generateMenu(4);
        $this->_tvars["sermenu"] = Helper::generateMenu(5);

        $this->_tvars["showdocmenu"] = count($this->_tvars["docmenu"]['groups']) > 0 || count($this->_tvars["docmenu"]['items']) > 0;
        $this->_tvars["showrepmenu"] = count($this->_tvars["repmenu"]['groups']) > 0 || count($this->_tvars["repmenu"]['items']) > 0;
        $this->_tvars["showregmenu"] = count($this->_tvars["regmenu"]['groups']) > 0 || count($this->_tvars["regmenu"]['items']) > 0;
        $this->_tvars["showrefmenu"] = count($this->_tvars["refmenu"]['groups']) > 0 || count($this->_tvars["refmenu"]['items']) > 0;
        $this->_tvars["showsermenu"] = count($this->_tvars["sermenu"]['groups']) > 0 || count($this->_tvars["sermenu"]['items']) > 0;


        $this->_tvars["islogined"] = $user->user_id > 0;
        $this->_tvars["isadmin"] = $user->userlogin == 'admin';
        $this->_tvars["isadmins"] = $user->rolename == 'admins';
        $this->_tvars["isemp"] = $user->employee_id>0 ;
        $this->_tvars["showtimesheet"] = ($user->rolename == 'admins' || $user->employee_id>0 );

        if ($this->_tvars["usebranch"] == false) {
            $this->branch_id = 0;
            System::setBranch(0);
        }
        $this->_tvars["smart"] = Helper::generateSmartMenu();
        //модули
        $modules = System::getOptions('modules');

        $this->_tvars["shop"] = $modules['shop'] == 1;
        $this->_tvars["ocstore"] = $modules['ocstore'] == 1;
        $this->_tvars["woocomerce"] = $modules['woocomerce'] == 1;
        $this->_tvars["note"] = $modules['note'] == 1;
        $this->_tvars["issue"] = $modules['issue'] == 1;

        $this->_tvars["ppo"] = $modules['ppo'] == 1;
        $this->_tvars["np"] = $modules['np'] == 1;
        $this->_tvars["promua"] = $modules['promua'] == 1;
        $this->_tvars["paperless"] = $modules['paperless'] == 1;

      //  $printer = System::getOptions('printer');

        $this->_tvars["psurl"] =  $user->pserver ;  
        $this->_tvars["printserver"] = $user->prtype == 1;  


        //доступы к  модулям
        if (strpos(System::getUser()->modules, 'shop') === false && System::getUser()->rolename != 'admins') {
            $this->_tvars["shop"] = false;
        }
        if (strpos(System::getUser()->modules, 'note') === false && System::getUser()->rolename != 'admins') {
            $this->_tvars["note"] = false;
        }
        if (strpos(System::getUser()->modules, 'issue') === false && System::getUser()->rolename != 'admins') {
            $this->_tvars["issue"] = false;
        }
        if (strpos(System::getUser()->modules, 'ocstore') === false && System::getUser()->rolename != 'admins') {
            $this->_tvars["ocstore"] = false;
        }
        if (strpos(System::getUser()->modules, 'woocomerce') === false && System::getUser()->rolename != 'admins') {
            $this->_tvars["woocomerce"] = false;
        }
  
        if (strpos(System::getUser()->modules, 'ppo') === false && System::getUser()->rolename != 'admins') {
            $this->_tvars["ppo"] = false;
        }
        if (strpos(System::getUser()->modules, 'np') === false && System::getUser()->rolename != 'admins') {
            $this->_tvars["np"] = false;
        }
        if (strpos(System::getUser()->modules, 'promua') === false && System::getUser()->rolename != 'admins') {
            $this->_tvars["promua"] = false;
        }
        if (strpos(System::getUser()->modules, 'paperless') === false && System::getUser()->rolename != 'admins') {
            $this->_tvars["paperless"] = false;
        }

        if ($this->_tvars["shop"] ||
            $this->_tvars["ocstore"] ||
            $this->_tvars["woocomerce"] ||
            $this->_tvars["note"] ||
            $this->_tvars["issue"] ||
            $this->_tvars["promua"] ||
            $this->_tvars["paperless"] ||
            $this->_tvars["ppo"] ||
            $this->_tvars["np"]
        ) {
            $this->_tvars["showmodmenu"] = true;
        } else {
            $this->_tvars["showmodmenu"] = false;
        }

        /*
        if ($this->_tvars["isadmins"]) {  //для  роли админов  видны  все  разделы  меню
            $this->_tvars["showdocmenu"] = true;
            $this->_tvars["showrepmenu"] = true;
            $this->_tvars["showregmenu"] = true;
            $this->_tvars["showrefmenu"] = true;
            $this->_tvars["showsermenu"] = true;
            $this->_tvars["showmodmenu"] = true;
        }   */

        //скрыть  боковое  меню
        $this->_tvars["hidesidebar"] = $user->hidesidebar == 1 ? 'hold-transition   sidebar-collapse' : 'hold-transition sidebar-mini sidebar-collapse';
        if ($user->darkmode == 1) {
            $this->_tvars["hidesidebar"] = $this->_tvars["hidesidebar"] . ' ' . 'dark-mode';
        }

        $this->_tvars["darkmode"] = $user->darkmode == 1;

        //для скрытия блока разметки  в  шаблоне страниц                           
        $this->_tvars["hideblock"] = false;
        
        //активные   пользователий
        if ($options['showactiveusers'] == 1) {
            $this->_tvars["showactiveusers"] = true;
            $this->_tvars["activeuserscnt"] = 0;
            $this->_tvars["aulist"] = array();

            $conn = \ZDB\DB::getConnect();
            $conn->Execute("update users  set  lastactive = now() where  user_id= " . $user->user_id);

   
            $w = "     TIME_TO_SEC(timediff(now(),lastactive)) <300  ";
            if($conn->dataProvider=="postgres") {
                $w = "     EXTRACT(EPOCH FROM now() - lastactive) <300  ";
            }            
            
            if ($this->branch_id > 0) {
                $w .= "  and  employee_id  in (select employee_id from employees where branch_id ={$this->branch_id}) ";
            }


            $users = \App\Entity\User::findArray('username', $w, 'username');
            foreach ($users as $id => $u) {
                if ($id == $user->user_id) {
                    $id = null;
                }
                $this->_tvars["aulist"][] = array("auserid" => $id, 'ausername' => $u);
            }


            $this->_tvars["activeuserscnt"] = count($this->_tvars["aulist"]);
          //  \App\Helper::sendLetter("softman@ukr.net","test3","sub")  ;
        }
        //чат
        if ($options['showchat'] == 1) {
            $this->_tvars["showchat"] = true;

            $cnt = \App\Entity\Notify::findCnt("user_id=" . \App\Entity\Notify::CHAT . " and notify_id>" . intval($_COOKIE['last_chat_id']));

            $this->_tvars["chatcnt"] = $cnt > 0 ? $cnt : false;;

        }
        $this->generateToasts();
        
     }

    public function LogoutClick($sender) {
        \App\Helper::logout();
    }

    public function onnbFirm($sender) {
        $branch_id = $sender->getValue();
        System::setBranch($branch_id);

        setcookie("branch_id", $branch_id, time() + 60 * 60 * 24 * 30);

        $page = get_class($this);
        App::Redirect($page);
    }

    //вывод ошибки,  используется   в дочерних страницах

    public function setError($msg, $p1 = "", $p2 = "") {
        $msg = str_replace("'","`",$msg) ;
     
        $msg = Helper::l($msg, $p1, $p2);
        System::setErrorMsg($msg);
    }
    //вывод  как  bootstrap alert  (для сообщений что  могут  вызвать  ошибку  javascript)
    public function setErrorTopPage($msg ) {
        $msg = str_replace("'","`",$msg) ;
    
        System::setErrorMsg($msg,true);
    }

    public function setSuccess($msg, $p1 = "", $p2 = "") {
        $msg = str_replace("'","`",$msg) ;
        $msg = Helper::l($msg, $p1, $p2);
        System::setSuccessMsg($msg);
    }

    public function setWarn($msg, $p1 = "", $p2 = "") {
         $msg = str_replace("'","`",$msg) ;
       $msg = Helper::l($msg, $p1, $p2);
        System::setWarnMsg($msg);
    }

    public function setInfo($msg, $p1 = "", $p2 = "") {
        $msg = str_replace("'","`",$msg) ;
        $msg = Helper::l($msg, $p1, $p2);
        System::setInfoMsg($msg);
    }

    final protected function isError() {
        return (strlen(System::getErrorMsg()) > 0 || strlen(System::getErrorMsg( )) > 0);
    }

    public function beforeRender() {
        $user = System::getUser();
        $this->_tvars['notcnt'] = \App\Entity\Notify::isNotify($user->user_id);
        $this->_tvars['taskcnt'] = \App\Entity\Event::isNotClosedTask($user->user_id);
        $this->_tvars['alerterror'] = "";
        if (strlen(System::getErrorMsgTopPage()) > 0) {
            $this->_tvars['alerterror'] = System::getErrorMsgTopPage();

            $this->goAnkor('topankor');


        }
    }

    protected function afterRender() {

        $user = System::getUser();
        if (strlen(System::getErrorMsg()) > 0) {
            
            $this->addJavaScript("toastr.error('" . System::getErrorMsg() . "','',{'timeOut':'8000'})        ", true);
        }

        if (strlen(System::getWarnMsg()) > 0) {
            $this->addJavaScript("toastr.warning('" . System::getWarnMsg() . "','',{'timeOut':'4000'})        ", true);
        }
        if (strlen(System::getSuccesMsg()) > 0) {
            $this->addJavaScript("toastr.success('" . System::getSuccesMsg() . "','',{'timeOut':'2000'})        ", true);
        }
        if (strlen(System::getInfoMsg()) > 0) {
            $this->addJavaScript("toastr.info('" . System::getInfoMsg() . "','',{'timeOut':'3000'})        ", true);
        }


        $this->setError('');
        $this->setErrorTopPage('');
        $this->setSuccess('');
        $this->setInfo('');
        $this->setWarn('');
    }

    //Перезагрузить страницу  с  клиента
    //например для  сброса  адресной строки  после  команды удаления
    protected final function resetURL() {
        \App\Application::$app->setReloadPage();
    }

    /**
     * Вставляет  JavaScript  в  конец   выходного  потока
     * @param string  Код  скрипта
     * @param boolean Если  true  - вставка  после  загрузки  документа в  браузер
     */
    public function addJavaScript($js, $docready = false) {
        App::$app->getResponse()->addJavaScript($js, $docready);
    }

    public function goDocView() {
        $this->goAnkor('dankor');
    }

    public function sendMsg($args, $post) {

        $n = new \App\Entity\Notify();
        $n->user_id = $post["sendmsgrecid"];
        $n->message = $post["sendmsgtext"];
        $n->sender_id = System::getUser()->user_id;
        $n->save();

    }

    private function generateToasts() {


        $this->_tvars["toasts"] = array();
        if (\App\Session::getSession()->toasts == true) {
            return;
        }//уже показан

        $user = System::getUser();
        if ($user->defstore == 0) {
         //   $this->_tvars["toasts"][] = array('title' => "title:\"" . Helper::l("nodefstore") . "\"");
        }
        if ($user->deffirm == 0) {
         //   $this->_tvars["toasts"][] = array('title' => "title:\"" . Helper::l("nodeffirm") . "\"");
        }
        if ($user->defmf == 0) {
        //    $this->_tvars["toasts"][] = array('title' => "title:\"" . Helper::l("nodefmf") . "\"");
        }
        if ($user->userlogin == "admin") {
            if ($user->userpass == "admin" || $user->userpass == '$2y$10$GsjC.thVpQAPMQMO6b4Ma.olbIFr2KMGFz12l5/wnmxI1PEqRDQf.') {
                $this->_tvars["toasts"][] = array('title' => "title:\"" . Helper::l("nodefadminpass") . "\"");

            }
        }
        if ($user->rolename == "admins") {
            if (\App\Entity\Notify::isNotify(\App\Entity\Notify::SYSTEM)) {
                $this->_tvars["toasts"][] = array('title' => "title:\"" . Helper::l("hassystemnotify") . "\"");

            }
        }
        
        if (count($this->_tvars["toasts"]) == 0) {
           // $this->_tvars["toasts"][] = array('title' => '');
           \App\Session::getSession()->toasts = false;
           return;
        }
        \App\Session::getSession()->toasts = true;
    }

}
