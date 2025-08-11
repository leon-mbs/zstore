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
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Link\BookmarkableLink;
use App\Application as App;

/**
 * журнал доходы  и расходы
 */
class IOState extends \App\Pages\Base
{
    private $_doc    = null;
    private $_ptlist = null;
    

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
        
        $this->_ptlist = \App\Entity\IOState::getTypeList();

        $this->add(new Form('filter'))->onSubmit($this, 'filterOnSubmit');
        $this->filter->add(new DropDownChoice('fuser', \App\Entity\User::findArray('username', 'disabled<>1', 'username'), 0));
        $this->filter->add(new DropDownChoice('ftype', $this->_ptlist, 0));
        $this->filter->add(new Date('from',strtotime('-1 month')));
        $this->filter->add(new Date('to'));

        
        $this->add(new Form('docform'))->onSubmit($this, 'addOnSubmit');
        $this->docform->add(new TextInput('docnumber'));
     
        
        $doclist = $this->add(new DataView('doclist', new IOStateListDataSource($this), $this, 'doclistOnRow'));

        $this->add(new Pager('pag', $doclist));
        $doclist->setPageSize(H::getPG());

        $this->add(new \App\Widgets\DocView('docview'))->setVisible(false);

       
        
        $this->add(new ClickLink('csv', $this, 'oncsv'));
        $this->add(new ClickLink('bmode', $this, 'onmode')) ;
        $this->add(new ClickLink('jmode', $this, 'onmode')) ;

        $this->_ptlist[0] = '';
       
        $this->update(); 
    }

    public function onmode($sender) {
        
        $this->_tvars['bmode'] = $sender->id=='bmode';
        if($this->_tvars['bmode'] ) {
            $dt = new \App\DateTime();
            $dt->subMonth(1)   ;
            $from = $dt->startOfMonth()->getTimestamp();
            $to = $dt->endOfMonth()->getTimestamp();
            $this->filter->from->setDate($from); 
            $this->filter->to->setDate($to); 
             
        }  
        $this->update(); 
    }
   
    public function filterOnSubmit($sender) {
        $this->docview->setVisible(false);
        $this->update();
    }
    
    public function addOnSubmit($sender) {
        $dm = trim($this->docform->docnumber->getText() );
        $doc = Document::getFirst("  document_number = ".Document::qstr($dm) )  ;
        if($doc==null) {
            $this->setError('Документ не знайдено') ;
            return;
        }
        $doc->setHD('iniostate',1);
        $doc->setHD('outiostate',0);
        $doc->save();
        $this->setSuccess('Додано') ;
        $this->docform->docnumber->setText('');
        $this->docview->setVisible(false);
        $this->update();
    }

    private function update( ) {
     
        $this->doclist->Reload(); 
        
        $this->_tvars['totalin'] = 0;
        $this->_tvars['totalout'] = 0;
        foreach($this->doclist->getDataSource()->getItems(-1, -1) as $doc) {
           if($doc->iotype < 30) {
               $this->_tvars['totalin']  += $doc->amount;   
            }  else {
               $this->_tvars['totalout'] += (0-$doc->amount);
            }            
        }
   
        
  
        $this->_tvars['totalin']   = H::fa($this->_tvars['totalin']   );
        $this->_tvars['totalout']  = H::fa($this->_tvars['totalout']   );
        $this->_tvars['totaldiff'] = H::fa($this->_tvars['totalin'] - $this->_tvars['totalout'] );
        
    }

    public function doclistOnRow(\Zippy\Html\DataList\DataRow $row) {
        $doc = $row->getDataItem();

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
        } else {
           $row ->amountout->setText(H::fa(0-$doc->amount));
        }
  
        
        
    }

    //просмотр
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
            if($doc->iotype < 30)) {
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

        $where = "  iotype not in (30,31,80,81,82)  ";
        $from = $this->page->filter->from->getDate();
        $to = $this->page->filter->to->getDate();

        if ($from > 0) {
            $where .= " and  d.document_date >= " . $conn->DBDate($from);
        }
        if ($to > 0) {
            $where .= " and  d.document_date <= " . $conn->DBDate($to);
        }

        if($this->page->_tvars['bmode'] ==true) {
             $where .= " and ( iotype in (1,3,50,51,54,59,60)  or    d.content  like '%<iniostate>1</iniostate>%' )  and d.content not like '%<outiostate>1</outiostate>%'  " ; 
        } else {
            $author = $this->page->filter->fuser->getValue();
            $type = $this->page->filter->ftype->getValue();

            if ($type > 0) {
                $where .= " and iotype=" . $type;
            }


            if ($author > 0) {
                $where .= " and d.user_id=" . $author;
            }
         
        }

        $c = \App\ACL::getBranchConstraint();
        if (strlen($c) > 0) {
            $where .= " and d." . $c;
        }

        if ($user->rolename != 'admins') {
            if ($user->onlymy == 1) {

                $where .= " and d.user_id  = " . $user->user_id;
            }

            $where .= " and d.meta_id in({$user->aclview}) ";
        }
        return $where;
    }

    public function getItemCount() {
        $conn = \ZDB\DB::getConnect();
        $sql = "select coalesce(count(*),0) from documents_view  d join iostate_view i on d.document_id = i.document_id where " . $this->getWhere();
        return $conn->GetOne($sql);
    }

    public function getItems($start, $count, $sortfield = null, $asc = null) {

        $conn = \ZDB\DB::getConnect();
        $sql = "select  i.*,d.username,d.meta_id,d.document_number,d.document_date,i.amount  from documents_view  d join iostate_view i on d.document_id = i.document_id where " . $this->getWhere() . " order  by d.document_date   ";
        if ($count > 0) {
            $limit =" limit {$start},{$count}";
            $sql .= $limit;
        }
     
        $docs = \App\Entity\IOState::findBySql($sql);

        return $docs;
    }

    public function getItem($id) {

    }

}
