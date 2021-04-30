<?php

namespace App\Modules\Tecdoc;

use App\Entity\Item;
use App\Helper as H;
use App\System;
use App\Application as App;
use Zippy\Html\DataList\DataView;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\Button;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Panel;
use Zippy\Html\Label;
use Zippy\Html\Form\Date;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\DataList\ArrayDataSource;
use \Zippy\Binding\PropertyBinding as Bind;

class Search extends \App\Pages\Base
{

    public $_ds   = array();
    public $_card = array();

    public function __construct() {
        parent::__construct();

        if (strpos(System::getUser()->modules, 'tecdoc') === false && System::getUser()->rolename != 'admins') {
            System::setErrorMsg(\App\Helper::l('noaccesstopage'));

            App::RedirectError();
            return;
        }

        $this->add(new Panel('tpanel'));

        $this->tpanel->add(new ClickLink('tabl', $this, 'onTab'));
        $this->tpanel->add(new ClickLink('tabc', $this, 'onTab'));
        $this->tpanel->add(new ClickLink('tabb', $this, 'onTab'));

        $tablist = $this->tpanel->add(new Panel('tablist'));
        $tabcode = $this->tpanel->add(new Panel('tabcode'));
        $tabbarcode = $this->tpanel->add(new Panel('tabbarcode'));

        $tablist->add(new Form('search1form'));

        $tablist->search1form->add(new DropDownChoice('stype', array('passenger' => 'Авто', 'commercial' => 'Грузовик', 'motorbike' => 'Мотоцикл'), 'passenger'))->onChange($this, 'onType');
        $tablist->search1form->add(new DropDownChoice('sbrand', array(), 0))->onChange($this, 'onBrand');
        $tablist->search1form->add(new DropDownChoice('smodel', array(), 0))->onChange($this, 'onModel');
        $tablist->search1form->add(new DropDownChoice('smodif', array(), 0))->onChange($this, 'onModif');
        $tablist->search1form->add(new Label('modifdetail'));

        $tablist->add(new \ZCL\BT\Tree("tree"))->onSelectNode($this, "onTree");

        $tabcode->add(new Form('search2form'))->onSubmit($this, 'onSearch1');
        $tabcode->search2form->add(new TextInput('searchcode'));
        $tabcode->search2form->add(new TextInput('searchbrand'));

        $tabbarcode->add(new Form('search3form'))->onSubmit($this, 'onSearch2');
        $tabbarcode->search3form->add(new TextInput('searchbarcode'));

        $this->add(new Panel('tlist'))->setVisible(false);
        $this->tlist->add(new ClickLink('back'))->onClick($this, 'onBack');

        $this->tlist->add(new DataView('itemlist', new ArrayDataSource(new Bind($this, "_ds")), $this, 'listOnRow'));
        $this->tlist->itemlist->setSelectedClass('table-success');
        $this->tlist->itemlist->setPageSize(H::getPG(10));
        $this->tlist->add(new \Zippy\Html\DataList\Paginator('pag', $this->tlist->itemlist));

        $this->add(new Panel('tview'))->setVisible(false);

        //Корзина
        $this->_card = \App\Session::getSession()->clipboard;
        if (!is_array($this->_card)) {
            $this->_card = array();
        }
        $this->tlist->add(new Panel('cartpan'))->setVisible(count($this->_card) > 0);
        $this->tlist->cartpan->add(new DataView('cardlist', new ArrayDataSource(new Bind($this, "_card")), $this, 'cardOnRow'));
        $this->tlist->cartpan->cardlist->Reload();
        $this->tlist->cartpan->add(new ClickLink('copycart', $this, 'OnCopy'));

        $this->onTab($this->tpanel->tabl);
        $this->onType($tablist->search1form->stype);
    }

    public function onTab($sender) {

        $this->_tvars['tablbadge'] = $sender->id == 'tabl' ? "badge badge-primary  badge-pill " : "badge badge-light  badge-pill  ";
        $this->_tvars['tabcbadge'] = $sender->id == 'tabc' ? "badge badge-primary  badge-pill " : "badge badge-light  badge-pill  ";;
        $this->_tvars['tabbbadge'] = $sender->id == 'tabb' ? "badge badge-primary  badge-pill " : "badge badge-light  badge-pill  ";;

        $this->tpanel->tablist->setVisible($sender->id == 'tabl');
        $this->tpanel->tabcode->setVisible($sender->id == 'tabc');
        $this->tpanel->tabbarcode->setVisible($sender->id == 'tabb');

        if ($sender->id == 'tabc') {
            $api = new APIHelper();

            $ret = $api->getAllBrands();

            if ($ret['success'] == true) {
                foreach ($ret['data'] as $name) {
                    $this->_tvars['brandslist'][] = array('bname' => $name);
                }
            } else {
                $this->setError($ret['error']);
            }
        }

        $this->tlist->setVisible(false);
        $this->tview->setVisible(false);
    }

    public function onType($sender) {
        $api = new APIHelper($this->tpanel->tablist->search1form->stype->getValue());

        $ret = $api->getManufacturers();
        if ($ret['success'] == true) {
            $this->tpanel->tablist->search1form->sbrand->setOptionList($ret['data']);
        } else {
            $this->setError($ret['error']);
        }

        $this->tpanel->tablist->search1form->smodel->setOptionList(array());
        $this->tpanel->tablist->search1form->smodif->setOptionList(array());
        $this->tpanel->tablist->search1form->modifdetail->setText('');
        $this->tpanel->tablist->tree->removeNodes();
    }

    public function onBrand($sender) {

        $api = new APIHelper($this->tpanel->tablist->search1form->stype->getValue());

        $ret = $api->getModels($this->tpanel->tablist->search1form->sbrand->getValue());

        if ($ret['success'] == true) {
            $this->tpanel->tablist->search1form->smodel->setOptionList($ret['data']);
        } else {
            $this->setError($ret['error']);
        }


        $this->tpanel->tablist->search1form->smodif->setOptionList(array());
        $this->tpanel->tablist->search1form->modifdetail->setText('');
        $this->tpanel->tablist->tree->removeNodes();
    }

    public function onModel($sender) {

        $api = new APIHelper($this->tpanel->tablist->search1form->stype->getValue());

        $ret = $api->getModifs($this->tpanel->tablist->search1form->smodel->getValue());

        if ($ret['success'] == true) {
            $this->tpanel->tablist->search1form->smodif->setOptionList($ret['data']);
        } else {
            $this->setError($ret['error']);
        }


        $this->tpanel->tablist->search1form->modifdetail->setText('');
        $this->tpanel->tablist->tree->removeNodes();
    }

    public function onModif($sender) {
        $api = new APIHelper($this->tpanel->tablist->search1form->stype->getValue());

        $ret = $api->getModifDetail($this->tpanel->tablist->search1form->smodif->getValue());

        if ($ret['success'] != true) {
            $this->setError($ret['error']);
            return;
        }

        $t = "<table  style='font-size:smaller;'>";
        foreach ($ret['data'] as $k => $v) {

            if ($k == 'ConstructionInterval') {
                $t = $t . "<tr><td>Годы выпуска</td><td>{$v}</td></tr>";
            }
            if ($k == 'BodyType') {
                $t = $t . "<tr><td>Кузов</td><td>{$v}</td></tr>";
            }
            if ($k == 'DriveType') {
                $t = $t . "<tr><td>Привод</td><td>{$v}</td></tr>";
            }
            if ($k == 'EngineCode') {
                $t = $t . "<tr><td>Код двигателя</td><td>{$v}</td></tr>";
            }
            if ($k == 'EngineType') {
                $t = $t . "<tr><td>Двигатель</td><td>{$v}</td></tr>";
            }
            if ($k == 'NumberOfCylinders') {
                $t = $t . "<tr><td>Цилиндров</td><td>{$v}</td></tr>";
            }
            if ($k == 'Capacity') {
                $t = $t . "<tr><td>Обьем</td><td>{$v}</td></tr>";
            }
            if ($k == 'Power') {
                $t = $t . "<tr><td>Мощность</td><td>{$v}</td></tr>";
            }
            if ($k == 'BrakeSystem') {
                $t = $t . "<tr><td>Тормоз</td><td>{$v}</td></tr>";
            }
            if ($k == 'FuelType') {
                $t = $t . "<tr><td>Топливо</td><td>{$v}</td></tr>";
            }
            if ($k == 'PlatformType') {
                $t = $t . "<tr><td>Тип</td><td>{$v}</td></tr>";
            }
            if ($k == 'Tonnage') {
                $t = $t . "<tr><td>Тоннаж</td><td>{$v}</td></tr>";
            }
            //$t = $t ."<tr><td>{$k}</td><td>{$v}</td></tr>" ;
        }
        $t .= "</table>";
        $this->tpanel->tablist->search1form->modifdetail->setText($t, true);

        $tlist = array();
        $this->tpanel->tablist->tree->removeNodes();
        $this->tpanel->tablist->tree->selectedNodeId(-1);

        $root = new \ZCL\BT\TreeNode('//', 0);
        $tlist[0] = $root;
        $this->tpanel->tablist->tree->addNode($root);

        $ret = $api->getTree($this->tpanel->tablist->search1form->smodif->getValue());

        $list = array();
        foreach ($ret['data'] as $row) {
            $item = new \App\DataItem();
            $item->intree = false;
            $item->id = $row['id'];
            $item->parentId = $row['parentId'];
            $item->description = $row['description'];
            $list[$item->id] = $item;
        }


        while(true) {
            $wasadded = false;
            foreach ($list as $n) {
                if ($n->intree) {
                    continue;
                }

                if (array_key_exists($n->parentId, $list) == false) {
                    //если  вообще  нет парента
                    $node = new \ZCL\BT\TreeNode($n->description, $n->id);
                    $this->tpanel->tablist->tree->addNode($node, $root);
                    $n->intree = true;
                    $wasadded = true;
                    $tlist[$n->id] = $node;
                    continue;
                }
                if (array_key_exists($n->parentId, $tlist) == true) {
                    //если  парент  вставлен
                    $node = new \ZCL\BT\TreeNode($n->description, $n->id);
                    $this->tpanel->tablist->tree->addNode($node, $tlist[$n->parentId]);
                    $n->intree = true;
                    $wasadded = true;
                    $tlist[$n->id] = $node;
                    continue;
                }
            }
            if ($wasadded == false) {
                break;
            }
        }
    }

    public function onTree($sender, $id) {

        foreach ($sender->nodes as $n) {
            if ($n->zippyid == $id) {
                if (count($n->children) > 0) {
                    return; //если  есть дочерние не  выбираем
                };
            }
        }
        if ($id == -1) {
            return;
        }
        $api = new APIHelper($this->tpanel->tablist->search1form->stype->getValue());

        $ret = $api->searchByCategory($id, $this->tpanel->tablist->search1form->smodif->getValue());

        if ($ret['success'] != true) {
            $this->setError($ret['error']);
            return;
        }

        $this->_ds = array();
        foreach ($ret['data'] as $row) {
            $item = new \App\DataItem();
            $item->part_number = $row['part_number'];
            $item->supplier_name = $row['supplier_name'];
            $item->product_name = $row['product_name'];
            $item->brand_id = $row['brand_id'];
            $this->_ds[] = $item;
        }
        $this->OnItemList();

        if (count($this->_ds) > 0) {

            $this->tpanel->setVisible(false);
            $this->tlist->setVisible(true);
        } else {
            $this->setWarn('notfound');
        }
        $this->tview->setVisible(false);
    }

    public function onSearch1($sender) {

        $code = trim($sender->searchcode->getText());
        $brand = trim($sender->searchbrand->getText());

        $api = new APIHelper($this->tpanel->tablist->search1form->stype->getValue());

        $ret = $api->searchByBrandAndCode($code, $brand);

        if ($ret['success'] != true) {
            $this->setError($ret['error']);
            return;
        }
        $this->_ds = array();
        foreach ($ret['data'] as $row) {
            $item = new \App\DataItem();
            $item->part_number = $row['part_number'];
            $item->supplier_name = $row['supplier_name'];
            $item->product_name = $row['product_name'];
            $item->brand_id = $row['brand_id'];
            $this->_ds[] = $item;
        }
        $this->OnItemList();

        if (count($this->_ds) > 0) {
            $this->tpanel->setVisible(false);

            $this->tlist->setVisible(true);
        } else {
            $this->setWarn('notfound');
        }
        $this->tview->setVisible(false);
    }

    public function onSearch2($sender) {

        $code = trim($sender->searchbarcode->getText());
        $api = new APIHelper($this->tpanel->tablist->search1form->stype->getValue());

        $ret = $api->searchByBarCode($code);

        if ($ret['success'] != true) {
            $this->setError($ret['error']);
            return;
        }
        $this->_ds = array();
        foreach ($ret['data'] as $row) {
            $item = new \App\DataItem();
            $item->part_number = $row['part_number'];
            $item->supplier_name = $row['supplier_name'];
            $item->product_name = $row['product_name'];
            $item->brand_id = $row['brand_id'];
            $this->_ds[] = $item;
        }
        $this->OnItemList();

        if (count($this->_ds) > 0) {
            $this->tpanel->setVisible(false);
            $this->tlist->setVisible(true);
        } else {
            $this->setWarn('notfound');
        }
        $this->tview->setVisible(false);
    }

    public function onBack($sender) {
        $this->tpanel->setVisible(true);
        $this->tlist->setVisible(false);
        $this->tview->setVisible(false);
    }

    private function OnItemList() {
        $modules = System::getOptions("modules");

        $items = array();
        foreach ($this->_ds as $it) {
            $it->qty = '';

            $item = Item::getFirst("manufacturer=" . Item::qstr($it->supplier_name) . " and item_code=" . Item::qstr($it->part_number));
            if ($item != null) {
                $it->qty = $item->getQuantity($modules['td_store']);
                $it->price = $item->getPrice($modules['td_pricetype'], $modules['td_store']);
            }


            $items[] = $it;
        }

        usort($items, function($a, $b) {
            return $a->qty < $b->qty;
        });

        $this->_ds = $items;
        $this->tlist->itemlist->Reload();
    }

    public function listOnRow($row) {
        $item = $row->getDataItem();
        $row->add(new Label("lbrand", $item->supplier_name));
        $row->add(new Label("lcode", $item->part_number));
        $row->add(new Label("lname", $item->product_name));
        $item = Item::getFirst("manufacturer=" . Item::qstr($item->supplier_name) . " and item_code=" . Item::qstr($item->part_number));

        $row->add(new Label("qty"))->setVisible($item instanceof Item);
        $row->add(new Label("price"))->setVisible($item instanceof Item);
        if ($item instanceof Item) {
            $modules = System::getOptions("modules");
            $row->qty->setText(H::fqty($item->getQuantity($modules['td_store'])));
            $row->price->setText(H::fa($item->getPrice($modules['td_pricetype'], $modules['td_store'])));
        }
        $row->add(new ClickLink('show'))->onClick($this, 'showOnClick');
        $row->add(new ClickLink('cart', $this, "OnCart")); //->setVisible($item->quantity>0);
    }

    public function showOnClick($sender) {
        $modules = System::getOptions("modules");

        $this->tview->setVisible(true);
        $this->tlist->itemlist->setSelectedRow($sender->getOwner());
        $this->tlist->itemlist->Reload(false);
        $part = $sender->getOwner()->getDataItem();

        $api = new APIHelper($this->tpanel->tablist->search1form->stype->getValue());

        $ret = $api->getAttributes($part->part_number, $part->brand_id);

        if ($ret['success'] != true) {
            $this->setError($ret['error']);
            return;
        }
        $list = $ret['data'];

        $this->_tvars['isattr'] = count($list) > 0;
        $this->_tvars['attr'] = array();
        foreach ($list as $k => $v) {
            $this->_tvars['attr'][] = array('k' => $k, 'v' => $v);
        }

        $this->_tvars['isimage'] = false;

        $ret = $api->getImage($part->part_number, $part->brand_id);
        if ($ret['success'] != true) {
            // $this->setError($ret['error']);
            // return;
        }

        if (strlen($ret['data']) > 0) {
            $this->_tvars['isimage'] = true;

            //$this->_tvars['imagepath'] = "/proxy.php?im=" . $ret['data'];
            $this->_tvars['imagepath'] = $ret['data'];
        }

        //Оригинальные  номера
        $this->_tvars['isoem'] = false;
        $this->_tvars['oem'] = array();
        $ret = $api->getOemNumbers($part->part_number, $part->brand_id);
        if ($ret['success'] != true) {
            $this->setError($ret['error']);
            return;
        }
        if (count($ret['data']) > 0) {
            $this->_tvars['isoem'] = true;
            foreach ($ret['data'] as $row) {

                $this->_tvars['oem'][] = array('oemnum' => $row['OENbr'], 'manufacturer_name' => $row['manufacturer_name']);
            }
        }


        //Замены
        $this->_tvars['isrep'] = false;
        $this->_tvars['rep'] = array();

        $ret = $api->getReplace($part->part_number, $part->brand_id);
        if ($ret['success'] != true) {
            $this->setError($ret['error']);
            return;
        }

        if (count($ret['data']) > 0) {
            $this->_tvars['isrep'] = true;
            foreach ($ret['data'] as $r) {
                $q = '';
                $p = '';

                $item = Item::getFirst("manufacturer=" . Item::qstr($r['supplier']) . " and item_code=" . Item::qstr($r['replacenbr']));

                if ($item instanceof Item) {
                    $modules = System::getOptions("modules");
                    $q = H::fqty($item->getQuantity($modules['td_store']));
                    $p = H::fa($item->getPrice($modules['td_pricetype'], $modules['td_store']));
                    $q = "<span  class=\"badge badge-info badge-pill\">{$q}</span>";
                    $p = "<span  class=\"badge badge-info badge-pill\">{$p}</span>";
                }

                $this->_tvars['rep'][] = array('sup' => $r['supplier'], 'num' => $r['replacenbr'], 'q' => $q, 'p' => $p);
            }
            usort($this->_tvars['rep'], function($a, $b) {
                return $a['q'] < $b['q'];
            });
        }


        //Составные части
        $this->_tvars['ispart'] = false;
        $this->_tvars['part'] = array();

        $ret = $api->getArtParts($part->part_number, $part->brand_id);
        if ($ret['success'] != true) {
            $this->setError($ret['error']);
            return;
        }


        if (count($ret['data']) > 0) {
            $this->_tvars['ispart'] = true;
            foreach ($ret['data'] as $r) {
                $this->_tvars['part'][] = array('Brand' => $r['Brand'], 'partnumber' => $r['partnumber'], 'Quantity' => $r['Quantity']);
            }
        }


        //Аналоги
        $this->_tvars['crosslist'] = array();
        $this->_tvars['iscross'] = false;
        $this->_tvars['cross'] = array();

        $ret = $api->getArtCross($part->part_number, $part->brand_id);
        if ($ret['success'] != true) {
            $this->setError($ret['error']);
            return;
        }

        if (count($ret['data']) > 0) {
            $this->_tvars['iscross'] = true;
            foreach ($ret['data'] as $c) {
                $item = Item::getFirst("manufacturer=" . Item::qstr($c['description']) . " and item_code=" . Item::qstr($c['crossnumber']));
                $q = '';
                $p = '';

                if ($item instanceof Item) {
                    $modules = System::getOptions("modules");
                    $q = H::fqty($item->getQuantity($modules['td_store']));
                    $p = H::fa($item->getPrice($modules['td_pricetype'], $modules['td_store']));
                    $q = "<span  class=\"badge badge-info badge-pill\">{$q}</span>";
                    $p = "<span  class=\"badge badge-info badge-pill\">{$p}</span>";
                }


                $this->_tvars['crosslist'][] = array('desc' => $c['description'], 'cross' => $c['crossnumber'], 'q' => $q, 'p' => $p);
            }
            usort($this->_tvars['crosslist'], function($a, $b) {
                return $a['q'] < $b['q'];
            });
        }

        //Применимость
        $this->_tvars['isapp'] = false;
        $this->_tvars['applist'] = array();

        $ret = $api->getArtVehicles($part->part_number, $part->brand_id);
        if ($ret['success'] != true) {
            $this->setError($ret['error']);
            return;
        }

        if (count($ret['data']) > 0) {
            $this->_tvars['isapp'] = true;
            foreach ($ret['data'] as $r) {
                $this->_tvars['applist'][] = array('years' => $r['years'], 'desc' => $r['desc']);
            }
        }
    }

    public function cardOnRow($row) {
        $item = $row->getDataItem();
        $row->add(new Label('carditem', $item->itemname));
        $row->add(new Label('carcode', $item->item_code));
        $row->add(new Label('cardbrand', $item->manufacturer));
        $row->add(new ClickLink('delcard', $this, "OnDelCart"));
    }

    public function OnCart($sender) {
        $item = $sender->getOwner()->getDataItem();
        $item->itemname = $item->product_name;
        $item->manufacturer = $item->brand;
        $item->item_code = $item->part_number;

        if ($item->item_id > 0) {
            $item->item_code = $item->product_name;

            $this->_card[$item->item_id] = $item;
        } else {
            $_item = Item::getFirst("item_code=" . Item::qstr($item->part_number) . " and manufacturer=" . Item::qstr($item->brand));
            if ($_item instanceof Item) {
                $item->item_id = $_item->item_id;
            } else {
                $_item = new Item();
                $_item->itemname = $item->product_name;

                $_item->item_code = $item->part_number;
                $_item->manufacturer = $item->brand;

                $_item->save();
                $item->item_id = $_item->item_id;
                $item->quantity = 1;
                $this->_card[$item->item_id] = $item;
            }
        }

        $this->_card[$item->item_id] = $item;
        $this->tlist->cartpan->cardlist->Reload();
        $this->tlist->cartpan->setVisible(count($this->_card) > 0);
    }

    public function OnDelCart($sender) {
        $item = $sender->getOwner()->getDataItem();
        $this->_card = array_diff_key($this->_card, array($item->item_id => $this->_card[$item->item_id]));
        $this->tlist->cartpan->cardlist->Reload();
        $this->tlist->cartpan->setVisible(count($this->_card) > 0);
    }

    public function OnCopy($sender) {

        \App\Session::getSession()->clipboard = $this->_card;
        $this->setInfo('Скопировано');
    }

}
