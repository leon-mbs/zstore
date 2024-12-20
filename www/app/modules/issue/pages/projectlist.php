<?php

namespace App\Modules\Issue\Pages;

use App\Application as App;
use App\Entity\Customer;
use App\Modules\Issue\Entity\Project;
use App\System;
use Zippy\Html\DataList\ArrayDataSource;
use Zippy\Html\DataList\DataView;
use Zippy\Html\DataList\Paginator;
use Zippy\Html\Form\AutocompleteTextInput;
use Zippy\Html\Form\Button;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\TextArea;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Panel;
use App\Helper as H;

class ProjectList extends \App\Pages\Base
{
    public $id=0;

    public function __construct($id = '0') {
        parent::__construct();
        $this->_user = System::getUser();
        $id = intval($id);

        $allow = (strpos($this->_user->modules, 'issue') !== false || $this->_user->rolename == 'admins');
        if (!$allow) {
            System::setErrorMsg("Немає права доступу до сторінки");
            App::RedirectError();
            return;
        }

        $this->id = $id;

    }



    public function init($args, $post) {
        $user = \App\System::getUser();

        $post = json_decode($post) ;
        $stlist = Project::getStatusList();
        $users = \App\Entity\User::findArray("username", " user_id <>" . $user->user_id, 'username');

        return json_encode(array(
                  'stlist'=> \App\Util::tokv($stlist) ,
                  'userlist'=> \App\Util::tokv($users) ,

                  'id'=> $this->id ,   //открыть на просмотр
                  'pagesize'=> H::getPG()
                    ), JSON_UNESCAPED_UNICODE);

    }

    public function del($args, $post=null) {


        $del = Project::delete($args[0]);


    }


    public function getList($args, $post) {

        $post = json_decode($post) ;
        $user = System::getUser();



        $number = trim($post->searchnumber);
        $cust = $post->searchcust;
        $status = $post->searchstate;

        if ($status == 0) {
            $where = " status <>  " . Project::STATUS_CLOSED;
        } else {
            $where = " status= " . $status;
        }

        if ($cust > 0) {
            $where .= " and customer_id = " . $cust;
        }
        $user = System::getUser();

        if ($user->rolename != 'admins') {
            $where .= " and project_id in (select project_id from issue_projectacc where user_id = {$user->user_id} )   ";
        }

        if (strlen($number) > 0) {
            $s = Project::qstr('%' . $number . '%');

            $where = "   (details like {$s} or project_name like {$s}  )  ";
        }
        $stlist = Project::getStatusList() ;

        $prlist = array();
        foreach(Project::find($where, "project_id desc", $post->count, $post->start) as $p) {
            $pa = $p->getData() ;
            $pa['status_id']  = $p->status;
            $pa['allowedit']  = true;
            $pa['allowdel']  = true;

            if ($user->rolename != 'admins' && $user->user_id != $p->creator_id) {
                $pa['allowedit']  = false;
                $pa['allowdel']  = false;
            }


            if ($p->status == Project::STATUS_CLOSED) {
                $pa['allowedit']  = false;
                $pa['allowdel']  = false;
            }


            $pa['status']  = $stlist[$p->status];
            unset($pa['details']);
            $prlist[]=  $pa;
        }
        $clist = Customer::findArray('customer_name', 'customer_id in (select customer_id from issue_projectlist )', 'customer_name');

        return json_encode(array('prlist'=>$prlist,'cnt'=> Project::findCnt($where) ,  'custlist'=> \App\Util::tokv($clist) ,  ), JSON_UNESCAPED_UNICODE);

    }


    public function edit($args, $post=null) {
        $pd = Project::load($args[0])  ;
        $users = array();
        $pusers =  implode(",", $pd->getUsers());
        if(strlen($pusers)>0) {
            $users = \App\Entity\User::findArray("username", " user_id  in ({$pusers})", 'username');
        }


        return json_encode(array('name'=>$pd->project_name,
                     'selusers'=>\App\Util::tokv($users),
                     'desc'=>$pd->desc,
                     'customer_id'=>$pd->customer_id,
                     'customer_name'=>$pd->customer_name

                       ), JSON_UNESCAPED_UNICODE);


    }

    public function show($args, $post=null) {
        $pd = Project::load($args[0])  ;


        return json_encode(array('name'=>$pd->project_name,

                     'desc'=>$pd->desc,
                     'status'=>$pd->status

                       ), JSON_UNESCAPED_UNICODE);


    }

    public function save($args, $post=null) {
        $pd = Project::load($args[0])  ;
        if($pd==null) {
            $pd = new  Project();
        }

        $users = trim($post["users"], ",");
        if(strlen($users)>0) {
            $pd->setUsers(explode(",", $users));
        }
        $pd->project_name=$post["name"] ;
        $pd->desc=$post["desc"] ;
        $pd->customer_id=$post["customer_id"] ;
        $pd->save();


        return "";


    }

    public function ontextCustomers($args, $post=null) {


        $text = $args[0];
        $text= Customer::qstr('%'.$text.'%') ;

        $list =\App\Util::tokv(Customer::findArray("customer_name", "status=0 and customer_name like " . $text));

        return json_encode($list, JSON_UNESCAPED_UNICODE);

    }


    public function onStatus($args, $post=null) {
        $pd = Project::load($args[0])  ;
        if($pd==null) {
            return;
        }
        $pd->status  =  $args[1]  ;
        $pd->save();

    }

    public function getFileList($args, $post) {
        $user = \App\System::getUser() ;
        $pr = Project::load($args[0]);

        $filelist = array();
        foreach(H::getFileList($args[0], \App\Entity\Message::TYPE_PROJECT) as $f) {


            $url = _BASEURL . 'loadfile.php?id=' . $f->file_id;
            if (strlen($f->mime) > 0) {
                $url = $url . '&im=1';
            }



            $filelist[]=array(
            'file_id'=>$f->file_id,
            'url'=>$url,
            'filename'=>$f->filename,

            'candel'=>($user->user_id == $pr->user_id || $user->rolename  =='admins')
            ) ;
        }


        return json_encode($filelist, JSON_UNESCAPED_UNICODE);

    }

    public function addFile($args, $post) {


        $file =  $_FILES['pfile']  ;

        if(strlen($file['tmp_name'] ?? '')==0) {
            return;
        }

        H::addFile($file, $args[0], '', \App\Entity\Message::TYPE_PROJECT);


    }
    public function delFile($args, $post) {

        \App\Helper::deleteFile($args[0]);

    }

    public function delMsg($args, $post) {

        \App\Entity\Message::delete($args[0]);

    }
    public function addMsg($args, $post) {
        $msg = new \App\Entity\Message();
        $msg->message = $post;
        $msg->created = time();
        $msg->user_id =  \App\System::getUser()->user_id;
        $msg->item_id = $args[0];
        $msg->item_type = \App\Entity\Message::TYPE_PROJECT;
        if (strlen($msg->message) == 0) {
            return;
        }
        $msg->save();


    }

    public function getMsgList($args, $post) {
        $user = \App\System::getUser() ;

        $where = 'item_type = 6 and item_id=' . $args[0] ;

        $cnt =  \App\Entity\Message::findCnt($where)    ;

        $msglist = \App\Entity\Message::find($where, 'message_id');

        $list = array();
        foreach($msglist as $m) {
            $msg = array() ;
            $msg['data']  = $m->message;
            $msg['candel']  = ($user->user_id == $m->user_id || $user->rolename  =='admins') ;
            $msg['user']  = $m->username;
            $msg['message_id']  = $m->message_id;
            $msg['date']  = H::fd($m->created);


            $list[]=$msg;
        }

        return json_encode(array('list'=>$list,'cnt'=>$cnt  ), JSON_UNESCAPED_UNICODE);

    }



}
