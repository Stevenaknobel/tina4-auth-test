-- Add tags field to monitored_sites table
-- This field will store a comma-separated list of tags for filtering and grouping
ALTER TABLE monitored_sites ADD COLUMN tags varchar(255);

-- Create index on tags field for faster searching
CREATE INDEX idx_monitored_sites_tags ON monitored_sites(tags);