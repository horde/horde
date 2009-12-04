CREATE TABLE kronolith_events_geo (
    event_id VARCHAR(32) NOT NULL,
    lat VARCHAR(32) NOT NULL,
    lon VARCHAR(32) NOT NULL
);

CREATE INDEX kronolith_events_geo_idx ON kronolith_events_geo (event_id);