ALTER TABLE `documents` ADD `priority` SMALLINT NULL DEFAULT 100 ;


DROP VIEW documents_view;

CREATE VIEW documents_view
AS
SELECT
  `d`.`document_id` AS `document_id`,
  `d`.`document_number` AS `document_number`,
  `d`.`document_date` AS `document_date`,
  `d`.`user_id` AS `user_id`,
  `d`.`content` AS `content`,
  `d`.`amount` AS `amount`,
  `d`.`meta_id` AS `meta_id`,
  `u`.`username` AS `username`,
  `c`.`customer_id` AS `customer_id`,
  `c`.`customer_name` AS `customer_name`,
  `d`.`state` AS `state`,
  `d`.`notes` AS `notes`,
  `d`.`payamount` AS `payamount`,
  `d`.`payed` AS `payed`,
  `d`.`parent_id` AS `parent_id`,
  `d`.`branch_id` AS `branch_id`,
  `b`.`branch_name` AS `branch_name`,
  `d`.`firm_id` AS `firm_id`,
  `d`.`priority` AS `priority`,
  `f`.`firm_name` AS `firm_name`,
  `metadata`.`meta_name` AS `meta_name`,
  `metadata`.`description` AS `meta_desc`
FROM (((((`documents` `d`
  LEFT JOIN `users_view` `u`
    ON ((`d`.`user_id` = `u`.`user_id`)))
  LEFT JOIN `customers` `c`
    ON ((`d`.`customer_id` = `c`.`customer_id`)))
  JOIN `metadata`
    ON ((`metadata`.`meta_id` = `d`.`meta_id`)))
  LEFT JOIN `branches` `b`
    ON ((`d`.`branch_id` = `b`.`branch_id`)))
  LEFT JOIN `firms` `f`
    ON ((`d`.`firm_id` = `f`.`firm_id`)));
    
    
ALTER TABLE `customers` ADD `passw` VARCHAR(255) NULL  ;    



DROP VIEW customers_view  ;

CREATE
VIEW customers_view
AS
SELECT
  `customers`.`customer_id` AS `customer_id`,
  `customers`.`customer_name` AS `customer_name`,
  `customers`.`detail` AS `detail`,
  `customers`.`email` AS `email`,
  `customers`.`phone` AS `phone`,
  `customers`.`status` AS `status`,
  `customers`.`city` AS `city`,
  `customers`.`leadsource` AS `leadsource`,
  `customers`.`leadstatus` AS `leadstatus`,
  `customers`.`country` AS `country`,
  `customers`.`passw` AS `passw`,
  (SELECT
      COUNT(0)
    FROM `messages` `m`
    WHERE ((`m`.`item_id` = `customers`.`customer_id`)
    AND (`m`.`item_type` = 2))) AS `mcnt`,
  (SELECT
      COUNT(0)
    FROM `files` `f`
    WHERE ((`f`.`item_id` = `customers`.`customer_id`)
    AND (`f`.`item_type` = 2))) AS `fcnt`,
  (SELECT
      COUNT(0)
    FROM `eventlist` `e`
    WHERE ((`e`.`customer_id` = `customers`.`customer_id`)
    AND (`e`.`eventdate` >= NOW()))) AS `ecnt`
FROM `customers`;



UPDATE documents SET  priority = 1  WHERE  state =9;
UPDATE documents SET  priority = 10  WHERE  state =5;