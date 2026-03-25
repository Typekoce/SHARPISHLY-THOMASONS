-- THOMASONS V3: Migration 002
-- Target: add vector status to csv
ALTER TABLE csv_contents 
ADD COLUMN vector_id VARCHAR(255) NULL AFTER status,
ADD COLUMN embedded_at TIMESTAMP NULL;
