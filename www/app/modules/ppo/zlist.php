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
   public $_doc;
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
        
        $this->add(new Panel('detail'))->setVisible(false);
        $this->detail->add(new Label('vxml'));
        $this->detail->add(new Label('axml'));
        $this->detail->add(new Label('preview'));
        $this->detail->add(new ClickLink('download', $this, 'onfile'));
        
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
          $conn = \ZDB\DB::getConnect();

        $this->_tcnt=0;
        $this->_tamount=0;
        $this->_trcnt=0;
        $this->_tramount=0;
        $from = $this->filter->from->getDate();
        $to = $this->filter->to->getDate();
     
     
        $where = " DATE(createdon) >= " . $conn->DBDate($from) . "               AND DATE(createdon) <= " . $conn->DBDate($to)  ;
        $fnpos =  $sender->pos->getText();
        $fndoc =  $sender->doc->getText();
        if(strlen($fnpos)>0) $where .=  " and fnpos=". ZRecord::qstr($fnpos); 
        if(strlen($fndoc)>0) $where .=  " and fndoc=". ZRecord::qstr($fndoc); 
     
     
         
        $this->_list = ZRecord::find($where,"createdon") ;
        $this->list->Reload();
  
    }

    public function onView($sender) {

         $this->_doc = $sender->getOwner()->getDataItem();
         $this->detail->setVisible(true);
         $printer = \App\System::getOptions('printer');
    
         $this->detail->vxml->setText($this->_doc->sentxml);
         
         $answer = PPOHelper::decrypt($this->_doc->taxanswer) ;
          
         $this->detail->axml->setText($answer );
         
         $xml = $this->_doc->sentxml;
          
         $p = strpos($xml,"?>") ;
         if($p !== false)  {
             $xml = substr($xml,$p+2) ;
         }
        
         $xml = new \SimpleXMLElement($xml);
 
         $header = array();
         $wp = 'style="width:40mm"';
        if (strlen($printer['pwidth']) > 0) {
            $wp = 'style="width:' . $printer['pwidth'] . '"';
        }       
         
         $header['printw']  = $wp;
         $header['date']  = date('Y-m-d',$this->_doc->createdon) ;
         $header['fnpos']  =   $this->_doc->fnpos;
         $header['fndoc']  =   $this->_doc->fndoc;
         $header['cnt']  =   $this->_doc->cnt;
         $header['rcnt']  =   $this->_doc->rcnt;
         $header['payments']  = array();
         $header['rpayments'] = array();
         
         if(  isset($xml->ZREPREALIZ->PAYFORMS) ){
             foreach($xml->ZREPREALIZ->PAYFORMS->children() as $row) {
                $header['payments'][]=array('forma'=>$row->PAYFORMNM,'amount'=>H::fa($row->SUM));    
             }
         }

         if(  isset($xml->v->PAYFORMS) ){
             foreach($xml->ZREPRETURN->PAYFORMS->children() as $row) {
                $header['rpayments'][]=array('forma'=>$row->PAYFORMNM,'amount'=>H::fa($row->SUM));    
             }
         }         
         $header['address']  = (string)  $xml->ZREPHEAD->POINTADDR;
         $header['test']  = "1" == (string)  $xml->ZREPHEAD->TESTING;
         
         $header['inn']  = (string)  $xml->ZREPHEAD->IPN;
         if(strlen($header['inn'])==0) {
            $header['inn']  = (string)  $xml->ZREPHEAD->TIN;   
         }

         $header['firm']  = (string)  $xml->ZREPHEAD->POINTNM;
         if(strlen($header['firm'])==0) {
            $header['firm']  = (string)  $xml->ZREPHEAD->ORGNM;   
         }

         
      
         $report = new \App\Report('zrep.tpl');

         $html = $report->generate($header);
    
         
         $this->detail->preview->setText($html,true);
         
         \App\Session::getSession()->printform  = $html;
         
  
    }

    public function onfile($sender) {
         header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename=zreport'. date('_Y_m_d')  );
        header('Content-Transfer-Encoding: binary');
    

        header('Expires: 0');
     
        header('Content-Length: ' . strlen($this->_doc->taxanswer));
        echo $this->_doc->taxanswer; 
        flush();
              
    }
    
    
    public function oncsv($sender) {
        $csv = "";

        $header = array();
        $data = array();

        $i = 0;
    
            foreach ($this->_list as $c) {
                $i++;
                $data['A' . $i] = H::fd($c->createdon);
                $data['B' . $i] = $c->fnpos;
                $data['C' . $i] = $c->fndoc;
                $data['D' . $i] = $c->cnt;
                $data['E' . $i] = H::fa($c->amount);
                $data['F' . $i] = $c->rcnt;
                $data['G' . $i] = H::fa($c->ramount);
            }
       
 

        H::exportExcel($data, $header, 'zlist'. date('_Y_m_d').'.xlsx');
    }
  

}
