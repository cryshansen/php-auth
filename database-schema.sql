-- =====================================================
-- PHP Auth Application Database Schema
-- =====================================================

-- Drop existing tables if they exist (for fresh setup)
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS email_verification;
DROP TABLE IF EXISTS password_reset_tokens;
DROP TABLE IF EXISTS user_authentication;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS auth_telemetry;
SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- Users Table
-- =====================================================
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    firstname VARCHAR(100) NOT NULL,
    lastname VARCHAR(100) NOT NULL,
    is_verified TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_verified (is_verified)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- User Authentication Table
-- =====================================================
CREATE TABLE user_authentication (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    failed_attempts INT DEFAULT 0,
    lock_until DATETIME NULL,
    ip_address VARCHAR(45),
    last_login DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_lock_until (lock_until)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Email Verification Table
-- =====================================================
CREATE TABLE email_verification (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    email VARCHAR(255) NOT NULL,
    token VARCHAR(255) UNIQUE NOT NULL,
    expires_at DATETIME NOT NULL,
    used TINYINT DEFAULT 0,
    used_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_user_id (user_id),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Password Reset Tokens Table
-- =====================================================
CREATE TABLE password_reset_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    email VARCHAR(255) NOT NULL,
    token VARCHAR(255) UNIQUE NOT NULL,
    expires_at DATETIME NOT NULL,
    used TINYINT DEFAULT 0,
    used_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_user_id (user_id),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Auth Telemetry Table
-- =====================================================
CREATE TABLE auth_telemetry (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    event_type VARCHAR(50) NOT NULL,
    event_description VARCHAR(255),
    ip_address VARCHAR(45),
    user_agent VARCHAR(500),
    success TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_event_type (event_type),
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Sample Data (for testing)
-- =====================================================

-- Test User 1: Unverified
INSERT INTO users (email, firstname, lastname, is_verified) VALUES
('unverified@example.com', 'John', 'Doe', 0);

INSERT INTO user_authentication (user_id, password_hash, failed_attempts, ip_address) VALUES
(1, '$2y$10$YourHashedPasswordHere', 0, '127.0.0.1');

-- Test User 2: Verified
INSERT INTO users (email, firstname, lastname, is_verified) VALUES
('verified@example.com', 'Jane', 'Smith', 1);

INSERT INTO user_authentication (user_id, password_hash, failed_attempts, ip_address) VALUES
(2, '$2y$10$AnotherHashedPasswordHere', 0, '127.0.0.1');

-- =====================================================
-- Indexes for Performance
-- =====================================================
-- Additional indexes for common queries
ALTER TABLE users ADD FULLTEXT INDEX ft_name (firstname, lastname);
ALTER TABLE users ADD INDEX idx_created_at (created_at);
ALTER TABLE user_authentication ADD INDEX idx_created_at (created_at);
ALTER TABLE email_verification ADD INDEX idx_used (used);
ALTER TABLE password_reset_tokens ADD INDEX idx_used (used);
