<?php

namespace App\Modules\Issue\Pages;

use \App\Application as App;
use \App\System;
use \ZCL\DB\DB as DB;
use \Zippy\Html\Label;
use \Zippy\Html\Panel;
use \Zippy\Html\Form\Form;
use \Zippy\Html\Form\Date;
use \Zippy\Html\Link\ClickLink;
use \App\Modules\Issue\Entity\Issue;
use \Zippy\Html\DataList\ArrayDataSource;
use \Zippy\Html\DataList\DataView;

/**
 * страница статистики
 */
class Stat extends \App\Pages\Base {

    public $_clist = array();
    public $_dlist = array();
    public $_elist = array();
    
    public function __construct() {

        parent::__construct();

        $allow = (strpos(System::getUser()->modules, 'issue') !== false || System::getUser()->userlogin == 'admin');
        if (!$allow) {
            System::setErrorMsg('Нет права  доступа  к   модулю ');
            App::RedirectHome();
            return false;
        }
        $this->add(new Form('filter'))->onSubmit($this, 'filterOnSubmit');
        $this->filter->add(new Date('from', strtotime("-1 month",time())));
        $this->filter->add(new Date('to', time()  ));
        
 
        $this->add(new DataView('elist', new ArrayDataSource($this,'_elist'), $this, 'elistOnRow'));
         $this->add(new DataView('clist', new ArrayDataSource($this,'_clist'), $this, 'clistOnRow'));

        $this->add(new Panel("detailpan"))->setVisible(false);
        $this->detailpan->add(new DataView('dlist', new ArrayDataSource($this,'_dlist'), $this, 'dlistOnRow'));
        $this->detailpan->add(new Label("name"));
        $this->detailpan->add(new ClickLink("back",$this,"onBack"));
        
        $this->filterOnSubmit(null);
        
    }
  
    public function filterOnSubmit($sender) {
 
       
        $from = $this->filter->from->getDate();
        $to = $this->filter->to->getDate(true);
        
        
        //пользователи
         $this->_elist=array();
         $conn = DB::getConnect();
         $sql = "select sum(duration) as total ,user_id,username from  issue_history_view
                where  issue_id  in (select issue_id from issue_issuelist where  status=1 and    date(lastupdate) >= " . $conn->DBDate($from) . " and  date(lastupdate) <= " . $conn->DBDate($to) .")  
                               
   
                group by user_id, username
                having total >0
                order  by  username ";
                
         $res = $conn->Execute($sql);
         foreach($res as $v){
             $item = new \App\DataItem();
 
           
             $item->user_id = $v['user_id'];
             $item->username = $v['username'];
             $item->total  = $v['total'];
             $this->_elist[]= $item;
         }        
        
        
         $this->elist->Reload();
         $this->elist->setVisible(count($this->_elist)>0);
         
        //контрагенты
         $this->_clist=array();
         $conn = DB::getConnect();
         $sql = "select sum(price) as amount ,issue_issuelist.customer_id,customer_name from  issue_issuelist
                join  customers on  issue_issuelist.customer_id = customers.customer_id 
                where    issue_issuelist.status=1 and    date(lastupdate) >= " . $conn->DBDate($from) . " and  date(lastupdate) <= " . $conn->DBDate($to) ."   
                               
   
                group by issue_issuelist.customer_id, customer_name
                having amount >0
                order  by  customer_name ";
                
         $res = $conn->Execute($sql);
         foreach($res as $v){
             $item = new \App\DataItem();
 
           
             $item->customer_id = $v['customer_id'];
             $item->customer_name = $v['customer_name'];
             $item->amount  = $v['amount'];
             $this->_clist[]= $item;
         }        
        
        
          $this->clist->Reload();
          $this->clist->setVisible(count($this->_clist)>0);
         
         
        
    }
     
    public function elistOnRow($row) {
        $user = $row->getDataItem();
        $row->add(new Label('ename', $user->username));
        $row->add(new Label('etime', $user->total));
        $row->add(new ClickLink('eshow'))->onClick($this, 'showOnClick');
         
        
    }
  
 
    public function clistOnRow($row) {
        $cust = $row->getDataItem();
        $row->add(new Label('cname', $cust->customer_name));
        $row->add(new Label('csum', $cust->amount));
        $row->add(new ClickLink('cshow'))->onClick($this, 'showOnClick');
         
        
    }
     
     public function showOnClick($sender) {
         $this->filter->setVisible(false);   
         $this->elist->setVisible(false);   
         $this->clist->setVisible(false);   
         $this->detailpan->setVisible(true);
     
         $from = $this->filter->from->getDate();
         $to = $this->filter->to->getDate(true);
         
         $this->_dlist=array();
         $conn = DB::getConnect();
          
         $item = $sender->getOwner()->getDataItem()  ;
         
         if(substr($sender->id,0,5) =="eshow"){
             $this->detailpan->name->setText($item->username);
             $sql="select issue_id,issue_name, price,lastupdate,
             (select sum(duration) from issue_history where issue_history.user_id ={$item->user_id}  and issue_history.issue_id= issue_issuelist.issue_id  ) as    total
             from issue_issuelist where  status=1 and    date(lastupdate) >= " . $conn->DBDate($from) . " and  date(lastupdate) <= " . $conn->DBDate($to) ." 
             having total >0  order  by issue_id";
         }
         
         if(substr($sender->id,0,5)  =="cshow"){
             $this->detailpan->name->setText($item->customer_name);
               $sql="select issue_id,issue_name,  lastupdate, price as  total  from issue_issuelist  
               where customer_id ={$item->customer_id} and price>0 and   status=1 and    date(lastupdate) >= " . $conn->DBDate($from) . " and  date(lastupdate) <= " . $conn->DBDate($to) . " order  by issue_id" ;
             
         }
         
         $res = $conn->Execute($sql);
         foreach($res as $issue){
             $item = new \App\DataItem();
 
           
        
             $item->issue_id = $issue['issue_id'];
             $item->issue_name = $issue['issue_name'];
             $item->total  = $issue['total'];
             $item->lastupdate  =  $issue['lastupdate'];
             $this->_dlist[]= $item;
         }         
         
         $this->detailpan->dlist->Reload();   
     }
     
    public function dlistOnRow($row) {
        $issue = $row->getDataItem();
        $row->add(new Label('did', "#".$issue->issue_id));
        $row->add(new Label('dname', $issue->issue_name));
        
        $row->add(new Label('dtotal', $issue->total));
        $row->add(new Label('ddate', date('Y.m.d',strtotime($issue->lastupdate))));
      
        
    }     
     
     public function onBack($sender) {
         $this->filter->setVisible(true);   
         $this->elist->setVisible(true);   
         $this->clist->setVisible(true);   
         $this->detailpan->setVisible(false);
         $this->filterOnSubmit(null); 
     }
     
     
}
