-- Database Setup Script
-- Run this SQL to set up the database

CREATE DATABASE IF NOT EXISTS outfit_planner;
USE outfit_planner;

-- Weather Data Table (stores current/default weather)
CREATE TABLE IF NOT EXISTS weather (
    id INT AUTO_INCREMENT PRIMARY KEY,
    city VARCHAR(100) NOT NULL DEFAULT 'Cairo',
    country VARCHAR(100) NOT NULL DEFAULT 'Egypt',
    temperature INT NOT NULL DEFAULT 28,
    condition_text VARCHAR(100) NOT NULL DEFAULT 'Sunny & Clear',
    emoji VARCHAR(10) NOT NULL DEFAULT '☀️',
    humidity INT NOT NULL DEFAULT 42,
    wind_speed INT NOT NULL DEFAULT 14,
    uv_index VARCHAR(20) NOT NULL DEFAULT 'High',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Clothing Items Table
CREATE TABLE IF NOT EXISTS clothing (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    category ENUM('Tops','Bottoms','Dresses','Outerwear','Footwear','Accessories') NOT NULL,
    season ENUM('Spring','Summer','Autumn','Winter','All Seasons') NOT NULL,
    emoji VARCHAR(10) NOT NULL DEFAULT '👕',
    gradient_from VARCHAR(50) NOT NULL DEFAULT 'from-sky-100',
    gradient_to VARCHAR(50) NOT NULL DEFAULT 'to-blue-200',
    image_path VARCHAR(255) DEFAULT NULL,
    description VARCHAR(255) DEFAULT NULL,
    is_suggested TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_season (season),
    INDEX idx_is_suggested (is_suggested),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed Weather (matches HTML exactly)
INSERT INTO weather (city, country, temperature, condition_text, emoji, humidity, wind_speed, uv_index)
VALUES ('Cairo', 'Egypt', 28, 'Sunny & Clear', '☀️', 42, 14, 'High');

-- Seed Clothing Items (matches HTML exactly)
-- Suggested Outfit cards (shown in the "Suggested Outfits" section)
INSERT INTO clothing (name, category, season, emoji, gradient_from, gradient_to, is_suggested) VALUES
('White Oxford Shirt',  'Tops',    'Summer', '👕', 'from-sky-100',   'to-blue-200',   1),
('Slim Chino Trousers', 'Bottoms', 'Spring', '👖', 'from-amber-100', 'to-orange-200', 1);

-- Full Wardrobe cards (shown in the "Full Wardrobe" section)
INSERT INTO clothing (name, category, season, emoji, gradient_from, gradient_to, is_suggested) VALUES
('Floral Midi Dress', 'Dresses',   'Summer',      '👗', 'from-violet-100', 'to-purple-200', 0),
('Wool Overcoat',     'Outerwear', 'Winter',      '🧥', 'from-slate-100',  'to-zinc-200',   0),
('Canvas Sneakers',   'Footwear',  'All Seasons', '👟', 'from-rose-100',   'to-pink-200',   0);
