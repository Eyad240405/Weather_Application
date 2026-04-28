'use strict';

const API = {

    uploadClothing: async (file) => {
        try {
            const fd = new FormData();
            fd.append('file', file);
            const res = await fetch('Upload.php', { method: 'POST', body: fd });
            if (!res.ok) throw new Error('Upload failed');
            return await res.json();
        } catch (err) {
            console.error('[API] uploadClothing:', err);
            return { success: false, message: 'Upload failed. Please try again.' };
        }
    },

    addClothing: async (name, category, season, imagePath = null, description = '') => {
        try {
            if (!name || !category || !season)
                return { success: false, message: 'Name, category and season are required.' };

            const fd = new FormData();
            fd.append('action',      'add');
            fd.append('name',        name);
            fd.append('category',    category);
            fd.append('season',      season);
            fd.append('description', description);
            if (imagePath) fd.append('image_path', imagePath);

            const res = await fetch('DB_Ops.php', { method: 'POST', body: fd });
            if (!res.ok) throw new Error('Request failed');
            return await res.json();
        } catch (err) {
            console.error('[API] addClothing:', err);
            return { success: false, message: 'Error adding item. Please try again.' };
        }
    },

    getClothing: async (filter = null) => {
        try {
            let url = 'DB_Ops.php?action=get';
            if (filter && filter !== 'all') url += '&filter=' + encodeURIComponent(filter);
            const res = await fetch(url);
            if (!res.ok) throw new Error('Request failed');
            return await res.json();
        } catch (err) {
            console.error('[API] getClothing:', err);
            return { success: false, message: 'Error loading items.', items: [] };
        }
    },

    getSuggestedOutfits: async () => {
        try {
            const res = await fetch('DB_Ops.php?action=getSuggested');
            if (!res.ok) throw new Error('Request failed');
            return await res.json();
        } catch (err) {
            console.error('[API] getSuggestedOutfits:', err);
            return { success: false, message: 'Error loading suggestions.', items: [] };
        }
    },

    updateClothing: async (id, name, category, season, description = '') => {
        try {
            if (!id || !name || !category || !season)
                return { success: false, message: 'ID, name, category and season are required.' };

            const fd = new FormData();
            fd.append('action',      'update');
            fd.append('id',          id);
            fd.append('name',        name);
            fd.append('category',    category);
            fd.append('season',      season);
            fd.append('description', description);

            const res = await fetch('DB_Ops.php', { method: 'POST', body: fd });
            if (!res.ok) throw new Error('Request failed');
            return await res.json();
        } catch (err) {
            console.error('[API] updateClothing:', err);
            return { success: false, message: 'Error updating item. Please try again.' };
        }
    },

    deleteClothing: async (id) => {
        try {
            if (!id) return { success: false, message: 'Invalid clothing ID.' };
            const fd = new FormData();
            fd.append('action', 'delete');
            fd.append('id',     id);
            const res = await fetch('DB_Ops.php', { method: 'POST', body: fd });
            if (!res.ok) throw new Error('Request failed');
            return await res.json();
        } catch (err) {
            console.error('[API] deleteClothing:', err);
            return { success: false, message: 'Error deleting item. Please try again.' };
        }
    },

    getWeather: async (city = 'Cairo') => {
        try {
            const res = await fetch('API_Ops.php?action=getWeather&city=' + encodeURIComponent(city));
            if (!res.ok) throw new Error('Request failed');
            return await res.json();
        } catch (err) {
            console.error('[API] getWeather:', err);
            return {
                success: false, fallback: true,
                data: { city: 'Cairo', country: 'Egypt', temperature: 28,
                        condition_text: 'Sunny & Clear', emoji: '☀️',
                        humidity: 42, wind_speed: 14, uv_index: 'High' },
            };
        }
    },

    suggestOutfit: async (weatherData, clothingItems) => {
        try {
            const res = await fetch('API_Ops.php?action=suggestOutfit', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify({ weather_data: weatherData, clothing: clothingItems }),
            });
            if (!res.ok) throw new Error('Request failed');
            return await res.json();
        } catch (err) {
            console.error('[API] suggestOutfit:', err);
            return { success: false, message: 'Error generating outfit suggestion.' };
        }
    },
};
