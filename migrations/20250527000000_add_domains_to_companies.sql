-- Add domains field to companies table
-- This field will store a comma-separated list of domains associated with this company (e.g., example.com,another-example.org)
ALTER TABLE companies ADD COLUMN domains TEXT;
