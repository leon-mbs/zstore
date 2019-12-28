-- v3.1.0
ALTER TABLE `items` CHANGE `description` `description` LONGTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;

--v3.2.0
ALTER TABLE `items` CHANGE `description` `description` LONGTEXT