ALTER TABLE ansel_images ADD COLUMN image_latitude VARCHAR(32) DEFAULT '' NOT NULL;
ALTER TABLE ansel_images ADD COLUMN image_longitude VARCHAR(32) DEFAULT '' NOT NULL;
ALTER TABLE ansel_images ADD COLUMN image_location VARCHAR(255) DEFAULT '' NOT NULL;
