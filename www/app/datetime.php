<?php

namespace App;

/**
 * вспомагательный  клас  для  работы  с датами
 */
class DateTime
{
    private $time;

    public function __construct($time = 0) {
        if ($time > 0) {
            $this->time = $time;
        } else {
            $this->time = time();
        }
    }

    public function getTimestamp() {
        return $this->time;
    }

    public function getISO() {
        return date(\DateTimeInterface::ISO8601, $this->time);
    }


    public function startOfMonth() {
        $this->time = strtotime(date('Y-m-01 00:00:00', $this->time));
        return $this;
    }
    
    public function startOfYear() {
        $this->time = strtotime(date('Y-01-01 00:00:00', $this->time));
        return $this;
    }

    public function startOfDay() {
        $this->time = strtotime(date('Y-m-d 00:00:00', $this->time));
        return $this;
    }

    public function endOfDay() {
        $this->time = strtotime(date('Y-m-d 23:59:59', $this->time));
        return $this;
    }

    public function endOfMonth() {

        $this->time = strtotime(date('Y-m-01 00:00:00', $this->time));
        $this->time = strtotime('+1 month', $this->time) - 1;
        return $this;
    }

    public function subMonth($n) {

        $this->time = strtotime("-{$n} month", $this->time);
        return $this;
    }

    public function addMonth($n) {

        $this->time = strtotime("+{$n} month", $this->time);
        return $this;
    }

    public function subDay($n) {

        $this->time = strtotime("-{$n} day", $this->time);
        return $this;
    }

    public function addDay($n) {
        $this->time = strtotime("+{$n} day", $this->time);
        return $this;
    }

    public function monthNumber() {
        return (int)date("m", $this->time);

    }


}
