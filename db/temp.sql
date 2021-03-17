ALTER TABLE `item_cat` ADD `parent_id` INT NOT NULL DEFAULT '0'  ;   
CREATE   VIEW `item_cat_view` AS 
  select 
    `c`.`cat_id` AS `cat_id`,
    `c`.`cat_name` AS `cat_name`,
    `c`.`detail` AS `detail`,
    `c`.`parent_id` AS `parent_id`,
    `p`.`cat_name` AS `parent_name`,
    coalesce((
  select 
    count(0) 
  from 
    `items` `i` 
  where 
    (`i`.`cat_id` = `item_cat`.`cat_id`)),0) AS `qty` 
  from 
    (`item_cat` `c` left join `item_cat` `p` on((`c`.`parent_id` = `p`.`cat_id`)));
    
     
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



CREATE TABLE `prodproc` (
  `pp_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `detail` LONGTEXT DEFAULT NULL,
   PRIMARY KEY (`pp_id`)
  
) engine=InnoDB DEFAULT CHARSET=utf8;



    
     
      