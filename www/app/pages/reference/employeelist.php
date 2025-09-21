<?php

namespace App\Pages\Reference;

use App\Entity\Employee;
use App\Helper as H;
use App\System;
use ZCL\DB\EntityDataSource as EDS;
use Zippy\Html\DataList\DataView;
use Zippy\Html\Form\Button;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\TextArea;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Form\Date;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Panel;
use Zippy\Html\DataList\DataRow;
use Zippy\Html\DataList\ArrayDataSource;
use Zippy\Binding\PropertyBinding as Bind;


class EmployeeList extends \App\Pages\Base
{
    private $_employee;
    private $_blist;
    public $_msglist    = array();
    public $_fileslist  = array();
   
    private $_zlist=[];
    private $_alist=[];
      
    public function __construct() {
        parent::__construct();
        if (false == \App\ACL::checkShowRef('EmployeeList')) {
            return;
        }

        $this->_blist = \App\Entity\Branch::getList(\App\System::getUser()->user_id);


        $this->add(new Panel('employeetable'))->setVisible(true);
        $this->employeetable->add(new ClickLink('addnew'))->onClick($this, 'addOnClick');

        $this->employeetable->add(new Form('filter'))->onSubmit($this, 'OnFilter');
        $this->employeetable->filter->add(new TextInput('searchtext'));


        $this->employeetable->add(new DataView('employeelist', new EmpDataSource($this), $this, 'employeelistOnRow'));
        $this->employeetable->employeelist->setPageSize(H::getPG());
        $this->employeetable->add(new \Zippy\Html\DataList\Paginator('pag', $this->employeetable->employeelist));

        $this->add(new Form('employeedetail'))->setVisible(false);

        $this->employeedetail->add(new SubmitButton('save'))->onClick($this, 'saveOnClick');
        $this->employeedetail->add(new Button('cancel'))->onClick($this, 'cancelOnClick');
        $this->employeedetail->add(new DropDownChoice('editlogin'));
        $this->employeedetail->add(new TextInput('editemp_name'));
        $this->employeedetail->add(new DropDownChoice('editbranch', $this->_blist, 0));

        $this->employeedetail->add(new Date('edithiredate'));
        $this->employeedetail->add(new TextInput('editphone'));
        $this->employeedetail->add(new TextInput('editemail'));
        $this->employeedetail->add(new TextArea('editcomment'));
        $this->employeedetail->add(new TextInput('editdepartment'));
        $this->employeedetail->add(new TextInput('editposition'));
        $this->employeedetail->add(new CheckBox('editdisabled'));

        $this->employeedetail->add(new DropDownChoice('editztype', array("1" => "Оклад", "2" => "Погодинна оплата", "3" => "Понарядна"), 1))->onChange($this, "onType");
        $this->employeedetail->add(new TextInput('editzhour'));
        $this->employeedetail->add(new TextInput('editzmon'));
        $this->employeedetail->add(new TextInput('editadvance'));
        $this->employeedetail->add(new TextInput('editchildren'));
        $this->employeedetail->add(new CheckBox('editinvalid'));
        $this->employeedetail->add(new CheckBox('editcoworker'));

   
 
        $this->add(new Panel('contentview'))->setVisible(false);
        $this->contentview->add(new Label("accname"));
        $this->contentview->add(new ClickLink("accback"))->onClick($this, 'cancelOnClick');
        
        
        
        $this->contentview->add(new Form('addmsgform'))->onSubmit($this, 'OnMsgSubmit');
        $this->contentview->addmsgform->add(new TextArea('addmsg'));
        $this->contentview->add(new DataView('dw_msglist', new ArrayDataSource(new Bind($this, '_msglist')), $this, 'msgListOnRow'));
        
        $this->contentview->add(new Form('addfileform'))->onSubmit($this, 'OnFileSubmit');
        $this->contentview->addfileform->add(new \Zippy\Html\Form\File('addfile'));
        $this->contentview->addfileform->add(new TextInput('adddescfile'));
        $this->contentview->add(new DataView('dw_files', new ArrayDataSource(new Bind($this, '_fileslist')), $this, 'fileListOnRow'));

        $this->contentview->add(new Form('filters'))->onSubmit($this, 'OnSubmitS');

        $d = new \App\DateTime() ;
        $d = $d->startOfMonth()->subMonth(1) ;

        $this->contentview->filters->add(new Date('from', $d->getTimestamp()));
        $this->contentview->filters->add(new Date('to' ));
       
        $conn = \ZDB\DB::getConnect();
      
        $sql = "select coalesce(sum(0-amount),0) as am, emp_id from empacc where  optype in(3,4)  group by emp_id "  ;
        foreach($conn->Execute($sql) as $r){
           $this->_zlist[$r['emp_id']]= H::fa($r['am']) ;
        }
        $sql = "select coalesce(sum(0-amount),0) as am, emp_id from empacc where  optype in(105)  group by emp_id   "  ;
        foreach($conn->Execute($sql) as $r){
           $this->_alist[$r['emp_id']]= H::fa($r['am']) ;
        }
           
        $this->employeetable->employeelist->Reload();

    }


    public function onType($sender) {
        $t = $sender->getValue();
        $this->employeedetail->editzmon->setVisible($t == 1);
        $this->employeedetail->editzhour->setVisible($t == 2);
    }


    public function employeelistOnRow(\Zippy\Html\DataList\DataRow $row) {
        $item = $row->getDataItem();


        $row->add(new Label('position', $item->position));
        $row->add(new Label('department', $item->department));
        $row->add(new Label('emp_name', $item->emp_name));
        $row->add(new Label('login', $item->login));
        $row->add(new Label('branch', $this->_blist[$item->branch_id] ??''));

        $row->add(new Label('zarp',""));        
        $row->add(new Label('av',""));        
        
        if(($this->_zlist[$item->employee_id] ?? 0 )>0){
           $row->zarp->setText(H::fa($this->_zlist[$item->employee_id])); 
        }
        if(($this->_alist[$item->employee_id] ?? 0 )>0){
           $row->av->setText(H::fa($this->_alist[$item->employee_id])); 
        }
        
        $row->add(new ClickLink('edit'))->onClick($this, 'editOnClick');
        $row->add(new ClickLink('delete'))->onClick($this, 'deleteOnClick');

     
        $row->add(new ClickLink('contentlist'))->onClick($this, 'viewContentOnClick');
             
    }

    public function deleteOnClick($sender) {
        if (false == \App\ACL::checkDelRef('EmployeeList')) {
            return;
        }
        $this->contentview->setVisible(false);

        Employee::delete($sender->owner->getDataItem()->employee_id);
        $this->employeetable->employeelist->Reload();
    }



    public function editOnClick($sender) {
        $this->_employee = $sender->owner->getDataItem();
        $this->employeetable->setVisible(false);
        $this->employeedetail->setVisible(true);

        if (strlen($this->_employee->login) > 0) {
            $this->employeedetail->editlogin->setOptionList(Employee::getFreeLogins($this->_employee->login));
            $this->employeedetail->editlogin->setValue($this->_employee->login);
        } else {
            $this->employeedetail->editlogin->setOptionList(Employee::getFreeLogins());
            $this->employeedetail->editlogin->setValue('0');
        }

        $this->employeedetail->editemp_name->setText($this->_employee->emp_name);
        $this->employeedetail->editcomment->setText($this->_employee->comment);
        $this->employeedetail->editdepartment->setText($this->_employee->department);
        $this->employeedetail->editposition->setText($this->_employee->position);
        $this->employeedetail->editemail->setText($this->_employee->email);
        $this->employeedetail->editphone->setText($this->_employee->phone);
        $this->employeedetail->editbranch->setValue($this->_employee->branch_id);
        $this->employeedetail->editdisabled->setChecked($this->_employee->disabled);

        $this->employeedetail->edithiredate->setDate($this->_employee->hiredate);
        $this->employeedetail->editztype->setValue($this->_employee->ztype);
        $this->employeedetail->editzhour->setText($this->_employee->zhour);
        $this->employeedetail->editzmon->setText($this->_employee->zmon);
        $this->employeedetail->editadvance->setText($this->_employee->advance);
        $this->employeedetail->editchildren->setText($this->_employee->children);
        $this->employeedetail->editinvalid->setChecked($this->_employee->invalid);
        $this->employeedetail->editcoworker->setChecked($this->_employee->coworker);
        $dp =  Employee::getDP()  ;
        $this->employeedetail->editposition->setDataList($dp['p']);
        $this->employeedetail->editdepartment->setDataList($dp['d']);
  
        $this->contentview->setVisible(false);
        $this->onType($this->employeedetail->editztype);

    }

    public function addOnClick($sender) {
        $this->employeetable->setVisible(false);
        $this->employeedetail->setVisible(true);
        // Очищаем  форму
        $this->employeedetail->clean();
        $this->employeedetail->edithiredate->setDate(time());
        $this->employeedetail->editlogin->setOptionList(Employee::getFreeLogins());
        $this->employeedetail->editlogin->setValue('0');
        $this->employeedetail->editztype->setValue('1');

        $b = \App\System::getBranch();
        $this->employeedetail->editbranch->setValue($b > 0 ? $b : 0);
        
        $dp =  Employee::getDP()  ;
        $this->employeedetail->editposition->setDataList($dp['p']);
        $this->employeedetail->editdepartment->setDataList($dp['d']);
        $this->contentview->setVisible(false);      
        $this->_employee = new Employee();
    }

    public function saveOnClick($sender) {
        if (false == \App\ACL::checkEditRef('EmployeeList')) {
            return;
        }

        $login = trim($this->employeedetail->editlogin->getValue());

        if (strlen($login) < 2) {

            $login = "";
        }
        $this->_employee->login = $login;
        $this->_employee->emp_name = trim($this->employeedetail->editemp_name->getText());
        $this->_employee->emp_name = str_replace("'", "`", $this->_employee->emp_name);

        $this->_employee->email = $this->employeedetail->editemail->getText();
        $this->_employee->phone = $this->employeedetail->editphone->getText();
        $this->_employee->comment = $this->employeedetail->editcomment->getText();
        $this->_employee->department = $this->employeedetail->editdepartment->getText();
        $this->_employee->position = $this->employeedetail->editposition->getText();

        $this->_employee->branch_id = $this->employeedetail->editbranch->getValue();

        $this->_employee->hiredate = $this->employeedetail->edithiredate->getDate();
        $this->_employee->ztype = $this->employeedetail->editztype->getValue();
        $this->_employee->zhour = $this->employeedetail->editzhour->getText();
        $this->_employee->zmon = $this->employeedetail->editzmon->getText();
        $this->_employee->advance = $this->employeedetail->editadvance->getText();
        $this->_employee->children = $this->employeedetail->editchildren->getText();


        $this->_employee->invalid = $this->employeedetail->editinvalid->isChecked() ? 1 : 0;
        $this->_employee->coworker = $this->employeedetail->editcoworker->isChecked() ? 1 : 0;

        $this->_employee->disabled = $this->employeedetail->editdisabled->isChecked() ? 1 : 0;
        if ($this->_employee->disabled == 1) {
            $u = \App\Entity\User::getByLogin($this->_employee->login);
            if($u != null) {
              $u->userpass = '';
              $u->save();
              $this->_employee->login = '';
            }
        }

        $this->_employee->save();

        $this->employeedetail->setVisible(false);
        $this->employeetable->setVisible(true);
        $this->employeetable->employeelist->Reload();
    }

    public function cancelOnClick($sender) {
        $this->employeetable->setVisible(true);
        $this->employeedetail->setVisible(false);
        $this->contentview->setVisible(false);
        
    }

 
   public function viewContentOnClick($sender) {

        $this->employeetable->setVisible(false);
        $this->contentview->setVisible(true);
        $this->_employee = $sender->getOwner()->getDataItem();
        $this->contentview->accname->setText($this->_employee->emp_name);

      
        $this->updateMessages();
        $this->updateFiles();
            
        $this->_tvars['detacc'] =[];       
   }
   
 //лицевой счет
    public function OnSubmitS($sender) {


        $emp_id =$this->_employee->employee_id ;
        $from =  $this->contentview->filters->from->getDate();
        $to =  $this->contentview->filters->to->getDate();

        $conn = \ZDB\DB::getConnect();
          
         $sql = "select * from empacc_view where     emp_id = {$emp_id}   and createdon >= " . $conn->DBDate($from) ;
         if($to > 0) {
             $sql = $sql . "  and createdon <= " . $conn->DBDate($to)  ;
         }
         $sql = $sql ." order  by  ea_id ";
        
        $en=\App\Entity\EmpAcc::getNames();

        $detail = array();
        
        foreach ($conn->Execute($sql) as $row) {
              $detail[] = array(
                'notes'    => $row['notes'],
                'opname'    => $en[$row['optype']],
                'dt'    => H::fd(strtotime($row['createdon'])),
                'amount'    => H::fa($row['amount']),
                'doc'   => $row['document_number'] 
                
            );


             
        }

        $this->_tvars['detacc']  =  $detail;

    }
       
   
   public function OnMsgSubmit($sender) {
        $msg = new \App\Entity\Message();
        $msg->message = $this->contentview->addmsgform->addmsg->getText();
        $msg->created = time();
        $msg->user_id = System::getUser()->user_id;
        $msg->item_id = $this->_employee->employee_id;
        $msg->item_type = \App\Entity\Message::TYPE_EMP;
        if (strlen($msg->message) == 0) {
            return;
        }
        $msg->save();

        $this->contentview->addmsgform->addmsg->setText('');
        $this->updateMessages();
        $this->goAnkor('contentviewlink');
      //  $this->customertable->listform->customerlist->Reload(false);
    }

    //список   комментариев
    private function updateMessages() {
        $this->_msglist = \App\Entity\Message::find('item_type = 9 and item_id=' . $this->_employee->employee_id, 'message_id');
        $this->contentview->dw_msglist->Reload();
    }

    //вывод строки  коментария
    public function msgListOnRow(DataRow $row) {
        $item = $row->getDataItem();

        $row->add(new Label("msgdata", nl2br($item->message)));
        $row->add(new Label("msgdate", \App\Helper::fdt($item->created)));
        $row->add(new Label("msguser", $item->username));

        $row->add(new ClickLink('delmsg'))->onClick($this, 'deleteMsgOnClick');
    }

    //удаление коментария
    public function deleteMsgOnClick($sender) {
        $msg = $sender->owner->getDataItem();
        \App\Entity\Message::delete($msg->message_id);
        $this->updateMessages();

    }

    public function OnFileSubmit($sender) {

        $file = $this->contentview->addfileform->addfile->getFile();
        if ($file['size'] > 10000000) {
            $this->setError("Файл більше 10 МБ!");
            return;
        }

        H::addFile($file, $this->_employee->employee_id, $this->contentview->addfileform->adddescfile->getText(), \App\Entity\Message::TYPE_EMP);
        $this->contentview->addfileform->adddescfile->setText('');
        $this->updateFiles();
    }

    // обновление  списка  прикрепленных файлов
    private function updateFiles() {
        $this->_fileslist = H::getFileList($this->_employee->employee_id, \App\Entity\Message::TYPE_EMP);
        $this->contentview->dw_files->Reload();
    }

    //вывод строки  прикрепленного файла
    public function filelistOnRow(DataRow $row) {
        $item = $row->getDataItem();

        $file = $row->add(new \Zippy\Html\Link\BookmarkableLink("filename", _BASEURL . 'loadfile.php?id=' . $item->file_id));
        $file->setValue($item->filename);
        $file->setAttribute('title', $item->description);
        $user= \App\System::getUser() ;
        $row->add(new ClickLink('delfile',$this, 'deleteFileOnClick'))->setVisible(   $item->user_id == $user->user_id || $user->rolename=='admins'  ); 
    }

    //удаление прикрепленного файла
    public function deleteFileOnClick($sender) {
        $file = $sender->owner->getDataItem();
        H::deleteFile($file->file_id);
        $this->updateFiles();
  
    }

  
    
}


class EmpDataSource implements \Zippy\Interfaces\DataSource
{
    private $page;

    public function __construct($page) {

        $this->page = $page;
    }

    private function getWhere() {
        $where ="";

        $text = trim($this->page->employeetable->filter->searchtext->getText()) ;
        $texts = Employee::qstr('%'.$text.'%') ;
        $textp = Employee::qstr('%<phone>' . $text.'</phone>%') ;
        if(strlen($text)>0) {
            $where = " emp_name like {$texts} or  detail like {$textp} ";
        }

        return $where;
    }

    public function getItemCount() {
        return Employee::findCnt($this->getWhere());
    }

    public function getItems($start, $count, $orderbyfield = null, $desc = true) {
        return Employee::find($this->getWhere(), "disabled, emp_name", $count, $start);
    }

    public function getItem($id) {
        return Employee::load($id);
    }

}
