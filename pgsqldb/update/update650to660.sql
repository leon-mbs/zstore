 
ALTER TABLE stores ADD disabled smallint  DEFAULT 0  ;
ALTER TABLE mfund  ADD disabled smallint   DEFAULT 0  ;
 
 
 
DELETE  FROM "options" WHERE  optname='version' ;
INSERT INTO "options" (optname, optvalue) values('version','6.6.0');   