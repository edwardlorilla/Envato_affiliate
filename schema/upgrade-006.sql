INSERT INTO `setting` (`id`, `label`, `name`, `value`, `type`, `options`, `help`) VALUES (NULL, 'Default market', 'default_market', '1', 'select', '[{value:1, text:''ThemeForest''}, {value:2, text:''CodeCanyon''}, {value:3, text:''VideoHive''}, {value:4, text:''AudioJungle''}, {value:5, text:''GraphicRiver''}, {value:6, text:''PhotoDune''}, {value:7, text:''3DOcean''}]', 'Default market');

UPDATE `setting` SET help = 'Enter your site sticky author and separate by comma. i.e. name1,name2' WHERE name = 'site_sticky_author';

CREATE TABLE `item_stats` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `item_id` int(11) NOT NULL,
  `date` char(10) COLLATE utf8_persian_ci NOT NULL,
  `click` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `item_id_2` (`item_id`,`date`),
  KEY `item_id` (`item_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
