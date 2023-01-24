
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 2, 'Продажі', 'OlapBay', 'Аналітика', 0);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 2, 'Закупівлі', 'OlapSell', 'Аналітика', 0); 

update  metadata set  menugroup ='Аналітика' where  meta_name='ABC';

ALTER TABLE `stores` ADD `disabled` tinyint(1)   DEFAULT 0  ;
ALTER TABLE `mfund`  ADD `disabled` tinyint(1)   DEFAULT 0  ;




delete  from  options where  optname='version' ;
insert  into options (optname,optvalue) values('version','6.6.0');
     