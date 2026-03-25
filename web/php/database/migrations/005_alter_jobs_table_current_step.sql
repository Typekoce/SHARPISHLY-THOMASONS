-- THOMASONS V3: Migration 005
-- Target: alter jobs table current
ALTER TABLE jobs
    ADD COLUMN current_step VARCHAR(255) NULL AFTER status,
    ADD COLUMN steps_json JSON NULL AFTER current_step;