
  CREATE TABLE `discounts` (
     id,
     typedisc, 
     document_id,
     customer_id,
     item_id,
     cat_id,
     pricetype,
     bonus,
     discount,
     startdate,
     enddate
  )
 
 
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



     
    