 INSERT INTO `metadata` ( `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 3, 'Пр. процессы', 'ProdProcList', 'Производство', 0);
 INSERT INTO `metadata` ( `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 3, 'Пр. этапы', 'ProdStageList', 'Производство', 0);
 INSERT INTO `metadata` ( `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 1, 'Перемещение  партий ТМЦ', 'MovePart', 'Склад', 0);
 
 UPDATE `metadata` SET `description` = 'Оплата по производству' WHERE `meta_name` = 'EmpTask' ;

 
CREATE TABLE prodproc (
  pp_id int(11) NOT NULL AUTO_INCREMENT,
  procname varchar(255) NOT NULL,
  basedoc varchar(255) DEFAULT NULL,
  snumber varchar(255) DEFAULT NULL,
  state smallint(4) DEFAULT 0,
  detail longtext DEFAULT NULL,
  PRIMARY KEY (pp_id)
) engine=InnoDB DEFAULT CHARSET=utf8;
     
  
CREATE TABLE prodstage (
  st_id int(11) NOT NULL AUTO_INCREMENT,
  pp_id int(11) NOT NULL,
  pa_id int(11) NOT NULL,
  state smallint(6) NOT NULL,
  stagename varchar(255) NOT NULL,
  detail longtext DEFAULT NULL,
  PRIMARY KEY (st_id)
) engine=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE prodstageagenda (
  sta_id int(11) NOT NULL AUTO_INCREMENT,
  st_id int(11) NOT NULL,
  startdate datetime NOT NULL,
  enddate datetime NOT NULL,
  PRIMARY KEY (sta_id)
) engine=InnoDB DEFAULT CHARSET=utf8;
  
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
 
  
CREATE VIEW prodstage_view
AS
SELECT
  `ps`.`st_id` AS `st_id`,
  `ps`.`pp_id` AS `pp_id`,
  `ps`.`pa_id` AS `pa_id`,
  `ps`.`state` AS `state`,
   ps.stagename,
  COALESCE((SELECT
      MIN(`pag`.`startdate`)
    FROM `prodstageagenda` `pag`
    WHERE (`pag`.`st_id` = `ps`.`st_id`)), NULL) AS `startdate`,
  COALESCE((SELECT
      MAX(`pag`.`enddate`)
    FROM `prodstageagenda` `pag`
    WHERE (`pag`.`st_id` = `ps`.`st_id`)), NULL) AS `enddate`,
  COALESCE((SELECT
      MAX(`pag`.`hours`)
    FROM `prodstageagenda_view` `pag`
    WHERE (`pag`.`st_id` = `ps`.`st_id`)), NULL) AS `hours`,
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


  
 

 
 
 
    