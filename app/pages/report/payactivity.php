<?php

namespace App\Pages\Report;

use App\Application as App;
use App\Entity\MoneyFund;
use App\Helper as H;
use Zippy\Html\Form\Date;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Label;
use Zippy\Html\Link\RedirectLink;
use Zippy\Html\Panel;

/**
 * Движение по денежным счетам
 */
class PayActivity extends \App\Pages\Base
{

    public function __construct() {
        parent::__construct();
        if (false == \App\ACL::checkShowReport('PayActivity')) {
            return;
        }

        $this->add(new Form('filter'))->onSubmit($this, 'OnSubmit');
        $this->filter->add(new Date('from', time() - (7 * 24 * 3600)));
        $this->filter->add(new Date('to', time()));

        $this->filter->add(new DropDownChoice('mf', MoneyFund::getList(), H::getDefMF()));

        $this->add(new \Zippy\Html\Link\ClickLink('autoclick'))->onClick($this, 'OnAutoLoad', true);

        $this->add(new Panel('detail'))->setVisible(false);
 
        $this->detail->add(new Label('preview'));
        \App\Session::getSession()->issubmit = false;
    }

    public function OnSubmit($sender) {


        $html = $this->generateReport();
        $this->detail->preview->setText($html, true);
        \App\Session::getSession()->printform = "<html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\"></head><body>" . $html . "</body></html>";

   
        $this->detail->setVisible(true);

        $this->detail->preview->setText("<b >Загрузка...</b>", true);
        \App\Session::getSession()->printform = "";
        \App\Session::getSession()->issubmit = true;
    }

    private function generateReport() {

        $mf_id = $this->filter->mf->getValue();

        $from = $this->filter->from->getDate();
        $to = $this->filter->to->getDate();

        $i = 1;
        $detail = array();
        $conn = \ZDB\DB::getConnect();
        $doc = "";
        $d = \App\Entity\Doc\Document::getConstraint();
        if (strlen($d) > 0) {
            $doc = " and document_id in ( select document_id from documents_view  where  {$d}) ";
        }
        $sql = "
         SELECT  t.*,
          
         (
        SELECT  
          
          COALESCE(SUM(sc2.amount), 0)  
         FROM paylist_view sc2
           
              WHERE 
              sc2.mf_id =  {$mf_id}
        
              AND sc2.paydate  < t.dt   
              {$doc} 
                                 
         ) as begin_amount   
         
          from (
           select
    
          date(sc.paydate) AS dt,
          SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END) AS obin,
          SUM(CASE WHEN amount < 0 THEN 0 - amount ELSE 0 END) AS obout 
           
        FROM paylist_view sc
             WHERE   
                sc.mf_id = {$mf_id}  {$doc} 
              AND DATE(sc.paydate) >= " . $conn->DBDate($from) . "
              AND DATE(sc.paydate) <= " . $conn->DBDate($to) . "
              GROUP BY    
                       DATE(sc.paydate)  ) t
              ORDER BY t.dt  
        ";

        $rs = $conn->Execute($sql);

        $tend = 0;
        $tin = 0;
        $tout = 0;
        foreach ($rs as $row) {


            $detail[] = array(
                "date"  => \App\Helper::fd(strtotime($row['dt'])),
                "in"    => H::fa(strlen($row['begin_amount']) > 0 ? $row['begin_amount'] : 0),
                "obin"  => H::fa($row['obin']),
                "obout" => H::fa($row['obout']),
                "out"   => H::fa($row['begin_amount'] + $row['obin'] - $row['obout'])
            );
            $tend = $row['begin_amount'] + $row['obin'] - $row['obout'];
            $tin += $row['obin'];
            $tout += $row['obout'];
        }
        $tb = $tend - $tin + $tout;

        $header = array('datefrom' => \App\Helper::fd($from),
                        "_detail"  => $detail,
                        'tb'       => H::fa($tb),
                        'tin'      => H::fa($tin),
                        'tout'     => H::fa($tout),
                        'tend'     => H::fa($tend),
                        'dateto'   => \App\Helper::fd($to),
                        "mf_name"  => MoneyFund::load($mf_id)->mf_name
        );
        $report = new \App\Report('report/payactivity.tpl');

        $html = $report->generate($header);

        return $html;
    }

    public function OnAutoLoad($sender) {

        if (\App\Session::getSession()->issubmit === true) {
            $html = $this->generateReport();
            \App\Session::getSession()->printform = "<html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\"></head><body>" . $html . "</body></html>";
            $this->detail->preview->setText($html, true);
           
        }
    }

    public function beforeRender() {
        parent::beforeRender();

        App::addJavaScript("\$('#autoclick').click()", true);
    }

}
