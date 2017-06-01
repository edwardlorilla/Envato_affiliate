INSERT IGNORE INTO `setting` (`id`, `label`, `name`, `value`, `help`) VALUES
  (NULL, 'Authers Sidebar Button', 'authors_sidebar_button', '0', 'Change visibility of authors button on sidebar.');

UPDATE `setting`
SET `type` = 'select', `options` = '[{value:"0", text:"Disable"},{value:"1", text:"Enable"}]'
WHERE `setting`.`name` = 'authors_sidebar_button';

CREATE TABLE IF NOT EXISTS `author` (
  `id`        INT(11)      NOT NULL AUTO_INCREMENT,
  `username`  VARCHAR(100)  NOT NULL,
  `country`   VARCHAR(100)  NOT NULL,
  `sales`     INT(11)      NOT NULL,
  `location`  VARCHAR(300) NOT NULL,
  `image`     VARCHAR(300) NOT NULL,
  `followers` INT(11)      NOT NULL,
  PRIMARY KEY (`id`)
)
  ENGINE = MyISAM
  DEFAULT CHARSET = utf8;

INSERT IGNORE INTO `setting` (`id`, `label`, `name`, `value`, `help`) VALUES
  (NULL, 'Contacts Footer Link', 'contacts_footer_link', '0', 'Change visibility of contacts link on footer.');

UPDATE `setting`
SET `type` = 'select', `options` = '[{value:"0", text:"Disable"},{value:"1", text:"Enable"}]'
WHERE `setting`.`name` = 'contacts_footer_link';

INSERT IGNORE INTO `setting` (`id`, `label`, `name`, `value`, `help`) VALUES
  (NULL, 'Contacts Email Address', 'contacts_email', 'info@example.com', 'Contacts emails will send to this email address.');

UPDATE `setting`
SET `type` = 'text', `options` = ''
WHERE `setting`.`name` = 'contacts_email';

INSERT IGNORE INTO `setting` (`id`, `label`, `name`, `value`, `help`) VALUES
  (NULL, 'Google Analytics Id', 'google_analytics_id', 'UA-XXXXX-X', 'Google analytics tracing id.');

UPDATE `setting`
SET `type` = 'text', `options` = ''
WHERE `setting`.`name` = 'google_analytics_id';

INSERT IGNORE INTO `setting` (`id`, `label`, `name`, `value`, `help`) VALUES
  (NULL, 'Google Analytics Domain', 'google_analytics_domain', 'example.com', 'Your defined site domain in google analytics.');

UPDATE `setting`
SET `type` = 'text', `options` = ''
WHERE `setting`.`name` = 'google_analytics_domain';

CREATE TABLE IF NOT EXISTS `post` (
  `id`         INT(11)     NOT NULL AUTO_INCREMENT,
  `title`      VARCHAR(200) NOT NULL,
  `text`       LONGTEXT    NOT NULL,
  `more`       LONGTEXT    NOT NULL,
  `created_at` DATETIME    NOT NULL,
  `updated_at` DATETIME    NOT NULL,
  PRIMARY KEY (`id`)
)
  ENGINE =MyISAM
  AUTO_INCREMENT =1
  DEFAULT CHARSET =utf8;

ALTER TABLE  `post` ADD  `alias` VARCHAR( 200 ) NOT NULL AFTER  `title`;

ALTER TABLE `post` ADD UNIQUE(`alias`);

INSERT IGNORE INTO `setting` (`id`, `label`, `name`, `value`, `help`) VALUES
  (NULL, 'Blog Footer Link', 'blog_footer_link', '0', 'Change visibility of weblog link on footer.');

UPDATE `setting`
SET `type` = 'select', `options` = '[{value:"0", text:"Disable"},{value:"1", text:"Enable"}]'
WHERE `setting`.`name` = 'blog_footer_link';

INSERT IGNORE INTO `setting` (`id`, `label`, `name`, `value`, `help`, `type`, `options`) VALUES
  (NULL, 'Site Image Logo', 'site_image_logo', '', 'Changing site logo using uploaded image.', 'file', '');

ALTER TABLE  `setting` CHANGE  `options`  `options` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;

INSERT INTO `setting` (`id`, `label`, `name`, `value`, `type`, `options`, `help`) VALUES
  (NULL, 'Item Currency', 'item_currency_type', 'USD', 'select', '[ {value:"USD", text:"USD"}, {value:"JPY", text:"JPY"}, {value:"BGN", text:"BGN"}, {value:"CZK", text:"CZK"}, {value:"DKK", text:"DKK"}, {value:"GBP", text:"GBP"}, {value:"HUF", text:"HUF"}, {value:"LTL", text:"LTL"}, {value:"PLN", text:"PLN"}, {value:"RON", text:"RON"}, {value:"SEK", text:"SEK"}, {value:"CHF", text:"CHF"}, {value:"NOK", text:"NOK"}, {value:"HRK", text:"HRK"}, {value:"RUB", text:"RUB"}, {value:"TRY", text:"TRY"}, {value:"AUD", text:"AUD"}, {value:"BRL", text:"BRL"}, {value:"CAD", text:"CAD"}, {value:"CNY", text:"CNY"}, {value:"HKD", text:"HKD"}, {value:"IDR", text:"IDR"}, {value:"ILS", text:"ILS"}, {value:"INR", text:"INR"}, {value:"KRW", text:"KRW"}, {value:"MXN", text:"MXN"}, {value:"MYR", text:"MYR"}, {value:"NZD", text:"NZD"}, {value:"PHP", text:"PHP"}, {value:"SGD", text:"SGD"}, {value:"THB", text:"THB"}, {value:"ZAR", text:"ZAR"} ]', 'Change to currency for cost of items.');

INSERT INTO `setting` (`id`, `label`, `name`, `value`, `type`, `options`, `help`)
VALUES (NULL, 'Featured Item', 'item_featured_show', '0', 'select', '[{value:"0", text:"Disable"},{value:"1", text:"Enable"}]', 'Change to disable or enable displaying featured item.');

INSERT INTO `setting` (`id`, `label`, `name`, `value`, `type`, `options`, `help`)
VALUES (NULL, 'Free Item', 'item_free_show', '0', 'select', '[{value:"0", text:"Disable"},{value:"1", text:"Enable"}]', 'Change to disable or enable displaying free item.');
