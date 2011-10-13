-- $Horde: sesha/scripts/upgrades/2008-07-24_add_category_priority.sql,v 1.1 2008/07/24 20:30:02 chuck Exp $
ALTER TABLE sesha_categories ADD COLUMN priority SMALLINT UNSIGNED DEFAULT 0 NOT NULL;
