
? ALTER TABLE saltypes ADD isedited  tinyint(1)   DEFAULT 0;



ALTER TABLE roles ADD disabled  tinyint(1) DEFAULT 0;

DROP VIEW roles_view  ;

CREATE VIEW roles_view
AS
SELECT
  `roles`.`role_id` AS `role_id`,
  `roles`.`rolename` AS `rolename`,
  `roles`.`disabled` AS `disabled`,
  `roles`.`acl` AS `acl`,
  (SELECT
      COALESCE(COUNT(0), 0)
    FROM `users`
    WHERE (`users`.`role_id` = `roles`.`role_id`)) AS `cnt`
FROM `roles`;


ALTER TABLE entrylist ADD cost decimal(11, 2) DEFAULT NULL ;

DROP VIEW entrylist_view;
    
CREATE VIEW entrylist_view 
AS
SELECT
  entrylist.entry_id AS entry_id,
  entrylist.document_id AS document_id,
  entrylist.quantity AS quantity,
  documents.customer_id AS customer_id,
  entrylist.stock_id AS stock_id,
  entrylist.service_id AS service_id,
  entrylist.tag AS tag,
  entrylist.createdon AS createdon,
  store_stock.item_id AS item_id,
  store_stock.partion AS partion,
  case when entrylist.createdon  is NULL  then documents.document_date else entrylist.createdon  end      AS document_date,
  entrylist.cost AS cost
  entrylist.outprice AS outprice
FROM ((entrylist
  LEFT JOIN store_stock
    ON ((entrylist.stock_id = store_stock.stock_id)))
  JOIN documents
    ON ((entrylist.document_id = documents.document_id)));    
    

CREATE TABLE acc_entry (
  id bigint NOT NULL AUTO_INCREMENT,
  createdon DATE DEFAULT NULL,
  accdt varchar(4)  NULL,
  accct varchar(4)  NULL,
  amount decimal(11, 2) NOT NULL,
  document_id int NOT NULL,
  notes varchar(255)  NULL,
  tag int    NULL   ,
 
  PRIMARY KEY (id) ,
  KEY document_id (document_id),
  KEY accdt (accdt),
  KEY accct (accct),
  CONSTRAINT accentrylist_ibfk_1 FOREIGN KEY (document_id) REFERENCES documents (document_id) 
  
) ENGINE = INNODB  DEFAULT CHARSET = utf8  ; 
 
  

CREATE VIEW acc_entry_view
AS
SELECT
  e.id AS id,
 
  e.accdt AS accdt,
  e.accct AS accct,
  e.amount AS amount,
   case when e.createdon  is NULL  then d.document_date else e.createdon  end      AS createdon,
   case when e.notes  is NULL  then d.notes else e.notes  end      AS notes,
  
  e.document_id AS document_id,
  d.branch_id AS branch_id,
  e.tag AS tag,
   
  d.document_number AS document_number
FROM  acc_entry e
  JOIN documents d
    ON  d.document_id = e.document_id ;

    
    
    
INSERT INTO metadata (  meta_type, description,   meta_name, menugroup,   disabled) VALUES( 4, 'План рахункiв', 'AccountList', 'Бухоблiк',   0 );
INSERT INTO metadata (  meta_type, description,   meta_name, menugroup,   disabled) VALUES( 3, 'Журнал проводок', 'AccountEntryList', 'Бухоблiк',   0 );
INSERT INTO metadata (  meta_type, description,   meta_name, menugroup,   disabled) VALUES( 2, 'Рух по рахунку', 'AccountActivity', 'Бухоблiк',     0);
INSERT INTO metadata (  meta_type, description,   meta_name, menugroup,   disabled) VALUES( 1, 'Ручна проводка', 'ManualEntry', 'Бухоблiк',   0);
INSERT INTO metadata (  meta_type, description,   meta_name, menugroup,   disabled) VALUES( 2, 'Оборотно-сальдова вiдомiсть', 'ObSaldo', 'Бухоблiк',     0);
INSERT INTO metadata (  meta_type, description,   meta_name, menugroup,   disabled) VALUES( 2, 'Шахматна вiдомiсть', 'Shahmatka', 'Бухоблiк',   0 );
INSERT INTO metadata (  meta_type, description,   meta_name, menugroup,   disabled) VALUES( 2, 'Фiн. звiт малого  пiдприємства', 'FinReportSmall', 'Бухоблiк',  0, 0);
INSERT INTO metadata (  meta_type, description,   meta_name, menugroup,   disabled) VALUES( 1, 'Закриття перiоду', 'FinResult', 'Бухоблiк',   0);

30 31
36 63

20 по  складу
26 28 24 22
23  по  списанию и оприходованию

10 15 13

оставить с 64

66
