CREATE TABLE hylax_faxes (
    fax_id              INT NOT NULL DEFAULT 0,
    job_id              INT DEFAULT NULL,
    fax_type            SMALLINT(1) NOT NULL,
    fax_user            VARCHAR(255) NOT NULL DEFAULT '',
    fax_number          VARCHAR(255) NOT NULL DEFAULT '',
    fax_pages           INT NOT NULL DEFAULT 0,
    fax_created         INT NOT NULL DEFAULT 0,
    fax_status          VARCHAR(255) NOT NULL DEFAULT '',
    fax_folder          VARCHAR(255) NOT NULL DEFAULT '',

    PRIMARY KEY       (fax_id)
);

CREATE INDEX hylax_faxes_fax_id_idx ON hylax_faxes (fax_id);


CREATE TABLE hylax_fax_attributes (
    fax_id              VARCHAR(255) NOT NULL default '',
    attribute_name      VARCHAR(255) NOT NULL default '',
    attribute_key       VARCHAR(255) NOT NULL default '',
    attribute_value     VARCHAR(MAX)
);

CREATE INDEX hylax_attribute_idx ON hylax_fax_attributes (fax_id);
CREATE INDEX hylax_attribute_name_idx ON hylax_fax_attributes (attribute_name);
CREATE INDEX hylax_attribute_key_idx ON hylax_fax_attributes (attribute_key);
