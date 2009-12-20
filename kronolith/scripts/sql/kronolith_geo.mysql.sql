-- This script creates the table and indexs necessary to use kronolith's geo
-- location features with a gis extension enabled mySQL database.

CREATE TABLE kronolith_events_geo (
    event_id VARCHAR(32) NOT NULL,
    event_coordinates POINT NOT NULL,
    SPATIAL INDEX (event_coordinates)
);

CREATE INDEX kronolith_events_geo_idx ON kronolith_events_geo (event_id);