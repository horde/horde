ALTER TABLE kronolith_events ADD event_resources TEXT;

CREATE TABLE kronolith_resources (
    resource_id INT NOT NULL,
    resource_name VARCHAR(255),
    resource_calendar VARCHAR(255),
    resource_description TEXT,
    resource_category VARCHAR(255) DEFAULT '',
    resource_response_type INT DEFAULT 0,
    
    PRIMARY KEY (resource_id)
);
