-- Script to add due date to whups_tickets table
--
-- $Horde: whups/scripts/upgrades/2006-07-12_add_due_date.sql,v 1.1 2006/07/19 02:54:31 chuck Exp $
--

ALTER TABLE whups_tickets ADD ticket_due INT;
