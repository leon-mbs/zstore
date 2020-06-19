

ALTER TABLE   `issue_history` ADD `description` VARCHAR(255)   NULL  ;

CREATE TABLE  `issue_projectacc` ( `id` INT NOT NULL AUTO_INCREMENT , `project_id` INT NOT NULL , `user_id` INT NOT NULL , PRIMARY KEY (`id`))  ;

ALTER TABLE   `note_nodes`  ADD  `ispublic` TINYINT(1) NULL DEFAULT '0'  ;
ALTER TABLE   `note_topics` DROP `favorites`;
ALTER TABLE   `note_topics` DROP `ispublic`;
ALTER TABLE   `note_topics` ADD  `acctype`  SMALLINT(4) NULL DEFAULT '0'  ;
ALTER TABLE   `note_topics` ADD `user_id` INT NOT NULL DEFAULT '0'  ;
ALTER TABLE `note_topics` CHANGE `content` `content` LONGTEXT CHARACTER SET utf8 COLLATE utf8_general_ci   NULL;

CREATE TABLE  `note_fav` ( `fav_id` INT NOT NULL AUTO_INCREMENT , `topic_id` INT NOT NULL , `user_id` INT NOT NULL , PRIMARY KEY (`fav_id`))  ;

