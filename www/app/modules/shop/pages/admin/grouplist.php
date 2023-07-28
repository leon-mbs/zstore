<?php

namespace App\Modules\Shop\Pages\Admin;

use App\Application as App;
use App\Modules\Shop\Entity\Product;
use App\Modules\Shop\Entity\ProductAttribute;

use App\Modules\Shop\Helper;
use App\System;
use App\Entity\Category;
use Zippy\Binding\PropertyBinding as Bind;
use Zippy\Html\DataList\DataView;
use Zippy\Html\DataList\ArrayDataSource;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\TextArea;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Link\SubmitLink;
use Zippy\Html\Panel;

class GroupList extends \App\Pages\Base
{
    private $group      = null;
    public $_grouplist = array();
    public $attrlist   = array();
    private $mm;

    public function __construct() {
        parent::__construct();

        if (strpos(System::getUser()->modules, 'shop') === false && System::getUser()->rolename != 'admins') {
            System::setErrorMsg("Немає права доступу до сторінки");
            App::RedirectError();
            return;
        }

        $clist = Category::find(" cat_id in(select cat_id from items where disabled <>1) and detail not  like '%<noshop>1</noshop>%' ");

        $this->_grouplist = Category::findFullData($clist);

        usort($this->_grouplist, function ($a, $b) {
            return $a->full_name > $b->full_name;
        });

        $this->add(new DataView('grouplist', new ArrayDataSource($this, '_grouplist'), $this, 'OnGroupRow'));

        $this->grouplist->Reload();

        $attrpanel = $this->add(new Panel('attrpanel'));
        $attrpanel->add(new \Zippy\Html\DataList\DataView('attritem', new \Zippy\Html\DataList\ArrayDataSource(new Bind($this, 'attrlist')), $this, 'OnAttrRow'));

        //$this->UpdateAttrList();

        $attrpanel->add(new ClickLink('addattr'))->onClick($this, 'OnAddAttribute');
        $form = $attrpanel->add(new Form('attreditform'));
        $form->setVisible(false);
        $form->onSubmit($this, 'OnSaveAttribute');
        $form->add(new TextInput('attrname'));
        $form->add(new TextInput('attrid'));
        $form->add(new \Zippy\Html\Form\DropDownChoice('attrtype', Helper::getAttributeTypes()))->onChange($this, 'OnAttrType');
        $form->add(new Label('attrtypename'));
        $form->add(new Label('tt'))->setAttribute("title", "Атрибут 'Есть/Нет' указывает наличие или  отсутствие какойго либо параметра. Наприме FM-тюнер");

        $form->add(new Panel('attrvaluespanel'));
        $form->attrvaluespanel->add(new TextArea('attrvalues'));
        $form->attrvaluespanel->setVisible(false);

        $form->add(new Panel('meashurepanel'));
        $form->meashurepanel->add(new TextInput('meashure'));
        $form->meashurepanel->setVisible(false);
    }

    public function OnGroupRow($row) {
        $group = $row->getDataItem();
        $row->add(new ClickLink('groupname', $this, 'onGroup'))->setValue($group->full_name);
        if ($group->cat_id == $this->group->cat_id) {
            $row->setAttribute('class', 'table-success');
        }
    }

    public function onGroup($sender) {
        $this->group = $sender->getOwner()->getDataItem();

        $this->grouplist->Reload(false);
        $this->UpdateAttrList();
        $this->attrpanel->attreditform->setVisible(false);
    }

    //обновить атрибуты
    protected function UpdateAttrList() {
        $conn = \ZCL\DB\DB::getConnect();
        $this->mm = $conn->GetRow("select coalesce(max(ordern),0) as mm,coalesce(min(ordern),0) as mi from shop_attributes_order where  pg_id=" . $this->group->cat_id);

        $this->attrlist = Helper::getProductAttributeListByGroup($this->group->cat_id);
        $this->attrpanel->attritem->Reload();
    }

    public function OnAttrRow(\Zippy\Html\DataList\DataRow $datarow) {
        $item = $datarow->getDataItem();
        $datarow->add(new Label("itemname", $item->attributename));
        $attrlist = Helper::getAttributeTypes();
        $datarow->add(new Label("itemtype", $attrlist[$item->attributetype]));
        $datarow->add(new Label("itemvalues", $item->valueslist));
        $datarow->add(new ClickLink("itemdel", $this, 'OnDeleteAtribute'))->setVisible($this->group->cat_id == $item->cat_id);
        $datarow->add(new ClickLink("itemedit", $this, 'OnEditAtribute'))->setVisible($this->group->cat_id == $item->cat_id);
        $datarow->add(new ClickLink("orderup", $this, 'OnUp'))->setVisible($item->ordern > $this->mm["mi"]);
        $datarow->add(new ClickLink("orderdown", $this, 'OnDown'))->setVisible($item->ordern < $this->mm["mm"]);

        return $datarow;
    }

    public function OnAddAttribute($sender) {
        $form = $this->attrpanel->attreditform;
        $form->setVisible(true);
        $form->tt->setVisible(true);
        $form->attrtype->setVisible(true);
        $form->attrvaluespanel->setVisible(false);
        $form->attrvaluespanel->attrvalues->setValue('');
        $form->meashurepanel->setVisible(false);
        $form->meashurepanel->meashure->setValue('');
        $form->attrtypename->setVisible(false);
        $form->attrtype->setValue(1);
        $form->attrname->setValue("");
        $form->attrid->setValue("0");
    }

    //сменить  тип  атрибута
    public function OnAttrType($sender) {

        $type = $sender->getValue();
        $this->attrpanel->attreditform->attrvaluespanel->setVisible(false);
        $this->attrpanel->attreditform->meashurepanel->setVisible(false);
        if ($type == 2) {
            $this->attrpanel->attreditform->meashurepanel->setVisible(true);
        }
        if ($type == 3 || $type == 4) {
            $this->attrpanel->attreditform->attrvaluespanel->setVisible(true);
        }
        if ($type == 1) {
            $this->attrpanel->attreditform->tt->setAttribute("title", "Атрибут `Є/Немає` вказує на наявність або відсутність характеристики (наприклад, FM-тюнер).");
        }
        if ($type == 2) {
            $this->attrpanel->attreditform->tt->setAttribute("title", "Атрибут `Число` - числовий параметр (наприклад, ємність акумулятора). Перелік для фільтра відбору формується на основі діапазона значень атрибута, заданих для товарів.");
        }
        if ($type == 3) {
            $this->attrpanel->attreditform->tt->setAttribute("title", "Атрибут `Перелік` передбачений для переліку, з якого можна вибрати тільки одне значення (наприклад, колір). Задається переліком через кому");
        }
        if ($type == 4) {
            $this->attrpanel->attreditform->tt->setAttribute("title", "Атрибут `Набір` передбачений для переліку, з якого можна вибрати кілька значень (наприклад, діапазони прийому сигнала). Задається переліком через кому. ");
        }
        if ($type == 5) {
            $this->attrpanel->attreditform->tt->setAttribute("title", "Атрибут 'Строка'- просто текстовый параметр (например тип процессора). Значени не  используется в фильтре. ");
        }
    }

    public function OnEditAtribute($sender) {
        $item = $sender->getOwner()->getDataItem();

        $form = $this->attrpanel->attreditform;
        $form->setVisible(true);
        $form->attrid->setValue($item->attribute_id);
        $form->attrname->setValue($item->attributename);
        $form->meashurepanel->meashure->setValue($item->valueslist);
        $form->attrvaluespanel->attrvalues->setValue($item->valueslist);

        $form->attrtype->setVisible(false);
        $form->tt->setVisible(false);
        $form->attrvaluespanel->setVisible(false);
        $form->meashurepanel->setVisible(false);
        $form->attrtypename->setVisible(true);

        $attrlist = Helper::getAttributeTypes();

        $form->attrtypename->setText($attrlist[$item->attributetype]);

        if ($item->attributetype == 2) {
            $form->meashurepanel->setVisible(true);
        }
        if ($item->attributetype == 3 || $item->attributetype == 4) {
            $form->attrvaluespanel->setVisible(true);
        }

    }

    public function OnSaveAttribute($sender) {
        $form = $this->attrpanel->attreditform;
        $attrid = $form->attrid->getText();

        if ($attrid == "0") {
            $attr = new ProductAttribute();
            $attr->cat_id = $this->group->cat_id;
            $attr->attributetype = $form->attrtype->getValue();
        } else {
            $attr = ProductAttribute::load($attrid);
        }
        $attr->attributename = $form->attrname->getText();

        if (strlen($attr->attributename) == 0) {
            $this->setError("Не введено назву");

            return;
        }
        if ($attr->attributetype == 2) {
            $attr->valueslist = $form->meashurepanel->meashure->getText();
        }
        if ($attr->attributetype == 3 || $attr->attributetype == 4) {
            $attr->valueslist = $form->attrvaluespanel->attrvalues->getText();

            $r = array();

            foreach(explode(',', trim($attr->valueslist)) as $l) {
                $l = trim($l) ;
                $l = trim($l) ;
                if(strlen($l) > 0) {
                    $r[] = $l ;
                }

            }


            $attr->valueslist = implode(",", $r);
        }

        $attr->Save();

        if ($attrid == "0") {
            $conn = \ZCL\DB\DB::getConnect();
            $no = $conn->GetOne("select coalesce(max(ordern),0)+1 from shop_attributes_order");
            $conn->Execute("insert into shop_attributes_order (pg_id,attr_id,ordern)values({$attr->cat_id},{$attr->attribute_id},{$no} )");
        }

        $this->UpdateAttrList();

        $form->setVisible(false);
    }

    public function OnUp($sender) {
        $a1 = $sender->getOwner()->getDataItem();

        //предыдущий
        $a2 = ProductAttribute::getFirst("cat_id={$this->group->cat_id} and ordern < {$a1->ordern}", "ordern desc");
        $conn = \ZCL\DB\DB::getConnect();
        $conn->Execute("update shop_attributes_order set ordern={$a2->ordern} where pg_id={$this->group->cat_id} and attr_id={$a1->attribute_id}");
        $conn->Execute("update shop_attributes_order set ordern={$a1->ordern} where pg_id={$this->group->cat_id} and attr_id={$a2->attribute_id}");

        $this->UpdateAttrList();
    }

    public function OnDown($sender) {
        $a1 = $sender->getOwner()->getDataItem();

        //следующий
        $a2 = ProductAttribute::getFirst("cat_id={$this->group->cat_id} and ordern > {$a1->ordern}", "ordern asc");
        $conn = \ZCL\DB\DB::getConnect();
        $conn->Execute("update shop_attributes_order set ordern={$a2->ordern} where pg_id={$this->group->cat_id} and attr_id={$a1->attribute_id}");
        $conn->Execute("update shop_attributes_order set ordern={$a1->ordern} where pg_id={$this->group->cat_id} and attr_id={$a2->attribute_id}");

        $this->UpdateAttrList();
    }

    public function OnDeleteAtribute($sender) {
        $id = $sender->getOwner()->getDataItem()->attribute_id;
        ProductAttribute::delete($id);
        $this->UpdateAttrList();
        $this->attrpanel->attreditform->setVisible(false);
    }

    public function beforeRender() {
        parent::beforeRender();

        $this->attrpanel->setVisible(false);
        if ($this->group instanceof \App\Entity\Category) {

            $this->attrpanel->setVisible(true);
        }
    }

}
