<?php

namespace App\Pages\Doc;

use App\Application as App;
use App\Entity\Doc\Document;
use App\Entity\Employee;
use App\Entity\MoneyFund;
use App\Entity\SalType;
use App\Entity\EmpAcc;
use App\Entity\TimeItem;
use App\Helper as H;
use App\System;
use App\Entity\IOState;

use Zippy\Html\Label;

/**
 * Страница   начисление  зарплаты
 */
class CalcSalary extends \App\Pages\Base
{
    private $_doc;
    private $_list;


    /**
    * @param mixed $docid     редактирование
    */
    public function __construct($docid = 0) {
        parent::__construct();


        $this->_list = Employee::find('disabled<>1', 'emp_name');
        $this->_stlist = SalType::find("disabled<>1", "salcode");

        $opt = System::getOptions("salary");


        if ($docid > 0) {    //загружаем   содержимое  документа на страницу
            $this->_doc = Document::load($docid)->cast();

            $this->_list = $this->_doc->unpackDetails('detaildata');
        } else {
            $this->_doc = Document::create('CalcSalary');
            $this->_doc->document_number = $this->_doc->nextNumber();
            $this->_doc->document_date = time();
            $this->_doc->headerdata['daysmon'] =22;
            $this->_doc->headerdata['year'] = round(date('Y'));
            $this->_doc->headerdata['month'] = round(date('m'));
        }


  

        $calcvar ='';
        //переменные  из настроек
        $calcvar .= "var daysmon = fa(this.doc.daysmon) \n"  ;
        $calcvar .= "var invalid = emp.invalid   \n" ;
        $calcvar .= "var salarytype = fa(emp.salarytype)   \n" ;
        $calcvar .= "var sellvalue = fa(emp.sellvalue)   \n" ;
        $calcvar .= "var salarym = fa(emp.salarym)   \n" ;
        $calcvar .= "var salaryh = fa(emp.salaryh)   \n" ;
        $calcvar .= "var hours = fa(emp.hours)   \n" ;
        $calcvar .= "var tasksum = fa(emp.tasksum)   \n" ;
        $calcvar .= "var days = fa(emp.days)   \n" ;
        $calcvar .= "var hours_week = fa(emp.hours_week)   \n" ;
        $calcvar .= "var hours_over = fa(emp.hours_over)   \n" ;
        $calcvar .= "var days_week = fa(emp.days_week)   \n" ;
        $calcvar .= "var days_vac = fa(emp.days_vac)   \n" ;
        $calcvar .= "var days_sick = fa(emp.days_sick)   \n" ;
        $calcvar .= "var days_bt = fa(emp.days_bt)   \n" ;
        $calcvar .= "var advance = fa(emp.advance)   \n" ;
        $calcvar .= "var fine = fa(emp.fine)   \n" ;
        $calcvar .= "var bonus = fa(emp.bonus)   \n" ;
     
     
       
        // из  строки сотрудника  в переменные
        foreach($this->_stlist as $st) {
         
            $calcvar .= "var v{$st->salcode} =  parseVal(emp['_c{$st->salcode}'] ) ;\n ";

        }

        $calcinit = $calcvar;
        $calcinit .= "\n\n";
        $calcinit .= $opt['calcbase'];  //формулы начислений
        $calcinit .= "\n\n";
        $calcinit .= "emp._baseval=fa(v".$opt['codebaseincom'].")";
        $calcinit .= "\n\n";
        
  
        $calc = $calcvar;
        $calc .= "\n\n";
        $calc .= ("v".$opt['codebaseincom']."=emp._baseval" ) ;
        $calc .= "\n\n";
        $calc .= $opt['calc'];  //формулы удержаний
        $calc .= "\n\n";
  

   
        // из  переменных в строку  сотрудника

        foreach($this->_stlist as $st) {

   
            $calc .= "emp['_c{$st->salcode}']  = fa( v{$st->salcode}) ;\n ";
            $calcinit .= "emp['_c{$st->salcode}']  = fa( v{$st->salcode}) ;\n ";
      
        }

        $this->_tvars['calcs'] = $calc;
        $this->_tvars['calcsinit'] = $calcinit;
    


    }


    public function save($args, $post) {
        $post = json_decode($post) ;
        if (false == \App\ACL::checkEditDoc($this->_doc, false, false)) {

            return json_encode(['error'=>'Нема прав редагування документу' ], JSON_UNESCAPED_UNICODE);
        }

        $this->_doc->document_number = $post->doc->document_number;
        $this->_doc->document_date = strtotime($post->doc->document_date);
        $this->_doc->notes = $post->doc->notes;
        $this->_doc->headerdata['daysmon'] = $post->doc->daysmon;
        $this->_doc->headerdata['year'] = $post->doc->year;
        $this->_doc->headerdata['month'] = $post->doc->month;
        $this->_doc->headerdata['acccode'] = $post->doc->acccode;
        $this->_doc->headerdata['department'] = $post->doc->department;
        $mlist = \App\Util::getMonth();
        $this->_doc->headerdata['monthname'] = $mlist[$post->doc->month] ;



        if (false == $this->_doc->checkUniqueNumber()) {
            return json_encode(['error'=>'Не унікальний номер документу. Створено новий.','newnumber'=>$this->_doc->nextNumber()], JSON_UNESCAPED_UNICODE);
        }


        $opt = System::getOptions("salary");


        $elist = $post->emps;

        $this->_doc->amount = 0 ;
        $this->_list = array();

        $emps = Employee::find('disabled<>1', 'emp_name');


        foreach($elist as $e) {
            $emp = $emps[$e->id];
            $this->_doc->amount +=  $e->{'_c'.$opt['coderesult']}    ;

            foreach ($this->_stlist as $st) {
                $c   = "_c".$st->salcode ;
                $emp->{$c} = $e->{$c};
              
            }          
             
            $emp->_baseval = $e->_baseval;
            $emp->_tasksum = $e->tasksum;
          
            $this->_list[]= $emp;
        }

        $this->_doc->packDetails('detaildata', $this->_list);

        $isEdited = $this->_doc->document_id > 0;

        $conn = \ZDB\DB::getConnect();
        $conn->BeginTrans();
        try {

            $this->_doc->save();
            if ($post->op == 'execdoc') {
                if (!$isEdited) {
                    $this->_doc->updateStatus(Document::STATE_NEW);
                }
                $this->_doc->updateStatus(Document::STATE_EXECUTED);
            } else {
                $this->_doc->updateStatus($isEdited ? Document::STATE_EDITED : Document::STATE_NEW);
            }
            $conn->CommitTrans();

        } catch(\Throwable $ee) {
            global $logger;
            $conn->RollbackTrans();
            if ($isEdited == false) {
                $this->_doc->document_id = 0;
            }
            $logger->error('Line '. $ee->getLine().' '.$ee->getFile().'. '.$ee->getMessage()  );

            return json_encode(['error'=>$ee->getMessage()], JSON_UNESCAPED_UNICODE);


        }

        return json_encode([], JSON_UNESCAPED_UNICODE);
    }




    public function loaddata($args, $post) {
        $opt = System::getOptions("salary");

        $post = json_decode($post) ;
        $conn = \ZDB\DB::getConnect();

        
        $rowsadv=[];
        $rowsb=[];
        $rowsf=[];
        
        if ($this->_doc->document_id == 0) {
         
      
            $rowsadv = EmpAcc::getAmountByType(EmpAcc::ADVANCE,  $post->year,  $post->month );
            
            $rowsb = EmpAcc::getAmountByType(EmpAcc::BONUS,  $post->year,  $post->month );
            $rowsf = EmpAcc::getAmountByType(EmpAcc::FINE,  $post->year,  $post->month );
 


        }
        
        
        
        $from =''.$post->year .'-'. $post->month .'-01' ;
        $from =  strtotime($from);
        $to =   strtotime('+1 month', $from) - 1 ;

        $from = $conn->DBDate($from) ;
        $to = $conn->DBDate($to) ;


        $br = "";
        $brids = \App\ACL::getBranchIDsConstraint();
        if (strlen($brids) > 0) {
            $br = " and d.branch_id in ({$brids}) ";
        }

       //сдельная
       
       $etasklist = []; 
       $be="";
       if (strlen($brids) > 0) {
          $be = " and document_id in(select document_id from documents where branch_id in ({$brids}) )   ";
       }  
       
       
       $sql = "select coalesce( abs ( sum(amount)),0) as am,emp_id from  empacc_view  where  optype = 104 {$be} AND DATE(createdon) >= {$from}   AND DATE(createdon) <= " .$to . "  group by  emp_id   ";
      
       foreach($conn->Execute($sql) as $r){
          $etasklist[$r['emp_id']]  =  $r['am'];
       }
      
        //по  продажам
        $sqlitem = "
                  select   sum(0-e.quantity*e.outprice) as summa 
                      from entrylist_view  e

                      join items i on e.item_id = i.item_id
                     join documents_view d on d.document_id = e.document_id
                       where e.item_id >0  and (e.tag = 0 or e.tag = -1  or e.tag = -4) 
                      and d.meta_name in ('GoodsIssue','ServiceAct' ,'POSCheck','ReturnIssue','TTN','OrderCust','OrderFood')           
                       {$br}  AND DATE(e.document_date) >= {$from}   AND DATE(e.document_date) <= " .$to  ;


        $sqlservice ="        select  sum(0-e.outprice*e.quantity) as summa    
                      from entrylist_view  e

                      join services s on e.service_id = s.service_id
                     join documents_view d on d.document_id = e.document_id
                       where e.service_id >0  and e.quantity <>0       
                      and d.meta_name in (  'ServiceAct' ,'POSCheck' )
                       {$br}  AND DATE(e.document_date) >={$from} 
                      AND DATE(e.document_date) <= " . $to ;



        $ret=[];
        $ret['newdoc'] = $this->_doc->document_id == 0 ;
        $ret['emps'] = [] ;
  
        $ret['opt'] = [];
        $ret['opt']['coderesult']  =  $opt['coderesult'];
        $ret['opt']['codebaseincom']  =  $opt['codebaseincom'];



        foreach ($this->_list as $emp) {
            if(intval($emp->employee_id)==0) {
                continue;
            }
            $e = array('emp_name'=>$emp->emp_name,'id'=>$emp->employee_id);
            $e['sellvalue'] = 0;
            $u = \App\Entity\User::getByLogin($emp->login) ;
            if($u != null) {
                $sql= $sqlitem ." and d.user_id=" .$u->user_id;
                $e['sellvalue'] = intval($conn->GetOne($sql)) ;
                $sql= $sqlservice ." and d.user_id=" .$u->user_id;
                $e['sellvalue'] = $e['sellvalue'] + intval($conn->GetOne($sql)) ;
            }
            if($emp->sellvalue > 0) {
               $e['sellvalue'] = doubleval($emp->sellvalue)  ;
            }

            
          
             $e['hours'] =0;
             $e['hours_week'] =0;
             $e['hours_over'] =0;
             $e['days'] =0;
             $e['days_week'] =0;
             $e['days_vac'] =0;
             $e['days_sick'] =0;
             $e['days_bt'] =0;
             $e['advance'] = H::fa($rowsadv[$emp->employee_id] ?? 0 ) ;
             $e['fine'] =H::fa($rowsb[$emp->employee_id] ?? 0) ;
             $e['bonus'] =H::fa($rowsf[$emp->employee_id] ?? 0) ;
            
            $sql="select sum(tm) as tm, count(distinct dd) as dd,t_type   from (select  date(t_start) as dd, (UNIX_TIMESTAMP(t_end)-UNIX_TIMESTAMP(t_start)  - t_break*60)   as  tm,t_type from timesheet where    emp_id = {$emp->employee_id} and  date(t_start)>=date({$from}) and  date( t_start)<= date( {$to} ) ) t  group by t_type ";
          

            $rs = $conn->Execute($sql);
            foreach($rs as $row) {
               if($row['t_type']==TimeItem::TIME_WORK ) {
                  $e['hours']  += intval($row['tm']/3600);
                  $e['days']   += doubleval($row['dd']); 
               }
               if($row['t_type']==TimeItem::TINE_WN  ) {
                  $e['hours_week']  += intval($row['tm']/3600);
                  $e['days_week']   += doubleval($row['dd']); 
               }
               if($row['t_type']==TimeItem::TINE_OVER  ) {
                  $e['hours_over']  += intval($row['tm']/3600);
       
               }
               if($row['t_type']==TimeItem::TINE_HL  ) {
                  $e['days_vac']   += doubleval($row['dd']); 
               }
               if($row['t_type']==TimeItem::TINE_ILL  ) {
                  $e['days_sick']   += doubleval($row['dd']); 
               }
               if($row['t_type']==TimeItem::TINE_BT  ) {
                  $e['days_bt']   += doubleval($row['dd']); 
               }
            }
            
            $e['tasksum'] = 0  ;
            if(isset($etasklist[$e['id']])){
               $e['tasksum']  =   $etasklist[$e['id']] ?? 0; 

            }        

            $e['invalid'] = $emp->invalid == 1  ;
            $e['salarytype'] = $emp->ztype ;
            $e['salarym'] = $emp->zmon  ;
            $e['salaryh'] = $emp->zhour  ;
            $e['department'] = $emp->department  ;
            
         

            foreach($this->_stlist as $st) {
                $e['_c'.$st->salcode]  =  $emp->{'_c'.$st->salcode};
            }
            $e['_baseval'] = $emp->_baseval  ??0 ;
    
            if(strlen($post->department ??'') >0)  {
               if($e['department'] != $post->department )   {
                   continue;
               }
            }
    
            $ret['emps'][] = $e;
        }

        
 
        
        return json_encode($ret, JSON_UNESCAPED_UNICODE);
    }


    public function loaddoc($args, $post) {

        if (false == \App\ACL::checkShowDoc($this->_doc, false, false)) {

            return json_encode(['error'=>'Нема прав на  доступ до документу' ], JSON_UNESCAPED_UNICODE);
        }


        $opt = System::getOptions("salary");
        $ret=[];

        $ret['years'] =  \App\Util::tokv(\App\Util::getYears());
        $ret['monthes'] = \App\Util::tokv(\App\Util::getMonth()) ;


        foreach($this->_stlist as $st) {
            $ret['stlist'][]  = array("salname"=>$st->salshortname,"salcode"=>'_c'.$st->salcode);
        }
    
        $pd = Employee::getDP() ;
        $ret['deps'] = $pd['d'] ;
       
        
        $ret['doc'] = [] ;
        $ret['doc']['document_date']   =  date('Y-m-d', $this->_doc->document_date) ;
        $ret['doc']['document_number']   =   $this->_doc->document_number ;
        $ret['doc']['notes']   =   $this->_doc->notes ;
        $ret['doc']['document_id']   =   $this->_doc->document_id ;
        $ret['doc']['daysmon']   =   $this->_doc->headerdata['daysmon'] ;
        $ret['doc']['year']   =   $this->_doc->headerdata['year'] ;
        $ret['doc']['month']   =   $this->_doc->headerdata['month'] ;
        $ret['doc']['acccode']   =   $this->_doc->headerdata['acccode'] ?? 91;
        $ret['doc']['department']   =   $this->_doc->headerdata['department'] ??'';


        return json_encode($ret, JSON_UNESCAPED_UNICODE);
    }

}
