<?php

namespace App\Pages\Report;

use App\Application as App;
use App\Entity\Item;
use App\Entity\Excise;
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
 * Акцизные  марки
 */
class ExciseList extends \App\Pages\Base
{
    public function __construct() {
        parent::__construct();
        if (false == \App\ACL::checkShowReport('ExciseList')) {
            return;
        }

        $this->add(new Form('filter'));
        $this->filter->add(new Date('from', time() - (7 * 24 * 3600)));
        $this->filter->add(new Date('to', time()));

        $this->filter->add(new TextInput('search')) ;
      
        $items=Item::findArray("itemname","item_id in (select item_id from excisestamps )","itemname");
        $this->filter->add(new DropDownChoice('item',$items,0)) ;
     
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
         
        $stamp = $this->filter->search->getText();
        $itemid = $this->filter->item->getValue();
        $itemname = $this->filter->item->getValueName();
        $from = $this->filter->from->getDate();
        $to = $this->filter->to->getDate();
    

        $where = "document_date >=".$conn->DBDate($from)."and document_date <=".$conn->DBDate($to) ;
        if ($itemid > 0) {
            $where .= " and  item_id=" . $itemid;
        }
        if (strlen($stamp) > 0) {
            $where = " stamp like " . Excise::qstr('%'.$stamp.'%');
        }

     
     
        $detail = array();
      
        $sql = "
   
    
        SELECT  * from excisestamps_view 
              where {$where} 
              ORDER BY id  
        ";
        
        $rs = $conn->Execute($sql);
    


        foreach ($rs as $row) {

        
            $r = array(
          
                "stamp"  => $row['stamp']??'',
                "item_code"  => $row['item_code']??'',
                "itemname"  => $row['itemname'],
                "document_number"  => $row['document_number'],

                "document_date"      => \App\Helper::fd(strtotime($row['document_date'])) 
             
            );

            $detail[] = $r;
      


        }
        $totamount=0;
        $amount=0;
      
        
        $rs =  \App\Entity\Doc\Document::find("document_id in ( SELECT  document_id from excisestamps_view    where {$where} )");
        foreach($rs as $doc)  {
            $totamount += $doc->amount;
            $amount += doubleval($doc->getHD('exciseval'));
        }
        
        $header = array('datefrom'      => \App\Helper::fd($from),
                        "_detail"       => $detail,
                        'dateto'        => \App\Helper::fd($to),
                        "totamount"  => H::fa($totamount),
                        "amount"  => H::fa($amount),
                        "itemname"      => $itemid > 0 ? $itemname : false
        );

       
        $report = new \App\Report('report/exciselist.tpl');

        $html = $report->generate($header);

        return $html;
    }

    public function getData() {


        $html = $this->generateReport();
        \App\Session::getSession()->printform = "<html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\"></head><body>" . $html . "</body></html>";

        return $html;

    }


}
