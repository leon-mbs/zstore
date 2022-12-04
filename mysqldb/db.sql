
/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES cp1251 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `branches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `branches` (
  `branch_id` int(11) NOT NULL AUTO_INCREMENT,
  `branch_name` varchar(255) NOT NULL,
  `details` longtext NOT NULL,
  `disabled` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`branch_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `contracts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contracts` (
  `contract_id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) DEFAULT '0',
  `firm_id` int(11) DEFAULT '0',
  `createdon` date NOT NULL,
  `contract_number` varchar(64) NOT NULL,
  `disabled` tinyint(1) DEFAULT '0',
  `details` longtext NOT NULL,
  PRIMARY KEY (`contract_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `contracts_view`;
/*!50001 DROP VIEW IF EXISTS `contracts_view`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `contracts_view` AS SELECT 
 1 AS `contract_id`,
 1 AS `customer_id`,
 1 AS `firm_id`,
 1 AS `createdon`,
 1 AS `contract_number`,
 1 AS `disabled`,
 1 AS `details`,
 1 AS `customer_name`,
 1 AS `firm_name`*/;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `cust_acc_view`;
/*!50001 DROP VIEW IF EXISTS `cust_acc_view`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `cust_acc_view` AS SELECT 
 1 AS `s_passive`,
 1 AS `s_active`,
 1 AS `b_passive`,
 1 AS `b_active`,
 1 AS `customer_id`*/;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `custacc`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `custacc` (
  `ca_id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `document_id` int(11) DEFAULT NULL,
  `optype` int(11) NOT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `createdon` date DEFAULT NULL,
  PRIMARY KEY (`ca_id`),
  KEY `document_id` (`document_id`),
  KEY `customer_id` (`customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `custitems`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `custitems` (
  `custitem_id` int(11) NOT NULL AUTO_INCREMENT,
  `item_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `quantity` decimal(10,3) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `cust_code` varchar(255) NOT NULL,
  `comment` varchar(255) DEFAULT NULL,
  `updatedon` date NOT NULL,
  PRIMARY KEY (`custitem_id`),
  KEY `item_id` (`item_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `custitems_view`;
/*!50001 DROP VIEW IF EXISTS `custitems_view`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `custitems_view` AS SELECT 
 1 AS `custitem_id`,
 1 AS `item_id`,
 1 AS `customer_id`,
 1 AS `quantity`,
 1 AS `price`,
 1 AS `updatedon`,
 1 AS `cust_code`,
 1 AS `comment`,
 1 AS `itemname`,
 1 AS `cat_id`,
 1 AS `item_code`,
 1 AS `detail`,
 1 AS `customer_name`*/;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `customers` (
  `customer_id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_name` varchar(255) DEFAULT NULL,
  `detail` mediumtext NOT NULL,
  `email` varchar(64) DEFAULT NULL,
  `phone` varchar(64) DEFAULT NULL,
  `status` smallint(4) NOT NULL DEFAULT '0',
  `city` varchar(255) DEFAULT NULL,
  `leadstatus` varchar(255) DEFAULT NULL,
  `leadsource` varchar(255) DEFAULT NULL,
  `createdon` date DEFAULT NULL,
  `country` varchar(255) DEFAULT NULL,
  `passw` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`customer_id`)
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `customers_view`;
/*!50001 DROP VIEW IF EXISTS `customers_view`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `customers_view` AS SELECT 
 1 AS `customer_id`,
 1 AS `customer_name`,
 1 AS `detail`,
 1 AS `email`,
 1 AS `phone`,
 1 AS `status`,
 1 AS `city`,
 1 AS `leadsource`,
 1 AS `leadstatus`,
 1 AS `country`,
 1 AS `passw`,
 1 AS `mcnt`,
 1 AS `fcnt`,
 1 AS `ecnt`*/;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `docstatelog`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `docstatelog` (
  `log_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `document_id` int(11) NOT NULL,
  `docstate` smallint(6) NOT NULL,
  `createdon` datetime NOT NULL,
  `hostname` varchar(64) NOT NULL,
  PRIMARY KEY (`log_id`),
  KEY `document_id` (`document_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3584 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `docstatelog_view`;
/*!50001 DROP VIEW IF EXISTS `docstatelog_view`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `docstatelog_view` AS SELECT 
 1 AS `log_id`,
 1 AS `user_id`,
 1 AS `document_id`,
 1 AS `docstate`,
 1 AS `createdon`,
 1 AS `hostname`,
 1 AS `username`,
 1 AS `document_number`,
 1 AS `meta_desc`,
 1 AS `meta_name`*/;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `documents` (
  `document_id` int(11) NOT NULL AUTO_INCREMENT,
  `document_number` varchar(45) NOT NULL,
  `document_date` date NOT NULL,
  `user_id` int(11) NOT NULL,
  `content` longtext,
  `amount` decimal(11,2) DEFAULT NULL,
  `meta_id` int(11) NOT NULL,
  `state` tinyint(4) NOT NULL,
  `notes` varchar(255) NOT NULL,
  `customer_id` int(11) DEFAULT '0',
  `payamount` decimal(11,2) DEFAULT '0.00',
  `payed` decimal(11,2) DEFAULT '0.00',
  `branch_id` int(11) DEFAULT '0',
  `parent_id` bigint(20) DEFAULT '0',
  `firm_id` int(11) DEFAULT NULL,
  `priority` smallint(6) DEFAULT '100',
  `lastupdate` datetime DEFAULT NULL,
  PRIMARY KEY (`document_id`),
  UNIQUE KEY `unuqnumber` (`meta_id`,`document_number`,`branch_id`),
  KEY `document_date` (`document_date`),
  KEY `customer_id` (`customer_id`),
  KEY `user_id` (`user_id`),
  KEY `branch_id` (`branch_id`),
  CONSTRAINT `documents_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1044 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `documents_view`;
/*!50001 DROP VIEW IF EXISTS `documents_view`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `documents_view` AS SELECT 
 1 AS `document_id`,
 1 AS `document_number`,
 1 AS `document_date`,
 1 AS `user_id`,
 1 AS `content`,
 1 AS `amount`,
 1 AS `meta_id`,
 1 AS `username`,
 1 AS `customer_id`,
 1 AS `customer_name`,
 1 AS `state`,
 1 AS `notes`,
 1 AS `payamount`,
 1 AS `payed`,
 1 AS `parent_id`,
 1 AS `branch_id`,
 1 AS `branch_name`,
 1 AS `firm_id`,
 1 AS `priority`,
 1 AS `firm_name`,
 1 AS `lastupdate`,
 1 AS `meta_name`,
 1 AS `meta_desc`*/;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `empacc`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `empacc` (
  `ea_id` int(11) NOT NULL AUTO_INCREMENT,
  `emp_id` int(11) NOT NULL,
  `document_id` int(11) DEFAULT NULL,
  `optype` int(11) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `createdon` date DEFAULT NULL,
  PRIMARY KEY (`ea_id`),
  KEY `emp_id` (`emp_id`),
  KEY `document_id` (`document_id`)
) ENGINE=InnoDB AUTO_INCREMENT=46 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `empacc_view`;
/*!50001 DROP VIEW IF EXISTS `empacc_view`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `empacc_view` AS SELECT 
 1 AS `ea_id`,
 1 AS `emp_id`,
 1 AS `document_id`,
 1 AS `optype`,
 1 AS `notes`,
 1 AS `amount`,
 1 AS `createdon`,
 1 AS `document_number`,
 1 AS `emp_name`*/;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `employees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `employees` (
  `employee_id` int(11) NOT NULL AUTO_INCREMENT,
  `login` varchar(64) DEFAULT NULL,
  `detail` mediumtext,
  `disabled` tinyint(1) DEFAULT '0',
  `emp_name` varchar(64) NOT NULL,
  `branch_id` int(11) DEFAULT '0',
  PRIMARY KEY (`employee_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `entrylist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `entrylist` (
  `entry_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `document_id` int(11) NOT NULL,
  `quantity` decimal(11,3) DEFAULT '0.000',
  `stock_id` int(11) DEFAULT NULL,
  `service_id` int(11) DEFAULT NULL,
  `outprice` decimal(10,2) DEFAULT NULL,
  `tag` int(11) DEFAULT '0',
  PRIMARY KEY (`entry_id`),
  KEY `document_id` (`document_id`),
  KEY `stock_id` (`stock_id`),
  CONSTRAINT `entrylist_ibfk_1` FOREIGN KEY (`document_id`) REFERENCES `documents` (`document_id`),
  CONSTRAINT `entrylist_ibfk_2` FOREIGN KEY (`stock_id`) REFERENCES `store_stock` (`stock_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1440 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = latin1 */ ;
/*!50003 SET character_set_results = latin1 */ ;
/*!50003 SET collation_connection  = latin1_swedish_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 */ /*!50003 TRIGGER `entrylist_after_ins_tr` AFTER INSERT ON `entrylist`

  FOR EACH ROW

BEGIN







 IF new.stock_id >0 then



  update store_stock set qty=(select  coalesce(sum(quantity),0) from entrylist where stock_id=new.stock_id) where store_stock.stock_id = new.stock_id;

 END IF;

END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = latin1 */ ;
/*!50003 SET character_set_results = latin1 */ ;
/*!50003 SET collation_connection  = latin1_swedish_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 */ /*!50003 TRIGGER `entrylist_after_del_tr` AFTER DELETE ON `entrylist`

  FOR EACH ROW

BEGIN





 IF old.stock_id >0 then



  update store_stock set qty=(select  coalesce(sum(quantity),0) from entrylist where stock_id=old.stock_id) where store_stock.stock_id = old.stock_id;

 END IF;

END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
DROP TABLE IF EXISTS `entrylist_view`;
/*!50001 DROP VIEW IF EXISTS `entrylist_view`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `entrylist_view` AS SELECT 
 1 AS `entry_id`,
 1 AS `document_id`,
 1 AS `quantity`,
 1 AS `customer_id`,
 1 AS `stock_id`,
 1 AS `service_id`,
 1 AS `tag`,
 1 AS `item_id`,
 1 AS `partion`,
 1 AS `document_date`,
 1 AS `outprice`*/;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `equipments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `equipments` (
  `eq_id` int(11) NOT NULL AUTO_INCREMENT,
  `eq_name` varchar(255) DEFAULT NULL,
  `detail` mediumtext,
  `disabled` tinyint(1) DEFAULT '0',
  `description` text,
  PRIMARY KEY (`eq_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eventlist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `eventlist` (
  `user_id` int(11) NOT NULL,
  `eventdate` datetime NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `event_id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `isdone` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`event_id`),
  KEY `user_id` (`user_id`),
  KEY `customer_id` (`customer_id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eventlist_view`;
/*!50001 DROP VIEW IF EXISTS `eventlist_view`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `eventlist_view` AS SELECT 
 1 AS `user_id`,
 1 AS `eventdate`,
 1 AS `title`,
 1 AS `description`,
 1 AS `event_id`,
 1 AS `customer_id`,
 1 AS `isdone`,
 1 AS `customer_name`*/;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `files` (
  `file_id` int(11) NOT NULL AUTO_INCREMENT,
  `item_id` int(11) DEFAULT NULL,
  `filename` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `item_type` int(11) NOT NULL,
  `mime` varchar(16) DEFAULT NULL,
  PRIMARY KEY (`file_id`),
  KEY `item_id` (`item_id`)
) ENGINE=MyISAM AUTO_INCREMENT=68 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `filesdata`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `filesdata` (
  `file_id` int(11) DEFAULT NULL,
  `filedata` longblob,
  UNIQUE KEY `file_id` (`file_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `firms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `firms` (
  `firm_id` int(11) NOT NULL AUTO_INCREMENT,
  `firm_name` varchar(255) NOT NULL,
  `details` longtext NOT NULL,
  `disabled` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`firm_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `images` (
  `image_id` int(11) NOT NULL AUTO_INCREMENT,
  `content` longblob NOT NULL,
  `mime` varchar(16) DEFAULT NULL,
  `thumb` longblob,
  PRIMARY KEY (`image_id`)
) ENGINE=MyISAM AUTO_INCREMENT=106 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `iostate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `iostate` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `document_id` int(11) NOT NULL,
  `iotype` smallint(6) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `document_id` (`document_id`)
) ENGINE=InnoDB AUTO_INCREMENT=263 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `iostate_view`;
/*!50001 DROP VIEW IF EXISTS `iostate_view`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `iostate_view` AS SELECT 
 1 AS `id`,
 1 AS `document_id`,
 1 AS `iotype`,
 1 AS `amount`,
 1 AS `document_date`,
 1 AS `branch_id`*/;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `issue_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `issue_history` (
  `hist_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `issue_id` int(11) NOT NULL,
  `createdon` date NOT NULL,
  `user_id` int(11) NOT NULL,
  `description` varchar(255) NOT NULL,
  PRIMARY KEY (`hist_id`),
  KEY `issue_id` (`issue_id`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `issue_issuelist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `issue_issuelist` (
  `issue_id` int(11) NOT NULL AUTO_INCREMENT,
  `issue_name` varchar(255) NOT NULL,
  `details` longtext NOT NULL,
  `status` smallint(6) NOT NULL,
  `priority` tinyint(4) NOT NULL,
  `user_id` int(11) NOT NULL,
  `lastupdate` datetime DEFAULT NULL,
  `project_id` int(11) NOT NULL,
  PRIMARY KEY (`issue_id`),
  KEY `project_id` (`project_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `issue_issuelist_view`;
/*!50001 DROP VIEW IF EXISTS `issue_issuelist_view`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `issue_issuelist_view` AS SELECT 
 1 AS `issue_id`,
 1 AS `issue_name`,
 1 AS `details`,
 1 AS `status`,
 1 AS `priority`,
 1 AS `user_id`,
 1 AS `lastupdate`,
 1 AS `project_id`,
 1 AS `username`,
 1 AS `project_name`*/;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `issue_projectacc`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `issue_projectacc` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `issue_projectlist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `issue_projectlist` (
  `project_id` int(11) NOT NULL AUTO_INCREMENT,
  `project_name` varchar(255) NOT NULL,
  `details` longtext NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `status` smallint(6) DEFAULT NULL,
  PRIMARY KEY (`project_id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `issue_projectlist_view`;
/*!50001 DROP VIEW IF EXISTS `issue_projectlist_view`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `issue_projectlist_view` AS SELECT 
 1 AS `project_id`,
 1 AS `project_name`,
 1 AS `details`,
 1 AS `customer_id`,
 1 AS `status`,
 1 AS `customer_name`,
 1 AS `inew`,
 1 AS `iproc`,
 1 AS `iclose`*/;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `issue_time`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `issue_time` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `issue_id` int(11) NOT NULL,
  `createdon` datetime NOT NULL,
  `user_id` int(11) NOT NULL,
  `duration` decimal(10,2) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `issue_id` (`issue_id`)
) ENGINE=InnoDB AUTO_INCREMENT=48 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `issue_time_view`;
/*!50001 DROP VIEW IF EXISTS `issue_time_view`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `issue_time_view` AS SELECT 
 1 AS `id`,
 1 AS `issue_id`,
 1 AS `createdon`,
 1 AS `user_id`,
 1 AS `duration`,
 1 AS `notes`,
 1 AS `username`,
 1 AS `issue_name`,
 1 AS `project_id`,
 1 AS `project_name`*/;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `item_cat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `item_cat` (
  `cat_id` int(11) NOT NULL AUTO_INCREMENT,
  `cat_name` varchar(255) NOT NULL,
  `detail` longtext,
  `parent_id` int(11) DEFAULT '0',
  PRIMARY KEY (`cat_id`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `item_set`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `item_set` (
  `set_id` int(11) NOT NULL AUTO_INCREMENT,
  `item_id` int(11) DEFAULT '0',
  `pitem_id` int(11) NOT NULL DEFAULT '0',
  `qty` decimal(11,3) DEFAULT '0.000',
  `service_id` int(11) DEFAULT '0',
  `cost` decimal(10,2) DEFAULT '0.00',
  PRIMARY KEY (`set_id`)
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `item_set_view`;
/*!50001 DROP VIEW IF EXISTS `item_set_view`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `item_set_view` AS SELECT 
 1 AS `set_id`,
 1 AS `item_id`,
 1 AS `pitem_id`,
 1 AS `qty`,
 1 AS `service_id`,
 1 AS `cost`,
 1 AS `itemname`,
 1 AS `item_code`,
 1 AS `service_name`*/;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `items` (
  `item_id` int(11) NOT NULL AUTO_INCREMENT,
  `itemname` varchar(255) DEFAULT NULL,
  `description` longtext,
  `detail` longtext NOT NULL,
  `item_code` varchar(64) DEFAULT NULL,
  `bar_code` varchar(64) DEFAULT NULL,
  `cat_id` int(11) NOT NULL,
  `msr` varchar(64) DEFAULT NULL,
  `disabled` tinyint(1) DEFAULT '0',
  `minqty` decimal(11,3) DEFAULT '0.000',
  `manufacturer` varchar(355) DEFAULT NULL,
  `item_type` int(11) DEFAULT NULL,
  PRIMARY KEY (`item_id`),
  KEY `item_code` (`item_code`),
  KEY `itemname` (`itemname`),
  KEY `cat_id` (`cat_id`)
) ENGINE=InnoDB AUTO_INCREMENT=962 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `items_view`;
/*!50001 DROP VIEW IF EXISTS `items_view`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `items_view` AS SELECT 
 1 AS `item_id`,
 1 AS `itemname`,
 1 AS `description`,
 1 AS `detail`,
 1 AS `item_code`,
 1 AS `bar_code`,
 1 AS `cat_id`,
 1 AS `msr`,
 1 AS `disabled`,
 1 AS `minqty`,
 1 AS `item_type`,
 1 AS `manufacturer`,
 1 AS `cat_name`*/;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `keyval`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `keyval` (
  `keyd` varchar(255) NOT NULL,
  `vald` text NOT NULL,
  PRIMARY KEY (`keyd`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `messages` (
  `message_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `message` text,
  `item_id` int(11) NOT NULL,
  `item_type` int(11) DEFAULT NULL,
  PRIMARY KEY (`message_id`),
  KEY `item_id` (`item_id`)
) ENGINE=InnoDB AUTO_INCREMENT=149 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `messages_view`;
/*!50001 DROP VIEW IF EXISTS `messages_view`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `messages_view` AS SELECT 
 1 AS `message_id`,
 1 AS `user_id`,
 1 AS `created`,
 1 AS `message`,
 1 AS `item_id`,
 1 AS `item_type`,
 1 AS `username`*/;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `metadata`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `metadata` (
  `meta_id` int(11) NOT NULL AUTO_INCREMENT,
  `meta_type` tinyint(11) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `meta_name` varchar(255) NOT NULL,
  `menugroup` varchar(255) DEFAULT NULL,
  `disabled` tinyint(4) NOT NULL,
  PRIMARY KEY (`meta_id`)
) ENGINE=InnoDB AUTO_INCREMENT=116 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `mfund`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mfund` (
  `mf_id` int(11) NOT NULL AUTO_INCREMENT,
  `mf_name` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `branch_id` int(11) DEFAULT '0',
  `detail` longtext,
  PRIMARY KEY (`mf_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `note_fav`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `note_fav` (
  `fav_id` int(11) NOT NULL AUTO_INCREMENT,
  `topic_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`fav_id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `note_nodes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `note_nodes` (
  `node_id` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(11) NOT NULL,
  `title` varchar(50) NOT NULL,
  `mpath` varchar(255) CHARACTER SET latin1 NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `ispublic` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`node_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `note_nodesview`;
/*!50001 DROP VIEW IF EXISTS `note_nodesview`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `note_nodesview` AS SELECT 
 1 AS `node_id`,
 1 AS `pid`,
 1 AS `title`,
 1 AS `mpath`,
 1 AS `user_id`,
 1 AS `ispublic`,
 1 AS `tcnt`*/;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `note_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `note_tags` (
  `tag_id` int(11) NOT NULL AUTO_INCREMENT,
  `topic_id` int(11) NOT NULL,
  `tagvalue` varchar(255) NOT NULL,
  PRIMARY KEY (`tag_id`),
  KEY `topic_id` (`topic_id`)
) ENGINE=InnoDB AUTO_INCREMENT=47 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `note_topicnode`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `note_topicnode` (
  `topic_id` int(11) NOT NULL,
  `node_id` int(11) NOT NULL,
  `tn_id` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tn_id`),
  KEY `topic_id` (`topic_id`),
  KEY `node_id` (`node_id`)
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `note_topicnodeview`;
/*!50001 DROP VIEW IF EXISTS `note_topicnodeview`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `note_topicnodeview` AS SELECT 
 1 AS `topic_id`,
 1 AS `node_id`,
 1 AS `tn_id`,
 1 AS `title`,
 1 AS `user_id`,
 1 AS `content`*/;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `note_topics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `note_topics` (
  `topic_id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `content` longtext NOT NULL,
  `acctype` smallint(4) DEFAULT '0',
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`topic_id`)
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `note_topicsview`;
/*!50001 DROP VIEW IF EXISTS `note_topicsview`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `note_topicsview` AS SELECT 
 1 AS `topic_id`,
 1 AS `title`,
 1 AS `content`,
 1 AS `acctype`,
 1 AS `user_id`*/;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `notifies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifies` (
  `notify_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `dateshow` datetime NOT NULL,
  `checked` tinyint(1) NOT NULL DEFAULT '0',
  `message` text,
  `sender_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`notify_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=383 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `options`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `options` (
  `optname` varchar(64) NOT NULL,
  `optvalue` longtext NOT NULL,
  UNIQUE KEY `optname` (`optname`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `parealist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `parealist` (
  `pa_id` int(11) NOT NULL AUTO_INCREMENT,
  `pa_name` varchar(255) NOT NULL,
  PRIMARY KEY (`pa_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `paylist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `paylist` (
  `pl_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `document_id` int(11) NOT NULL,
  `amount` decimal(11,2) NOT NULL,
  `mf_id` int(11) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `paydate` datetime DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `paytype` smallint(6) NOT NULL,
  `detail` longtext,
  `bonus` int(11) DEFAULT NULL,
  PRIMARY KEY (`pl_id`),
  KEY `document_id` (`document_id`),
  CONSTRAINT `paylist_ibfk_1` FOREIGN KEY (`document_id`) REFERENCES `documents` (`document_id`)
) ENGINE=InnoDB AUTO_INCREMENT=827 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `paylist_view`;
/*!50001 DROP VIEW IF EXISTS `paylist_view`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `paylist_view` AS SELECT 
 1 AS `pl_id`,
 1 AS `document_id`,
 1 AS `amount`,
 1 AS `mf_id`,
 1 AS `notes`,
 1 AS `user_id`,
 1 AS `paydate`,
 1 AS `paytype`,
 1 AS `bonus`,
 1 AS `document_number`,
 1 AS `username`,
 1 AS `mf_name`,
 1 AS `customer_id`,
 1 AS `customer_name`*/;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `poslist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `poslist` (
  `pos_id` int(11) NOT NULL AUTO_INCREMENT,
  `pos_name` varchar(255) NOT NULL,
  `details` longtext NOT NULL,
  `branch_id` int(11) DEFAULT '0',
  PRIMARY KEY (`pos_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ppo_zformrep`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ppo_zformrep` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `createdon` date NOT NULL,
  `fnpos` varchar(255) NOT NULL,
  `fndoc` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `cnt` smallint(6) NOT NULL,
  `ramount` decimal(10,2) NOT NULL,
  `rcnt` smallint(6) NOT NULL,
  `sentxml` longtext NOT NULL,
  `taxanswer` longblob NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ppo_zformstat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ppo_zformstat` (
  `zf_id` int(11) NOT NULL AUTO_INCREMENT,
  `pos_id` int(11) NOT NULL,
  `checktype` int(11) NOT NULL,
  `createdon` datetime NOT NULL,
  `amount0` decimal(10,2) NOT NULL,
  `amount1` decimal(10,2) NOT NULL,
  `amount2` decimal(10,2) NOT NULL,
  `amount3` decimal(10,2) NOT NULL,
  `document_number` varchar(255) DEFAULT NULL,
  `fiscnumber` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`zf_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `prodproc`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `prodproc` (
  `pp_id` int(11) NOT NULL AUTO_INCREMENT,
  `procname` varchar(255) NOT NULL,
  `basedoc` varchar(255) DEFAULT NULL,
  `snumber` varchar(255) DEFAULT NULL,
  `state` smallint(4) DEFAULT '0',
  `detail` longtext,
  PRIMARY KEY (`pp_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `prodproc_view`;
/*!50001 DROP VIEW IF EXISTS `prodproc_view`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `prodproc_view` AS SELECT 
 1 AS `pp_id`,
 1 AS `procname`,
 1 AS `basedoc`,
 1 AS `snumber`,
 1 AS `state`,
 1 AS `startdate`,
 1 AS `enddate`,
 1 AS `stagecnt`,
 1 AS `detail`*/;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `prodstage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `prodstage` (
  `st_id` int(11) NOT NULL AUTO_INCREMENT,
  `pp_id` int(11) NOT NULL,
  `pa_id` int(11) NOT NULL,
  `state` smallint(6) NOT NULL,
  `stagename` varchar(255) NOT NULL,
  `detail` longtext,
  PRIMARY KEY (`st_id`),
  KEY `pp_id` (`pp_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `prodstage_view`;
/*!50001 DROP VIEW IF EXISTS `prodstage_view`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `prodstage_view` AS SELECT 
 1 AS `st_id`,
 1 AS `pp_id`,
 1 AS `pa_id`,
 1 AS `state`,
 1 AS `stagename`,
 1 AS `startdate`,
 1 AS `enddate`,
 1 AS `hours`,
 1 AS `detail`,
 1 AS `procname`,
 1 AS `snumber`,
 1 AS `procstate`,
 1 AS `pa_name`*/;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `prodstageagenda`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `prodstageagenda` (
  `sta_id` int(11) NOT NULL AUTO_INCREMENT,
  `st_id` int(11) NOT NULL,
  `startdate` datetime NOT NULL,
  `enddate` datetime NOT NULL,
  PRIMARY KEY (`sta_id`),
  KEY `st_id` (`st_id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `prodstageagenda_view`;
/*!50001 DROP VIEW IF EXISTS `prodstageagenda_view`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `prodstageagenda_view` AS SELECT 
 1 AS `sta_id`,
 1 AS `st_id`,
 1 AS `startdate`,
 1 AS `enddate`,
 1 AS `stagename`,
 1 AS `state`,
 1 AS `hours`,
 1 AS `pa_id`,
 1 AS `pp_id`*/;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roles` (
  `role_id` int(11) NOT NULL AUTO_INCREMENT,
  `rolename` varchar(255) DEFAULT NULL,
  `acl` mediumtext,
  PRIMARY KEY (`role_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `roles_view`;
/*!50001 DROP VIEW IF EXISTS `roles_view`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `roles_view` AS SELECT 
 1 AS `role_id`,
 1 AS `rolename`,
 1 AS `acl`,
 1 AS `cnt`*/;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `saltypes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `saltypes` (
  `st_id` int(11) NOT NULL AUTO_INCREMENT,
  `salcode` int(11) NOT NULL,
  `salname` varchar(255) NOT NULL,
  `salshortname` varchar(255) DEFAULT NULL,
  `disabled` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`st_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `services` (
  `service_id` int(11) NOT NULL AUTO_INCREMENT,
  `service_name` varchar(255) NOT NULL,
  `detail` text,
  `disabled` tinyint(1) DEFAULT '0',
  `category` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`service_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `shop_attributes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `shop_attributes` (
  `attribute_id` int(11) NOT NULL AUTO_INCREMENT,
  `attributename` varchar(64) NOT NULL,
  `cat_id` int(11) NOT NULL,
  `attributetype` tinyint(4) NOT NULL,
  `valueslist` text,
  PRIMARY KEY (`attribute_id`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `shop_attributes_order`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `shop_attributes_order` (
  `order_id` int(11) NOT NULL AUTO_INCREMENT,
  `attr_id` int(11) NOT NULL,
  `pg_id` int(11) NOT NULL,
  `ordern` int(11) NOT NULL,
  PRIMARY KEY (`order_id`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `shop_attributes_view`;
/*!50001 DROP VIEW IF EXISTS `shop_attributes_view`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `shop_attributes_view` AS SELECT 
 1 AS `attribute_id`,
 1 AS `attributename`,
 1 AS `cat_id`,
 1 AS `attributetype`,
 1 AS `valueslist`,
 1 AS `ordern`*/;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `shop_attributevalues`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `shop_attributevalues` (
  `attributevalue_id` int(11) NOT NULL AUTO_INCREMENT,
  `attribute_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `attributevalue` varchar(255) NOT NULL,
  PRIMARY KEY (`attributevalue_id`),
  KEY `attribute_id` (`attribute_id`)
) ENGINE=InnoDB AUTO_INCREMENT=82 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `shop_prod_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `shop_prod_comments` (
  `comment_id` int(11) NOT NULL AUTO_INCREMENT,
  `item_id` int(11) NOT NULL,
  `author` varchar(64) NOT NULL,
  `comment` text NOT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `rating` tinyint(4) NOT NULL DEFAULT '0',
  `moderated` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`comment_id`),
  KEY `product_id` (`item_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `shop_products_view`;
/*!50001 DROP VIEW IF EXISTS `shop_products_view`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `shop_products_view` AS SELECT 
 1 AS `item_id`,
 1 AS `itemname`,
 1 AS `description`,
 1 AS `detail`,
 1 AS `item_code`,
 1 AS `bar_code`,
 1 AS `cat_id`,
 1 AS `msr`,
 1 AS `disabled`,
 1 AS `minqty`,
 1 AS `item_type`,
 1 AS `manufacturer`,
 1 AS `cat_name`,
 1 AS `qty`,
 1 AS `comments`,
 1 AS `ratings`*/;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `shop_varitems`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `shop_varitems` (
  `varitem_id` int(11) NOT NULL AUTO_INCREMENT,
  `var_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  PRIMARY KEY (`varitem_id`),
  KEY `item_id` (`item_id`),
  KEY `var_id` (`var_id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `shop_varitems_view`;
/*!50001 DROP VIEW IF EXISTS `shop_varitems_view`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `shop_varitems_view` AS SELECT 
 1 AS `varitem_id`,
 1 AS `var_id`,
 1 AS `item_id`,
 1 AS `attr_id`,
 1 AS `attributevalue`,
 1 AS `itemname`,
 1 AS `item_code`*/;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `shop_vars`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `shop_vars` (
  `var_id` int(11) NOT NULL AUTO_INCREMENT,
  `attr_id` int(11) NOT NULL,
  `varname` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`var_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `shop_vars_view`;
/*!50001 DROP VIEW IF EXISTS `shop_vars_view`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `shop_vars_view` AS SELECT 
 1 AS `var_id`,
 1 AS `attr_id`,
 1 AS `varname`,
 1 AS `attributename`,
 1 AS `cat_id`,
 1 AS `cnt`*/;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `stats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stats` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `category` smallint(6) NOT NULL,
  `keyd` int(11) NOT NULL,
  `vald` int(11) NOT NULL,
  `dt` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `category` (`category`),
  KEY `dt` (`dt`)
) ENGINE=MyISAM AUTO_INCREMENT=37 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `store_stock`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `store_stock` (
  `stock_id` int(11) NOT NULL AUTO_INCREMENT,
  `item_id` int(11) NOT NULL,
  `partion` decimal(11,2) DEFAULT NULL,
  `store_id` int(11) NOT NULL,
  `qty` decimal(11,3) DEFAULT '0.000',
  `snumber` varchar(64) DEFAULT NULL,
  `sdate` date DEFAULT NULL,
  PRIMARY KEY (`stock_id`),
  KEY `item_id` (`item_id`),
  KEY `store_id` (`store_id`),
  CONSTRAINT `store_stock_fk` FOREIGN KEY (`store_id`) REFERENCES `stores` (`store_id`),
  CONSTRAINT `store_stock_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `items` (`item_id`)
) ENGINE=InnoDB AUTO_INCREMENT=656 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `store_stock_view`;
/*!50001 DROP VIEW IF EXISTS `store_stock_view`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `store_stock_view` AS SELECT 
 1 AS `stock_id`,
 1 AS `item_id`,
 1 AS `partion`,
 1 AS `store_id`,
 1 AS `itemname`,
 1 AS `item_code`,
 1 AS `cat_id`,
 1 AS `msr`,
 1 AS `item_type`,
 1 AS `bar_code`,
 1 AS `cat_name`,
 1 AS `itemdisabled`,
 1 AS `storename`,
 1 AS `qty`,
 1 AS `snumber`,
 1 AS `sdate`*/;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `stores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stores` (
  `store_id` int(11) NOT NULL AUTO_INCREMENT,
  `storename` varchar(64) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `branch_id` int(11) DEFAULT '0',
  PRIMARY KEY (`store_id`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `subscribes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `subscribes` (
  `sub_id` int(11) NOT NULL AUTO_INCREMENT,
  `sub_type` int(11) DEFAULT NULL,
  `reciever_type` int(11) DEFAULT NULL,
  `msg_type` int(11) DEFAULT NULL,
  `msgtext` text,
  `detail` longtext,
  `disabled` int(1) DEFAULT '0',
  PRIMARY KEY (`sub_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `supitems`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `supitems` (
  `supitem_id` int(11) NOT NULL AUTO_INCREMENT,
  `item_id` int(11) NOT NULL,
  `sup_id` int(11) NOT NULL,
  `quantity` decimal(10,3) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `sup_code` varchar(255) DEFAULT NULL,
  `comment` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`supitem_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `timesheet`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `timesheet` (
  `time_id` int(11) NOT NULL AUTO_INCREMENT,
  `emp_id` int(11) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `t_start` datetime DEFAULT NULL,
  `t_end` datetime DEFAULT NULL,
  `t_type` int(11) DEFAULT '0',
  `t_break` smallint(6) DEFAULT '0',
  `branch_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`time_id`),
  KEY `emp_id` (`emp_id`)
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `timesheet_view`;
/*!50001 DROP VIEW IF EXISTS `timesheet_view`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `timesheet_view` AS SELECT 
 1 AS `time_id`,
 1 AS `emp_id`,
 1 AS `description`,
 1 AS `t_start`,
 1 AS `t_end`,
 1 AS `t_type`,
 1 AS `t_break`,
 1 AS `emp_name`,
 1 AS `branch_name`,
 1 AS `disabled`,
 1 AS `branch_id`*/;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `userlogin` varchar(32) NOT NULL,
  `userpass` varchar(255) NOT NULL,
  `createdon` date NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `acl` mediumtext NOT NULL,
  `disabled` int(1) NOT NULL DEFAULT '0',
  `options` longtext,
  `role_id` int(11) DEFAULT NULL,
  `lastactive` datetime DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `userlogin` (`userlogin`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users_view`;
/*!50001 DROP VIEW IF EXISTS `users_view`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `users_view` AS SELECT 
 1 AS `user_id`,
 1 AS `userlogin`,
 1 AS `userpass`,
 1 AS `createdon`,
 1 AS `email`,
 1 AS `acl`,
 1 AS `options`,
 1 AS `disabled`,
 1 AS `lastactive`,
 1 AS `rolename`,
 1 AS `role_id`,
 1 AS `roleacl`,
 1 AS `employee_id`,
 1 AS `username`*/;
SET character_set_client = @saved_cs_client;
/*!50001 DROP VIEW IF EXISTS `contracts_view`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = latin1 */;
/*!50001 SET character_set_results     = latin1 */;
/*!50001 SET collation_connection      = latin1_swedish_ci */;
/*!50001 CREATE  */
/*!50013   */
/*!50001 VIEW `contracts_view` AS select `co`.`contract_id` AS `contract_id`,`co`.`customer_id` AS `customer_id`,`co`.`firm_id` AS `firm_id`,`co`.`createdon` AS `createdon`,`co`.`contract_number` AS `contract_number`,`co`.`disabled` AS `disabled`,`co`.`details` AS `details`,`cu`.`customer_name` AS `customer_name`,`f`.`firm_name` AS `firm_name` from ((`contracts` `co` join `customers` `cu` on((`co`.`customer_id` = `cu`.`customer_id`))) left join `firms` `f` on((`co`.`firm_id` = `f`.`firm_id`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `cust_acc_view`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8 */;
/*!50001 SET character_set_results     = utf8 */;
/*!50001 SET collation_connection      = utf8_general_ci */;
/*!50001 CREATE  */
/*!50013   */
/*!50001 VIEW `cust_acc_view` AS select coalesce(sum((case when (`d`.`meta_name` in ('InvoiceCust','GoodsReceipt','IncomeService','OutcomeMoney')) then `d`.`payed` when ((`d`.`meta_name` = 'OutcomeMoney') and (`d`.`content` like '%<detail>2</detail>%')) then `d`.`payed` when (`d`.`meta_name` = 'RetCustIssue') then `d`.`payamount` else 0 end)),0) AS `s_passive`,coalesce(sum((case when (`d`.`meta_name` in ('IncomeService','GoodsReceipt')) then `d`.`payamount` when ((`d`.`meta_name` = 'IncomeMoney') and (`d`.`content` like '%<detail>2</detail>%')) then `d`.`payed` when (`d`.`meta_name` = 'RetCustIssue') then `d`.`payed` else 0 end)),0) AS `s_active`,coalesce(sum((case when (`d`.`meta_name` in ('GoodsIssue','TTN','PosCheck','OrderFood','ServiceAct')) then `d`.`payamount` when ((`d`.`meta_name` = 'OutcomeMoney') and (`d`.`content` like '%<detail>1</detail>%')) then `d`.`payed` when (`d`.`meta_name` = 'ReturnIssue') then `d`.`payed` else 0 end)),0) AS `b_passive`,coalesce(sum((case when (`d`.`meta_name` in ('GoodsIssue','Order','PosCheck','OrderFood','Invoice','ServiceAct')) then `d`.`payed` when ((`d`.`meta_name` = 'IncomeMoney') and (`d`.`content` like '%<detail>1</detail>%')) then `d`.`payed` when (`d`.`meta_name` = 'ReturnIssue') then `d`.`payamount` else 0 end)),0) AS `b_active`,`d`.`customer_id` AS `customer_id` from `documents_view` `d` where ((`d`.`state` not in (0,1,2,3,15,8,17)) and (`d`.`customer_id` > 0)) group by `d`.`customer_id` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `custitems_view`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8 */;
/*!50001 SET character_set_results     = utf8 */;
/*!50001 SET collation_connection      = utf8_general_ci */;
/*!50001 CREATE  */
/*!50013   */
/*!50001 VIEW `custitems_view` AS select `s`.`custitem_id` AS `custitem_id`,`s`.`item_id` AS `item_id`,`s`.`customer_id` AS `customer_id`,`s`.`quantity` AS `quantity`,`s`.`price` AS `price`,`s`.`updatedon` AS `updatedon`,`s`.`cust_code` AS `cust_code`,`s`.`comment` AS `comment`,`i`.`itemname` AS `itemname`,`i`.`cat_id` AS `cat_id`,`i`.`item_code` AS `item_code`,`i`.`detail` AS `detail`,`c`.`customer_name` AS `customer_name` from ((`custitems` `s` join `items` `i` on((`s`.`item_id` = `i`.`item_id`))) join `customers` `c` on((`s`.`customer_id` = `c`.`customer_id`))) where ((`i`.`disabled` <> 1) and (`c`.`status` <> 1)) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `customers_view`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8 */;
/*!50001 SET character_set_results     = utf8 */;
/*!50001 SET collation_connection      = utf8_general_ci */;
/*!50001 CREATE  */
/*!50013   */
/*!50001 VIEW `customers_view` AS select `customers`.`customer_id` AS `customer_id`,`customers`.`customer_name` AS `customer_name`,`customers`.`detail` AS `detail`,`customers`.`email` AS `email`,`customers`.`phone` AS `phone`,`customers`.`status` AS `status`,`customers`.`city` AS `city`,`customers`.`leadsource` AS `leadsource`,`customers`.`leadstatus` AS `leadstatus`,`customers`.`country` AS `country`,`customers`.`passw` AS `passw`,(select count(0) from `messages` `m` where ((`m`.`item_id` = `customers`.`customer_id`) and (`m`.`item_type` = 2))) AS `mcnt`,(select count(0) from `files` `f` where ((`f`.`item_id` = `customers`.`customer_id`) and (`f`.`item_type` = 2))) AS `fcnt`,(select count(0) from `eventlist` `e` where ((`e`.`customer_id` = `customers`.`customer_id`) and (`e`.`eventdate` >= now()))) AS `ecnt` from `customers` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `docstatelog_view`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = latin1 */;
/*!50001 SET character_set_results     = latin1 */;
/*!50001 SET collation_connection      = latin1_swedish_ci */;
/*!50001 CREATE  */
/*!50013   */
/*!50001 VIEW `docstatelog_view` AS select `dl`.`log_id` AS `log_id`,`dl`.`user_id` AS `user_id`,`dl`.`document_id` AS `document_id`,`dl`.`docstate` AS `docstate`,`dl`.`createdon` AS `createdon`,`dl`.`hostname` AS `hostname`,`u`.`username` AS `username`,`d`.`document_number` AS `document_number`,`d`.`meta_desc` AS `meta_desc`,`d`.`meta_name` AS `meta_name` from ((`docstatelog` `dl` join `users_view` `u` on((`dl`.`user_id` = `u`.`user_id`))) join `documents_view` `d` on((`d`.`document_id` = `dl`.`document_id`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `documents_view`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8 */;
/*!50001 SET character_set_results     = utf8 */;
/*!50001 SET collation_connection      = utf8_general_ci */;
/*!50001 CREATE  */
/*!50013   */
/*!50001 VIEW `documents_view` AS select `d`.`document_id` AS `document_id`,`d`.`document_number` AS `document_number`,`d`.`document_date` AS `document_date`,`d`.`user_id` AS `user_id`,`d`.`content` AS `content`,`d`.`amount` AS `amount`,`d`.`meta_id` AS `meta_id`,`u`.`username` AS `username`,`c`.`customer_id` AS `customer_id`,`c`.`customer_name` AS `customer_name`,`d`.`state` AS `state`,`d`.`notes` AS `notes`,`d`.`payamount` AS `payamount`,`d`.`payed` AS `payed`,`d`.`parent_id` AS `parent_id`,`d`.`branch_id` AS `branch_id`,`b`.`branch_name` AS `branch_name`,`d`.`firm_id` AS `firm_id`,`d`.`priority` AS `priority`,`f`.`firm_name` AS `firm_name`,`d`.`lastupdate` AS `lastupdate`,`metadata`.`meta_name` AS `meta_name`,`metadata`.`description` AS `meta_desc` from (((((`documents` `d` left join `users_view` `u` on((`d`.`user_id` = `u`.`user_id`))) left join `customers` `c` on((`d`.`customer_id` = `c`.`customer_id`))) join `metadata` on((`metadata`.`meta_id` = `d`.`meta_id`))) left join `branches` `b` on((`d`.`branch_id` = `b`.`branch_id`))) left join `firms` `f` on((`d`.`firm_id` = `f`.`firm_id`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `empacc_view`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8 */;
/*!50001 SET character_set_results     = utf8 */;
/*!50001 SET collation_connection      = utf8_general_ci */;
/*!50001 CREATE  */
/*!50013   */
/*!50001 VIEW `empacc_view` AS select `e`.`ea_id` AS `ea_id`,`e`.`emp_id` AS `emp_id`,`e`.`document_id` AS `document_id`,`e`.`optype` AS `optype`,`d`.`notes` AS `notes`,`e`.`amount` AS `amount`,coalesce(`e`.`createdon`,`d`.`document_date`) AS `createdon`,`d`.`document_number` AS `document_number`,`em`.`emp_name` AS `emp_name` from ((`empacc` `e` left join `documents` `d` on((`d`.`document_id` = `e`.`document_id`))) join `employees` `em` on((`em`.`employee_id` = `e`.`emp_id`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `entrylist_view`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE  */
/*!50013   */
/*!50001 VIEW `entrylist_view` AS select `entrylist`.`entry_id` AS `entry_id`,`entrylist`.`document_id` AS `document_id`,`entrylist`.`quantity` AS `quantity`,`documents`.`customer_id` AS `customer_id`,`entrylist`.`stock_id` AS `stock_id`,`entrylist`.`service_id` AS `service_id`,`entrylist`.`tag` AS `tag`,`store_stock`.`item_id` AS `item_id`,`store_stock`.`partion` AS `partion`,`documents`.`document_date` AS `document_date`,`entrylist`.`outprice` AS `outprice` from ((`entrylist` left join `store_stock` on((`entrylist`.`stock_id` = `store_stock`.`stock_id`))) join `documents` on((`entrylist`.`document_id` = `documents`.`document_id`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `eventlist_view`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE  */
/*!50013   */
/*!50001 VIEW `eventlist_view` AS select `e`.`user_id` AS `user_id`,`e`.`eventdate` AS `eventdate`,`e`.`title` AS `title`,`e`.`description` AS `description`,`e`.`event_id` AS `event_id`,`e`.`customer_id` AS `customer_id`,`e`.`isdone` AS `isdone`,`c`.`customer_name` AS `customer_name` from (`eventlist` `e` left join `customers` `c` on((`e`.`customer_id` = `c`.`customer_id`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `iostate_view`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8 */;
/*!50001 SET character_set_results     = utf8 */;
/*!50001 SET collation_connection      = utf8_general_ci */;
/*!50001 CREATE  */
/*!50013   */
/*!50001 VIEW `iostate_view` AS select `s`.`id` AS `id`,`s`.`document_id` AS `document_id`,`s`.`iotype` AS `iotype`,`s`.`amount` AS `amount`,`d`.`document_date` AS `document_date`,`d`.`branch_id` AS `branch_id` from (`iostate` `s` join `documents` `d` on((`s`.`document_id` = `d`.`document_id`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `issue_issuelist_view`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = latin1 */;
/*!50001 SET character_set_results     = latin1 */;
/*!50001 SET collation_connection      = latin1_swedish_ci */;
/*!50001 CREATE  */
/*!50013   */
/*!50001 VIEW `issue_issuelist_view` AS select `i`.`issue_id` AS `issue_id`,`i`.`issue_name` AS `issue_name`,`i`.`details` AS `details`,`i`.`status` AS `status`,`i`.`priority` AS `priority`,`i`.`user_id` AS `user_id`,`i`.`lastupdate` AS `lastupdate`,`i`.`project_id` AS `project_id`,`u`.`username` AS `username`,`p`.`project_name` AS `project_name` from ((`issue_issuelist` `i` left join `users_view` `u` on((`i`.`user_id` = `u`.`user_id`))) join `issue_projectlist` `p` on((`i`.`project_id` = `p`.`project_id`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `issue_projectlist_view`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = latin1 */;
/*!50001 SET character_set_results     = latin1 */;
/*!50001 SET collation_connection      = latin1_swedish_ci */;
/*!50001 CREATE  */
/*!50013   */
/*!50001 VIEW `issue_projectlist_view` AS select `p`.`project_id` AS `project_id`,`p`.`project_name` AS `project_name`,`p`.`details` AS `details`,`p`.`customer_id` AS `customer_id`,`p`.`status` AS `status`,`c`.`customer_name` AS `customer_name`,(select coalesce(sum((case when (`i`.`status` = 0) then 1 else 0 end)),0) from `issue_issuelist` `i` where (`i`.`project_id` = `p`.`project_id`)) AS `inew`,(select coalesce(sum((case when (`i`.`status` > 1) then 1 else 0 end)),0) from `issue_issuelist` `i` where (`i`.`project_id` = `p`.`project_id`)) AS `iproc`,(select coalesce(sum((case when (`i`.`status` = 1) then 1 else 0 end)),0) from `issue_issuelist` `i` where (`i`.`project_id` = `p`.`project_id`)) AS `iclose` from (`issue_projectlist` `p` left join `customers` `c` on((`p`.`customer_id` = `c`.`customer_id`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `issue_time_view`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = latin1 */;
/*!50001 SET character_set_results     = latin1 */;
/*!50001 SET collation_connection      = latin1_swedish_ci */;
/*!50001 CREATE  */
/*!50013   */
/*!50001 VIEW `issue_time_view` AS select `t`.`id` AS `id`,`t`.`issue_id` AS `issue_id`,`t`.`createdon` AS `createdon`,`t`.`user_id` AS `user_id`,`t`.`duration` AS `duration`,`t`.`notes` AS `notes`,`u`.`username` AS `username`,`i`.`issue_name` AS `issue_name`,`i`.`project_id` AS `project_id`,`i`.`project_name` AS `project_name` from ((`issue_time` `t` join `users_view` `u` on((`t`.`user_id` = `u`.`user_id`))) join `issue_issuelist_view` `i` on((`t`.`issue_id` = `i`.`issue_id`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `item_set_view`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8 */;
/*!50001 SET character_set_results     = utf8 */;
/*!50001 SET collation_connection      = utf8_general_ci */;
/*!50001 CREATE  */
/*!50013   */
/*!50001 VIEW `item_set_view` AS select `item_set`.`set_id` AS `set_id`,`item_set`.`item_id` AS `item_id`,`item_set`.`pitem_id` AS `pitem_id`,`item_set`.`qty` AS `qty`,`item_set`.`service_id` AS `service_id`,`item_set`.`cost` AS `cost`,`items`.`itemname` AS `itemname`,`items`.`item_code` AS `item_code`,`services`.`service_name` AS `service_name` from ((`item_set` left join `items` on(((`item_set`.`item_id` = `items`.`item_id`) and (`items`.`disabled` <> 1)))) left join `services` on(((`item_set`.`service_id` = `services`.`service_id`) and (`services`.`disabled` <> 1)))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `items_view`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE  */
/*!50013   */
/*!50001 VIEW `items_view` AS select `items`.`item_id` AS `item_id`,`items`.`itemname` AS `itemname`,`items`.`description` AS `description`,`items`.`detail` AS `detail`,`items`.`item_code` AS `item_code`,`items`.`bar_code` AS `bar_code`,`items`.`cat_id` AS `cat_id`,`items`.`msr` AS `msr`,`items`.`disabled` AS `disabled`,`items`.`minqty` AS `minqty`,`items`.`item_type` AS `item_type`,`items`.`manufacturer` AS `manufacturer`,`item_cat`.`cat_name` AS `cat_name` from (`items` left join `item_cat` on((`items`.`cat_id` = `item_cat`.`cat_id`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `messages_view`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = latin1 */;
/*!50001 SET character_set_results     = latin1 */;
/*!50001 SET collation_connection      = latin1_swedish_ci */;
/*!50001 CREATE  */
/*!50013   */
/*!50001 VIEW `messages_view` AS select `messages`.`message_id` AS `message_id`,`messages`.`user_id` AS `user_id`,`messages`.`created` AS `created`,`messages`.`message` AS `message`,`messages`.`item_id` AS `item_id`,`messages`.`item_type` AS `item_type`,`users_view`.`username` AS `username` from (`messages` join `users_view` on((`messages`.`user_id` = `users_view`.`user_id`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `note_nodesview`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = latin1 */;
/*!50001 SET character_set_results     = latin1 */;
/*!50001 SET collation_connection      = latin1_swedish_ci */;
/*!50001 CREATE  */
/*!50013   */
/*!50001 VIEW `note_nodesview` AS select `note_nodes`.`node_id` AS `node_id`,`note_nodes`.`pid` AS `pid`,`note_nodes`.`title` AS `title`,`note_nodes`.`mpath` AS `mpath`,`note_nodes`.`user_id` AS `user_id`,`note_nodes`.`ispublic` AS `ispublic`,(select count(`note_topicnode`.`topic_id`) AS `Count(topic_id)` from `note_topicnode` where (`note_topicnode`.`node_id` = `note_nodes`.`node_id`)) AS `tcnt` from `note_nodes` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `note_topicnodeview`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = latin1 */;
/*!50001 SET character_set_results     = latin1 */;
/*!50001 SET collation_connection      = latin1_swedish_ci */;
/*!50001 CREATE  */
/*!50013   */
/*!50001 VIEW `note_topicnodeview` AS select `note_topicnode`.`topic_id` AS `topic_id`,`note_topicnode`.`node_id` AS `node_id`,`note_topicnode`.`tn_id` AS `tn_id`,`note_topics`.`title` AS `title`,`note_nodes`.`user_id` AS `user_id`,`note_topics`.`content` AS `content` from ((`note_topics` join `note_topicnode` on((`note_topics`.`topic_id` = `note_topicnode`.`topic_id`))) join `note_nodes` on((`note_nodes`.`node_id` = `note_topicnode`.`node_id`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `note_topicsview`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = latin1 */;
/*!50001 SET character_set_results     = latin1 */;
/*!50001 SET collation_connection      = latin1_swedish_ci */;
/*!50001 CREATE  */
/*!50013   */
/*!50001 VIEW `note_topicsview` AS select `t`.`topic_id` AS `topic_id`,`t`.`title` AS `title`,`t`.`content` AS `content`,`t`.`acctype` AS `acctype`,`t`.`user_id` AS `user_id` from `note_topics` `t` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `paylist_view`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8 */;
/*!50001 SET character_set_results     = utf8 */;
/*!50001 SET collation_connection      = utf8_general_ci */;
/*!50001 CREATE  */
/*!50013   */
/*!50001 VIEW `paylist_view` AS select `pl`.`pl_id` AS `pl_id`,`pl`.`document_id` AS `document_id`,`pl`.`amount` AS `amount`,`pl`.`mf_id` AS `mf_id`,`pl`.`notes` AS `notes`,`pl`.`user_id` AS `user_id`,`pl`.`paydate` AS `paydate`,`pl`.`paytype` AS `paytype`,`pl`.`bonus` AS `bonus`,`d`.`document_number` AS `document_number`,`u`.`username` AS `username`,`m`.`mf_name` AS `mf_name`,`d`.`customer_id` AS `customer_id`,`d`.`customer_name` AS `customer_name` from (((`paylist` `pl` join `documents_view` `d` on((`pl`.`document_id` = `d`.`document_id`))) left join `users_view` `u` on((`pl`.`user_id` = `u`.`user_id`))) left join `mfund` `m` on((`pl`.`mf_id` = `m`.`mf_id`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `prodproc_view`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8 */;
/*!50001 SET character_set_results     = utf8 */;
/*!50001 SET collation_connection      = utf8_general_ci */;
/*!50001 CREATE  */
/*!50013   */
/*!50001 VIEW `prodproc_view` AS select `p`.`pp_id` AS `pp_id`,`p`.`procname` AS `procname`,`p`.`basedoc` AS `basedoc`,`p`.`snumber` AS `snumber`,`p`.`state` AS `state`,coalesce((select min(`ps`.`startdate`) from `prodstage_view` `ps` where (`ps`.`pp_id` = `p`.`pp_id`)),NULL) AS `startdate`,coalesce((select max(`ps`.`enddate`) from `prodstage_view` `ps` where (`ps`.`pp_id` = `p`.`pp_id`)),NULL) AS `enddate`,coalesce((select count(0) from `prodstage` `ps` where (`ps`.`pp_id` = `p`.`pp_id`)),NULL) AS `stagecnt`,`p`.`detail` AS `detail` from `prodproc` `p` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `prodstage_view`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8 */;
/*!50001 SET character_set_results     = utf8 */;
/*!50001 SET collation_connection      = utf8_general_ci */;
/*!50001 CREATE  */
/*!50013   */
/*!50001 VIEW `prodstage_view` AS select `ps`.`st_id` AS `st_id`,`ps`.`pp_id` AS `pp_id`,`ps`.`pa_id` AS `pa_id`,`ps`.`state` AS `state`,`ps`.`stagename` AS `stagename`,coalesce((select min(`pag`.`startdate`) from `prodstageagenda` `pag` where (`pag`.`st_id` = `ps`.`st_id`)),NULL) AS `startdate`,coalesce((select max(`pag`.`enddate`) from `prodstageagenda` `pag` where (`pag`.`st_id` = `ps`.`st_id`)),NULL) AS `enddate`,coalesce((select max(`pag`.`hours`) from `prodstageagenda_view` `pag` where (`pag`.`st_id` = `ps`.`st_id`)),NULL) AS `hours`,`ps`.`detail` AS `detail`,`pr`.`procname` AS `procname`,`pr`.`snumber` AS `snumber`,`pr`.`state` AS `procstate`,`pa`.`pa_name` AS `pa_name` from ((`prodstage` `ps` join `prodproc` `pr` on((`pr`.`pp_id` = `ps`.`pp_id`))) join `parealist` `pa` on((`pa`.`pa_id` = `ps`.`pa_id`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `prodstageagenda_view`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8 */;
/*!50001 SET character_set_results     = utf8 */;
/*!50001 SET collation_connection      = utf8_general_ci */;
/*!50001 CREATE  */
/*!50013   */
/*!50001 VIEW `prodstageagenda_view` AS select `a`.`sta_id` AS `sta_id`,`a`.`st_id` AS `st_id`,`a`.`startdate` AS `startdate`,`a`.`enddate` AS `enddate`,`pv`.`stagename` AS `stagename`,`pv`.`state` AS `state`,(timestampdiff(MINUTE,`a`.`startdate`,`a`.`enddate`) / 60) AS `hours`,`pv`.`pa_id` AS `pa_id`,`pv`.`pp_id` AS `pp_id` from (`prodstageagenda` `a` join `prodstage` `pv` on((`a`.`st_id` = `pv`.`st_id`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `roles_view`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE  */
/*!50013   */
/*!50001 VIEW `roles_view` AS select `roles`.`role_id` AS `role_id`,`roles`.`rolename` AS `rolename`,`roles`.`acl` AS `acl`,(select coalesce(count(0),0) from `users` where (`users`.`role_id` = `roles`.`role_id`)) AS `cnt` from `roles` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `shop_attributes_view`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = latin1 */;
/*!50001 SET character_set_results     = latin1 */;
/*!50001 SET collation_connection      = latin1_swedish_ci */;
/*!50001 CREATE  */
/*!50013   */
/*!50001 VIEW `shop_attributes_view` AS select `shop_attributes`.`attribute_id` AS `attribute_id`,`shop_attributes`.`attributename` AS `attributename`,`shop_attributes`.`cat_id` AS `cat_id`,`shop_attributes`.`attributetype` AS `attributetype`,`shop_attributes`.`valueslist` AS `valueslist`,`shop_attributes_order`.`ordern` AS `ordern` from (`shop_attributes` join `shop_attributes_order` on(((`shop_attributes`.`attribute_id` = `shop_attributes_order`.`attr_id`) and (`shop_attributes`.`cat_id` = `shop_attributes_order`.`pg_id`)))) order by `shop_attributes_order`.`ordern` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `shop_products_view`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = latin1 */;
/*!50001 SET character_set_results     = latin1 */;
/*!50001 SET collation_connection      = latin1_swedish_ci */;
/*!50001 CREATE  */
/*!50013   */
/*!50001 VIEW `shop_products_view` AS select `i`.`item_id` AS `item_id`,`i`.`itemname` AS `itemname`,`i`.`description` AS `description`,`i`.`detail` AS `detail`,`i`.`item_code` AS `item_code`,`i`.`bar_code` AS `bar_code`,`i`.`cat_id` AS `cat_id`,`i`.`msr` AS `msr`,`i`.`disabled` AS `disabled`,`i`.`minqty` AS `minqty`,`i`.`item_type` AS `item_type`,`i`.`manufacturer` AS `manufacturer`,`i`.`cat_name` AS `cat_name`,coalesce((select sum(`store_stock`.`qty`) from `store_stock` where (`store_stock`.`item_id` = `i`.`item_id`)),0) AS `qty`,coalesce((select count(0) from `shop_prod_comments` `c` where (`c`.`item_id` = `i`.`item_id`)),0) AS `comments`,coalesce((select sum(`c`.`rating`) from `shop_prod_comments` `c` where (`c`.`item_id` = `i`.`item_id`)),0) AS `ratings` from `items_view` `i` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `shop_varitems_view`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8 */;
/*!50001 SET character_set_results     = utf8 */;
/*!50001 SET collation_connection      = utf8_general_ci */;
/*!50001 CREATE  */
/*!50013   */
/*!50001 VIEW `shop_varitems_view` AS select `shop_varitems`.`varitem_id` AS `varitem_id`,`shop_varitems`.`var_id` AS `var_id`,`shop_varitems`.`item_id` AS `item_id`,`sv`.`attr_id` AS `attr_id`,`sa`.`attributevalue` AS `attributevalue`,`it`.`itemname` AS `itemname`,`it`.`item_code` AS `item_code` from (((`shop_varitems` join `shop_vars` `sv` on((`shop_varitems`.`var_id` = `sv`.`var_id`))) join `shop_attributevalues` `sa` on(((`sa`.`item_id` = `shop_varitems`.`item_id`) and (`sv`.`attr_id` = `sa`.`attribute_id`)))) join `items` `it` on((`shop_varitems`.`item_id` = `it`.`item_id`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `shop_vars_view`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8 */;
/*!50001 SET character_set_results     = utf8 */;
/*!50001 SET collation_connection      = utf8_general_ci */;
/*!50001 CREATE  */
/*!50013   */
/*!50001 VIEW `shop_vars_view` AS select `shop_vars`.`var_id` AS `var_id`,`shop_vars`.`attr_id` AS `attr_id`,`shop_vars`.`varname` AS `varname`,`shop_attributes`.`attributename` AS `attributename`,`shop_attributes`.`cat_id` AS `cat_id`,(select count(0) from `shop_varitems` where (`shop_varitems`.`var_id` = `shop_vars`.`var_id`)) AS `cnt` from ((`shop_vars` join `shop_attributes` on((`shop_vars`.`attr_id` = `shop_attributes`.`attribute_id`))) join `item_cat` on((`shop_attributes`.`cat_id` = `item_cat`.`cat_id`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `store_stock_view`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE  */
/*!50013   */
/*!50001 VIEW `store_stock_view` AS select `st`.`stock_id` AS `stock_id`,`st`.`item_id` AS `item_id`,`st`.`partion` AS `partion`,`st`.`store_id` AS `store_id`,`i`.`itemname` AS `itemname`,`i`.`item_code` AS `item_code`,`i`.`cat_id` AS `cat_id`,`i`.`msr` AS `msr`,`i`.`item_type` AS `item_type`,`i`.`bar_code` AS `bar_code`,`i`.`cat_name` AS `cat_name`,`i`.`disabled` AS `itemdisabled`,`stores`.`storename` AS `storename`,`st`.`qty` AS `qty`,`st`.`snumber` AS `snumber`,`st`.`sdate` AS `sdate` from ((`store_stock` `st` join `items_view` `i` on(((`i`.`item_id` = `st`.`item_id`) and (`i`.`disabled` <> 1)))) join `stores` on((`stores`.`store_id` = `st`.`store_id`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `timesheet_view`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE  */
/*!50013   */
/*!50001 VIEW `timesheet_view` AS select `t`.`time_id` AS `time_id`,`t`.`emp_id` AS `emp_id`,`t`.`description` AS `description`,`t`.`t_start` AS `t_start`,`t`.`t_end` AS `t_end`,`t`.`t_type` AS `t_type`,`t`.`t_break` AS `t_break`,`e`.`emp_name` AS `emp_name`,`b`.`branch_name` AS `branch_name`,`e`.`disabled` AS `disabled`,`t`.`branch_id` AS `branch_id` from ((`timesheet` `t` join `employees` `e` on((`t`.`emp_id` = `e`.`employee_id`))) left join `branches` `b` on((`t`.`branch_id` = `b`.`branch_id`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `users_view`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8 */;
/*!50001 SET character_set_results     = utf8 */;
/*!50001 SET collation_connection      = utf8_general_ci */;
/*!50001 CREATE  */
/*!50013   */
/*!50001 VIEW `users_view` AS select `users`.`user_id` AS `user_id`,`users`.`userlogin` AS `userlogin`,`users`.`userpass` AS `userpass`,`users`.`createdon` AS `createdon`,`users`.`email` AS `email`,`users`.`acl` AS `acl`,`users`.`options` AS `options`,`users`.`disabled` AS `disabled`,`users`.`lastactive` AS `lastactive`,`roles`.`rolename` AS `rolename`,`users`.`role_id` AS `role_id`,`roles`.`acl` AS `roleacl`,coalesce(`employees`.`employee_id`,0) AS `employee_id`,(case when isnull(`employees`.`emp_name`) then `users`.`userlogin` else `employees`.`emp_name` end) AS `username` from ((`users` left join `employees` on(((`users`.`userlogin` = `employees`.`login`) and (`employees`.`disabled` <> 1)))) left join `roles` on((`users`.`role_id` = `roles`.`role_id`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

