<?php

namespace App\Modules\Shop\Pages\Catalog;

use App\Application as App;
use App\Modules\Shop\Entity\Product;
use App\Modules\Shop\Entity\ProductComment;
use App\Modules\Shop\Helper;
use App\System;
use Zippy\Binding\PropertyBinding;
use Zippy\Html\DataList\ArrayDataSource;
use Zippy\Html\DataList\DataView;
use Zippy\Html\Form\TextArea;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Label;
use Zippy\Html\Panel;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Link\RedirectLink;
use Zippy\Html\Link\BookmarkableLink;

//детализация  по товару, отзывы
class ProductView extends Base
{
    public $msg;
    public $attrlist;
    public $clist;
    protected $item_id;

    public function __construct($item_id = 0) {
        parent::__construct();

        $item_id = intval($item_id);

        $this->item_id = $item_id;
        $product = Product::load($item_id);
        if ($product == null) {
            http_response_code(404) ;
            die;
        }

        $options = \App\System::getOptions('shop');
        $this->_tvars['usefeedback'] = $options['usefeedback'] == 1;
    
        $this->add(new Label("breadcrumb", Helper::getBreadScrumbs($product->cat_id), true));
        $this->add(new ClickLink('backtolist', $this, 'OnBack'));

        $this->_title = $product->itemname;
        // $this->_description = $product->getDescription();

        $this->add(new \Zippy\Html\Link\BookmarkableLink('product_image'));
//        $this->add(new \Zippy\Html\Link\BookmarkableLink('product_image'))->setValue("/loadshopimage.php?id={$product->image_id}");
        $this->product_image->setAttribute('href', "/loadshopimage.php?id={$product->image_id}");

        $this->product_image->add( new  \Zippy\Html\Image('product_imageimg'))->setUrl("/loadshopimage.php?id={$product->image_id}");
        
        $this->add(new Label('productname', $product->itemname));
        $this->add(new Label('productcode', $product->item_code));
   
 
        
        $this->add(new Label('customsize',  $product->customsize));
        $this->add(new Label('onstore'));
        $this->add(new Label('action'))->setVisible(false);
        $this->add(new \Zippy\Html\Label('manufacturername', $product->manufacturer))->SetVisible(strlen($product->manufacturer) > 0);
        $price = $product->getPurePrice($options['defpricetype']);
        $price = \App\Helper::fa($price);

        $this->add(new Label('price', $price . ' ' . $options['currencyname']));
        $this->add(new Label('actionprice', \App\Helper::fa($product->getActionPrice()). ' ' . $options['currencyname']))->setVisible(false);
        if ($product->hasAction()) {
            $this->price->setAttribute('style', 'font-size:smaller;text-decoration:line-through');
            $this->actionprice->setVisible(true);
            $this->action->setVisible(true);
        }

        $this->add(new Label('description', $product->getDescription(), true));
        $this->add(new TextInput('rated'))->setText($product->getRating());
        $this->add(new Label('comments', "Відгуків (".intval($product->comments).") "));

        $list = Helper::getAttributeValuesByProduct($product, false);
        $this->add(new \Zippy\Html\DataList\DataView('attributelist', new \Zippy\Html\DataList\ArrayDataSource($list), $this, 'OnAddAttributeRow'))->Reload();
        $this->add(new ClickLink('buy', $this, 'OnBuy'));
        $this->add(new ClickLink('addtocompare', $this, 'OnAddCompare'));
        $this->add(new RedirectLink('compare', "\\App\\Modules\\Shop\\Pages\\Catalog\\Compare"))->setVisible(false);

        $form = $this->add(new \Zippy\Html\Form\Form('formcomment'));
        $form->onSubmit($this, 'OnComment');
        $form->add(new TextInput('starnick'));
        $form->add(new TextInput('rating'));
        $form->add(new TextArea('comment'));
        $form->add(new TextInput('capchacode'));
        $form->add(new \ZCL\Captcha\Captcha('capcha'));

        $this->clist = ProductComment::findByProduct($product->item_id);
        $this->add(new \Zippy\Html\DataList\DataView('commentlist', new \Zippy\Html\DataList\ArrayDataSource(new PropertyBinding($this, 'clist')), $this, 'OnAddCommentRow'));
        $this->commentlist->setPageSize(10);
        $this->add(new \Zippy\Html\DataList\Pager("pag", $this->commentlist));
        $this->commentlist->Reload();

        if ($product->disabled == 1 || $product->noshop == 1) {
            $this->onstore = 'Знято з продажу';
            $this->buy->setVisible(false);
        } else {

            if ($product->getQuantity() > 0 || $this->_tvars["isfood"]==true) {
                $this->onstore->setText('В наявності');
                $this->buy->setValue('Купити');
            } else {
                $this->onstore->setText('Під замовлення');
                $this->buy->setValue('Замовити');
            }
        }

        $imglist = array();

        foreach ($product->getImages(true) as $id) {
            $img = \App\Entity\Image::load($id);
            if($img != null) {
               $imglist[] = \App\Entity\Image::load($id);    
            }
            
        }
        $this->add(new DataView('imagelist', new ArrayDataSource($imglist), $this, 'imglistOnRow'))->Reload();
        $this->_tvars['islistimage'] = count($imglist) > 1;

        //вариации
        $vars = $product->getVarList($options['defpricetype']);

        $this->add(new Panel('varpan'))->setVisible(count($vars)>0);
        $this->varpan->add(new DataView('varlist', new ArrayDataSource($vars), $this, 'varlistOnRow'))->Reload();
        $this->varpan->add(new Label("vattrname", $product->vattrname))  ;



        $this->add(new Panel('recpan'))->setVisible(count($product->reclist ?? [])>0);
        $reclist=[];
        foreach($product->reclist ?? [] as $r) {
            $reclist[] = Product::load($r->item_id);
        }
        $this->recpan->add(new DataView('reclist', new ArrayDataSource($reclist), $this, 'reclistOnRow'))->Reload();



        $recently = \App\Session::getSession()->recently;
        if (!is_array($recently)) {
            $recently = array();
        }
        $recently[$product->item_id] = $product->item_id;
        \App\Session::getSession()->recently = $recently;


        if(strlen($_COOKIE['viewitem_'.$product->item_id])==0) {
            \App\Helper::insertstat(\App\Helper::STAT_VIEW_ITEM, $product->item_id, 0) ;
            setcookie('viewitem_'.$product->item_id, "viewed", time() + 60 * 60 * 24);

        }

    }

    public function OnBack($sender) {
        $product = Product::load($this->item_id);

        App::Redirect("\\App\\Modules\\Shop\\Pages\\Catalog\\Catalog", $product->cat_id);
    }

    public function OnAddAttributeRow(\Zippy\Html\DataList\DataRow $datarow) {
        $item = $datarow->getDataItem();
        $datarow->add(new Label("attrname", $item->attributename));
        $meashure = "";
        $nodata = "Немає даних";
        $yes = "Є";
        $no = "Немає";
        $value = $item->attributevalue;
        if ($item->attributetype == 2) {
            $meashure = $item->valueslist;
        }

        if ($item->attributetype == 1) {
            if ($item->attributevalue == '0') {
                $value = $no;
            }
            if ($item->attributevalue == '1') {
                $value = $yes;
            }
        }
        $value = $value . $meashure;
        if ($item->hasData() == false) {
            $value = $nodata;
        }
        
        if($item->attributetype == 5 && strpos($value,'http') === 0 ) {
            $link ="<a href=\"{$value}\" target=\"_blank\" >{$value}</a>";
            $datarow->add(new Label("attrvalue", $link,true));            
            return;
        }
        $datarow->add(new Label("attrvalue", $value));
    }

    //добавление в корзину
    public function OnBuy($sender) {
        $product = Product::load($this->item_id);
        $product->quantity = 1;
        \App\Modules\Shop\Basket::getBasket()->addProduct($product);

        $this->setSuccess("Товар доданий до кошика");
        $this->resetURL();
        //  App::RedirectURI('/pcat/' . $product->cat_id);
    }

    //добавить к форме сравнения
    public function OnAddCompare($sender) {
        $product = Product::load($this->item_id);
        $comparelist = \App\Modules\Shop\CompareList::getCompareList();
        if (false == $comparelist->addProduct($product)) {

            $this->setWarn('Додавати можна тільки товари з однакової категорії');
            return;
        }
        // App::RedirectURI('/pcat/'.$product->group_id)  ;
    }

    //добавать комментарий
    public function OnComment($sender) {

        $entercode = $this->formcomment->capchacode->getText();
        $capchacode = $this->formcomment->capcha->getCode();
        $this->formcomment->capchacode->setText('');
        if (strlen($entercode) == 0 || $entercode != $capchacode) {
            $this->setError("Невірний код капчі");

            return;
        }

        $comment = new \App\Modules\Shop\Entity\ProductComment();
        $comment->item_id = $this->item_id;
        $comment->author = $this->formcomment->starnick->getText();
        $comment->comment = $this->formcomment->starcomment->getText();
        $comment->rating = $this->formcomment->rating->getText();
        $comment->created = time();
        $comment->save();
        $this->formcomment->starnick->setText('');
        $this->formcomment->starcomment->setText('');
        $this->formcomment->rating->setText('0');
        $this->clist = ProductComment::findByProduct($this->item_id);
        $this->commentlist->Reload();

        $this->updateComments();
    }

    protected function beforeRender() {
        parent::beforeRender();

        if (\App\Modules\Shop\CompareList::getCompareList()->hasProsuct($this->item_id)) {
            $this->compare->setVisible(true);
            $this->addtocompare->setVisible(false);
        } else {
            $this->compare->setVisible(false);
            $this->addtocompare->setVisible(true);
        }
    }

    public function OnAddCommentRow(\Zippy\Html\DataList\DataRow $datarow) {
        $item = $datarow->getDataItem();
        if ($item->moderated == 1) {
            $item->comment = "Скасовано модератором";
        }
        $datarow->add(new Label("nick", $item->author));
        $datarow->add(new Label("comment", $item->comment));
        $datarow->add(new Label("created", \App\Helper::fdt($item->created)));
        $datarow->add(new TextInput("rate"))->setText($item->rating);
        $datarow->add(new ClickLink('deletecomment', $this, 'OnDeleteComment'))->setVisible(System::getUser()->userlogin == 'admin' && $item->moderated != 1);
    }

    //удалить коментарий
    public function OnDeleteComment($sender) {
        $comment = $sender->owner->getDataItem();
        $comment->moderated = 1;
        $comment->rating = 0;
        $comment->save();
        // App::$app->getResponse()->addJavaScript("window.location='#{$comment->comment_id}'", true);
        //\Application::getApplication()->Redirect('\\ZippyCMS\\Modules\\Articles\\Pages\\ArticleList');
        $this->clist = ProductComment::findByProduct($this->item_id);
        $this->commentlist->Reload();
        $this->updateComments();
    }

    private function updateComments() {
        $conn = \ZDB\DB::getConnect();

        $product = Product::load($this->item_id);

        $product->rating = $conn->GetOne("select sum(rating)/count(*) from shop_prod_comments where  item_id ={$this->item_id} and moderated <> 1 and  rating >0");
        $product->rating = round($product->rating);
        $product->comments = $conn->GetOne("select count(*) from shop_prod_comments where  item_id ={$this->item_id} and moderated <> 1");
        $product->save();
        $this->rated->setText($product->rating);
        $this->comments->setText("Вiгукiв({$product->comments})");
    }

    public function imglistOnRow($row) {
        $image = $row->getDataItem();

        $row->add(new \Zippy\Html\Link\BookmarkableLink('product_thumb'))->setValue("/loadshopimage.php?id={$image->image_id}&t=t");
        $row->product_thumb->setAttribute('href', "/loadshopimage.php?id={$image->image_id}");
    }

    public function varlistOnRow($row) {
        $vi = $row->getDataItem();
        $options = \App\System::getOptions('shop');
        $row->add(new \Zippy\Html\Link\BookmarkableLink('vattrvalue', "/sp/".$vi->item_id))->setValue($vi->attributevalue);
        $row->add(new Label("vprice", \App\Helper::fa($vi->price)));
        $row->add(new Label("vactionprice", \App\Helper::fa($vi->actionprice)))->setVisible(false);
        $row->add(new Label("vcurr", $options['currencyname'])) ;
        if ($vi->hasaction) {
            $row->vprice->setAttribute('style', 'font-size:smaller;text-decoration:line-through');
            $row->vactionprice->setAttribute('style', 'color:red; ');
            $row->vactionprice->setVisible(true);

        }


        if($vi->item_id == $this->item_id) {
            $row->vattrvalue->setAttribute('style', 'font-weight:bolder');
        }

    }

    public function reclistOnRow($row) {
        $item = $row->getDataItem();
        $row->add(new BookmarkableLink("rcimage", $item->getSEF()))->setValue('/loadshopimage.php?id=' . $item->image_id . "&t=t");
        $row->add(new BookmarkableLink("rcname", $item->getSEF()))->setValue($item->itemname);

    }
}
