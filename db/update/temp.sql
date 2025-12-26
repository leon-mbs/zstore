SET NAMES 'utf8'; 


CREATE TABLE  excisestamps (
  id bigint NOT NULL AUTO_INCREMENT,
  stamp varchar(255) NOT NULL ,
  item_id int NOT NULL,
  document_id bigint NOT NULL,
   
  KEY (stamp) ,
  PRIMARY KEY (id)
) ENGINE = INNODB DEFAULT CHARSET = utf8 ;  



 
INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 2, 'Обмеження системи', 'Toc', 'Аналітика', 0);


UPDATE metadata set  disabled=1 where meta_name='PredSell';
 

delete from options where  optname='version' ;
insert into options (optname,optvalue) values('version','8.1.0'); 
