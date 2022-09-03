<?php

namespace App\Modules\Issue\Pages;

use App\Application as App;
use App\Entity\User;
use App\Modules\Issue\Entity\Project;
use App\System;
use ZCL\DB\DB as DB;
use Zippy\Html\DataList\ArrayDataSource;
use Zippy\Html\DataList\DataView;
use Zippy\Html\Form\Date;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Label;
use App\Modules\Issue\Entity\TimeLine;
use App\Modules\Issue\Entity\Issue;

use App\Helper as H;

/**
 * страница  учета  времени  по  задачам
 */
class TimeSheet extends \App\Pages\Base
{

    public $_list = array();

    public function __construct() {

        parent::__construct();

        $user = System::getUser();

        $allow = (strpos($user->modules, 'issue') !== false || $user->rolename == 'admins');
        if (!$allow) {
            $this->setError('noaccesstopage');
            App::RedirectError();
            return;
        }
        /*
        $this->add(new Form('filter'))->onSubmit($this, 'filterOnSubmit');
        $this->filter->add(new Date('from', strtotime("-1 month", time())));
        $this->filter->add(new Date('to', time()));
        $projects = Project::findArray('project_name', 'status <> ' . Project::STATUS_CLOSED, 'project_name');
        $this->filter->add(new DropDownChoice('searchproject', $projects, 0));

        $users =  User::findArray("username","disabled<>1 and  user_id     in(select  user_id  from issue_projectacc  )","username");
     
    

        $this->filter->add(new DropDownChoice('searchemp', $users, 0))->setVisible($user->rolename == 'admins');

        $this->add(new DataView('list', new ArrayDataSource($this, '_list'), $this, 'listOnRow'));
        $this->add(new Label('total'))->setVisible(false);;
        
        
        
        $this->filterOnSubmit($this->filter);
        */
    }

    public function filterOnSubmit($sender) {


        $searchproject = $this->filter->searchproject->getValue();
        $searchemp = $this->filter->searchemp->getValue();
      
        $user = System::getUser();
        
        if($user->rolename != 'admins'){
            $searchemp = $user->user_id;
        }
        
        
        $from = $this->filter->from->getDate();
        $to = $this->filter->to->getDate(true);
        $where = "";
        if ($searchproject > 0) {
            $where .= " and project_id = " . $searchproject;
        }
        if ($searchemp > 0) {
            $where .= " and user_id = " . $searchemp;
        }

        $total = 0;
        $this->_list = array();
        $conn = DB::getConnect();
        $sql = "select sum(duration) as amount ,username,project_name from  issue_time_view
                where  date(createdon) >= " . $conn->DBDate($from) . " and  date(createdon) <= " . $conn->DBDate($to) . "   
                {$where}
                group by   username,project_name  
                having sum(duration) >0
                order  by  username,project_name ";

        $res = $conn->Execute($sql);
        foreach ($res as $v) {
            $item = new \App\DataItem();

            $item->project_name = $v['project_name'];
            $item->username = $v['username'];
            $item->amount = doubleval($v['amount']);
            $this->_list[] = $item;
            $total += $item->amount;
        }


        $this->list->Reload();

        $this->total->setVisible($total > 0);
        $this->total->setText($total);
    }

    public function listOnRow($row) {
        $item = $row->getDataItem();
        $row->add(new Label('project_name', $item->project_name));
        $row->add(new Label('username', $item->username));
        $row->add(new Label('amount', $item->amount));
    }

    
    public function init($arg,$post=null){
        $user = \App\System::getUser() ;
  
        $ret = array();  
        $ret['user_id']  =  $user->user_id;
        $ret['isadmin']  =  $user->rolename=="admins";
        if($ret['isadmin'] ==true)  $ret['user_id'] =0;
        
        $projects = Project::findArray('project_name', 'status <> ' . Project::STATUS_CLOSED, 'project_name');
        if($ret['isadmin'] != true) {
            $projects =  Project::findArray("project_name","  project_id in (select project_id from issue_projectacc where  user_id={$ret['user_id']} )     and status <> " . Project::STATUS_CLOSED,"project_name");
            
        }

        $users =  User::findArray("username","disabled<>1 and  user_id     in(select  user_id  from issue_projectacc  )","username");
        if($ret['isadmin'] != true) {
            $users =  User::findArray("username"," user_id  =".$ret['user_id'] ,"username");
            
        }
    
        $ret['projects']  =  \App\Util::tokv($projects);
        $ret['users']  =  \App\Util::tokv($users ) ;

        $dt = new \App\DateTime();

        $ret["from"] = date("Y-m-d",strtotime("-1 month", time()));
        $ret['to'] = date("Y-m-d",  time() );
        $ret["today"] = date("Y-m-d",time() );
        
         
        return json_encode($ret, JSON_UNESCAPED_UNICODE);     
       
    }      
   public function loaddata($arg,$post){
       
        $post = json_decode($post) ;
        
        $conn = \ZDB\DB::getConnect();
         
        $user_id = $post->user_id;
      
        $user = System::getUser();
        $project_id = $post->project_id;
        $t_start = $conn->DBDate(strtotime($post->from));
        $t_end = $conn->DBDate(strtotime($post->to));
        $where = " and project_id in (select project_id from  issue_projectlist where  status <>12) ";
            
         
        if($user->rolename != 'admins'){
            $user_id = $user->user_id;
            $where = " and project_id in (select project_id from issue_projectacc where  user_id={$user_id} ) and project_id in (select project_id from  issue_projectlist where  status <>12) ";
              
        }
        if ($project_id > 0) {
            $where .= " and project_id = " . $project_id;
        }
        if ($user_id > 0) {
            $where .= " and user_id = " . $user_id;
        }

      
        $conn = DB::getConnect();
        $sql = "select sum(duration) as amount ,username,project_name from  issue_time_view
                where  date(createdon) >= date({$t_start}) and  date(createdon) <= date({$t_end})    
                {$where}
                group by   username,project_name  
                having sum(duration) >0
                order  by  username,project_name ";

        $res = $conn->Execute($sql);
         
      
        $ret = array()  ;
        $ret['stat'] = array();
        $ret['times'] = array();
       
       

         $total = 0;
     
         foreach ($res as $row) {
 
            
          $ret['stat'][] = array(
             "amount"=> $row['amount'],
             "username"=> $row['username'],
             "project_name"=> $row['project_name']
          );
          $total = $total + $row['amount'];
                                            
        }
        $ret['total']  = $total;
        if($user_id > 0) {
           $times = TimeLine::find("project_id in (select project_id from  issue_projectlist where  status <>12) and user_id={$user_id} and date(createdon) >= date({$t_start}) and  date(createdon) <= date({$t_end})    "    );
        }  else {
           $times = TimeLine::find(" project_id in (select project_id from  issue_projectlist where  status <>12)  and  date(createdon) >= date({$t_start}) and  date(createdon) <= date({$t_end})    "    );
        }
      
       
       foreach ($times as $tm) {
 
            
          $ret['times'][] = array(
             "date"=>  \App\Helper::fd($tm->createdon),
             "time"=> $tm->duration,
             "issue"=> '#' . $tm->issue_id . ' ' . $tm->issue_name,
             "issue_id"=>   $tm->issue_id  ,
             "project"=> $tm->project_name,
             "project_id"=> $tm->project_id,
             "user_id"=> $tm->user_id,
             "id"=> $tm->id,
             "user"=> $tm->username,
             "notes"=> $tm->notes 

          );
          
                                            
        }    
      
        return json_encode($ret, JSON_UNESCAPED_UNICODE);     
               
    }
    public function del($args) {


        TimeLine::delete($args[0]);


    }   
    public function loadprojects($args) {

        $projects = Project::findArray("project_name","project_id in (select project_id from issue_projectacc where  user_id={$args[0]}) and  project_id in (select project_id from  issue_projectlist where  status <>12) ","project_name");
        

        $ret =  \App\Util::tokv($projects);
        return json_encode($ret, JSON_UNESCAPED_UNICODE);     
  
    }   
    public function loadissues($args) {

        $issues = Issue::findArray("issue_name","project_id=".$args[0],"issue_name");
        

        $ret =  \App\Util::tokv($issues);
        return json_encode($ret, JSON_UNESCAPED_UNICODE);     
  

    }   
    
    public function save($args,$post) {
        
        $post = json_decode($post) ;
       
        $time = new TimeLine();
        if($args[0] > 0) {
           $time = TimeLine::load($args[0] ); 
        }
        
        $time->issue_id = $post->issue_id;
        $time->notes = $post->notes;
        $time->createdon = strtotime($post->date);
        $time->user_id = $post->user_id;
        if ($time->user_id == 0) {

            return json_encode(H::l("noempselected"), JSON_UNESCAPED_UNICODE);     
        }
   
     
        $time->duration =doubleval( $post->time);
       
        if ($time->duration == 0) {

            return H::l("nosettime");     
        }
  

        $time->save();

        return "";     
       
    }
      
}
