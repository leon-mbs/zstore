 

CREATE TABLE promocodes (
  id int(11) NOT NULL AUTO_INCREMENT,
  code varchar(16) NOT NULL,
  type tinyint(4) NOT NULL,
  disabled tinyint(1) NOT NULL default 0,
  
  details text DEFAULT NULL,
  PRIMARY KEY (id)
) ENGINE = INNODB DEFAULT CHARSET = utf8
;

ALTER TABLE promocodes
ADD UNIQUE INDEX code (code)   ; 
 
ALTER TABLE paylist ADD customer_id INT NULL ;

DROP VIEW IF EXISTS paylist_view;

CREATE VIEW paylist_view
AS
SELECT 
  pl.pl_id AS pl_id,
  pl.document_id AS document_id,
  pl.amount AS amount,
  pl.mf_id AS mf_id,
  pl.notes AS notes,
  pl.user_id AS user_id,
  pl.paydate AS paydate,
  pl.paytype AS paytype,
  pl.bonus AS bonus,
  d.document_number AS document_number,
  u.username AS username,
  m.mf_name AS mf_name,
  (CASE WHEN (c.customer_id IS NOT NULL) THEN c.customer_id ELSE d.customer_id END) AS customer_id,
  (CASE WHEN (c.customer_name IS NOT NULL) THEN c.customer_name ELSE d.customer_name END) AS customer_name
FROM ((((paylist pl
  JOIN documents_view d
    ON ((pl.document_id = d.document_id)))
  LEFT JOIN users_view u
    ON ((pl.user_id = u.user_id)))
  LEFT JOIN mfund m
    ON ((pl.mf_id = m.mf_id)))
  LEFT JOIN customers c
    ON ((pl.customer_id = c.customer_id))) ;








DROP VIEW IF EXISTS customers_view  ;

CREATE VIEW customers_view  
AS
SELECT 
  customers.customer_id AS customer_id,
  customers.customer_name AS customer_name,
  customers.detail AS detail,
  customers.email AS email,
  customers.phone AS phone,
  customers.status AS status,
  customers.city AS city,
  customers.leadstatus AS leadstatus,
  customers.leadsource AS leadsource,
  customers.createdon AS createdon,
  customers.country AS country,
  customers.passw AS passw,
  (SELECT
      COUNT(0)
    FROM messages m
    WHERE ((m.item_id = customers.customer_id)
    AND (m.item_type = 2))) AS mcnt,
  (SELECT
      COUNT(0)
    FROM files f
    WHERE ((f.item_id = customers.customer_id)
    AND (f.item_type = 2))) AS fcnt,
  (SELECT
      COUNT(0)
    FROM eventlist e
    WHERE ((e.customer_id = customers.customer_id)
    AND (e.eventdate >= NOW()))) AS ecnt
FROM customers;


ALTER TABLE equipments ADD branch_id INT NULL ;
ALTER TABLE ppo_zformstat ADD amount4 decimal(10, 2)  default 0;



update  metadata set  description ='Програма лояльності' where  meta_name='Discounts';
update  "metadata" set  description ='Отримані послуги' where  meta_name='IncomeService';

delete  from  options where  optname='version' ;
insert  into options (optname,optvalue) values('version','6.9.0'); 