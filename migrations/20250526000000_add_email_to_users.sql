-- Add email field to users table
ALTER TABLE users ADD COLUMN email VARCHAR(255);

-- Create index on email field
CREATE INDEX idx_users_email ON users(email);

-- Add unique constraint to email field
ALTER TABLE users ADD CONSTRAINT uq_users_email UNIQUE (email);