CREATE TABLE horde_cache (
    cache_id          VARCHAR(32) NOT NULL,
    cache_timestamp   BIGINT NOT NULL,
    cache_expiration  BIGINT NOT NULL,
    cache_data        TEXT,

    PRIMARY KEY  (cache_id)
);
