CREATE SEQUENCE horde_perms_id_seq;
CREATE TABLE horde_perms (
    perm_id INTEGER NOT NULL DEFAULT NEXTVAL('horde_perms_id_seq'),
    perm_name VARCHAR(255) NOT NULL UNIQUE,
    perm_parents VARCHAR(255) NOT NULL,
    perm_data TEXT,
    PRIMARY KEY (perm_id)
);
