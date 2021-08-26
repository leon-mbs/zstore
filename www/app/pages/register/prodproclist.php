<?php

namespace App\Pages\Register;

use App\Entity\ProdProc;
use App\Entity\ProdStage;
 
use App\Helper as H;
use App\System;
use Zippy\Html\DataList\DataView;
use Zippy\Html\DataList\Paginator;
use Zippy\Html\Form\AutocompleteTextInput;
use Zippy\Html\Form\Date;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Link\BookmarkableLink;

/**
 * журнал производственные процессы
 */
class ProdProcList extends \App\Pages\Base
{
 
    private $_proc    = null;
   

    /**
     *
     * @return DocList
     */
    public function __construct() {
        parent::__construct();
        if (false == \App\ACL::checkShowReg('ProdProcList')) {
            return;
        }

     
        $proclist = $this->add(new DataView('proclist', new PProcListDataSource($this), $this, 'proclistOnRow'));

        $this->add(new Paginator('pag', $proclist));
        $proclist->setPageSize(H::getPG());

        
        $proclist->Reload();
     
    }

 

    public function proclistOnRow(\Zippy\Html\DataList\DataRow $row) {
        $doc = $row->getDataItem();

        $row->add(new Label('number', $doc->document_number));

        $row->add(new Label('date', H::fd($doc->paydate)));
        $row->add(new Label('notes', $doc->notes));

        $row->add(new Label('username', $doc->username));

        $row->add(new Label('paytype', $this->_ptlist[$doc->paytype]));

        $row->add(new ClickLink('show', $this, 'showOnClick'));
        $user = \App\System::getUser();

    }

    //просмотр
    public function showOnClick($sender) {

         

   
    }


   
}

/**
 *  Источник  данных  для   списка  документов
 */
class PProcListDataSource implements \Zippy\Interfaces\DataSource
{

    private $page;

    public function __construct($page) {
        $this->page = $page;
    }

    private function getWhere() {
  

  
        return "";
    }

    public function getItemCount() {
        
        return ProdProc::findCnt($this->getWhere());
    }

    public function getItems($start, $count, $sortfield = null, $asc = null) {
         

       return  ProdProc::find($this->getWhere()," pp_id desc  ");

        
    }

    public function getItem($id) {

    }

}
