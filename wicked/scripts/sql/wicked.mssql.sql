CREATE TABLE wicked_pages (
    page_id           INT NOT NULL,
    page_name         VARCHAR(100) NOT NULL,
    page_text         VARCHAR(MAX),
    page_hits         INT DEFAULT 0,
    page_majorversion SMALLINT NOT NULL,
    page_minorversion INT NOT NULL,
    version_created   INT NOT NULL,
    change_author     VARCHAR(255),
    change_log        VARCHAR(MAX),
--
    PRIMARY KEY (page_id),
    UNIQUE (page_name)
);

CREATE TABLE wicked_history (
    page_id           INT NOT NULL,
    page_name         VARCHAR(100) NOT NULL,
    page_text         VARCHAR(MAX),
    page_majorversion SMALLINT NOT NULL,
    page_minorversion INT NOT NULL,
    version_created   INT NOT NULL,
    change_author     VARCHAR(255),
    change_log        VARCHAR(MAX),
--
    PRIMARY KEY (page_id, page_majorversion, page_minorversion)
);

CREATE INDEX wicked_history_name_idx ON wicked_history (page_name);
CREATE INDEX wicked_history_version_idx ON wicked_history (page_majorversion, page_minorversion);

CREATE TABLE wicked_attachments (
    page_id                 INT NOT NULL,
    attachment_name         VARCHAR(100) NOT NULL,
    attachment_hits         INT DEFAULT 0,
    attachment_majorversion SMALLINT NOT NULL,
    attachment_minorversion INT NOT NULL,
    attachment_created      INT NOT NULL,
    change_author           VARCHAR(255),
    change_log              VARCHAR(MAX),
--
    PRIMARY KEY (page_id, attachment_name)
);

CREATE TABLE wicked_attachment_history (
    page_id                 INT NOT NULL,
    attachment_name         VARCHAR(100) NOT NULL,
    attachment_majorversion SMALLINT NOT NULL,
    attachment_minorversion INT NOT NULL,
    attachment_created      INT NOT NULL,
    change_author           VARCHAR(255),
    change_log              VARCHAR(MAX),
--
    PRIMARY KEY (page_id, attachment_name, attachment_majorversion,
                 attachment_minorversion)
);

CREATE INDEX wicked_attachment_history_name_idx ON wicked_attachment_history (page_id, attachment_name);
CREATE INDEX wicked_attachment_history_version_idx ON wicked_attachment_history (attachment_majorversion, attachment_minorversion);
