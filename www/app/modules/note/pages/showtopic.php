<?php

namespace App\Modules\Note\Pages;

use App\Application as App;
use App\Modules\Note\Entity\Node;
use App\System;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;

/**
 * страница показа  топика  по  публичной ссылке
 */
class ShowTopic extends \App\Pages\Base
{

    public $_topic;

    public function __construct($topic_id) {

        parent::__construct();

        $this->_topic = \App\Modules\Note\Entity\Topic::load($topic_id);
        if ($this->_topic == null) {
            App::Redirect404();
            return;
        }

        if ($this->_topic->acctype == 0) {   //приватный
            App::Redirect404();
            return;
        }
        if ($this->_topic->isout == 0) {
            $user_id = System::getUser()->user_id;
            if ($user_id == 0) { //незалогиненый
                App::Redirect404();
                return;
            }
        }

        $this->add(new Label("title", $this->_topic->title, true));
        $this->add(new Label("detail", $this->_topic->detail, true));
    }

     
}
