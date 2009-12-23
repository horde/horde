-- $Horde: framework/VFS/data/VFS/vfs.sql,v 1.3 2008/07/19 23:14:36 chuck Exp $

CREATE TABLE vfs (
    vfs_id        INT UNSIGNED NOT NULL,
    vfs_type      SMALLINT UNSIGNED NOT NULL,
    vfs_path      VARCHAR(255) NOT NULL,
    vfs_name      VARCHAR(255) NOT NULL,
    vfs_modified  BIGINT NOT NULL,
    vfs_owner     VARCHAR(255) NOT NULL,
    vfs_data      LONGBLOB,
-- Or, on some DBMS systems:
--  vfs_data      IMAGE,
    PRIMARY KEY   (vfs_id)
);

CREATE INDEX vfs_path_idx ON vfs (vfs_path);
CREATE INDEX vfs_name_idx ON vfs (vfs_name);
