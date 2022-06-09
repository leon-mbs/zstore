ALTER TABLE ppo_zformstat  ADD fiscnumber CHARACTER VARYING(255) NULL ;
ALTER TABLE item_set  ADD service_id INTEGER DEFAULT NULL ;
ALTER TABLE item_set  ADD cost DECIMAL(10, 2) DEFAULT NULL ;


 

ALTER
VIEW item_set_view
AS
SELECT
  item_set.set_id AS set_id,
  item_set.item_id AS item_id,
  item_set.pitem_id AS pitem_id,
  item_set.qty AS qty,
  item_set.service_id AS service_id,
  item_set.cost AS cost,
  items.itemname AS itemname,
  items.item_code AS item_code,
  services.service_name AS service_name
FROM ((item_set
  LEFT JOIN items
    ON (((item_set.item_id = items.item_id)
    AND (items.disabled <> 1))))
  LEFT JOIN services
    ON (((item_set.service_id = services.service_id)
    AND (services.disabled <> 1)))); 
    
    
ALTER TABLE documents ADD lastupdate TIMESTAMP NULL;    


 

ALTER VIEW documents_view
AS
SELECT
  d.document_id AS document_id,
  d.document_number AS document_number,
  d.document_date AS document_date,
  d.user_id AS user_id,
  d.content AS content,
  d.amount AS amount,
  d.meta_id AS meta_id,
  u.username AS username,
  c.customer_id AS customer_id,
  c.customer_name AS customer_name,
  d.state AS state,
  d.notes AS notes,
  d.payamount AS payamount,
  d.payed AS payed,
  d.parent_id AS parent_id,
  d.branch_id AS branch_id,
  b.branch_name AS branch_name,
  d.firm_id AS firm_id,
  d.priority AS priority,
  d.lastupdate AS lastupdate,
  f.firm_name AS firm_name,
  metadata.meta_name AS meta_name,
  metadata.description AS meta_desc
FROM (((((documents d
  LEFT JOIN users_view u
    ON ((d.user_id = u.user_id)))
  LEFT JOIN customers c
    ON ((d.customer_id = c.customer_id)))
  JOIN metadata
    ON ((metadata.meta_id = d.meta_id)))
  LEFT JOIN branches b
    ON ((d.branch_id = b.branch_id)))
  LEFT JOIN firms f
    ON ((d.firm_id = f.firm_id)));