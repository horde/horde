CREATE TABLE vfs (
    vfs_id        INT UNSIGNED NOT NULL,
    vfs_type      SMALLINT UNSIGNED NOT NULL,
    vfs_path      VARCHAR(255) NOT NULL,
    vfs_name      VARCHAR(255) NOT NULL,
    vfs_modified  BIGINT NOT NULL,
    vfs_owner     VARCHAR(255) NOT NULL,
    vfs_data      VARBINARY(MAX),
    PRIMARY KEY   (vfs_id)
);

CREATE INDEX vfs_path_idx ON vfs (vfs_path);
CREATE INDEX vfs_name_idx ON vfs (vfs_name);
