<?php

namespace App\Pages;

use \Zippy\Html\DataList\DataView;
use \App\Entity\User;
use \App\Entity\UserRole;
use \App\System;
use \App\Helper as H;
use \App\Application as App;
use \Zippy\Html\Form\CheckBox;
use \Zippy\Html\Form\DropDownChoice;
use \Zippy\Html\Form\Form;
use \Zippy\Html\Form\Button;
use \Zippy\Html\Form\TextArea;
use \Zippy\Html\Form\TextInput;
use \Zippy\Html\Label;
use \Zippy\Html\Link\ClickLink;
use \Zippy\Html\Panel;
use \Zippy\Binding\PropertyBinding as Bind;

class Roles extends \App\Pages\Base
{

    public $role = null;

    public function __construct() {
        parent::__construct();

        if (System::getUser()->userlogin != 'admin') {
            $this->setError('onlyadminaccess');
            App::RedirectError();
            return false;
        }


        $this->add(new Panel("listpan"));
        $this->listpan->add(new ClickLink('addnew', $this, "onAdd"));
        $this->listpan->add(new DataView("rolerow", new RoleDataSource(), $this, 'OnRow'))->Reload();

        $this->add(new Panel("editpanname"))->setVisible(false);
        $this->editpanname->add(new Form('editformname'))->onSubmit($this, 'savenameOnClick');
        $this->editpanname->editformname->add(new TextInput('editname'));
        $this->editpanname->editformname->add(new Button('cancelname'))->onClick($this, 'cancelOnClick');

        $this->add(new Panel("editpan"))->setVisible(false);
        $this->editpan->add(new Form('editform'))->onSubmit($this, 'saveaclOnClick');


        $this->editpan->editform->add(new CheckBox('editnoshowpartion'));
        $this->editpan->editform->add(new CheckBox('editshowotherstores'));

        //виджеты
        $this->editpan->editform->add(new CheckBox('editwminqty'));
        $this->editpan->editform->add(new CheckBox('editwsdate'));
        $this->editpan->editform->add(new CheckBox('editwrdoc'));
        $this->editpan->editform->add(new CheckBox('editwmdoc'));
        $this->editpan->editform->add(new CheckBox('editwinfo'));
        $this->editpan->editform->add(new CheckBox('editwgraph'));

        //модули
        $this->editpan->editform->add(new CheckBox('editocstore'));
        $this->editpan->editform->add(new CheckBox('editshop'));
        $this->editpan->editform->add(new CheckBox('editwoocomerce'));
        $this->editpan->editform->add(new CheckBox('editnote'));
        $this->editpan->editform->add(new CheckBox('editissue'));
        
        $this->editpan->editform->add(new CheckBox('editppo'));
        $this->editpan->editform->add(new CheckBox('editnp'));
        $this->editpan->editform->add(new CheckBox('editpu'));
        $this->editpan->editform->add(new CheckBox('editpl'));

        $this->editpan->editform->add(new Button('cancel'))->onClick($this, 'cancelOnClick');

        $this->editpan->editform->add(new Panel('metaaccess'));
        $this->editpan->editform->metaaccess->add(new DataView('metarow', new \ZCL\DB\EntityDataSource("\\App\\Entity\\MetaData", "", "meta_type,description"), $this, 'metarowOnRow'));
        $this->editpan->editform->metaaccess->metarow->Reload();

        $this->add(new Panel("editpanmenu"))->setVisible(false);
        $this->editpanmenu->add(new Form('editformmenu'))->onSubmit($this, 'savemenuOnClick');

        $this->editpanmenu->editformmenu->add(new Button('cancelmenu'))->onClick($this, 'cancelOnClick');

        $this->editpanmenu->editformmenu->add(new DataView('mlist', new \Zippy\Html\DataList\ArrayDataSource(array()), $this, 'menurowOnRow'));
    }

    public function onAdd($sender) {


        $this->listpan->setVisible(false);
        $this->editpanname->setVisible(true);
        // Очищаем  форму
        $this->editpanname->editformname->clean();

        $this->role = new UserRole();
    }

    public function onEdit($sender) {
        $this->listpan->setVisible(false);
        $this->editpanname->setVisible(true);
        $this->role = $sender->getOwner()->getDataItem();
        $this->editpanname->editformname->editname->setText($this->role->rolename);
    }

    public function OnMenu($sender) {
        $this->listpan->setVisible(false);
        $this->editpanmenu->setVisible(true);
        $this->role = $sender->getOwner()->getDataItem();

        $w = "";

        if (strlen($this->role->aclview) > 0) {
            $w = " and meta_id in ({$this->role->aclview})";
        } else {
            $w = " and meta_id in (0)";
        }
        if ($this->role->rolename == 'admins') {
            $w = "";
        }

        $smlist = \App\Entity\MetaData::find("disabled<>1 {$w}", "meta_type,description");

        $mod = H::modulesMetaData($this->role);

        $smlist = array_merge($smlist, $mod);

        $this->editpanmenu->editformmenu->mlist->getDataSource()->setArray($smlist);
        $this->editpanmenu->editformmenu->mlist->Reload();
    }

    public function OnAcl($sender) {


        $this->listpan->setVisible(false);
        $this->editpan->setVisible(true);

        $this->role = $sender->getOwner()->getDataItem();

        $this->editpan->editform->metaaccess->metarow->Reload();


        $this->editpan->editform->editnoshowpartion->setChecked($this->role->noshowpartion);
        $this->editpan->editform->editshowotherstores->setChecked($this->role->showotherstores);


        if (strpos($this->role->widgets, 'wminqty') !== false) {
            $this->editpan->editform->editwminqty->setChecked(true);
        }
        if (strpos($this->role->widgets, 'wsdate') !== false) {
            $this->editpan->editform->editwsdate->setChecked(true);
        }
        if (strpos($this->role->widgets, 'wrdoc') !== false) {
            $this->editpan->editform->editwrdoc->setChecked(true);
        }
        if (strpos($this->role->widgets, 'wmdoc') !== false) {
            $this->editpan->editform->editwmdoc->setChecked(true);
        }
        if (strpos($this->role->widgets, 'winfo') !== false) {
            $this->editpan->editform->editwinfo->setChecked(true);
        }
        if (strpos($this->role->widgets, 'wgraph') !== false) {
            $this->editpan->editform->editwgraph->setChecked(true);
        }


        if (strpos($this->role->modules, 'ocstore') !== false) {
            $this->editpan->editform->editocstore->setChecked(true);
        }
        if (strpos($this->role->modules, 'woocomerce') !== false) {
            $this->editpan->editform->editwoocomerce->setChecked(true);
        }
        if (strpos($this->role->modules, 'shop') !== false) {
            $this->editpan->editform->editshop->setChecked(true);
        }
        if (strpos($this->role->modules, 'note') !== false) {
            $this->editpan->editform->editnote->setChecked(true);
        }
        if (strpos($this->role->modules, 'issue') !== false) {
            $this->editpan->editform->editissue->setChecked(true);
        }
        if (strpos($this->role->modules, 'ppo') !== false) {
            $this->editpan->editform->editppo->setChecked(true);
        }
        if (strpos($this->role->modules, 'np') !== false) {
            $this->editpan->editform->editnp->setChecked(true);
        }
        if (strpos($this->role->modules, 'promua') !== false) {
            $this->editpan->editform->editpu->setChecked(true);
        }
        if (strpos($this->role->modules, 'paperless') !== false) {
            $this->editpan->editform->editpl->setChecked(true);
        }
    }

    public function savenameOnClick($sender) {
        $this->role->rolename = $this->editpanname->editformname->editname->getText();

        $role = UserRole::getFirst('rolename=' . UserRole::qstr($this->role->rolename));
        if ($role instanceof UserRole) {
            if ($role->role_id != $this->role->role_id) {
                $this->setError('Неуникальное имя');
                return;
            }
        }

        $this->role->save();
        $this->listpan->rolerow->Reload();
        $this->listpan->setVisible(true);
        $this->editpanname->setVisible(false);
    }

    public function savemenuOnClick($sender) {
        $smartmenu = array();

        foreach ($sender->mlist->getDataRows() as $row) {
            $item = $row->getDataItem();
            if ($item->mview == true) {
                $smartmenu[] = $item->meta_id;
            }
        }
        $this->role->smartmenu = implode(',', $smartmenu);

        $this->role->save();
        $this->listpan->rolerow->Reload();
        $this->listpan->setVisible(true);
        $this->editpanmenu->setVisible(false);
    }

    public function saveaclOnClick($sender) {

        $this->role->noshowpartion = $this->editpan->editform->editnoshowpartion->isChecked() ? 1 : 0;
        $this->role->showotherstores = $this->editpan->editform->editshowotherstores->isChecked() ? 1 : 0;

        $varr = array();
        $earr = array();
        $xarr = array();
        $carr = array();
        $sarr = array();
        $darr = array();

        foreach ($this->editpan->editform->metaaccess->metarow->getDataRows() as $row) {
            $item = $row->getDataItem();
            if ($item->viewacc == true) {
                $varr[] = $item->meta_id;
            }
            if ($item->editacc == true) {
                $earr[] = $item->meta_id;
            }
            if ($item->stateacc == true) {
                $sarr[] = $item->meta_id;
            }
            if ($item->exeacc == true) {
                $xarr[] = $item->meta_id;
            }
            if ($item->cancelacc == true) {
                $carr[] = $item->meta_id;
            }
            if ($item->deleteacc == true) {
                $darr[] = $item->meta_id;
            }
        }
        $this->role->aclview = implode(',', $varr);
        $this->role->acledit = implode(',', $earr);
        $this->role->aclexe = implode(',', $xarr);
        $this->role->aclcancel = implode(',', $carr);
        $this->role->aclstate = implode(',', $sarr);
        $this->role->acldelete = implode(',', $darr);

        $widgets = "";

        if ($this->editpan->editform->editwminqty->isChecked()) {
            $widgets = $widgets . ',wminqty';
        }
        if ($this->editpan->editform->editwsdate->isChecked()) {
            $widgets = $widgets . ',wsdate';
        }
        if ($this->editpan->editform->editwrdoc->isChecked()) {
            $widgets = $widgets . ',wrdoc';
        }
        if ($this->editpan->editform->editwmdoc->isChecked()) {
            $widgets = $widgets . ',wmdoc';
        }
        if ($this->editpan->editform->editwinfo->isChecked()) {
            $widgets = $widgets . ',winfo';
        }
        if ($this->editpan->editform->editwgraph->isChecked()) {
            $widgets = $widgets . ',wgraph';
        }


        $this->role->widgets = trim($widgets, ',');

        $modules = "";
        if ($this->editpan->editform->editshop->isChecked()) {
            $modules = $modules . ',shop';
        }
        if ($this->editpan->editform->editnote->isChecked()) {
            $modules = $modules . ',note';
        }
        if ($this->editpan->editform->editocstore->isChecked()) {
            $modules = $modules . ',ocstore';
        }
        if ($this->editpan->editform->editwoocomerce->isChecked()) {
            $modules = $modules . ',woocomerce';
        }
        if ($this->editpan->editform->editissue->isChecked()) {
            $modules = $modules . ',issue';
        }
        if ($this->editpan->editform->editppo->isChecked()) {
            $modules = $modules . ',ppo';
        }
        if ($this->editpan->editform->editnp->isChecked()) {
            $modules = $modules . ',np';
        }
        if ($this->editpan->editform->editpu->isChecked()) {
            $modules = $modules . ',promua';
        }
        if ($this->editpan->editform->editpl->isChecked()) {
            $modules = $modules . ',paperless';
        }

        $this->role->modules = trim($modules, ',');

        $this->role->save();
        $this->listpan->rolerow->Reload();
        $this->listpan->setVisible(true);
        $this->editpan->setVisible(false);
    }

    public function cancelOnClick($sender) {
        $this->listpan->setVisible(true);
        $this->editpan->setVisible(false);
        $this->editpanname->setVisible(false);
        $this->editpanmenu->setVisible(false);
    }

    //удаление  роли

    public function OnRemove($sender) {

        $role = $sender->getOwner()->getDataItem();

        $del = UserRole::delete($role->role_id);
        if (strlen($del) > 0) {
            $this->setError($del);
            return;
        }

        $this->listpan->rolerow->Reload();
    }

    public function OnRow($datarow) {
        $item = $datarow->getDataItem();
        $datarow->add(new \Zippy\Html\Label("rolename", $item->rolename));
        $datarow->add(new \Zippy\Html\Label("cnt", $item->cnt));

        $datarow->add(new \Zippy\Html\Link\ClickLink("smenu", $this, "OnMenu"));
        $datarow->add(new \Zippy\Html\Link\ClickLink("acl", $this, "OnAcl"))->setVisible($item->rolename != 'admins');
        $datarow->add(new \Zippy\Html\Link\ClickLink("edit", $this, "OnEdit"))->setVisible($item->rolename != 'admins');
        $datarow->add(new \Zippy\Html\Link\ClickLink("remove", $this, "OnRemove"))->setVisible($item->rolename != 'admins');
        if ($item->cnt > 0) {
            $datarow->remove->setVisible(false);
        }
        if ($item->cnt == 0) {
            $datarow->cnt->setVisible(false);
        }
    }

    public function metarowOnRow($row) {
        $item = $row->getDataItem();
        switch($item->meta_type) {
            case 1:
                $title = H::l('md_doc');
                break;
            case 2:
                $title = H::l('md_rep');
                break;
            case 3:
                $title = H::l('md_reg');
                break;
            case 4:
                $title = H::l('md_ref');
                break;
            case 5:
                $title = H::l('md_ser');
                break;
        }
        $item->editacc = false;
        $item->viewacc = false;
        $item->exeacc = false;
        $item->stateacc = false;
        $item->cancelacc = false;
        $item->deleteacc = false;
        $earr = @explode(',', $this->role->acledit);
        if (is_array($earr)) {
            $item->editacc = in_array($item->meta_id, $earr);
        }
        $sarr = @explode(',', $this->role->aclstate);
        if (is_array($sarr)) {
            $item->stateacc = in_array($item->meta_id, $sarr);
        }
        $varr = @explode(',', $this->role->aclview);
        if (is_array($varr)) {
            $item->viewacc = in_array($item->meta_id, $varr);
        }
        $xarr = @explode(',', $this->role->aclexe);
        if (is_array($xarr)) {
            $item->exeacc = in_array($item->meta_id, $xarr);
        }
        $carr = @explode(',', $this->role->aclcancel);
        if (is_array($carr)) {
            $item->cancelacc = in_array($item->meta_id, $carr);
        }
        $darr = @explode(',', $this->role->acldelete);
        if (is_array($carr)) {
            $item->deleteacc = in_array($item->meta_id, $darr);
        }

        $row->add(new Label('description', $item->description));
        $row->add(new Label('meta_name', $title));

        $row->add(new CheckBox('viewacc', new Bind($item, 'viewacc')));
        $row->add(new CheckBox('editacc', new Bind($item, 'editacc')))->setVisible($item->meta_type == 1 || $item->meta_type == 4);
        $row->add(new CheckBox('exeacc', new Bind($item, 'exeacc')))->setVisible($item->meta_type == 1);
        $row->add(new CheckBox('cancelacc', new Bind($item, 'cancelacc')))->setVisible($item->meta_type == 1);
        $row->add(new CheckBox('stateacc', new Bind($item, 'stateacc')))->setVisible($item->meta_type == 1);
        $row->add(new CheckBox('deleteacc', new Bind($item, 'deleteacc')))->setVisible($item->meta_type == 1 || $item->meta_type == 4);
    }

    public function menurowOnRow($row) {
        $item = $row->getDataItem();
        switch($item->meta_type) {
            case 1:
                $title = H::l('md_doc');
                break;
            case 2:
                $title = H::l('md_rep');
                break;
            case 3:
                $title = H::l('md_reg');
                break;
            case 4:
                $title = H::l('md_ref');
                break;
            case 5:
                $title = H::l('md_ser');
                break;
            case 6:
                $title = H::l('md_mod');
                break;
        }
        $smartmenu = @explode(',', $this->role->smartmenu);
        if (is_array($smartmenu)) {
            $item->mview = in_array($item->meta_id, $smartmenu);
        }


        $row->add(new Label('meta_desc', $item->description));
        $row->add(new Label('meta_name', $title));
        $row->add(new Label('menugroup', $item->menugroup));

        $row->add(new CheckBox('mshow', new Bind($item, 'mview')));
    }

}

class RoleDataSource implements \Zippy\Interfaces\DataSource
{

    //private $model, $db;

    public function getItemCount() {
        return UserRole::findCnt();
    }

    public function getItems($start, $count, $orderbyfield = null, $desc = true) {
        return UserRole::find('', $orderbyfield, $count, $start);
    }

    public function getItem($id) {
        return UserRole::load($id);
    }

}
