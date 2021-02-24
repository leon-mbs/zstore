  CREATE TABLE `subscribes` (
  `sub_id` int(11) NOT NULL AUTO_INCREMENT,
  `sub_type` int(11) DEFAULT NULL,
 
  `reciever_type` int(11) DEFAULT NULL,
 
  `msg_type` int(11) DEFAULT NULL,
  `detail` LONGTEXT DEFAULT NULL,
  `disabled`  int(1)  DEFAULT 0,
   PRIMARY KEY (`sub_id`)
  
) engine=InnoDB  DEFAULT CHARSET=utf8;

     