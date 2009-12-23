CREATE TABLE horde_cache (
    cache_id          VARCHAR(32) NOT NULL,
    cache_timestamp   BIGINT NOT NULL,
    cache_expiration  BIGINT NOT NULL,
    cache_data        LONGBLOB,
-- Or on PostgreSQL:
--  cache_data        TEXT,
-- Or on some other DBMS systems:
--  cache_data        IMAGE,

    PRIMARY KEY  (cache_id)
);
