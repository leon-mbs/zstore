ALTER TABLE ppo_zformstat  ADD fiscnumber CHARACTER VARYING(255) NULL ;
ALTER TABLE item_set  ADD service_id INTEGER DEFAULT NULL ;
ALTER TABLE item_set  ADD cost DECIMAL(10, 2) DEFAULT NULL ;


DROP VIEW item_set_view;

CREATE
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