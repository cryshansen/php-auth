-- =====================================================
-- PHP Auth Application Database Schema
-- =====================================================

-- Drop existing tables if they exist (for fresh setup)
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS user_email_verification;
DROP TABLE IF EXISTS user_login_attempts;
DROP TABLE IF EXISTS user_password_reset;
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
-- Email Verification Table
-- =====================================================
CREATE TABLE user_email_verification (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    email VARCHAR(255) NOT NULL,
    token VARCHAR(255) UNIQUE NOT NULL,
    expires_at DATETIME NOT NULL,
    used TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(50) NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_user_id (user_id),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =====================================================
-- User Login Attempts   Table
-- =====================================================
CREATE TABLE user_login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NULL,
    email VARCHAR(255) NOT NULL,
    attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- =====================================================
-- User Authentication Table
-- =====================================================
CREATE TABLE user_authentication (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email varchar(255) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    last_login DATETIME NULL,
    failed_attempts INT DEFAULT 0,
    lock_until DATETIME NULL,
    ip_address VARCHAR(45),
    user_id INT NOT NULL UNIQUE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_lock_until (lock_until)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;




-- =====================================================
-- Password Reset Tokens Table  
-- =====================================================
CREATE TABLE user_password_reset (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(255) UNIQUE NOT NULL,
    expires_at DATETIME NOT NULL,
    used TINYINT DEFAULT 0,
    ip_address varchar(255) NOT NULL,
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
    `event` VARCHAR(50) NOT NULL,
    ip_address VARCHAR(45),
    user_agent VARCHAR(500),
    metadata text  NULL,
    `timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    user_id INT NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_event_type (`event`),
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (`timestamp`)
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

INSERT INTO user_authentication ( password_hash,last_login, failed_attempts ,  lock_until , ip_address, user_id) VALUES
( '$2y$10$OL72FraYHJIRxklSmojFJOJBGGUXpVPbFcU7bDLQmUgxlPwDq7Fou', NULL, 0,NULL, '127.0.0.1',1);
( '$2y$10$V41QNyHBNgWBBFrFgqwS/OA9cFppomrTAtvf.tQlYrGGo4DkwRLfK', '2026-01-14 18:49:59', 0, '0000-00-00 00:00:00', '192.0.224.208', 2);


-- Test User1 Unverified  User 2: Verified

INSERT INTO `user_email_verification` ( `user_id`, `email`, `token`, `expires_at`, `used`, `created_at`, `ip_address`) VALUES
(1, 'unverified@example.com', 'cea9b1bf0a3d8f2c776ebeb4b7374fc8', '2026-01-15 19:26:15', 0, '2026-01-14 14:26:15', '');
( 2, 'verified@example.com', '5e79ca35a59679b959d70f243dc36754', '2025-08-06 18:30:35', 1, '2025-08-05 14:30:35', '192.0.224.208'),


-- =====================================================
-- Indexes for Performance
-- =====================================================
-- Additional indexes for common queries
ALTER TABLE users ADD FULLTEXT INDEX ft_name (firstname, lastname);
ALTER TABLE users ADD INDEX idx_created_at (created_at);
ALTER TABLE user_authentication ADD INDEX idx_created_at (created_at);
ALTER TABLE user_email_verification ADD INDEX idx_used (used);
ALTER TABLE user_password_reset ADD INDEX idx_used (used);
