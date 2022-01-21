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

    public $_project   = null;
    public $_msglist   = array();
    public $_fileslist = array();
    public $_stlist    = array();

    public function __construct($id = 0) {
        parent::__construct();
        $this->_user = System::getUser();
        
        $allow = (strpos($this->_user->modules, 'issue') !== false || $this->_user->rolename == 'admins');
        if (!$allow) {
            System::setErrorMsg(H::l('noaccesstopage'));
            App::RedirectError();
            return;
        }

        $this->_stlist = Project::getStatusList();
        
        $projectpanel = $this->add(new Panel('projectpanel'));

        $projectpanel->add(new Form('filter'))->onSubmit($this, 'filterOnSubmit');

        $clist = Customer::findArray('customer_name', 'customer_id in (select customer_id from issue_projectlist )', 'customer_name');
        $projectpanel->filter->add(new DropDownChoice('searchcust', $clist, 0));
        $projectpanel->filter->add(new DropDownChoice('searchstate', $this->_stlist, 0));
        $projectpanel->filter->add(new TextInput('searchnumber'));

        $list = $projectpanel->add(new DataView('projectlist', new ProjectDS($this), $this, 'listOnRow'));
        $list->setPageSize(15);
        $this->projectpanel->add(new Paginator('pag', $list));
        $this->projectpanel->projectlist->Reload();

        $projectpanel->add(new ClickLink('padd'))->onClick($this, 'addOnClick');
        $this->add(new Form('projectform'))->setVisible(false);
        $this->projectform->add(new TextInput('editname'));
        $this->projectform->add(new AutocompleteTextInput('editcust'))->onText($this, 'OnAutoCustomer');
        $this->projectform->add(new TextArea('editdesc'));
        $this->projectform->add(new SubmitButton('save'))->onClick($this, 'saveOnClick');
        $this->projectform->add(new Button('cancel'))->onClick($this, 'cancelOnClick');
        $this->projectform->add(new \Zippy\Html\Form\CheckBoxList('userlist', '<br>'));

        $this->add(new Panel("showpan"))->setVisible(false);
        $this->showpan->add(new ClickLink('back', $this, 'cancelOnClick'));
        $this->showpan->add(new ClickLink('toilist', $this, 'toilistOnClick'));
        $this->showpan->add(new ClickLink('newissue', $this, 'newissueOnClick'));

        $this->showpan->add(new Label('mtitle'));
        $this->showpan->add(new Label('mdesc'));
        $this->showpan->add(new Form('addmsgform'))->onSubmit($this, 'onAddMsg');
        $this->showpan->addmsgform->add(new TextArea('msgdata'));
        $this->showpan->add(new DataView('msglist', new ArrayDataSource($this, '_msglist'), $this, 'msgListOnRow'));
        $this->showpan->msglist->setPageSize(15);
        $this->showpan->add(new Paginator('pagmsg', $this->showpan->msglist));

        $this->showpan->add(new Form('addfileform'))->onSubmit($this, 'OnFileSubmit');
        $this->showpan->addfileform->add(new \Zippy\Html\Form\File('addfile'));
        $this->showpan->add(new DataView('filelist', new ArrayDataSource($this, '_fileslist'), $this, 'fileListOnRow'));

        $this->showpan->add(new Form('statusform'))->onSubmit($this, 'onStatus');
        $this->showpan->statusform->add(new DropDownChoice('stlist', $this->_stlist, 0));

        if ($id > 0) {
            $this->open($id);
        }   
    }

    public function listOnRow($row) {
        $pr = $row->getDataItem();

        $row->add(new Label('project_name', $pr->project_name));
        $row->add(new Label('customer_name', $pr->customer_name));

        $row->add(new Label('status', $this->_stlist[$pr->status]));
        $row->add(new Label('inew', $pr->inew))->setVisible($pr->inew > 0);
        $row->add(new Label('iproc', $pr->iproc))->setVisible($pr->iproc > 0);
        $row->add(new Label('iclose', $pr->iclose))->setVisible($pr->iclose > 0);

        $row->add(new ClickLink('preview'))->onClick($this, 'previewOnClick');
        $user = System::getUser();

        $row->add(new ClickLink('edit'))->onClick($this, 'editOnClick');
        $row->add(new ClickLink('delete'))->onClick($this, 'deleteOnClick');

        if ($user->rolename != 'admins' && $user->user_id != $pr->creator_id) {
            $row->edit->setVisible(false);
            $row->delete->setVisible(false);
        }


        if ($pr->status == Project::STATUS_CLOSED) {
            $row->edit->setVisible(false);
            $row->delete->setVisible(false);
        }
    }

    public function editOnClick($sender) {
        $this->_project = $sender->owner->getDataItem();

        $user = System::getUser();

        $this->projectpanel->setVisible(false);
        $this->projectform->setVisible(true);
        $this->projectform->editname->setText($this->_project->project_name);
        $this->projectform->editdesc->setText($this->_project->desc);
        $this->projectform->editcust->setKey($this->_project->customer_id);
        $this->projectform->editcust->setText($this->_project->customer_name);
        $this->updateUsers();
    }

    public function deleteOnClick($sender) {

        $del = Project::delete($sender->owner->getDataItem()->project_id);
        if (strlen($del) > 0) {
            $this->setError($del);
            return;
        }
        $this->projectpanel->projectlist->Reload();
        $this->resetURL();
    }

    public function filterOnSubmit($sender) {
        $this->projectpanel->projectlist->Reload();
    }

    public function addOnClick($sender) {
        $this->projectpanel->setVisible(false);
        $this->projectform->setVisible(true);
        $this->projectform->editname->setText('');
        $this->projectform->editdesc->setText('');
        $this->_project = new Project();
        $user = System::getUser();
        $this->_project->creator_id = $user->user_id;
        $this->_project->creator = $user->username;

        $this->updateUsers();

        $this->projectform->userlist->setAllChecked(0);
    }

    public function saveOnClick($sender) {


        $this->_project->project_name = $this->projectform->editname->getText();
        $this->_project->customer_id = $this->projectform->editcust->getKey();
        $this->_project->desc = $this->projectform->editdesc->getText();
        if ($this->_project->project_name == '') {

            $this->setError("entername");
            return;
        }
        $users = $this->projectform->userlist->getCheckedList();

        if (in_array($this->_project->creator_id, $users) == false) {
            $users[] = $this->_project->creator_id;
        }


        $this->_project->save();
        $this->_project->setUsers($users);

        $this->projectform->setVisible(false);
        $this->projectpanel->setVisible(true);
        $this->projectpanel->projectlist->Reload();
    }

    public function cancelOnClick($sender) {
        $this->showpan->setVisible(false);
        $this->projectform->setVisible(false);
        $this->projectpanel->setVisible(true);
    }

    public function OnAutoCustomer($sender) {
        $text = Customer::qstr('%' . $sender->getText() . '%');
        return Customer::findArray("customer_name", "status=0 and customer_name like " . $text);
    }

    public function previewOnClick($sender) {

        $this->open($sender->getOwner()->getDataItem()->project_id);
    }

    public function open($id) {

        $this->_project = Project::load($id);
        if ($this->_project == null) {
            return;
        }
        $this->showpan->mtitle->setText($this->_project->project_name);
        $this->showpan->mdesc->setText($this->_project->desc, true);

        $this->projectpanel->setVisible(false);

        $this->showpan->setVisible(true);
        $this->updateMessages();

        $this->showpan->statusform->stlist->setValue($this->_project->status);
    }

    public function toilistOnClick($sender) {
        App::Redirect("\\App\\Modules\\Issue\\Pages\\IssueList", 0, $this->_project->project_id);
    }

    public function onAddMsg($sender) {
        $msg = new \App\Entity\Message();
        $msg->message = $this->showpan->addmsgform->msgdata->getText();
        $msg->created = time();
        $msg->user_id = $this->_user->user_id;
        $msg->item_id = $this->_project->project_id;
        $msg->item_type = \App\Entity\Message::TYPE_PROJECT;
        if (strlen($msg->message) == 0) {
            return;
        }
        $msg->save();

        $this->showpan->addmsgform->msgdata->setText('');
        $this->updateMessages();

        $this->goAnkor('msgankor');
    }

    private function updateMessages() {
        $this->_msglist = \App\Entity\Message::find('item_type = 6 and item_id=' . $this->_project->project_id, 'message_id');
        $this->showpan->msglist->Reload();
        $ocnt = $this->showpan->msglist->getPageCount();
        $this->showpan->msglist->setCurrentPage($ocnt);
        $this->showpan->msglist->Reload(false);
        $this->_fileslist = \App\Helper::getFileList($this->_project->project_id, \App\Entity\Message::TYPE_PROJECT);
        $this->showpan->filelist->Reload();
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

        \App\Helper::addFile($file, $this->_project->project_id, '', 6);

        $this->updateMessages();
    }

    public function filelistOnRow($row) {
        $item = $row->getDataItem();

        $file = $row->add(new \Zippy\Html\Link\BookmarkableLink("filename", _BASEURL . 'loadfile.php?id=' . $item->file_id));
        $file->setValue($item->filename);
        // $file->setAttribute('title', $item->description);

        $row->add(new ClickLink('delfile'))->onClick($this, 'deleteFileOnClick');

        if ($this->_user->rolename == 'admins' || $this->_user->user_id == $this->_project->creator_id) {
            $row->delfile->setVisible(true);
        } else {
            $row->delfile->setVisible(false);
        }
    }

    public function deleteFileOnClick($sender) {
        $file = $sender->owner->getDataItem();
        \App\Helper::deleteFile($file->file_id);
        $this->updateMessages();
    }

    public function onStatus($sender) {
        $this->_project->status = $this->showpan->statusform->stlist->getValue();
        $this->_project->save();
        $this->projectpanel->projectlist->Reload();
    }

    public function updateUsers() {
        $user = System::getUser();
        $this->projectform->userlist->clean();

        $pusers = $this->_project->getUsers();
        $users = \App\Entity\User::find(" user_id <>" . $user->user_id, 'username');
        foreach ($users as $k => $v) {
            if ($v->rolename != 'admins' && strpos($v->modules, 'issue') === false) {
                continue;
            }
            $inlist = in_array($k, $pusers);
            $this->projectform->userlist->AddCheckBox($k, $inlist, $v->username);
        }
    }

    public function newissueOnClick($sender) {
        App::Redirect("\\App\\Modules\\Issue\\Pages\\IssueList", 0, $this->_project->project_id, true);
    }

    
      //vue
    public function ontextCustomers($args,$post) {
 
        
         $text = $args[0];
         $text= Customer::qstr('%'.$text.'%') ;
 
       $list = H::kv2o(Customer::findArray("customer_name", "status=0 and customer_name like " . $text));
     
       return json_encode($list, JSON_UNESCAPED_UNICODE);          
        
    }
  
      public function delpr($args, $post ){
          if($args[0] > 0) {
             Project::delete($args[0] );     
          }  
          return json_encode(array('success'=>true ), JSON_UNESCAPED_UNICODE);     
            
      }
      public function savepr($args, $post ){
        
        $data = @json_decode($post) ;        
        
        
        $project = Project::load($data->project_id);
        if($project== null)$project = new Project();
        $user = System::getUser();
        $project->creator_id = $user->user_id;
        $project->creator = $user->username;
        $project->project_name =   $data->project_name   ;
        $project->customer_id =   $data->customer_id   ;
        $project->desc =   $data->desc   ;
     
     
        $project->save();
          
         return json_encode(array('success'=>true ), JSON_UNESCAPED_UNICODE);     
      
    }  
    
    public function loadinit($args, $post=null){
           
         $stlist = H::kv2o(Project::getStatusList());
         $custlist =  H::kv2o( Customer::findArray('customer_name', 'customer_id in (select customer_id from issue_projectlist )', 'customer_name') );
   
         return json_encode(array('stlist'=>$stlist,'custlist'=>$custlist), JSON_UNESCAPED_UNICODE);     
      
    }
     public function loaddata($args, $post){
        $number = trim($post['searchnumber']);
        $cust = $post['searchcust'];    
        $status = $post['searchstate'];
      
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
        foreach(Project::find($where, "project_id desc", $args[1], $args[0]) as $p)  {
           $pa = $p->getData() ;
           $pa['status_id']  = $p->status;
           $pa['status']  = $stlist[$p->status];
           unset($pa['details']);
           $prlist[]=  $pa;  
        }
            
         return json_encode(array('prlist'=>$prlist,'allcnt'=> Project::findCnt($where)  ), JSON_UNESCAPED_UNICODE);     
      
    }
    
     public function getusers() {
        $user = System::getUsers($args, $post=null);
        $this->projectform->userlist->clean();

        $pusers = $this->_project->getUsers();
        $users = \App\Entity\User::find(" user_id <>" . $user->user_id, 'username');
        foreach ($users as $k => $v) {
            if ($v->rolename != 'admins' && strpos($v->modules, 'issue') === false) {
                continue;
            }
            $inlist = in_array($k, $pusers);
            $this->projectform->userlist->AddCheckBox($k, $inlist, $v->username);
        }
    }   
}

class ProjectDS implements \Zippy\Interfaces\DataSource
{

    private $page;

    public function __construct($page) {
        $this->page = $page;
    }

    private function getWhere() {

        $number = trim($this->page->projectpanel->filter->searchnumber->getText());
        $cust = $this->page->projectpanel->filter->searchcust->getValue();
        $status = $this->page->projectpanel->filter->searchstate->getValue();

        $conn = \ZDB\DB::getConnect();

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

        return $where;
    }

    public function getItemCount() {
        return Project::findCnt($this->getWhere());
    }

    public function getItems($start, $count, $sortfield = null, $asc = null) {

        $sort = "project_id desc";

        return Project::find($this->getWhere(), $sort, $count, $start);
    }

    public function getItem($id) {

    }

}
