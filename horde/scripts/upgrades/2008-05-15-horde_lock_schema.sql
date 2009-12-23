DROP TABLE horde_locks;

CREATE TABLE horde_locks (
    lock_id                  VARCHAR(32) NOT NULL,
    lock_owner               VARCHAR(32) NOT NULL,
    lock_scope               VARCHAR(32) NOT NULL,
    lock_principal           VARCHAR(255) NOT NULL,
    lock_origin_timestamp    BIGINT NOT NULL,
    lock_update_timestamp    BIGINT NOT NULL,
    lock_expiry_timestamp    BIGINT NOT NULL,
    lock_type                TINYINT NOT NULL,

    PRIMARY KEY (lock_id)
);
