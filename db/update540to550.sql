 INSERT INTO `metadata` ( `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 3, 'Пр. процессы', 'ProdProcList', 'Производство', 0);
 INSERT INTO `metadata` ( `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 3, 'Пр. этапы', 'ProdStageList', 'Производство', 0);
 INSERT INTO `metadata` ( `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 1, 'Перемещение  партий ТМЦ', 'MovePart', 'Склад', 0);

 
CREATE TABLE `prodproc` (
  `pp_id` int(11) NOT NULL AUTO_INCREMENT,
  `procname` varchar(255)   NOT NULL,
  `basedoc` varchar(255) DEFAULT NULL,
  `snumber` varchar(255) DEFAULT NULL,
  `state` smallint(4) DEFAULT 0,
  `detail` LONGTEXT DEFAULT NULL,
   PRIMARY KEY (`pp_id`)
  
) engine=InnoDB DEFAULT CHARSET=utf8;
     
  
 CREATE TABLE `prodstage` (
  `st_id` int(11) NOT NULL AUTO_INCREMENT,
  `pp_id` int(11) NOT NULL ,
  `pa_id` int(11) NOT NULL ,
  
  `stagename` varchar(255) NOT NULL,
  `detail` LONGTEXT DEFAULT NULL,
   KEY (`pp_id`) ,
   PRIMARY KEY (`st_id`)  
   
) engine=InnoDB DEFAULT CHARSET=utf8;


 CREATE TABLE `prodstageagenda` (
  `sta_id` int(11) NOT NULL AUTO_INCREMENT,
  `st_id` int(11) NOT NULL ,
  `startdate` DateTime  NOT NULL,
  `enddate` DateTime  NOT NULL,
 
   KEY (`st_id`) ,
   PRIMARY KEY (`sta_id`)  
   
) engine=InnoDB DEFAULT CHARSET=utf8;
  

CREATE VIEW prodproc_view
AS
SELECT
  `p`.`pp_id` AS `pp_id`,
  `p`.`procname` AS `procname`,
  `p`.`basedoc` AS `basedoc`,
  `p`.`snumber` AS `snumber`,
  `p`.`state` AS `state`,
  COALESCE((SELECT
      MIN(`ps`.`startdate`)
    FROM `prodstage_view` `ps`
    WHERE (`ps`.`pp_id` = `p`.`pp_id`)), NULL) AS `startdate`,
  COALESCE((SELECT
      MAX(`ps`.`enddate`)
    FROM `prodstage_view` `ps`
    WHERE (`ps`.`pp_id` = `p`.`pp_id`)), NULL) AS `enddate`,
  COALESCE((SELECT
      COUNT(0)
    FROM `prodstage` `ps`
    WHERE (`ps`.`pp_id` = `p`.`pp_id`)), NULL) AS `stagecnt`,
  `p`.`detail` AS `detail`
FROM `prodproc` `p`;

CREATE VIEW prodstage_view
AS
SELECT
  `ps`.`st_id` AS `st_id`,
  `ps`.`pp_id` AS `pp_id`,
  `ps`.`pa_id` AS `pa_id`,
  COALESCE((SELECT
      MIN(`pag`.`startdate`)
    FROM `prodstageagenda` `pag`
    WHERE (`pag`.`st_id` = `ps`.`st_id`)), NULL) AS `startdate`,
  COALESCE((SELECT
      MAX(`pag`.`enddate`)
    FROM `prodstageagenda` `pag`
    WHERE (`pag`.`st_id` = `ps`.`st_id`)), NULL) AS `enddate`,
  `ps`.`stagename` AS `stagename`,
  `ps`.`detail` AS `detail`,
  `pr`.`procname` AS `procname`,
  `pr`.`snumber` AS `snumber`,
  `pr`.`state` AS `procstate`,
  `pa`.`pa_name` AS `pa_name`
FROM ((`prodstage` `ps`
  JOIN `prodproc` `pr`
    ON ((`pr`.`pp_id` = `ps`.`pp_id`)))
  JOIN `parealist` `pa`
    ON ((`pa`.`pa_id` = `ps`.`pa_id`)));
  
 
CREATE VIEW prodstageagenda_view
AS
SELECT
  `a`.`sta_id` AS `sta_id`,
  `a`.`st_id` AS `st_id`,
  `a`.`startdate` AS `startdate`,
  `a`.`enddate` AS `enddate`,
  `pv`.`stagename` AS `stagename`,
  (TIMESTAMPDIFF(MINUTE, `a`.`startdate`, `a`.`enddate`) / 60) AS `hours`,
  `pv`.`pa_id` AS `pa_id`,
  `pv`.`pp_id` AS `pp_id`
FROM (`prodstageagenda` `a`
  JOIN `prodstage` `pv`
    ON ((`a`.`st_id` = `pv`.`st_id`)));
 

 
старт  процесса. отмена  пока нет документов
   

сколько  списано  оприходовано  нормочасы или  сдельная
   

производственный цикл - процес плюс дата процесса
дата  определяется  этапми 
 
 
код продукции этапа на данном участке сколько  оприходовать
сколько надо списать на производство по каждому этапу 


создание  списания  и оприходования - привязка  документов  к  этапу
инфа об этапе  в  комент
подсчет по  докам сколько  списано  оприходовано и сколько надо
 
 
журнал  этапов
смена  статуса
документы  оприходования  и списания
 
 


отчет для начисления  зарплаты (доделать  по нарядам? )

 
    