<?php

namespace App\Pages\Register;

use App\Entity\Customer;
use App\Entity\Doc\Document;
use App\Entity\Pay;
use App\Helper as H;
use App\System;
use Zippy\Html\DataList\DataView;
use Zippy\Html\DataList\Pager;
use Zippy\Html\Form\AutocompleteTextInput;
use Zippy\Html\Form\Date;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Label;
use Zippy\Html\Panel;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Link\BookmarkableLink;
use App\Application as App;

/**
 * журнал доходы  и расходы
 */
class IOState extends \App\Pages\Base
{
    private ?Document $_doc    = null;
    public array $_ptlist = [];
    

    /**
     *
     * @return DocList
     */
    public function __construct() {
        parent::__construct();
        if (false == \App\ACL::checkShowReg('IOState')) {
            App::RedirectHome() ;
        }
        $this->_tvars['bmode'] = false;
        $this->_tvars['totalin'] = "";
        $this->_tvars['totalout'] = "";
        $this->_tvars['totaldiff'] = "";
          
        $this->_ptlist = \App\Entity\IOState::getTypeListBook();

        $this->add(new Form('filter'))->onSubmit($this, 'filterOnSubmit');
        $this->filter->add(new DropDownChoice('fuser', \App\Entity\User::findArray('username', 'disabled<>1', 'username'), 0));
        $this->filter->add(new DropDownChoice('ftype', $this->_ptlist, 0));
      
        $dt = new \App\DateTime();
        $dt->subMonth(1)   ;
        $from = $dt->startOfMonth()->getTimestamp();
        $to = $dt->endOfMonth()->getTimestamp();
        $this->filter->add(new Date('from',$from));
        $this->filter->add(new Date('to',$to));
              
        
        $this->add(new Form('docform'))->onSubmit($this, 'addOnSubmit');
        $this->docform->add(new TextInput('docnumber'));
        $this->docform->add(new TextInput('docamount'));
        $this->docform->add(new DropDownChoice('docio', $this->_ptlist, 0));
         
        
        $doclist = $this->add(new DataView('doclist', new IOStateListDataSource($this), $this, 'doclistOnRow'));

        
        $this->add(new Pager('pag', $doclist));
        $doclist->setPageSize(H::getPG());

        $this->add(new \App\Widgets\DocView('docview'))->setVisible(false);

       
        
        $this->add(new ClickLink('csv', $this, 'oncsv'));
        $this->add(new ClickLink('viewbook', $this, 'onviewbook'));
        $this->add(new ClickLink('bmode', $this, 'onmode')) ;
        $this->add(new ClickLink('jmode', $this, 'onmode')) ;

        
        $this->add(new Panel('bookrep' ))->setVisible(false);
       
        $this->bookrep->add(new Label('bookrephtml' ));
        
        
   //     $this->_ptlist[0] = '';
       
        $this->update(); 
    }

    public function onmode($sender) {
        
        $this->_tvars['bmode'] = $sender->id=='bmode';
      
        $this->update(); 
    }
   
    public function filterOnSubmit($sender) {
        $this->docview->setVisible(false);
        $this->update();
    }
    
    public function addOnSubmit($sender) {
        $dn = trim($this->docform->docnumber->getText() );
        $da = doubleval($this->docform->docamount->getText() );
        $type = intval($this->docform->docio->getValue());
        $doc = Document::getFirst("  document_number = ".Document::qstr($dn) )  ;
        if($doc==null) {
            $this->setError('Документ не знайдено') ;
            return;
        }
        if($type ==0) {
            $this->setError('Не вказаний тип') ;
            return;
        }
        if($da == 0) {
            $this->setError('Не введена  сума') ;
            return;
        }
        
        $doc->setHD('iniostate',1);
        $doc->setHD('outiostate',0);
        $doc->setHD('iniostatetype',$type);
        $doc->setHD('iniostateamount',$da);
        $doc->save();
        $this->setSuccess('Додано') ;
        $this->docform->docamount->setText('');
        $this->docform->docnumber->setText('');
        $this->docform->docio->setValue(0);
        $this->docview->setVisible(false);
        $this->update();
    }

    private function update( ) {
        $this->_tvars['totalin'] = 0;
        $this->_tvars['totalout'] = 0;
        $this->doclist->Reload(); 
        
  
        
  
        $this->_tvars['totalin']   = H::fa($this->_tvars['totalin']   );
        $this->_tvars['totalout']  = H::fa($this->_tvars['totalout']   );
        $this->_tvars['totaldiff'] = H::fa($this->_tvars['totalin'] - $this->_tvars['totalout'] );
        $this->bookrep->setVisible(false);  
                                    
          
    }

    public function doclistOnRow(\Zippy\Html\DataList\DataRow $row) {
        $doc = $row->getDataItem();
        $d=Document::load($doc->document_id)  ;
             
        if($d->getHD('iniostatetype') > 0) {
             $doc->iotype = $d->getHD('iniostatetype') ;
             $doc->amount = $d->getHD('iniostateamount') ;
        }
        $row->add(new Label('number', $doc->document_number));
        $row->add(new Label('date', H::fd($doc->document_date)));
        $row->add(new Label('amountin', ''));
        $row->add(new Label('amountout', ''));
        $row->add(new Label('username', $doc->username));
        $row->add(new Label('iotype', $this->_ptlist[$doc->iotype] ??''));
        $row->add(new ClickLink('show', $this, 'showOnClick'));
        $row->add(new ClickLink('delete', $this, 'deleteOnClick'));
        
        if($doc->iotype < 30) {
           $row ->amountin->setText(H::fa($doc->amount));
           $this->_tvars['totalin']  += $doc->amount;   
        } else {
           $row ->amountout->setText(H::fa(0-$doc->amount));
           $this->_tvars['totalout'] += (0-$doc->amount);
        }
  
             
        
    }

   
    public function deleteOnClick($sender) {

        $this->_doc = Document::load($sender->owner->getDataItem()->document_id);
        $this->_doc->setHD('outiostate',1);
        $this->_doc->setHD('iniostate',0);
        $this->_doc->save();

        $this->docview->setVisible(false);
        $this->update();       
    }
  
    public function showOnClick($sender) {

        $this->_doc = Document::load($sender->owner->getDataItem()->document_id);

        if (false == \App\ACL::checkShowDoc($this->_doc, true)) {
            return;
        }
        $this->bookrep->setVisible(false);  
        
        $this->docview->setVisible(true);
        $this->docview->setDoc($this->_doc);
    }



  
    
    public function oncsv($sender) {
        $list = $this->doclist->getDataSource()->getItems(-1, -1);

      
        
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('Прибутки'); // Optionally set a title
     
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Видатки');
         

        $i1 = 0;
        $i2 = 0;
        foreach ($list as $doc) {
            if($doc->iotype < 30)  {
               $i1++; 
               $i =  $i1;
               $sheet =  $sheet1; 
            } else {
               $i2++;    
               $i =  $i2;
               $sheet =  $sheet2; 
            }
           
         
            $c = $sheet->getCell('A' . $i);
            $style = $sheet->getStyle('A' . $i);  
            $style->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_DATE_DDMMYYYY);
            $c->setValue(date('d/m/Y', $doc->document_date));
       
            $c = $sheet->getCell('B' . $i);
            $c->setValueExplicit($doc->document_number, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                    
            $c = $sheet->getCell('C' . $i);
            $style = $sheet->getStyle('C' . $i);  
            $style->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
            $c->setValueExplicit(H::fa($doc->amount), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
        
            $c = $sheet->getCell('D' . $i);
            $c->setValueExplicit($this->_ptlist[$doc->iotype] ??'', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        
        }
 
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="iostate.xlsx"');
        $writer->save('php://output');
        die;        
        
    }

    
    public function onviewbook($sender) {
      
       
       $list=[];
       foreach($this->doclist->getDataSource()->getItems(-1, -1)  as $r){
          
           $d= H::fd(  $r->document_date )  ;
           if(!isset($list[$d])) $list[$d] =[];
           
           $list[$d][]= $r;
       }
       
       
       $header=[];
       $header['rows']=[];
       
       foreach($list as $d=>$iolist) {
           $row=[];
           $docs=[];
           $row['date']  = $d;
           $c2=0; $c3=0; $c4=0; $c5=0;$c6=0;$c7=0;$c8=0;$c9=0;$c10=0;$c11=0;
           foreach($iolist as $io) {  
              $doc=Document::load($io->document_id)  ;
              if($doc->getHD('iniostatetype') > 0) {
                 $io->iotype = $doc->getHD('iniostatetype') ;
                 $io->amount = $doc->getHD('iniostateamount') ;
              }
               
               
              if($io->iotype == 1 || $io->iotype == 2 || $io->iotype == 3 )  { //доходы
                 $c2 +=  abs( $io->amount );
                 continue; 
              }

              if($doc->meta_name=='ReturnIssue') {  //возврат
                 $c3 +=  abs( $io->amount ); 
                 continue; 
              }              
              
              //затраты
              if($io->iotype == 50)  {   //закупка
                 $c6 +=  abs( $io->amount );
              }     
              if($io->iotype == 54)  {   //зарплата
                 $c7 +=  abs( $io->amount );
              }     
              if( in_array($io->iotype,[55,70,71])   )  {   //налоги
                 $c8 +=  abs( $io->amount );
              }     
              if(in_array($io->iotype,[53,60,63]))  {   
                 $c9 +=  abs($io->amount);
              }     
              
                 
              if($io->iotype == 67)  {  
                 $c10 +=  abs( $io->amount );
              }        
              
              $docs[]=  $io->document_number   ;
  
             
    
              
              
              
                         
           }
           $c4 = $c2 - $c3;
            
           $c11 = $c4 - $c6 - $c7 - $c8 - $c9 - $c10 ;
           
           $row['c2']   = number_format($c2, 2, '.', '') ;
           $row['c3']   = number_format($c3, 2, '.', '') ;
           $row['c4']   = number_format($c4, 2, '.', '') ;
           $row['c6']   = number_format($c6, 2, '.', '') ;
           $row['c7']   = number_format($c7, 2, '.', '') ;
           $row['c8']   = number_format($c8, 2, '.', '') ;
           $row['c9']   = number_format($c9, 2, '.', '') ;
           $row['c10']   = number_format($c10, 2, '.', '') ;
           $row['c11']   = number_format($c11, 2, '.', '') ;
           $row['dn']   = implode(', ',$docs) ;
           
           $header['rows'][]=$row;
           
           
           
       }
       
       
       unset($list) ; 
       
       
       
       
       
       $report = new \App\Report('iobook.tpl');

       $html = $report->generate($header);
  
  
       \App\Session::getSession()->printform = "<html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\"></head><body>" . $html . "</body></html>";

       $this->bookrep->setVisible(true);  
       $this->docview->setVisible(false);       
       $this->bookrep->bookrephtml->setText($html,true);       
       $this->goAnkor('bookrep') ;
    }    
  
      
}

/**
 *  Источник  данных  для   списка  документов
 */
class IOStateListDataSource implements \Zippy\Interfaces\DataSource
{
    private $page;

    public function __construct($page) {
        $this->page = $page;
    }

    private function getWhere() {
        $user = System::getUser();

        $conn = \ZDB\DB::getConnect();

        $where = "  1=1 ";
        $from = $this->page->filter->from->getDate();
        $to = $this->page->filter->to->getDate();

        if ($from > 0) {
            $where .= " and  d.document_date >= " . $conn->DBDate($from);
        }
        if ($to > 0) {
            $where .= " and  d.document_date <= " . $conn->DBDate($to);
        }

        if($this->page->_tvars['bmode'] ==true) {
             $ids= implode(',', array_keys($this->page->_ptlist) );
             $where .= " and ( coalesce(iotype,0) in ({$ids})  or    d.content  like '%<iniostate>1</iniostate>%' )  and d.content not like '%<outiostate>1</outiostate>%'  " ; 
            
        } else {
            $where .= "  coalesce(iotype,0) not in (30,31,80,81,82)  ";
    
            $author = $this->page->filter->fuser->getValue();
            $type = $this->page->filter->ftype->getValue();

            if ($type > 0) {
                $where .= " and coalesce(iotype,0)=" . $type;
            }


            if ($author > 0) {
                $where .= " and d.user_id=" . $author;
            }
         
        }

        
        $id = \App\System::getBranch(); //если  выбран  конкретный
        if ($id > 0) {

            return "d.branch_id = ".$id;
        }        
 
        return $where;
    }

    public function getItemCount() {
        $conn = \ZDB\DB::getConnect();
        $sql = "select coalesce(count(*),0) from documents_view  d left join iostate_view i on d.document_id = i.document_id where " . $this->getWhere();
        H::log($sql);
        return $conn->GetOne($sql);
    }

    public function getItems($start, $count, $sortfield = null, $asc = null) {

        $conn = \ZDB\DB::getConnect();
        $sql = "select  i.iotype,i.amount, d.username,  d.document_id,  d.document_number,d.document_date,i.amount  from documents_view  d left join iostate_view i on d.document_id = i.document_id where " . $this->getWhere() . " order  by d.document_date   ";
        if ($count > 0) {
            $limit =" limit {$start},{$count}";
            $sql .= $limit;
        }
     
        $docs =  Document::findBySql($sql);

        return $docs;
    }

    public function getItem($id) {

    }

}
