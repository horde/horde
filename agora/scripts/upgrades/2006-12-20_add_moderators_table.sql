CREATE TABLE agora_moderators (
    forum_id INT DEFAULT 0 NOT NULL,
    horde_uid VARCHAR(32) NOT NULL,
--
    PRIMARY KEY (forum_id, horde_uid)
);
