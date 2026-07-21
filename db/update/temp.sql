SET NAMES 'utf8'; 


ALTER TABLE users ADD otpcode int DEFAULT NULL ;
ALTER TABLE store_stock ADD tag int DEFAULT NULL ;
 
delete from options where  optname='version' ;
insert into options (optname,optvalue) values('version','8.3.0'); 

