SET NAMES 'utf8';

 
ALTER TABLE note_topics ADD ispublic   tinyint(1) DEFAULT 0;
ALTER TABLE note_topicnode ADD islink  tinyint(1) DEFAULT 0;
 

DROP VIEW IF EXISTS item_cat_view  ;

CREATE VIEW item_cat_view
AS
SELECT
  ic.cat_id AS cat_id,
  ic.cat_name AS cat_name,
  ic.detail AS detail,
  ic.parent_id AS parent_id,
  COALESCE((SELECT
      COUNT(*)
    FROM items i
    WHERE i.cat_id = ic.cat_id), 0) AS itemscnt  ,
    COALESCE((SELECT
      COUNT(*)
    FROM item_cat ic2
    WHERE ic2.cat_id = ic.parent_id), 0) AS childcnt
FROM item_cat ic   ;
 
delete  from  options where  optname='version' ;
insert  into options (optname,optvalue) values('version','6.12.0'); 

 