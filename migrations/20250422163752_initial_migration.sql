CREATE TABLE companies (
company_id INT PRIMARY KEY AUTO_INCREMENT,
company_name VARCHAR(255) NOT NULL,
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE users (
user_id INT PRIMARY KEY AUTO_INCREMENT,
company_id INT,
username VARCHAR(255) UNIQUE NOT NULL,
password VARCHAR(255) NOT NULL,
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY (company_id) REFERENCES companies(company_id)
);
CREATE TABLE tokens (
token_id INT PRIMARY KEY AUTO_INCREMENT,
user_id INT,
token VARCHAR(255) NOT NULL,
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY (user_id) REFERENCES users(user_id)
);
CREATE TABLE monitoring_types (
type_id INT PRIMARY KEY AUTO_INCREMENT,
type_name VARCHAR(50) NOT NULL
);
CREATE TABLE monitored_sites (
site_id INT PRIMARY KEY AUTO_INCREMENT,
company_id INT,
type_id INT,
url VARCHAR(255) NOT NULL,
site_name VARCHAR(255),
status VARCHAR(50) DEFAULT 'pending',
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY (company_id) REFERENCES companies(company_id),
FOREIGN KEY (type_id) REFERENCES monitoring_types(type_id)
);
CREATE TABLE monitoring (
monitoring_id INT PRIMARY KEY AUTO_INCREMENT,
site_id INT,
type_id INT,
status VARCHAR(50),
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY (site_id) REFERENCES monitored_sites(site_id),
FOREIGN KEY (type_id) REFERENCES monitoring_types(type_id)
);
CREATE TABLE monitoring_history (
    history_id INT PRIMARY KEY AUTO_INCREMENT,
    site_id INT,
    type_id INT,
    status VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);