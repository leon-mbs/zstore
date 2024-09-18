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
        $this->_tvars["usescanner"] = $options['usescanner'] == 1 || $options['usemobilescanner'] == 1;
        $this->_tvars["usemobilescanner"] = $options['usemobilescanner'] == 1;
        $this->_tvars["useimages"] = $options['useimages'] == 1;
        $this->_tvars["usebranch"] = $options['usebranch'] == 1;
        $this->_tvars["useval"] = $options['useval'] == 1;
        $this->_tvars["usecattree"] = $options['usecattree'] == 1;
        $this->_tvars["usemobileprinter"] = $user->usemobileprinter == 1;
        $this->_tvars["canevent"] = $user->canevent == 1;
        if($user->rolename=='admins') {
            $this->_tvars["canevent"] = true;
        }
        $this->_tvars["noshowpartion"] = $user->noshowpartion;
        $this->_tvars["showsidemenu"] = !($user->hidemenu == true);
        $this->_tvars["twodigit"] = round($options['amdigits']) > 0;

        $this->_tvars['qtydigits']  = intval($options['qtydigits'] ?? 0);
        $this->_tvars['amdigits']  = intval($options['amdigits'] ?? 0);

        
        $this->_tvars["usesnumber"] = $options['usesnumber']  > 0;
        $this->_tvars["usesnumberdate"] = $options['usesnumber']  == 2;
        $this->_tvars["usesnumberitem"] = $options['usesnumber']  == 3;
        

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
        $this->add(new Label('loginname', $user->username));


        //меню
        $menu = Session::getSession()->menu ?? [];
        if(count($menu)==0) {
            $menu["docmenu"] = Helper::generateMenu(1);
            $menu["repmenu"] = Helper::generateMenu(2);
            $menu["regmenu"] = Helper::generateMenu(3);
            $menu["refmenu"] = Helper::generateMenu(4);
            $menu["sermenu"] = Helper::generateMenu(5);
            Session::getSession()->menu = $menu;
        }

        $this->_tvars["docmenu"] = $menu["docmenu"];
        $this->_tvars["repmenu"] = $menu["repmenu"];
        $this->_tvars["regmenu"] = $menu["regmenu"];
        $this->_tvars["refmenu"] = $menu["refmenu"];
        $this->_tvars["sermenu"] = $menu["sermenu"];

        $this->_tvars["showdocmenu"] = count($this->_tvars["docmenu"]['groups']) > 0 || count($this->_tvars["docmenu"]['items']) > 0;
        $this->_tvars["showrepmenu"] = count($this->_tvars["repmenu"]['groups']) > 0 || count($this->_tvars["repmenu"]['items']) > 0;
        $this->_tvars["showregmenu"] = count($this->_tvars["regmenu"]['groups']) > 0 || count($this->_tvars["regmenu"]['items']) > 0;
        $this->_tvars["showrefmenu"] = count($this->_tvars["refmenu"]['groups']) > 0 || count($this->_tvars["refmenu"]['items']) > 0;
        $this->_tvars["showsermenu"] = count($this->_tvars["sermenu"]['groups']) > 0 || count($this->_tvars["sermenu"]['items']) > 0;


        $this->_tvars["islogined"] = $user->user_id > 0;
        $this->_tvars["isadmin"] = $user->userlogin == 'admin';
        $this->_tvars["isadmins"] = $user->rolename == 'admins';
        $this->_tvars["isemp"] = $user->employee_id>0 ;
        $this->_tvars["showtimesheet"] = ($user->rolename == 'admins' || $user->employee_id>0);

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
        $this->_tvars["checkbox"] = $modules['checkbox'] == 1;
        $this->_tvars["vkassa"] = $modules['vkassa'] == 1;
        $this->_tvars["horoshop"] = $modules['horoshop'] == 1;
        $this->_tvars["vdoc"] = $modules['vdoc'] == 1;



        //  $printer = System::getOptions('printer');

        $this->_tvars["psurl"] =  $user->pserver ;
        $this->_tvars["printserver"] = $user->prtype == 1;
        $this->_tvars["psurllabel"] =  $user->pserverlabel ;
        $this->_tvars["printserverlabel"] = $user->prtypelabel == 1;



        //доступы к  модулям
        if (strpos(System::getUser()->modules ?? '', 'shop') === false && System::getUser()->rolename != 'admins') {
            $this->_tvars["shop"] = false;
        }
        if (strpos(System::getUser()->modules ?? '', 'note') === false && System::getUser()->rolename != 'admins') {
            $this->_tvars["note"] = false;
        }
        if (strpos(System::getUser()->modules ?? '', 'issue') === false && System::getUser()->rolename != 'admins') {
            $this->_tvars["issue"] = false;
        }
        if (strpos(System::getUser()->modules ?? '', 'ocstore') === false && System::getUser()->rolename != 'admins') {
            $this->_tvars["ocstore"] = false;
        }
        if (strpos(System::getUser()->modules ?? '', 'woocomerce') === false && System::getUser()->rolename != 'admins') {
            $this->_tvars["woocomerce"] = false;
        }

        if (strpos(System::getUser()->modules ?? '', 'ppo') === false && System::getUser()->rolename != 'admins') {
            $this->_tvars["ppo"] = false;
        }
        if (strpos(System::getUser()->modules ?? '', 'np') === false && System::getUser()->rolename != 'admins') {
            $this->_tvars["np"] = false;
        }
        if (strpos(System::getUser()->modules ?? '', 'promua') === false && System::getUser()->rolename != 'admins') {
            $this->_tvars["promua"] = false;
        }
        if (strpos(System::getUser()->modules ?? '', 'checkbox') === false && System::getUser()->rolename != 'admins') {
            $this->_tvars["checkbox"] = false;
        }
        if (strpos(System::getUser()->modules ?? '', 'vkassa') === false && System::getUser()->rolename != 'admins') {
            $this->_tvars["vkassa"] = false;
        }
        if (strpos(System::getUser()->modules ?? '', 'horoshop') === false && System::getUser()->rolename != 'admins') {
            $this->_tvars["horoshop"] = false;
        }
        if (strpos(System::getUser()->modules ?? '', 'vdoc') === false && System::getUser()->rolename != 'admins') {
            $this->_tvars["vdoc"] = false;
        }

        $this->_tvars["fiscal"] = $this->_tvars["checkbox"] || $this->_tvars["ppo"] || $this->_tvars["vkassa"];

        if ($this->_tvars["shop"] ||
            $this->_tvars["ocstore"] ||
            $this->_tvars["woocomerce"] ||
            $this->_tvars["note"] ||
            $this->_tvars["issue"] ||
            $this->_tvars["promua"] ||
            $this->_tvars["ppo"] ||
            $this->_tvars["horoshop"] ||
            $this->_tvars["vdoc"] ||
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

            $cnt = \App\Entity\Notify::findCnt("user_id=" . \App\Entity\Notify::CHAT . " and notify_id>" . intval($_COOKIE['last_chat_id'] ?? 0));

            $this->_tvars["chatcnt"] = $cnt > 0 ? $cnt : false;

        }


        if((Session::getSession()->toasts ?? true) ==false) {
           Session::getSession()->toasts = false;  
           
            if ($user->defstore == 0) {
                //   $this->_tvars["toasts"][] = array('title' => "title:\"Вкажіть у профілі склад за замовчуванням\"");
            }
            if ($user->deffirm == 0) {
                //   $this->_tvars["toasts"][] = array('title' => "title:\"Вкажіть у профілі компанію за замовчуванням\"");
            }
            if ($user->defmf == 0) {
                //    $this->_tvars["toasts"][] = array('title' => "title:\"Вкажіть у профілі касу за замовчуванням\"");
            }
            if ($user->userlogin == "admin") {
                if ($user->userpass == "admin" || $user->userpass == '$2y$10$GsjC.thVpQAPMQMO6b4Ma.olbIFr2KMGFz12l5/wnmxI1PEqRDQf.') {
                    $this->addToastrWarn("Змініть у профілі пароль за замовчуванням"); 
                }
            }
            if ($user->rolename == "admins") {
                if (\App\Entity\Notify::isNotify(\App\Entity\Notify::SYSTEM)) {
                    $this->addToastrInfo("Є непрочитані системні повідомлення"); 
                }
            }           
                 
           
        }
     
    //    $duration =  Session::getSession()->duration() ;
     //   $this->_tvars['showver'] = $duration < 60   ;

        //планировщик
        $this->_tvars['cron']  = false;

        $last = \App\Helper::getKeyValInt('lastcron')  ;
        if(\App\System::useCron()  &&  (time() - $last) > \App\Entity\CronTask::MIN_INTERVAL) {
            $this->_tvars['cron']  = true;
        }

        //миграция  данных
        if(  Session::getSession()->migrationcheck != true && ($this instanceof \App\Pages\Update)==false) {
            Helper::migration() ;
            Session::getSession()->migrationcheck = true;
        }
       
        
      
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

    public function setError($msg ) {
        $msg = str_replace("'", "`", $msg) ;


        System::setErrorMsg($msg);
    }
    //вывод  как  bootstrap alert  (для сообщений что  могут  вызвать  ошибку  javascript)
    public function setErrorTopPage($msg) {
        $msg = str_replace("'", "`", $msg) ;

        System::setErrorMsg($msg, true);
    }

    public function setSuccess($msg ) {
        $msg = str_replace("'", "`", $msg) ;

        System::setSuccessMsg($msg);
    }

    public function setWarn($msg ) {
        $msg = str_replace("'", "`", $msg) ;

        System::setWarnMsg($msg);
    }

    public function setInfo($msg ) {
        $msg = str_replace("'", "`", $msg) ;

        System::setInfoMsg($msg);
    }
    public function setInfoTopPage($msg) {
        $msg = str_replace("'", "`", $msg) ;

        System::setInfoMsg($msg, true);
    }

    final protected function isError() {
        return (strlen(System::getErrorMsg()) > 0 || strlen(System::getErrorMsg()) > 0);
    }

    public function beforeRender() {
        parent::beforeRender()  ;
        $user = System::getUser();
        $this->_tvars['notcnt'] = \App\Entity\Notify::isNotify($user->user_id);
        $this->_tvars['taskcnt'] = \App\Entity\Event::isNotClosedTask($user->user_id);
        $this->_tvars['alerterror'] = "";
        $this->_tvars['alertinfo'] = "";
        if (strlen(System::getErrorMsgTopPage() ?? '') > 0) { //стационарные сообщения
            $this->_tvars['alerterror'] = System::getErrorMsgTopPage();

            $this->goAnkor('topankor');


        }
        if (strlen(System::getInfoMsgTopPage() ?? '') > 0) { //стационарные сообщения
            $this->_tvars['alertinfo'] = System::getInfoMsgTopPage();

            $this->goAnkor('topankor');


        }
    }

    protected function afterRender() {

        $user = System::getUser();
        if (strlen(System::getErrorMsg() ?? '') > 0) {

            $this->addJavaScript("toastr.error('" . System::getErrorMsg() . "','',{'timeOut':'8000'})        ", true);
        }

        if (strlen(System::getWarnMsg() ?? '') > 0) {
            $this->addJavaScript("toastr.warning('" . System::getWarnMsg() . "','',{'timeOut':'4000'})        ", true);
        }
        if (strlen(System::getSuccesMsg() ?? '') > 0) {
            $this->addJavaScript("toastr.success('" . System::getSuccesMsg() . "','',{'timeOut':'2000'})        ", true);
        }
        if (strlen(System::getInfoMsg() ?? '') > 0) {
            $this->addJavaScript("toastr.info('" . System::getInfoMsg() . "','',{'timeOut':'3000'})        ", true);
        }


        $this->setError('');
        $this->setErrorTopPage('');
        $this->setInfoTopPage('');
        $this->setSuccess('');
        $this->setInfo('');
        $this->setWarn('');
        
        
        parent::afterRender()  ;
    }

    //Перезагрузить страницу  с  клиента
    //например для  сброса  адресной строки  после  команды удаления
    final protected function resetURL() {
        \App\Application::$app->setReloadPage();
    }

    /**
     * Вставляет  JavaScript  в  конец   выходного HTML потока
     * @param string  Код  скрипта
     * @param boolean Если  true  - вставка  после  загрузки  документа в  браузер
     */
    public function addJavaScript($js, $docready = false) {
        App::$app->getResponse()->addJavaScript($js, $docready);
    }
    
    /**
    * Добавление  javascript в AJAX вызовах
    * 
    * @param mixed $js
    */
    public function addAjaxJavaScript($js) {
         $this->addAjaxResponse($js) ; 
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

    public function getCustomerInfo($args, $post) {
        $conn= \ZDB\DB::getConnect() ;

        $c = \App\Entity\Customer::load($args[0]);
        if($c==null) {
            return  "N/A";
        }

        $report = new \App\Report('cinfo.tpl');
        $header = [];

        $header['name'] = $c->customer_name;
        $header['phone'] = $c->phone;
        $header['email'] = strlen($c->email) > 0 ? $c->email : false;
        $header['address'] = strlen($c->address) > 0 ? $c->address : false;
        $header['comment'] = strlen($c->comment) > 0 ? $c->comment : false;

        $header['bonus'] = intval($c->getBonus());
        if($header['bonus']==0) {
            $header['bonus'] = false;
        }
        $header['dolg'] = doubleval($c->getDolg());

        if($header['dolg']>0) {
            $header['dolg'] ='+'.$header['dolg'] ;
        }
        if($header['dolg']==0) {
            $header['dolg'] = false;
        }
        $header['disc'] = doubleval($c->getDiscount());
        if($header['disc']==0) {
            $header['disc'] = false;
        }
        $header['last'] = false;
        $doc = \App\Entity\Doc\Document::getFirst(" customer_id={$c->customer_id}", "document_id desc") ;
        if($doc != null) {
            $header['last']= $doc->meta_desc .' '. $doc->document_number;
            $header['lastdate']=Helper::fd($doc->document_date);
            $header['lastsum']=Helper::fa($doc->amount);
            $header['laststatus']   =  \App\Entity\Doc\Document::getStateName($doc->state)  ;

            $header['goods'] = [];

            $sql = "select items.item_id, items.itemname,items.item_code    from 
             entrylist_view  join items  on items.item_id = entrylist_view.item_id 
             where entrylist_view.customer_id={$c->customer_id}  
             order  by  entry_id desc  limit 0,10 "    ;

            foreach($conn->Execute($sql) as $i) {
                $header['goods'][$i['item_id']] = $i;
            }

            $header['goods']  =  array_values($header['goods']) ;

        }


        $header['smscode'] = false;

        $sms = System::getOptions("sms");
        if(intval($sms) > 0 && strlen($c->phone)>0) {
            $header['smscode'] = "".rand(100, 990)  ;
            $header['click'] = "onclick=\"sendSMSCust('{$c->phone}',{$header['smscode']})\"" ;

        }
        $header['sumall'] = \App\Helper::fa($c->sumAll());


        $data = $report->generate($header);
        $data = str_replace("'", "`", $data)  ;
        //  $data = str_replace("\"","`",$data)  ;




        return $data;

    }


    public function sendSMSCode($args, $post) {


        $ret = \App\Entity\Subscribe::sendSMS($args[0], $args[1])  ;
        return $ret ?? "";

    }


    
    /**
    * добавляет стьроку  заказа в  заявку  поставщику
    * 
    * @param mixed $args
    * @param mixed $post
    * @return mixed
    */
    public function addItemToCO($args, $post=null) {
        try{
            $e = \App\Entity\Entry::getFirst("item_id={$args[0]} and quantity > 0 and document_id in (select document_id from documents_view where  meta_name='GoodsReceipt' ) ","entry_id desc")  ;
            $d = \App\Entity\Doc\Document::load($e->document_id)  ;

            if($d == null) {
                return "По  даному  ТМЦ  закупок не  було";
            }
            $price = $e->partion;
            $quantity = $e->quantity;
            $customer_id = $d->customer_id;
            if($args[1] > 0) {
                $quantity = $args[1] ;
            }
            $co = \App\Entity\Doc\Document::getFirst("meta_name='OrderCust' and  customer_id={$d->customer_id}   and state=1 ","document_id desc") ;
            
            if($co==null) {
                $co = \App\Entity\Doc\Document::create('OrderCust');
                $co->document_number = $co->nextNumber();        
                $co->customer_id = $customer_id;        
                $co->save();
                $co->updateStatus(1);
            }  else {
                $co->document_date = time(); 
                $co->save();
            }

            $items=  $co->unpackDetails('detaildata');
            $i=-1;
            foreach($items as $k=>$v)  {
                if($v->item_id == $args[0] ) {
                    $i=  $k;
                    break;
                }
            }
            if($i==-1)  {
                $item = \App\Entity\Item::load($args[0]);
     
                $item->quantity = $quantity;
                $item->price = $price;
                $item->rowid = $item->item_id;        
                $items[$item->rowid]=$item;
            }   else {
                $items[$i]->quantity += $quantity;  
            }
            $total = 0;


            foreach ($items as $item) {
                $item->amount = \App\Helper::fa($item->price * $item->quantity);

                $total = $total + $item->amount;
            }
            $co->amount= \App\Helper::fa($total);
            
            
            $co->packDetails('detaildata',$items);
            $co->save();
            
            return "";
        } catch(\Exception $e){
            return $e->getMessage() ;
        }

    }

    /**
    *  всплывающая  подсказка
    * 
    * @param mixed $text
    */
    protected function addToastrInfo($text,$ajax=false) {
        $text = str_replace('`',"'",$text) ;
        $text = str_replace('`',"\"",$text) ;
                
        $js=" $(document).Toasts('create', {
                    icon: 'fa fa-info-circle text-info',
                            position:'bottomRight',
                            title:'{$text}'

                    }) ";
        if($ajax) {
            $this->addJavaScript($js,true) ;    
        }else {
          $this->addAjaxResponse($js) ; 
        }           
        
    }
    protected function addToastrWarn($text,$ajax=false) {
        $text = str_replace('`',"'",$text) ;
        $text = str_replace('`',"\"",$text) ;
                
        $js=" $(document).Toasts('create', {
                    icon: 'fa fa-exclamation-triangle text-warning',
                            position:'bottomRight',
                            title:'{$text}'

                    }) ";
        if($ajax) {
            $this->addJavaScript($js,true) ;    
        }else {
          $this->addAjaxResponse($js) ; 
        }
    }
    
    //callPM

    public function vonTextCust($args, $post=null) {

        $list =\App\Util::tokv(\App\Entity\Customer::getList($args[0], $args[1]));

        return json_encode($list, JSON_UNESCAPED_UNICODE);
    }

    public function vonTextItem($args, $post=null) {

        $list =\App\Util::tokv(\App\Entity\Item::findArrayAC($args[0], $args[1], $args[2]));

        return json_encode($list, JSON_UNESCAPED_UNICODE);
    }

    public function vLoadService($args, $post=null) {

        $service_id = $args[0];
        $ser =   \App\Entity\Service::load($service_id) ;
        $ret = [];
        if($ser != null) {
            $ret['service_id']   = $service_id;
            $ret['service_name'] = $ser->service_name;
            $ret['category'] = $ser->category;
            $ret['msr'] = $ser->msr;
            $ret['pureprice'] = $ser->getPurePrice();
            $ret['price'] = $ser->getPrice($args[1]);
            if($ret['pureprice'] > $ret['price']) {
                $ret['disc']  = number_format((1 - ($ret['price']/($ret['pureprice'])))*100, 1, '.', '') ;
            }

        }

        return json_encode($ret, JSON_UNESCAPED_UNICODE);

    }

    public function vLoadItem($args, $post=null) {
        $item_id=$args[0];
        $p = strlen($post)==null ? array() : json_decode($post, true)  ;


        $item =   \App\Entity\Item::load($item_id) ;
        $ret = [];
        if($item != null) {
            $ret['item_id'] = $item_id;
            $ret['itemname'] = $item->itemname;

            $ret['price'] = $item->getPriceEx(array(
                 'pricetype'=>$p['pt'],
                 'store'=>$p['store'] ,
                 'customer'=>$p['customer']

            ));



       //     $ret['lastpartion'] = $item->getLastPartion(0, "", true); //последняя  закупка
            $ret['qtystock'] = $item->getQuantity(); // на  складе
            $ret['item_code'] = $item->item_code;
            $ret['useserial'] = $item->useserial;
            $ret['snumber'] = '';


            $ret['disc'] = '';
            $ret['pureprice'] = $item->getPurePrice();
            if($ret['pureprice'] > $ret['price']) {
                $ret['disc']  = number_format((1 - ($ret['price']/($ret['pureprice'])))*100, 1, '.', '') ;
            }
            if($ret['useserial']==1) {
               $ret['serials'] = $item->getSerials($p['store']); 
            }
                      
  

        }


        return json_encode($ret, JSON_UNESCAPED_UNICODE);
    }


    public function vSaveNewcust($args, $post=null) {
        $post=json_decode($post) ;

        $c = new  \App\Entity\Customer() ;
        $c->customer_name = $post->name;
        $c->phone =  $post->phone;
        $c->email =  $post->email;
        $c->type = $post->type ?? 0; 

        $c->save() ;
        $ret = array('customer_id'=>$c->customer_id) ;

        return json_encode($ret, JSON_UNESCAPED_UNICODE);
    }

    public function vSaveNewitem($args, $post=null) {

        $post=json_decode($post) ;

        $item = new  \App\Entity\Item()  ;
        $item->itemname = $post->itemname;
        $item->item_code = $post->item_code;
        $item->msr = $post->msr;
        $item->manufacturer = $post->brand;
        $item->cat_id = $post->cat_id;


        if ($item->checkUniqueArticle()==false) {
           return json_encode(array('error'=>'Такий артикул вже існує'), JSON_UNESCAPED_UNICODE);
        }

        if (strlen($item->item_code) == 0 ){
           $item->item_code =  \App\Entity\Item::getNextArticle();
        }

        $itemname = \App\Entity\Item::qstr($item->itemname);
        $code = \App\Entity\Item::qstr($item->item_code);
        $cnt = \App\Entity\Item::findCnt("item_id <> {$item->item_id} and itemname={$itemname} and item_code={$code} ");
        if ($cnt > 0) {

            return json_encode(array('error'=>'ТМЦ з такою назвою і артикулом вже існує'), JSON_UNESCAPED_UNICODE);

        }

        $item->save() ;

        $ret=array('item_id'=>$item->item_id) ;

        return json_encode($ret, JSON_UNESCAPED_UNICODE);
    }

    //загрузка  категорий  и брендов
    public function vLoadLists($args, $post) {
        $post = json_decode($post) ;
        $ret = [];
        if($post->cats ?? null) {
            $cats =  \App\Entity\Category::getList() ;
            $ret['cats'] =  \App\Util::tokv($cats) ;
        }
        if($post->brands ?? null) {
            $brands = \App\Entity\Item::getManufacturers(true) ;
            $ret['brands'] =  \App\Util::tokv($brands) ;
        }
        if($post->stores ?? null) {
            $stores = \App\Entity\Store::getList() ;
            $ret['stores'] =  \App\Util::tokv($stores) ;
        }
        if($post->firms ?? null) {
            $firms = \App\Entity\Firm::getList() ;
            $ret['firms'] =  \App\Util::tokv($firms) ;
        }
        if($post->mfs ?? null) {
            $mfs = \App\Entity\MoneyFund::getList() ;
            $ret['mfs'] =  \App\Util::tokv($mfs) ;
        }

        return json_encode($ret, JSON_UNESCAPED_UNICODE);
    }

    public function vLoadContracts($args, $post) {

        $ret=[];
        $ret['contracts'] =   \App\Util::tokv(\App\Entity\Contract::getList($args[0], $args[1]));


        return json_encode($ret, JSON_UNESCAPED_UNICODE);

    }

    //для vue
    public function vgetPriceByQty($args, $post) {
        $post = json_decode($post) ;

        $item =  \App\Entity\Item::load($post->item) ;

        $price = $item->getActionPriceByQuantity($post->qty);

        $ret=[];
        $ret['price'] = $price;

        return json_encode($ret, JSON_UNESCAPED_UNICODE);

    }
    public function vLoadCust($args, $post) {

        $ret=[];
        $info=[] ;
        $c = \App\Entity\Customer::load($args[0]) ;
        if($c != null) {
            $info['customer_name'] = $c->customer_name;
            $info['disctext'] = '';
            $info['discount']  = $c->getDiscount()  ;
            $info['bonus']  = $c->getBonus()  ;
            if (doubleval($info['discount']) > 0) {
                $info['disctext'] =  "Постійна знижка {$info['discount']}%";
                $info['bonus'] =0;
            } else {
                if ($info['bonus'] > 0) {
                    $info['disctext'] = "Нараховано бонусів " . $info['bonus'];
                }
            }

        }
        $ret['custinfo'] = $info;


        return json_encode($ret, JSON_UNESCAPED_UNICODE);

    }


}
