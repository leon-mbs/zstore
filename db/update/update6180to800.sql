SET NAMES 'utf8'; 

delete from options where  optname='version' ;
insert into options (optname,optvalue) values('version','8.0.0'); 
