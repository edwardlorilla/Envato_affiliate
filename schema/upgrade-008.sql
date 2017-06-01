ALTER TABLE category DROP INDEX category_id_2;
ALTER TABLE `item` ADD INDEX ( `title` );
ALTER TABLE item_stats DROP INDEX item_id_2;
