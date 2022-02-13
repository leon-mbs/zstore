CREATE TABLE  ppo_zformrep (
  id int(11) NOT NULL AUTO_INCREMENT,
  createdon date NOT NULL,
  fnpos varchar(255) NOT NULL,
  fndoc varchar(255) NOT NULL,
  amount decimal(10, 2) NOT NULL,
  cnt decimal(10, 3) NOT NULL,
  ramount decimal(10, 2) NOT NULL,
  rcnt decimal(10, 3) NOT NULL,
  sentxml longtext   NULL,
  taxanswer longblob   NULL,
  PRIMARY KEY (id)
)
ENGINE = INNODB 
 
 ;