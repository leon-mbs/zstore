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
        $this->add(new ClickLink("copy", $this, "onInsert"));
        $this->add(new ClickLink("link", $this, "onInsert"));
    }

    public function onInsert($sender) {
        $user = System::getUser();
        $node = Node::getFirst("user_id=" . $user->user_id, "node_id  ");
        if ($node == null) {
            $this->setError("noroot");

            return;
        }

        if ($sender->id == "copy") {
            $this->_topic->topic_id = 0;
            $this->_topic->save();
        }
        $this->_topic->addToNode($node->node_id);
        App::Redirect("App\\Modules\\Note\\Pages\\Main");
    }

}
