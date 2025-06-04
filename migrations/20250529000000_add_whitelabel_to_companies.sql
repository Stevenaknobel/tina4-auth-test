-- Add whitelabel fields to companies table
ALTER TABLE companies ADD COLUMN logo_url VARCHAR(255);
ALTER TABLE companies ADD COLUMN primary_color VARCHAR(20);
ALTER TABLE companies ADD COLUMN secondary_color VARCHAR(20);
ALTER TABLE companies ADD COLUMN custom_css TEXT;