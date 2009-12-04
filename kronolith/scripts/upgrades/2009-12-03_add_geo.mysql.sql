CREATE TABLE kronolith_events_geo (
    event_id VARCHAR(32) NOT NULL,
    coordinates POINT NOT NULL,
    SPATIAL INDEX (coordinates)
);
