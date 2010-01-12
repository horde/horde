ALTER TABLE `news` CHANGE `reads` `view_count` SMALLINT( 5 ) UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `news` ADD `trackbacks` SMALLINT NOT NULL DEFAULT '0';

CREATE TABLE `news_trackback` (
  `id` int(11) NOT NULL,
  `excerpt` text,
  `created` datetime NOT NULL,
  `title` varchar(255) NOT NULL,
  `url` varchar(255) NOT NULL,
  `blog_name` varchar(255) NOT NULL,
  KEY `created` (`created`),
  KEY `id` (`id`)
);
