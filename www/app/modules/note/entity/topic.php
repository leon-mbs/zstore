<?php

namespace App\Modules\Note\Entity;

/**
 *  Класс  инкапсулирующий топик
 * @table=note_topics
 * @keyfield=topic_id
 */
class Topic extends \ZCL\DB\Entity
{

    protected function init() {
        $this->topic_id = 0;
        $this->acctype = 0;
    }

    protected function beforeSave() {
        parent::beforeSave();
        //упаковываем  данные в detail
        $this->content = "<content>";

        $this->content .= "<detail><![CDATA[{$this->detail}]]></detail>";
        $this->content .= "</content>";

        return true;
    }

    protected function afterLoad() {
        //распаковываем  данные из detail
        $xml = @simplexml_load_string($this->content);

        $this->detail = (string)($xml->detail[0]);

        parent::afterLoad();
    }

    /**
     * список топиков  для  узла
     *
     * @param mixed $node_id
     */
    public static function findByNode($node_id) {

        $user = \App\System::getUser();
        $w = "(user_id={$user->user_id} or acctype > 0  ) and ";
        if ($user->rolename == 'admins') {
            $w = '';
        }

        return self::find(" {$w} topic_id in (select topic_id from note_topicnode where  node_id={$node_id})");
    }

    /**
     * удаление  топиков узла
     *
     * @param mixed $node_id
     */
    public static function deleteByNode($node_id) {

        $conn = \ZCL\DB\DB::getConnect();

        $conn->Execute("delete from note_topicnode where node_id= {$node_id} ");

        $conn->Execute("delete from note_topics where topic_id not  in (select topic_id from note_topicnode)");
        $conn->Execute("delete from files where item_type=4 and  item_id not  in (select topic_id from note_topicnode)");
    }

    /**
     * добавить  к  узлу
     *
     * @param mixed $node_id
     */
    public function addToNode($node_id) {
        $conn = \ZCL\DB\DB::getConnect();
        $conn->Execute("insert into note_topicnode(topic_id,node_id)values({$this->topic_id},{$node_id})");
    }

    /**
     * удалить с  узла
     *
     * @param mixed $node_id
     */
    public function removeFromNode($node_id) {
        $conn = \ZCL\DB\DB::getConnect();
        $conn->Execute("delete from note_topicnode where topic_id= {$this->topic_id} and node_id = {$node_id}");
    }

    /**
     * @see Entity
     *
     */
    protected function beforeDelete() {
        $conn = \ZCL\DB\DB::getConnect();
        $conn->Execute("delete from files where item_type=4 and  item_id=" . $this->topic_id);
        $conn->Execute("delete from note_topicnode where topic_id=" . $this->topic_id);

        return "";
    }

    /**
     * возвращает количество  узлов  в  которых упоминается
     *
     */
    public function getNodesCnt() {
        $conn = \ZCL\DB\DB::getConnect();

        return $conn->GetOne("select count(*) from note_topicnode where topic_id=" . $this->topic_id);
    }

    /**
     * записать тэги
     *
     * @param mixed $tags
     */
    public function saveTags($tags) {
        $conn = \ZCL\DB\DB::getConnect();
        $conn->Execute("delete from note_tags where topic_id=" . $this->topic_id);

        foreach ($tags as $tag) {
            $tag = trim($tag) ;
            $tag = strtolower($tag) ;
            $conn->Execute("insert into note_tags (topic_id,tagvalue) values (" . $this->topic_id . "," . $conn->qstr($tag) . ")");
        }
    }

    /**
     * получить теги
     *
     */
    public function getTags() {
        $tl = array();
        $conn = \ZCL\DB\DB::getConnect();
        $rc = $conn->GetCol("select distinct tagvalue from note_tags where topic_id=" . $this->topic_id);
        foreach ($rc as $k => $v) {
            if (strlen($v)) {
                $tl[$k] = $v;
            }
        }
        return $tl;
    }

    /**
     * получить  подсказки из существующих тегов
     *
     */
    public function getSuggestionTags() {
        $conn = \ZCL\DB\DB::getConnect();
        return $conn->GetCol("select distinct tagvalue from note_tags where topic_id <> " . $this->topic_id);
    }

}
