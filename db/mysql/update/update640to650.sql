 
DROP VIEW paylist_view ;

CREATE 
VIEW paylist_view
AS
SELECT
  pl.pl_id AS pl_id,
  pl.document_id AS document_id,
  pl.amount AS amount,
  pl.mf_id AS mf_id,
  pl.notes AS notes,
  pl.user_id AS user_id,
  CASE WHEN  pl.paydate IS NOT NULL THEN pl.paydate ELSE d.document_date  END   AS paydate,
  
  pl.paytype AS paytype,
  pl.bonus AS bonus,
  d.document_number AS document_number,
  u.username AS username,
  m.mf_name AS mf_name,
  d.customer_id AS customer_id,
  d.customer_name AS customer_name
FROM (((paylist pl
  JOIN documents_view d
    ON ((pl.document_id = d.document_id)))
  LEFT JOIN users_view u
    ON ((pl.user_id = u.user_id)))
  LEFT JOIN mfund m
    ON ((pl.mf_id = m.mf_id)));
    
    
 

CREATE TABLE stats (
  id bigint(20) NOT NULL AUTO_INCREMENT,
  category smallint(6) NOT NULL,
  keyd int(11) NOT NULL,
  vald int(11) NOT NULL,
  dt datetime DEFAULT NULL,
  KEY (category) ,
  PRIMARY KEY (id)
)  DEFAULT CHARSET=utf8;   

CREATE TABLE keyval (
  keyd varchar(255) NOT NULL,
  vald text NOT NULL,
  PRIMARY KEY (keyd)
)
DEFAULT CHARSET=utf8;

delete  from  options where  optname='version' ;
insert  into options (optname,optvalue) values('version','6.5.0');
     