<?php

namespace App\Pages\Report;

use App\Entity\Item;
use App\Entity\Category;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Form\DropDownChoice;
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
        $this->filter->add(new CheckBox('showqty'));
        $this->filter->add(new CheckBox('showdesc'));
        $this->filter->add(new CheckBox('showimage'));

        $catlist = array();
        foreach (Category::findYield("cat_id in (select cat_id from items where disabled <>1 )", "cat_name") as $c) {
            if($c->noprice==1) {
                continue;
            }
            $catlist[$c->cat_id] = $c->cat_name;
        }
        $this->filter->add(new DropDownChoice('searchcat', $catlist, 0));
        $this->filter->add(new TextInput('searchbrand'));
        $this->filter->searchbrand->setDataList(Item::getManufacturers());
        
        
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
        $showdesc = $this->filter->showdesc->isChecked();
        $showimage = $this->filter->showimage->isChecked();
        $showqty = $this->filter->showqty->isChecked();
        $cat = $this->filter->searchcat->getValue();
        $brand = $this->filter->searchbrand->getText();
        
        $this->_tvars['showimage'] = $showimage;
        
        $detail = array();
        
        
        $sql ="disabled <>1 and detail not  like '%<noprice>1</noprice>%'";
        if($cat > 0){
            $c =   Category::load($cat) ;
            $ch = $c->getChildren();
            if(count($ch)==0) {
               $sql = $sql . " and cat_id = ". $cat;    
            } else {
               $j = implode(',',$ch) ;
               $sql = $sql . " and cat_id in (". $j .")";    
            }
            
        }
        if(strlen($brand) > 0){
            $sql = $sql . " and manufacturer=". Item::qstr($brand) ;
        }

        $cats = Category::find('') ;                   
           
        foreach (Item::findYield($sql, "cat_name,itemname") as $item) {


            if($item->cat_id >0) {
                $c= $cats[$item->cat_id]?? null;
                if($c instanceof Category) {
                    if($c->noprice) {
                        continue;
                    }
                }
                
            }
            
            $qty = $item->getQuantity();

            if ($onstore && ($qty > 0) == false) {
                continue;
            }
            $im="";
            if($item->image_id>0 && $showimage) {
               $image=\App\Entity\Image::load($item->image_id)   ;
               if($image != null) {
                   $im=$image->getUrlData();
                   unset($image) ;
               } else {
                  $item->image_id=0; 
               }
            }
            
            $notes  = str_replace("\"","`",$item->notes) ;
            $notes  = str_replace("'","`",$notes) ;
            $notes  = str_replace("<","&lt;",$notes) ;
            $notes  = str_replace(">","&gt;",$notes) ;
            
            $detail[] = array(
                "code"   => $item->item_code,
                "name"   => $item->itemname,
                "desc"   => $item->description,
                "isimage"   => $item->image_id>0 && $showimage==true,
                "im"   => $im,
                "cat"    => $item->cat_name,
                "brand"  => $item->manufacturer,
                "msr"    => $item->msr,
                "notes"  => $notes,
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
            "price1name" => $isp1 ? $option['price1'] : false,
            "price2name" => $isp2 ? $option['price2'] : false,
            "price3name" => $isp3 ? $option['price3'] : false,
            "price4name" => $isp4 ? $option['price4'] : false,
            "price5name" => $isp5 ? $option['price5'] : false,
            "iscat" => $cat == 0 ,
            "showimage"   => $showimage,
            "isbrand" => strlen($brand)==0 ,
            "catname" => $this->filter->searchcat->getValueName(),
            "brandname" => $brand,
            "showqty" => $showqty == 1,
            "showdesc" => $showdesc == 1,
            'date'       => \App\Helper::fd(time())
        );
        $report = new \App\Report('report/price.tpl');

        $html = $report->generate($header);

        return $html;
    }

}
