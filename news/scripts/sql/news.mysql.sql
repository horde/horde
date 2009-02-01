CREATE TABLE news (
  id smallint(5) UNSIGNED NOT NULL auto_increment,
  sortorder tinyint(2) NOT NULL DEFAULT '0',
  status tinyint(1) UNSIGNED NOT NULL DEFAULT '0',
  view_count smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  publish datetime DEFAULT NULL,
  unpublish datetime DEFAULT NULL,
  submitted datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  updated timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  user varchar(11) NOT NULL DEFAULT '',
  editor varchar(11) NOT NULL DEFAULT '',
  sourcelink varchar(34) NOT NULL DEFAULT '',
  source varchar(11) DEFAULT NULL,
  category1 smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  category2 smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  comments smallint(5) NOT NULL DEFAULT '0',
  chars smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  attachments tinyint(1) UNSIGNED NOT NULL DEFAULT '0',
  picture int(4) NOT NULL DEFAULT '0',
  gallery int(10) UNSIGNED NOT NULL DEFAULT '0',
  selling varchar(50) DEFAULT NULL,
  trackbacks int(10) UNSIGNED NOT NULL DEFAULT '0',
  form_id smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  form_ttl int(10) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY  (id),
  KEY datum (publish),
  KEY sortorder (sortorder),
  KEY status (status),
  KEY user (user),
  KEY cat (category1)
);

CREATE TABLE news_files (
  file_id int(10) UNSIGNED NOT NULL DEFAULT '0',
  news_id int(10) UNSIGNED NOT NULL DEFAULT '0',
  news_lang varchar(5) NOT NULL,
  file_name varchar(85) NOT NULL DEFAULT '',
  file_size int(10) UNSIGNED NOT NULL DEFAULT '0',
  file_type varchar(85) NOT NULL DEFAULT '',
  PRIMARY KEY (file_id),
  KEY news (news_id, news_lang)
);

CREATE TABLE news_body (
  id smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  lang varchar(5) NOT NULL DEFAULT '0',
  title varchar(67) NOT NULL DEFAULT '',
  abbreviation text NOT NULL,
  content text NOT NULL,
  picture_comment varchar(255);
  tags varchar(255);
  PRIMARY KEY  (id,lang)
);

CREATE TABLE news_categories (
  category_id int(10) UNSIGNED NOT NULL auto_increment,
  category_name varchar(50) NOT NULL,
  category_description varchar(255) DEFAULT NULL,
  category_parentid int(10) UNSIGNED NOT NULL,
  category_form varchar(50) NOT NULL,
  category_image int(1) UNSIGNED NOT NULL,
  PRIMARY KEY  (category_id)
);

CREATE TABLE news_categories_nls (
  category_id int(10) UNSIGNED NOT NULL,
  category_nls char(5) NOT NULL,
  category_name varchar(50) NOT NULL,
  category_description varchar(255) NOT NULL,
  PRIMARY KEY  (category_id, category_nls),
  KEY category_nls (category_nls)
);

CREATE TABLE news_sources (
  source_id int(10) UNSIGNED NOT NULL,
  source_name varchar(255) NOT NULL,
  source_url varchar(255) NOT NULL,
  PRIMARY KEY  (source_id)
);

CREATE TABLE news_trackback (
  id int(11) NOT NULL,
  excerpt text,
  created datetime NOT NULL,
  title varchar(255) NOT NULL,
  url varchar(255) NOT NULL,
  blog_name varchar(255) NOT NULL,
  KEY created (created),
  KEY id (id)
);

CREATE TABLE news_user_reads (
  id int(10) UNSIGNED NOT NULL DEFAULT '0',
  user varchar(85) NOT NULL DEFAULT '',
  ip varchar(9) NOT NULL DEFAULT '',
  readdate datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  KEY id (id)
);

CREATE TABLE news_versions (
  id int(10) UNSIGNED NOT NULL DEFAULT '0',
  version float UNSIGNED NOT NULL DEFAULT '0',
  created datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  user_uid varchar(85) NOT NULL DEFAULT '',
  content text NOT NULL,
  PRIMARY KEY  (id,version)
);
