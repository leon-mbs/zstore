delete from  metadata  where  meta_name='CustActivity'
delete from  metadata  where  meta_name='EmpAccRep'


CREATE TABLE  custacc (
  ca_id int(11) NOT NULL AUTO_INCREMENT,
  customer_id int(11) NOT NULL,
  document_id int(11) DEFAULT NULL,
  optype int(11) NOT NULL,
  notes varchar(255) DEFAULT NULL,
  amount decimal(10, 2) NOT NULL,
  createdon date DEFAULT NULL,
  KEY `document_id` (`document_id`) ,
  KEY `customer_id` (`customer_id`),
  PRIMARY KEY (ca_id)
) ENGINE=InnoDB;


CREATE VIEW custacc_view
AS
SELECT
  `a`.`ca_id` AS `ca_id`,
  `a`.`customer_id` AS `customer_id`,
  `a`.`document_id` AS `document_id`,
  `a`.`optype` AS `optype`,
  `a`.`notes` AS `notes`,
  `a`.`amount` AS `amount`,
  `a`.`createdon` AS `createdon`,
  `c`.`customer_name` AS `customer_name`,
  `d`.`document_number` AS `document_number`
FROM ((`custacc` `a`
  JOIN `documents` `d`
    ON ((`d`.`document_id` = `a`.`document_id`)))
  JOIN `customers` `c`
    ON ((`c`.`customer_id` = `a`.`customer_id`)));