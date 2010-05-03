--
-- $Horde: jonah/scripts/upgrades/2008-12-17_add_full_feed.sql,v 1.2 2009/10/20 21:28:30 jan Exp $
--
ALTER TABLE jonah_channels ADD COLUMN `channel_full_feed` SMALLINT;
UPDATE jonah_channels SET channel_full_feed = 0;
ALTER TABLE jonah_channels CHANGE channel_full_feed channel_full_feed SMALLINT DEFAULT 0 NOT NULL;
