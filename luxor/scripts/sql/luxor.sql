-- $Horde: luxor/scripts/sql/luxor.sql,v 1.4 2006/03/21 01:57:37 selsky Exp $

CREATE TABLE luxor_declarations (
    declid SMALLINT NOT NULL,
    langid SMALLINT DEFAULT 0 NOT NULL,
    declaration char(255) DEFAULT '' NOT NULL,
    PRIMARY KEY (declid)
);

CREATE TABLE luxor_files (
    fileid INT NOT NULL,
    source VARCHAR(255) DEFAULT '' NOT NULL,
    filename VARCHAR(255) DEFAULT '' NOT NULL,
    tag VARCHAR(255) DEFAULT '' NOT NULL,
    lastmodified INT DEFAULT 0 NOT NULL,
    PRIMARY KEY (fileid)
);

CREATE INDEX luxor_files_source ON luxor_files (source);
CREATE INDEX luxor_files_tag ON luxor_files (tag);
CREATE INDEX luxor_files_filename ON luxor_files (filename);

CREATE TABLE luxor_indexes (
    symid INT DEFAULT 0 NOT NULL,
    fileid INT DEFAULT 0 NOT NULL,
    line INT DEFAULT 0 NOT NULL,
    declid SMALLINT DEFAULT 0 NOT NULL
);

CREATE INDEX luxor_indexes_symid ON luxor_indexes (symid);
CREATE INDEX luxor_indexes_fileid ON luxor_indexes (fileid);
CREATE INDEX luxor_indexes_declid ON luxor_indexes (declid);

CREATE TABLE luxor_status (
    fileid INT DEFAULT 0 NOT NULL,
    status SMALLINT DEFAULT 0 NOT NULL,
    PRIMARY KEY (fileid)
);

CREATE TABLE luxor_symbols (
    symid INT NOT NULL,
    symname VARCHAR(255) DEFAULT '' NOT NULL,
    source VARCHAR(255) DEFAULT '' NOT NULL,
    PRIMARY KEY (symid)
);

CREATE INDEX luxor_symbols_symname ON luxor_symbols (symname);
CREATE INDEX luxor_symbols_source ON luxor_symbols (source);

CREATE TABLE luxor_usage (
    symid INT DEFAULT 0 NOT NULL,
    fileid INT DEFAULT 0 NOT NULL,
    line INT DEFAULT 0 NOT NULL
);

CREATE INDEX luxor_usage_symid ON luxor_usage (symid);
CREATE INDEX luxor_usage_fileid ON luxor_usage (fileid);
