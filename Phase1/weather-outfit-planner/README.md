# Weather & Outfit Planner - Complete SPA

A Single Page Application (SPA) built with **PHP**, **MySQL**, and **AJAX (Fetch API)** that helps users plan their outfits based on current weather conditions.

## 🎯 Features

✅ **No Page Reloads** - Pure AJAX-based interactions using Fetch API
✅ **Upload Clothing Items** - Add images with summer/winter tags
✅ **Real-time Weather** - Fetch weather data using OpenWeatherMap API
✅ **Outfit Suggestions** - Get clothing recommendations based on weather
✅ **CRUD Operations** - Add, view, edit, delete clothing items
✅ **Server-side Validation** - All inputs validated on backend
✅ **Responsive Design** - Mobile-friendly layout
✅ **Secure** - Uses prepared statements (PDO), no SQL injection

## 📁 File Structure

```
weather-outfit-planner/
├── index.php              # Main SPA layout
├── header.php             # Header component
├── footer.php             # Footer component
├── config.php             # Configuration file
├── DB_Ops.php             # Database operations with validation
├── Upload.php             # File upload handling
├── API_Ops.php            # Third-party API calls (Weather)
├── API_Ops.js             # AJAX requests to backend
├── app.js                 # Frontend logic & event handling
├── styles.css             # Responsive styling
├── database_setup.sql     # Database schema
├── uploads/               # Directory for clothing images
└── README.md              # This file
```

## 🚀 Setup Instructions

### 1. Database Setup

Create a new MySQL database:

```sql
CREATE DATABASE outfit_planner;
```

Run the database setup script:

```bash
mysql -u root -p outfit_planner < database_setup.sql
```

Or manually run the SQL queries in `database_setup.sql`.

### 2. Configuration

Edit `config.php` and update database credentials:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'outfit_planner');
define('DB_USER', 'root');
define('DB_PASSWORD', 'your_password');
```

### 3. Weather API Key

Get a free API key from [OpenWeatherMap](https://openweathermap.org/api):

Update `API_Ops.php`:

```php
private $api_key = 'YOUR_API_KEY_HERE';
```

Or set as environment variable:

```bash
export WEATHER_API_KEY='YOUR_API_KEY_HERE'
```

### 4. Create Uploads Directory

```bash
mkdir uploads
chmod 755 uploads
```

### 5. Run Local Server

```bash
php -S localhost:8000
```

Visit `http://localhost:8000` in your browser.

## 🔧 Technical Details

### Database Schema

**clothing table:**
- `id` - Primary key
- `type` - ENUM (summer/winter)
- `image_path` - Path to uploaded image
- `description` - Item description
- `created_at` - Creation timestamp
- `updated_at` - Last update timestamp

### API Endpoints

#### DB_Ops.php

**GET: /DB_Ops.php?action=get[&type=summer]**
- Get all clothing items or filter by type
- Returns: `{ success: boolean, items: array }`

**POST: /DB_Ops.php** (FormData)
- `action=add` - Add new clothing item
- `action=update` - Update existing item
- `action=delete` - Delete item

#### Upload.php

**POST: /Upload.php** (FormData with file)
- Upload clothing image
- Returns: `{ success: boolean, path: string, message: string }`
- Max file size: 5MB
- Allowed types: JPEG, PNG, GIF, WebP

#### API_Ops.php

**GET: /API_Ops.php?action=getWeather&city=CityName**
- Get current weather data using OpenWeatherMap API
- Returns: `{ success: boolean, data: object }`

**POST: /API_Ops.php?action=suggestOutfit** (JSON)
- Get outfit suggestion based on weather
- Body: `{ weather_data: object, clothing: array }`
- Returns: `{ success: boolean, recommendation: object }`

### Security Features

✅ **Prepared Statements** - Prevents SQL injection
✅ **File Upload Validation** - Type and size checks
✅ **Input Sanitization** - HTML escaping on frontend
✅ **API Key Protection** - Not exposed in JavaScript
✅ **CORS Prevention** - Server-side API calls only

### Client-side Validation

- Required field checks before submission
- File type and size validation
- Input length validation
- HTML escaping to prevent XSS

### Server-side Validation

- PDO prepared statements for all queries
- Input type validation
- File upload security checks
- API response validation

## 📱 Frontend Architecture

### AJAX Module (`API_Ops.js`)

Centralized API communication layer:

```javascript
API.uploadClothing(file, type, description)
API.addClothing(type, imagePath, description)
API.getClothing(type)
API.updateClothing(id, type, description)
API.deleteClothing(id)
API.getWeather(city)
API.suggestOutfit(weatherData, clothingItems)
```

### Event Management (`app.js`)

- Form submission handlers
- Filter/search functionality
- Modal dialogs for editing
- Real-time UI updates
- Error handling with user-friendly messages

## 🎨 Responsive Design

- **Mobile** (< 480px) - Single column layout
- **Tablet** (480px - 768px) - Two column layout
- **Desktop** (> 768px) - Multi-column responsive grid

## 🐛 Troubleshooting

**Database connection fails:**
- Verify MySQL is running
- Check credentials in `config.php`
- Ensure database exists

**Weather API not working:**
- Verify API key is set in `API_Ops.php`
- Check API key is active on OpenWeatherMap
- Verify internet connection

**File upload fails:**
- Ensure `uploads/` directory exists
- Check directory permissions (755)
- Verify file size is under 5MB
- Confirm file type is supported

**AJAX requests fail:**
- Open browser console (F12)
- Check Network tab for requests
- Verify PHP file exists at the requested path
- Check server error logs

## 📝 Example Workflow

1. **User visits app** → Weather loads automatically
2. **User uploads clothing image** → File validated, uploaded, saved to database
3. **User filters by type** → AJAX fetches matching items without page reload
4. **User clicks "Get Outfit Suggestion"** → Backend analyzes weather and suggests items
5. **User edits/deletes items** → Updates via modal without page reload

## 🔐 Environment Variables

```bash
WEATHER_API_KEY=your_api_key_here
DB_HOST=localhost
DB_NAME=outfit_planner
DB_USER=root
DB_PASSWORD=password
```

## 💡 Future Enhancements

- User authentication & profiles
- Save favorite outfits
- Weather forecast suggestions
- Social sharing
- Mobile app
- Advanced filtering (color, brand, etc.)

## 📄 License

This project is provided as-is for educational purposes.

## 👨‍💻 Created with

- PHP 7.4+
- MySQL 5.7+
- Vanilla JavaScript (ES6+)
- Responsive CSS3
- Fetch API (AJAX)
