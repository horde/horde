-- $Horde: kronolith/scripts/upgrades/2006-03-16_add_private_flag.sql,v 1.2 2006/03/18 06:02:46 selsky Exp $

ALTER TABLE kronolith_events ADD event_private INT DEFAULT 0 NOT NULL;
