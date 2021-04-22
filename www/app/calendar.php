<?php

namespace App;

class Calendar extends \Zippy\Html\HtmlComponent implements \Zippy\Interfaces\Requestable, \Zippy\Interfaces\AjaxRender
{

    private $event = null;
    private $data  = array();
    private $view  = 'dayGridMonth';

    public function __construct($id) {
        parent::__construct($id);
        $this->view = 'dayGridMonth';
    }

    public final function RenderImpl() {
        global $_config;
        $id = $this->getAttribute('id');
        $url = $this->owner->getURLNode() . "::" . $this->id;
        $lang = 'ru';
        if ($_config['common']['lang'] == 'ua') {
            $lang = 'ua';
        }

        if (count($this->data) > 0) {
            $ev = ",events: [";
            foreach ($this->data as $dt) {

                $ev .= "  {  id     : '{$dt->id}',
                             title  : '{$dt->title}',    
                             start  :  {$dt->start} , 
                             end    :  {$dt->end} , 
                             backgroundColor: '{$dt->color}', 
                        
                             allDay         : false },";
            }
            $ev = rtrim($ev, ',');
            $ev .= "]";
        }

        $js = <<<EOT
         
          var calendarEl = document.getElementById('{$id}');
        var Calendar = FullCalendar.Calendar;
 
           var calendar = new Calendar(calendarEl, { 
              
      headerToolbar: {
        left  : 'prev,next today',
        center: 'title',
        right : 'dayGridMonth,timeGridWeek,timeGridDay'
      },
      initialView: '{$this->view}' ,
      
      themeSystem: 'bootstrap' ,
      editable: true,
      droppable: true,
     
         eventClick: function(info) {
             var url ='{$url}:'   + 'click:'+ info.event.id ;
            
             window.location= url;
         
         
        }, 
         dateClick: function(info) {
               
             var url ='{$url}:'   + 'add:'+ info.dateStr;
            
             window.location= url;
              
         
        },   
             
  eventResize: function(info) {

           
              var url ='{$url}:'   + 'resize:'+ info.event.id +':' + info.startDelta.milliseconds + ':' + info.endDelta.milliseconds +'&ajax=true' ;
            
            // window.location= url;
             getUpdate(url)  ;

  } ,       
 eventDrop: function(info) {
               
               var url ='{$url}:'   + 'move:'+ info.event.id +':' + info.delta.years+':' + info.delta.months+':' + info.delta.days+':' + info.delta.milliseconds +'&ajax=true' ;
                                                      
              // window.location= url;
             getUpdate(url)  ;

  } ,        
         locale: '{$lang}' 
          {$ev}
        
         

      

      
      });   
      
        calendar.render();      
EOT;

        Application::$app->getResponse()->addJavaScript($js, true);
    }

    public final function RequestHandle() {
        $params = Application::$app->getRequest()->request_params[$this->id];
        $action = array();

        $action['action'] = $params[0];
        $action['id'] = $params[1];
        $action['startdelta'] = $params[2];
        $action['enddelta'] = $params[3];
        $action['years'] = $params[2];
        $action['months'] = $params[3];
        $action['days'] = $params[4];
        $action['ms'] = $params[5];

        if ($action['action'] == 'add') {
            $dt = $params[1] . ':' . $params[2] . ':' . $params[3];
            $action['date'] = strtotime($dt);
        }
        if ($action['action'] == 'resize') {
            $action['startdelta'] = $action['startdelta'] / 1000;
            $action['enddelta'] = $action['enddelta'] / 1000;
        }
        if ($action['action'] == 'move') {
            $action['years'] = $action['years'];
            $action['month'] = $action['month'];
            $action['days'] = $action['days'];
            $action['ms'] = $action['ms'] / 1000;
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

    public function AjaxAnswer() {

        return '';
    }

}

class CEvent
{

    public $id, $title, $start, $end, $color;

    public function __construct($id, $title, $start, $end, $color) {
        $this->id = $id;
        $this->title = $title;
        $this->start = "new Date(" . date("Y", $start) . ", " . (date("m", $start) - 1) . ", " . date("d", $start) . "," . date("H", $start) . "," . date("i", $start) . ")";
        $this->end = "new Date(" . date("Y", $end) . ", " . (date("m", $end) - 1) . ", " . date("d", $end) . "," . date("H", $end) . "," . date("i", $end) . ")";
        //   $this->end = date("Y-m-dTH:i", $end);
        $this->color = $color;
    }

}
