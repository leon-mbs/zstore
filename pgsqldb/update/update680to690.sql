

ALTER TABLE "equipments" ADD branch_id INTEGER NULL ;
ALTER TABLE "ppo_zformstat" ADD amount4 decimal(10, 2) NOT NULL   ;


 --  createdon
DROP VIEW customers_view  ;

CREATE
AS
SELECT
  `customers`.`customer_id` AS `customer_id`,
  `customers`.`customer_name` AS `customer_name`,
  `customers`.`detail` AS `detail`,
  `customers`.`email` AS `email`,
  `customers`.`phone` AS `phone`,
  `customers`.`status` AS `status`,
  `customers`.`city` AS `city`,
  `customers`.`leadstatus` AS `leadstatus`,
  `customers`.`leadsource` AS `leadsource`,
  `customers`.`createdon` AS `createdon`,
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

CREATE TABLE promocodes (
  id INTEGER NOT NULL GENERATED BY DEFAULT AS IDENTITY,
  code CHARACTER VARYING(16) NOT NULL,
  type smallint NOT NULL,
  state smallint NOT NULL,
  enddate DATE DEFAULT NULL,
  details text DEFAULT NULL,
  CONSTRAINT PK_promocodes PRIMARY KEY (id)
)   ;   

CREATE UNIQUE INDEX IF NOT EXISTS IX_promocodes_code
ON promocodes (
  code
);


update  "metadata" set  description ='Програми лояльності' where  meta_name='Discounts';

DELETE  FROM "options" WHERE  optname='version' ;
INSERT INTO "options" (optname, optvalue) values('version','6.9.0');  