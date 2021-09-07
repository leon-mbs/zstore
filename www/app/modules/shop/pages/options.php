<?php

namespace App\Modules\Shop\Pages;

use App\Application as App;
use App\Entity\Item;
use App\Modules\Shop\Entity\Product;
use App\System;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\AutocompleteTextInput;
use Zippy\Html\Form\File;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\TextArea;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Form\CheckBox;

class Options extends \App\Pages\Base
{

    public function __construct() {
        parent::__construct();
        if (strpos(System::getUser()->modules, 'shop') === false && System::getUser()->rolename != 'admins') {
            System::setErrorMsg('noaccesstopage');
            App::RedirectError();
            return;
        }


        $this->add(new Form('shop'))->onSubmit($this, 'saveShopOnClick');

        $this->shop->add(new DropDownChoice('shopordertype', array(), 0));
        $this->shop->add(new AutocompleteTextInput('shopdefcust'))->onText($this, 'OnAutoCustomer');

        $this->shop->add(new DropDownChoice('shopdefpricetype', \App\Entity\Item::getPriceTypeList()));
        $this->shop->add(new DropDownChoice('shopdefbranch', \App\Entity\Branch::getList()));
        $this->shop->add(new TextInput('email'));
        $this->shop->add(new TextInput('shopname'));
        $this->shop->add(new TextInput('currencyname'));
        $this->shop->add(new TextInput('phone'));
        $this->shop->add(new File('logo'));
        $this->shop->add(new CheckBox('uselogin'));
        $this->shop->add(new CheckBox('usefilter'));
        $this->shop->add(new CheckBox('usefeedback'));
        $this->shop->add(new CheckBox('usemainpage'));
        $this->shop->add(new CheckBox('createnewcust'));

        $this->add(new Form('texts'))->onSubmit($this, 'saveTextsOnClick');
        $this->texts->add(new TextArea('aboutus'));
        $this->texts->add(new TextArea('contact'));
        $this->texts->add(new TextArea('delivery'));
        $this->texts->add(new TextArea('news'));

        $shop = System::getOptions("shop");
        if (!is_array($shop)) {
            $shop = array();
        }


        $this->shop->shopdefbranch->setValue($shop['defbranch']);
        $this->shop->shopdefcust->setKey($shop['defcust']);
        $this->shop->shopdefcust->setText($shop['defcustname']);
        $this->shop->shopordertype->setValue($shop['ordertype']);
        $this->shop->shopdefpricetype->setValue($shop['defpricetype']);
        $this->shop->currencyname->setText($shop['currencyname']);
        $this->shop->uselogin->setChecked($shop['uselogin']);
        $this->shop->usefilter->setChecked($shop['usefilter']);
        $this->shop->createnewcust->setChecked($shop['createnewcust']);
        $this->shop->usefeedback->setChecked($shop['usefeedback']);
        $this->shop->usemainpage->setChecked($shop['usemainpage']);
        $this->shop->shopname->setText($shop['shopname']);
        $this->shop->email->setText($shop['email']);
        $this->shop->currencyname->setText($shop['currencyname']);
        $this->shop->phone->setText($shop['phone']);

        $this->add(new ClickLink('updatesitemap'))->onClick($this, 'updateSiteMapOnClick');

        if (strlen($shop['aboutus']) > 10) {
            $this->texts->aboutus->setText(base64_decode($shop['aboutus']));
        }
        if (strlen($shop['contact']) > 10) {
            $this->texts->contact->setText(base64_decode($shop['contact']));
        }
        if (strlen($shop['delivery']) > 10) {
            $this->texts->delivery->setText(base64_decode($shop['delivery']));
        }
        if (strlen($shop['news']) > 10) {
            $this->texts->news->setText(base64_decode($shop['news']));
        }
    }

    public function saveShopOnClick($sender) {
        $shop = System::getOptions("shop");
        if (!is_array($shop)) {
            $shop = array();
        }

        $shop['defcust'] = $this->shop->shopdefcust->getKey();
        $shop['defcustname'] = $this->shop->shopdefcust->getText();

        $shop['defbranch'] = $this->shop->shopdefbranch->getValue();
        $shop['ordertype'] = $this->shop->shopordertype->getValue();
        $shop['defpricetype'] = $this->shop->shopdefpricetype->getValue();
        $shop['email'] = $this->shop->email->getText();
        $shop['shopname'] = $this->shop->shopname->getText();
        $shop['currencyname'] = $this->shop->currencyname->getText();
        $shop['phone'] = $this->shop->phone->getText();
        $shop['uselogin'] = $this->shop->uselogin->isChecked() ? 1 : 0;
        $shop['usefilter'] = $this->shop->usefilter->isChecked() ? 1 : 0;
        $shop['createnewcust'] = $this->shop->createnewcust->isChecked() ? 1 : 0;
        $shop['usefeedback'] = $this->shop->usefeedback->isChecked() ? 1 : 0;
        $shop['usemainpage'] = $this->shop->usemainpage->isChecked() ? 1 : 0;

        $file = $sender->logo->getFile();
        if (strlen($file["tmp_name"]) > 0) {
            $imagedata = getimagesize($file["tmp_name"]);

            if (preg_match('/(gif|png|jpeg)$/', $imagedata['mime']) == 0) {
                $this->setError('invalidformat');
                return;
            }

            if ($imagedata[0] * $imagedata[1] > 10000000) {
                $this->setError('toobigimage');
                return;
            }

            $name = basename($file["name"]);
            move_uploaded_file($file["tmp_name"], _ROOT . "upload/" . $name);

            $shop['logo'] = "/upload/" . $name;
        }
        System::setOptions("shop", $shop);
        $this->setSuccess('saved');
    }

    public function updateSiteMapOnClick($sender) {


        $sm = _ROOT . 'sitemap.xml';
        @unlink($sm);
        $xml = "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">";

        $prods = Product::find(" deleted = 0 ");
        foreach ($prods as $p) {
            if (strlen($p->sef) > 0) {
                $xml = $xml . " <url><loc>" . _BASEURL . "{$p->sef}</loc></url>";
            } else {
                $xml = $xml . " <url><loc>" . _BASEURL . "sp/{$p->item_id}</loc></url>";
            }
        }
        $xml .= "</urlset>";
        file_put_contents($sm, $xml);
        $this->setSuccess('refreshed');
    }

    public function saveTextsOnClick($sender) {
        $shop = System::getOptions("shop");
        if (!is_array($shop)) {
            $shop = array();
        }
        $shop['aboutus'] = base64_encode($this->texts->aboutus->getText());
        $shop['contact'] = base64_encode($this->texts->contact->getText());
        $shop['delivery'] = base64_encode($this->texts->delivery->getText());
        $shop['news'] = base64_encode($this->texts->news->getText());

        System::setOptions("shop", $shop);
        $this->setSuccess('refreshed');
    }

    public function OnAutoCustomer($sender) {
        return \App\Entity\Customer::getList($sender->getText(), 1);
    }

}
