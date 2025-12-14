SET NAMES 'utf8';

ALTER TABLE custitems ADD INDEX  (customer_id) ; 

CREATE TABLE  substitems (
  id bigint NOT NULL AUTO_INCREMENT,
  itemname varchar(255) NOT NULL ,
  origcode varchar(255) NOT NULL ,
  origbrand varchar(255) DEFAULT NULL ,
  substcode varchar(255) NOT NULL ,
  substbrand varchar(255) DEFAULT NULL ,
  customer_id int DEFAULT NULL,
   
  KEY (origcode) ,
  PRIMARY KEY (id)
) ENGINE = INNODB DEFAULT CHARSET = utf8 ;  


   
INSERT INTO metadata (meta_type, description, meta_name,  menugroup,   disabled) VALUES(  3, 'Замiни ТМЦ', 'SubstItems', 'Склад',     0);
UPDATE metadata set menugroup ='Каса та платежі' where meta_name='EndDay';
UPDATE metadata set menugroup ='Закупівлі' where meta_name='CustItems';
  

delete from options where  optname='version' ;
insert into options (optname,optvalue) values('version','6.17.0'); 
