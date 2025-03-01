<?php

namespace App\Modules\OCStore;

use App\Entity\Item;
use App\Helper as H;
use App\System;
use Zippy\Binding\PropertyBinding as Prop;
use Zippy\Html\DataList\ArrayDataSource;
use Zippy\Html\DataList\DataView;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Link\SubmitLink;
use App\Application as App;

class Items extends \App\Pages\Base
{
    public $_items = array();

    public function __construct() {
        parent::__construct();

        if (strpos(System::getUser()->modules, 'ocstore') === false && System::getUser()->rolename != 'admins') {
            System::setErrorMsg("Немає права доступу до сторінки");

            App::RedirectError();
            return;
        }
        $modules = System::getOptions("modules");
        $cats = System::getSession()->cats;
        if (is_array($cats) == false) {
            $cats = array();
            $this->setWarn('Виконайте з`єднання на сторінці налаштувань');
        }

        $this->add(new Form('filter'))->onSubmit($this, 'filterOnSubmit');
        $this->filter->add(new DropDownChoice('searchcat', \App\Entity\Category::getList(), 0));

        $this->add(new Form('exportform'))->onSubmit($this, 'exportOnSubmit');

        $this->exportform->add(new DataView('newitemlist', new ArrayDataSource(new Prop($this, '_items')), $this, 'itemOnRow'));
        $this->exportform->newitemlist->setPageSize(H::getPG());
        $this->exportform->add(new \Zippy\Html\DataList\Paginator('pag', $this->exportform->newitemlist));
        $this->exportform->add(new DropDownChoice('ecat', $cats, 0));

        $this->add(new Form('upd'));
        $this->upd->add(new DropDownChoice('updcat', \App\Entity\Category::getList(), 0));

        $this->upd->add(new SubmitLink('updateqty'))->onClick($this, 'onUpdateQty');
        $this->upd->add(new SubmitLink('updateprice'))->onClick($this, 'onUpdatePrice');


        $this->add(new ClickLink('checkconn'))->onClick($this, 'onCheck');



        $this->add(new Form('importform'))->onSubmit($this, 'importOnSubmit');
        $this->importform->add(new CheckBox('createcat'));


    }

    public function onCheck($sender) {

        Helper::connect();
        \App\Application::Redirect("\\App\\Modules\\OCStore\\Items");
    }


    public function filterOnSubmit($sender) {
        $this->_items = array();
        $modules = System::getOptions("modules");
        $url = $modules['ocsite'] . '/index.php?route=api/zstore/articles&' . System::getSession()->octoken;
        if($modules['ocv4']==1) {
            $url = $modules['ocsite'] . '/index.php?route=api/zstore.articles&' . System::getSession()->octoken;
        }
        $json = Helper::do_curl_request($url);
        if ($json === false) {
            return;
        }
        $data = json_decode($json, true);
        if (!isset($data)) {

            $this->setError("Невірна відповідь");
            \App\Helper::log($json);
            return;
        }
        if ($data['error'] == "") {

            $cat = $this->filter->searchcat->getValue();
            $where = "disabled <> 1   ";
            if ($cat > 0) {
                $where .= " and cat_id=" . $cat;
            }
            
            foreach (Item::findYield($where, "itemname") as $item) {
                if (strlen($item->item_code) == 0) {
                    continue;
                }
                if($item->noshop ==1)  continue;
                 
                if (in_array($item->item_code, $data['articles'])) {
                    continue;
                } //уже  в  магазине
                $item->qty = $item->getQuantity();

                if (strlen($item->qty) == 0) {
                    $item->qty = 0;
                }
                $this->_items[] = $item;
            }

            $this->exportform->newitemlist->Reload();
            $this->exportform->ecat->setValue(0);
        } else {
            $data['error']  = str_replace("'", "`", $data['error']) ;

            $this->setErrorTopPage($data['error']);
        }
    }

    public function itemOnRow($row) {
        $modules = System::getOptions("modules");

        $item = $row->getDataItem();
        $row->add(new CheckBox('ch', new Prop($item, 'ch')));
        $row->add(new Label('name', $item->itemname));
        $row->add(new Label('code', $item->item_code));
        $row->add(new Label('qty', \App\Helper::fqty($item->qty)));
        $row->add(new Label('price', $item->getPrice($modules['ocpricetype'])));
        $row->add(new Label('desc', $item->desription));
    }

    public function exportOnSubmit($sender) {
        $modules = System::getOptions("modules");
        $cat = $this->exportform->ecat->getValue();

        $elist = array();
        foreach ($this->_items as $item) {
            if ($item->ch == false) {
                continue;
            }
            $elist[] = array('name'     => $item->itemname,
                             'sku'      => $item->item_code,
                             'quantity' => \App\Helper::fqty($item->qty),
                             'price'    => $item->getPrice($modules['ocpricetype'])
            );
        }
        if (count($elist) == 0) {

            $this->setError('Не обрано товар');
            return;
        }
        $data = json_encode($elist);

        $fields = array(
            'data' => $data,
            'cat'  => $cat
        );

        $url = $modules['ocsite'] . '/index.php?route=api/zstore/addproducts&' . System::getSession()->octoken;
        if($modules['ocv4']==1) {
            $url = $modules['ocsite'] . '/index.php?route=api/zstore.addproducts&' . System::getSession()->octoken;
        }

        $json = Helper::do_curl_request($url, $fields);
        if ($json === false) {
            return;
        }
        $data = json_decode($json, true);

        if ($data['error'] != "") {
            $data['error']  = str_replace("'", "`", $data['error']) ;

            $this->setErrorTopPage($data['error']);
            return;
        }
        $this->setSuccess("Експортовано ".count($elist)." товарів");

        //обновляем таблицу
        $this->filterOnSubmit(null);
    }

    public function onUpdateQty($sender) {
        $modules = System::getOptions("modules");
        $cat = $this->upd->updcat->getValue();

        $elist = array();
        
        foreach (Item::findYield("disabled <> 1  ". ($cat>0 ? " and cat_id=".$cat : "")) as $item) {
            if (strlen($item->item_code) == 0) {
                continue;
            }

            $qty = $item->getQuantity();
            $elist[$item->item_code] = round($qty);
        }

        $data = json_encode($elist);

        $fields = array(
            'data' => $data
        );
        $url = $modules['ocsite'] . '/index.php?route=api/zstore/updatequantity&' . System::getSession()->octoken;
        if($modules['ocv4']==1) {
            $url = $modules['ocsite'] . '/index.php?route=api/zstore.updatequantity&' . System::getSession()->octoken;
        }
        $json = Helper::do_curl_request($url, $fields);
        if ($json === false) {
            return;
        }
        $data = json_decode($json, true);

        if ($data['error'] != "") {
            $data['error']  = str_replace("'", "`", $data['error']) ;

            $this->setErrorTopPage($data['error']);
            return;
        }
        $this->setSuccess('Оновлено');
    }

    public function onUpdatePrice($sender) {
        $modules = System::getOptions("modules");
        $cat = $this->upd->updcat->getValue();

        $elist = array();
        
        foreach (Item::findYield("disabled <> 1  ". ($cat>0 ? " and cat_id=".$cat : "")) as $item) {
            if (strlen($item->item_code) == 0) {
                continue;
            }
            $elist[$item->item_code] = $item->getPrice($modules['ocpricetype']);
        }

        $data = json_encode($elist);

        $fields = array(
            'data' => $data
        );
        $url = $modules['ocsite'] . '/index.php?route=api/zstore/updateprice&' . System::getSession()->octoken;
        if($modules['ocv4']==1) {
            $url = $modules['ocsite'] . '/index.php?route=api/zstore.updateprice&' . System::getSession()->octoken;
        }

        $json = Helper::do_curl_request($url, $fields);
        if ($json === false) {
            return;
        }
        $data = json_decode($json, true);

        if ($data['error'] != "") {
            $data['error']  = str_replace("'", "`", $data['error']) ;

            $this->setErrorTopPage($data['error']);
            return;
        }
        $this->setSuccess('Оновлено');
    }

    public function importOnSubmit($sender) {
        $modules = System::getOptions("modules");
        $common = System::getOptions("common");

        $cats = System::getSession()->cats;
        if (is_array($cats) == false) {
            $cats = array();
            $this->setWarn('Виконайте з`єднання на сторінці налаштувань');
            return;
        }



        $elist = array();

        $url = $modules['ocsite'] . '/index.php?route=api/zstore/getproducts&' . System::getSession()->octoken;
        if($modules['ocv4']==1) {
            $url = $modules['ocsite'] . '/index.php?route=api/zstore.getproducts&' . System::getSession()->octoken;
        }

        $json = Helper::do_curl_request($url);
        if ($json === false) {
            return;
        }
        $data = json_decode($json, true);

        if ($data['error'] != "") {
            $data['error']  = str_replace("'", "`", $data['error']) ;

            $this->setErrorTopPage($data['error']);
            return;
        }
        //  $this->setInfo($json);
        $i = 0;
        foreach ($data['products'] as $product) {

            if (strlen($product['sku']) == 0) {
                continue;
            }
            $cnt = Item::findCnt("item_code=" . Item::qstr($product['sku']));
            if ($cnt > 0) {
                continue;
            } //уже  есть с  таким  артикулом

            $product['name'] = str_replace('&quot;', '"', $product['name']);
            $item = new Item();
            $item->item_code = $product['sku'];
            $item->itemname = $product['name'];
            // $item->description = $product['description'];
            $item->manufacturer = $product['manufacturer'];
            $w = $product['weight'];
            $w = str_replace(',', '.', $w);
            if ( intval($product['weight_class_id']) == 2) {
                $w = doubleval($w) / 1000;
            } //граммы
            if ($w > 0) {
                $item->weight = floatval($w);
            }
            if ($modules['ocpricetype'] == 'price1') {
                $item->price1 = $product['price'];
            }
            if ($modules['ocpricetype'] == 'price2') {
                $item->price2 = $product['price'];
            }
            if ($modules['ocpricetype'] == 'price3') {
                $item->price3 = $product['price'];
            }
            if ($modules['ocpricetype'] == 'price4') {
                $item->price4 = $product['price'];
            }
            if ($modules['ocpricetype'] == 'price5') {
                $item->price5 = $product['price'];
            }


            if ($common['useimages'] == 1) {
                $im = $modules['ocsite'] . '/image/' . $product['image'];
                $im = @file_get_contents($im);
                if (strlen($im) > 0) {
                    $imagedata = getimagesizefromstring($im);
                    $image = new \App\Entity\Image();
                    $image->content = $im;
                    $image->mime = $imagedata['mime'];
                

                    $image->save();
                    $item->image_id = $image->image_id;
                }
            }

            if($sender->createcat->isChecked() && $product['cat_id'] >0) {
                $cat_name =trim($cats[$product['cat_id']]);
                if(strlen($cat_name)>0) {
                    $cat_name = str_replace('&nbsp;', '', $cat_name) ;
                    if(strpos($cat_name, '&gt;')>0) {
                        $ar = explode('&gt;', $cat_name) ;
                        $cat_name = trim($ar[count($ar)-1]);

                    }
                    $cat = \App\Entity\Category::getFirst("cat_name=" . \App\Entity\Category::qstr($cat_name)) ;

                    if($cat == null) {
                        $cat = new   \App\Entity\Category();
                        $cat->cat_name = $cat_name;
                        $cat->save();

                    }

                    $item->cat_id=$cat->cat_id;
                }
            }


            $item->save();
            $i++;
        }

        $this->setSuccess("Завантажено {$i} товарів");
    }

}
