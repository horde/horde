-- $Horde: framework/VFS/data/VFS/vfs.pgsql.sql,v 1.4 2008/10/15 05:03:51 chuck Exp $

CREATE TABLE vfs (
    vfs_id        INT NOT NULL,
    vfs_type      SMALLINT NOT NULL,
    vfs_path      VARCHAR(255) NOT NULL,
    vfs_name      VARCHAR(255) NOT NULL,
    vfs_modified  BIGINT NOT NULL,
    vfs_owner     VARCHAR(255) NOT NULL,
    vfs_data      TEXT,

    PRIMARY KEY   (vfs_id)
);

CREATE INDEX vfs_path_idx ON vfs (vfs_path);
CREATE INDEX vfs_name_idx ON vfs (vfs_name);
