<?php

namespace App\Pages\Report;

use App\Entity\Customer;
use App\Entity\Doc\Document;
use App\Helper as H;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Label;
use Zippy\Html\Link\RedirectLink;
use Zippy\Html\Panel;

/**
 * Заказанные товары (товары  в  пути)
 */
class CustOrder extends \App\Pages\Base
{
    public function __construct() {
        parent::__construct();

        if (false == \App\ACL::checkShowReport('CustOrder')) {
            return;
        }

        $this->add(new Form('filter'))->onSubmit($this, 'OnSubmit');

        $where = "status<>1 and customer_id in  (select customer_id from documents_view where meta_name='OrderCust'  and state= " . Document::STATE_INPROCESS . ")";
        $this->filter->add(new DropDownChoice('cust', Customer::findArray('customer_name', $where, 'customer_name'), 0));

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

        $cust = $this->filter->cust->getValue();

        $detail = array();

        $where = "   meta_name='OrderCust'  and  state= " . Document::STATE_INPROCESS;
        if ($cust > 0) {
            $where .= " and customer_id=" . $cust;
        }
        
        $total = 0;
        $items = array();

        foreach (Document::findYield($where) as $doc) {

            foreach ($doc->unpackDetails('detaildata') as $item) {
                if (!isset($items[$item->itemname])) {
                    $items[$item->itemname] = array('itemname' => $item->itemname, 'msr' => $item->msr, 'qty' => 0);
                }
                $items[$item->itemname]['qty'] += $item->quantity;
                $total += $item->amount;
            }
        }

        $names = array_keys($items);
        sort($names);  //соартируем по  алфавиту

        foreach ($names as $name) {
            $item = $items[$name];

            $detail[] = array('name' => $item['itemname'], 'msr' => $item['msr'], 'qty' => H::fqty($item['qty']));
        }


        $header = array(
            "_detail"       => $detail,
            'total'         => H::fa($total),
            'cust'          => $cust > 0,
            'date'          => \App\Helper::fd(time()),
            'customer_name' => $this->filter->cust->getValueName()
        );
        $report = new \App\Report('report/custorder.tpl');

        $html = $report->generate($header);

        return $html;
    }

}
