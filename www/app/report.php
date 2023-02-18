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
     * @param mixed $header Массив  с даннымы  шапки
     * @param mixed $detail Двумерный массив  табличной  части
     * @param mixed $summary Список  полей  по  которым  вычисляются  итоговые  данные табличной части
     */
    public function generate(array $header) {
      
        $dir = 'templates';
    
        $fp = _ROOT . $dir . '/printforms/' . $this->_template ;
        $fp_c = str_replace(".tpl","_custom.tpl",$fp) ;    //кастомный  шаблон
        if(file_exists($fp_c)) {
            $template = @file_get_contents($fp_c);
        }   else {
            $template = @file_get_contents($fp);
        }
        
        
        if (strlen($template) == 0) {
            return "Файл  печатной формы " . $this->_template . " не найден";
        }
        $m = new \Mustache_Engine();
        $html = $m->render($template, $header);

        $html = str_replace("\n", "", $html);
        $html = str_replace("\r", "", $html);
        return $html;
    }

}
