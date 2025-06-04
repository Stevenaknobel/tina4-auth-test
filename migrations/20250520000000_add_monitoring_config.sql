CREATE TABLE IF NOT EXISTS monitoring_config (
    config_id INT PRIMARY KEY AUTO_INCREMENT,
    site_id INT NOT NULL,
    request_headers TEXT NULL,
    request_body TEXT NULL,
    expected_response TEXT NULL,
    expected_status_code INT NULL,
    FOREIGN KEY (site_id) REFERENCES monitored_sites(site_id) ON DELETE CASCADE
);

-- Add notifications_enabled column to companies table if it doesn't exist
ALTER TABLE companies
ADD COLUMN notifications_enabled TINYINT(1) DEFAULT 0;

-- Add slack_webhook_url column to companies table if it doesn't exist
ALTER TABLE companies
ADD COLUMN slack_webhook_url VARCHAR(255) NULL;

-- Add notification_email column to companies table if it doesn't exist
ALTER TABLE companies
ADD COLUMN notification_email VARCHAR(255) NULL;

-- Insert default monitoring types if they don't exist
INSERT INTO monitoring_types (type_name) 
SELECT 'Ping' 
WHERE NOT EXISTS (SELECT 1 FROM monitoring_types WHERE type_name = 'Ping');

INSERT INTO monitoring_types (type_name) 
SELECT 'API GET' 
WHERE NOT EXISTS (SELECT 1 FROM monitoring_types WHERE type_name = 'API GET');

INSERT INTO monitoring_types (type_name) 
SELECT 'API POST' 
WHERE NOT EXISTS (SELECT 1 FROM monitoring_types WHERE type_name = 'API POST');
