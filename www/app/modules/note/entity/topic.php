<?php

namespace App\Entity;

/**
 *  Класс  инкапсулирующий топик
 * @table=topics
 * @view=topicsview
 * @keyfield=topic_id
 */
class Topic extends \ZCL\DB\Entity
{

    protected function init() {
        $this->topic_id = 0;
        $this->ispublic = 0;
    }

    /**
     * список топиков  для  узла
     * 
     * @param mixed $node_id
     */
    public static function findByNode($node_id) {
        return self::find("topic_id in (select topic_id from topicnode where node_id={$node_id})");
    }

    /**
     * удаление  топиков узла
     * 
     * @param mixed $node_id
     */
    public static function deleteByNode($node_id) {

        $conn = \ZCL\DB\DB::getConnect();

        $conn->Execute("delete from topicnode where node_id= {$node_id} ");

        $conn->Execute("delete from topics where topic_id not  in (select topic_id from topicnode)");
        $conn->Execute("delete from files where topic_id not  in (select topic_id from topicnode)");
    }

    /**
     * добавить  к  узлу
     * 
     * @param mixed $node_id
     */
    public function addToNode($node_id) {
        $conn = \ZCL\DB\DB::getConnect();
        $conn->Execute("insert into topicnode(topic_id,node_id)values({$this->topic_id},{$node_id})");
    }

    /**
     * удалить с  узла
     * 
     * @param mixed $node_id
     */
    public function removeFromNode($node_id) {
        $conn = \ZCL\DB\DB::getConnect();
        $conn->Execute("delete from topicnode where topic_id= {$this->topic_id} and node_id = {$node_id}");
    }

    /**
     * @see Entity
     * 
     */
    protected function beforeDelete() {
        $conn = \ZCL\DB\DB::getConnect();
        $conn->Execute("delete from files where topic_id=" . $this->topic_id);
        $conn->Execute("delete from topicnode where topic_id=" . $this->topic_id);

        return true;
    }

    /**
     * записать тэги
     * 
     * @param mixed $tags
     */
    public function saveTags($tags) {
        $conn = \ZCL\DB\DB::getConnect();
        $conn->Execute("delete from tags where topic_id=" . $this->topic_id);

        foreach ($tags as $tag) {
            $conn->Execute("insert tags (topic_id,tagvalue) values (" . $this->topic_id . "," . $conn->qstr($tag) . ")");
        }
    }

    /**
     * получить теги
     * 
     */
    public function getTags() {
        $tl = array();
        $conn = \ZCL\DB\DB::getConnect();
        $rc = $conn->GetCol("select distinct tagvalue from tags where topic_id=" . $this->topic_id);
        foreach($rc as $k=>$v){
           if(strlen($v))$tl[$k] = $v;
        }
        return $tl;
    }

    /**
     * получить  подсказки из существующих тегов
     * 
     */
    public function getSuggestionTags() {
        $conn = \ZCL\DB\DB::getConnect();
        return $conn->GetCol("select distinct tagvalue from tags where topic_id <> " . $this->topic_id);
    }

}
