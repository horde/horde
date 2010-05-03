--
-- $Horde: jonah/scripts/upgrades/2008-08-22_add_story_authors.sql,v 1.1 2008/08/23 02:48:34 bklang Exp $
--
ALTER TABLE jonah_stories ADD COLUMN story_author VARCHAR(255);
UPDATE jonah_stories SET story_author='Anonymous';
ALTER TABLE jonah_stories CHANGE COLUMN story_author story_author VARCHAR(255) NOT NULL;
