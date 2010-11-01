-- SQL script for adding a column for the story release date.

ALTER TABLE jonah_stories ADD COLUMN story_release INT;
UPDATE jonah_stories SET story_release = 1;