<?php

namespace App\Entity;

use ZCL\DB\Entity;

/**
 *  Класс  инкапсулирующий   сущность  UserRole
 * @table=roles
 * @view=roles_view
 * @keyfield=role_id
 */
class UserRole extends Entity
{
    /**
     * @see Entity
     *
     */
    protected function init() {

        $this->role_id = 0;
    }

    /**
     * @see Entity
     *
     */
    protected function afterLoad() {
     

        $acl = @unserialize($this->acl);
        if (!is_array($acl)) {
            $acl = array();
        }

        $this->canevent = $acl['canevent']??0;
        $this->noshowpartion = $acl['noshowpartion']??0;
        $this->showotherstores = $acl['showotherstores']??0;
        $this->aclview = $acl['aclview']??'';
        $this->acledit = $acl['acledit']??'';
        $this->aclexe = $acl['aclexe']??'';
        $this->aclcancel = $acl['aclcancel']??'';
        $this->aclstate = $acl['aclstate']??'';
        $this->acldelete = $acl['acldelete']??'';

        $this->widgets = $acl['widgets']??'';
        $this->modules = $acl['modules']??'';
        $this->smartmenu = $acl['smartmenu']??'';

        parent::afterLoad();
    }

    /**
     * @see Entity
     *
     */
    protected function beforeSave() {
        parent::beforeSave();

        $acl = array();

        $acl['canevent'] = $this->canevent;
        $acl['noshowpartion'] = $this->noshowpartion;
        $acl['showotherstores'] = $this->showotherstores;
        $acl['aclview'] = $this->aclview;
        $acl['acledit'] = $this->acledit;
        $acl['aclexe'] = $this->aclexe;
        $acl['aclcancel'] = $this->aclcancel;
        $acl['aclstate'] = $this->aclstate;
        $acl['acldelete'] = $this->acldelete;

        $acl['widgets'] = $this->widgets;
        $acl['widgets'] = $this->widgets;
        $acl['modules'] = $this->modules;
        $acl['smartmenu'] = $this->smartmenu;
        $this->acl = serialize($acl);

        return true;
    }

}
