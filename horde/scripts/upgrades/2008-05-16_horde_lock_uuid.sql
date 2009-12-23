-- Allow lock IDs to be UUIDs:
ALTER TABLE horde_locks CHANGE lock_id lock_id VARCHAR(36);
