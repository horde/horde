-- $Horde: kronolith/scripts/upgrades/2008-10-21_add_allday_flag.sql,v 1.1 2008/10/22 21:14:08 jan Exp $

ALTER TABLE kronolith_events ADD event_allday INT DEFAULT 0;

UPDATE kronolith_events SET event_allday = 1 WHERE event_start + INTERVAL 1 DAY = event_end;
