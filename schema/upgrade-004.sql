ALTER TABLE  `item` ADD  `static` TINYINT( 1 ) NOT NULL DEFAULT  '0' AFTER  `id`;

INSERT INTO `setting` (`id`, `label`, `name`, `value`, `type`, `options`, `help`)
VALUES (NULL, 'Site Sticky Author', 'site_sticky_author', 'preciouscoder', 'text', '', 'Enter your site sticky author.');