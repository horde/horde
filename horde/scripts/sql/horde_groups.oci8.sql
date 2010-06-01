CREATE TABLE horde_groups (
    group_uid NUMBER(16) NOT NULL,
    group_name VARCHAR2(255) NOT NULL UNIQUE,
    group_parents VARCHAR2(255) NOT NULL,
    group_email VARCHAR2(255),
    PRIMARY KEY (group_uid)
);

CREATE TABLE horde_groups_members (
    group_uid NUMBER(16) NOT NULL,
    user_uid VARCHAR2(255) NOT NULL
);

CREATE INDEX group_uid_idx ON horde_groups_members (group_uid);
CREATE INDEX user_uid_idx ON horde_groups_members (user_uid);

CREATE SEQUENCE horde_groups_uid_seq;
CREATE TRIGGER horde_groups_uid_trigger
BEFORE INSERT ON horde_groups
FOR EACH ROW
BEGIN
    SELECT horde_groups_uid_seq.nextval INTO :new.group_uid FROM dual;
END;
