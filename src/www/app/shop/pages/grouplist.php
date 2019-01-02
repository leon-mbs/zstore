<?php

namespace App\Shop\Pages;

use \Zippy\Html\Panel;
use \Zippy\Html\Label;
use \Zippy\Binding\PropertyBinding as Bind;
use \Zippy\Html\Form\Form;
use \Zippy\Html\Form\TextInput;
use \Zippy\Html\Form\TextArea;
use \Zippy\Html\Form\CheckBox;
use \Zippy\Html\Link\SubmitLink;
use \Zippy\Html\Link\ClickLink;
use \ZCL\BT\Tree;
use \App\Shop\Entity\Product;
use \App\Shop\Entity\ProductGroup;
use \App\Shop\Entity\ProductAttribute;
use \App\Shop\Helper;

class GroupList extends \App\Pages\Base
{

    private $group = null, $rootgroup;
    public $attrlist = array();
    private $mm;

    public function __construct() {
        parent::__construct();
        if (false == \App\ACL::checkShowCat('GroupList'))
            return;



        $tree = $this->add(new Tree("tree"));
        $tree->onSelectNode($this, "onTree");

        $this->ReloadTree();

        $form = $this->add(new Form('newgroupform'));
        $form->add(new TextInput('newgroupname'));
        $form->add(new SubmitLink('newgroup'))->onClick($this, 'OnNewGroup');

        $form = $this->add(new Form('groupform'));


        $form->add(new TextInput('groupname'));
        $form->add(new SubmitLink('renamegroup'))->onClick($this, 'OnRenameGroup');
        $form->add(new SubmitLink('delgroup'))->onClick($this, 'OnDelGroup');
        $form->add(new SubmitLink('savegroup'))->onClick($this, 'OnSavePhoto');
        $form->add(new \Zippy\Html\Form\File('photo'));
        $form->add(new \Zippy\Html\Image('group_image', ''));

        $attrpanel = $this->add(new Panel('attrpanel'));
        $attrpanel->add(new \Zippy\Html\DataList\DataView('attritem', new \Zippy\Html\DataList\ArrayDataSource(new Bind($this, 'attrlist')), $this, 'OnAddRow'));

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
        $form->add(new CheckBox('showinlist'));

        $form->add(new Panel('attrvaluespanel'));
        $form->attrvaluespanel->add(new TextArea('attrvalues'));
        $form->attrvaluespanel->setVisible(false);

        $form->add(new Panel('meashurepanel'));
        $form->meashurepanel->add(new TextInput('meashure'));
        $form->meashurepanel->setVisible(false);
    }

    //загрузить дерево
    public function ReloadTree() {

        $this->tree->removeNodes();

        $this->rootgroup = new ProductGroup();
        $this->rootgroup->group_id = PHP_INT_MAX;
        $this->rootgroup->groupname = "//";

        $root = new \ZCL\BT\TreeNode("//", PHP_INT_MAX);
        $this->tree->addNode($root);

        $itemlist = ProductGroup::find("", "mpath,groupname");
        $nodelist = array();

        foreach ($itemlist as $item) {
            $node = new \ZCL\BT\TreeNode($item->groupname, $item->group_id);
            $parentnode = @$nodelist[$item->parent_id];
            if ($item->parent_id == 0)
                $parentnode = $root;

            $this->tree->addNode($node, $parentnode);

            $nodelist[$item->group_id] = $node;
        }
    }

    //клик по  узлу
    public function onTree($sender, $id) {
        $nodeid = $this->tree->selectedNodeId();
        if ($nodeid == -1) {
            $this->group = null;
            return;
        }
        if ($nodeid == PHP_INT_MAX) {
            $this->group = $this->rootgroup;
            return;
        }
        $this->group = ProductGroup::load($nodeid);
        $this->groupform->groupname->setText($this->group->groupname);

        $this->groupform->group_image->setUrl('/assets/images/noimage.jpg');
        if ($this->group->image_id > 0) {
            $this->groupform->group_image->setUrl('/loadimage.php?id=' . $this->group->image_id);
        }
        $this->UpdateAttrList();
    }

    public function OnNewGroup($sender) {
        $this->group = new ProductGroup();
        $this->group->groupname = $this->newgroupform->newgroupname->getText();
        $this->group->parent_id = $this->tree->selectedNodeId();
        if ($this->group->parent_id == $this->rootgroup->group_id) {
            $this->group->parent_id = 0;
        } else {
            $pcnt = Product::findCnt("group_id=" . $this->group->parent_id);
            if ($pcnt > 0) {
                $this->setError('Нльзя добавить дочернюю в  группу с товарами');
                return;
            }
        }
        $this->group->save();

        $this->newgroupform->newgroupname->setText('');
        $this->ReloadTree();
        $this->tree->selectedNodeId($this->group->group_id);
        $this->onTree($this->tree, 0);
    }

    public function OnRenameGroup($sender) {
        $newname = $this->groupform->groupname->getText();

        if ($this->group->groupname == $newname) {
            return;
        }

        $this->group->groupname = $newname;
        $this->group->save();
        $this->ReloadTree();
    }

    public function OnDelGroup($sender) {
        $pcnt = Product::findCnt("group_id=" . $this->group->group_id);
        if ($pcnt > 0) {
            $this->setError('Граппа  с товарами!');
            return;
        }
        ProductGroup::delete($this->group->group_id);
        $this->group = null;
        $this->ReloadTree();
    }

    public function OnSavePhoto($sender) {


        $filedata = $this->getComponent('photo')->getFile();
        if (strlen($filedata["tmp_name"]) > 0) {
            $imagedata = getimagesize($filedata["tmp_name"]);

            if (preg_match('/(gif|png|jpeg)$/', $imagedata['mime']) == 0) {
                $this->setError('Невірний формат');
                return;
            }

            if ($imagedata[0] * $imagedata[1] > 1000000) {
                $this->setError('Шлишком большой размер изображения');
                return;
            }
            $r = ((double) $imagedata[0]) / $imagedata[1];
            if ($r > 1.1 || $r < 0.9) {
                $this->setError('Изображение должно  быть примерно квадратным');
                return;
            }
            $th = new \JBZoo\Image\Image($filedata['tmp_name']);
            $th = $th->resize(256, 256);

            $image = new \App\Entity\Image();
            $image->content = file_get_contents($filedata['tmp_name']);
            $image->thumb = $th->getBinary();

            $image->mime = $imagedata['mime'];
            $image->save();
            $this->group->image_id = $image->image_id;
            $this->group->save();
            $this->groupform->group_image->setUrl('/loadimage.php?id=' . $this->group->image_id);
        }
    }

    //обновить атрибуты
    protected function UpdateAttrList() {
        $conn = \ZCL\DB\DB::getConnect();
        $this->mm = $conn->GetRow("select coalesce(max(ordern),0) as mm,coalesce(min(ordern),0) as mi from shop_attributes_order where  pg_id=" . $this->group->group_id);

        $this->attrlist = Helper::getProductAttributeListByGroup($this->group->group_id);
        $this->attrpanel->attritem->Reload();
    }

    public function OnAddRow(\Zippy\Html\DataList\DataRow $datarow) {
        $item = $datarow->getDataItem();
        $datarow->add(new Label("itemname", $item->attributename));
        $attrlist = Helper::getAttributeTypes();
        $datarow->add(new Label("itemtype", $attrlist[$item->attributetype]));
        $datarow->add(new Label("itemvalues", $item->valueslist));
        $datarow->add(new ClickLink("itemdel", $this, 'OnDeleteAtribute'))->setVisible($this->group->group_id == $item->group_id);
        $datarow->add(new ClickLink("itemedit", $this, 'OnEditAtribute'))->setVisible($this->group->group_id == $item->group_id);
        $datarow->add(new ClickLink("orderup", $this, 'OnUp'))->setVisible($item->ordern > $this->mm["mi"]);
        $datarow->add(new ClickLink("orderdown", $this, 'OnDown'))->setVisible($item->ordern < $this->mm["mm"]);

        return $datarow;
    }

    public function OnAddAttribute($sender) {
        $form = $this->attrpanel->attreditform;
        $form->setVisible(true);
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
            $this->attrpanel->attreditform->tt->setAttribute("title", "Атрибут 'Ксть/Нет' указывает на  наличие или отстствие характеристики. Например FM-тюнер");
        }
        if ($type == 2) {
            $this->attrpanel->attreditform->tt->setAttribute("title", "Атрибут 'Число' - числовой параметр (напрмер емкость акумулятора). Список для для фильтра отбора формируется    на осноании диапазона значений атрибутв заданых для товароыв.");
        }
        if ($type == 3) {
            $this->attrpanel->attreditform->tt->setAttribute("title", "Атрибут 'Список' предназначен для перечня из которого можно выбрать только одно значение. Например цвет. Задается списком через запятую");
        }
        if ($type == 4) {
            $this->attrpanel->attreditform->tt->setAttribute("title", "Атрибут 'Набор' предназначен для перечня из которого можно выбрать несколько значений.. Например диапазоны приема сигнала. Задается списком через запятую. ");
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
        $form->showinlist->setChecked($item->showinlist > 0);
    }

    public function OnSaveAttribute($sender) {
        $form = $this->attrpanel->attreditform;
        $attrid = $form->attrid->getText();

        if ($attrid == "0") {
            $attr = new ProductAttribute();
            $attr->group_id = $this->group->group_id;
            $attr->attributetype = $form->attrtype->getValue();
        } else {
            $attr = ProductAttribute::load($attrid);
        }
        $attr->attributename = $form->attrname->getText();


        if (strlen($attr->attributename) == 0) {
            $this->setError("Введіть найменування!");

            return;
        }
        if ($attr->attributetype == 2) {
            $attr->valueslist = $form->meashurepanel->meashure->getText();
        }
        if ($attr->attributetype == 3 || $attr->attributetype == 4) {
            $attr->valueslist = $form->attrvaluespanel->attrvalues->getText();
            $attr->valueslist = preg_replace('/\s+/', " ", $attr->valueslist);
        }
        $attr->showinlist = $form->showinlist->isChecked() ? 1 : 0;

        $attr->Save();

        if ($attrid == "0") {
            $conn = \ZCL\DB\DB::getConnect();
            $no = $conn->GetOne("select coalesce(max(ordern),0)+1 from shop_attributes_order");
            $conn->Execute("insert into shop_attributes_order (pg_id,attr_id,ordern)values({$attr->group_id},{$attr->attribute_id},{$no} )");
        }

        $this->UpdateAttrList();

        $form->setVisible(false);
    }

    public function OnUp($sender) {
        $a1 = $sender->getOwner()->getDataItem();

        //предыдущий
        $a2 = ProductAttribute::getFirst("group_id={$this->group->group_id} and ordern < {$a1->ordern}", "ordern desc");
        $conn = \ZCL\DB\DB::getConnect();
        $conn->Execute("update shop_attributes_order set ordern={$a2->ordern} where pg_id={$this->group->group_id} and attr_id={$a1->attribute_id}");
        $conn->Execute("update shop_attributes_order set ordern={$a1->ordern} where pg_id={$this->group->group_id} and attr_id={$a2->attribute_id}");

        $this->UpdateAttrList();
    }

    public function OnDown($sender) {
        $a1 = $sender->getOwner()->getDataItem();

        //следующий
        $a2 = ProductAttribute::getFirst("group_id={$this->group->group_id} and ordern > {$a1->ordern}", "ordern asc");
        $conn = \ZCL\DB\DB::getConnect();
        $conn->Execute("update shop_attributes_order set ordern={$a2->ordern} where pg_id={$this->group->group_id} and attr_id={$a1->attribute_id}");
        $conn->Execute("update shop_attributes_order set ordern={$a1->ordern} where pg_id={$this->group->group_id} and attr_id={$a2->attribute_id}");

        $this->UpdateAttrList();
    }

    public function OnDeleteAtribute($sender) {
        $id = $sender->getOwner()->getDataItem()->attribute_id;
        ProductAttribute::delete($id);
        $this->UpdateAttrList();
        $this->attrpanel->attreditform->setVisible(false);
    }

    protected function beforeRender() {
        parent::beforeRender();

        $this->groupform->setVisible(false);
        $this->attrpanel->setVisible(false);
        if ($this->group instanceof ProductGroup) {
            if ($this->group->groupname != "//") {
                $this->groupform->setVisible(true);
                $this->attrpanel->setVisible(true);
            }
        }
    }

}
