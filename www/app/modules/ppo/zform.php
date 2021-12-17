<?php

namespace App\Modules\PPO;

use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Form\CheckBox;

class ZForm extends \App\Pages\Base
{

    private $pos;

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
    }

    public function OnRefresh($sender) {
        $pos_id = $this->filter->pos->getValue();
        if ($pos_id == 0) {
            return;
        }
        $this->pos = \App\Entity\Pos::load($pos_id);

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
    }

    public function OnClose($sender) {

        $ret=true;
        if($this->stat->onlyshift->isChecked()==false){
           $ret = $this->zform();    
        }
        
        if ($ret == true) {
            //$this->closeshift();
            $pos = \App\Entity\Pos::load($this->pos->pos_id);
  
            $ret = \App\Modules\PPO\PPOHelper::shift($this->pos->pos_id, false);

            if ($ret['success'] == false && $ret['doclocnumber'] > 0) {
                //повторяем для  нового номера
                $this->pos->fiscdocnumber = $ret['doclocnumber'];
                $this->pos->save();
                $ret = \App\Modules\PPO\PPOHelper::shift( $this->pos->pos_id, false);

            }

            if ($ret['success'] != true) {
                $this->setError($ret['data']);
            } else {
                \App\Modules\PPO\PPOHelper::clearStat($this->pos->pos_id);
                $this->setSuccess('Смена  закрыта');
                $this->stat->clean();
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

        $ret = \App\Modules\PPO\PPOHelper::zform($this->pos->pos_id, $stat, $rstat);
        if (strpos($ret['data'], 'ZRepAlreadyRegistered')) {
            return true;
        }
        if ($ret['success'] == false && $ret['docnumber'] > 0) {
            //повторяем для  нового номера
            $this->pos->fiscdocnumber = $ret['docnumber'];
            $this->pos->save();
            $ret = \App\Modules\PPO\PPOHelper::zform($this->pos->pos_id, $stat, $rstat);
        }
        if ($ret['success'] == false) {
            $this->setError($ret['data']);
            return false;
        } else {

            if ($ret['docnumber'] > 0) {
                $this->pos->fiscdocnumber = $ret['doclocnumber'] + 1;
                $this->pos->save();
                return true;
            } else {
                $this->setError("ppo_noretnumber");
                return false;
            }
        }



    }

    public function closeshift() {


        $ret = \App\Modules\PPO\PPOHelper::shift($this->pos->pos_id, false);
        if ($ret['success'] == false && $ret['docnumber'] > 0) {
            //повторяем для  нового номера
            $pos->fiscdocnumber = $ret['docnumber'];
            $pos->save();
            $ret = \App\Modules\PPO\PPOHelper::shift($this->pos->pos_id, false);
        }
        if ($ret['success'] == false) {
            $this->setError($ret['data']);
            return false;
        } else {
            $this->setSuccess("ppo_shiftclosed");
            if ($ret['docnumber'] > 0) {
                $this->pos->fiscdocnumber = $ret['doclocnumber'] + 1;
                $this->pos->save();
            } else {
                $this->setError("ppo_noretnumber");
                return;
            }
            \App\Modules\PPO\PPOHelper::clearStat($this->pos->pos_id);
        }


        return true;
    }

}
