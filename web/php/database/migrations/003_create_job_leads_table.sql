-- THOMASONS V3: Migration 003
-- Target: job_leads table for Agent persistence

CREATE TABLE IF NOT EXISTS job_leads (
    id          INT PRIMARY KEY,
    title       VARCHAR(255) NOT NULL,
    company     VARCHAR(255),
    location    VARCHAR(100),
    salary      VARCHAR(100),
    source_url  VARCHAR(500),
    posted_at   DATE,
    keywords    JSON,
    created_at  DATETIME,
    updated_at  DATETIME
);

-- Note: Unique constraints and Auto-increment logic are handled 
-- by the App\Services\DbJson service in Dev mode.