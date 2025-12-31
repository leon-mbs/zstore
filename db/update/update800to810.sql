SET NAMES 'utf8'; 


CREATE TABLE  excisestamps (
  id int NOT NULL AUTO_INCREMENT,
  stamp varchar(255) NOT NULL ,
  item_id int NOT NULL,
  document_id bigint NOT NULL,
  anount decimal(11, 2) NOT NULL DEFAULT 0.00,   
  KEY (stamp) ,
  PRIMARY KEY (id)
) ENGINE = INNODB DEFAULT CHARSET = utf8 ;  


CREATE VIEW excisestamps_view
AS
SELECT
  s.id AS id,
  s.stamp AS stamp,
  s.item_id AS item_id,
  s.anount AS anount,
  s.document_id AS document_id,
  i.itemname AS itemname,
  i.item_code AS item_code,
  d.document_number AS document_number,
  d.document_date AS document_date
FROM ((excisestamps s
  JOIN documents d
    ON ((d.document_id = s.document_id)))
  JOIN items i
    ON ((i.item_id = s.item_id)));

 
INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 2, 'Обмеження системи', 'Toc', 'Аналітика', 0);
INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 2, 'Звіт по акцизних марках', 'ExciseList', 'Продажі', 1);


UPDATE metadata set  disabled=1 where meta_name='PredSell';
 

delete from options where  optname='version' ;
insert into options (optname,optvalue) values('version','8.1.0'); 
