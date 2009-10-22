ALTER TABLE kronolith_events ADD event_resources TEXT;

CREATE TABLE kronolith_resources (
    resource_id INT NOT NULL,
    resource_name VARCHAR(255),
    resource_calendar VARCHAR(255),
    resource_description TEXT,
    resource_response_type INT,
    resource_type VARCHAR(255) NOT NULL,
    resource_members TEXT,
--
    PRIMARY KEY (resource_id)
);

CREATE INDEX kronolith_resources_type_idx ON kronolith_resources (resource_type);
CREATE INDEX kronolith_resources_calendar_idx ON kronolith_resources (resource_calendar);