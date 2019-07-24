<?php

namespace App\Modules\Issue\Pages ;

use \Zippy\Html\DataList\DataView;
use \Zippy\Html\Panel;
use \Zippy\Html\Label;
use \Zippy\Html\Image;
use \Zippy\Html\Form\Form;
use \Zippy\Html\Form\Button;
use \Zippy\Html\Form\TextInput;
use \Zippy\Html\Form\TextArea;
use \Zippy\Html\Form\AutocompleteTextInput;
use \Zippy\Html\Form\CheckBox;
use \Zippy\Html\Form\DropDownChoice;
use \Zippy\Html\Form\SubmitButton;
use \Zippy\Html\Link\RedirectLink;
use \Zippy\Html\Link\ClickLink;
use \Zippy\Html\Link\BookmarkableLink;
use \Zippy\Html\Link\SubmitLink;
use \ZCL\DB\EntityDataSource;
use \Zippy\Html\DataList\Paginator;
use \App\Application as App;
use \App\System;
use \App\Modules\Issue\Helper;
use \App\Filter;
use \ZCL\BT\Tags;
use \App\Modules\Issue\Entity\Issue;
use \App\Entity\Customer;
use \App\Entity\User;
 

/**
 * Главная страница
 */
class IssueList extends \App\Pages\Base 
{
 

    public function __construct($id=0) {
        parent::__construct();

        $user = System::getUser();
        
        $allow = (strpos($user->modules, 'issue') !== false || $user->userlogin == 'admin');
        if(!$allow){
            System::setErrorMsg('Нет права  доступа  к   модулю ');
            App::RedirectHome();
            return  ;
        }
 
        $this->add(new  Panel("listpan"));
         
        $this->listpan->add(new Form('filter'))->onSubmit($this, 'reload');
        $this->listpan->filter->add(new AutocompleteTextInput('searchcust'))->onText($this, 'OnAutoCustomer');
        $this->listpan->filter->add(new TextInput('searchnumber', $filter->searchnumber));
        
        //пользователи ассоциированные с сотрудниками
        $this->listpan->filter->add(new DropDownChoice('searchassignedto', User::findArray('username', 'employee_id > 0', 'username'), $user->user_id));
        
        $stlist = Issue::getStatusList();
        $stlist[-1]='Открытые';
        $stlist[100]='Все';
        $this->listpan->filter->add(new DropDownChoice('searchstatus',  $stlist  , -1));
         
        
        $this->listpan->add(new Form('sort'))->onSubmit($this, 'reload');
        $this->listpan->sort->add(new DropDownChoice('sorttype', array(0=>'Последние измененные',1=>'Дата создания',2=>'Приоритет'),0));
        
        
        $list = $this->listpan->add(new DataView('list', new IssueDS($this), $this, 'listOnRow'));
        $list->setPageSize(25);
        $this->listpan->add(new Paginator('pag', $list));
        
        $this->add(new  Panel("editpan"))->setVisible(false);
        $this->add(new  Panel("statuspan"))->setVisible(false) ;
        $this->add(new  Panel("msgpan"))->setVisible(false)  ;

 
       // $this->reload(null);
    }

    public function reload($sender) {

    }
   
 
    //вывод строки  списка   

    public function listOnRow($row) {
        $doc = $row->getDataItem();
    }
 
  

   
    public function OnAutoCustomer($sender) {
        $text = Customer::qstr('%' . $sender->getText() . '%');
        return Customer::findArray("customer_name", "status=0 and customer_name like " . $text);
    }

 
}
class IssueDS implements \Zippy\Interfaces\DataSource {
    private $page;

    public function __construct($page) {
        $this->page = $page;
    }

    private function getWhere() {
        

        $conn = \ZDB\DB::getConnect();
         
        $where = "" ;

     

        return $where;
    }

    public function getItemCount() {
        return Issue::findCnt($this->getWhere());
    }

    public function getItems($start, $count, $sortfield = null, $asc = null) {
       
        $sort="lastupdate desc" ;
        $s = $page->listpan->sort->sorttype->getValue();
        if($s==1)   $sort="issue_id desc" ;
        if($s==2)   $sort="priority desc" ;
        
        return Issue::find($this->getWhere(), $sort, $count, $start);

        
    }

    public function getItem($id) {
        
    }

}