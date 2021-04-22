<?php

namespace App\Pages\Report;

use App\Application as App;
use App\Entity\Customer;
use App\Helper as H;
use Zippy\Html\Form\Date;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Label;
use Zippy\Html\Link\RedirectLink;
use Zippy\Html\Panel;
use Zippy\Html\Form\AutocompleteTextInput;

/**
 * Движение по  контрагентам
 */
class CustActivity extends \App\Pages\Base
{

    public function __construct() {
        parent::__construct();
        if (false == \App\ACL::checkShowReport('CustActivity')) {
            return;
        }

        $this->add(new Form('filter'))->onSubmit($this, 'OnSubmit');
        $this->filter->add(new Date('from', time() - (7 * 24 * 3600)));
        $this->filter->add(new Date('to', time()));

        $this->filter->add(new AutocompleteTextInput('cust'))->onText($this, 'OnAutoCustomer');

        $this->add(new \Zippy\Html\Link\ClickLink('autoclick'))->onClick($this, 'OnAutoLoad', true);

        $this->add(new Panel('detail'))->setVisible(false);
        $this->detail->add(new \Zippy\Html\Link\BookmarkableLink('print', ""));

        $this->detail->add(new RedirectLink('excel', "custreport"));

        $this->detail->add(new RedirectLink('pdf', "custreport"));
        $this->detail->add(new Label('preview'));
        \App\Session::getSession()->issubmit = false;
    }

    public function OnSubmit($sender) {
        if ($this->filter->cust->getKey() == 0) {
            $this->setError(H::l('noselcust'));
            return;
        }

        $html = $this->generateReport();
        $this->detail->preview->setText($html, true);
        \App\Session::getSession()->printform = "<html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\"></head><body>" . $html . "</body></html>";

        // \ZippyERP\System\Session::getSession()->storereport = "<html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\"></head><body>" . $html . "</body></html>";
        $reportpage = "App/Pages/ShowReport";
        $reportname = "custreport";

        $this->detail->excel->pagename = $reportpage;
        $this->detail->excel->params = array('xls', $reportname);
        $this->detail->pdf->pagename = $reportpage;
        $this->detail->pdf->params = array('pdf', $reportname);

        $this->detail->setVisible(true);

        $this->detail->preview->setText("<b >Загрузка...</b>", true);
        \App\Session::getSession()->printform = "";
        \App\Session::getSession()->issubmit = true;
    }

    private function generateReport() {

        $cust = $this->filter->cust->getKey();

        $from = $this->filter->from->getDate();
        $to = $this->filter->to->getDate();

        $i = 1;
        $detail = array();
        $conn = \ZDB\DB::getConnect();

        $sql = "
         SELECT  t.* 
          
              
         
          from (
           select
                document_number,
          date(sc.paydate) AS dt,
          SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END) AS obin,
          SUM(CASE WHEN amount < 0 THEN 0 - amount ELSE 0 END) AS obout 
           
        FROM paylist_view sc
             
             WHERE   
                customer_id= {$cust} and  
                DATE(sc.paydate) >= " . $conn->DBDate($from) . "
              AND DATE(sc.paydate) <= " . $conn->DBDate($to) . "
              GROUP BY    
                     document_number, DATE(sc.paydate)  ) t
              ORDER BY t.dt  
        ";

        $rs = $conn->Execute($sql);

        $tend = 0;
        $tin = 0;
        $tout = 0;
        foreach ($rs as $row) {


            $detail[] = array(
                "date"            => \App\Helper::fd(strtotime($row['dt'])),
                "document_number" => $row['document_number'],
                "obin"            => H::fa($row['obin']),
                "obout"           => H::fa($row['obout'])
            );

            $tin += $row['obin'];
            $tout += $row['obout'];
        }
        $tb = $tend - $tin + $tout;

        $header = array('datefrom'  => \App\Helper::fd($from),
                        "_detail"   => $detail,
                        'tin'       => H::fa($tin),
                        'tout'      => H::fa($tout),
                        'dateto'    => \App\Helper::fd($to),
                        "cust_name" => $this->filter->cust->getText()
        );
        $report = new \App\Report('report/custactivity.tpl');

        $html = $report->generate($header);

        return $html;
    }

    public function OnAutoLoad($sender) {

        if (\App\Session::getSession()->issubmit === true) {
            $html = $this->generateReport();
            \App\Session::getSession()->printform = "<html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\"></head><body>" . $html . "</body></html>";
            $this->detail->preview->setText($html, true);
            $this->updateAjax(array('preview'));
        }
    }

    public function beforeRender() {
        parent::beforeRender();

        App::addJavaScript("\$('#autoclick').click()", true);
    }

    public function OnAutoCustomer($sender) {
        return Customer::getList($sender->getText());
    }

}
