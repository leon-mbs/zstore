<?php

namespace App\Modules\PU;

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
use Zippy\WebApplication as App;

class Items extends \App\Pages\Base
{

    public $_items = array();

    public function __construct() {
        parent::__construct();

        if (strpos(System::getUser()->modules, 'promua') === false && System::getUser()->rolename != 'admins') {
            System::setErrorMsg(H::l('noaccesstopage'));

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
      //  $this->exportform->add(new DropDownChoice('ecat', $cats, 0));

        $this->add(new Form('upd'));
        $this->upd->add(new DropDownChoice('updcat', \App\Entity\Category::getList(), 0));
        
        $this->upd->add(new SubmitLink('updateqty'))->onClick($this, 'onUpdateQty');
        $this->upd->add(new SubmitLink('updateprice'))->onClick($this, 'onUpdatePrice');
        
 
        $this->add(new Form('importform'))->onSubmit($this, 'importOnSubmit');
        $this->importform->add(new CheckBox('createcat'));
   
        
    }

 

    public function filterOnSubmit($sender) {
        $this->_items = array();
        $modules = System::getOptions("modules");
        $products = array();
        try {
           $last_id=0;
           while(true){
               $data = Helper::make_request("GET","/api/v1/products/list?last_id=".$last_id,null);
               if(count($data['products'])==0) {
                   break;
               }
               foreach ($data['products'] as $product) {
                  $products[]=$product; 
                  $last_id = $product['id']  ;
               }            
           } 

          
          
          
        } catch(\Exception $ee) {
            System::setErrorMsg($ee->getMessage());
            return;
        }      
      $sku = array();
      foreach ($products as $product) {

            if (strlen($product['sku']) == 0) {
                continue;
            }
            $sku[]= $product['sku'];
      }    

            $cat = $this->filter->searchcat->getValue();
            $where = "disabled <> 1   ";
            if ($cat > 0) {
                $where .= " and cat_id=" . $cat;
            }
            $items = Item::find($where, "itemname");
            foreach ($items as $item) {
                if (strlen($item->item_code) == 0) {
                    continue;
                }
                if (in_array($item->item_code, $sku)) {
                    continue;
                } //уже  в  магазине
                $item->qty = $item->getQuantity();

                if (strlen($item->qty) == 0) {
                    $item->qty = 0;
                }
                $this->_items[] = $item;
            }

            $this->exportform->newitemlist->Reload();
            //$this->exportform->ecat->setValue(0);
      
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
                             'quantity_in_stock' => \App\Helper::fqty($item->qty),
                             'price'    => \App\Helper::fa($item->getPrice($modules['pupricetype']))
            );
        }
        if (count($elist) == 0) {

            $this->setError('noselitem');
            return;
        }
        $data = json_encode($elist);

    

        try {
          $data = Helper::make_request("GET","/api/v1/products/edit",$data);
        } catch(\Exception $ee) {
            System::setErrorMsg($ee->getMessage());
            return;
        }   
        $this->setSuccess('exported_items', count($elist));

        //обновляем таблицу
        $this->filterOnSubmit(null);
    }

    public function onUpdateQty($sender) {
        
        $cat = $this->upd->updcat->getValue();
        $modules = System::getOptions("modules");
          
        $elist = array();
        $items = Item::find("disabled <> 1  ". ($cat>0 ? " and cat_id=".$cat : ""));
        foreach ($items as $item) {
            if (strlen($item->item_code) == 0) {
                continue;
            }

            $qty = $item->getQuantity();
            $elist[$item->item_code] = H::fqty($qty);
        }

       
        $products = array();
        try {
           $last_id=0;
           while(true){
               $data = Helper::make_request("GET","/api/v1/products/list?last_id=".$last_id,null);
               if(count($data['products'])==0) {
                   break;
               }
               foreach ($data['products'] as $product) {
                  $products[]=$product; 
                  $last_id = $product['id']  ;
               }            
           } 

          
          
          
        } catch(\Exception $ee) {
            System::setErrorMsg($ee->getMessage());
            return;
        }       
      $sku = array();
      foreach ($products as $product) {

            if (strlen($product['sku']) == 0) {
                continue;
            }
            $sku[$product['sku']]= $product['id'];
      }    
    
         $list = array();
         foreach ($elist as $code=>$qty) {
        
            if($sku[$code]>0)  {
                $list[] = array('sku'     => $sku[$code],
                                 
                                 'quantity_in_stock' =>$elist[$code]
                                 
                );
            }
        }
  
        $data = json_encode($list);

    

        try {
          $data = Helper::make_request("POST","/api/v1/products/edit",$data);
        } catch(\Exception $ee) {
            System::setErrorMsg($ee->getMessage());
            return;
        }       
    
      $this->setSuccess('refreshed');
    }

    public function onUpdatePrice($sender) {
        $modules = System::getOptions("modules");
        $cat = $this->upd->updcat->getValue();
        
        $elist = array();
        $items = Item::find("disabled <> 1  ". ($cat>0 ? " and cat_id=".$cat : ""));
        foreach ($items as $item) {
            if (strlen($item->item_code) == 0) {
                continue;
            }

            $elist[$item->item_code] = \App\Helper::fa($item->getPrice($modules['pupricetype']));
        }

        $products = array();
        try {
           $last_id=0;
           while(true){
               $data = Helper::make_request("GET","/api/v1/products/list?last_id=".$last_id,null);
               if(count($data['products'])==0) {
                   break;
               }
               foreach ($data['products'] as $product) {
                  $products[]=$product; 
                  $last_id = $product['id']  ;
               }            
           } 

          
          
          
        } catch(\Exception $ee) {
            System::setErrorMsg($ee->getMessage());
            return;
        }     
      $sku = array();
      foreach ($products as $product) {

            if (strlen($product['sku']) == 0) {
                continue;
            }
            $sku[$product['sku']]= $product['id'];
      }    
    
         $list = array();
         foreach ($elist as $code=>$price) {
        
            if($sku[$code]>0)  {
                $list[] = array('sku'     => $sku[$code],
                                 
                                 
                                 'price'    =>$price
                                  
                );
            }
        }
  
        $data = json_encode($list);

    

        try {
          $data = Helper::make_request("POST","/api/v1/products/edit",$data);
        } catch(\Exception $ee) {
            System::setErrorMsg($ee->getMessage());
            return;
        }       
    
      $this->setSuccess('refreshed');
    }

    public function importOnSubmit($sender) {
        $modules = System::getOptions("modules");
        $common = System::getOptions("common");
           
        
        $elist = array();
        $products = array();
        try {
           $last_id=0;
           while(true){
               $data = Helper::make_request("GET","/api/v1/products/list?last_id=".$last_id,null);
               if(count($data['products'])==0) {
                   break;
               }
               foreach ($data['products'] as $product) {
                  $products[]=$product; 
                  $last_id = $product['id']  ;
               }            
           } 

          
          
          
        } catch(\Exception $ee) {
            System::setErrorMsg($ee->getMessage());
            return;
        }     
      
     
        //  $this->setInfo($json);
        $i = 0;
        foreach ($products as $product) {

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
   
            if ($modules['pupricetype'] == 'price1') {
                $item->price1 = $product['price'];
            }
            if ($modules['pupricetype'] == 'price2') {
                $item->price2 = $product['price'];
            }
            if ($modules['pupricetype'] == 'price3') {
                $item->price3 = $product['price'];
            }
            if ($modules['pupricetype'] == 'price4') {
                $item->price4 = $product['price'];
            }
            if ($modules['pupricetype'] == 'price5') {
                $item->price5 = $product['price'];
            }


            if ($common['useimages'] == 1) {
                $im =    $product['main_image'];
                $im = @file_get_contents($im);
                if (strlen($im) > 0) {
                    $imagedata = getimagesizefromstring($im);
                    $image = new \App\Entity\Image();
                    $image->content = $im;
                    $image->mime = $imagedata['mime'];
                      $conn =   \ZDB\DB::getConnect();
                    if($conn->dataProvider=='postgres') {
                      $image->thumb = pg_escape_bytea($image->thumb);
                      $image->content = pg_escape_bytea($image->content);
                        
                    }

                    $image->save();
                    $item->image_id = $image->image_id;
                }
            }
            
            $cat_name =trim( $product['group']['name']);
            if($sender->createcat->isChecked() && strlen($cat_name)>0) {
                
                 
                   $cat_name = str_replace('&nbsp;','',$cat_name) ;
                   if(strpos($cat_name,'&gt;')>0) {
                       $ar = explode('&gt;',$cat_name) ;
                       $cat_name = trim($ar[count($ar)-1] );
                       
                   }
                   $cat = \App\Entity\Category::getFirst("cat_name=" . \App\Entity\Category::qstr($cat_name) ) ;
                   
                   if($cat == null) {
                       $cat = new   \App\Entity\Category();
                       $cat->cat_name = $cat_name; 
                       $cat->save();
                       
                   }    
                    
                   $item->cat_id=$cat->cat_id; 
                 
            }           
            
            
            $item->save();
            $i++;
        }

        $this->setSuccess("loaded_items", $i);
    }

}
