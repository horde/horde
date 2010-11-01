CREATE TABLE horde_perms (
    perm_id SERIAL UNIQUE,
    perm_name VARCHAR(255) NOT NULL UNIQUE,
    perm_parents VARCHAR(255) NOT NULL,
    perm_data TEXT,
    PRIMARY KEY (perm_id)
);
