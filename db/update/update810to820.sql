SET NAMES 'utf8'; 

ALTER TABLE documents  CHANGE COLUMN user_id user_id INT DEFAULT NULL;
ALTER TABLE docstatelog  CHANGE COLUMN user_id user_id INT DEFAULT NULL;

DROP VIEW docstatelog_view  ;

CREATE VIEW docstatelog_view
AS
SELECT
  dl.log_id AS log_id,
  dl.user_id AS user_id,
  dl.document_id AS document_id,
  dl.docstate AS docstate,
  dl.createdon AS createdon,
  dl.hostname AS hostname,
  u.username AS username,
  d.document_number AS document_number,
  d.meta_desc AS meta_desc,
  d.meta_name AS meta_name
FROM ((docstatelog dl
  LEFT JOIN users_view u
    ON ((dl.user_id = u.user_id)))
  JOIN documents_view d
    ON ((d.document_id = dl.document_id))) ;


INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 2, 'Форма ведення обліку ТМЦ', 'FormItemsReport', 'Склад', 0);
 
delete from options where  optname='version' ;
insert into options (optname,optvalue) values('version','8.2.0'); 

