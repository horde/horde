CREATE TABLE agora_files (
    file_id INT(11) UNSIGNED NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_size INT(11) UNSIGNED DEFAULT 0 NOT NULL,
    file_type VARCHAR(32) NOT NULL,
    message_id MEDIUMINT(9) UNSIGNED DEFAULT 0,
--
    PRIMARY KEY (file_id)
);
CREATE INDEX agora_file_message_idx ON agora_files (message_id);

CREATE TABLE agora_forums (
    forum_id SMALLINT(6) UNSIGNED NOT NULL,
    scope VARCHAR(10) NOT NULL,
    forum_name VARCHAR(255) NOT NULL,
    active SMALLINT(6) UNSIGNED NOT NULL,
    forum_description TEXT,
    forum_parent_id SMALLINT(11) UNSIGNED,
    author VARCHAR(32) NOT NULL,
    forum_moderated SMALLINT(6) UNSIGNED,
    forum_attachments TINYINT(1) UNSIGNED DEFAULT 0,
    forum_distribution_address VARCHAR(255) DEFAULT '' NOT NULL,
    message_count SMALLINT(6) UNSIGNED DEFAULT 0,
    thread_count SMALLINT(6) UNSIGNED DEFAULT 0,
    count_views SMALLINT(6) UNSIGNED,
    last_message_id MEDIUMINT(10) UNSIGNED DEFAULT 0,
    last_message_author VARCHAR(50),
    last_message_timestamp MEDIUMINT(10) UNSIGNED DEFAULT 0,
--
    PRIMARY KEY (forum_id)
);
CREATE INDEX agora_forum_scope_idx ON agora_forums (scope, active);

CREATE TABLE agora_messages (
    message_id MEDIUMINT(9) UNSIGNED NOT NULL,
    forum_id SMALLINT(6) UNSIGNED DEFAULT 0 NOT NULL,
    message_thread MEDIUMINT(9) UNSIGNED DEFAULT 0 NOT NULL,
    parents VARCHAR(255) DEFAULT NULL,
    message_author VARCHAR(32) NOT NULL,
    message_subject VARCHAR(85) NOT NULL,
    body TEXT NOT NULL,
    attachments TINYINT(1) UNSIGNED DEFAULT 0 NOT NULL,
    ip VARCHAR(30) NOT NULL,
    status TINYINT(1) UNSIGNED DEFAULT 2 NOT NULL,
    message_seq INT(11) DEFAULT 0 NOT NULL,
    approved TINYINT(1) UNSIGNED DEFAULT 0 NOT NULL,
    message_timestamp INT(11) UNSIGNED DEFAULT 0 NOT NULL,
    message_modifystamp INT(11) UNSIGNED DEFAULT 0 NOT NULL,
    view_count INT(11) UNSIGNED DEFAULT 0 NOT NULL,
    locked TINYINT(1) UNSIGNED DEFAULT 0 NOT NULL,
    last_message_id INT(10) UNSIGNED DEFAULT 0 NOT NULL,
    last_message_author VARCHAR(255),
--
    PRIMARY KEY (message_id)
);
CREATE INDEX agora_messages_forum_id ON agora_messages (forum_id);
CREATE INDEX agora_messages_message_thread ON agora_messages (message_thread);
CREATE INDEX agora_messages_parents ON agora_messages (parents);

CREATE TABLE agora_moderators (
    forum_id SMALLINT(6) UNSIGNED NOT NULL,
    horde_uid VARCHAR(32) NOT NULL,
--
    PRIMARY KEY (forum_id, horde_uid)
);
