<?php

namespace App\Pages;

use App\Entity\Employee;
use App\Entity\TimeItem;
use App\Helper as H;
use App\System;
use App\Application as App;
use Zippy\Html\DataList\DataView;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\Button;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Panel;
use Zippy\Html\Label;
use Zippy\Html\Form\Date;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\DataList\ArrayDataSource;

class TimeSheet extends \App\Pages\Base
{
    private $_time_id = 0;
    public $_list    = array();
    public $_stat    = array();

    public function __construct() {
        parent::__construct();
        $user = System::getUser();
        if ($user->user_id == 0) {
            App::Redirect("\\App\\Pages\\Userlogin");
        }


    }




    //vue

    public function init($arg, $post=null) {
        $user = \App\System::getUser() ;
        $common = System::getOptions("common");

        $ret = array();
        $ret['empid']  =  $user->employee_id;
        $ret['isadmin']  =  $user->rolename=="admins";
        $ret['types']  =  \App\Util::tokv(TimeItem::getTypeTime());
        $ret['emps']  =  \App\Util::tokv(\App\Entity\Employee::findArray("emp_name", "disabled<>1", "emp_name")) ;
        if($ret['isadmin'] == false) {
            $ret['emps']  =  \App\Util::tokv(\App\Entity\Employee::findArray("emp_name", "disabled<>1 and employee_id=".$user->employee_id, "emp_name")) ;
        }
        $dt = new \App\DateTime();

        $ret['from'] = date("Y-m-d", $dt->startOfMonth()->getTimestamp());
        $ret["to"] = date("Y-m-d", $dt->endOfMonth()->getTimestamp());
        $ret["today"] = date("Y-m-d", time());
        $ret["start"] = $common['ts_start'] ;
        $ret["end"] = $common['ts_end'] ;
        $ret["break"] = $common['ts_break'] ;

        return $this->jsonOK($ret) ;
     

    }

    public function loaddata($arg, $post) {

        $post = json_decode($post) ;

        $conn = \ZDB\DB::getConnect();

        $emp_id = $post->empid;
        $t_start = $conn->DBDate(strtotime($post->from));
        $t_end = $conn->DBDate(strtotime($post->to));

        $ret = array()  ;
        $ret['stat'] = array();

        $tn = TimeItem::getTypeTime();
        $sql="select t_type,sum(tm) as tm, count(distinct dd) as dd   from (select t_type, date(t_start) as dd, (UNIX_TIMESTAMP(t_end)-UNIX_TIMESTAMP(t_start)  - t_break*60)   as  tm from timesheet where  emp_id = {$emp_id} and  date(t_start)>=date({$t_start}) and  date( t_start)<= date( {$t_end} ) ) t  group by t_type order by t_type ";

        $stat = $conn->Execute($sql);
        foreach ($stat as $row) {

            $color="";
            if ($row['t_type'] == TimeItem::TIME_WORK) {
                $color=  'badge badge-primary' ;
            }
            if ($row['t_type'] == TimeItem::TINE_BT) {

                $color=  'badge badge-info'   ;
            }
            if ($row['t_type'] == TimeItem::TINE_HL) {
                $color=  'badge badge-success'  ;
            }
            if ($row['t_type'] == TimeItem::TINE_ILL) {
                $color=  'badge badge-warning'  ;
            }
            if ($row['t_type'] == TimeItem::TINE_OVER) {
                $color=  'badge badge-danger'  ;
            }
            if ($row['t_type'] == TimeItem::TINE_WN) {
                $color=  'badge badge-danger'  ;
            }
            if ($row['t_type'] == TimeItem::TINE_OTHER) {
                $color=  'badge badge-light'   ;
            }

            $ret['stat'][] = array(
            "color"=> $color,
            "days"=> $row['dd'],
            "cnt"=> number_format($row['tm'] / 3600, 2, '.', '') ,
            "name"=>$tn[$row['t_type']]
            );
        }

        $ret['times'] = array();
        $ret['events'] = array();

        $w = "emp_id = {$emp_id} and  date(t_start)>=date({$t_start}) and  date( t_start)<= date( {$t_end} )  ";

        if ($this->_tvars["usebranch"] && $this->branch_id > 0) {
            $w .= " and branch_id=" . $this->branch_id;
        }



        foreach (TimeItem::findYield($w, 't_start') as $tm) {

            $color="";
            $colorcal="";
            if ($tm->t_type == TimeItem::TIME_WORK) {
                $color=  'badge badge-primary' ;
                $colorcal=  "#007bff" ;
            }
            if ($tm->t_type == TimeItem::TINE_BT) {

                $color=  'badge badge-info'   ;
                $colorcal=  "#17a2b8" ;
            }
            if ($tm->t_type == TimeItem::TINE_HL) {
                $color=  'badge badge-success'  ;
                $colorcal=  "#28a745" ;
            }
            if ($tm->t_type == TimeItem::TINE_ILL) {
                $color=  'badge badge-warning'  ;
                $colorcal=  "#ffc107" ;
            }
            if ($tm->t_type == TimeItem::TINE_OVER) {
                $color=  'badge badge-danger'  ;
                $colorcal=  "#dc3545" ;
            }
            if ($tm->t_type == TimeItem::TINE_WN) {
                $color=  'badge badge-danger'  ;
                $colorcal=  "#dc3545" ;
            }
            if ($tm->t_type == TimeItem::TINE_OTHER) {
                $color=  'badge badge-light'   ;
                $colorcal=  "#bbb" ;
            }
            $diff = $tm->t_end - $tm->t_start - ($tm->t_break * 60);
            $diff = number_format($diff / 3600, 2, '.', '');

            $ret['times'][] = array(
             "date"=> date('Y-m-d', $tm->t_start),
             "from"=> date('H:i', $tm->t_start),
             "to"=> date('H:i', $tm->t_end),
             "desc"=> $tm->description,
             "type"=> $tm->t_type,
             "break"=> $tm->t_break,
             "dur"=> $diff,
             "color"=> $color,
             "branch"=> $tm->branch_id > 0 ? $tm->branch_name : '',
             "name"=>$tn[$tm->t_type]  ,
             "time_id"=>$tm->time_id
            );

            $ret['events'][] = array(
               "start"=> date('c', $tm->t_start),
               "end"=> date('c', $tm->t_start),

               "backgroundColor"=>$colorcal ,
               "title"=>$tm->description ,
               "id"=>$tm->time_id
              );


        }


        return $this->jsonOK($ret) ;
     

    }
    public function save($args, $post) {

        $post = json_decode($post) ;

        $time = new TimeItem();
        if($args[0] > 0) {
            $time = TimeItem::load($args[0]);
        }

        $time->description = $post->desc;
        $time->emp_id = intval( $post->empid);
        if ($time->emp_id == 0) {

            return json_encode("Не обрано співробітника", JSON_UNESCAPED_UNICODE);
        }
        $time->t_type = $post->type;

        if ($time->t_type == 0) {
            return $this->jsonError("Не обрано тип";) ;
     
           
        }


        $time->t_break = $post->break;
        $from = $post->date . ' ' . $post->from;
        $to = $post->date . ' ' . $post->to;

        $time->t_start = strtotime($from);
        $time->t_end = strtotime($to);

        $v = $time->isValid();
        if (strlen($v) > 0) {
            return  $v;

        }

        if ($this->_tvars["usebranch"]) {
            if ($this->branch_id == 0) {

                return "Виберіть філію";

            }
            $time->branch_id = $this->branch_id;
        }


        $time->save();

       return $this->jsonOK() ;
     
     

    }

    public function del($args) {


        TimeItem::delete($args[0]);
        return $this->jsonOK() ;
     

    }
}
