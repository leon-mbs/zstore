<?php

namespace App\Pages\Report;

use App\Application as App;
use App\Entity\Item;
use App\Entity\Entry;
use App\Entity\Store;
use App\Helper as H;
use Zippy\Html\Form\AutocompleteTextInput;
use Zippy\Html\Form\Date;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Label;
use Zippy\Html\Link\RedirectLink;
use Zippy\Html\Panel;

/**
 * Форма  учета  товарных запасов
 */
class FormItemsReport extends \App\Pages\Base
{
    public function __construct() {
        parent::__construct();
        if (false == \App\ACL::checkShowReport('ItemActivity')) {
            return;
        }

        $this->add(new Form('filter'));
        $this->filter->add(new Date('from', time() - (30* 24 * 3600)));
        $this->filter->add(new Date('to', time()));

      
        $this->filter->add(new DropDownChoice('store', Store::getList(), H::getDefStore()));
       
 
        $this->filter->add(new SubmitButton('show'))->onClick($this, 'OnSubmit');
   
        $this->add(new Panel('detail'))->setVisible(false);

        $this->detail->add(new Label('preview'));
        \App\Session::getSession()->issubmit = false;
    }

  
 

    public function OnSubmit($sender) {


        $this->detail->setVisible(true);

        $html = $this->generateReport();
        \App\Session::getSession()->printform = "<html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\"></head><body>" . $html . "</body></html>";
        $this->detail->preview->setText($html, true);

       
    }

    private function generateReport() {
        $conn = \ZDB\DB::getConnect();
 
        $storeid = $this->filter->store->getValue();
        $it = "1=1";
    

        $from = $this->filter->from->getDate();
        $to = $this->filter->to->getDate();

        $i = 1;
        $detail = array();
  
        $sql="SELECT d.meta_name,d.meta_desc,d.customer_name,d.document_number,d.document_date, t.* FROM documents_view d  
            JOIN 
            (SELECT document_id,tag,SUM(partion) AS spart,SUM(ev.outprice) AS soprice,SUM(ev.quantity) AS qty FROM entrylist_view ev WHERE item_id > 0  AND tag <>-64  
            GROUP BY document_id,tag ) t                                                                   
            ON d.document_id = t.document_id
            WHERE   d.document_date >= " . $conn->DBDate($from) . " and d.document_date <= " . $conn->DBDate($to) ."   
            
            and d.meta_name not in ('ProdMove','MoveItem','MovePart','TransItem')     
            ORDER BY d.document_date,d.document_id";
        
        $rs = $conn->Execute($sql);

        $i=1;
        foreach ($rs as $row) {
 
            $type =  $row['meta_desc']  ;
            $in =  $row['qty'] >0 ? H::fa($row['spart']) :''  ;
            $out =   $row['qty'] >0 ? H::fa(($row['outprice'] ??0)>0  ? $row['outprice']:''  ) :$row['spart']   ;
            
            if($type != 'ProdReceipt' && $row['tag'] == Entry::TAG_FROMPROD  )  $type='Оприходування з виробництва';
            if($type != 'ProdIssue' && $row['tag'] == Entry::TAG_TOPROD  )   $type='Списання в виробництв';
              
            $row['nrec'] = $i++ ;
            $row['datedec'] = H::fd(strtotime($row['document_date'])) ;
            $row['doctype'] =  $type  ;
            $row['docdate'] = H::fd(strtotime($row['document_date']));
            $row['docno'] = $row['document_number'] ;
            $row['cust'] = $row['customer_name'] ?? '';
            $row['in'] = $in;
            $row['out'] = $out ;
                                          
            $detail[] = $row;
  
        }

        $firm = H::getFirmData() ;
        
        $header = array('datefrom'      => \App\Helper::fd($from),
                        "_detail"       => $detail,
                        "firmname"       => $firm['firm_name'],
                        'dateto'        => \App\Helper::fd($to) 
                      
        );

   
        $report = new \App\Report('report/formitemsreport.tpl');

        $html = $report->generate($header);

        return $html;
    }

 


}
