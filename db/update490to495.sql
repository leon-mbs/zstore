INSERT INTO `metadata` ( `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 5, 'АРМ кассира', 'ARMFood', 'Общепит', 0);
INSERT INTO `metadata` ( `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`)  VALUES( 1, 'Заказ', 'OrderFood', 'Общепит', 0);
INSERT INTO `metadata` ( `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`)  VALUES( 3, 'Журнал заказов (общепит)', 'OrderFoodList', '', 0);

ALTER TABLE `paylist` CHANGE `paydate` `paydate` DATETIME NULL DEFAULT NULL;
