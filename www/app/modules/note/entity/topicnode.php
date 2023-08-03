<?php

namespace App\Modules\Note\Entity;

/**
 *  Класс  инкапсулирующий  комбинацию  топик-узел (используется  для поиска)
 * @table=note_topicnodeview
 * @view=note_topicnodeview
 * @keyfield=tn_id
 */
class TopicNode extends \ZCL\DB\Entity
{
    protected function init() {
        $this->tn_id = 0;
    }

    /**
     * поиск по  тексту
     *
     * @param mixed $text
     */
    public static function searchByText($text, $type, $title) {
        global $logger;

        $arr = array();
        $text = trim($text);

        if ($type == 1) {
            $arr[] = Topic::qstr('%' . $text . '%');
        } else {
            $ta = explode(' ', $text);
            foreach ($ta as $a) {
                $arr[] = Topic::qstr('%' . $a . '%');
            }
        }
        $user = \App\System::getUser();
        $n = " note_topicnodeview.node_id in  ( select node_id from  note_nodes where  user_id={$user->user_id} or ispublic=1  )  and ";
        if ($user->rolename == 'admins') {
            $n = '';
        }
        $t = " note_topicnodeview.topic_id in  ( select topic_id from  note_topics where  user_id={$user->user_id} or acctype>0  )  and ";
        if ($user->rolename == 'admins') {
            $t = '';
        }


        $sql = "  select * from note_topicnodeview   where   {$n}  {$t}    (1=1   ";

        foreach ($arr as $t) {


            if ($title == false) {
                $sql .= "and ( title like {$t}  or content like {$t} )";
            } else {
                $sql .= " and  title like {$t} ";
            }
        }
        $sql .= ")  ";

        // $logger->info($sql);

        $list = TopicNode::findBySql($sql);

        return $list;
    }

    /**
     * поиск по  тегу
     *
     * @param mixed $tag
     */
    public static function searchByTag($tag) {
        $user = \App\System::getUser();
        $n = " note_topicnodeview.node_id in  ( select node_id from  note_nodes where  user_id={$user->user_id} or ispublic=1  )  and ";
        if ($user->rolename == 'admins') {
            $n = '';
        }
        $t = " note_topicnodeview.topic_id in  ( select topic_id from  note_topics where  user_id={$user->user_id} or acctype>0  )  and ";
        if ($user->rolename == 'admins') {
            $t = '';
        }

        $sql = "  select * from note_topicnodeview   where  {$n}  {$t}  topic_id in (select topic_id from note_tags where tagvalue  = " . Topic::qstr($tag) . " )  ";

        $list = TopicNode::findBySql($sql);

        return $list;
    }

    // поиск избранных
    public static function searchFav() {

        $sql = "  select * from note_topicnodeview   where topic_id in (select  topic_id from note_fav where user_id = " . \App\System::getUser()->user_id . ") ";

        $list = TopicNode::findBySql($sql);

        return $list;
    }

    /**
     * цепочка  названий узлов до  корня
     *
     */
    public function nodes() {

        $node = Node::load($this->node_id);
        $list = $node->getParents();
        $list = array_reverse($list);

        $path = implode(" > ", $list);
        return $path;
    }

}
