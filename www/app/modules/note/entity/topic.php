<?php

namespace App\Modules\Note\Entity;

/**
 *  Класс  инкапсулирующий топик
 * @table=note_topics
 * @keyfield=topic_id
 */
class Topic extends \ZCL\DB\Entity
{
    public $accusers = [];
    
    protected function init() {
        $this->topic_id = 0;
        $this->ispublic = 0;
 
        $this->accusers=[] ;
    }

    protected function beforeSave() {
        parent::beforeSave();
        
        /*
        $this->content = "<content>";
        $this->detail = base64_encode($this->detail) ;
        $this->content .= "<detail>{$this->detail}</detail>";
        $this->content .= "<isbasa64>1</isbasa64>";
        $this->content .= "<updatedon>{$this->updatedon}</updatedon>";
        $this->content .= "</content>";
        */
        
        $content=[]  ;
        $content['detail']    = $this->detail ;
        $content['updatedon'] = $this->updatedon ;
        $content['accusers'] = $this->accusers  ;
      
        $this->content = serialize($content) ;
        
    }

    protected function afterLoad() {
        
        if(strpos($this->content,'<content>') ===0  ) {
            $xml = @simplexml_load_string($this->content);

            $this->detail = (string)($xml->detail[0]);
            $this->isbasa64 = (int)($xml->isbasa64[0]);
            if($this->isbasa64==1) {
                $this->detail = base64_decode($this->detail) ;
            }
            $this->updatedon = (int)($xml->updatedon[0]);       
            $this->accusers =  [];
       }  else  {
            $content = unserialize($this->content) ;
            $this->updatedon = $content['updatedon'] ;
            $this->detail = $content['detail'] ;
            $this->accusers = $content['accusers'] ??[];
     
        }
     
        
        parent::afterLoad();
    }

    /**
     * список топиков  для  узла
     *
     * @param mixed $node_id
     */
    public static function findByNode($node_id) {

        $user = \App\System::getUser();
        $w = "(user_id={$user->user_id} or ispublic=1  ) and ";
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
    public function addToNode($node_id,$islink=false) {
        $conn = \ZCL\DB\DB::getConnect();
        $conn->Execute("delete from note_topicnode where topic_id={$this->topic_id} and node_id = {$node_id} ");
        $conn->Execute("insert into note_topicnode(topic_id,node_id,islink)values({$this->topic_id},{$node_id}," . ($islink ? 1:0  ). ")");
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
     
        $conn = \ZCL\DB\DB::getConnect();
        return $conn->GetCol("select distinct tagvalue from note_tags where topic_id=" . $this->topic_id);
  
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
