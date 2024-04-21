<?php

namespace App\Modules\WC;

use App\Entity\Item;
use App\Entity\Category;
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

        if (strpos(System::getUser()->modules, 'woocomerce') === false && System::getUser()->rolename != 'admins') {
            System::setErrorMsg("Немає права доступу до сторінки");

            App::RedirectError();
            return;
        }
        $modules = System::getOptions("modules");

        $this->add(new Form('filter'))->onSubmit($this, 'filterOnSubmit');
        $this->filter->add(new DropDownChoice('searchcat', Category::getList(), 0));

        $this->add(new Form('exportform'))->onSubmit($this, 'exportOnSubmit');

        $this->exportform->add(new DataView('newitemlist', new ArrayDataSource(new Prop($this, '_items')), $this, 'itemOnRow'));
        $this->exportform->newitemlist->setPageSize(H::getPG());
        $this->exportform->add(new \Zippy\Html\DataList\Paginator('pag', $this->exportform->newitemlist));

        $this->add(new Form('upd'));
        $this->upd->add(new DropDownChoice('updcat', \App\Entity\Category::getList(), 0));

        $this->upd->add(new SubmitLink('updateqty'))->onClick($this, 'onUpdateQty');
        $this->upd->add(new SubmitLink('updateprice'))->onClick($this, 'onUpdatePrice');

        $this->add(new ClickLink('getitems'))->onClick($this, 'onGetItems');

        $this->add(new ClickLink('checkconn'))->onClick($this, 'onCheck');

    }

    public function onCheck($sender) {

        Helper::connect();
        \App\Application::Redirect("\\App\\Modules\\WC\\Items");
    }

    public function filterOnSubmit($sender) {
        $this->_items = array();
        $modules = System::getOptions("modules");

        $client = \App\Modules\WC\Helper::getClient();
        $skus = array();

        try {
            $data = $client->get('products', array('status' => 'publish'));
        } catch(\Exception $ee) {
            $this->setErrorTopPage($ee->getMessage());
            return;
        }
          $qty =  count($data);
        foreach ($data as $p) {
            if (strlen($p->sku) > 0) {
                $skus[] = $p->sku;
            }
        }
        unset($data);
        $cat_id = $sender->searchcat->getValue();

        $w = "disabled <> 1";
        if ($cat_id > 0) {
            $w .= " and cat_id=" . $cat_id;
        }
        
        foreach (Item::findYield($w, "itemname") as $item) {
            if (strlen($item->item_code) == 0) {
                continue;
            }
            if (in_array($item->item_code, $skus)) {
                continue;
            } //уже  в  магазине

            $item->qty = $item->getQuantity();

            if (strlen($item->qty) == 0) {
                $item->qty = 0;
            }
            $this->_items[] = $item;
        }

        $this->exportform->newitemlist->Reload();
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

    //экспорт товара  в  магазин
    public function exportOnSubmit($sender) {
        $modules = System::getOptions("modules");
        $client = \App\Modules\WC\Helper::getClient();

        $elist = array();
        foreach ($this->_items as $item) {
            if ($item->ch == false) {
                continue;
            }
            $elist[] = array('name'           => $item->itemname,
                //  'short_description' => $item->description,
                             'sku'            => $item->item_code,
                             'manage_stock'   => true,
                             'stock_quantity' => (string)\App\Helper::fqty($item->qty),
                             'price'          => (string)$item->getPrice($modules['wcpricetype']),
                             'regular_price'  => (string)$item->getPrice($modules['wcpricetype'])
            );
        }
        if (count($elist) == 0) {
            $this->setError('Не обрано товар');
            return;
        }

        try {
            foreach ($elist as $p) {

                $data = $client->post('products', $p);
            }
        } catch(\Exception $ee) {
            $this->setErrorTopPage($ee->getMessage());
            return;
        }

        $this->setSuccess("Експортовано ".count($elist)." товарів");

        //обновляем таблицу
        $this->filterOnSubmit($this->filter);
    }

    //обновление  количества в  магазине
    public function onUpdateQty($sender) {
        $modules = System::getOptions("modules");
        $client = \App\Modules\WC\Helper::getClient();
        $cat = $this->upd->updcat->getValue();

        $page=1;
        $cnt =1;
        while(true) {

            try {
                $data = $client->get('products', array('status' => 'publish' , 'page' => $page, 'per_page' => 100));
            } catch(\Exception $ee) {
                $this->setErrorTopPage($ee->getMessage());
                return;
            }
             $c = count($data);

            \App\Helper::log($page*$c);

            if ($c == 0) {
                break;
            }
            $page++;

            $skulist = array();
            $skuvarlist = array();

            foreach ($data as $p) {
                if (strlen($p->sku) == 0) {
                    continue;
                }
                $skulist[$p->sku] = $p->id;
                if(is_array($p->variations)) {
                    foreach($p->variations as $vid) {
                        $var = $client->get("products/{$p->id}/variations/{$vid}", array('status' => 'publish'));

                        if (strlen($var->sku) == 0) {
                            continue;
                        }
                        $skuvarlist[$var->sku]=array("pid"=>$p->id,"vid"=>$vid);

                    }
                }
            }
            unset($data);




            $qty =  count($skulist);
            $qty =  count($skuvarlist);

            $elist = array();
            
            foreach (Item::findYield("disabled <> 1  ". ($cat>0 ? " and cat_id=".$cat : "")) as $item) {
                if (strlen($item->item_code) == 0) {
                    continue;
                }
                if ($skulist[$item->item_code] > 0) {
                    $qty = $item->getQuantity();
                    if ($qty > 0) {
                        $elist[$item->item_code] = $qty;
                    }
                }
            }
            $data = array('update' => array());
            foreach ($elist as $sku => $qty) {

                $data['update'][] = array('id' => $skulist[$sku], 'stock_quantity' => (string)$qty);
                $cnt++;
            }

            try {
                $client->post('products/batch', $data);
            } catch(\Exception $ee) {
                $this->setErrorTopPage($ee->getMessage());
                return;
            }


            foreach ($skuvarlist as $sku => $arr) {
                $qty =  $elist[$sku];
                if(strlen($qty)==0) {
                    $qty=0;
                }
                $client->put("products/{$arr['pid']}/variations/{$arr['vid']}", array(  'stock_quantity' => (string)$qty ));
                $cnt++;
            }
        }

        $this->setSuccess("Оновлено {$cnt} товарів");
    }

    //обновление цен в  магазине
    public function onUpdatePrice($sender) {
        $modules = System::getOptions("modules");
        $client = \App\Modules\WC\Helper::getClient();
        $cat = $this->upd->updcat->getValue();

        $page=1;
        $cnt =1;
        while(true) {


            $skulist = array();
            $skuvarlist = array();
            try {
                $data = $client->get('products', array('status' => 'publish', 'page' => $page, 'per_page' => 100));
            } catch(\Exception $ee) {
                $this->setErrorTopPage($ee->getMessage());
                return;
            }
              $c = count($data);
            if ($c == 0) {
                break;
            }
            $page++;

            $sku = array();
            foreach ($data as $p) {
                if (strlen($p->sku) == 0) {
                    continue;
                }
                $skulist[$p->sku] = $p->id;
                if(is_array($p->variations)) {
                    foreach($p->variations as $vid) {
                        $var = $client->get("products/{$p->id}/variations/{$vid}", array('status' => 'publish'));

                        if (strlen($var->sku) == 0) {
                            continue;
                        }
                        $skuvarlist[$var->sku]=array("pid"=>$p->id,"vid"=>$vid);

                    }
                }

            }
            unset($data);

            $elist = array();
            
            foreach (Item::findYield("disabled <> 1  ". ($cat>0 ? " and cat_id=".$cat : "")) as $item) {
                if (strlen($item->item_code) == 0) {
                    continue;
                }
                if ($skulist[$item->item_code] > 0 || is_array($skuvarlist[$item->item_code])) {
                    $price = $item->getPrice($modules['wcpricetype']);
                    if ($price > 0) {
                        $elist[$item->item_code] = $price;
                    }
                }
            }
            $data = array('update' => array());
            foreach ($elist as $sku => $price) {
                $cnt++;
                $data['update'][] = array('id' => $skulist[$sku], 'price' => (string)$price, 'regular_price' => (string)$price);
            }

            try {
                $client->post('products/batch', $data);
            } catch(\Exception $ee) {
                $this->setErrorTopPage($ee->getMessage());
                return;
            }



            foreach ($skuvarlist as $sku => $arr) {
                $price =  $elist[$sku];
                $client->put("products/{$arr['pid']}/variations/{$arr['vid']}", array(  'price' => (string)$price, 'regular_price' => (string)$price));
                $cnt++;
            }

        }

        $this->setSuccess("Оновлено {$cnt} товарів");
    }

    //импорт товара с  магазина
    public function onGetItems($sender) {
        $modules = System::getOptions("modules");
        $common = System::getOptions("common");
        $conn =   \ZDB\DB::getConnect();
        $client = \App\Modules\WC\Helper::getClient();
        $i = 0;

        $page = 1;
        while(true) {


            try {
                $data = $client->get('products', array('status' => 'publish', 'page' => $page, 'per_page' => 100));
            } catch(\Exception $ee) {
                $this->setErrorTopPage($ee->getMessage());
                return;
            }
            $page++;

            $c = count($data);
            if ($c == 0) {
                break;
            }
            foreach ($data as $product) {

                if (strlen($product->sku) == 0) {
                    continue;
                }
                $cnt = Item::findCnt("item_code=" . Item::qstr($product->sku));
                if ($cnt > 0) {
                    continue;
                } //уже  есть с  таким  артикулом

                $product->name = str_replace('&quot;', '"', $product->name);
                $item = new Item();
                $item->item_code = $product->sku;
                $item->itemname = $product->name;
                //   $item->description = $product->short_description;

                if ($modules['wcpricetype'] == 'price1') {
                    $item->price1 = $product->price;
                }
                if ($modules['wcpricetype'] == 'price2') {
                    $item->price2 = $product->price;
                }
                if ($modules['wcpricetype'] == 'price3') {
                    $item->price3 = $product->price;
                }
                if ($modules['wcpricetype'] == 'price4') {
                    $item->price4 = $product->price;
                }
                if ($modules['wcpricetype'] == 'price5') {
                    $item->price5 = $product->price;
                }


                if ($common['useimages'] == 1) {
                    foreach ($product->images as $im) {

                        $im = @file_get_contents($im->src);
                        if (strlen($im) > 0) {
                            $imagedata = getimagesizefromstring($im);
                            $image = new \App\Entity\Image();
                            $image->content = $im;
                            $image->mime = $imagedata['mime'];

                            $image->save();
                            $item->image_id = $image->image_id;
                            break;
                        }
                    }
                }

                $item->save();
                $i++;

                //вариации
                if(is_array($product->variations)) {
                    foreach($product->variations as $vid) {
                        $var = $client->get("products/{$product->id}/variations/{$vid}", array('status' => 'publish', 'page' => $page, 'per_page' => 100));
                        if (strlen($var->sku) == 0) {
                            continue;
                        }
                        $cnt = Item::findCnt("item_code=" . Item::qstr($var->sku));
                        if ($cnt > 0) {
                            continue;
                        } //уже  есть с  таким  артикулом


                        $item = new Item();
                        //  $item->wcvar = 1;
                        $item->item_code = $var->sku;
                        $item->itemname = $product->name ." (var {$vid})";
                        //   $item->description = $product->short_description;

                        if ($modules['wcpricetype'] == 'price1') {
                            $item->price1 = $var->price;
                        }
                        if ($modules['wcpricetype'] == 'price2') {
                            $item->price2 = $var->price;
                        }
                        if ($modules['wcpricetype'] == 'price3') {
                            $item->price3 = $var->price;
                        }
                        if ($modules['wcpricetype'] == 'price4') {
                            $item->price4 = $var->price;
                        }
                        if ($modules['wcpricetype'] == 'price5') {
                            $item->price5 = $var->price;
                        }


                        if ($common['useimages'] == 1 && $var->image !=null) {

                            $im = @file_get_contents($var->image->src);
                            if (strlen($im) > 0) {
                                $imagedata = getimagesizefromstring($im);
                                $image = new \App\Entity\Image();
                                $image->content = $im;
                                $image->mime = $imagedata['mime'];
                              

                                $image->save();
                                $item->image_id = $image->image_id;
                                break;
                            }

                        }

                        $item->save();
                        $i++;





                    }

                }




            }
        }

        $this->setSuccess("Завантажено {$i} товарів");
    }

}
