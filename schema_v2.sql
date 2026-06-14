-- Adloaf Database Schema v2
-- Run this in phpMyAdmin AFTER running schema.sql and admin_upgrades.sql
-- This adds new tables for the major overhaul

-- Public User Accounts
CREATE TABLE IF NOT EXISTS users_public (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    whatsapp VARCHAR(30) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    profile_photo VARCHAR(255) DEFAULT NULL,
    country VARCHAR(100) DEFAULT NULL,
    state VARCHAR(100) DEFAULT NULL,
    city VARCHAR(100) DEFAULT NULL,
    about_business TEXT DEFAULT NULL,
    email_verified TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Password Reset Tokens
CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(150) NOT NULL,
    token VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    used TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Bake Requests (Project Requests from Public Users)
CREATE TABLE IF NOT EXISTS bake_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    service_type VARCHAR(100) NOT NULL,
    content_language VARCHAR(50) DEFAULT 'english',
    deadline DATE NOT NULL,
    project_description TEXT NOT NULL,
    ai_generated_desc TEXT DEFAULT NULL,
    estimated_price_inr DECIMAL(10,2) DEFAULT 0.00,
    market_price_inr DECIMAL(10,2) DEFAULT 0.00,
    currency_code VARCHAR(10) DEFAULT 'INR',
    status ENUM('Pending','Accepted','Rejected','Approved','Completed') DEFAULT 'Pending',
    admin_notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users_public(id) ON DELETE CASCADE
);

-- Login Rate Limiting
CREATE TABLE IF NOT EXISTS login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(50) NOT NULL,
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Visitor Tracking
CREATE TABLE IF NOT EXISTS visitors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(50) NOT NULL,
    country VARCHAR(100) DEFAULT NULL,
    country_code VARCHAR(10) DEFAULT NULL,
    city VARCHAR(100) DEFAULT NULL,
    timezone VARCHAR(100) DEFAULT NULL,
    isp VARCHAR(200) DEFAULT NULL,
    user_agent TEXT DEFAULT NULL,
    page_visited VARCHAR(255) DEFAULT '/',
    referrer VARCHAR(500) DEFAULT NULL,
    visited_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Popular Clients (Homepage Marquee)
CREATE TABLE IF NOT EXISTS popular_clients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_name VARCHAR(100) NOT NULL,
    logo_path VARCHAR(255) NOT NULL,
    website_url VARCHAR(255) DEFAULT NULL,
    sort_order INT DEFAULT 0
);

-- Site Statistics (Admin configurable counters)
CREATE TABLE IF NOT EXISTS site_stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    stat_key VARCHAR(50) UNIQUE NOT NULL,
    stat_value INT DEFAULT 0
);

-- Insert default site stats
INSERT IGNORE INTO site_stats (stat_key, stat_value) VALUES
('total_clients', 0),
('completed_projects', 0),
('active_years', 1);

-- Global Currency Setting
INSERT IGNORE INTO site_settings (setting_key, setting_value) VALUES
('base_currency', 'INR'),
('base_currency_symbol', '₹'),
('whatsapp_admin', '916282563209'),
('gemini_api_key', 'YOUR_GEMINI_API_KEY');

-- Add pricing columns to services table
ALTER TABLE services 
ADD COLUMN IF NOT EXISTS price_from_inr DECIMAL(10,2) DEFAULT 0.00,
ADD COLUMN IF NOT EXISTS market_price_inr DECIMAL(10,2) DEFAULT 0.00,
ADD COLUMN IF NOT EXISTS price_note VARCHAR(255) DEFAULT 'Starting price';
