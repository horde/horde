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
