<?php

namespace App\Pages\Report;

use App\Helper as H;
use Zippy\Html\Form\Date;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\AutocompleteTextInput;
use Zippy\Html\Form\Form;
use Zippy\Html\Label;
use Zippy\Html\Link\RedirectLink;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Panel;

/**
 * OLAP анализ
 */
class OLAP extends \App\Pages\Base
{
    private $br       = '';

    public function __construct() {
        parent::__construct();

        if (false == \App\ACL::checkShowReport('OLAP')) {
            return;
        }
        $conn = \ZDB\DB::getConnect();


        //        $dt = new \App\DateTime() ;
        //        $to = $dt->startOfMonth()->getTimestamp()  - 1 ;
        //        $from = $dt->subMonth(1)->getTimestamp()  ;



        $this->add(new Form('startform'))->onSubmit($this, 'OnNext') ;
        $this->startform->add(new Date('stfrom', time() - (7 * 24 * 3600)));
        $this->startform->add(new Date('stto', time()));
        $this->startform->add(new DropDownChoice('sttype', array(), 0))->onChange($this, 'OnType');
        $this->startform->add(new DropDownChoice('sthor', array(), 0));
        $this->startform->add(new DropDownChoice('stver', array(), 0));


        $this->add(new Panel('reppan'))->setVisible(false);

        $this->reppan->add(new ClickLink('back'))->onClick($this, 'OnBack');

        $this->reppan->add(new Form('filter'))->onSubmit($this, 'OnFilter');
        $this->reppan->filter->add(new DropDownChoice('slcat_name', array(), 0));
        $this->reppan->filter->add(new DropDownChoice('slitemname', array(), 0));
        $this->reppan->filter->add(new DropDownChoice('slcustomer_name', array(), 0));
        $this->reppan->filter->add(new DropDownChoice('slservice_name', array(), 0));
        $this->reppan->filter->add(new DropDownChoice('sldocument_date', array(), 0));

        $this->reppan->filter->add(new DropDownChoice('slusername', array(), 0));
        $this->reppan->filter->add(new DropDownChoice('slmf_name', array(), 0));
        $this->reppan->filter->add(new DropDownChoice('slstorename', array(), 0));
        $this->reppan->filter->add(new DropDownChoice('slbranch_name', array(), 0));

        $this->reppan->add(new Panel('detail'))->setVisible(false);

        $this->reppan->detail->add(new Label('preview'));


    }

    public function OnType($sender) {

        $type = $this->startform->sttype->getValue();

        $dim=[];

        $options=\App\System::getOptions('common')  ;

        $dim['document_date'] = "Дата";
        $dim['customer_name'] = "Контрагент";
  
        $dim['username'] = "Співробітник";

        if($type  < 4) {
            $dim['itemname'] = "ТМЦ";
            $dim['cat_name'] = "Категорія";
            $dim['storename'] = "Склад";


        }

        if($type==4 || $type==7 ) {
            $dim['service_name'] = "Послуга";

        }
      

        if($type==5) {
            $dim['mf_name'] = "Рахунок";

        }
        if($type  == 6) {
            $dim['itemname'] = "ТМЦ";
            $dim['cat_name'] = "Категорія";
            $dim['storename'] = "Склад";
            $dim['service_name'] = "Послуга";


        }


        if ($options['usebranch'] == 1) {
            $id = \App\System::getBranch();
            if ($id == 0) {
                $dim['branch_name'] = "Філія";
            }
        }

        $this->startform->stver->setOptionList($dim);
        $this->startform->sthor->setOptionList($dim);

    }

    public function OnBack($sender) {

        $this->reppan->setVisible(false);
        $this->startform->setVisible(true);
        $this->reppan->detail->setVisible(false);
    }

    public function OnNext($sender) {
        $type = $this->startform->sttype->getValue();
        $hor = $this->startform->sthor->getValue();
        $ver = $this->startform->stver->getValue();
        if($type==0) {
            $this->setError('Не вибраний тип') ;
            return;
        }
        if($hor==$ver) {
            $this->setError('Виміри однакові') ;
            return;
        }

        $options=\App\System::getOptions('common')  ;


        $this->reppan->setVisible(true);
        $this->startform->setVisible(false);
        $this->reppan->detail->setVisible(false);

        $this->reppan->filter->clean();

        $cols = $this->getSlices();

        $this->_tvars['itemname']  = in_array('itemname', $cols);
        $this->_tvars['document_date']  = in_array('document_date', $cols);
        $this->_tvars['cat_name']  = in_array('cat_name', $cols);
        $this->_tvars['service_name']  = in_array('service_name', $cols);
        $this->_tvars['customer_name']  = in_array('customer_name', $cols);
        $this->_tvars['storename']  = in_array('storename', $cols);
  
        $this->_tvars['mf_name']  = in_array('mf_name', $cols);
        $this->_tvars['username']  = in_array('username', $cols);
        $this->_tvars['branch_name']  = in_array('branch_name', $cols);


        $colsdata=[];
        foreach($cols as $d) {
            $colsdata[$d]=array();
        }
        $conn = \ZDB\DB::getConnect();

        //данные  для  срезов
        $sql = $this->getBaseSql($type)  ;

        $m = \App\Util::getMonth()  ;

        foreach($cols as $d) {
        
            $res = $conn->Execute("select distinct {$d} from ({$sql} ) t order  by {$d} ");

            foreach($res as $row) {

                if(!in_array($row[$d], $colsdata[$d])) {

                    $colsdata[$d][$row[$d]] = $row[$d] ;
                    if($d=="document_date") {

                        $a = explode('-', $row[$d]);
                        $colsdata[$d][$row[$d]] = $m[intval($a[1]) ].', '.$a[0] ;

                    }

                }

            }

        }

        foreach($cols as $d) {

            if(is_array($colsdata[$d])) {
                if(count($colsdata[$d])==0) {
                    continue;
                }

                $list = $this->getComponent('sl'.$d) ;
                if($list != null) {
                    $list->setOptionList($colsdata[$d]);
                    $list->setValue('0');

                }

            }


        }



    }

    public function OnFilter($sender) {

        $html = $this->generateReport();
        $this->reppan->detail->preview->setText($html, true);
        \App\Session::getSession()->printform = "<html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\"></head><body>" . $html . "</body></html>";


        $this->reppan->detail->setVisible(true);
    }

    private function generateReport() {
        $conn = \ZDB\DB::getConnect();

        $type = $this->startform->sttype->getValue();
        $hor = $this->startform->sthor->getValue();
        $ver = $this->startform->stver->getValue();


        $sql = $this->getBaseSql($type)  ;

        $data="";
        if($type==1 ||  $type==4) {
            $data = "sum(outprice) "   ;
        }
        if($type==2) {
            $data = "sum(outprice-partion) "  ;
        }
        if($type==3) {
            $data = "sum(partion) "  ;
        }
        if($type==5) {
            $data = "sum(amount) "  ;
        }
        if($type==7) {
            $data = "sum(outprice-cost) "  ;
        }
        if($type==6) {
            $data = "count(document_id) "  ;
        }

        $where  = "where  1=1" ;
        $slices = $this->getSlices()  ;
        foreach($slices as $sl) {
            $c = $this->getComponent('sl'.$sl) ;
            if($c != null) {

                $v = $c->getValue();

                if($v != '0') {
                    $where = $where . " and {$sl}=". $conn->qstr($v);
                }

            }
        }


        $dver = $conn->GetCol("select distinct coalesce( {$ver},'Н/Д') as {$ver} from ({$sql} ) t {$where} order  by {$ver} ");
        $dhor = $conn->GetCol("select distinct coalesce( {$hor},'Н/Д') as {$hor} from ({$sql} ) t {$where} order  by {$hor} ");



        $sql = "select {$ver},{$hor}, {$data} as amount from ({$sql} ) t {$where} group by  {$ver},{$hor}  ";


        $detail = [];
        $detailv = [];
        $h = [];
        $v = [];



        $res = $conn->Execute($sql);

        foreach($res as $row) {

            $detail[$row[$ver]][$row[$hor]] =doubleval($row['amount'])==0 ? '' : H::fa($row['amount']) ;
        }
        $m = \App\Util::getMonth()  ;

        foreach($dhor as $n) {

            if($hor=="document_date") {
                $a = explode('-', $n);
                $n =   $m[ intval($a[1]) ] .', '.$a[0]  ;
            }

            $h[]=array('name'=>$n)  ;
        }
        foreach($dver as $n) {
            $vname = $n;
            if($ver=="document_date") {
                $a = explode('-', $n);
                $vname =   $m[intval($a[1])].', '.$a[0]  ;
            }

            $da=[]  ;

            foreach($dhor as $_h) {

                $da[]=array('val'=> $detail[$n][$_h] ?? '')  ;
            }


            $v[]=array('name'=>$vname,'row'=>$da)  ;



        }


        $header = array('from'    => H::fd($this->startform->stfrom->getDate()),
                        'to'      => H::fd($this->startform->stto->getDate()),
                        "cols"    => count($h)+1,
                        "hor"    => $h,
                        "ver"    => $v

        );
        $report = new \App\Report('report/olap.tpl');

        $html = $report->generate($header);

        return $html;
    }

    private function getBaseSql($type) {
        $options=\App\System::getOptions('common')  ;

        $conn = \ZDB\DB::getConnect();

        $concat=" concat(year(dv.document_date),'-',( case when month(dv.document_date)< 10 then concat('0',month(dv.document_date) )  else concat('',month(dv.document_date) ) end  ) )  ";

 

        $where = "  dv.document_date >= " . $conn->DBDate($this->startform->stfrom->getDate()) . " 
                    AND dv.document_date <= " . $conn->DBDate($this->startform->stto->getDate()) ;


        $sql ='';

        if ($options['usebranch'] == 1) {

            $brids = \App\ACL::getBranchIDsConstraint();
            if (strlen($brids) > 0) {
                $where = $where . " and dv.branch_id in ({$brids}) ";
            }



            $id = \App\System::getBranch();
            if ($id > 0) {
                $where = $where . " and dv.branch_id=".$id;
            }
        }


        if($type < 4) {   //товар

            if($type==3) {
                $where .=  " and   ev.tag in(-2, -8 ) ";
            } else {
                $where .=  " and   ev.tag in(-1, -4 ) ";
            }

            $sql = "SELECT iv.itemname,
                ssv.storename,
                iv.cat_name,
                COALESCE(c.customer_name,'Фіз. особа') AS customer_name, 
                {$concat} as document_date ,
                COALESCE(b.branch_name,'Н/Д') AS branch_name,
               
                COALESCE(uv.username ,'Н/Д') AS username,
                COALESCE(ev.partion,0) AS partion, 
      
                COALESCE(ev.outprice,0) AS outprice   
                FROM entrylist_view ev   
                JOIN documents dv ON ev.document_id = dv.document_id
                JOIN items_view iv ON ev.item_id = iv.item_id
                JOIN store_stock_view ssv ON ev.stock_id = ssv.stock_id
                LEFT JOIN customers c ON dv.customer_id = c.customer_id
                LEFT JOIN users_view uv  ON dv.user_id = uv.user_id 
               
                LEFT JOIN branches b ON dv.branch_id = b.branch_id
                where  {$where}
                
                ";
        }

        if($type == 4 || $type == 7) {   //услуга



            $sql = "SELECT  ss.service_name,
                COALESCE(c.customer_name,'Фіз. особа') AS customer_name, 
                {$concat} as document_date ,
                COALESCE(b.branch_name,'Н/Д') AS branch_name,
                
                COALESCE(uv.username ,'Н/Д') AS username,
                COALESCE(ev.outprice,0) AS outprice,   
                COALESCE(ev.cost,0) AS cost    
                FROM entrylist_view ev   
                JOIN documents dv ON ev.document_id = dv.document_id
                JOIN services ss ON ev.service_id = ss.service_id

                LEFT JOIN customers c ON dv.customer_id = c.customer_id
                LEFT JOIN users_view uv  ON dv.user_id = uv.user_id 
               
                LEFT JOIN branches b ON dv.branch_id = b.branch_id
                where  {$where}
                
                ";
        }
        if($type == 5) {   //платежи

            $concat=" concat(year(pv.paydate),'-',( case when month(pv.paydate)< 10 then concat('0',month(pv.paydate) )  else concat('',month(pv.paydate) ) end  ) )  ";


            $where = " pv.amount <> 0  and  pv.paydate >= " . $conn->DBDate($this->startform->stfrom->getDate()) . " 
                        AND pv.paydate <= " . $conn->DBDate($this->startform->stto->getDate()) ;




            $sql = "SELECT  pv.mf_name, 
                COALESCE(c.customer_name,'Н/Д') AS customer_name, 
                {$concat} as document_date ,
                COALESCE(b.branch_name,'Н/Д') AS branch_name,
              
                COALESCE(uv.username ,'Н/Д') AS username,
                COALESCE(pv.amount,0) AS amount 
                FROM paylist_view pv   
                JOIN documents dv ON pv.document_id = dv.document_id
                LEFT JOIN customers c ON dv.customer_id = c.customer_id
                LEFT JOIN users_view uv  ON dv.user_id = uv.user_id 
             
                LEFT JOIN branches b ON dv.branch_id = b.branch_id
                where dv.meta_name in('GoodsIssue', 'POSCheck','OrderFood','ServiceAct') and  {$where}
                
                ";
        }

        if($type == 6) {   //документи



            $sql = "SELECT  
                ss.service_name,  
                iv.itemname,
                ssv.storename,
                iv.cat_name,            
                COALESCE(c.customer_name,'Фіз. особа') AS customer_name, 
                {$concat} as document_date ,
                COALESCE(b.branch_name,'Н/Д') AS branch_name,
               
                COALESCE(uv.username ,'Н/Д') AS username,
                dv.document_id    
                FROM entrylist_view ev   
                JOIN documents dv ON ev.document_id = dv.document_id
                LEFT JOIN services ss ON ev.service_id = ss.service_id
                LEFT JOIN items_view iv ON ev.item_id = iv.item_id
                LEFT JOIN store_stock_view ssv ON ev.stock_id = ssv.stock_id
                LEFT JOIN customers c ON dv.customer_id = c.customer_id
                LEFT JOIN users_view uv  ON dv.user_id = uv.user_id 
           
                LEFT JOIN branches b ON dv.branch_id = b.branch_id
                where  {$where}
                
                ";
        }

        return $sql;
    }

    //срезы
    private function getSlices() {
        $type = $this->startform->sttype->getValue();
        $hor = $this->startform->sthor->getValue();
        $ver = $this->startform->stver->getValue();
        $options=\App\System::getOptions('common')  ;
        $cols=[];
        $_cols=[];


        $_cols[] = 'document_date';
        $_cols[] = 'customer_name';
   
        $_cols[] = 'username';

        if($type < 4) {
            $_cols[] = 'itemname';
            $_cols[] = 'cat_name';
            $_cols[] = 'storename';

        }

        if($type== 4) {
            $_cols[] = 'service_name';
        }
        if($type== 7) {
            $_cols[] = 'service_name';
        }

        if($type==5) {
            $_cols[] = 'mf_name';
        }
        if($type ==6) {
            $_cols[] = 'itemname';
            $_cols[] = 'cat_name';
            $_cols[] = 'storename';
            $_cols[] = 'service_name';

        }

        if ($options['usebranch'] == 1) {
            $id = \App\System::getBranch();
            if ($id == 0) {
                $_cols[] = "branch_name";
            }
        }

        foreach($_cols as $_d) {
            if($_d != $hor &&  $_d != $ver) {
                $cols[]=$_d;
            }
        }

        return  $cols;
    }
}
