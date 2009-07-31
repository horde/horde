CREATE TABLE ansel_images_geolocation (
    image_id INT NOT NULL,
    image_latitude varchar(32),
    image_longitude varchar(32),

    PRIMARY KEY (image_id)
);
