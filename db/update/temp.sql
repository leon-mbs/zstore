SET NAMES 'utf8';

ALTER TABLE promocodes ADD dateto DATE DEFAULT  NULL ;

ALTER TABLE note_topics ADD ispublic   tinyint(1) DEFAULT 0;
ALTER TABLE note_topicnode ADD islink  tinyint(1) DEFAULT 0;
//ALTER TABLE note_nodes ADD detail  text DEFAULT NULL,;
 
ALTER TABLE documents DROP INDEX `unuqnumber`;

delete  from  options where  optname='version' ;
insert  into options (optname,optvalue) values('version','6.12.0'); 
    