-- SQL script for adapting to updated story date handling.

ALTER TABLE jonah_stories ADD COLUMN story_published INT;
ALTER TABLE jonah_stories ADD COLUMN story_updated INT NOT NULL;
UPDATE jonah_stories SET story_published = story_release;
UPDATE jonah_stories SET story_updated = story_date;
ALTER TABLE jonah_stories DROP COLUMN story_release;
ALTER TABLE jonah_stories DROP COLUMN story_date;
