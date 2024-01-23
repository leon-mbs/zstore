<?php

namespace App\Pages\Report;

use App\Helper as H;
use Zippy\Html\Form\Date;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Label;
use Zippy\Html\Link\RedirectLink;
use Zippy\Html\Panel;
use App\Entity\Pay;

/**
 * Управоенческий  баланс
 */
class Balance extends \App\Pages\Base
{
    public function __construct() {
        parent::__construct();
        if (false == \App\ACL::checkShowReport('Balance')) {
            return;
        }


        $this->add(new Form('filter'))->onSubmit($this, 'OnSubmit');

        $this->filter->add(new Date('from', time()));


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


        $from = $this->filter->from->getDate();
      
        $conn= \ZDB\DB::getConnect() ;
  

        $brdoc = "";
        $brst = "";
        $brids = \App\ACL::getBranchIDsConstraint();
        if (strlen($brids) > 0) {
       //     $brst = " and   store_id in( select store_id from  stores where  branch_id in ({$brids})  ) ";

            $brdoc = " and  document_id in(select  document_id from  documents where branch_id in ({$brids}) )";
        }

        $from = $conn->DBDate($from);

        $amat=0;
        $sql = " SELECT  SUM( quantity*partion)  FROM entrylist_view 
                 where  quantity>0 ";
   
        $sql = " SELECT  SUM(( select coalesce(sum(st1.qty*st1.partion),0 ) from store_stock_view st1 where {$cstr}  st1.item_id= items.item_id )) AS ss  FROM items
                 where     ( select coalesce(sum(st1.qty),0 ) from store_stock_view st1 where {$cstr}  st1.item_id= items.item_id ) >0   ";

        $sql = " SELECT  SUM( qty * partion)  from store_stock_view
                 where  1=1 ";
   
        
        $amat = doubleval($conn->GetOne($sql)) ;
 
        $atotal = $amat;
        $ptotal = 0;
        $bal = $atotal - $ptotal ;
 

        $header = array(
            'datefrom' => \App\Helper::fd($from),
            'amat'      => $amat >0 ? H::fa($amat) : false,

            'atotal'    => H::fa($atotal) ,
            'ptotal'    => H::fa($ptotal) ,
            'bal'       => H::fa($bal)

        );

 
        $report = new \App\Report('report/balance.tpl');

        $html = $report->generate($header);

        return $html;
    }

}
