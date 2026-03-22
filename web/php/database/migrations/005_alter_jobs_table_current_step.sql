ALTER TABLE jobs
    ADD COLUMN current_step VARCHAR(255) NULL AFTER status,
    ADD COLUMN steps_json JSON NULL AFTER current_step;