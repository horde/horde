-- SQL script for adding a column for the stories' permalinks.

ALTER TABLE jonah_stories ADD COLUMN story_permalink VARCHAR(255);
