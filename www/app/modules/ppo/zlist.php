<?php

namespace App\Modules\PPO;

use App\Application as App;
use App\Entity\MoneyFund;
use Zippy\Binding\PropertyBinding as Prop;
use \App\Helper as H;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Form\Date;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Label;
use Zippy\Html\Link\RedirectLink;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Panel;
use Zippy\Html\DataList\ArrayDataSource;
use Zippy\Html\DataList\DataView;
/**
 * Журнал  z - отчетов
 */
class ZList extends \App\Pages\Base
{
   public $_list=array();
   public $_tcnt=0,$_tamount=0,$_trcnt=0,$_tramount=0;
   
    public function __construct() {
        parent::__construct();
     

        $this->add(new Form('filter'))->onSubmit($this, 'OnSubmit');
        $this->filter->add(new Date('from', time() - (7 * 24 * 3600)));
        $this->filter->add(new Date('to', time()));
        $this->filter->add(new TextInput('pos'));
        $this->filter->add(new TextInput('doc'));

        $this->filter->add(new Label('tcnt',new Prop($this, '_tcnt')));
        $this->filter->add(new Label('trcnt',new Prop($this, '_trcnt')));
        $this->filter->add(new Label('tamount',new Prop($this, '_tamount')));
        $this->filter->add(new Label('tramount',new Prop($this, '_tramount')));
   
        $this->add(new DataView('list', new ArrayDataSource(new Prop($this, '_list')), $this, 'OnRow'));
        $this->add(new ClickLink('csv', $this, 'oncsv'));
    
    }
  
    public  function onRow($row){
        $item = $row->getDataItem();
        $row->add(new Label("createdon", H::fd($item->createdon)) );
        $row->add(new Label("fnpos", $item->fnpos) );
        $row->add(new Label("fndoc", $item->fndoc) );
        $row->add(new Label("cnt",  ($item->cnt) ));
        $row->add(new Label("rcnt",  ($item->rcnt)) );
        $row->add(new Label("amount", H::fa($item->amount) ));
        $row->add(new Label("ramount", H::fa($item->ramount) ) );

        $row->add(new  ClickLink("view",$this,"onView")) ;       
        
        $this->_tcnt += $item->cnt;
        $this->_tamount += H::fa($item->amount) ;
        $this->_trcnt += $item->rcnt;
        $this->_tramount += H::fa($item->ramount);

    }

    public function OnSubmit($sender) {

        $this->_tcnt=0;
        $this->_tamount=0;
        $this->_trcnt=0;
        $this->_tramount=0;
        
        $this->_list = ZRecord::find("","createdon") ;
        $this->list->Reload();
  
    }

    public function onView($sender) {

         $item = $sender->getOwner()->getDataItem();
         
  
    }

    public function oncsv($sender) {
        $csv = "";

        $header = array();
        $data = array();

        $i = 0;

        if ($sender->id == 'csv') {
            $list = $this->clist->custlist->getDataSource()->getItems(-1, -1, 'customer_name');

            foreach ($list as $c) {
                $i++;
                $data['A' . $i] = $c->customer_name;
                $data['B' . $i] = $c->phone;
                $data['C' . $i] = H::fa($c->sam);
            }
        }
        if ($sender->id == 'csv2') {
            $list = $this->plist->doclist->getDataSource()->getItems(-1, -1, 'document_id');

            foreach ($list as $d) {
                $i++;
                $data['A' . $i] = H::fd($d->document_date);
                $data['B' . $i] = $d->document_number;
                $data['C' . $i] = H::fa($d->amount);
                $data['D' . $i] = $d->notes;
            }
        }

        H::exportExcel($data, $header, 'baylist.xlsx');
    }
  

}
