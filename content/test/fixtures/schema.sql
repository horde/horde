CREATE TABLE rampage_objects (
  object_id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  object_name varchar(255) NOT NULL,
  type_id INTEGER NOT NULL
);
CREATE UNIQUE INDEX rampage_objects_type_object_name ON rampage_objects (type_id, object_name);

CREATE TABLE rampage_types (
  type_id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  type_name varchar(255) NOT NULL
);
CREATE UNIQUE INDEX rampage_types_type_name ON rampage_types (type_name);

CREATE TABLE rampage_users (
  user_id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  user_name varchar(255) NOT NULL
);
CREATE UNIQUE INDEX rampage_users_user_name ON rampage_users (user_name);

CREATE TABLE rampage_tags (
  tag_id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  tag_name varchar(255) NOT NULL
);
CREATE UNIQUE INDEX rampage_tags_tag_name ON rampage_tags (tag_name);

CREATE TABLE rampage_tagged (
  user_id INTEGER NOT NULL,
  object_id INTEGER NOT NULL,
  tag_id INTEGER NOT NULL,
  created datetime default NULL,
  PRIMARY KEY (user_id, object_id, tag_id)
);
CREATE INDEX rampage_tagged_object_id ON rampage_tagged (object_id);
CREATE INDEX rampage_tagged_tag_id ON rampage_tagged (tag_id);
CREATE INDEX rampage_tagged_created ON rampage_tagged (created);

CREATE TABLE rampage_tag_stats (
  tag_id INTEGER NOT NULL,
  count INTEGER NOT NULL,
  PRIMARY KEY (tag_id)
);

CREATE TABLE rampage_user_tag_stats (
  user_id INTEGER NOT NULL,
  tag_id INTEGER NOT NULL,
  count INTEGER NOT NULL,
  PRIMARY KEY (user_id, tag_id)
);
CREATE INDEX rampage_user_tag_stats_tag_id ON rampage_user_tag_stats (tag_id);


--
-- Set up some initial types, objects, users, and tags
--

INSERT INTO rampage_types (type_id, type_name) VALUES (1, 'event');
INSERT INTO rampage_types (type_id, type_name) VALUES (2, 'blog');

INSERT INTO rampage_objects (object_id, object_name, type_id) VALUES (1, 'party', 1);
INSERT INTO rampage_objects (object_id, object_name, type_id) VALUES (2, 'office hours', 1);
INSERT INTO rampage_objects (object_id, object_name, type_id) VALUES (3, 'huffington post', 2);
INSERT INTO rampage_objects (object_id, object_name, type_id) VALUES (4, 'daring fireball', 2);

INSERT INTO rampage_users (user_id, user_name) VALUES (1, 'alice');
INSERT INTO rampage_users (user_id, user_name) VALUES (2, 'bob');

INSERT INTO rampage_tags (tag_id, tag_name) VALUES (1, 'work');
INSERT INTO rampage_tags (tag_id, tag_name) VALUES (2, 'play');
INSERT INTO rampage_tags (tag_id, tag_name) VALUES (3, 'apple');
