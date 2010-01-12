CREATE TABLE wicked_pages (
    page_id           NUMBER(16) NOT NULL,
    page_name         VARCHAR2(100) NOT NULL,
    page_text         CLOB,
    page_hits         NUMBER(16) DEFAULT 0,
    page_majorversion NUMBER(8) NOT NULL,
    page_minorversion NUMBER(16) NOT NULL,
    version_created   NUMBER(16) NOT NULL,
    change_author     VARCHAR2(255),
    change_log        CLOB,
--
    PRIMARY KEY (page_id),
    UNIQUE (page_name)
);

CREATE TABLE wicked_history (
    page_id           NUMBER(16) NOT NULL,
    page_name         VARCHAR2(100) NOT NULL,
    page_text         CLOB,
    page_majorversion NUMBER(8) NOT NULL,
    page_minorversion NUMBER(16) NOT NULL,
    version_created   NUMBER(16) NOT NULL,
    change_author     VARCHAR2(255),
    change_log        CLOB,
--
    PRIMARY KEY (page_id, page_majorversion, page_minorversion)
);

CREATE INDEX wicked_history_name_idx ON wicked_history (page_name);
CREATE INDEX wicked_history_version_idx ON wicked_history (page_majorversion, page_minorversion);

CREATE TABLE wicked_attachments (
    page_id                 NUMBER(16) NOT NULL,
    attachment_name         VARCHAR2(100) NOT NULL,
    attachment_hits         NUMBER(16) DEFAULT 0,
    attachment_majorversion NUMBER(8) NOT NULL,
    attachment_minorversion NUMBER(16) NOT NULL,
    attachment_created      NUMBER(16) NOT NULL,
    change_author           VARCHAR2(255),
    change_log              CLOB,
--
    PRIMARY KEY (page_id, attachment_name)
);

CREATE TABLE wicked_attachment_history (
    page_id                 NUMBER(16) NOT NULL,
    attachment_name         VARCHAR2(100) NOT NULL,
    attachment_majorversion NUMBER(8) NOT NULL,
    attachment_minorversion NUMBER(16) NOT NULL,
    attachment_created      NUMBER(16) NOT NULL,
    change_author           VARCHAR2(255),
    change_log              CLOB,
--
    PRIMARY KEY (page_id, attachment_name, attachment_majorversion,
                 attachment_minorversion)
);

CREATE INDEX wicked_attach_hist_name_idx ON wicked_attachment_history (page_id, attachment_name);
CREATE INDEX wicked_attach_hist_version_idx ON wicked_attachment_history (attachment_majorversion, attachment_minorversion);
