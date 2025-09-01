<?php

namespace App\Pages;

use App\Helper as H;
use App\System;
use Zippy\Html\DataList\ArrayDataSource;
use Zippy\Html\DataList\DataView;
use Zippy\Html\DataList\Paginator;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use App\Entity\Stock;
use App\Entity\Item;
use App\DataItem;
use App\Util;

/**
 * Главная страница
 */
class Main extends Base
{
    private $_docstatelist;

    public function __construct() {
        parent::__construct();


        
        $user = System::getUser();

        $this->_docstatelist = \App\Entity\Doc\Document::getStateList();

        $this->_tvars['dashboard'] =  (($user->dashboard ==1 ) ||  ($user->rolename=='admins') );
     
        $br = '';
        $cstr = '';
        $brpay = '';
        $brf = '';
        $cust = '';

        $brids = \App\ACL::getBranchIDsConstraint();
        if (strlen($brids) > 0) {
            $br = " and d.branch_id in ({$brids}) ";
        }


        if (strlen($brids) > 0) {
            $brf = " and branch_id in ({$brids}) ";
        }

        $cstr = \App\ACL::getStoreBranchConstraint();
        if (strlen($cstr) > 0) {
            $cstr = "    store_id in ({$cstr})  and   ";
        }

        $conn =   \ZDB\DB::getConnect();

        //просроченые товары
     
            $data = array();
            $stock = Stock::find(" {$cstr}   qty > 0 and sdate is not null  and sdate <  ADDDATE( now(), INTERVAL 7 day)  ");

            foreach ($stock as $st) {
                $data[$st->stock_id] = $st;
            }

            if (count($data) == 0) {
                $this->_tvars['wsdate'] = false;
            }

            $this->add(new ClickLink('sdcsv', $this, 'onSDcsv'));
            $sdlist = $this->add(new DataView('sdlist', new ArrayDataSource($data), $this, 'sdlistOnRow'));
            $sdlist->setPageSize(10);
            $this->add(new Paginator("sdpag", $sdlist));
            $sdlist->Reload();
        
        //минимальное количество
       
            $data = array();
            $sql = "select coalesce(t.qty,0) as qty, i.minqty,i.itemname,i.item_code,i.item_id   from 
           items  i 
          left join (select  item_id, coalesce(sum( qty),0) as qty   from  store_stock  where    {$cstr}  1=1        group by  item_id    ) t
               on t.item_id = i.item_id
           
            where i.disabled  <> 1 and  coalesce(t.qty,0) < i.minqty and i.minqty>0 order  by  i.itemname ";
            $rs = $conn->Execute($sql);

            foreach ($rs as $row) {

                $data[] = new DataItem($row);
            }
            if (count($data) == 0) {
                $this->_tvars['wminqty'] = false;
            }
            $this->add(new ClickLink('mqcsv', $this, 'onMQcsv'));
            $mqlist = $this->add(new DataView('mqlist', new ArrayDataSource($data), $this, 'mqlistOnRow'));
            $mqlist->setPageSize(10);
            $this->add(new Paginator("mqpag", $mqlist));
            $mqlist->Reload();
       

        //недавние  документы
     
            $data = array();

            $sql = "SELECT u.username,d.document_number,d.document_id,d.amount,d.meta_desc,d.state ,d.lastupdate 
                      from documents_view d    
                      join users_view u  on d.user_id= u.user_id where  d.lastupdate >  " . $conn->DBDate(strtotime("-1 week", time())) . "  {$br} ORDER  BY  d.lastupdate desc,document_id desc";


            $rc = $conn->Execute($sql);

            foreach ($rc as $row) {
                $data[] = new \App\DataItem($row);
            }
            if (count($data) == 0) {
                $this->_tvars['wrdoc'] = false;
            }
            $this->add(new ClickLink('rdcsv', $this, 'onRDcsv'));

            $doclist = $this->add(new DataView('rdoclist', new ArrayDataSource($data), $this, 'rdoclistOnRow'));
            $doclist->setPageSize(10);
            $this->add(new Paginator("wrpag", $doclist));

            $doclist->Reload();
      



        //мои  документы
   
            $data = array();

            $sql = "select    d.document_id,d.meta_desc,d.document_number,d.document_date,d.amount from   documents_view d  where 1=1   {$br}  and  d.user_id={$user->user_id}   order  by  document_id desc  ";

            $rc = $conn->SelectLimit($sql, 25, 0);

            foreach ($rc as $row) {
                $data[] = new \App\DataItem($row);
            }
            if (count($data) == 0) {
                $this->_tvars['wmdoc'] = false;
            }
            $this->add(new ClickLink('mdcsv', $this, 'onMDcsv'));

            $doclist = $this->add(new DataView('mdoclist', new ArrayDataSource($data), $this, 'mdoclistOnRow'));
            $doclist->setPageSize(10);
            $this->add(new Paginator("wmpag", $doclist));

            $doclist->Reload();
        

        //структура  доходов  и расходов
        $dt = new \App\DateTime();

        $to = $dt->startOfMonth()->getTimestamp();
        $dt = $dt->subMonth(12);
        $from = $dt->startOfMonth()->getTimestamp();

        $names = \App\Entity\IOState::getTypeList();
        if (strlen($brids) > 0) {

            $brpay = " and  document_id in(select  document_id from  documents where branch_id in ({$brids}) )";
        }

        $sql = " 
         SELECT   iotype,coalesce(sum(amount),0) as am   FROM iostate_view 
             WHERE   
              iotype >= 50 and  iotype < 80    {$brpay}
              AND document_date  >= " . $conn->DBDate($from) . "
              AND  document_date  <= " . $conn->DBDate($to) . "
             GROUP BY  iotype order  by  iotype  
                         
        ";

        $rs = $conn->Execute($sql);


        $ps = [];
        $ps[]=["name","amount"] ;

        foreach ($rs as $row) {

            $ps[]=[$names[$row['iotype']]??'',abs(round($row['am']??0))] ;

        }
        $this->_tvars['ps'] = json_encode($ps);

        //сравнение  доходов  и расходов
        $mon = array();
        $in = array();
        $out = array();

        $pc=[];

        $mlist = Util::genPastMonths(12);

        foreach ($mlist as $m) {
            $sql = " 
             SELECT   coalesce(sum(amount),0) as am   FROM iostate_view 
                 WHERE   
                  iotype >= 50    {$brpay}
                  AND document_date  >= " . $conn->DBDate($m['start']) . "
                  AND  document_date  <= " . $conn->DBDate($m['end']) . "
                  
                             
            ";
            $out = abs(round($conn->GetOne($sql)));
            $sql = " 
             SELECT   coalesce(sum(amount),0) as am   FROM iostate_view 
                 WHERE   
                  iotype <  50    {$brpay}
                  AND document_date  >= " . $conn->DBDate($m['start']) . "
                  AND  document_date  <= " . $conn->DBDate($m['end']) . "
                  
                             
            ";
            $in = abs(round($conn->GetOne($sql)));


            $pc[]=[$m['name'],$in,$out]  ;

        }
        $this->_tvars['pc'] = json_encode($pc);

        //реализация
        $ts = [];
        //  $ts[] = ['Month','Goods','Service'];

        $mlist = Util::genPastMonths(12);

        foreach ($mlist as $m) {


            $sql = "
           select  coalesce(sum(0-(e.outprice*e.quantity))) as summa 
              from entrylist_view  e

              join items_view i on e.item_id = i.item_id
              join documents_view d on d.document_id = e.document_id
              where e.item_id > 0  and e.quantity <> 0   
              and d.meta_name in ('GoodsIssue', 'POSCheck','ReturnIssue','TTN','OrderFood')
              {$br}  
              AND DATE(e.document_date) >= " . $conn->DBDate($m['start']) . "
              AND DATE(e.document_date) <= " . $conn->DBDate($m['end']) . "
            
               
        ";


            $tov = abs(round($conn->GetOne($sql)));

            $sql = "
           select  coalesce( sum(0-(e.outprice*e.quantity)) ) as summa     
              from entrylist_view  e

              join services s on e.service_id = s.service_id
             join documents_view d on d.document_id = e.document_id
               where e.service_id >0  and e.quantity <>0      {$cust}  
              and d.meta_name in (  'ServiceAct' ,'POSCheck' )
               {$br}   AND DATE(e.document_date) >= " . $conn->DBDate($m['start']) . "
              AND DATE(e.document_date) <= " . $conn->DBDate($m['end']) . "
                    
                  ";

            $ser = abs(round($conn->GetOne($sql)));

            $ts[] = [$m['name'],$ser,$tov];



        }
        $this->_tvars['ts'] = json_encode($ts);

        //инфоблоки
        $sql = " select coalesce(count(*),0) as cnt  from  documents_view d where  meta_name in ('Order','ServiceAct')  
         {$br}   and d.state in (7,8,21,16)      ";

        $this->_tvars['biorders'] = $conn->GetOne($sql);

//        $sql = " select coalesce(sum(partion*qty),0) as cnt  from  store_stock_view  where {$cstr} qty >0  and item_id in (select item_id from items where disabled<>1 )                     ";
 //       $sql = " SELECT  SUM(( select coalesce(sum(st1.qty*st1.partion),0 ) from store_stock_view st1 where {$cstr}  st1.item_id= items.item_id )) AS ss  FROM items
   //              where     ( select coalesce(sum(st1.qty),0 ) from store_stock_view st1 where {$cstr}  st1.item_id= items.item_id ) >0   ";

        $sql = " SELECT coalesce( SUM( qty * partion),0)  from store_stock where {$cstr} item_id in (select item_id from items where  disabled<>1) ";
        
                 
        $this->_tvars['biitemscnt'] = H::fa($conn->GetOne($sql));
        
        $cust_acc_view = \App\Entity\CustAcc::get_acc_view()  ;
        
        //к оплате
        $sql = "SELECT COALESCE( SUM(   a.s_active - a.s_passive    ) ,0) AS d   FROM ({$cust_acc_view}) a where  a.s_active > a.s_passive   ";
        $sum = doubleval($conn->GetOne($sql));
        $sql = "SELECT COALESCE( SUM(   a.b_active - a.b_passive    ) ,0) AS d   FROM ({$cust_acc_view}) a where  a.b_active > a.b_passive   ";
        $sum += doubleval($conn->GetOne($sql));
        $this->_tvars['bicredit'] = H::fa($sum);

        //ожидается  оплата
        $sql = "SELECT COALESCE( SUM( a.s_passive -  a.s_active      ) ,0) AS d   FROM ({$cust_acc_view}) a where  a.s_active < a.s_passive   ";
        $sum = doubleval($conn->GetOne($sql));
        $sql = "SELECT COALESCE( SUM(  a.b_passive -  a.b_active      ) ,0) AS d   FROM ({$cust_acc_view}) a where  a.b_active < a.b_passive   ";
        $sum += doubleval($conn->GetOne($sql));
        $this->_tvars['bidebet'] = H::fa($sum);


        $sql = "select coalesce(sum(amount),0)  from paylist_view where  paytype <=1000 and mf_id  in (select mf_id  from mfund where detail not like '%<beznal>1</beznal>%' {$brf})";

        $this->_tvars['binal'] = H::fa($conn->GetOne($sql));
        $sql = "select coalesce(sum(amount),0)  from paylist_view where  paytype <=1000 and mf_id  in (select mf_id  from mfund where detail like '%<beznal>1</beznal>%' {$brf})";
        $this->_tvars['bibeznal'] = H::fa($conn->GetOne($sql));



    }

    public function sdlistOnRow($row) {
        $stock = $row->getDataItem();

        $row->add(new Label('wsd_storename', $stock->storename));
        $row->add(new Label('wsd_itemname', $stock->itemname));
        $row->add(new Label('wsd_snumber', $stock->snumber));
        $row->add(new Label('wsd_edate', H::fd($stock->sdate)));
        $row->add(new Label('wsd_qty', H::fqty($stock->qty)));
        $row->wsd_edate->setAttribute('class', 'badge badge-danger');
        if ($stock->sdate > time()) {
            $row->wsd_edate->setAttribute('class', 'badge badge-warning');
        }
    }

    public function mqlistOnRow($row) {
        $item = $row->getDataItem();

        $row->add(new Label('wmq_itemname', $item->itemname));
        $row->add(new Label('wmq_item_code', $item->item_code));
        
        
        $d = \App\Entity\Doc\Document::getFirst("meta_name='GoodsReceipt' and document_id in (select document_id from entrylist_view where  item_id = {$item->item_id})  ","document_id desc") ;
        
        $row->add(new Label('wmq_cust', $d->customer_name ?? '-'));
        $row->add(new Label('wmq_qty', H::fqty($item->qty)));
        $row->add(new Label('wmq_minqty', H::fqty($item->minqty)));
    }

    public function rdoclistOnRow($row) {
        $item = $row->getDataItem();

        $row->add(new Label('wrd_date', \App\Helper::fd(strtotime($item->lastupdate))));
        $row->add(new Label('wrd_type', $item->meta_desc));
        $row->add(new Label('wrd_state', $this->_docstatelist[$item->state]));
        $row->add(new Label('wrd_user', $item->username));
        $row->add(new Label('wrd_amount', H::fa($item->amount)));

        $row->add(new \Zippy\Html\Link\RedirectLink("wrd_number", "\\App\\Pages\\Register\\DocList", $item->document_id))->setValue($item->document_number);
    }

    public function mdoclistOnRow($row) {
        $item = $row->getDataItem();

        $row->add(new Label('wmd_date', \App\Helper::fd(strtotime($item->document_date))));
        $row->add(new Label('wmd_type', $item->meta_desc));
        $row->add(new Label('wmd_amount', H::fa($item->amount)));
        $row->add(new \Zippy\Html\Link\RedirectLink("wmd_number", "\\App\\Pages\\Register\\DocList", $item->document_id))->setValue($item->document_number);
    }

    public function onRDcsv($sender) {
        $br = '';
        $brids = \App\ACL::getBranchIDsConstraint();
        if (strlen($brids) > 0) {
            $br = " and d.branch_id in ({$brids}) ";
        }

        $data = array();
        $conn = $conn = \ZDB\DB::getConnect();

            $sql = "SELECT u.username,d.document_number,d.document_id,d.amount,d.meta_desc,d.state ,d.lastupdate 
                      from documents_view d    
                      join users_view u  on d.user_id= u.user_id where  d.lastupdate >  " . $conn->DBDate(strtotime("-1 week", time())) . "  {$br} ORDER  BY  d.lastupdate desc,document_id desc";


        $rc = $conn->Execute($sql);

        $header = array();
        $data = array();

        $i = 0;
        foreach ($rc as $row) {
            $i++;
            $data['A' . $i] = $row['document_number'];
            $data['B' . $i] = $row['meta_desc'];
            $data['C' . $i] = $this->_docstatelist[$row['state']];
            $data['D' . $i] = array('value' => H::fa($row['amount']), 'format' => 'number');
            $data['E' . $i] = $row['username'];
            $data['F' . $i] = \App\Helper::fd(strtotime($row['lastupdate']));
        }

        H::exportExcel($data, $header, 'recentlydoc.xlsx');
    }

    public function onMDcsv($sender) {
        $br = '';
        $brids = \App\ACL::getBranchIDsConstraint();
        if (strlen($brids) > 0) {
            $br = " and d.branch_id in ({$brids}) ";
        }

        $data = array();
        $conn = $conn = \ZDB\DB::getConnect();
        $user = System::getUser();

        $sql = "select    d.document_id,d.meta_desc,d.document_number,d.document_date,d.amount from   documents_view d  where 1=1   {$br}  and  d.user_id={$user->user_id}   order  by  document_id desc  ";
        $rc = $conn->SelectLimit($sql, 25, 0);

        $header = array();
        $data = array();

        $i = 0;
        foreach ($rc as $row) {
            $i++;
            $data['A' . $i] = $row['document_number'];
            $data['B' . $i] = \App\Helper::fd(strtotime($row['document_date']));
            $data['C' . $i] = $row['meta_desc'];
            $data['D' . $i] = array('value' => H::fa($row['amount']), 'format' => 'number');
        }

        H::exportExcel($data, $header, 'mydoc.xlsx');
    }

    public function onSDcsv($sender) {
        //  $brids = \App\ACL::getBranchIDsConstraint();
        //  if (strlen($brids) > 0) {
        //      $br = " and d.branch_id in ({$brids}) ";
        //  }
        $cstr = \App\ACL::getStoreBranchConstraint();
        if (strlen($cstr) > 0) {
            $cstr = "    store_id in ({$cstr})  and   ";
        }

        $conn = $conn = \ZDB\DB::getConnect();
        $stock = Stock::find(" {$cstr}  qty > 0 and sdate is not null  and sdate <  ADDDATE( now(), INTERVAL 7 day)  ");
        $header = array();
        $data = array();

        $i = 0;
        foreach ($stock as $st) {
            $i++;
            $data['A' . $i] = $st->storename;
            $data['B' . $i] = $st->cat_name;
            $data['C' . $i] = $st->itemname;
            $data['D' . $i] = $st->item_code;
            $data['E' . $i] = $st->bar_code;
            $data['F' . $i] = $st->snumber;
            $data['G' . $i] = H::fd($st->sdate);
            $data['H' . $i] = array('value' => H::fqty($st->qty), 'format' => 'number');
        }
        H::exportExcel($data, $header, 'termitem.xlsx');
    }

    public function onMQcsv($sender) {
        $br ="";
        $brids = \App\ACL::getBranchIDsConstraint();
        if (strlen($brids) > 0) {
            $br = " and branch_id in ({$brids}) ";
        }
        $cstr = \App\ACL::getStoreBranchConstraint();
        if (strlen($cstr) > 0) {
            $cstr = " where store_id in ({$cstr})     ";
        }
        $conn = $conn = \ZDB\DB::getConnect();

       $sql = "select t.qty, i.minqty,i.itemname,i.item_code,i.item_id,i.bar_code,i.cat_name   from 
            (select  item_id, coalesce(sum( qty),0) as qty   from  store_stock    {$cstr}       group by  item_id    ) t
            join items_view  i  on t.item_id = i.item_id
           
            where i.disabled  <> 1 and  t.qty < i.minqty and i.minqty>0 order  by  i.itemname ";

        $rc = $conn->Execute($sql);
        $header = array();
        $data = array();

        $i = 0;
        foreach ($rc as $row) {
            $i++;
            
        $d = \App\Entity\Doc\Document::getFirst("  meta_name='GoodsReceipt'  {$br}  and document_id in (select document_id from entrylist_view where  item_id = {$row['item_id']}) ","document_id desc") ;
            

            $data['A' . $i] = $row['cat_name'];
            $data['B' . $i] = $row['itemname'];
            $data['C' . $i] = $row['item_code'];
            $data['D' . $i] = $row['bar_code'];
            $data['E' . $i] = $d->customer_name ?? '';
            $data['F' . $i] = array('value' => H::fqty($row['qty']), 'format' => 'number');
            $data['G' . $i] = array('value' => H::fqty($row['minqty']), 'format' => 'number');
        }
        H::exportExcel($data, $header, 'minqty.xlsx');
    }

    public function test($args, $post) {

        // $this->testa->setText("Hello");
    }

}
