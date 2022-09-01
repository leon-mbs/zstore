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
 

    public function __construct($id = 0, $project_id = 0, $new = false) {
        parent::__construct();

        $this->_user = System::getUser();

        $allow = (strpos($this->_user->modules, 'issue') !== false || $this->_user->rolename == 'admins');
        if (!$allow) {
            System::setErrorMsg(H::l('noaccesstopage'));
            App::RedirectError();
            return;
        }
         $this->id = $id;
         $this->project_id = $project_id;
         $this->isnew = $new;
  
        /*
        $this->add(new Form('filter'))->onSubmit($this, 'onFilter');
        $where = 'status <>' . Project::STATUS_CLOSED;
        if ($project_id > 0) {
            $where .= " or project_id=" . $project_id;
        }
        $projects = Project::findArray('project_name', $where, 'project_name');
        $this->filter->add(new DropDownChoice('searchproject', $projects, $project_id));

        $this->filter->add(new TextInput('searchnumber'));
        if ($id > 0) {
            $this->filter->searchnumber->setText($id);
        }

        //пользователи ассоциированные с сотрудниками

        $users = User::findArray('username', 'user_id in (select user_id from issue_issuelist)', 'username');

        $user_id = 0;

        if ($id == 0 && $project_id == 0) {
            $uid = System::getUser()->user_id;
            if (array_key_exists($uid, $users)) {
                $user_id = $uid; // Показываем  мои задачи
            }
        }

        $this->filter->add(new DropDownChoice('searchemp', $users, $user_id));

        $stlist = Issue::getStatusList();
        $stlist[0] = H::l('mopen');
        $stlist[100] = H::l('mall');
        $this->filter->add(new DropDownChoice('searchstatus', $stlist, 0));

        $this->add(new ClickLink('addnew', $this, 'onNew'));

        $this->add(new Panel("listpan"));

        $list = $this->listpan->add(new DataView('list', new IssueDS($this), $this, 'listOnRow'));
        $list->setSelectedClass('table-success');
        $list->setPageSize(15);
        $this->listpan->add(new Paginator('pag', $list));

        $msgpan = $this->listpan->add(new Panel("msgpan"));
        $msgpan->setVisible(false);

        $msgpan->add(new Label('mcreate'));
        $msgpan->add(new Label('mtitle'));
        $msgpan->add(new Label('mdesc'));
        $msgpan->add(new ClickLink('editissue', $this, 'editOnClick'));
        $msgpan->add(new ClickLink('deleteissue', $this, 'deleteOnClick'));

        $msgpan->add(new Form('addmsgform'))->onSubmit($this, 'onAddMsg');
        $msgpan->addmsgform->add(new TextArea('msgdata'));
        $msgpan->addmsgform->add(new \Zippy\Html\Form\CheckBoxList('userlist', '<br>'));
        $msgpan->add(new DataView('msglist', new ArrayDataSource($this, '_msglist'), $this, 'msgListOnRow'));
        $msgpan->msglist->setPageSize(15);
        $msgpan->add(new Paginator('pagmsg', $msgpan->msglist));

        $msgpan->add(new Form('addfileform'))->onSubmit($this, 'OnFileSubmit');
        $msgpan->addfileform->add(new \Zippy\Html\Form\File('addfile'));
        $msgpan->add(new DataView('filelist', new ArrayDataSource($this, '_fileslist'), $this, 'fileListOnRow'));

        $stform = $msgpan->add(new Form('stform'));

        $stform->add(new DropDownChoice('ststatus', $stlist, -1));
        $stform->add(new DropDownChoice('stpr', array(Issue::PRIORITY_NORMAL => 'Нормальный', Issue::PRIORITY_HIGH => 'Высокий', Issue::PRIORITY_LOW => 'Низкий'), 0));
        $stform->add(new DropDownChoice('stuser', array(), 0));
        $stform->onSubmit($this, "onStatus");
        $msgpan->add(new DataView('stlist', new ArrayDataSource($this, '_stlist'), $this, 'stlistOnRow'));

        $this->listpan->list->Reload();

        $this->add(new Panel("editpan"))->setVisible(false);
        $this->editpan->add(new Form('editform'))->onSubmit($this, 'onSaveIssue');
        $this->editpan->editform->add(new TextInput('edittitle'));
        $this->editpan->editform->add(new TextArea('editcontent'));
        $this->editpan->editform->add(new DropDownChoice('editemp', User::findArray('username', '', 'username'), 0));
        $this->editpan->editform->add(new DropDownChoice('editpr', array(Issue::PRIORITY_NORMAL => 'Нормальный', Issue::PRIORITY_HIGH => 'Высокий', Issue::PRIORITY_LOW => 'Низкий'), 0));
        $this->editpan->editform->add(new DropDownChoice('editproj', $projects, $project_id));

        $this->editpan->editform->add(new TextInput('edithours'));

        $this->editpan->editform->add(new ClickLink('editcancel', $this, 'onCancel'));

        $issue = Issue::load($id);
        if ($issue instanceof Issue) {
            $this->openIssue($issue);
        }
        if ($new == true) {
            $this->onNew(null);
        }
        */
    }

    public function onNew($sender) {
        $this->editpan->editform->clean();
        $this->editpan->setVisible(true);
        $this->listpan->setVisible(false);
        $this->_issue = new Issue();
        $this->editpan->editform->editproj->setValue($this->filter->searchproject->getValue());
    }

    public function onCancel($sender) {

        $this->listpan->setVisible(true);
        $this->editpan->setVisible(false);
        $this->listpan->msgpan->setVisible(false);

        $this->listpan->list->Reload();
    }

    public function onFilter($sender) {
        $this->listpan->list->Reload();
    }

    //вывод строки  списка   

    public function listOnRow($row) {
        $issue = $row->getDataItem();
        $row->add(new RedirectLink('issue_number', "\\App\\Modules\\Issue\\Pages\\IssueList", array($issue->issue_id)))->setValue('#' . $issue->issue_id);

        $row->issue_number->setAttribute('class', 'badge badge-success');
        if ($issue->priority == Issue::PRIORITY_HIGH) {
            $row->issue_number->setAttribute('class', 'badge badge-danger');
        }
        if ($issue->priority == Issue::PRIORITY_LOW) {
            $row->issue_number->setAttribute('class', 'badge badge-warning');
        }

        $row->add(new ClickLink('title', $this, 'OnIssue'))->setValue($issue->issue_name);

        $row->add(new Label('emp', \App\Util::getLabelName($issue->username)));
        $row->emp->setAttribute('title', $issue->username);
        if ($this->_issue->issue_id == $issue) {
            $this->listpan->list->setSelectedRow($row);
        }
    }

    public function editOnClick($sender) {


        if ($this->_issue->status == Issue::STATUS_CLOSED) {
            $this->setError('issueclosed');

            return;
        }


        if ($this->_user->rolename != 'admins' && $this->_user->user_id != $this->_issue->createdby) {

            $this->setError('editallowedaa');
            return;
        }

        $this->listpan->setVisible(false);
        $this->editpan->setVisible(true);

        $this->editpan->editform->edittitle->setText($this->_issue->issue_name);
        $this->editpan->editform->editcontent->setText($this->_issue->desc);
        $this->editpan->editform->editpr->setValue($this->_issue->priority);
        $this->editpan->editform->editproj->setValue($this->_issue->project_id);
        $this->editpan->editform->editemp->setValue($this->_issue->user_id);
        $this->editpan->editform->edithours->setText($this->_issue->hours);
    }

    public function onSaveIssue($sender) {


        $this->_issue->issue_name = $sender->edittitle->getText();
        $this->_issue->desc = $sender->editcontent->getText();
        $this->_issue->project_id = $sender->editproj->getValue();
        $this->_issue->priority = $sender->editpr->getValue();
        $this->_issue->hours = $sender->edithours->getText();
        $this->_issue->user_id = $sender->editemp->getValue();
        if ($this->_issue->user_id == 0) {
            $this->_issue->user_id = $this->_user->user_id;
        }
        if ($this->_issue->issue_id == 0) {

            $this->_issue->createdby = $this->_user->user_id;
            $this->_issue->createdbyname = $this->_user->username;
        }
        if ($this->_issue->project_id == 0) {

            $this->setError('noselproject');
            return;
        }
        $emp = User::load($this->_issue->user_id);

        if ($emp->rolename != 'admins') {
            $pr = Project::load($this->_issue->project_id);
            if (in_array($this->_issue->user_id, $pr->getUsers()) == false) {
                $this->setError('empnoaccess');
                return;
            }
        }


        $idnew = $this->_issue->issue_id == 0;
        $this->_issue->save();
        if ($idnew) {
            $this->_issue->addStatusLog(H::l("iscreated"));
        }
        $this->listpan->setVisible(true);
        $this->editpan->setVisible(false);
        $this->listpan->list->Reload();
        $this->openIssue($this->_issue);
    }

    public function OnIssue($sender) {
        $this->openIssue($sender->getOwner()->getDataItem());
    }

    public function openIssue($issue) {
        $this->_issue = $issue;
        if ($this->_issue == null) {
            return;
        }
        $this->listpan->msgpan->setVisible(true);
        $bd = "badge-success";
        if ($this->_issue->priority == Issue::PRIORITY_HIGH) {
            $bd = "badge-danger";
        };
        if ($this->_issue->priority == Issue::PRIORITY_LOW) {
            $bd = "badge-warning";
        };

        $user_id = System::getUser()->user_id;
        $project = Project::load($this->_issue->project_id);
        $this->listpan->msgpan->mcreate->setText('Автор ' . $this->_issue->createdbyname . ' ' . \App\Helper::fd($this->_issue->createdon) . '&nbsp;Проект&nbsp;<a href="/project/' . $project->project_id . '">' . $project->project_name . '</a> ', true);

        $users = \App\Entity\User::findArray('username', "user_id <> {$user_id}  and user_id in (select user_id from issue_projectacc where project_id={$project->project_id} )", 'username');
        foreach ($users as $k => $v) {
            $this->listpan->msgpan->addmsgform->userlist->AddCheckBox($k, false, $v);
        }

        $this->listpan->msgpan->mtitle->setText('<span class="badge ' . $bd . '">#' . $this->_issue->issue_id . '</span> ' . $this->_issue->issue_name, true);
        $this->listpan->msgpan->mdesc->setText($this->_issue->desc, true);
        $this->listpan->msgpan->stform->ststatus->setValue($this->_issue->status);
        $this->listpan->msgpan->stform->stpr->setValue($this->_issue->priority);

        $users = \App\Entity\User::findArray('username', "  user_id in (select user_id from issue_projectacc where project_id={$project->project_id} )", 'username');

        $this->listpan->msgpan->stform->stuser->setOptionList($users);
        $this->listpan->msgpan->stform->stuser->setValue($this->_issue->user_id);

        $this->updateStList();
        $this->updateMessages();

        $this->listpan->list->Reload(false);

    }

    public function deleteOnClick($sender) {

        if ($this->_issue->status == Issue::STATUS_CLOSED) {
            $this->setError('issueclosed');
            return;
        }


        if ($this->_user->rolename != 'admins' && $this->_user->user_id != $this->_issue->createdby) {
            $this->setError('delallowedaa');

            return;
        }


        $msg = Issue::delete($this->_issue->issue_id);
        if (strlen($msg) > 0) {
            $this->setError($msg);
            return;
        }
        $this->listpan->list->Reload();
        $this->listpan->msgpan->setVisible(false);
        $this->resetURL();
    }

    public function onAddMsg($sender) {
        $msg = new \App\Entity\Message();
        $msg->message = $this->listpan->msgpan->addmsgform->msgdata->getText();
        $msg->created = time();
        $msg->user_id = $this->_user->user_id;
        $msg->item_id = $this->_issue->issue_id;
        $msg->item_type = \App\Entity\Message::TYPE_ISSUE;
        if (strlen($msg->message) == 0) {
            return;
        }
        $msg->save();

        $this->listpan->msgpan->addmsgform->msgdata->setText('');
        $this->updateMessages();

        $recievers = $this->listpan->msgpan->addmsgform->userlist->getCheckedList();
        if (!in_array($this->_issue->user_id, $recievers)) {
            $recievers[] = $this->_issue->user_id;
        }

        foreach ($recievers as $u) {
            if ($u == System::getUser()->userid) {
                continue;
            }
            $n = new \App\Entity\Notify();
            $n->user_id = $u;
            $n->message = H::l('msgtask') . "  #{$this->_issue->issue_id} {$this->_issue->issue_name} ";
            $n->message .= "<br>  <a href=\"/issue/{$this->_issue->issue_id}/{$this->_issue->project_id}/#msgankor\">" . H::l('msgreply') . "</a> ";
            $n->sender_id = System::getUser()->user_id;
            $n->save();
        }

        $this->goAnkor('msgankor');
    }

    //список   комментариев
    private function updateMessages() {
        $this->_msglist = \App\Entity\Message::find('item_type = ' . \App\Entity\Message::TYPE_ISSUE . ' and item_id=' . $this->_issue->issue_id, 'message_id');
        $this->listpan->msgpan->msglist->Reload();
        $ocnt = $this->listpan->msgpan->msglist->getPageCount();
        $this->listpan->msgpan->msglist->setCurrentPage($ocnt);
        $this->listpan->msgpan->msglist->Reload(false);
        $this->_fileslist = \App\Helper::getFileList($this->_issue->issue_id, \App\Entity\Message::TYPE_ISSUE);
        $this->listpan->msgpan->filelist->Reload();
    }

    public function msgListOnRow($row) {
        $item = $row->getDataItem();
        $row->add(new Label('msgdate', \App\Helper::fdt($item->created)));
        $row->add(new Label('msguser', $item->username));
        $row->add(new Label('msgdata', nl2br($item->message), true));
        $row->add(new ClickLink('delmsg'))->onClick($this, 'deleteMmsOnClick');
        if ($this->_user->rolename == 'admins' || $this->_user->user_id == $item->user_id) {
            $row->delmsg->setVisible(true);
        } else {
            $row->delmsg->setVisible(false);
        }
    }

    public function deleteMmsOnClick($sender) {
        $msg = $sender->getOwner()->getDataItem();

        \App\Entity\Message::delete($msg->message_id);
        $this->updateMessages();
    }

    public function OnFileSubmit($sender) {

        $file = $sender->addfile->getFile();
        if ($file['size'] > 10000000) {

            $this->setError("filemore10M");
            return;
        }

        \App\Helper::addFile($file, $this->_issue->issue_id, '', 5);

        $this->updateMessages();
        $this->goAnkor('afiles');
    }

    public function filelistOnRow($row) {
        $item = $row->getDataItem();

        $file = $row->add(new \Zippy\Html\Link\BookmarkableLink("filename", _BASEURL . 'loadfile.php?id=' . $item->file_id));
        $file->setValue($item->filename);
        // $file->setAttribute('title', $item->description);

        $row->add(new ClickLink('delfile'))->onClick($this, 'deleteFileOnClick');

        if ($this->_user->rolename == 'admins' || $this->_user->user_id == $this->_issue->createdby) {
            $row->delfile->setVisible(true);
        } else {
            $row->delfile->setVisible(false);
        }
    }

    public function deleteFileOnClick($sender) {
        $file = $sender->owner->getDataItem();
        \App\Helper::deleteFile($file->file_id);
        $this->updateMessages();
        $this->goAnkor('afiles');
    }

    public function onStatus($sender) {

        $olduser = $this->_issue->user_id;
        $oldstatus = $this->_issue->status;
        $oldpriority = $this->_issue->priority;

        $this->_issue->status = $sender->ststatus->getValue();
        $this->_issue->user_id = $sender->stuser->getValue();
        $this->_issue->priority = $sender->stpr->getValue();
        $this->_issue->lastupdate = time();
        $this->_issue->save();

        if ($oldstatus != $this->_issue->status) {
            $this->_issue->addStatusLog(H::l("isstchanged") . " <b>" . $sender->ststatus->getValueName() . "</b>");
        }

        if ($oldpriority != $this->_issue->priority) {
            $this->_issue->addStatusLog(H::l("isprchanged") . " <b>" . $sender->stpr->getValueName() . "</b>");
        }

        $this->updateStList();
        $this->listpan->list->Reload(false);

        if ($olduser != $this->_issue->user_id) {

            $this->_issue->addStatusLog(H::l("isuserchanged") . " <b>" . $sender->stuser->getValueName() . "</b>");

            $n = new \App\Entity\Notify();
            $n->user_id = $this->_issue->user_id;
            $n->message = "  #{$this->_issue->issue_id} {$this->_issue->issue_name} ";
            $n->message .= H::l("isassigned") . "#{$this->_issue->issue_id}  <br>  <a href=\"/issue/{$this->_issue->issue_id}/{$this->_issue->project_id}\">" . H::l("msgopen") . "</a> ";
            $n->sender_id = System::getUser()->user_id;
            $n->save();
        }
    }

    public function stlistOnRow($row) {
        $item = $row->getDataItem();
        $row->add(new Label('stlisttime', \App\Helper::fd($item->createdon)));
        $row->add(new Label('stlistuser', $item->username));
        $row->add(new Label('stlistdesc', $item->description, true));
    }

    public function updateStList() {
        $this->_stlist = $this->_issue->getLogList();
        $this->listpan->msgpan->stlist->Reload();
    }

    
    public function init($args, $post){
        $user = \App\System::getUser();
      
        $post = json_decode($post) ;
        $stlist = Issue::getStatusList();
        $prlist = Issue::getPriorityList();
        $users = \App\Entity\User::findArray("username"," user_id <>" . $user->user_id, 'username');
        $projects = Project::findArray('project_name', $where, 'project_name');
        $times = Project::findArray('project_name', $where, 'project_name');
      
        return json_encode(array(
                  'stlist'=> \App\Util::tokv($stlist) ,
                  'prlist'=> \App\Util::tokv($prlist) ,
                  'emps'=> \App\Util::tokv($users) ,
                  'projects'=> \App\Util::tokv($projects) ,
                
                  'id'=> $this->id ,   //открыть на просмотр
                  'project_id'=> $this->project_id ,   
                  'cuser_id'=> $user->user_id ,   
                  'isnew'=> $this->isnew ? true:false   

                    ), JSON_UNESCAPED_UNICODE);     
      
   }    
    
   public function empbyproj($args, $post=null){ 
      //  $conn = \ZDB\DB::getConnect();
        $users = \App\Entity\User::findArray("username","user_id in(select  user_id  from issue_projectacc   where   project_id={$args[0]}   )","username");
            
       
        return json_encode(array('emps'=>\App\Util::tokv($users)) , JSON_UNESCAPED_UNICODE);     
    
   }
   
   public function loadhistory($args, $post=null){ 
        $conn = \ZDB\DB::getConnect();
        $res = $conn->Execute("select h.*,u.username from issue_history h join users_view u on h.user_id=u.user_id  where  issue_id={$args[0]} order  by  hist_id");
        $hist = array();    
        foreach($res as $row )  {
           $hist[]=array( 
               "user"=>$row["username"],
               "time"=> h::fd( strtotime( $row["createdon"] ) ),
               "desc"=>$row["description"],
           );
        }
        return json_encode(array('hist'=>$hist) , JSON_UNESCAPED_UNICODE);     
    
   }
   
   public function getList($args, $post){
       
        $post = json_decode($post) ;
        $user = System::getUser();
       
        $number = $post->searchnumber;
        $status = $post->searchstatus;
        $emp = $post->searchemp;
        $project = $post->searchproject;
        $sort = $post->searchsort;
        $orderby ="";
        if($sort==0){
            $orderby = "priority asc";
        }
        if($sort==1){
            $orderby = "issue_id desc";
        }
        if($sort==2){
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
        foreach(Issue::find($where, $orderby ) as $i)  {
           $ia = array();
           $ia['issue_id']  =  $i->issue_id;
           $ia['issue_name']  =  $i->issue_name;
           $ia['prcl'] = "badge badge-success";
           if ($i->priority == Issue::PRIORITY_HIGH)$ia['prcl'] = "badge badge-danger";
           if ($i->priority == Issue::PRIORITY_LOW)$ia['prcl'] = "badge badge-warning";
           if ($i->status == Issue::STATUS_CLOSED)$ia['prcl'] = "badge badge-secondary";
           $ia['url'] = "/index.php?p=App/Modules/Issue/Pages/IssueList&args=".$i->issue_id;
           $ia["emp"] =  \App\Util::getLabelName($i->username) ;
           $ia["emptitle"] =  $i->username ;
            

           $list[]=  $ia;  
        }
            
        return json_encode(array('issuelist'=>$list,'cnt'=> Issue::findCnt($where) ) , JSON_UNESCAPED_UNICODE);     
      
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
       $ret["createdon"] = H::fd( $issue->createdon );
       $ret["updateddon"] = H::fdt( $issue->lastupdate );
      
       return json_encode($ret, JSON_UNESCAPED_UNICODE);     
       
  }
 
   public function saveissue($args, $post) {
        $post = json_decode($post) ;
         
        if($post->id >0){
            $issue = Issue::load($post->id);
        }
        else {
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
            $issue->addStatusLog(H::l("iscreated"));
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
           $desc=H::l("isstchanged") . " <b>" . $stlist[$issue->status] . "</b>"  ;
        }

        if($issue->priority != $post->priority){
           $issue->priority = $post->priority;    
           $prlist = Issue::getPriorityList() ;
           $desc=H::l("isprchanged") . " <b>" . $prlist[$issue->priority] . "</b>" ;
        }        
        
        if($issue->user_id != $post->user_id){
          $issue->user_id = $post->user_id;    
          $user = \App\Entity\User::load($issue->user_id) ;
          $desc=H::l("isuserchanged") . " <b>" . $user->username . "</b>";
        }
        
    
        if(strlen($desc)>0)  {
            $issue->addStatusLog($desc);
            $issue->save();            
        }

        return "";
     }
  
   public function del($args, $post=null){ 
            
           Issue::delete($args[0])  ;     
    
   }
   
   public function getFileList($args, $post){
        $user = \App\System::getUser() ;
        $i = Issue::load($args[0]);
   
        $filelist = array();
        foreach( H::getFileList($args[0], \App\Entity\Message::TYPE_ISSUE) as $f) {
          
          
            $url = _BASEURL . 'loadfile.php?id=' . $f->file_id;
            if (strlen($f->mime) > 0) {
                $url = $url . '&im=1';
            }
    
  
            
           $filelist[]=array(
           'file_id'=>$f->file_id,
           'url'=>$url,
           'filename'=>$f->filename,
  
           'candel'=>($user->user_id == $i->user_id || $user->rolename  =='admins' )           
           ) ; 
        }
        
             
        return json_encode($filelist, JSON_UNESCAPED_UNICODE);     
      
   }
   
   public function addFile($args, $post){
    
        
        $file =  @$_FILES['pfile']  ;
     
        if(strlen($file['tmp_name'])==0 ) return;
                                     
        H::addFile($file, $args[0], '', \App\Entity\Message::TYPE_ISSUE);
         
 
   }
  
   public function delFile($args, $post){
    
         \App\Helper::deleteFile($args[0]);
 
   }
   
   public function delMsg($args, $post){
    
      \App\Entity\Message::delete($args[0]);
  
   }
  
   public function addMsg($args, $post){
       
        $users = trim ($post["users"],",");          
        $msg = trim($post["msg"]);          
        if(strlen($msg)==0)  return;
        $msg = new \App\Entity\Message();
        $msg->message = $post["msg"];
        $msg->created = time();
        $msg->user_id =  \App\System::getUser()->user_id;
        $msg->item_id = $args[0];
        $msg->item_type = \App\Entity\Message::TYPE_ISSUE;

        $msg->save();
   
   
      $issue = Issue::load($args[0]);
      if(strlen($users)==0) return;
      foreach (explode(",",$users) as $u) {
            if ($u == System::getUser()->userid) {
                continue;
            }
            $n = new \App\Entity\Notify();
            $n->user_id = $u;
            $n->message = H::l('msgtask') . "  #{$issue->issue_id} {$issue->issue_name} ";
            $n->message .= "<br>  <a href=\"/issue/{$issue->issue_id}/{$issue->project_id}/#msgankor\">" . H::l('msgreply') . "</a> ";
            $n->sender_id = System::getUser()->user_id;
            $n->save();
        }

   
  
   }
   
   public function getMsgList($args, $post){
           $user = \App\System::getUser() ;
   
         $where = 'item_type = 5 and item_id=' . $args[0] ;

         $cnt =  \App\Entity\Message::findCnt($where)    ;       
         
         $msglist = \App\Entity\Message::find($where, 'message_id');
 
         $list = array();
         foreach( $msglist as $m)  {
           $msg = array() ;
           $msg['data']  = $m->message;
           $msg['candel']  = ($user->user_id == $m->user_id || $user->rolename  =='admins' ) ;
           $msg['user']  = $m->username;
           $msg['message_id']  = $m->message_id;
           $msg['date']  = H::fd($m->created);

            
           $list[]=$msg;
         } 
                     
         return json_encode(array('list'=>$list,'cnt'=>$cnt  ), JSON_UNESCAPED_UNICODE);     
      
   }
  
   
   
   
}
 