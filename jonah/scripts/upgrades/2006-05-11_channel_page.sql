-- SQL script for adding a column for the channel story pages.

ALTER TABLE jonah_channels ADD COLUMN channel_page_link VARCHAR(255);
