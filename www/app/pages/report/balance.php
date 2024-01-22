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
      


        $detail = array();
        $detail2 = array();

    

        $brpay = "";
        $brst = "";
        $brids = \App\ACL::getBranchIDsConstraint();
        if (strlen($brids) > 0) {
       //     $brst = " and   store_id in( select store_id from  stores where  branch_id in ({$brids})  ) ";

       //     $brpay = " and  document_id in(select  document_id from  documents where branch_id in ({$brids}) )";
        }


 

        $header = array(
            'datefrom' => \App\Helper::fd($from),
            'dateto'   => \App\Helper::fd($to),
            "_detail"  => $detail,
            "_detail2" => $detail2,
            'tin'      => H::fa($tin),
            'tout'     => H::fa($tout),
            'total'    => H::fa($total)

        );

 
        $report = new \App\Report('report/balance.tpl');

        $html = $report->generate($header);

        return $html;
    }

}
