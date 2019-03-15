<?php

namespace App;

use \App\Application;

class Calendar extends \Zippy\Html\HtmlComponent implements \Zippy\Interfaces\Requestable
{

    private $event = null;
    private $data = array();

    public final function RenderImpl() {
        $id = $this->getAttribute('id');
        $url = $this->owner->getURLNode() . "::" . $this->id;


        if (count($this->data) > 0) {
            $ev = ",events: [";
            foreach ($this->data as $dt) {
                $ev .= "  { id  : '{$dt->id}',title  : '{$dt->title}', start  : '{$dt->start}' ,            end  : '{$dt->end}' ,  color :  '{$dt->color}'},";
            }
            $ev = rtrim($ev, ',');
            $ev .= "]";
        }

        $js = <<<EOT
         
         
            $("#{$id}").fullCalendar({ 
              
             header: {
        left: 'prev,next today',
        center: 'title',
        right: 'month,agendaWeek,agendaDay,listMonth'
         },
         defaultView: 'month',
         minTime: '08:00:00',
         maxTime: '20:00:00',
         eventClick: function(calEvent, jsEvent, view) {
             var url ='{$url}:'   + 'click:'+ calEvent.id ;
             window.location= url;
         
         
        }, 
         dayClick: function(date, jsEvent, view) {
             var dt =date.toISOString();   
              
             
             var url ='{$url}:'   + 'add:'+ dt;
             window.location= url;
              
         
        }, 
editable: true,
  eventResize: function(event, delta, revertFunc) {

     

              var url ='{$url}:'   + 'resize:'+ event.id +':' +delta;
             window.location= url;
    

  } ,       
 eventDrop: function(event, delta, revertFunc) {
               
               var url ='{$url}:'   + 'move:'+ event.id +':' +delta;
             window.location= url;

     

  } ,       
         locale: 'ru' 
          {$ev} 

        

      
      });         
EOT;

        Application::$app->getResponse()->addJavaScript($js, true);
    }

    public final function RequestHandle() {
        $params = Application::$app->getRequest()->request_params[$this->id];
        $action = array();

        $action['action'] = $params[0];
        $action['id'] = $params[1];
        $action['delta'] = $params[2];


        if ($action['action'] == 'add') {
            $dt = $params[1] . ':' . $params[2] . ':' . $params[3];
            $action['date'] = strtotime($dt);
        }
        if ($action['action'] == 'resize') {
            $action['delta'] = $action['delta'] / 1000;
        }
        if ($action['action'] == 'move') {
            $action['delta'] = $action['delta'] / 1000;
        }

        if ($this->event != null) {
            $this->event->onEvent($this, $action);
        }
    }

    public function setEvent(\Zippy\Interfaces\EventReceiver $receiver, $handler) {

        $this->event = new \Zippy\Event($receiver, $handler);
    }

    public function setData($data) {
        $this->data = $data;
    }

}

class CEvent
{

    public $id, $title, $start, $end, $color;

    public function __construct($id, $title, $start, $end, $color) {
        $this->id = $id;
        $this->title = $title;
        $this->start = date("Y-m-d H:i", $start);
        $this->end = date("Y-m-d H:i", $end);
        $this->color = $color;
    }

}
