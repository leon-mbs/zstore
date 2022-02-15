CREATE TABLE  ppo_zformrep (
  id int(11) NOT NULL AUTO_INCREMENT,
  createdon date NOT NULL,
  fnpos varchar(255) NOT NULL,
  fndoc varchar(255) NOT NULL,
  amount decimal(10, 2) NOT NULL,
  cnt smallint(6) NOT NULL,
  ramount decimal(10, 2) NOT NULL,
  rcnt smallint(6) NOT NULL,
  sentxml longtext NOT NULL,
  taxanswer longblob NOT NULL,
  PRIMARY KEY (id)
)
ENGINE = INNODB 
 
 ;