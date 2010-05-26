
CREATE TABLE hermes_timeslices (
    timeslice_id           INT NOT NULL,
    clientjob_id           VARCHAR(255) NOT NULL,
    employee_id            VARCHAR(255) NOT NULL,
    jobtype_id             INT NOT NULL,
    timeslice_hours        NUMERIC(10, 2) NOT NULL,
    timeslice_rate         NUMERIC(10, 2),
    timeslice_isbillable   SMALLINT DEFAULT 0 NOT NULL,
    timeslice_date         INT NOT NULL,
    timeslice_description  VARCHAR(MAX) NOT NULL,
    timeslice_note         VARCHAR(MAX),
    timeslice_submitted    SMALLINT DEFAULT 0 NOT NULL,
    timeslice_exported     SMALLINT DEFAULT 0 NOT NULL,
    costobject_id          VARCHAR(255),
--
    PRIMARY KEY (timeslice_id)
);

CREATE TABLE hermes_jobtypes (
    jobtype_id          INT NOT NULL,
    jobtype_name        VARCHAR(255),
    jobtype_enabled     SMALLINT DEFAULT 1 NOT NULL,
    jobtype_rate        NUMERIC(10, 2),
    jobtype_billable    SMALLINT DEFAULT 0 NOT NULL,
--
    PRIMARY KEY (jobtype_id)
);

CREATE TABLE hermes_clientjobs (
    clientjob_id                VARCHAR(255) NOT NULL,
    clientjob_enterdescription  SMALLINT DEFAULT 1 NOT NULL,
    clientjob_exportid          VARCHAR(255),
--
    PRIMARY KEY (clientjob_id)
);

CREATE TABLE hermes_deliverables (
    deliverable_id          INT NOT NULL,
    client_id               VARCHAR(250) NOT NULL,
    deliverable_name        VARCHAR(250) NOT NULL,
    deliverable_parent      INT,
    deliverable_estimate    NUMERIC(10, 2),
    deliverable_active      SMALLINT DEFAULT 1 NOT NULL,
    deliverable_description VARCHAR(MAX),
--
    PRIMARY KEY (deliverable_id)
);

CREATE INDEX hermes_deliverables_client ON hermes_deliverables (client_id, deliverable_name);
