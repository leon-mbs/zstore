 

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


ALTER TABLE entrylist ADD cost decimal(11, 2) DEFAULT 0 ;

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
  entrylist.cost AS cost,
  entrylist.outprice AS outprice
FROM entrylist
  LEFT JOIN store_stock 
    ON entrylist.stock_id = store_stock.stock_id
  JOIN documents
    ON entrylist.document_id = documents.document_id;    
    


INSERT INTO metadata (  meta_type, description,   meta_name, menugroup,   disabled) VALUES( 1, 'Авансовий звiт', 'AdvanceRep', 'Каса та платежі',   0);

delete from options where  optname='version' ;
insert into options (optname,optvalue) values('version','6.15.0'); 

   