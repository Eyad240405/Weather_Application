<?php
// config.php - Database & App Configuration

define('DB_HOST', 'localhost');
define('DB_NAME', 'outfit_planner');
define('DB_USER', 'root');
define('DB_PASSWORD', '');

// Weather API Configuration
define('WEATHER_API_KEY', ''); // Set your OpenWeatherMap API key here

// Upload Configuration
define('UPLOAD_DIR', 'uploads/');
define('MAX_UPLOAD_SIZE', 8 * 1024 * 1024); // 8MB (matches HTML: "up to 8MB")
define('ALLOWED_FILE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);

// Clothing Categories (matches HTML form options)
define('CLOTHING_CATEGORIES', ['Tops', 'Bottoms', 'Dresses', 'Outerwear', 'Footwear', 'Accessories']);

// Clothing Seasons (matches HTML form options)
define('CLOTHING_SEASONS', ['Spring', 'Summer', 'Autumn', 'Winter', 'All Seasons']);

// Category → emoji mapping
define('CATEGORY_EMOJIS', [
    'Tops'        => '👕',
    'Bottoms'     => '👖',
    'Dresses'     => '👗',
    'Outerwear'   => '🧥',
    'Footwear'    => '👟',
    'Accessories' => '🎒',
]);

// Season → badge color classes (Tailwind, matches HTML)
define('SEASON_BADGE_CLASSES', [
    'Summer'      => 'text-emerald-700 bg-emerald-50',
    'Spring'      => 'text-yellow-700 bg-yellow-50',
    'Autumn'      => 'text-orange-700 bg-orange-50',
    'Winter'      => 'text-blue-700 bg-blue-50',
    'All Seasons' => 'text-purple-700 bg-purple-50',
]);

// Season → emoji (matches HTML labels)
define('SEASON_EMOJIS', [
    'Spring'      => '🌸',
    'Summer'      => '☀️',
    'Autumn'      => '🍂',
    'Winter'      => '❄️',
    'All Seasons' => '🌤',
]);

// Default weather seed data (matches HTML exactly)
define('DEFAULT_WEATHER', [
    'city'           => 'Cairo',
    'country'        => 'Egypt',
    'temperature'    => 28,
    'condition_text' => 'Sunny & Clear',
    'emoji'          => '☀️',
    'humidity'       => 42,
    'wind_speed'     => 14,
    'uv_index'       => 'High',
]);
?>
