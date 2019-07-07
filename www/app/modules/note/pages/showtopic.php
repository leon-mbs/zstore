<?php

namespace App\Modules\Note\Pages;

use \App\Application as App;
use \App\Helper;
use \App\System;
use \Zippy\Html\Label;

/**
 * страница показа  топика  по  публичной ссылке
 */
class ShowTopic extends \App\Pages\Base 
{

    public function __construct($topic_id) {
        $topic = \App\Modules\Note\Entity\Topic::load($topic_id);
        if($topic == null){
            App::Redirect404();
            return;
        }
        
        if($topic->ispublic <> 1){
            $user = System::getUser();
            if ($user->user_id != $topic->user_id) {
                 App::Redirect404();
                 return;    
            }
        }
        
        $this->add(new Label("title", $topic->title, true));
        $this->add(new Label("content", $topic->content, true));
    }

}
