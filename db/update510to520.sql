

CREATE TABLE `iostate` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `document_id` int(11) NOT NULL,
  `iotype` smallint(6) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `document_id` (`document_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
  
CREATE   VIEW `iostate_view` AS 
  select 
    `s`.`id` AS `id`,
    `s`.`document_id` AS `document_id`,
    `s`.`iotype` AS `iotype`,
    `s`.`amount` AS `amount`,
    `d`.`document_date` AS `document_date`,
    `d`.`branch_id` AS `branch_id` 
  from 
    (`iostate` `s` join `documents` `d` on((`s`.`document_id` = `d`.`document_id`)));  
     