<?php

namespace App\Modules\Paperless;

use App\System;
use App\Helper as H;
use App\Entity\Doc\Document;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Form\TextInput;
use Zippy\WebApplication as App;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Link\BookmarkableLink;
use Zippy\Html\DataList\DataView;
use Zippy\Html\DataList\ArrayDataSource;

class DocList extends \App\Pages\Base
{

     

    public function __construct() {
        parent::__construct();

        if (strpos(System::getUser()->modules, 'paperless') === false && System::getUser()->rolename != 'admins') {
            System::setErrorMsg(H::l('noaccesstopage'));

            App::RedirectError();
            return;
        }
 
    }

 

}
