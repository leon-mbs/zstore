SET NAMES 'utf8';

ALTER TABLE documents ADD   INDEX parent_id (parent_id)   ; 
ALTER TABLE documents ADD   INDEX document_number (document_number)   ; 
ALTER TABLE employees ADD   INDEX login (login)   ; 
ALTER TABLE metadata  ADD   INDEX meta_name (meta_name)   ; 

ALTER TABLE equipments ADD  type  smallint DEFAULT 0;
ALTER TABLE parealist  ADD  disabled  tinyint(1) DEFAULT 0;
ALTER TABLE parealist  ADD  branch_id  int(11) DEFAULT null;


 
CREATE TABLE  eqentry (
  id int NOT NULL AUTO_INCREMENT,
  eq_id int NOT NULL,
  optype smallint NOT NULL,
  amount decimal(10, 2) DEFAULT NULL,
  document_id int DEFAULT NULL,
  KEY (eq_id) ,
  KEY (document_id) ,
  PRIMARY KEY (id)
) ENGINE = INNODB DEFAULT CHARSET = utf8 ;  

 

CREATE
VIEW eqentry_view
AS
SELECT
  e.id AS id,
  e.eq_id AS eq_id,
  d.document_date AS document_date,
  e.optype AS optype,
  e.amount AS amount,
  e.document_id AS document_id,
  d.document_number AS document_number,
  d.notes AS notes
FROM (eqentry e
  JOIN documents d
    ON ((e.document_id = d.document_id)))  ;
 

DROP VIEW if exists note_topicnodeview  ;

CREATE
VIEW note_topicnodeview
AS
SELECT
  note_topicnode.topic_id AS topic_id,
  note_topicnode.node_id AS node_id,
  note_topicnode.tn_id AS tn_id,
  note_topicnode.islink AS islink,
  note_topics.title AS title,
  note_topics.content AS content 
   
FROM note_topics
  JOIN note_topicnode
    ON note_topics.topic_id = note_topicnode.topic_id
  JOIN note_nodes
    ON note_topicnode.node_id = note_nodes.node_id    ;
 
     
CREATE TABLE shop_articles (
  id int NOT NULL AUTO_INCREMENT,
  title varchar(255) NOT NULL,
  shortdata text DEFAULT NULL,
  longdata longtext NOT NULL,
  createdon date NOT NULL,
  isactive tinyint NOT NULL DEFAULT 0,
  PRIMARY KEY (id)
) ENGINE = INNODB DEFAULT CHARSET = utf8 ;  

    
DROP VIEW if exists cust_acc_view;


INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 1, 'Операції з ОЗ та  НМА', 'EQ', '', 0);


delete  from options where  optname='version' ;
insert  into options (optname,optvalue) values('version','6.13.0'); 

 