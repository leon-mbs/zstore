<?php

namespace App\Modules\Issue\Pages;

use App\Application as App;
use App\Entity\User;
use App\Modules\Issue\Entity\Issue;
use App\Modules\Issue\Entity\Project;
use App\System;
use Zippy\Html\DataList\ArrayDataSource;
use Zippy\Html\DataList\DataView;
use Zippy\Html\DataList\Paginator;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\TextArea;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Label;
use Zippy\Html\Link\BookmarkableLink;
use Zippy\Html\Link\RedirectLink;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Panel;
use App\Helper as H;

/**
 * Главная страница
 */
class IssueList extends \App\Pages\Base
{
    public $id;
    public $project_id;
    public $isnew;


    public function __construct($id = '0', $project_id = 0, $new = false) {
        parent::__construct();
        $id = intval($id);
        $project_id = intval($project_id);

        $this->_user = System::getUser();

        $allow = (strpos($this->_user->modules, 'issue') !== false || $this->_user->rolename == 'admins');
        if (!$allow) {
            System::setErrorMsg("Немає права доступу до сторінки");
            App::RedirectError();
            return;
        }
        $this->id = $id;
        $this->project_id = $project_id;
        $this->isnew = $new;


    }




    public function init($args, $post) {
        $user = \App\System::getUser();

        $post = json_decode($post) ;
        $stlist = Issue::getStatusList();
        $prlist = Issue::getPriorityList();
        $users = \App\Entity\User::findArray("username", " user_id <>" . $user->user_id, 'username');
        $projects = Project::findArray('project_name', "status<>".Project::STATUS_CLOSED, 'project_name');

        return json_encode(array(
                  'stlist'=> \App\Util::tokv($stlist) ,
                  'prlist'=> \App\Util::tokv($prlist) ,
                  'emps'=> \App\Util::tokv($users) ,
                  'projects'=> \App\Util::tokv($projects) ,

                  'id'=> $this->id ,   //открыть на просмотр
                  'project_id'=> $this->project_id ,
                  'cuser_id'=> $user->user_id ,
                  'isnew'=> $this->isnew ? true : false

                    ), JSON_UNESCAPED_UNICODE);

    }

    public function empbyproj($args, $post=null) {
        //  $conn = \ZDB\DB::getConnect();
        $users = \App\Entity\User::findArray("username", "user_id in(select  user_id  from issue_projectacc   where   project_id={$args[0]}   )", "username");


        return json_encode(array('emps'=>\App\Util::tokv($users)), JSON_UNESCAPED_UNICODE);

    }

    public function loadhistory($args, $post=null) {
        $conn = \ZDB\DB::getConnect();
        $res = $conn->Execute("select h.*,u.username from issue_history h join users_view u on h.user_id=u.user_id  where  issue_id={$args[0]} order  by  hist_id");
        $hist = array();
        foreach($res as $row) {
            $hist[]=array(
                "user"=>$row["username"],
                "time"=> h::fd(strtotime($row["createdon"])),
                "desc"=>$row["description"],
            );
        }
        return json_encode(array('hist'=>$hist), JSON_UNESCAPED_UNICODE);

    }

    public function getList($args, $post) {

        $post = json_decode($post) ;
        $user = System::getUser();

        $number = $post->searchnumber;
        $status = $post->searchstatus;
        $emp = $post->searchemp;
        $project = $post->searchproject;
        $sort = $post->searchsort;
        $orderby ="";
        if($sort==0) {
            $orderby = "priority asc";
        }
        if($sort==1) {
            $orderby = "issue_id desc";
        }
        if($sort==2) {
            $orderby = "lastupdate desc";
        }

        $where = " 1=1 ";
        if ($status == 0) {
            $where .= " and status <> " . Issue::STATUS_CLOSED;
        }
        if ($status < 100 && $status > 0) {
            $where .= " and status = " . $status;
        }
        if ($project > 0) {
            $where .= " and project_id = " . $project;
        }
        if ($emp > 0) {
            $where .= " and user_id = " . $emp;
        }

        if (strlen($number) > 0) {

            $s = Issue::qstr('%' . $number . '%');
            $where = "  (details like {$s} or issue_name like {$s} or issue_id={$number})  ";
            $orderby ="";
        }


        $list = array();
        foreach(Issue::find($where, $orderby) as $i) {
            $ia = array();
            $ia['issue_id']  =  $i->issue_id;
            $ia['issue_name']  =  $i->issue_name;
            $ia['prcl'] = "badge badge-success";
            if ($i->priority == Issue::PRIORITY_HIGH) {
                $ia['prcl'] = "badge badge-danger";
            }
            if ($i->priority == Issue::PRIORITY_LOW) {
                $ia['prcl'] = "badge badge-warning";
            }
            if ($i->status == Issue::STATUS_CLOSED) {
                $ia['prcl'] = "badge badge-secondary";
            }
            $ia['url'] = "/index.php?p=App/Modules/Issue/Pages/IssueList&args=".$i->issue_id;
            $ia["emp"] =  \App\Util::getLabelName($i->username) ;
            $ia["emptitle"] =  $i->username ;


            $list[]=  $ia;
        }

        return json_encode(array('issuelist'=>$list,'cnt'=> Issue::findCnt($where) ), JSON_UNESCAPED_UNICODE);

    }

    public function loadissue($args, $post=null) {

        $ret = array();

        $issue = Issue::load($args[0]) ;
        $ret["issue_name"] =$issue->issue_name;
        $ret["desc"] =$issue->desc;
        $ret["user_id"] =$issue->user_id;
        $ret["hours"] =$issue->hours;
        $ret["priority"] =$issue->priority;
        $ret["project_id"] =$issue->project_id;
        $ret["status"] =$issue->status;
        $ret["creator"] =$issue->createdbyname;
        $ret["createdon"] = H::fd($issue->createdon);
        $ret["updateddon"] = H::fdt($issue->lastupdate);

        return json_encode($ret, JSON_UNESCAPED_UNICODE);

    }

    public function saveissue($args, $post) {
        $post = json_decode($post) ;

        if($post->id >0) {
            $issue = Issue::load($post->id);
        } else {
            $issue = new Issue() ;
            $issue->project_id = $post->project_id;
            $issue->priority = $post->priority;
            $issue->user_id = $post->user_id;

            $user = \App\System::getUser() ;
            $issue->createdby = $user->user_id;
            $issue->createdbyname = $user->username;
        }

        $issue->issue_name = $post->issue_name;
        $issue->desc = $post->desc;
        $issue->hours = doubleval($post->hours);



        $issue->save();
        if ($post->id==0) {
            $issue->addStatusLog("Створення завдання");
        }
        return "";
    }

    public function update($args, $post) {
        $post = json_decode($post) ;

        $issue = Issue::load($post->id);
        $desc="" ;

        if($issue->status != $post->status) {
            $issue->status = $post->status;
            $stlist = Issue::getStatusList()  ;
            $desc="Статус змінено на  <b>" . $stlist[$issue->status] . "</b>"  ;
        }

        if($issue->priority != $post->priority) {
            $issue->priority = $post->priority;
            $prlist = Issue::getPriorityList() ;
            $desc="Пріоритет змінено на  <b>" . $prlist[$issue->priority] . "</b>" ;
        }

        if($issue->user_id != $post->user_id) {
            $issue->user_id = $post->user_id;
            $user = \App\Entity\User::load($issue->user_id) ;
            $desc="Завдання переведено на  <b>" . $user->username . "</b>";
        }


        if(strlen($desc)>0) {
            $issue->addStatusLog($desc);
            $issue->save();
        }

        return "";
    }

    public function del($args, $post=null) {

        Issue::delete($args[0])  ;

    }

    public function getFileList($args, $post) {
        $user = \App\System::getUser() ;
        $i = Issue::load($args[0]);

        $filelist = array();
        foreach(H::getFileList($args[0], \App\Entity\Message::TYPE_ISSUE) as $f) {


            $url = _BASEURL . 'loadfile.php?id=' . $f->file_id;
            if (strlen($f->mime) > 0) {
                $url = $url . '&im=1';
            }



            $filelist[]=array(
            'file_id'=>$f->file_id,
            'url'=>$url,
            'filename'=>$f->filename,

            'candel'=>($user->user_id == $i->user_id || $user->rolename  =='admins')
            ) ;
        }


        return json_encode($filelist, JSON_UNESCAPED_UNICODE);

    }

    public function addFile($args, $post) {


        $file =  $_FILES['pfile']   ;

        if(strlen($file['tmp_name'] ?? '')==0) {
            return;
        }

        H::addFile($file, $args[0], '', \App\Entity\Message::TYPE_ISSUE);


    }

    public function delFile($args, $post) {

        \App\Helper::deleteFile($args[0]);

    }

    public function delMsg($args, $post) {

        \App\Entity\Message::delete($args[0]);

    }

    public function addMsg($args, $post) {

        $users = trim($post["users"], ",");
        $msg = trim($post["msg"]);
        if(strlen($msg)==0) {
            return;
        }
        $msg = new \App\Entity\Message();
        $msg->message = $post["msg"];
        $msg->created = time();
        $msg->user_id =  \App\System::getUser()->user_id;
        $msg->item_id = $args[0];
        $msg->item_type = \App\Entity\Message::TYPE_ISSUE;

        $msg->save();


        $issue = Issue::load($args[0]);
        if(strlen($users)==0) {
            return;
        }
        foreach (explode(",", $users) as $u) {
            if ($u == System::getUser()->userid) {
                continue;
            }
            $n = new \App\Entity\Notify();
            $n->user_id = $u;
            $n->message =  "Коментар до завдання  #{$issue->issue_id} {$issue->issue_name} ";
            $n->message .= "<br>  <a href=\"/issue/{$issue->issue_id}/{$issue->project_id}/#msgankor\">Відповісти</a> ";
            $n->sender_id = System::getUser()->user_id;
            $n->save();
        }



    }

    public function getMsgList($args, $post) {
        $user = \App\System::getUser() ;

        $where = 'item_type = 5 and item_id=' . $args[0] ;

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
