-- $Horde: ansel/scripts/upgrades/2008-12-5_add_geolocation_tables.sql,v 1.1 2008/12/05 19:42:23 mrubinsk Exp $

CREATE TABLE ansel_images_geolocation (
    image_id INT NOT NULL,
    image_latitude varchar(32),
    image_longitude varchar(32),

    PRIMARY KEY (image_id)
);
