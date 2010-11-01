CREATE TABLE agora_files (
    file_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_size INT DEFAULT 0 NOT NULL,
    file_type VARCHAR(32) NOT NULL,
    message_id INT DEFAULT 0 NOT NULL,
--
    PRIMARY KEY (file_id)
);
CREATE INDEX agora_file_message_idx ON agora_files (message_id);

CREATE TABLE agora_forums (
    forum_id INT NOT NULL,
    scope VARCHAR(10) NOT NULL,
    forum_name VARCHAR(255) NOT NULL,
    active SMALLINT NOT NULL,
    forum_description VARCHAR(255),
    forum_parent_id INT,
    author VARCHAR(32) NOT NULL,
    forum_moderated SMALLINT,
    forum_attachments SMALLINT DEFAULT 0 NOT NULL,
    forum_distribution_address VARCHAR(255) DEFAULT '' NOT NULL,
    message_count INT DEFAULT 0,
    thread_count INT DEFAULT 0,
    count_views SMALLINT,
    last_message_id INT DEFAULT 0 NOT NULL,
    last_message_author VARCHAR(32),
    last_message_timestamp INT DEFAULT 0 NOT NULL,
--
    PRIMARY KEY (forum_id)
);
CREATE INDEX agora_forum_scope_idx ON agora_forums (scope, active);

CREATE TABLE agora_messages (
    message_id INT NOT NULL,
    forum_id INT DEFAULT 0 NOT NULL,
    message_thread INT DEFAULT 0 NOT NULL,
    parents VARCHAR(255),
    message_author VARCHAR(32) NOT NULL,
    message_subject VARCHAR(85) NOT NULL,
    body text NOT NULL,
    attachments SMALLINT DEFAULT 0 NOT NULL,
    ip VARCHAR(30) NOT NULL,
    status SMALLINT DEFAULT 2 NOT NULL,
    message_seq INT DEFAULT 0 NOT NULL,
    approved SMALLINT DEFAULT 0 NOT NULL,
    message_timestamp INT DEFAULT 0 NOT NULL,
    message_modifystamp INT DEFAULT 0 NOT NULL,
    view_count INT DEFAULT 0 NOT NULL,
    locked SMALLINT DEFAULT 0 NOT NULL,
    last_message_id INT DEFAULT 0 NOT NULL,
    last_message_author VARCHAR(32),
--
    PRIMARY KEY  (message_id)
);
CREATE INDEX agora_messages_forum_id ON agora_messages (forum_id);
CREATE INDEX agora_messages_message_thread ON agora_messages (message_thread);
CREATE INDEX agora_messages_parents ON agora_messages (parents);

CREATE TABLE agora_moderators (
    forum_id INT DEFAULT 0 NOT NULL,
    horde_uid VARCHAR(32) NOT NULL,
--
    PRIMARY KEY (forum_id, horde_uid)
);
