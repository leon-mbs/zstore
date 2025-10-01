
    
INSERT INTO metadata (meta_type, description, meta_name,  menugroup,   disabled) VALUES(  3, 'Замiни ТМЦ', 'Substitution', 'Склад',     0);
UPDATE metadata set menugroup ='Каса та платежі' where meta_name='EndDay';
    
 

delete from options where  optname='version' ;
insert into options (optname,optvalue) values('version','6.17.0'); 
