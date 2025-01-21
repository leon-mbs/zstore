<?php

namespace App;

/**
 * Класс  для  рендеринга  печатных  форм
 */
class Report
{
    private $_template;

    /**
     * Путь к  файлу  шаблона
     *
     * @param mixed $template
     */
    public function __construct($template) {
        $this->_template = $template;
    }

    /**
     * Генерация  простой формы
     *
     * @param array $header Массив  с даннымы  шапки
     * @param mixed $removeendline  убирать перевод  строки
     */
    public function generate(array $header,$removeendline=true) {
        gc_enable();
        gc_collect_cycles();
        
        $dir = 'templates';

        $fp = _ROOT . $dir . '/printforms/' . $this->_template ;
        $fp_c = str_replace(".tpl", "_custom.tpl", $fp) ;    //кастомный  шаблон
        if(file_exists($fp_c)) {
            $template = @file_get_contents($fp_c);
        } else {
            $template = @file_get_contents($fp);
        }


        if (strlen($template) == 0) {
            return "Файл  форми друку " . $this->_template . " не знайдений";
        }
        $m = new \Mustache_Engine();
        $html = $m->render($template, $header);
        if($removeendline) {
          $html = str_replace("\n", "", $html);
          $html = str_replace("\r", "", $html);
        }
        return $html;
    }

}
