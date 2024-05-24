SET NAMES 'utf8';

INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 1, 'Офiсний документ', 'OfficeDoc', '', 0);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 3, 'Офiс', 'OfficeList', '', 0);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 2, 'Прогноз продаж', 'PredSell', 'Аналітика', 0);

  
delete  from  options where  optname='version' ;
insert  into options (optname,optvalue) values('version','6.10.0'); 
