<?php

namespace App\Pages\Report;

use App\Entity\Item;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Form\Form;
use Zippy\Html\Label;
use Zippy\Html\Link\RedirectLink;
use Zippy\Html\Panel;

/**
 * Прайсы
 */
class Price extends \App\Pages\Base
{

    public function __construct() {
        parent::__construct();
        if (false == \App\ACL::checkShowReport('Price')) {
            return;
        }

        $option = \App\System::getOptions('common');

        $this->add(new Form('filter'))->onSubmit($this, 'OnSubmit');
        $this->filter->add(new CheckBox('price1'))->setVisible(strlen($option['price1']) > 0);
        $this->filter->add(new CheckBox('price2'))->setVisible(strlen($option['price2']) > 0);
        $this->filter->add(new CheckBox('price3'))->setVisible(strlen($option['price3']) > 0);
        $this->filter->add(new CheckBox('price4'))->setVisible(strlen($option['price4']) > 0);
        $this->filter->add(new CheckBox('price5'))->setVisible(strlen($option['price5']) > 0);
        $this->filter->add(new CheckBox('onstore'));

        $this->_tvars['price1name'] = $option['price1'];
        $this->_tvars['price2name'] = $option['price2'];
        $this->_tvars['price3name'] = $option['price3'];
        $this->_tvars['price4name'] = $option['price4'];
        $this->_tvars['price5name'] = $option['price5'];

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

        $option = \App\System::getOptions('common');

        $isp1 = $this->filter->price1->isChecked();
        $isp2 = $this->filter->price2->isChecked();
        $isp3 = $this->filter->price3->isChecked();
        $isp4 = $this->filter->price4->isChecked();
        $isp5 = $this->filter->price5->isChecked();
        $onstore = $this->filter->onstore->isChecked();

        $detail = array();

        $items = Item::find("disabled <>1 and detail not  like '%<noprice>1</noprice>%'", "cat_name,itemname");

        foreach ($items as $item) {

            $qty = $item->getQuantity();

            if ($onstore && ($qty > 0) == false) {
                continue;
            }

            $detail[] = array(
                "code"   => $item->item_code,
                "name"   => $item->itemname,
                "cat"    => $item->cat_name,
                "brand"  => $item->manufacturer,
                "msr"    => $item->msr,
                "qty"    => \App\Helper::fqty($qty),
                "price1" => $isp1 ? $item->getPrice('price1') : "",
                "price2" => $isp2 ? $item->getPrice('price2') : "",
                "price3" => $isp3 ? $item->getPrice('price3') : "",
                "price4" => $isp4 ? $item->getPrice('price4') : "",
                "price5" => $isp5 ? $item->getPrice('price5') : ""
            );
        }

        $header = array(
            "_detail"    => $detail,
            "price1name" => $isp1 ? $option['price1'] : "",
            "price2name" => $isp2 ? $option['price2'] : "",
            "price3name" => $isp3 ? $option['price3'] : "",
            "price4name" => $isp4 ? $option['price4'] : "",
            "price5name" => $isp5 ? $option['price5'] : "",
            'date'       => \App\Helper::fd(time())
        );
        $report = new \App\Report('report/price.tpl');

        $html = $report->generate($header);

        return $html;
    }

}
