
INSERT INTO `metadata` ( `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 2, 'Акт звірки', 'CompareAct', 'Контрагенти', 0);
INSERT INTO `metadata` ( `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 2, 'Зарезервовані товари', 'Reserved', 'Склад', 0);


DROP VIEW prodstageagenda_view ;

CREATE VIEW prodstageagenda_view
AS
SELECT
  `a`.`sta_id` AS `sta_id`,
  `a`.`st_id` AS `st_id`,
  `a`.`startdate` AS `startdate`,
  `a`.`enddate` AS `enddate`,
  `pv`.`stagename` AS `stagename`,
  `pv`.`state` AS `state`,
  (TIMESTAMPDIFF(MINUTE, `a`.`startdate`, `a`.`enddate`) / 60) AS `hours`,
  `pv`.`pa_id` AS `pa_id`,
  `pv`.`pp_id` AS `pp_id`
FROM (`prodstageagenda` `a`
  JOIN `prodstage` `pv`
    ON ((`a`.`st_id` = `pv`.`st_id`)));
    
    
    
    
    
delete  from  options where  optname='version' ;
insert  into options (optname,optvalue) values('version','6.4.0');
     