<?php

namespace App\Shop\Pages;

use \Zippy\Html\Label;
use \Zippy\Binding\PropertyBinding;
use \Zippy\Html\Form\TextInput;
use \Zippy\Html\Form\TextArea;
use \Zippy\Binding\PropertyBinding as Bind;
use \App\Shop\Helper;
use \Zippy\Html\Image;
use \App\Shop\Entity\Product;
use \App\Shop\Entity\ProductComment;
use \Zippy\Html\Panel;
use \Zippy\Html\Link\RedirectLink;
use \Zippy\Html\Link\ClickLink;
use \Zippy\Html\Link\BookmarkableLink;
use \Zippy\Html\DataList\DataView;
use \App\System;
use \App\Application as App;

//детализация  по товару, отзывы
class ProductView extends Base
{

    public $msg, $attrlist, $clist;
    protected $product_id, $gotocomment;

    public function __construct($product_id = 0) {
        parent::__construct();

        $this->product_id = $product_id;
        $product = Product::load($product_id);
        if ($product == null) {
            App::Redirect404();
        }
        $this->add(new Label("breadcrumb", Helper::getBreadScrumbs($product->group_id), true));

        $this->_title = $product->productname;
        $this->_description = $product->description;
        //  $this->_keywords = $product->description;
        $this->add(new \Zippy\Html\Link\BookmarkableLink('product_image'))->setValue("/loadimage.php?id={$product->image_id}&t=t");
        $this->product_image->setAttribute('href', "/loadimage.php?id={$product->image_id}");

        $this->add(new Label('productname', $product->productname));
        $this->add(new Label('onstore'));
        $this->add(new \Zippy\Html\Label('manufacturername', $product->manufacturername))->SetVisible($product->manufacturer_id > 0);

        $this->add(new Label("topsold"))->setVisible($product->topsold == 1);
        $this->add(new Label("novelty"))->setVisible($product->novelty == 1);

        $this->add(new Label('price', $product->price));
        $this->add(new Label('oldprice', $product->oldprice))->setVisible($product->oldprice > 0);
        $this->add(new Label('description', $product->description));
        $this->add(new Label('fulldescription', $product->fulldescription));
        $this->add(new TextInput('rated'))->setText($product->rating);
        $this->add(new BookmarkableLink('comments'))->setValue("Отзывов({$product->comments})");

        $list = Helper::getAttributeValuesByProduct($product);
        $this->add(new \Zippy\Html\DataList\DataView('attributelist', new \Zippy\Html\DataList\ArrayDataSource($list), $this, 'OnAddAttributeRow'))->Reload();
        $this->add(new ClickLink('buy', $this, 'OnBuy'));
        $this->add(new ClickLink('addtocompare', $this, 'OnAddCompare'));
        $this->add(new RedirectLink('compare', "\\App\\Shop\\Pages\\Compare"))->setVisible(false);

        $form = $this->add(new \Zippy\Html\Form\Form('formcomment'));
        $form->onSubmit($this, 'OnComment');
        $form->add(new TextInput('nick'));
        $form->add(new TextInput('rating'));
        $form->add(new TextArea('comment'));
        $this->clist = ProductComment::findByProduct($product->product_id);
        $this->add(new \Zippy\Html\DataList\DataView('commentlist', new \Zippy\Html\DataList\ArrayDataSource(new PropertyBinding($this, 'clist')), $this, 'OnAddCommentRow'));
        $this->commentlist->setPageSize(25);
        $this->add(new \Zippy\Html\DataList\Pager("pag", $this->commentlist));
        $this->commentlist->Reload();

        if ($product->deleted == 1) {
            $this->onstore = "Снят с продажи";
            $this->buy->setVisible(false);
        } else {

            if (\App\Entity\Item::getQuantity($product->item_id) > 0) {
                $this->onstore->setText("В наличии");
                $this->buy->setValue("Купить");
            } else {
                $this->onstore->setText("Под заказ");
                $this->buy->setValue("Заказать");
            }
        }



        $recently = \App\Session::getSession()->recently;
        if (!is_array($recently)) {
            $recently = array();
        }
        $recently[$product->product_id] = $product->product_id;
        \App\Session::getSession()->recently = $recently;
    }

    public function OnAddAttributeRow(\Zippy\Html\DataList\DataRow $datarow) {
        $item = $datarow->getDataItem();
        $datarow->add(new Label("attrname", $item->attributename));
        $meashure = "";
        $value = $item->attributevalue;
        if ($item->attributetype == 2)
            $meashure = $item->valueslist;
        if ($item->attributetype == 1) {
            $value = $item->attributevalue == 1 ? "Есть" : "Нет";
        }
        $value = $value . $meashure;
        if ($item->attributevalue == '')
            $value = "Н/Д";
        $datarow->add(new Label("attrvalue", $value));
    }

    //добавление в корзину
    public function OnBuy($sender) {
        $product = Product::load($this->product_id);
        $product->quantity = 1;
        \App\Shop\Basket::getBasket()->addProduct($product);
        $this->setSuccess("Товар  добавлен  в   корзину");
        //$this->resetURL();
        App::RedirectURI('/pcat/' . $product->group_id);
    }

    //добавить к форме сравнения
    public function OnAddCompare($sender) {
        $product = Product::load($this->product_id);
        $comparelist = \App\Shop\CompareList::getCompareList();
        if (false == $comparelist->addProduct($product)) {
            $this->setWarn('Добавлять можно только товары с одинаковой категорией');
            return;
        }
        // App::RedirectURI('/pcat/'.$product->group_id)  ;
    }

    //добавать комментарий 
    public function OnComment($sender) {


        $comment = new \App\Shop\Entity\ProductComment();
        $comment->product_id = $this->product_id;
        $comment->author = $this->formcomment->nick->getText();
        $comment->comment = $this->formcomment->comment->getText();
        $comment->rating = $this->formcomment->rating->getText();
        $comment->created = time();
        $comment->Save();
        $this->formcomment->nick->setText('');
        $this->formcomment->comment->setText('');
        $this->formcomment->rating->setText('0');
        $this->clist = ProductComment::findByProduct($this->product_id);
        $this->commentlist->Reload();



        $this->gotocomment = true;
        $this->updateComments();
    }

    protected function beforeRender() {
        parent::beforeRender();

        if ($this->gotocomment == true) {
            App::addJavaScript("openComments();", true);
            $this->gotocomment = false;
        }
        if (\App\Shop\CompareList::getCompareList()->hasProsuct($this->product_id)) {
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
            $item->comment = "Отменено  модератором";
        }
        $datarow->add(new Label("nick", $item->author));
        $datarow->add(new Label("comment", $item->comment));
        $datarow->add(new Label("created", date('Y-m-d H:i', $item->created)));
        $datarow->add(new TextInput("rate"))->setText($item->rating);
        $datarow->add(new ClickLink('deletecomment', $this, 'OnDeleteComment'))->SetVisible(System::getUser()->userlogin == "admin" && $item->moderated != 1);
    }

    //удалить коментарий
    public function OnDeleteComment($sender) {
        $comment = $sender->owner->getDataItem();
        $comment->moderated = 1;
        $comment->rating = 0;
        $comment->Save();
        // App::$app->getResponse()->addJavaScript("window.location='#{$comment->comment_id}'", true);
        //\Application::getApplication()->Redirect('\\ZippyCMS\\Modules\\Articles\\Pages\\ArticleList');
        $this->clist = ProductComment::findByProduct($this->product_id);
        $this->commentlist->Reload();
        $this->updateComments();
    }

    private function updateComments() {
        $conn = \ZDB\DB::getConnect();

        $product = Product::load($this->product_id);

        $product->rating = $conn->GetOne("select sum(rating)/count(*) from `shop_prod_comments`where  product_id ={$this->product_id} and moderated <> 1 and  rating >0");
        $product->rating = round($product->rating);
        $product->comments = $conn->GetOne("select count(*) from `shop_prod_comments`where  product_id ={$this->product_id} and moderated <> 1");
        $product->save();
        $this->rated->setText($product->rating);
        $this->comments->setValue("Отзывов({$product->comments})");
    }

}
