# OutfitPlanner — Weather-Aware Wardrobe SPA

A Single Page Application built with **PHP 8+**, **MySQL**, and vanilla **JavaScript (Fetch API / AJAX)**.

---

## ✅ Bug Fix: "could not find driver" / PDO MySQL Error

**Error:** `Fatal error: Uncaught RuntimeException: Database connection failed: could not find driver`

**Cause:** The `php_pdo_mysql` extension is disabled in your PHP installation.

### Fix on Windows (XAMPP / WAMP / Laragon)

1. Find your `php.ini` — usually at `C:\xampp\php\php.ini` or `C:\wamp64\bin\php\php8.x\php.ini`
2. Open it in a text editor
3. Search for `pdo_mysql` and find this line:
   `;extension=pdo_mysql`
4. **Remove the semicolon** so it reads:
   `extension=pdo_mysql`
5. Save and **restart Apache** in XAMPP/WAMP Control Panel
6. Verify: visit phpinfo.php and search for "pdo_mysql"

### Fix on Linux (Ubuntu/Debian)
```bash
sudo apt install php-mysql
sudo systemctl restart apache2
```

### Fix on macOS (Homebrew)
```bash
brew install php   # pdo_mysql is bundled; restart your server
```

---

## File Structure

```
outfit-planner/
├── index.php            # Main SPA — HTML layout + PHP data bootstrap
├── config.php           # All constants (DB, API key, categories, seasons)
├── DB_Ops.php           # Database class (CRUD) + HTTP handler
├── API_Ops.php          # OpenWeatherMap integration + outfit suggestion logic
├── Upload.php           # Image upload handler
├── API_Ops.js           # Frontend AJAX wrapper (all fetch calls)
├── app.js               # Frontend logic, DOM, event handling
├── database_setup.sql   # Schema + seed data (run once)
└── uploads/             # Created automatically on first upload
```

---

## Quick Start

### 1. Enable PDO MySQL (see fix above first!)

### 2. Set up the Database
```bash
mysql -u root -p < database_setup.sql
```

### 3. Configure `config.php`
```php
define('DB_HOST',         'localhost');
define('DB_NAME',         'outfit_planner');
define('DB_USER',         'root');
define('DB_PASSWORD',     '');       // leave '' if no password set
define('WEATHER_API_KEY', '');       // optional — free key at openweathermap.org
```

### 4. Run
```bash
php -S localhost:8000
```
Open `http://localhost:8000` in your browser.

---

## Features

- Real-time weather via OpenWeatherMap (graceful fallback to Cairo defaults if no key)
- City search via header input — press Enter to update weather
- Smart outfit suggestions that update automatically based on temperature (≥25°C → Summer, 15–24°C → Spring/Autumn, <15°C → Winter)
- Add clothing items with optional photo upload (PNG/JPG/GIF/WEBP, max 8 MB)
- Full CRUD (add / edit / delete) with no page reloads
- Season filter pills on the Full Wardrobe section
- Animated cards, skeleton loaders, toast notifications
- Server-side validation with PDO prepared statements

---

## API Endpoints

| Endpoint | Method | Purpose |
|---|---|---|
| `DB_Ops.php?action=get[&filter=Season]` | GET | Fetch all/filtered clothing |
| `DB_Ops.php` (action=add/update/delete) | POST | CRUD operations |
| `DB_Ops.php?action=getSuggested` | GET | Fetch suggested outfits |
| `API_Ops.php?action=getWeather&city=X` | GET | Live weather + auto-updates suggestions |
| `API_Ops.php?action=suggestOutfit` | POST (JSON) | Outfit recommendation logic |
| `Upload.php` | POST (FormData) | Image upload |

---

## What Was Fixed / Completed

| # | Issue | Fix Applied |
|---|---|---|
| 1 | **"could not find driver" crash** | Added PDO driver check with clear error message pointing to exact php.ini fix |
| 2 | **Suggested Outfits grid always empty** | Added `updateSuggestedOutfits()` to DB_Ops.php — called after each weather fetch to keep DB flags in sync |
| 3 | **City search didn't refresh suggested grid** | app.js `loadWeather()` now re-fetches suggested items from DB when server signals an update |
