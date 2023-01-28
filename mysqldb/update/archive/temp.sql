 
ALTER TABLE `stores` ADD `disabled` tinyint(1)   DEFAULT 0  ;
ALTER TABLE `mfund`  ADD `disabled` tinyint(1)   DEFAULT 0  ;


INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 2, 'OLAP аналіз', 'OLAP', 'Ааналітика', 0);
UPDATE `metadata`  set  menugroup ='Ааналітика' where meta_name = 'ABC' ; 

 


 