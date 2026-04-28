<?php
error_reporting(0);
ini_set('display_errors', 0);

define('DB_HOST',     '');
define('DB_NAME',     '');
define('DB_USER',     '');
define('DB_PASSWORD', '');

define('WEATHER_API_KEY', '');



define('UPLOAD_DIR',       'uploads/');
define('MAX_UPLOAD_SIZE',  8 * 1024 * 1024); // 8 MB
define('ALLOWED_MIME',     ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);

define('CLOTHING_CATEGORIES', ['Tops', 'Bottoms', 'Dresses', 'Outerwear', 'Footwear', 'Accessories']);
define('CLOTHING_SEASONS',    ['Spring', 'Summer', 'Autumn', 'Winter', 'All Seasons']);

define('CATEGORY_EMOJIS', [
    'Tops'        => '👕',
    'Bottoms'     => '👖',
    'Dresses'     => '👗',
    'Outerwear'   => '🧥',
    'Footwear'    => '👟',
    'Accessories' => '🎒',
]);

define('CATEGORY_GRADIENTS', [
    'Tops'        => ['from-sky-100',    'to-blue-200'],
    'Bottoms'     => ['from-amber-100',  'to-orange-200'],
    'Dresses'     => ['from-violet-100', 'to-purple-200'],
    'Outerwear'   => ['from-slate-100',  'to-zinc-200'],
    'Footwear'    => ['from-rose-100',   'to-pink-200'],
    'Accessories' => ['from-teal-100',   'to-cyan-200'],
]);

define('SEASON_BADGE_CLASSES', [
    'Summer'      => 'text-emerald-700 bg-emerald-50',
    'Spring'      => 'text-yellow-700  bg-yellow-50',
    'Autumn'      => 'text-orange-700  bg-orange-50',
    'Winter'      => 'text-blue-700    bg-blue-50',
    'All Seasons' => 'text-purple-700  bg-purple-50',
]);

define('SEASON_EMOJIS', [
    'Spring'      => '🌸',
    'Summer'      => '☀️',
    'Autumn'      => '🍂',
    'Winter'      => '❄️',
    'All Seasons' => '🌤',
]);

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
