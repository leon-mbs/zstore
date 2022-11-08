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

        $this->_tvars['curversion'] = System::CURR_VERSION;
        $this->_tvars['curversionbd'] = System::getOptions('version',false);

        $user = System::getUser();

        $this->_docstatelist = \App\Entity\Doc\Document::getStateList();

        $this->_tvars['wminqty'] = strpos(System::getUser()->widgets, 'wminqty') !== false;
        $this->_tvars['wsdate'] = strpos(System::getUser()->widgets, 'wsdate') !== false;
        $this->_tvars['wrdoc'] = strpos(System::getUser()->widgets, 'wrdoc') !== false;
        $this->_tvars['wmdoc'] = strpos(System::getUser()->widgets, 'wmdoc') !== false;
        $this->_tvars['winfo'] = strpos(System::getUser()->widgets, 'winfo') !== false;
        $this->_tvars['wgraph'] = strpos(System::getUser()->widgets, 'wgraph') !== false;
        if ($user->rolename == 'admins') {
            $this->_tvars['wminqty'] = true;

            $this->_tvars['wsdate'] = true;
            $this->_tvars['wrdoc'] = true;
            $this->_tvars['wmdoc'] = true;
            $this->_tvars['winfo'] = true;
            $this->_tvars['wgraph'] = true;
        }
        if ($this->_tvars["usesnumber"] == false) {
            $this->_tvars['wsdate'] = false;
        }
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
        
        $cstr = \App\Acl::getStoreBranchConstraint();
        if (strlen($cstr) > 0) {
            $cstr = "    store_id in ({$cstr})  and   ";
        }

        $conn =   \ZDB\DB::getConnect();

        //просроченые товары
        if ($this->_tvars['wsdate'] == true) {
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
        }
        //минимальное количество  
        if ($this->_tvars['wminqty'] == true) {
            $data = array();
            $sql = "select t.qty,s.storename, t.store_id, i.minqty,i.itemname,i.item_code   from (select store_id, item_id, coalesce(sum( qty),0) as qty   from  store_stock
            where  {$cstr} 1=1 group by store_id, item_id    ) t
            join items  i  on t.item_id = i.item_id
              join stores  s  on t.store_id = s.store_id
           
            where i.disabled  <> 1 and  t.qty < i.minqty and i.minqty>0 order  by  s.storename ";
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
        }

        //недавние  документы
        if ($this->_tvars['wrdoc'] == true) {
            $data = array();

            $sql = "SELECT u.username,d.document_number,d.document_id,d.amount,d.meta_desc,d.state,l.md  FROM (SELECT     document_id,user_id,MAX(createdon) AS md from docstatelog where     createdon >  " . $conn->DBDate(strtotime("-1 week", time())) . " GROUP BY document_id,user_id      ) l 
                      join documents_view d  on l.document_id= d.document_id  
                      join users_view u  on l.user_id= u.user_id where  1=1 {$br} ORDER  BY  md desc";


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
        }

        
        
       
        //мои  документы
        if ($this->_tvars['wmdoc'] == true) {
            $data = array();

            $sql = "select    d.document_id,d.meta_desc,d.document_number,d.document_date,d.amount from   documents_view d  where 1=1   {$br}  and  d.user_id={$user->user_id}   order  by  document_id desc  ";

            $rc = $conn->SelectLimit($sql,25,0);

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
        }

        //структура  доходов  и расходов
        $dt = new \App\DateTime();

        $to = $dt->startOfMonth()->getTimestamp();
        $dt = $dt->subMonth(3);
        $from = $dt->startOfMonth()->getTimestamp();

        $names = \App\Entity\IOState::getTypeList();
        if (strlen($brids) > 0) {

            $brpay = " and  document_id in(select  document_id from  documents where branch_id in ({$brids}) )";
        }

        $sql = " 
         SELECT   iotype,coalesce(sum(amount),0) as am   FROM iostate_view 
             WHERE   
              iotype >= 50    {$brpay}
              AND document_date  >= " . $conn->DBDate($from) . "
              AND  document_date  <= " . $conn->DBDate($to) . "
             GROUP BY  iotype order  by  iotype  
                         
        ";

        $rs = $conn->Execute($sql);
        $title = array();
        $data = array();
        $color = array();

        foreach ($rs as $row) {
            $data[] = abs(round($row['am']));
            $title[] = $names[$row['iotype']];
            $color[] = '#' . Util::genColor();
        }
        $this->_tvars['pbtitle'] = json_encode($title, JSON_UNESCAPED_UNICODE);
        $this->_tvars['pbdata'] = json_encode($data);
        $this->_tvars['pbcolor'] = json_encode($color);

        //сравнение  доходов  и расходов  
        $mon = array();
        $in = array();
        $out = array();

        $mlist = Util::genPastMonths(3);

        foreach ($mlist as $m) {
            $sql = " 
             SELECT   coalesce(sum(amount),0) as am   FROM iostate_view 
                 WHERE   
                  iotype >= 50    {$brpay}
                  AND document_date  >= " . $conn->DBDate($m['start']) . "
                  AND  document_date  <= " . $conn->DBDate($m['end']) . "
                  
                             
            ";
            $out[] = abs(round($conn->GetOne($sql)));
            $sql = " 
             SELECT   coalesce(sum(amount),0) as am   FROM iostate_view 
                 WHERE   
                  iotype <  50    {$brpay}
                  AND document_date  >= " . $conn->DBDate($m['start']) . "
                  AND  document_date  <= " . $conn->DBDate($m['end']) . "
                  
                             
            ";
            $in[] = abs(round($conn->GetOne($sql)));
            $mon[] = $m['name'];
        }
        $this->_tvars['pcmon'] = json_encode($mon, JSON_UNESCAPED_UNICODE);
        $this->_tvars['pcin'] = json_encode($in);
        $this->_tvars['pcout'] = json_encode($out);

        //реализация
        $mon = array();
        $tstov = array();
        $tsser = array();

        $mlist = Util::genPastMonths(6);

        foreach ($mlist as $m) {

            $mon[] = $m['name'];

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

            $tstov[] = abs(round($conn->GetOne($sql)));

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
            $tsser[] = abs(round($conn->GetOne($sql)));
        }
        $this->_tvars['tsmon'] = json_encode($mon, JSON_UNESCAPED_UNICODE);
        $this->_tvars['tstov'] = json_encode($tstov);
        $this->_tvars['tsser'] = json_encode($tsser);

        //инфоблоки
        $sql = " select coalesce(count(*),0) as cnt  from  documents_view d where  meta_name in ('Order')  
         {$br}   and d.state in (7,21)      ";

        $this->_tvars['biorders'] = $conn->GetOne($sql);

        $sql = " select coalesce(sum(partion*qty),0) as cnt  from  store_stock_view  where {$cstr} qty <>0  and item_id in (select item_id from items where disabled<>1 )                     ";

        $this->_tvars['biitemscnt'] = H::fa($conn->GetOne($sql));


        //к оплате         
        $sql = "SELECT COALESCE( SUM(   s_active - s_passive    ) ,0) AS d   FROM cust_acc_view where  s_active > s_passive   ";
        $sum = doubleval($conn->GetOne($sql));
        $sql = "SELECT COALESCE( SUM(   b_active - b_passive    ) ,0) AS d   FROM cust_acc_view where  b_active > b_passive   ";
        $sum += doubleval($conn->GetOne($sql));
        
        
        $this->_tvars['bicredit'] = H::fa($sum);
         //ожидается  оплата
        $sql = "SELECT COALESCE( SUM( s_passive -  s_active      ) ,0) AS d   FROM cust_acc_view where  s_active < s_passive   ";
        $sum = doubleval($conn->GetOne($sql));
        $sql = "SELECT COALESCE( SUM(  b_passive -  b_active      ) ,0) AS d   FROM cust_acc_view where  b_active < b_passive   ";
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

        
        $row->add(new Label('wmq_storename', $item->storename));
        $row->add(new Label('wmq_itemname', $item->itemname));
        $row->add(new Label('wmq_item_code', $item->item_code));
        $row->add(new Label('wmq_qty', H::fqty($item->qty)));
        $row->add(new Label('wmq_minqty', H::fqty($item->minqty)));
    }

    public function rdoclistOnRow($row) {
        $item = $row->getDataItem();

        $row->add(new Label('wrd_date', \App\Helper::fd(strtotime($item->md))));
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

        $sql = "SELECT u.username,d.document_number,d.amount,d.meta_desc,d.state,l.md  FROM (SELECT     document_id,user_id,MAX(createdon) AS md from docstatelog where  1=1 {$br} and createdon >  " . $conn->DBDate(strtotime("-1 week", time())) . " GROUP BY document_id,user_id      ) l 
                      join documents_view d  on l.document_id= d.document_id  
                      join users_view u  on l.user_id= u.user_id  ORDER  BY  md desc";

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
            $data['F' . $i] = \App\Helper::fd(strtotime($row['md']));
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
        $rc = $conn->SelectLimit($sql,25,0);

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
        $cstr = \App\Acl::getStoreBranchConstraint();
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
            $data['B' . $i] = $st->itemname;
            $data['C' . $i] = $st->snumber;
            $data['D' . $i] = H::fd($st->sdate);
            $data['E' . $i] = array('value' => H::fqty($st->qty), 'format' => 'number');
        }
        H::exportExcel($data, $header, 'termitem.xlsx');
    }

    public function onMQcsv($sender) {
        $brids = \App\ACL::getBranchIDsConstraint();
        if (strlen($brids) > 0) {
            $br = " and d.branch_id in ({$brids}) ";
        }
        $cstr = \App\Acl::getStoreBranchConstraint();
        if (strlen($cstr) > 0) {
            $cstr = "   store_id in ({$cstr})  and  ";
        }
        $conn = $conn = \ZDB\DB::getConnect();

        $sql = "select t.qty,s.storename, t.store_id, i.minqty,i.itemname,i.item_code   from (select store_id, item_id, coalesce(sum( qty),0) as qty   from  store_stock
            where  {$cstr} 1=1 group by store_id, item_id    ) t
            join items  i  on t.item_id = i.item_id
              join stores  s  on t.store_id = s.store_id
           
            where i.disabled  <> 1 and  t.qty < i.minqty and i.minqty>0 order  by  s.storename ";

        $rc = $conn->Execute($sql);
        $header = array();
        $data = array();

        $i = 0;
        foreach ($rc as $row) {
            $i++;
            $data['A' . $i] = $row['storename'];
            $data['B' . $i] = $row['itemname'];
            $data['C' . $i] = $row['item_code'];
            $data['D' . $i] = H::fd($row['sdate']);
            $data['E' . $i] = array('value' => H::fqty($row['qty']), 'format' => 'number');
            $data['F' . $i] = array('value' => H::fqty($row['minqty']), 'format' => 'number');
        }
        H::exportExcel($data, $header, 'minqty.xlsx');
    }

    public function test($args, $post) {

        // $this->testa->setText("Hello");
    }

}
