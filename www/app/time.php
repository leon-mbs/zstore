<?php

namespace App;

use \Zippy\WebApplication;
use \Zippy\Event;
use \Zippy\Interfaces\ChangeListener;
use \Zippy\Interfaces\EventReceiver;
use \Zippy\Interfaces\Requestable;
use \Zippy\Html\Form\TextInput;

/**
 * Компонент  тэга  &lt;input type=&quot;time&quot;&gt; 
 
 */
class Time extends TextInput implements Requestable, ChangeListener
{

    private $event;
     

    public function __construct($id, $value = null ) {
        parent::__construct($id);
        $this->setDateTime($value);
        
    }
 

    public function RenderImpl() {
        TextInput::RenderImpl();
 
        $this->setAttribute('type','time') ;
    
        
    }

    /**
     * Возвращает дату с  временем  в виде timestamp
     * $date - дата к  которой время
     */
    public function getDateTime($date) {
        
        $d = date('Y-m-d', $date);
        return strtotime($d . ' '.$this->getText());
 
         
    }

    /**
     * Устанавливает время из даты
     * @param mixed $t - timestamp
     */
    public function setDateTime($t = 0) {
        if ($t > 0) {
            $this->setText(date('H:i', $t));
        } else {
            $this->setText("");
        }
    }

    /**
     * @see  ChangeListener
     */
    public function onChange(EventReceiver $receiver, $handler, $ajax = true) {

        $this->event = new Event($receiver, $handler);
        $this->event->isajax = $ajax;
    }

    /**
     * @see ChangeListener
     */
    public function OnEvent() {
        if ($this->event != null) {
            $this->event->onEvent($this);
        }
    }

    /**
     * @see Requestable
     */
    public function RequestHandle() {
        $this->OnEvent();
    }

}
