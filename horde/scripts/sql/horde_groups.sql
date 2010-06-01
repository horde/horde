CREATE TABLE horde_groups (
    group_uid INTEGER NOT NULL AUTO_INCREMENT,
    group_name VARCHAR(255) NOT NULL,
    group_parents VARCHAR(255) NOT NULL,
    group_email VARCHAR(255),
    PRIMARY KEY (group_uid)
);

CREATE TABLE horde_groups_members (
    group_uid INTEGER NOT NULL,
    user_uid VARCHAR(255) NOT NULL
);
CREATE INDEX group_uid_idx ON horde_groups_members (group_uid);
CREATE INDEX user_uid_idx ON horde_groups_members (user_uid);
