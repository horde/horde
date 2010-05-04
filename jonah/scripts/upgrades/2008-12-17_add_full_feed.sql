ALTER TABLE jonah_channels ADD COLUMN `channel_full_feed` SMALLINT;
UPDATE jonah_channels SET channel_full_feed = 0;
ALTER TABLE jonah_channels CHANGE channel_full_feed channel_full_feed SMALLINT DEFAULT 0 NOT NULL;
