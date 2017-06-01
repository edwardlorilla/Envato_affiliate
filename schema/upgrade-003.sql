INSERT INTO `setting` (`id`, `label`, `name`, `value`, `type`, `options`, `help`)
VALUES (NULL, 'Site SMTP Status', 'site_smtp_status', '0', 'select', '[{value:"0", text:"Disable"},{value:"1", text:"Enable"}]', 'Site SMTP Status.');

INSERT INTO `setting` (`id`, `label`, `name`, `value`, `type`, `options`, `help`)
VALUES (NULL, 'Site SMTP Hostname', 'site_smtp_hostname', 'example.com', 'text', '', 'Site SMTP Hostname.');

INSERT INTO `setting` (`id`, `label`, `name`, `value`, `type`, `options`, `help`)
VALUES (NULL, 'Site SMTP Username', 'site_smtp_username', 'info@example.com', 'text', '', 'Site SMTP Username.');

INSERT INTO `setting` (`id`, `label`, `name`, `value`, `type`, `options`, `help`)
VALUES (NULL, 'Site SMTP Password', 'site_smtp_password', '123456', 'text', '', 'Site SMTP Password.');

ALTER TABLE `author` ADD UNIQUE(`username`);