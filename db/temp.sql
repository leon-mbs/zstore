 
 
CREATE TABLE `prodproc` (
  `pp_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `detail` LONGTEXT DEFAULT NULL,
   PRIMARY KEY (`pp_id`)
  
) engine=InnoDB DEFAULT CHARSET=utf8;
     
  

CREATE TABLE `empacc` (
  `ea_id` int(11) NOT NULL AUTO_INCREMENT,
  `emp_id` int(11) NOT NULL,
  `document_id` int(11) DEFAULT NULL,
  `optype` int(11) DEFAULT NULL,
  //`createdon` date NOT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
 
  PRIMARY KEY (`ea_id`),
  KEY `emp_id` (`emp_id`)
) engine=InnoDB DEFAULT CHARSET=utf8;



  
журнал процесов
разбиетие на этапы

кол продукции этапа на данном участке
сколько надо списать на производство по каждуму этипу и общий отчет
время выполнения этапа
распределить этапы по календарю

проверка на совпадение с этапами других процессов

наряд на этап с указанием этапа и процесса , исполнители подсчет сдельной работы

списание оприходование с участка

производственный цикл - процес плюс дата процесса
связаные документы

таблица олтслеживания серийных номеров


**********************************************
зарплата
справочник  начислений  и удержаний

формулы на  яваскрипт    
    