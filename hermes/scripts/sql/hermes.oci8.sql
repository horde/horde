
CREATE TABLE hermes_timeslices (
    timeslice_id           NUMBER(16) NOT NULL,
    clientjob_id           VARCHAR2(255) NOT NULL,
    employee_id            VARCHAR2(255) NOT NULL,
    jobtype_id             NUMBER(16) NOT NULL,
    timeslice_hours        NUMBER(10, 2) NOT NULL,
    timeslice_rate         NUMBER(10, 2),
    timeslice_isbillable   NUMBER(1) DEFAULT 0 NOT NULL,
    timeslice_date         NUMBER(16) NOT NULL,
    timeslice_description  CLOB NOT NULL,
    timeslice_note         CLOB,
    timeslice_submitted    NUMBER(1) DEFAULT 0 NOT NULL,
    timeslice_exported     NUMBER(1) DEFAULT 0 NOT NULL,
    costobject_id          VARCHAR2(255),
--
    PRIMARY KEY (timeslice_id)
);

CREATE TABLE hermes_jobtypes (
    jobtype_id          NUMBER(16) NOT NULL,
    jobtype_name        VARCHAR2(255),
    jobtype_enabled     NUMBER(1) DEFAULT 1 NOT NULL,
    jobtype_rate        NUMBER(10, 2),
    jobtype_billable    NUMBER(1) DEFAULT 0 NOT NULL,
--
    PRIMARY KEY (jobtype_id)
);

CREATE TABLE hermes_clientjobs (
    clientjob_id                VARCHAR2(255) NOT NULL,
    clientjob_enterdescription  NUMBER(1) DEFAULT 1 NOT NULL,
    clientjob_exportid          VARCHAR2(255),
--
    PRIMARY KEY (clientjob_id)
);

CREATE TABLE hermes_deliverables (
    deliverable_id          NUMBER(16) NOT NULL,
    client_id               VARCHAR2(250) NOT NULL,
    deliverable_name        VARCHAR2(250) NOT NULL,
    deliverable_parent      NUMBER(16),
    deliverable_estimate    NUMBER(10, 2),
    deliverable_active      NUMBER(1) DEFAULT 1 NOT NULL,
    deliverable_description CLOB,
--
    PRIMARY KEY (deliverable_id)
);

CREATE INDEX hermes_deliverables_client ON hermes_deliverables (client_id, deliverable_name);
