
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
DROP TABLE IF EXISTS `customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `customers` (
  `customer_id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_name` varchar(255) DEFAULT NULL,
  `detail` text NOT NULL,
  `email` varchar(64) DEFAULT NULL,
  `phone` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`customer_id`)
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=utf8;
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
 1 AS `phone`*/;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `docrel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `docrel` (
  `doc1` int(11) DEFAULT NULL,
  `doc2` int(11) DEFAULT NULL,
  KEY `doc1` (`doc1`),
  KEY `doc2` (`doc2`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Связь между  документами';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `documents` (
  `document_id` int(11) NOT NULL AUTO_INCREMENT,
  `document_number` varchar(45) NOT NULL,
  `document_date` date NOT NULL,
  `user_id` int(11) NOT NULL,
  `content` text,
  `amount` int(11) DEFAULT NULL,
  `meta_id` int(11) NOT NULL,
  `state` tinyint(4) NOT NULL,
  `datatag` int(11) DEFAULT NULL,
  `notes` varchar(255) NOT NULL,
  `customer_id` int(11) DEFAULT '0',
  PRIMARY KEY (`document_id`),
  KEY `document_date` (`document_date`),
  KEY `customer_id` (`customer_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=59 DEFAULT CHARSET=utf8;
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
 1 AS `datatag`,
 1 AS `meta_name`,
 1 AS `meta_desc`*/;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `employees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `employees` (
  `employee_id` int(11) NOT NULL AUTO_INCREMENT,
  `login` varchar(64) DEFAULT NULL,
  `detail` text,
  `emp_name` varchar(64) NOT NULL,
  PRIMARY KEY (`employee_id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `entrylist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `entrylist` (
  `entry_id` int(11) NOT NULL AUTO_INCREMENT,
  `document_id` int(11) NOT NULL,
  `amount` int(11) NOT NULL DEFAULT '0',
  `quantity` int(11) DEFAULT '0',
  `customer_id` int(11) DEFAULT '0',
  `employee_id` int(11) DEFAULT '0',
  `extcode` int(11) DEFAULT '0',
  `stock_id` int(11) DEFAULT '0',
  `service_id` int(11) DEFAULT '0',
  PRIMARY KEY (`entry_id`),
  KEY `document_id` (`document_id`),
  KEY `stock_id` (`stock_id`)
) ENGINE=MyISAM AUTO_INCREMENT=61 DEFAULT CHARSET=utf8;
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
/*!50003 CREATE*/ /*!50017  */ /*!50003 TRIGGER `entrylist_after_ins_tr` AFTER INSERT ON `entrylist`
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
/*!50003 CREATE*/ /*!50017  */ /*!50003 TRIGGER `entrylist_after_del_tr` AFTER DELETE ON `entrylist`
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
 1 AS `amount`,
 1 AS `quantity`,
 1 AS `customer_id`,
 1 AS `employee_id`,
 1 AS `extcode`,
 1 AS `stock_id`,
 1 AS `service_id`,
 1 AS `item_id`,
 1 AS `document_date`*/;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `equipments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `equipments` (
  `eq_id` int(11) NOT NULL AUTO_INCREMENT,
  `eq_name` varchar(255) DEFAULT NULL,
  `detail` text,
  `description` text,
  PRIMARY KEY (`eq_id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eventlist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `eventlist` (
  `user_id` int(11) NOT NULL,
  `eventdate` datetime NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `notify_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  PRIMARY KEY (`event_id`),
  KEY `user_id` (`user_id`),
  KEY `customer_id` (`customer_id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;
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
 1 AS `notify_id`,
 1 AS `event_id`,
 1 AS `customer_id`,
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
  PRIMARY KEY (`file_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `filesdata`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `filesdata` (
  `file_id` int(11) DEFAULT NULL,
  `filedata` longblob,
  UNIQUE KEY `file_id` (`file_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
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
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `item_cat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `item_cat` (
  `cat_id` int(11) NOT NULL AUTO_INCREMENT,
  `cat_name` varchar(255) NOT NULL,
  PRIMARY KEY (`cat_id`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `items` (
  `item_id` int(11) NOT NULL AUTO_INCREMENT,
  `itemname` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `detail` text NOT NULL,
  `item_code` varchar(64) DEFAULT NULL,
  `bar_code` varchar(64) DEFAULT NULL,
  `cat_id` int(11) NOT NULL,
  `msr` varchar(64) DEFAULT NULL,
  `disabled` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`item_id`),
  KEY `item_code` (`item_code`),
  KEY `itemname` (`itemname`),
  KEY `cat_id` (`cat_id`)
) ENGINE=MyISAM AUTO_INCREMENT=13 DEFAULT CHARSET=utf8;
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
 1 AS `msr`,
 1 AS `cat_id`,
 1 AS `cat_name`,
 1 AS `disabled`,
 1 AS `qty`*/;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `messages` (
  `message_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `message` text,
  `item_id` int(11) NOT NULL,
  `item_type` int(11) DEFAULT NULL,
  PRIMARY KEY (`message_id`)
) ENGINE=MyISAM AUTO_INCREMENT=10 DEFAULT CHARSET=utf8;
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
  `menugroup` varchar(255) DEFAULT NULL COMMENT '???????????  ???   ???????',
  `notes` text NOT NULL,
  `disabled` tinyint(4) NOT NULL,
  `smartmenu` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`meta_id`)
) ENGINE=MyISAM AUTO_INCREMENT=41 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notifies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifies` (
  `notify_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `dateshow` datetime NOT NULL,
  `checked` tinyint(1) NOT NULL DEFAULT '0',
  `message` text NOT NULL,
  PRIMARY KEY (`notify_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `options`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `options` (
  `optname` varchar(64) NOT NULL,
  `optvalue` text NOT NULL,
  UNIQUE KEY `optname` (`optname`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `parealist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `parealist` (
  `pa_id` int(11) NOT NULL AUTO_INCREMENT,
  `pa_name` varchar(255) NOT NULL,
  PRIMARY KEY (`pa_id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `services` (
  `service_id` int(11) NOT NULL AUTO_INCREMENT,
  `service_name` varchar(255) NOT NULL,
  `detail` text,
  PRIMARY KEY (`service_id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `shop_attributes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `shop_attributes` (
  `attribute_id` int(11) NOT NULL AUTO_INCREMENT,
  `attributename` varchar(64) NOT NULL,
  `group_id` int(11) NOT NULL,
  `attributetype` tinyint(4) NOT NULL,
  `valueslist` varchar(255) DEFAULT NULL,
  `showinlist` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`attribute_id`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;
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
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `shop_attributes_view`;
/*!50001 DROP VIEW IF EXISTS `shop_attributes_view`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `shop_attributes_view` AS SELECT 
 1 AS `attribute_id`,
 1 AS `attributename`,
 1 AS `group_id`,
 1 AS `attributetype`,
 1 AS `valueslist`,
 1 AS `showinlist`,
 1 AS `ordern`*/;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `shop_attributevalues`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `shop_attributevalues` (
  `attributevalue_id` int(11) NOT NULL AUTO_INCREMENT,
  `attribute_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `attributevalue` varchar(255) NOT NULL,
  PRIMARY KEY (`attributevalue_id`),
  KEY `attribute_id` (`attribute_id`)
) ENGINE=MyISAM AUTO_INCREMENT=23 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `shop_manufacturers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `shop_manufacturers` (
  `manufacturer_id` int(11) NOT NULL AUTO_INCREMENT,
  `manufacturername` varchar(255) NOT NULL,
  `url` varchar(255) NOT NULL,
  PRIMARY KEY (`manufacturer_id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `shop_prod_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `shop_prod_comments` (
  `comment_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `author` varchar(64) NOT NULL,
  `comment` text NOT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `rating` tinyint(4) NOT NULL DEFAULT '0',
  `moderated` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`comment_id`),
  KEY `product_id` (`product_id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `shop_productgroups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `shop_productgroups` (
  `group_id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) NOT NULL DEFAULT '0',
  `groupname` varchar(128) NOT NULL,
  `mpath` varchar(1024) DEFAULT NULL,
  `image_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`group_id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `shop_productgroups_view`;
/*!50001 DROP VIEW IF EXISTS `shop_productgroups_view`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `shop_productgroups_view` AS SELECT 
 1 AS `group_id`,
 1 AS `parent_id`,
 1 AS `groupname`,
 1 AS `mpath`,
 1 AS `image_id`,
 1 AS `gcnt`,
 1 AS `pcnt`*/;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `shop_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `shop_products` (
  `product_id` int(11) NOT NULL AUTO_INCREMENT,
  `group_id` int(11) NOT NULL DEFAULT '0',
  `productname` varchar(255) NOT NULL,
  `manufacturer_id` int(11) NOT NULL DEFAULT '0',
  `price` int(11) NOT NULL DEFAULT '0',
  `sold` int(1) NOT NULL DEFAULT '0',
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  `sef` varchar(64) DEFAULT NULL,
  `item_id` int(11) NOT NULL,
  `created` date NOT NULL,
  `detail` longtext,
  `rating` smallint(6) DEFAULT '0',
  `comments` int(11) DEFAULT '0',
  PRIMARY KEY (`product_id`),
  KEY `group_id` (`group_id`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `shop_products_view`;
/*!50001 DROP VIEW IF EXISTS `shop_products_view`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `shop_products_view` AS SELECT 
 1 AS `product_id`,
 1 AS `group_id`,
 1 AS `productname`,
 1 AS `manufacturer_id`,
 1 AS `price`,
 1 AS `sold`,
 1 AS `deleted`,
 1 AS `sef`,
 1 AS `item_id`,
 1 AS `created`,
 1 AS `detail`,
 1 AS `rating`,
 1 AS `novelty`,
 1 AS `comments`,
 1 AS `groupname`,
 1 AS `manufacturername`*/;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `store_stock`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `store_stock` (
  `stock_id` int(11) NOT NULL AUTO_INCREMENT,
  `item_id` int(11) NOT NULL,
  `partion` int(11) DEFAULT NULL,
  `store_id` int(11) NOT NULL,
  `qty` int(11) DEFAULT NULL,
  PRIMARY KEY (`stock_id`),
  KEY `item_id` (`item_id`)
) ENGINE=MyISAM AUTO_INCREMENT=13 DEFAULT CHARSET=utf8;
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
 1 AS `bar_code`,
 1 AS `cat_name`,
 1 AS `storename`,
 1 AS `qty`*/;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `stores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stores` (
  `store_id` int(11) NOT NULL AUTO_INCREMENT,
  `storename` varchar(64) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`store_id`)
) ENGINE=MyISAM AUTO_INCREMENT=23 DEFAULT CHARSET=utf8 COMMENT='????? ????????';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `userlogin` varchar(32) NOT NULL,
  `userpass` varchar(255) NOT NULL,
  `createdon` date NOT NULL,
  `active` int(1) NOT NULL DEFAULT '0',
  `email` varchar(255) DEFAULT NULL,
  `acl` text NOT NULL,
  `smartmenu` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `userlogin` (`userlogin`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8;
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
 1 AS `active`,
 1 AS `email`,
 1 AS `acl`,
 1 AS `username`*/;
SET character_set_client = @saved_cs_client;
/*!50001 DROP VIEW IF EXISTS `customers_view`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = latin1 */;
/*!50001 SET character_set_results     = latin1 */;
/*!50001 SET collation_connection      = latin1_swedish_ci */;
/*!50001 CREATE  */
/*!50013  */
/*!50001 VIEW `customers_view` AS select `customers`.`customer_id` AS `customer_id`,`customers`.`customer_name` AS `customer_name`,`customers`.`detail` AS `detail`,`customers`.`email` AS `email`,`customers`.`phone` AS `phone` from `customers` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `documents_view`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = latin1 */;
/*!50001 SET character_set_results     = latin1 */;
/*!50001 SET collation_connection      = latin1_swedish_ci */;
/*!50001 CREATE  */
/*!50013  */
/*!50001 VIEW `documents_view` AS select `d`.`document_id` AS `document_id`,`d`.`document_number` AS `document_number`,`d`.`document_date` AS `document_date`,`d`.`user_id` AS `user_id`,`d`.`content` AS `content`,`d`.`amount` AS `amount`,`d`.`meta_id` AS `meta_id`,`u`.`username` AS `username`,`c`.`customer_id` AS `customer_id`,`c`.`customer_name` AS `customer_name`,`d`.`state` AS `state`,`d`.`notes` AS `notes`,`d`.`datatag` AS `datatag`,`metadata`.`meta_name` AS `meta_name`,`metadata`.`description` AS `meta_desc` from (((`documents` `d` join `users_view` `u` on((`d`.`user_id` = `u`.`user_id`))) left join `customers` `c` on((`d`.`customer_id` = `c`.`customer_id`))) join `metadata` on((`metadata`.`meta_id` = `d`.`meta_id`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `entrylist_view`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = latin1 */;
/*!50001 SET character_set_results     = latin1 */;
/*!50001 SET collation_connection      = latin1_swedish_ci */;
/*!50001 CREATE  */
/*!50013  */
/*!50001 VIEW `entrylist_view` AS select `entrylist`.`entry_id` AS `entry_id`,`entrylist`.`document_id` AS `document_id`,`entrylist`.`amount` AS `amount`,`entrylist`.`quantity` AS `quantity`,`entrylist`.`customer_id` AS `customer_id`,`entrylist`.`employee_id` AS `employee_id`,`entrylist`.`extcode` AS `extcode`,`entrylist`.`stock_id` AS `stock_id`,`entrylist`.`service_id` AS `service_id`,`store_stock`.`item_id` AS `item_id`,`documents`.`document_date` AS `document_date` from ((`entrylist` left join `store_stock` on((`entrylist`.`stock_id` = `store_stock`.`stock_id`))) join `documents` on((`entrylist`.`document_id` = `documents`.`document_id`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `eventlist_view`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = latin1 */;
/*!50001 SET character_set_results     = latin1 */;
/*!50001 SET collation_connection      = latin1_swedish_ci */;
/*!50001 CREATE  */
/*!50013  */
/*!50001 VIEW `eventlist_view` AS select `e`.`user_id` AS `user_id`,`e`.`eventdate` AS `eventdate`,`e`.`title` AS `title`,`e`.`description` AS `description`,`e`.`notify_id` AS `notify_id`,`e`.`event_id` AS `event_id`,`e`.`customer_id` AS `customer_id`,`c`.`customer_name` AS `customer_name` from (`eventlist` `e` left join `customers` `c` on((`e`.`customer_id` = `c`.`customer_id`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `items_view`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = latin1 */;
/*!50001 SET character_set_results     = latin1 */;
/*!50001 SET collation_connection      = latin1_swedish_ci */;
/*!50001 CREATE  */
/*!50013  */
/*!50001 VIEW `items_view` AS select `items`.`item_id` AS `item_id`,`items`.`itemname` AS `itemname`,`items`.`description` AS `description`,`items`.`detail` AS `detail`,`items`.`item_code` AS `item_code`,`items`.`bar_code` AS `bar_code`,`items`.`msr` AS `msr`,`items`.`cat_id` AS `cat_id`,`item_cat`.`cat_name` AS `cat_name`,`items`.`disabled` AS `disabled`,(select sum(`store_stock`.`qty`) from `store_stock` where (`store_stock`.`item_id` = `items`.`item_id`)) AS `qty` from (`items` left join `item_cat` on((`items`.`cat_id` = `item_cat`.`cat_id`))) */;
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
/*!50013  */
/*!50001 VIEW `messages_view` AS select `messages`.`message_id` AS `message_id`,`messages`.`user_id` AS `user_id`,`messages`.`created` AS `created`,`messages`.`message` AS `message`,`messages`.`item_id` AS `item_id`,`messages`.`item_type` AS `item_type`,`users_view`.`username` AS `username` from (`messages` join `users_view` on((`messages`.`user_id` = `users_view`.`user_id`))) */;
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
/*!50013  */
/*!50001 VIEW `shop_attributes_view` AS select `shop_attributes`.`attribute_id` AS `attribute_id`,`shop_attributes`.`attributename` AS `attributename`,`shop_attributes`.`group_id` AS `group_id`,`shop_attributes`.`attributetype` AS `attributetype`,`shop_attributes`.`valueslist` AS `valueslist`,`shop_attributes`.`showinlist` AS `showinlist`,`shop_attributes_order`.`ordern` AS `ordern` from (`shop_attributes` join `shop_attributes_order` on(((`shop_attributes`.`attribute_id` = `shop_attributes_order`.`attr_id`) and (`shop_attributes`.`group_id` = `shop_attributes_order`.`pg_id`)))) order by `shop_attributes_order`.`ordern` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `shop_productgroups_view`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = latin1 */;
/*!50001 SET character_set_results     = latin1 */;
/*!50001 SET collation_connection      = latin1_swedish_ci */;
/*!50001 CREATE  */
/*!50013  */
/*!50001 VIEW `shop_productgroups_view` AS select `g`.`group_id` AS `group_id`,`g`.`parent_id` AS `parent_id`,`g`.`groupname` AS `groupname`,`g`.`mpath` AS `mpath`,`g`.`image_id` AS `image_id`,(select count(`sg`.`group_id`) AS `cnt` from `shop_productgroups` `sg` where (`g`.`group_id` = `sg`.`parent_id`)) AS `gcnt`,(select count(`p`.`product_id`) AS `cnt` from `shop_products` `p` where (`g`.`group_id` = `p`.`group_id`)) AS `pcnt` from `shop_productgroups` `g` */;
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
/*!50013  */
/*!50001 VIEW `shop_products_view` AS select `p`.`product_id` AS `product_id`,`p`.`group_id` AS `group_id`,`p`.`productname` AS `productname`,`p`.`manufacturer_id` AS `manufacturer_id`,`p`.`price` AS `price`,`p`.`sold` AS `sold`,`p`.`deleted` AS `deleted`,`p`.`sef` AS `sef`,`p`.`item_id` AS `item_id`,`p`.`created` AS `created`,`p`.`detail` AS `detail`,`p`.`rating` AS `rating`,(case when (`p`.`created` > (now() - interval 1 month)) then 1 else 0 end) AS `novelty`,`p`.`comments` AS `comments`,`g`.`groupname` AS `groupname`,`m`.`manufacturername` AS `manufacturername` from ((`shop_products` `p` join `shop_productgroups` `g` on((`p`.`group_id` = `g`.`group_id`))) left join `shop_manufacturers` `m` on((`p`.`manufacturer_id` = `m`.`manufacturer_id`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `store_stock_view`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = latin1 */;
/*!50001 SET character_set_results     = latin1 */;
/*!50001 SET collation_connection      = latin1_swedish_ci */;
/*!50001 CREATE  */
/*!50013  */
/*!50001 VIEW `store_stock_view` AS select `st`.`stock_id` AS `stock_id`,`st`.`item_id` AS `item_id`,`st`.`partion` AS `partion`,`st`.`store_id` AS `store_id`,`i`.`itemname` AS `itemname`,`i`.`item_code` AS `item_code`,`i`.`cat_id` AS `cat_id`,`i`.`msr` AS `msr`,`i`.`bar_code` AS `bar_code`,`i`.`cat_name` AS `cat_name`,`stores`.`storename` AS `storename`,`st`.`qty` AS `qty` from ((`store_stock` `st` join `items_view` `i` on((`i`.`item_id` = `st`.`item_id`))) join `stores` on((`stores`.`store_id` = `st`.`store_id`))) where (`st`.`qty` <> 0) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `users_view`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = latin1 */;
/*!50001 SET character_set_results     = latin1 */;
/*!50001 SET collation_connection      = latin1_swedish_ci */;
/*!50001 CREATE  */
/*!50013  */
/*!50001 VIEW `users_view` AS select `users`.`user_id` AS `user_id`,`users`.`userlogin` AS `userlogin`,`users`.`userpass` AS `userpass`,`users`.`createdon` AS `createdon`,`users`.`active` AS `active`,`users`.`email` AS `email`,`users`.`acl` AS `acl`,(case when isnull(`employees`.`emp_name`) then `users`.`userlogin` else `employees`.`emp_name` end) AS `username` from (`users` left join `employees` on((`users`.`userlogin` = `employees`.`login`))) */;
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

