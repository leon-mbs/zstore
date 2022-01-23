<?php

namespace App\Modules\PPO;

use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\DataList\ArrayDataSource;
use Zippy\Html\DataList\DataView;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Binding\PropertyBinding as Prop;
use \App\Helper as H;

class ZForm extends \App\Pages\Base
{

    private $_pos;
    public $_list;

    public function __construct() {
        parent::__construct();

        $this->add(new Form('filter'))->onSubmit($this, 'OnRefresh');

        $this->filter->add(new DropDownChoice('pos', \App\Entity\Pos::findArray('pos_name', ''), 0));

        $this->add(new Form('stat'))->onSubmit($this, 'OnClose');
        $this->stat->add(new TextInput('nal'));
        $this->stat->add(new TextInput('bnal'));
        $this->stat->add(new TextInput('credit'));
        $this->stat->add(new TextInput('prepaid'));
        $this->stat->add(new TextInput('retnal'));
        $this->stat->add(new TextInput('retbnal'));
        $this->stat->add(new TextInput('cnt'));
        $this->stat->add(new TextInput('retcnt'));
        $this->stat->add(new CheckBox('onlyshift'));
        $this->stat->setVisible(false);
     
        $this->add(new DataView('list', new ArrayDataSource(new Prop($this, '_list')), $this, 'OnRow'));
       
        
    }

    public function OnRefresh($sender) {
        $pos_id = $this->filter->pos->getValue();
        if ($pos_id == 0) {
            return;
        }
        $this->_pos = \App\Entity\Pos::load($pos_id);

        $this->stat->setVisible(true);

        
        $data = \App\Modules\PPO\PPOHelper::getStat($pos_id, false);
    

        $this->stat->nal->setText($data['amount0']);
        $this->stat->bnal->setText($data['amount1']);
        $this->stat->credit->setText($data['amount2']);
        $this->stat->prepaid->setText($data['amount3']);
        $this->stat->cnt->setText($data['cnt']);

       
        $data = \App\Modules\PPO\PPOHelper::getStat($pos_id, true);
   

        $this->stat->retnal->setText($data['amount0']);
        $this->stat->retbnal->setText($data['amount1']);
        $this->stat->retcnt->setText($data['cnt']);
        
        
        $this->_list =  \App\Modules\PPO\PPOHelper::getStatList($pos_id );
        $this->list->Reload() ;
        
    }

    public  function onRow($row){
        $item = $row->getDataItem();
        
        
        $amount0=0;
        $amount1=0;
        $amount0r=0;
        $amount1r=0;
        $amount2=$item->amount2;
        $amount3=$item->amount3;

        if($item->checktype == "3" ){
          $amount0r=$item->amount0;
          $amount1r=$item->amount1;
            
        } else {
          $amount0=$item->amount0;
          $amount1=$item->amount1;
            
        }

        
          
        
        $row->add(new Label("docnumber",$item->document_number));
        $row->add(new Label("amount0", H::fa($amount0)) );
        $row->add(new Label("amount1",H::fa($amount1)));
        $row->add(new Label("amount2",H::fa($amount2)));
        $row->add(new Label("amount3",H::fa($amount3)));
        $row->add(new Label("amount0r",H::fa($amount0r)));
        $row->add(new Label("amount1r",H::fa($amount1r)));
        $row->add(new ClickLink("del", $this,"onDel" ));
      
        $row->add(new ClickLink("fisc", $this,"onFisc" ))->setVisible($item->tag==1);
        if($item->tag==1) {
            $this->setWarn('ppo_isnofisc')  ;
        }
        
    }
    
    
    public function onFisc($sender) {
           $item = $sender->getOwner()->getDataItem();
           $doc=\App\Entity\Doc\Document::getFirst("document_number=" . \App\Entity\Doc\Document::qstr($item->document_number) ) ;
           if($doc==null){
              
              return ; 
           }
            $doc->headerdata["fiscalnumberpos"]  = $this->_pos->fiscalnumber;
     
 
            $ret = \App\Modules\PPO\PPOHelper::check($doc);    
       
            
            if ($ret['success'] == false && $ret['doclocnumber'] > 0) {
                //повторяем для  нового номера
                $this->_pos->fiscdocnumber = $ret['doclocnumber'];
                $this->_pos->save();
                $ret = \App\Modules\PPO\PPOHelper::check($doc);
            }
            if ($ret['success'] == false) {
                $this->setErrorTopPage($ret['data']);
                 
                return;
            } else {                                  
                
                if ($ret['docnumber'] > 0) {
                    $this->_pos->fiscdocnumber = $ret['doclocnumber'] + 1;
                    $this->_pos->save();
                    $doc->headerdata["fiscalnumber"] = $ret['docnumber'];
                } else {
                    $this->setError("ppo_noretnumber");
                 
                    return;
                }
            }   
            $doc->save();
   
   
           $this->OnRefresh($this->filter) ;       
        
    }
   public function onDel($sender) {
           $item = $sender->getOwner()->getDataItem();
           \App\Modules\PPO\PPOHelper::delStat($item->zf_id );
   
           $this->OnRefresh($this->filter) ;       
        
    }
    public function OnClose($sender) {

        $ret=true;
        if($this->stat->onlyshift->isChecked()==false){
           $ret = $this->zform();    
        }
        
        if ($ret == true) {
            //$this->closeshift();
            $pos = \App\Entity\Pos::load($this->_pos->pos_id);
  
            $ret = \App\Modules\PPO\PPOHelper::shift($this->_pos->pos_id, false);

            if ($ret['success'] == false && $ret['docnumber'] > 0) {
                //повторяем для  нового номера
                $this->_pos->fiscdocnumber = $ret['docnumber'];
                $this->_pos->save();
                $ret = \App\Modules\PPO\PPOHelper::shift( $this->_pos->pos_id, false);

            }

            if ($ret['success'] != true) {
                $this->setErrorTopPage($ret['data']);
            } else {
                \App\Modules\PPO\PPOHelper::clearStat($this->_pos->pos_id);
                $this->setSuccess('Смена  закрыта');
                $this->stat->clean();
                $this->OnRefresh($this->filter) ;
                
            }

        };
    }

    public function zform() {

        $stat = array();
        $rstat = array();

        $stat['amount0'] = $this->stat->nal->getText();
        $stat['amount1'] = $this->stat->bnal->getText();
        $stat['amount2'] = $this->stat->credit->getText();
        $stat['amount3'] = $this->stat->prepaid->getText();
        $stat['cnt'] = $this->stat->cnt->getText();

        $rstat['amount0'] = $this->stat->retnal->getText();
        $rstat['amount1'] = $this->stat->retbnal->getText();
        $rstat['amount2'] = 0;
        $rstat['amount3'] = 0;
        $rstat['cnt'] = $this->stat->retcnt->getText();

        $ret = \App\Modules\PPO\PPOHelper::zform($this->_pos->pos_id, $stat, $rstat);
        if (strpos($ret['data'], 'ZRepAlreadyRegistered')) {
            return true;
        }
        if ($ret['success'] == false && $ret['docnumber'] > 0) {
            //повторяем для  нового номера
            $this->_pos->fiscdocnumber = $ret['docnumber'];
            $this->_pos->save();
            $ret = \App\Modules\PPO\PPOHelper::zform($this->_pos->pos_id, $stat, $rstat);
        }
        if ($ret['success'] == false) {
            $this->setErrorTopPage($ret['data']);
            return false;
        } else {

            if ($ret['docnumber'] > 0) {
                $this->_pos->fiscdocnumber = $ret['doclocnumber'] + 1;
                $this->_pos->save();
                return true;
            } else {
                $this->setError("ppo_noretnumber");
                return false;
            }
        }



    }
    /*
    public function closeshift() {


        $ret = \App\Modules\PPO\PPOHelper::shift($this->_pos->pos_id, false);
        if ($ret['success'] == false && $ret['docnumber'] > 0) {
            //повторяем для  нового номера
            $pos->fiscdocnumber = $ret['docnumber'];
            $pos->save();
            $ret = \App\Modules\PPO\PPOHelper::shift($this->_pos->pos_id, false);
        }
        if ($ret['success'] == false) {
            $this->setErrorTopPage($ret['data']);
            return false;
        } else {
            $this->setSuccess("ppo_shiftclosed");
            if ($ret['docnumber'] > 0) {
                $this->_pos->fiscdocnumber = $ret['doclocnumber'] + 1;
                $this->_pos->save();
            } else {
                $this->setError("ppo_noretnumber");
                return;
            }
            \App\Modules\PPO\PPOHelper::clearStat($this->_pos->pos_id);
        }


        return true;
    }
    */
}
