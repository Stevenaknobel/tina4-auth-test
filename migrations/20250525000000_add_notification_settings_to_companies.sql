ALTER TABLE companies
ADD COLUMN slack_webhook_url VARCHAR(255) NULL,
ADD COLUMN notification_email VARCHAR(255) NULL,
ADD COLUMN notifications_enabled TINYINT(1) DEFAULT 0;

-- Create a new table for monitoring type-specific configuration
CREATE TABLE monitoring_config (
    config_id INT PRIMARY KEY AUTO_INCREMENT,
    site_id INT NOT NULL,
    request_headers TEXT NULL,
    request_body TEXT NULL,
    expected_response TEXT NULL,
    expected_status_code INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (site_id) REFERENCES monitored_sites(site_id) ON DELETE CASCADE
);