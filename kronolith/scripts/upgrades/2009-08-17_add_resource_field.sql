ALTER TABLE kronolith_events ADD event_resources TEXT;

CREATE TABLE kronolith_resources (
    resource_uid INT NOT NULL,
    resource_name VARCHAR(255),
    resource_calendar VARCHAR(255),
    resource_category VARCHAR(255),
    
    PRIMARY KEY (resource_uid)
);
