--
-- $Horde: agora/scripts/upgrades/2008-10-26_add_distribution_address.sql,v 1.2 2009/10/20 21:28:31 jan Exp $
-- 
ALTER TABLE agora_forums ADD COLUMN forum_distribution_address VARCHAR(255) DEFAULT '' NOT NULL
