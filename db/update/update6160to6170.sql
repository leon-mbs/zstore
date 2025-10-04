
ALTER TABLE custitems ADD INDEX  (cust_code) ; 
ALTER TABLE custitems ADD INDEX  (customer_id) ; 

CREATE TABLE  substitems (
  id bigint NOT NULL AUTO_INCREMENT,
  itemname varchar(255) NOT NULL ,
  origcode varchar(255) NOT NULL ,
  origbrand varchar(255) DEFAULT NULL ,
  substcode varchar(255) NOT NULL ,
  substbrand varchar(255) DEFAULT NULL ,
  
  KEY (origcode) ,
  PRIMARY KEY (id)
) ENGINE = INNODB DEFAULT CHARSET = utf8 ;  


CREATE VIEW  substitems_view
AS
SELECT
  s.id AS id,
  s.itemname AS itemname,
  s.origcode AS origcode,
  s.origbrand AS origbrand,
  s.substcode AS substcode,
  s.substbrand AS substbrand,
  c.custitem_id,
  i.item_id

FROM substitems s
LEFT JOIN custitems c 
ON c.cust_code = s.substcode  and  coalesce(c.brand,'') = coalesce(s.substbrand,'')
LEFT JOIN items i 
ON i.item_code=s.substcode  and  coalesce(i.manufacturer,'') = coalesce(s.substbrand,'')  ;

    
INSERT INTO metadata (meta_type, description, meta_name,  menugroup,   disabled) VALUES(  3, 'Замiни ТМЦ', 'SubstItems', 'Склад',     0);
UPDATE metadata set menugroup ='Каса та платежі' where meta_name='EndDay';
UPDATE metadata set menugroup ='Закупівлі' where meta_name='CustItems';
  

delete from options where  optname='version' ;
insert into options (optname,optvalue) values('version','6.17.0'); 
