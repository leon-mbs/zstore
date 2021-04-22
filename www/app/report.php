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
        global $_config;

        $dir = 'templates';
        if ($_config['common']['lang'] == 'ua') {
            $dir = 'templates_ua';
        }

        $template = @file_get_contents(_ROOT . $dir . '/printforms/' . $this->_template);
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
