ALTER TABLE roles ADD disabled  tinyint(1) DEFAULT 0;

DROP VIEW roles_view  ;

CREATE VIEW roles_view
AS
SELECT
  `roles`.`role_id` AS `role_id`,
  `roles`.`rolename` AS `rolename`,
  `roles`.`disabled` AS `disabled`,
  `roles`.`acl` AS `acl`,
  (SELECT
      COALESCE(COUNT(0), 0)
    FROM `users`
    WHERE (`users`.`role_id` = `roles`.`role_id`)) AS `cnt`
FROM `roles`;

ALTER TABLE saltypes ADD isedited  tinyint(1)   DEFAULT 0;