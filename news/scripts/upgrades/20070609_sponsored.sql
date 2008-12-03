ALTER TABLE `news2` ADD `sponsored` TINYINT( 1 ) UNSIGNED NOT NULL AFTER `sourcelink` ;
ALTER TABLE `news2` ADD `parents` VARCHAR( 255 ) NOT NULL AFTER `sponsored` ;