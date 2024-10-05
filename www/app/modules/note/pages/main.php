<?php

namespace App\Modules\Note\Pages;

use App\Application as App;
use App\Modules\Note\Entity\Node;
use App\Modules\Note\Entity\Topic;
use App\Modules\Note\Entity\TopicNode;
use App\Modules\Note\Helper;
use App\System;
use ZCL\BT\Tree;
use Zippy\Html\Form\Button;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\TextArea;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Label;
use Zippy\Html\Link\BookmarkableLink;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Link\SubmitLink;
use Zippy\Html\Panel;
use App\Helper as H;

/**
 * Главная страница
 */
class Main extends \App\Pages\Base
{
    public function __construct() {
        parent::__construct();


    }


    public function onSearch($args, $post=null) {
        $cr = json_decode($post) ;
        $ret = array();
        $l = array();
        if($cr->fav == true) {
            $l = TopicNode::searchFav();
        }
        if(strlen($cr->tag) >0) {
            $l = TopicNode::searchByTag($cr->tag)    ;
        }
        if(strlen($cr->text) > 0) {
            $l =  TopicNode::searchByText($cr->text, $cr->type, $cr->title);
        }


        foreach($l as $t) {
            $ret[]=array(
                 "topic_id" =>$t->topic_id,
                 "node_id" =>$t->node_id,
                 "title" =>$t->title,
                 "hash" =>md5($t->topic_id . \App\Helper::getSalt()),
                 "nodes" =>$t->nodes()
            );
        }

        return json_encode($ret, JSON_UNESCAPED_UNICODE);

    }


    public function onDelFile($args, $post=null) {

        Helper::deleteFile($args[0]);


    }
    public function onAddFile($args, $post=null) {

        $file =  $_FILES['editfile']  ;

        if(strlen($file['tmp_name'] ?? '')==0) {
            return;
        }

        Helper::addFile($file, $args[0]);


    }


    public function onFav($args, $post=null) {


        $conn = \ZCL\DB\DB::getConnect();
        if ($args[1]=="true") {
            $conn->Execute("insert into note_fav (topic_id,user_id) values ({$args[0]}," . System::getUser()->user_id . ") ");
        } else {
            $conn->Execute("delete from note_fav where topic_id={$args[0]} and  user_id= " . System::getUser()->user_id);
        }

    }


    public function opTopic($args, $post=null) {
        if($args[0] =="delete") {
            Topic::delete($args[1]);
        }
        if($args[0] =="paste") {
            $node = Node::Load($args[2]);
            $topic = Topic::load($args[1]);

            if ($topic->acctype > 0 && $node->ispublic != 1) {
                return "Не можна додавати публічий топік до приватного вузла";

                 
            }
            $topic->removeFromNode($this->clipboard[3]);
            $topic->addToNode($this->tree->selectedNodeId());

        }
        if($args[0] =="pastel") {
            $node = Node::Load($args[2]);
            $topic = Topic::load($args[1]);

            if ($topic->acctype > 0 && $node->ispublic != 1) {
                return "Не можна додавати публічий топік до приватного вузла";


            }
            $newtopic = new Topic();
            $newtopic->user_id = System::getUser()->user_id;
            $newtopic->title = $topic->title;
            if ($node->node_id == $topic->node_id) {
                $newtopic->title = $topic->title . " (Копія)";
            }
            $newtopic->detail = $topic->detail;
            $newtopic->save();
            $newtopic->addToNode($node->node_id);

        }
        if($args[0] =="new") {

        }
        if($args[0] =="edit") {

        }

        return "";
    }

    public function saveTopic($args, $post=null) {

        $post = json_decode($post) ;
        if($args[0] > 0) {
            $topic = Topic::load($args[0]);
        } else {
            $topic = new  Topic();
            $topic->user_id = System::getUser()->user_id;

        }




        $topic->title = $post->title;
        $topic->detail = $post->data;
        $topic->acctype = $post->acctype;

        if (strlen($topic->title) == 0) {
            return 'Не введено заголовок';
        }


        $node = Node::load($args[1]);
        if ($topic->acctype > 0 && $node->ispublic != 1) {
            return "Не можна додавати приватний топік у публічний вузол" ;
        }
        $topic->updatedon = time();

        $topic->save();
        $tags = trim($post->tags) ;
        if(strlen($tags)>0) {
            $topic->saveTags(explode(";", $tags));
        }


        if ($args[0] == 0) {
            $topic->addToNode($args[1]);
        }



        return "";
    }

    public function opTree($args, $post=null) {
        if($args[0] =="new") {
            $id = $args[3] ;
            $parent = Node::load($id);
            $node = new Node();
            $node->pid = $id;
            $node->user_id = System::getUser()->user_id;
            $node->title = $args[1];
            $node->ispublic = $args[2]=="true" ? 1 : 0;
            if ($parent->ispublic == 0 && $node->ispublic == 1) {

                return "Не можна додавати публічний вузол до приватного" ;
            }

            $node->save();

        }
        if($args[0] =="edit") {
            $id = $args[3] ;
            $node = Node::load($id);
            $node->title = $args[1];
            $node->ispublic = $args[2]=="true" ? 1 : 0;
            $parent = Node::load($node->pid);
            if ($parent->ispublic == 0 && $node->ispublic == 1) {
                return "Не можна додавати публічний вузол до приватного" ;
            }

            $node->save();

        }
        if($args[0] =="delete") {
            Node::delete($args[1]);
            Topic::deleteByNode($args[1]);

        }
        if($args[0] =="paste") {
            $id = $args[1] ;
            $pid = $args[2] ;
            $dest = Node::load($pid);

            if ($pid == $id) {
                return;
            }
            $node = Node::load($id);
            if (strpos($dest->mpath, $node->mpath) === 0) {


                return "Не можна переміщувати в свого спадкоємця";
            }

            $node->moveTo($dest->node_id);



        }
        return "";
    }

    public function getTree($args, $post=null) {
        $expanded = strlen($args[0])>0 ? explode(",", $args[0]) : array();
        $tree = array();


        $user = System::getUser();
        $w = "ispublic = 1 or  user_id={$user->user_id}  ";
        if ($user->rolename == 'admins') {
            $w = '';
        }
        $itemlist = Node::find($w, "pid,mpath,title");
        if (count($itemlist) == 0) { //добавляем  корень
            $root = new Node();
            $root->title = "//";
            $root->user_id = 0;
            $root->ispublic = 1;
            $root->state = array('expanded'=>true) ;

            $root->save();

            $itemlist = Node::find($w, "pid,mpath,title");
        }

        $nodelist = array();
        foreach ($itemlist as $item) {


            $node = new Node2();
            $node->id = $item->node_id;
            $node->pid = $item->pid;
            $node->text = $item->title;
            $node->ispublic = $item->ispublic;

            if ($node->ispublic == 1) {
                $node->icon = 'fa fa-users fa-xs';
            } else {
                $node->icon = 'fa fa-lock fa-xs';
            }

            if(in_array($node->id, $expanded)) {    //восстанавливаем развернутые
                $node->state = array('expanded'=>true) ;

            }

            if((@$nodelist[$node->pid]) instanceof Node2) {
                if(!is_array($nodelist[$node->pid]->nodes)) {
                    $nodelist[$node->pid]->nodes  = array();
                }
                $nodelist[$node->pid]->nodes[]=$node;
            }
            $nodelist[$node->id] = $node;


        }
        foreach($nodelist as $n) {
            if($n->pid==0) {
                $tree[]=$n;
            }
        }



        return json_encode($tree, JSON_UNESCAPED_UNICODE);

    }

    public function loadTopic($args, $post=null) {
        $t = Topic::load($args[0]) ;


        $ret = array();
        $ret['acctype'] = $t->acctype;
        $ret['detail'] = $t->detail;
        $ret['tags'] = $t->getTags();
        $ret['files'] = array();
        $ret['sugs'] = $t->getSuggestionTags();
        foreach(Helper::findFileByTopic($t->topic_id) as $f) {
            $ret['files'][] = array('file_id'=>$f->file_id,
             'filename'=>$f->filename ,
             'link'=>"/loadfile.php?id=" . $f->file_id
             );
        }



        return json_encode($ret, JSON_UNESCAPED_UNICODE);

    }
  
    public function loadTopics($args, $post=null) {

        $conn = \ZCL\DB\DB::getConnect();
        $res = $conn->Execute("select topic_id from note_fav where user_id= " . System::getUser()->user_id);
        $favorites = array();
        foreach ($res as $r) {
            $favorites[] = $r['topic_id'];
        }


        $arr = array()  ;
        foreach(Topic::findByNode($args[0]) as $t) {
            $t->fav = in_array($t->topic_id, $favorites)  ;
            $arr[]=array(
             "title"=>$t->title,
             "fav"=>$t->fav,
             "topic_id"=>$t->topic_id,
             "hash" =>md5($t->topic_id . \App\Helper::getSalt()),
             
             );
        }

        return json_encode($arr, JSON_UNESCAPED_UNICODE);

    }



}

class Node2
{
    public $id;
    public $pid;
    public $icon;
    public $title;
    public $ispublic;
    public $nodes = null;
    public $state = array();
}
