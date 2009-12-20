-- Use this script to create the table and index necessary for geo location
-- support without mysql spatial extension support.
CREATE TABLE kronolith_events_geo (
    event_id VARCHAR(32) NOT NULL,
    event_lat VARCHAR(32) NOT NULL,
    event_lon VARCHAR(32) NOT NULL
);

CREATE INDEX kronolith_events_geo_idx ON kronolith_events_geo (event_id);