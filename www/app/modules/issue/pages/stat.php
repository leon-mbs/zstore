<?php

namespace App\Modules\Issue\Pages;

use \App\Application as App;
use \App\System;
use \Zippy\Html\Label;
use \Zippy\Html\Panel;
use \Zippy\Html\Form\Form;
use \Zippy\Html\Form\Date;
use \Zippy\Html\Link\ClickLink;
use \App\Modules\Issue\Entity\Issue;
use \Zippy\Html\DataList\ArrayDataSource;

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

        
    }
  
    public function filterOnSubmit($sender) {
 
       
        $from = $this->filter->from->getDate();
        $to = $this->filter->to->getDate(true);
        
         $this->_elist=array();
         $conn = DB::getConnect();
         $sql = "select sum(duration) as total ,username from  issue_history_view
                where  issue_id  in (select issue_id from issue_issuelist where  status=1 and    date(lastupdate) >= " . $conn->DBDate($from) . " and  date(lastupdate) <= " . $conn->DBDate($to) .")  
)                               
   
                group by user_id, username
                having total >0
                order  by  username,";
                
         $res = $conn->Execute($sql);
         foreach($res as $v){
             $item = new \App\DataItem();
 
             $item->changed  = strtotime($v['changed']);
             $item->username = $v['username'];
             $item->notes  = $v['notes'];
             $this->_elist[]= $item;
         }        
        
        
         $this->_elist->Reload();
         $this->_elist->setVisible(count($this->_elist)>0);
         
         
        
    }
    
}
