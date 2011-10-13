-- $Horde: sesha/scripts/sql/sesha.mssql.sql,v 1.6 2008/07/24 20:30:02 chuck Exp $

CREATE TABLE sesha_inventory (
    stock_id INT UNSIGNED NOT NULL,
    stock_name VARCHAR(255) DEFAULT '',
    note VARCHAR(MAX),
    PRIMARY KEY (stock_id)
);

CREATE TABLE sesha_categories (
    category_id INT UNSIGNED NOT NULL,
    category VARCHAR(255) DEFAULT '',
    description VARCHAR(MAX),
    priority SMALLINT UNSIGNED DEFAULT 0 NOT NULL,
    PRIMARY KEY (category_id)
);

CREATE TABLE sesha_properties (
    property_id INT UNSIGNED NOT NULL,
    property VARCHAR(255),
    datatype VARCHAR(128) DEFAULT 'text' NOT NULL,
    parameters VARCHAR(MAX),
    unit VARCHAR(32),
    description VARCHAR(MAX),
    priority SMALLINT UNSIGNED DEFAULT 0 NOT NULL,
    PRIMARY KEY (property_id)
);

CREATE TABLE sesha_relations (
    category_id INT UNSIGNED NOT NULL,
    property_id INT UNSIGNED NOT NULL
);

CREATE TABLE sesha_inventory_categories (
    stock_id INT UNSIGNED,
    category_id INT UNSIGNED,
);

CREATE TABLE sesha_inventory_properties (
    attribute_id INT UNSIGNED NOT NULL,
    property_id INT UNSIGNED,
    stock_id INT UNSIGNED,
    int_datavalue INT,
    txt_datavalue VARCHAR(MAX),
    PRIMARY KEY (attribute_id)
);
