ALTER TABLE ansel_images ADD COLUMN image_latitude VARCHAR(32) NOT NULL DEFAULT '';
ALTER TABLE ansel_images ADD COLUMN image_longitude VARCHAR(32) NOT NULL DEFAULT '';
ALTER TABLE ansel_images ADD COLUMN image_location VARCHAR(255) NOT NULL DEFAULT '';
