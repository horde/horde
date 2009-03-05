CREATE TABLE horde_cache (
    cache_id          VARCHAR(32) NOT NULL,
    cache_timestamp   BIGINT NOT NULL,
    cache_data        LONGBLOB,
-- Or, on some DBMS systems:
--  cache_data        IMAGE,

    PRIMARY KEY  (cache_id)
);
