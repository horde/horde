-- SQL script for setting the release date to the story date if it was "1".

UPDATE jonah_stories SET story_release = story_date WHERE story_release = 1;
