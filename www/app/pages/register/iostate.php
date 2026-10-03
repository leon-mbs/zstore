<?php

namespace App\Pages\Register;

use App\Entity\Customer;
use App\Entity\Doc\Document;
use App\Entity\Pay;
use App\Helper as H;
use App\System;
use Zippy\Html\DataList\ArrayDataSource;
use Zippy\Html\DataList\DataView;
use Zippy\Html\DataList\Pager;
use Zippy\Html\Form\AutocompleteTextInput;
use Zippy\Html\Form\Date;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Form\SubmitButton;
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
    public array $_list = [];
    private array $_ptlist = [];
    private array $_ptlistb = []; // без закупки
    

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
        $this->_ptlistb = \App\Entity\IOState::getTypeListBook();
        
        unset($this->_ptlistb[\App\Entity\IOState::TYPE_BASE_OUTCOME]) ;
         
        $this->add(new Form('filter'));
        $this->filter->add(new DropDownChoice('fuser', \App\Entity\User::findArray('username', 'disabled<>1', 'username'), 0));
        $this->filter->add(new DropDownChoice('ftype', $this->_ptlist, 0));
      
        $dt = new \App\DateTime();
        $dt->subMonth(1)   ;
        $from = $dt->startOfMonth()->getTimestamp();
        $to = $dt->endOfMonth()->getTimestamp();
        $this->filter->add(new Date('from',$from));
        $this->filter->add(new Date('to',$to));
        $this->filter->add(new SubmitButton('bfilter' ))->onClick($this, 'filterOnSubmit');
              
        

        $this->add(new Form('docform'))->onSubmit($this, 'addOnSubmit');
        $this->docform->add(new TextInput('docnumber'));
        $this->docform->add(new TextInput('docamount'));
        $this->docform->add(new Date('docdate'));
        $this->docform->add(new DropDownChoice('docio', $this->_ptlist, 0));
         
        
        $doclist = $this->add(new DataView('doclist', new ArrayDataSource($this,'_list'), $this, 'doclistOnRow'));

        
        $this->add(new Pager('pag', $doclist));
        $doclist->setPageSize(H::getPG());

        $this->add(new \App\Widgets\DocView('docview'))->setVisible(false);

       
        
        $this->add(new ClickLink('csv', $this, 'oncsv'));
        $this->add(new ClickLink('viewbook', $this, 'onviewbook'));
        $this->add(new ClickLink('bmode', $this, 'onmode')) ;
        $this->add(new ClickLink('jmode', $this, 'onmode')) ;

        
        $this->add(new Panel('bookrep' ))->setVisible(false);
       
        $this->bookrep->add(new Label('bookrephtml' ));
     
       
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
        $conn = \ZDB\DB::getConnect();
        $sql = "select i.id, i.iotype,i.amount, d.username, d.content,  d.document_id,  d.meta_name, d.document_number, date(i.document_date) as document_date ,i.amount    " ;
        $sql .= " from documents_view  d   join iostate_view i on d.document_id = i.document_id where 1=1 " ;
        $from = $this->filter->from->getDate();
        $to = $this->filter->to->getDate();
        $ttn=[] ;
        
        if($this->_tvars['bmode'] ==true) {
            $sql .= " and coalesce(iotype,0) <> 50    " ; 
        }        
        $sqlttn="select coalesce(sum(e.partion * e.quantity),0) as sm,e.document_id, GROUP_CONCAT(DISTINCT e.item_id SEPARATOR ',') AS items  from entrylist_view e  where e.item_id > 0 and e.tag = -1 ";
        
        $sql .= " and  i.document_date >= " . $conn->DBDate($from);
        $sql .= " and  i.document_date <= " . $conn->DBDate($to);
        $sqlttn .= " and  e.document_date >= " . $conn->DBDate($from);
        $sqlttn .= " and  e.document_date <= " . $conn->DBDate($to);
 
        if($this->_tvars['bmode'] ==true) {
             $ids= implode(',', array_keys($this->_ptlistb) );
             $sql .= " and ( coalesce(iotype,0) in ({$ids})  or    d.content  like '%<iniostate>1</iniostate>%' )  and d.content not like '%<outiostate>1</outiostate>%'  " ; 
                 
            
        } else {
            $sql .= " and coalesce(iotype,0) not in (30,31,80,81,82,0)  ";
    
            $author = $this->filter->fuser->getValue();
            $type = $this->filter->ftype->getValue();

            if ($type > 0) {
                $sql .= " and coalesce(iotype,0)=" . $type;
            }


            if ($author > 0) {
                $sql .= " and d.user_id=" . $author;
            }
         
        }
      
        
        $id = \App\System::getBranch(); //если  выбран  конкретный
        if ($id > 0) {

             $sql .= " and  d.branch_id = ".$id;
             $sqlttn .= " and  d.branch_id = ".$id;
        }        
        $sql .= " order  by i.document_date   ";
        
        if($this->_tvars['bmode'] ==true) {
            $sqlttn .= " GROUP BY e.document_id  " ;
            foreach(  $conn->Execute($sqlttn) as $row){
               $d = new \App\DataItem($row,'document_id');
               
               $doc = Document::load( $row['document_id']);
               $doc->iotype=50;
               $doc->outcome=abs( $row['sm']);
               $doc->amount=abs( $row['sm']);
               $doc->items= $row['items'];    
 
               $ttn[$doc->document_id] = $doc;
            }
            
           
            
        } 
        
        $this->_list=[];
        foreach(Document::findBySql($sql) as $doc){
            if($doc->getHD('iniostate',0)==1){
              $doc->iotype= $doc->getHD('iniostatetype',0) ; 
              $doc->amount= $doc->getHD('iniostateamount',0) ; 
            }             
            if($doc->iotype < 30) {
               $doc->income = $doc->amount; 
               $doc->outcome = 0; 
            } else {
               $doc->income = 0; 
               $doc->outcome = 0-$doc->amount; 
            }     
            
            if(isset($ttn[$doc->document_id]))  {
               $doc->outcome += $ttn[$doc->document_id]->outcome  ;  
               $doc->items= $doc->items;
               $doc->iotypeo= 50;
               unset($ttn[$doc->document_id]);
            }
              
            $this->_list[$doc->document_id] =  $doc;
        };
        foreach($ttn as $t) {
           $this->_list[$t->document_id] =  $t;  
        }
        unset($ttn)  ;
       
        usort( $this->_list,function ($a, $b) {
            return $a->document_date > $b->document_date;
        }) ;
        
      
        $this->reload();
        
                                    
          
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
       
        if($doc->income > 0) {
           $row ->amountin->setText(H::fa($doc->income));
           $this->_tvars['totalin']  += $doc->income;   
        } 
        if($doc->outcome > 0) {        
           $row ->amountout->setText(H::fa( $doc->outcome));
           $this->_tvars['totalout'] += ( $doc->outcome);
        }                
    
    }

    public function reload( ) {
        $this->_tvars['totalin'] = 0;
        $this->_tvars['totalout'] = 0;
      
        $this->doclist->Reload();  
        $this->_tvars['totalin']   = H::fa($this->_tvars['totalin']   );
        $this->_tvars['totalout']  = H::fa($this->_tvars['totalout']   );
        $this->_tvars['totaldiff'] = H::fa($this->_tvars['totalin'] - $this->_tvars['totalout'] );
        $this->bookrep->setVisible(false);  
        
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
          
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('Прибутки'); // Optionally set a title
     
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Видатки');
         

        $i1 = 0;
        $i2 = 0;
        foreach ($this->_list as $doc) {
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
      
       $conn = \ZDB\DB::getConnect();
    
       $list=[];
       foreach($this->_list  as $r){
          
           $d= H::fd(  $r->document_date )  ;
           if(!isset($list[$d])) $list[$d] =[];
           
           $list[$d][]= $r;
       }
       
       $tc2=0;$tc3=0;$tc4=0;$tc6=0;$tc7=0;$tc8=0;$tc9=0;$tc10=0;$tc11=0;
       $header=[];
       $header['rows']=[];
       
       foreach($list as $d=>$iolist) {
           $row=[];
           $docs=[];
           $row['date']  = $d;
           $c2=0; $c3=0; $c4=0; $c5=0;$c6=0;$c7=0;$c8=0;$c9=0;$c10=0;$c11=0;
           foreach($iolist as $io) {  
             // $doc=Document::load($io->document_id)  ;
              
              if($io->meta_name=='ReturnIssue') {  //возврат
                 $c3 +=  abs( $io->amount ); 
                 continue; 
              }              
              if($io->meta_name=='Invoice') {  //предоплата
                 $c3 +=  abs( $io->income ); 
                 continue; 
              }              
            
              if($io->iotype == 1 || $io->iotype == 2 || $io->iotype == 3 )  { //доходы
                 $c2 +=  abs( $io->income );
                // continue; 
              }
       
              //затраты
              if($io->iotype == 50 || intval( $io->iotypeo )  == 50  )  {   //закупка
                 $c6 +=  abs( $io->outcome );
                 if(strlen($io->items ??'')>0) {
                     $sql=" SELECT distinct document_number,document_date FROM  documents  WHERE document_id IN(SELECT document_id FROM entrylist_view where  tag= -2 and  item_id in ({$io->items}) )    ORDER BY  document_date desc  limit 0, " . count(explode(',',$io->items)); 
              
                     foreach($conn->Execute($sql) as $d) {
                         $docs[$d['document_number']]=$d['document_number'];
                     }
                 }
                  
              }     
              if($io->iotype == 54)  {   //зарплата
                 $c7 +=  abs( $io->outcome );
              }     
              if( in_array($io->iotype,[55,70,71])   )  {   //налоги
                 $c8 +=  abs( $io->outcome );
              }     
              if(in_array($io->iotype,[53,57,60,63]))  {   
                 $c9 +=  abs($io->outcome);
              }     
              
                 
              if($io->iotype == 67)  {  
                 $c10 +=  abs( $io->outcome );
              }        
              
                          
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
          
           $tc2 += doubleval($row['c2'] );
           $tc3 += doubleval($row['c3'] );
           $tc4 += doubleval($row['c4'] );
           $tc6 += doubleval($row['c6'] );
           $tc7 += doubleval($row['c7'] );
           $tc8 += doubleval($row['c8'] );
           $tc9 += doubleval($row['c9'] );
           $tc10 += doubleval($row['c10'] );
           $tc11 += doubleval($row['c11'] );

           $header['rows'][]=$row;
           
       }
       
       
       unset($list) ; 
     
       $header['tc2'] = number_format($tc2, 2, '.', '') ;
       $header['tc3'] = number_format($tc3, 2, '.', '') ;
       $header['tc4'] = number_format($tc4, 2, '.', '') ;
       $header['tc6'] = number_format($tc6, 2, '.', '') ;
       $header['tc7'] = number_format($tc7, 2, '.', '') ;
       $header['tc8'] = number_format($tc8, 2, '.', '') ;
       $header['tc9'] = number_format($tc9, 2, '.', '') ;
       $header['tc10'] = number_format($tc10, 2, '.', '') ;
       $header['tc11'] = number_format($tc11, 2, '.', '') ;

       
       $from = $this->filter->from->getDate();
       $to = $this->filter->to->getDate();
     
       $firm = H::getFirmData() ;
       $header['firmname']  = $firm['firm_name']  ;          
       $header['firmcode']  = ''  ;   
       if(strlen($firm['tin']??'')>0) {
          $header['firmcode']  = "ЄДРПОУ ". $firm['tin']  ;   
       }      
       if(strlen($firm['inn']??'')>0) {
          $header['firmcode']  = "ІПН ". $firm['inn']  ;   
       }      
              
       $header['from']  = H::fd($from) ;          
       $header['to']  = H::fd($to)   ;          
       
       $report = new \App\Report('iobook.tpl');

       $html = $report->generate($header);
  
  
       \App\Session::getSession()->printform = "<html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\"></head><body>" . $html . "</body></html>";

       $this->bookrep->setVisible(true);  
       $this->docview->setVisible(false);       
       $this->bookrep->bookrephtml->setText($html,true);       
       $this->goAnkor('bookrep') ;
    }    
  
      
}
 