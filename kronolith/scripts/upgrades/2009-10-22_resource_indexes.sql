ALTER TABLE kronolith_resources DROP resource_category;
CREATE INDEX kronolith_resources_type ON kronolith_resources (resource_type);
CREATE INDEX kronolith_resources_calendar ON kronolith_resources (resource_calendar);
