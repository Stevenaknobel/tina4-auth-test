ALTER TABLE users
ADD COLUMN role VARCHAR(50) DEFAULT 'user';

-- Insert default roles
INSERT INTO users (username, password, company_id, role, created_at)
SELECT 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 'global_admin', NOW()
WHERE NOT EXISTS (SELECT 1 FROM users WHERE role = 'global_admin');

-- Update existing users with company_id = 1 to be company_admin
UPDATE users SET role = 'company_admin' WHERE company_id = 1 AND role = 'user';