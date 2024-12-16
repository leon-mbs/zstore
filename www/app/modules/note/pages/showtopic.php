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

    public function __construct($topic_id,$hash="") {

        parent::__construct();
        $topic_id = intval($topic_id);

        $this->_topic = \App\Modules\Note\Entity\Topic::load($topic_id);
        if ($this->_topic == null) {
            http_response_code(404) ;
            die;
        }

        if ($this->_topic->ispublic == 0) {   //приватный
            http_response_code(404) ;
            die;
        }
        
        $h=md5($topic_id . \App\Helper::getSalt())  ;
        if($h !== $hash) {
            http_response_code(404) ;
            die;
        }
        
        /*
        if ($this->_topic->isout == 0) {
            $user_id = System::getUser()->user_id;
            if ($user_id == 0) { //незалогиненый
                http_response_code(404) ;
                die;
            }
        }
        */
        $this->add(new Label("title", $this->_topic->title, true));
        $this->add(new Label("detail", $this->_topic->detail, true));
    }


}
