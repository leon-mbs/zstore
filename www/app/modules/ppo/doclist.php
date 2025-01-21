<?php

namespace App\Modules\PPO;

use App\Application as App;
use App\Entity\Doc\Document;
use Zippy\Binding\PropertyBinding as Prop;
use App\Helper as H;
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
 *  Список  фискализованых чеков
 */
class DocList extends \App\Pages\Base
{
 
    public function __construct() {
        parent::__construct();


        $this->add(new Form('filter'))->onSubmit($this, 'OnSubmit');
        $this->filter->add(new Date('from', time() - (7 * 24 * 3600)));
        $this->filter->add(new Date('to', time()));
 
        $this->filter->add(new TextInput('doc'));

 
        $this->add(new Panel('detail'))->setVisible(false);

        $this->detail->add(new Label('preview'));


    }

    public function onRow($row) {
        $item = $row->getDataItem();
        $row->add(new Label("datadoc", H::fd($item->createdon)));
        $row->add(new Label("fndoc", $item->fndoc));
        $row->add(new Label("numdoc", $item->fndoc));
        $row->add(new Label("typedoc", $item->fndoc));
        $row->add(new Label("amount", H::fa($item->amount)));


   //     $this->_tcnt += $item->cnt;
     //   $this->_tamount += H::fa($item->amount) ;
   //     $this->_trcnt += $item->rcnt;
    //    $this->_tramount += H::fa($item->ramount);

    }

    public function OnSubmit($sender) {
        $html = $this->generateReport();
        $this->detail->preview->setText($html, true);
        \App\Session::getSession()->printform = "<html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\"></head><body>" . $html . "</body></html>";


        $this->detail->setVisible(true);        
        
    }
        
        
    public function generateReport() {
        $conn = \ZDB\DB::getConnect();

  
        $from = $this->filter->from->getDate();
        $to = $this->filter->to->getDate();

        $header = array('datefrom' => \App\Helper::fd($from),
                        'dateto'   => \App\Helper::fd($to) 
                      );
        $header['cnt'] =0;
        $header['tam'] =0;
        $header['rcnt'] =0;
        $header['rtam'] =0;
                      
        $where = "meta_name  in ('POSCheck' ,'ReturnIssue','OrderFood') and  document_date >= " . $conn->DBDate($from) . "     AND document_date <= " . $conn->DBDate($to)  ;
        $where .=  " and content like '%<fiscalnumber>%' ";

        $fndoc =  $this->filter->doc->getText();
     
        if(strlen($fndoc)>0) {
            $ndoc = Document::qstr($fndoc)  ;
            $fn = Document::qstr( '%'. $fndoc . '%')      ;
            
            $where .=  " and (document_number={$ndoc} or content like {$fn} ) ";
        }
 
        
        $detail = array();

        foreach(Document::findYield($where, "document_id") as $doc) {
            $row=[];
            $row['fn'] = $doc->headerdata['fiscalnumber']??  '';
            if($row['fn']=='') continue;
            if(($doc->header['fiscaltest']?? false) ) continue;  //тестовый
            
            $row['amount'] = doubleval($doc->headerdata['fiscalamount']??  0);
            if($row['amount']==0) {
               $row['amount']  = $doc->amount;               
            }
            $row['amount'] = H::fa($row['amount'] );

            $row['docdata'] = H::fd($doc->document_date);
            $row['docnumber'] = $doc->document_number;
            $row['type'] = 'Касовий чек';
            if($doc->meta_name == 'POSCheck' || $doc->meta_name == 'OrderFood') { 
               $header['cnt']++;
               $header['tam'] += $row['amount'];
            }           
            if($doc->meta_name == 'ReturnIssue' && ($doc->headerdata['docnumberback'] ?? '') != '') {
               $row['type'] = 'Повернення для '. $doc->headerdata['docnumberback'];                
               $header['rcnt']++;
               $header['rtam'] += $row['amount'];
            }
             
            
            $detail[]=$row;
        }

         
        $header['detail']  = $detail;
  
        $report = new \App\Report('report/prrodoclist.tpl');

        $html = $report->generate($header);

        return $html;
    }
 

 

}
