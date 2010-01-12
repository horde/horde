ALTER TABLE kronolith_events_geo CHANGE coordinates event_coordinates POINT NOT NULL;
CREATE INDEX kronolith_events_geo_idx ON kronolith_events_geo (event_id);