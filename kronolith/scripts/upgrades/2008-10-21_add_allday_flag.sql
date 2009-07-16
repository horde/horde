ALTER TABLE kronolith_events ADD event_allday INT DEFAULT 0;

UPDATE kronolith_events SET event_allday = 1 WHERE event_start + INTERVAL 1 DAY = event_end;
