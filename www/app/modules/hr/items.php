<?php

namespace App\Modules\HR;

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
    public $_pages = array();
    public $_origpages = array();

    public function __construct() {
        parent::__construct();

        if (strpos(System::getUser()->modules, 'horoshop') === false && System::getUser()->rolename != 'admins') {
            System::setErrorMsg("Немає права доступу до сторінки");

            App::RedirectError();
            return;
        }
        $modules = System::getOptions("modules");
    

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
        


    }

    public function onCheck($sender) {

        $token=  \App\Modules\HR\Helper::connect();
        if(strlen($token)==0) {
            return;
        }        
        try {
                $this->_pages=[];
                $this->_origpages=[];
                $body=[];
                $body['token'] =$token;
       

                $ret =   \App\Modules\HR\Helper::make_request("POST", "/api/pages/export", json_encode($body, JSON_UNESCAPED_UNICODE));
                $pages=[];
                $parents=[];
                $images=[];
                foreach($ret['pages'] as $p){
                    if($p['parent'] <2) continue;
                    $name=$p['title']['ua'] ??'' ;    
                    if($name=='') {
                       $name=$p['title']['ru'] ??'' ;                            
                    }
                    
                    $id = $p['id'] ;
                    
                    $pages[$id] = $name ;    
                    if(strlen($p['image'] ??'') >0 ) {
                       $images[$id] = $p['image'] ;        
                    }
                    
                    $parents[]=$p['parent'] ;
                };
      
                foreach($pages as $i=>$name){
                    if(in_array($i,$parents)==false)  {
                        $this->_pages[$i]=$name;
                        
                        $c= Category::getFirst('cat_name='.Category::qstr($name)) ;
                        if($c==null){
                            $c = new  Category();
                            $c->cat_name = $name; 
                            $c->save();
                             
                            if(isset($images[$i] )) {
                                $im = @file_get_contents($images[$i]);
                                if (strlen($im) > 0) {
                                    $imagedata = getimagesizefromstring($im);
                                    $image = new \App\Entity\Image();
                                    $image->content = $im;
                                    $image->mime = $imagedata['mime'];
                              
                                    if($conn->dataProvider=='postgres') {
                                        $image->thumb = pg_escape_bytea($image->thumb);
                                        $image->content = pg_escape_bytea($image->content);

                                    }
                                    $image->save();
                                    $c->image_id = $image->image_id;
                                    $c->save();
                                }        
                            }        
                        }
                        $this->_origpages[$i]  = $c->cat_id;
                        
                        
                    }
                }
      
            
            } catch(\Exception $ee) {
                $this->setErrorTopPage($ee->getMessage());
                return;
            }
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
        $conn =   \ZDB\DB::getConnect();
 
        if(count($this->_pages)==0){
            $this->setError('Не  оновллені категорії') ;
            return;
        }
 
 
        $token=  \App\Modules\HR\Helper::connect();
        if(strlen($token)==0) {
            return;
        }
 
        $i = 0;

        $page = 0;
        while(true) {


            try {
                
                $body=[];
                $body['token'] =$token;
                $body['expr'] =[];
                $body['offset'] = $page * 100 ;
                $body['limit'] = 100;
                
                $ret =   \App\Modules\HR\Helper::make_request("POST", "/api/catalog/export", json_encode($body, JSON_UNESCAPED_UNICODE));
            
            } catch(\Exception $ee) {
                $this->setErrorTopPage($ee->getMessage());
                return;
            }
            $page++;

            $data= $ret['products'] ;
            
            $c = count($data);
            if ($c == 0) {
                break;
            }
            foreach ($data as $product) {

                if (strlen($product['article']) == 0) {
                    continue;
                }
                if (intval($product['price']) == 0) {
                    continue;  //категория
                }
                $cnt = Item::findCnt("item_code=" . Item::qstr($product['article']));
                if ($cnt > 0) {
                    continue;
                } //уже  есть с  таким  артикулом

                $product->name = str_replace('&quot;', '"', $product['article']);
                $item = new Item();
                $item->item_code = $product['article'] ;
                $item->itemname = $product['title']['ua'] ?? '';
                if($item->itemname =='') {
                   $item->itemname = $product['title']['ru'] ?? '';
                }
                if($item->itemname =='') {
                   continue;
                }
                $item->description = $product['short_description']['ua'] ?? '';
                if($item->description =='') {
                   $item->description = $product['short_description']['ru'] ?? '';
                }

                if ($modules['hrpricetype'] == 'price1') {
                    $item->price1 = H::fa($product['price']);
                }
                if ($modules['hrpricetype'] == 'price2') {
                    $item->price2 = H::fa($product['price']);;
                }
                if ($modules['hrpricetype'] == 'price3') {
                    $item->price3 = H::fa($product['price']);;
                }
                if ($modules['hrpricetype'] == 'price4') {
                    $item->price4 = H::fa($product['price']);;
                }
                if ($modules['hrcpricetype'] == 'price5') {
                    $item->price5 = H::fa($product['price']);;
                }
                $item->manufacturer = $product['brand']['value']['ua'] ?? '';
                if($item->manufacturer =='') {
                   $item->manufacturer = $product['brand']['value']['ru'] ?? '';
                }
              
                $item->uktz = $product['uktzed']   ;
                $item->url = $product['link']   ;

                $cat_id=  $product['parent']['id']??0; 
                if(isset($this->_origpages[$cat_id])) {
                   $item->cat_id= $this->_origpages[$cat_id] ;     
                }
                
                
                
                if ($common['useimages'] == 1) {
                    foreach ($product['images'] as $im) {

                        $im = @file_get_contents($im);
                        if (strlen($im) > 0) {
                            $imagedata = getimagesizefromstring($im);
                            $image = new \App\Entity\Image();
                            $image->content = $im;
                            $image->mime = $imagedata['mime'];

                            if($conn->dataProvider=='postgres') {
                                $image->thumb = pg_escape_bytea($image->thumb);
                                $image->content = pg_escape_bytea($image->content);

                            }
                            $image->save();
                            $item->image_id = $image->image_id;
                            break;
                        }
                    }
                }

              
                $item->save();
        

            }
        }

        $this->setSuccess("Завантажено {$i} товарів");
    }

}
