--
-- $Horde: jonah/scripts/upgrades/2008-08-22_add_channel_slugs.sql,v 1.1 2008/08/23 02:02:37 bklang Exp $
--
ALTER TABLE jonah_channels ADD COLUMN `channel_slug` VARCHAR(64);
UPDATE jonah_channels SET channel_slug=channel_id;
ALTER TABLE jonah_channels CHANGE channel_slug channel_slug VARCHAR(64) NOT NULL;
