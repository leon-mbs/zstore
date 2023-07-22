<?php

namespace App;

// вспомагательный   класс  для   вывода  простых  списков
class DataItem implements \Zippy\Interfaces\DataItem
{
    public $id;
    protected $fields = array();

    public function __construct($row = null) {
        if(is_integer($row)) {
            $this->id = $row;
        }
        if (is_array($row)) {
            $this->fields = array_merge($this->fields, $row);
        }
    }

    final public function __set($name, $value) {
        $this->fields[$name] = $value;
    }

    final public function __get($name) {
        return $this->fields[$name];
    }

    public function getID() {
        return $this->id;
    }

    public function setID($id) {
        $this->id = $id;
    }

    /**
     * возвращает  список DataItem заполненый с запроса
     *
     * @param mixed $sql
     */
    public static function query($sql) {
        $conn = \ZDB\DB::getConnect();
        $list = array();

        $rc = $conn->Execute($sql);
        foreach ($rc as $row) {
            $list[] = new DataItem($row);
        }
        return $list;
    }

}
