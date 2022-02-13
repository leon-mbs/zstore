<?php

namespace App\Modules\PPO;

use App\Application as App;
use App\Entity\MoneyFund;
use App\Helper as H;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Form\Date;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Label;
use Zippy\Html\Link\RedirectLink;
use Zippy\Html\Panel;

/**
 * Журнал  z - отчетов
 */
class ZList extends \App\Pages\Base
{

    public function __construct() {
        parent::__construct();
     

        $this->add(new Form('filter'))->onSubmit($this, 'OnSubmit');
        $this->filter->add(new Date('from', time() - (7 * 24 * 3600)));
        $this->filter->add(new Date('to', time()));
        $this->filter->add(new TextInput('pos'));
        $this->filter->add(new TextInput('doc'));
   
     
    }

    public function OnSubmit($sender) {


  
    }

   

}
