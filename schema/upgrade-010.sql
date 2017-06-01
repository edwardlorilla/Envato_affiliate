ALTER TABLE `category` ADD `description` TEXT NOT NULL AFTER `name` ;

ALTER TABLE `category` ADD `title` TEXT NOT NULL AFTER `description` ;

ALTER TABLE `author` ADD `featured` INT NOT NULL DEFAULT '0';

INSERT INTO `setting` (`id`, `label`, `name`, `value`, `type`, `options`, `help`) VALUES (NULL, 'Disqus comment', 'disqus_comment', NULL, 'textarea', '', 'Disqus comment');

ALTER TABLE `item` ADD `featured` INT NOT NULL DEFAULT '0';

ALTER TABLE `page` ADD `meta_description` VARCHAR( 255 ) NULL AFTER `content`;

INSERT INTO `setting` (`id`, `label`, `name`, `value`, `type`, `options`, `help`) VALUES (NULL, 'Display item created', 'item_created_show', '0', 'select', '[{value:"0", text:"Disable"},{value:"1", text:"Enable"}]', 'Display item created'), (NULL, 'Display item last update', 'item_last_update_show', '0', 'select', '[{value:"0", text:"Disable"},{value:"1", text:"Enable"}]', 'Display item last update');

INSERT INTO `setting` (`id`, `label`, `name`, `value`, `type`, `options`, `help`) VALUES (null, 'Price range', 'price_range', '', 'intervals', '', 'Price range');

DELETE FROM `setting` WHERE `label` = 'Site Keywords' ;
