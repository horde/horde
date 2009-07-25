ALTER TABLE nag_tasks ADD task_start INT;
CREATE INDEX nag_start_idx ON nag_tasks (task_start);
