ALTER TABLE `item` ADD `preview` VARCHAR( 200 ) NOT NULL AFTER `demo` ;

ALTER TABLE `item` CHANGE `image` `image` VARCHAR( 200 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ,
CHANGE `thumbnail` `thumbnail` VARCHAR( 200 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
CHANGE `url` `url` VARCHAR( 200 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ,
CHANGE `demo` `demo` VARCHAR( 200 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ;

ALTER TABLE `item` CHANGE `thumbnail` `thumbnail` VARCHAR( 200 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL ,
CHANGE `preview` `preview` VARCHAR( 200 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL ;

ALTER TABLE `item` ADD `view_count` INT NOT NULL DEFAULT '0' AFTER `tags` ;

INSERT IGNORE INTO `category` VALUES
(1, 0, 'ThemeForest', 'themeforest'),
(2, 0, 'CodeCanyon', 'codecanyon'),
(3, 0, 'VideoHive', 'videohive'),
(4, 0, 'AudioJungle', 'audiojungle'),
(5, 0, 'GraphicRiver', 'graphicriver'),
(6, 0, 'PhotoDune', 'photodune'),
(7, 0, '3DOcean', '3docean');


INSERT IGNORE INTO `setting` (`id`, `label`, `name`, `value`, `help`) VALUES
(1, 'Site Name', 'site_name', 'Site Name', 'Enter your site name.'),
(2, 'Site Description', 'site_description', 'php scripts, wordpress themes, wordpress plugins, html 5 templates, code snippets, facebook apps, android apps, iso apps, buy online', 'Enter short description about what you do.'),
(3, 'Site Keywords', 'site_keywords', 'php scripts, wordpress themes, wordpress plugins, html 5 templates, code snippets, facebook apps, android apps, iso apps, buy online', 'Enter your site keywords and separate by comma.'),
(4, 'Envato Username', 'envato_username', 'username', 'Enter your affiliation username in envato.'),
(5, 'Facebook', 'social_facebook', 'username', 'Enter your Facebook username.'),
(6, 'Twitter', 'social_twitter', 'username', 'Enter your Twitter username.'),
(7, 'Google+', 'social_google', 'username', 'Enter your Google+ username.');

INSERT IGNORE INTO `user` (`id`, `username`, `password`, `email`) VALUES
(1, 'admin', 'e10adc3949ba59abbe56e057f20f883e', 'test@envato.com');

ALTER TABLE `item` ADD `slug` VARCHAR( 200 ) NOT NULL AFTER `category_id` ;

ALTER TABLE `category` CHANGE `category_id` `category_id` INT( 11 ) NOT NULL DEFAULT '0';

CREATE TABLE IF NOT EXISTS `page` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `alias` varchar(100) NOT NULL,
  `title` varchar(100) NOT NULL,
  `content` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT IGNORE INTO `setting` (`id`, `label`, `name`, `value`, `help`) VALUES
(NULL, 'Admin Per Page', 'admin_per_page', '10', 'Change per page limit for admin lists.');

CREATE TABLE IF NOT EXISTS `item_tag` (
  `item_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tag` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `tag` ADD UNIQUE(`name`);
ALTER TABLE `tag` CHANGE `name` `name` VARCHAR( 100 ) NOT NULL ;
ALTER TABLE `item_tag` ADD UNIQUE( `item_id`, `tag_id`);
ALTER TABLE `page` ENGINE = MYISAM ;
ALTER TABLE `tag` ENGINE = MYISAM ;
ALTER TABLE `item` DROP `tags` ;
ALTER TABLE `source` ADD UNIQUE(`url`);

CREATE TABLE IF NOT EXISTS `advert` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL,
  `content` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

INSERT INTO `advert` (`id`, `title`, `content`) VALUES
(1, 'Advert', '<img src="http://placehold.it/180x150" alt="advertise">');

ALTER TABLE `item` ADD `user` VARCHAR( 100 ) NOT NULL AFTER `sales` ;
ALTER TABLE `item` ADD `uploaded_on` DATETIME NOT NULL AFTER `user` ,
ADD `last_update` DATETIME NOT NULL AFTER `uploaded_on` ;
ALTER TABLE `item` ADD `rating` DECIMAL( 19, 1 ) NOT NULL AFTER `price` ;

INSERT INTO `setting` (`id`, `label`, `name`, `value`, `help`) VALUES
(NULL, 'Hot User', 'site_hot_user', 'username', 'Items from this user display as featured on sidebar.');

ALTER TABLE `category` ADD `item_count` INT NOT NULL AFTER `alias` ;

INSERT INTO `setting` (`id`, `label`, `name`, `value`, `help`) VALUES
(NULL, 'Category Item Count', 'cats_item_count', '1', 'You can disable or enable categories item count by change this to 0 or 1.');


INSERT INTO `setting` (`id`, `label`, `name`, `value`, `help`) VALUES
(NULL, 'Item Title', 'item_title_show', '0', 'Change to disable or enable displaying title of items.');
INSERT INTO `setting` (`id`, `label`, `name`, `value`, `help`) VALUES
(NULL, 'Item Cost', 'item_cost_show', '0', 'Change to disable or enable displaying cost of items.');
INSERT INTO `setting` (`id`, `label`, `name`, `value`, `help`) VALUES
(NULL, 'Item Sales', 'item_sales_show', '0', 'Change to disable or enable displaying sales of items.');
INSERT INTO `setting` (`id`, `label`, `name`, `value`, `help`) VALUES
(NULL, 'Item Rating', 'item_rating_show', '0', 'Change to disable or enable displaying rating of items.');
INSERT INTO `setting` (`id`, `label`, `name`, `value`, `help`) VALUES
(NULL, 'Item Author', 'item_author_show', '0', 'Change to disable or enable displaying author of items.');
INSERT INTO `setting` (`id`, `label`, `name`, `value`, `help`) VALUES
(NULL, 'Social Share', 'item_share_show', '1', 'Change to disable or enable displaying social share icons.');

ALTER TABLE `category` ADD INDEX ( `alias` ) ;
ALTER TABLE `category` ADD INDEX ( `category_id` ) ;
ALTER TABLE `item` ADD INDEX ( `category_id` ) ;
ALTER TABLE `page` ADD UNIQUE(`alias`);
ALTER TABLE `source` ADD INDEX ( `category_id` ) ;
ALTER TABLE `user` ADD UNIQUE(`username`);

ALTER TABLE `item` ADD `synced_on` DATETIME NOT NULL AFTER `last_update` ;

ALTER TABLE `setting` ADD `type` VARCHAR( 100 ) NOT NULL AFTER `value` , ADD `option` VARCHAR( 200 ) NOT NULL AFTER `type` ;
ALTER TABLE `setting` CHANGE `option` `options` VARCHAR( 200 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ;


UPDATE `setting` SET `type` = 'text',`options` = '' WHERE `setting`.`name` = 'site_name';
UPDATE `setting` SET `type` = 'textarea',`options` = '' WHERE `setting`.`name` = 'site_description';
UPDATE `setting` SET `type` = 'textarea',`options` = '' WHERE `setting`.`name` = 'site_keywords';
UPDATE `setting` SET `type` = 'text',`options` = '' WHERE `setting`.`name` = 'envato_username';
UPDATE `setting` SET `type` = 'text',`options` = '' WHERE `setting`.`name` = 'social_facebook';
UPDATE `setting` SET `type` = 'text',`options` = '' WHERE `setting`.`name` = 'social_twitter';
UPDATE `setting` SET `type` = 'text',`options` = '' WHERE `setting`.`name` = 'social_google';
UPDATE `setting` SET `type` = 'range',`options` = '' WHERE `setting`.`name` = 'admin_per_page';
UPDATE `setting` SET `type` = 'select',`options` = '[{value:"0", text:"Disable"},{value:"1", text:"Enable"}]' WHERE `setting`.`name` = 'cats_item_count';
UPDATE `setting` SET `type` = 'text',`options` = '' WHERE `setting`.`name` = 'site_hot_user';
UPDATE `setting` SET `type` = 'select',`options` = '[{value:"0", text:"Disable"},{value:"1", text:"Enable"}]' WHERE `setting`.`name` = 'item_title_show';
UPDATE `setting` SET `type` = 'select',`options` = '[{value:"0", text:"Disable"},{value:"1", text:"Enable"}]' WHERE `setting`.`name` = 'item_cost_show';
UPDATE `setting` SET `type` = 'select',`options` = '[{value:"0", text:"Disable"},{value:"1", text:"Enable"}]' WHERE `setting`.`name` = 'item_sales_show';
UPDATE `setting` SET `type` = 'select',`options` = '[{value:"0", text:"Disable"},{value:"1", text:"Enable"}]' WHERE `setting`.`name` = 'item_rating_show';
UPDATE `setting` SET `type` = 'select',`options` = '[{value:"0", text:"Disable"},{value:"1", text:"Enable"}]' WHERE `setting`.`name` = 'item_author_show';
UPDATE `setting` SET `type` = 'select',`options` = '[{value:"0", text:"Disable"},{value:"1", text:"Enable"}]' WHERE `setting`.`name` = 'item_share_show';

ALTER TABLE `category` DROP INDEX `alias` ;
ALTER TABLE `category` DROP INDEX `alias_2` ;
ALTER TABLE `category` ADD INDEX ( `alias` ) ;

ALTER TABLE `page` ADD `footer` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `content` ;
ALTER TABLE `category` ADD UNIQUE( `category_id`, `alias`);

ALTER TABLE `setting` CHANGE `options` `options` VARCHAR( 300 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ;

INSERT INTO `setting` (`id`, `label`, `name`, `value`, `type`, `options`, `help`) VALUES
(17, 'Site Markets', 'site_markets', '["1","2","3","4","5","7"]', 'checklist', '[{value:1, text:''ThemeForest''}, {value:2, text:''CodeCanyon''}, {value:3, text:''VideoHive''}, {value:4, text:''AudioJungle''}, {value:5, text:''GraphicRiver''}, {value:6, text:''PhotoDune''}, {value:7, text:''3DOcean''}]', 'Choose markets that you want show on primary site menu.');

ALTER TABLE `source` ADD `last_update` DATETIME NOT NULL AFTER `url` ;

UPDATE `source` SET `last_update` = NOW();

ALTER TABLE `item` ADD `preview_audio_url` VARCHAR( 200 ) NULL DEFAULT NULL AFTER `preview` ;
ALTER TABLE `item` ADD `preview_video_url` VARCHAR( 200 ) NULL DEFAULT NULL AFTER `preview_audio_url` ;