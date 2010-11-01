ALTER TABLE jonah_channels ADD COLUMN `channel_slug` VARCHAR(64);
UPDATE jonah_channels SET channel_slug=channel_id;
ALTER TABLE jonah_channels CHANGE channel_slug channel_slug VARCHAR(64) NOT NULL;
