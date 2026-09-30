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
    public $_siteid = 0;   //выбранный  сайт

    public function __construct() {
        parent::__construct();

        if (strpos(System::getUser()->modules, 'ocstore') === false && System::getUser()->rolename != 'admins') {
            System::setErrorMsg("Немає права доступу до сторінки");

            App::RedirectError();
            return;
        }
        //выбор  сайта  показывается  если  сайтов  больше  одного
        $sites = array();
        foreach (Helper::sites() as $site) {
            $sites[$site['id']] = $site['name'];
        }
        $this->_siteid = intval(System::getSession()->ocsiteid);
        if (isset($sites[$this->_siteid]) == false) {
            $this->_siteid = intval(Helper::site()['id'] ?? 0);
        }
        $this->_tvars['ocmulti'] = count($sites) > 1;

        $this->add(new Form('siteform'));
        $this->siteform->add(new DropDownChoice('site', $sites, $this->_siteid))->onChange($this, 'onSite');

        $cats = Helper::cats($this->_siteid);
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

        $this->upd->add(new CheckBox('allsites'));
        $this->upd->add(new SubmitLink('updateqty'))->onClick($this, 'onUpdateQty');
        $this->upd->add(new SubmitLink('updateprice'))->onClick($this, 'onUpdatePrice');
     

        $this->add(new ClickLink('checkconn'))->onClick($this, 'onCheck');



        $this->add(new Form('importform'))->onSubmit($this, 'importOnSubmit');
        $this->importform->add(new CheckBox('createcat'));

        $this->add(new ClickLink('updatenamestooc'))->onClick($this, 'onExportNames');
        $this->add(new ClickLink('updatenamesfromoc'))->onClick($this, 'onImportNames');

    }

    public function onCheck($sender) {

        System::getSession()->ocsiteid = $this->_siteid;
        Helper::connect($this->_siteid);
        \App\Application::Redirect("\\App\\Modules\\OCStore\\Items");
    }

    //сменился  сайт
    public function onSite($sender) {
        $this->_siteid = intval($sender->getValue());
        System::getSession()->ocsiteid = $this->_siteid;

        $this->_items = array();
        $this->exportform->newitemlist->Reload();

        $this->updateCats();
        if (is_array(Helper::cats($this->_siteid)) == false) {
            $this->setWarn('Виконайте з`єднання на сторінці налаштувань');
        }
    }

    //категории  выбранного  сайта
    private function updateCats() {
        $cats = Helper::cats($this->_siteid);
        if (is_array($cats) == false) {
            $cats = array();
        }
        if ($cats != $this->exportform->ecat->getOptionList()) {
            $this->exportform->ecat->setOptionList($cats);
        }
    }

    //тип  цены  выбранного  сайта
    private function priceType($siteId = 0) {
        $site = Helper::site($siteId > 0 ? $siteId : $this->_siteid);

        return $site['pricetype'] ?? 'price1';
    }

    //сайты  для  обновления  количества  и  цен: выбранный  или  все  включенные
    private function updSites() {
        if (count(Helper::sites()) > 1 && $this->upd->allsites->isChecked()) {
            return array_keys(Helper::sites());
        }

        return array($this->_siteid);
    }


    public function filterOnSubmit($sender) {
        $this->_items = array();
        $data = Helper::request($this->_siteid, 'api/zstore/articles');
        $this->updateCats();
        if ($data !== false) {

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
        }
    }

    public function itemOnRow($row) {

        $item = $row->getDataItem();
        $row->add(new CheckBox('ch', new Prop($item, 'ch')));
        $row->add(new Label('name', $item->itemname));
        $row->add(new Label('code', $item->item_code));
        $row->add(new Label('qty', \App\Helper::fqty($item->qty)));
        $row->add(new Label('price', $item->getPrice($this->priceType())));
        $row->add(new Label('desc', $item->desription));
    }

    public function exportOnSubmit($sender) {
        $cat = $this->exportform->ecat->getValue();

        $elist = array();
        foreach ($this->_items as $item) {
            if ($item->ch == false) {
                continue;
            }
            $elist[] = array('name'     => $item->itemname,
                             'sku'      => $item->item_code,
                             'quantity' => \App\Helper::fqty($item->qty),
                             'price'    => $item->getPrice($this->priceType())
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

        $data = Helper::request($this->_siteid, 'api/zstore/addproducts', $fields);
        if ($data === false) {
            return;
        }
        $this->setSuccess("Експортовано ".count($elist)." товарів");

        //обновляем таблицу
        $this->filterOnSubmit(null);
    }

    public function onUpdateQty($sender) {
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

        //ошибка  одного  сайта  не  останавливает  остальные
        $errors = array();
        foreach ($this->updSites() as $siteid) {
            if (Helper::request($siteid, 'api/zstore/updatequantity', $fields) === false) {
                $errors[] = System::getErrorMsg();
            }
        }
        if (count($errors) > 0) {
            System::setErrorMsg(implode('; ', $errors), true);
            return;
        }
        $this->setSuccess('Оновлено');
    }


    public function onUpdatePrice($sender) {
        $cat = $this->upd->updcat->getValue();

        //ошибка  одного  сайта  не  останавливает  остальные
        $errors = array();
        foreach ($this->updSites() as $siteid) {
            $pricetype = $this->priceType($siteid);   //тип  цены  у  каждого  сайта  свой

            $elist = array();

            foreach (Item::findYield("disabled <> 1  ". ($cat>0 ? " and cat_id=".$cat : "")) as $item) {
                if (strlen($item->item_code) == 0) {
                    continue;
                }
                $elist[$item->item_code] = $item->getPrice($pricetype);
            }

            $data = json_encode($elist);

            $fields = array(
                'data' => $data
            );

            if (Helper::request($siteid, 'api/zstore/updateprice', $fields) === false) {
                $errors[] = System::getErrorMsg();
            }
        }
        if (count($errors) > 0) {
            System::setErrorMsg(implode('; ', $errors), true);
            return;
        }
        $this->setSuccess('Оновлено');
    }

    public function importOnSubmit($sender) {
        $common = System::getOptions("common");
        $site = Helper::site($this->_siteid);
        $pricetype = $this->priceType();

        $elist = array();

        $data = Helper::request($this->_siteid, 'api/zstore/getproducts');
        if ($data === false) {
            return;
        }
        $cats = Helper::cats($this->_siteid);
        if (is_array($cats) == false) {
            $cats = array();
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
            if ($pricetype == 'price1') {
                $item->price1 = $product['price'];
            }
            if ($pricetype == 'price2') {
                $item->price2 = $product['price'];
            }
            if ($pricetype == 'price3') {
                $item->price3 = $product['price'];
            }
            if ($pricetype == 'price4') {
                $item->price4 = $product['price'];
            }
            if ($pricetype == 'price5') {
                $item->price5 = $product['price'];
            }


            if ($common['useimages'] == 1) {
                $im = $site['site'] . '/image/' . $product['image'];
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

    
    public function onImportNames($sender) {
        $common = System::getOptions("common");
  
        $elist = array();

        $data = Helper::request($this->_siteid, 'api/zstore/getnames');
        if ($data === false) {
            return;
        }
        //  $this->setInfo($json);
        $i = 0;
        foreach ($data['names'] as  $name) {

            if (strlen($name['name']) == 0) {
                continue;
            }
            
            $name['name']  = str_replace("&quot;","",$name['name']) ; 
            
            $item = Item::getFirst("item_code=" . Item::qstr($name['sku']));
            if ($item == null) {
                continue;
            }   
            if ($item->itemname===$name['name']) {
                continue;
            }   
            
            $item->itemname=$name['name']; 
            $item->save(); 
            
        }  
        $this->setSuccess("Оновлено ");
  
    }    
    public function onExportNames($sender) {
        $common = System::getOptions("common");
  
        $names = array();

        foreach(Item::findYield("disabled<>1") as $item){
            if($item->noshop ==1)  continue;
            if(strlen($item->item_code) ==0)  continue;
                
            $names[$item->item_code]=  $item->itemname ;
        }
        
        if (count($names) == 0) {

             
            return;
        }
        $data = json_encode($names);

        $fields = array(
            'data' => $data 
             
        );     
        
        $data = Helper::request($this->_siteid, 'api/zstore/updatenames', $fields);
        if ($data === false) {
            return;
        }
  
        $this->setSuccess("Оновлено ");
  
    }    
    
    
}
