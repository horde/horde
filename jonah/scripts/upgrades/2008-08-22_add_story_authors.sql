ALTER TABLE jonah_stories ADD COLUMN story_author VARCHAR(255);
UPDATE jonah_stories SET story_author='Anonymous';
ALTER TABLE jonah_stories CHANGE COLUMN story_author story_author VARCHAR(255) NOT NULL;
