// API_Ops.js - AJAX calls to backend APIs

const API = {

    /**
     * Upload clothing image
     */
    uploadClothing: async (file) => {
        try {
            const formData = new FormData();
            formData.append('file', file);

            const response = await fetch('Upload.php', { method: 'POST', body: formData });
            if (!response.ok) throw new Error('Upload failed');
            return await response.json();
        } catch (error) {
            console.error('[v0] Upload error:', error);
            return { success: false, message: 'Upload failed. Please try again.' };
        }
    },

    /**
     * Add clothing to database
     * @param {string} name         - Item name (e.g. "White Oxford Shirt")
     * @param {string} category     - One of CLOTHING_CATEGORIES (e.g. "Tops")
     * @param {string} season       - One of CLOTHING_SEASONS (e.g. "Summer")
     * @param {string|null} imagePath
     * @param {string} description
     */
    addClothing: async (name, category, season, imagePath = null, description = '') => {
        try {
            if (!name || !category || !season) {
                return { success: false, message: 'Name, category and season are required' };
            }

            const formData = new FormData();
            formData.append('action',      'add');
            formData.append('name',        name);
            formData.append('category',    category);
            formData.append('season',      season);
            formData.append('description', description);
            if (imagePath) formData.append('image_path', imagePath);

            const response = await fetch('DB_Ops.php', { method: 'POST', body: formData });
            if (!response.ok) throw new Error('Request failed');
            return await response.json();
        } catch (error) {
            console.error('[v0] Add clothing error:', error);
            return { success: false, message: 'Error adding clothing. Please try again.' };
        }
    },

    /**
     * Get all clothing items, optionally filtered by season
     * @param {string|null} filter - season name or null/'' for all
     */
    getClothing: async (filter = null) => {
        try {
            let url = 'DB_Ops.php?action=get';
            if (filter && filter !== 'all') url += '&filter=' + encodeURIComponent(filter);

            const response = await fetch(url);
            if (!response.ok) throw new Error('Request failed');
            return await response.json();
        } catch (error) {
            console.error('[v0] Get clothing error:', error);
            return { success: false, message: 'Error loading clothing items', items: [] };
        }
    },

    /**
     * Get only items marked as suggested outfits (is_suggested = 1)
     */
    getSuggestedOutfits: async () => {
        try {
            const response = await fetch('DB_Ops.php?action=getSuggested');
            if (!response.ok) throw new Error('Request failed');
            return await response.json();
        } catch (error) {
            console.error('[v0] Get suggested error:', error);
            return { success: false, message: 'Error loading suggestions', items: [] };
        }
    },

    /**
     * Update clothing item
     */
    updateClothing: async (id, name, category, season, description = '') => {
        try {
            if (!id || !name || !category || !season) {
                return { success: false, message: 'ID, name, category and season are required' };
            }

            const formData = new FormData();
            formData.append('action',      'update');
            formData.append('id',          id);
            formData.append('name',        name);
            formData.append('category',    category);
            formData.append('season',      season);
            formData.append('description', description);

            const response = await fetch('DB_Ops.php', { method: 'POST', body: formData });
            if (!response.ok) throw new Error('Request failed');
            return await response.json();
        } catch (error) {
            console.error('[v0] Update clothing error:', error);
            return { success: false, message: 'Error updating clothing. Please try again.' };
        }
    },

    /**
     * Delete clothing item
     */
    deleteClothing: async (id) => {
        try {
            if (!id) return { success: false, message: 'Invalid clothing ID' };

            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id',     id);

            const response = await fetch('DB_Ops.php', { method: 'POST', body: formData });
            if (!response.ok) throw new Error('Request failed');
            return await response.json();
        } catch (error) {
            console.error('[v0] Delete clothing error:', error);
            return { success: false, message: 'Error deleting clothing. Please try again.' };
        }
    },

    /**
     * Get weather data.
     * If a city is supplied, tries live API; otherwise returns DB / fallback data.
     * Fallback always matches the HTML: Cairo, 28°, Sunny & Clear, 42%, 14 km/h, UV High.
     */
    getWeather: async (city = 'Cairo') => {
        try {
            const url = 'API_Ops.php?action=getWeather&city=' + encodeURIComponent(city);
            const response = await fetch(url);
            if (!response.ok) throw new Error('Request failed');
            return await response.json();
        } catch (error) {
            console.error('[v0] Weather API error:', error);
            return {
                success:  false,
                message:  'Error fetching weather',
                fallback: true,
                data: {
                    city:           'Cairo',
                    country:        'Egypt',
                    temperature:    28,
                    condition_text: 'Sunny & Clear',
                    emoji:          '☀️',
                    humidity:       42,
                    wind_speed:     14,
                    uv_index:       'High',
                }
            };
        }
    },

    /**
     * Get outfit suggestion based on current weather + wardrobe
     */
    suggestOutfit: async (weatherData, clothingItems) => {
        try {
            const response = await fetch('API_Ops.php?action=suggestOutfit', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify({ weather_data: weatherData, clothing: clothingItems }),
            });
            if (!response.ok) throw new Error('Request failed');
            return await response.json();
        } catch (error) {
            console.error('[v0] Outfit suggestion error:', error);
            return { success: false, message: 'Error generating outfit suggestion' };
        }
    }
};
