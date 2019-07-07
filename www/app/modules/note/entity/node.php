<?php

namespace App\Entity;

use \ZCL\DB\TreeEntity;

/**
 *  Класс  инкапсулирующий   узел дерева
 * @table=nodes
 * @view=nodesview
 * @keyfield=node_id
 * @parentfield=pid
 * @pathfield=mpath
 */
class Node extends TreeEntity
{

    protected function init() {
        $this->node_id = 0;
        $this->pid = 0;
        $this->mpath = '';
    }

    protected function beforeDelete() {
        $conn = \ZCL\DB\DB::getConnect();
        $conn->Execute("delete from topicnode where node_id=" . $this->node_id);

        return true;
    }

    /**
     * получение  родительских узлов
     * 
     */
    public function getParents() {

        // $ids = str_split ($this->mpath,8);   ;

        $list = array();

        $parent = $this;
        do {
            $list[] = $parent->title;
            $parent = Node::load($parent->pid);
        } while ($parent instanceof Node);

        return $list;
    }

}
