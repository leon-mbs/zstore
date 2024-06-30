<?php

namespace App\Pages\Report;

use App\Entity\Item;
use App\Helper as H;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Label;
use Zippy\Html\Link\RedirectLink;
use Zippy\Html\Panel;

/**
 * Товары  на  комиссии
 */
class ItemComission extends \App\Pages\Base
{
    public function __construct() {
        parent::__construct();

        if (false == \App\ACL::checkShowReport('ItemComission')) {
            return;
        }
        $this->add(new Form('filter'))->onSubmit($this, 'OnSubmit');

        $br = "";
        $brids = \App\ACL::getBranchIDsConstraint();
        if (strlen($brids) > 0) {
            $br = " and  branch_id in ({$brids}) ";
        }        
        
        $clist = \App\Entity\Customer::findArray("customer_name","status=0 and customer_id in (select customer_id from documents_view where  meta_name='GoodsReceipt' and state=5 and  content like '%<comission>1</comission>%'  {$br} )","customer_name") ;
 
        $this->filter->add(new DropDownChoice('customer', $clist, 0));

        $this->add(new Panel('detail'))->setVisible(false);

        $this->detail->add(new Label('preview'));

   
    }

    public function OnSubmit($sender) {


        $html = $this->generateReport();
        $this->detail->preview->setText($html, true);
        \App\Session::getSession()->printform = "<html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\"></head><body>" . $html . "</body></html>";


        $this->detail->setVisible(true);
    }

    private function generateReport() {
      
        $cust =  $this->filter->customer->getValue() ;
        $br = "";
        $brids = \App\ACL::getBranchIDsConstraint();
        if (strlen($brids) > 0) {
            $br = " and  branch_id in ({$brids}) ";
        }        
      
      
        $where="where 1=1 ";
        if($cust >0){
            $c= " and s.customer_id=".$cust; 
        }
   
        $conn = $conn = \ZDB\DB::getConnect();
        $this->data = array();


        $sql = "
            select s.*,e.document_id from store_stock_view s
            join entrylist_view e  on e.stock_id = s.stock_id 
            {$where} and e.document_id in (select document_id from documents_view where  meta_name='GoodsReceipt' and state=5 and  content like '%<comission>1</comission>%'  {$br} ) 
            order by s.itemname 
        ";

        $detail = array();
        $res = $conn->Execute($sql);
        foreach ($res as $item) {
            $d= \App\Entity\Doc\Document::load($item["document_id"]);
            $det=[];
            $det['itemname'] =  $item['itemname'];
            $det['item_code'] = H::fqty($item['item_code']);
            $det['price'] = H::fa($item['partion']);
            $det['docs'] = $d->document_number;
            
            $sql="select coalesce(sum(quantity),0 ) from entrylist where quantity >0 and stock_id=".$item["stock_id"];
            $det['buyqty']  = H::fqty($conn->GetOne($sql) );
            $sql="select coalesce(sum(0-quantity),0 ) from entrylist where quantity < 0 and document_id in (select document_id from documents_view where  meta_name='RetCustIssue') and stock_id=".$item["stock_id"];
            $det['retqty'] =  H::fqty($conn->GetOne($sql) );
            $sql="select coalesce(sum(0-quantity),0 ) from entrylist where quantity < 0 and document_id not in (select document_id from documents_view where  meta_name='RetCustIssue') and stock_id=".$item["stock_id"];
            $det['sellqty'] = H::fqty($conn->GetOne($sql) );

            $detail[] = $det;
              
        }


        $header = array(
            "_detail" => $detail,
            "dt" => H::fd(time()) ,
            "iscust" => $cust > 0 ,
            "cust" => $cust > 0  ? $this->filter->customer->getValueName()  : ""
            

        );
        $report = new \App\Report('report/itemcomission.tpl');

        $html = $report->generate($header);

        return $html;
    }

}
