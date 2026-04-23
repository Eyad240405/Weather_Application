// app.js - Frontend Logic
// Drives the HTML UI produced by index.php / outfit-planner.html

'use strict';

// ── State ─────────────────────────────────────────────────────────────────────
let currentWeatherData = null;
let currentFilter      = 'all';
let editingId          = null;

// Season badge Tailwind classes (must match config.php SEASON_BADGE_CLASSES)
const SEASON_BADGE = {
    'Summer':      'text-emerald-700 bg-emerald-50',
    'Spring':      'text-yellow-700  bg-yellow-50',
    'Autumn':      'text-orange-700  bg-orange-50',
    'Winter':      'text-blue-700    bg-blue-50',
    'All Seasons': 'text-purple-700  bg-purple-50',
};

const SEASON_EMOJI = {
    'Summer':      '☀️',
    'Spring':      '🌸',
    'Autumn':      '🍂',
    'Winter':      '❄️',
    'All Seasons': '🌤',
};

// ── DOM refs (populated after DOMContentLoaded) ───────────────────────────────
let DOM = {};

// ── Init ──────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    console.log('[v0] App initialized');

    DOM = {
        // Weather
        weatherEmoji:     document.getElementById('weatherEmoji'),
        weatherTemp:      document.getElementById('weatherTemp'),
        weatherCondition: document.getElementById('weatherCondition'),
        weatherCity:      document.getElementById('weatherCity'),
        weatherHumidity:  document.getElementById('weatherHumidity'),
        weatherWind:      document.getElementById('weatherWind'),
        weatherUV:        document.getElementById('weatherUV'),
        citySearchInput:  document.getElementById('citySearchInput'),

        // Add Item form
        itemNameInput:    document.getElementById('itemNameInput'),
        categorySelect:   document.getElementById('categorySelect'),
        seasonSelect:     document.getElementById('seasonSelect'),
        photoInput:       document.getElementById('photoInput'),
        photoDropZone:    document.getElementById('photoDropZone'),
        photoLabel:       document.getElementById('photoLabel'),
        addItemBtn:       document.getElementById('addItemBtn'),

        // Suggested outfits grid
        suggestedGrid:    document.getElementById('suggestedGrid'),
        suggestedCount:   document.getElementById('suggestedCount'),

        // Full wardrobe grid
        wardrobeGrid:     document.getElementById('wardrobeGrid'),

        // Filter buttons
        filterBtns:       document.querySelectorAll('.filter-btn'),

        // Edit modal
        editModal:        document.getElementById('editModal'),
        editNameInput:    document.getElementById('editNameInput'),
        editCategory:     document.getElementById('editCategorySelect'),
        editSeason:       document.getElementById('editSeasonSelect'),
        editSaveBtn:      document.getElementById('editSaveBtn'),
        editCancelBtn:    document.getElementById('editCancelBtn'),
    };

    bindEvents();

    loadWeather().then(() => {
        loadWardrobe().then(() => {
            updateSuggestedByWeather();
        });
    });
});

// ── Event Binding ─────────────────────────────────────────────────────────────
function bindEvents() {
    // City search
    if (DOM.citySearchInput) {
        DOM.citySearchInput.addEventListener('keydown', async (e) => {
            if (e.key === 'Enter') {
                const city = DOM.citySearchInput.value.trim();

                if (!city) {
                    showToast('Please enter a city name.', false);
                    return;
                }

                await loadWeather(city);
                await updateSuggestedByWeather();
            }
        });
    }

    // Photo drop zone file change
    if (DOM.photoInput) {
        DOM.photoInput.addEventListener('change', () => {
            const file = DOM.photoInput.files[0];
            if (file && DOM.photoLabel) {
                DOM.photoLabel.textContent = file.name;
            } else if (DOM.photoLabel) {
                DOM.photoLabel.textContent = 'Click to upload';
            }
        });
    }

    // Add Item button
    if (DOM.addItemBtn) {
        DOM.addItemBtn.addEventListener('click', handleAddItem);
    }

    // Filter buttons
    DOM.filterBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            DOM.filterBtns.forEach(b => b.classList.remove('bg-indigo-600', 'text-white', 'shadow-md', 'shadow-indigo-200'));
            DOM.filterBtns.forEach(b => {
                b.classList.add('bg-white', 'text-gray-500', 'border', 'border-gray-200');
            });

            btn.classList.remove('bg-white', 'text-gray-500', 'border', 'border-gray-200');
            btn.classList.add('bg-indigo-600', 'text-white', 'shadow-md', 'shadow-indigo-200');

            currentFilter = btn.dataset.filter || 'all';
            loadWardrobe(currentFilter === 'all' ? null : currentFilter);
        });
    });

    // Edit modal cancel / backdrop
    if (DOM.editCancelBtn) {
        DOM.editCancelBtn.addEventListener('click', closeEditModal);
    }

    if (DOM.editModal) {
        DOM.editModal.addEventListener('click', (e) => {
            if (e.target === DOM.editModal) {
                closeEditModal();
            }
        });
    }

    if (DOM.editSaveBtn) {
        DOM.editSaveBtn.addEventListener('click', handleEditSave);
    }
}

// ── Weather ───────────────────────────────────────────────────────────────────
async function loadWeather(city = 'Cairo') {
    console.log('[v0] Loading weather for', city);
    const result = await API.getWeather(city);

    if (result.success || result.fallback) {
        currentWeatherData = result.data;
        renderWeather(result.data);
    } else {
        showToast(result.message || 'Failed to load weather.', false);
    }
}

function renderWeather(d) {
    if (DOM.weatherEmoji)     DOM.weatherEmoji.textContent     = d.emoji || '☀️';
    if (DOM.weatherTemp)      DOM.weatherTemp.textContent      = d.temperature + '°';
    if (DOM.weatherCondition) DOM.weatherCondition.textContent = d.condition_text || 'Sunny & Clear';
    if (DOM.weatherCity)      DOM.weatherCity.textContent      = d.city + (d.country ? ', ' + d.country : '');
    if (DOM.weatherHumidity)  DOM.weatherHumidity.textContent  = d.humidity + '%';
    if (DOM.weatherWind)      DOM.weatherWind.textContent      = d.wind_speed + ' km/h';
    if (DOM.weatherUV)        DOM.weatherUV.textContent        = d.uv_index || 'High';
}

// الجديد: تحديث الاقتراحات حسب الطقس الحالي
async function updateSuggestedByWeather() {
    if (!currentWeatherData) return;

    const wardrobeResult = await API.getClothing();
    if (!wardrobeResult.success) {
        renderSuggested([]);
        showToast('Could not load wardrobe items.', false);
        return;
    }

    const allItems = wardrobeResult.items || [];

    const suggestResult = await API.suggestOutfit(currentWeatherData, allItems);
    if (!suggestResult.success) {
        renderSuggested([]);
        showToast('Could not generate outfit suggestions.', false);
        return;
    }

    const suggestedSeasons = suggestResult.recommendation?.suggested_seasons || [];

    const filteredItems = allItems.filter(item =>
        suggestedSeasons.includes(item.season)
    );

    renderSuggested(filteredItems);

    if (suggestResult.recommendation?.message) {
        showToast(suggestResult.recommendation.message, true);
    }
}

// ── Suggested Outfits ─────────────────────────────────────────────────────────
async function loadSuggestedOutfits() {
    const result = await API.getSuggestedOutfits();
    const grid   = DOM.suggestedGrid;
    if (!grid) return;

    const items = result.success ? result.items : [];

    if (DOM.suggestedCount) {
        DOM.suggestedCount.textContent = items.length + ' item' + (items.length !== 1 ? 's' : '');
    }

    const slots = 4;
    let html = '';

    items.slice(0, slots).forEach(item => {
        html += buildSuggestedCard(item);
    });

    for (let i = items.length; i < slots; i++) {
        html += buildSkeletonCard();
    }

    grid.innerHTML = html;
    attachCardActions(grid, true);
}

// الجديد: رسم الاقتراحات الديناميكية
function renderSuggested(items) {
    const grid = DOM.suggestedGrid;
    if (!grid) return;

    if (DOM.suggestedCount) {
        DOM.suggestedCount.textContent = items.length + ' item' + (items.length !== 1 ? 's' : '');
    }

    const slots = 4;
    let html = '';

    items.slice(0, slots).forEach(item => {
        html += buildSuggestedCard(item);
    });

    for (let i = items.length; i < slots; i++) {
        html += buildSkeletonCard();
    }

    grid.innerHTML = html;
    attachCardActions(grid, true);
}

function buildSuggestedCard(item) {
    const badgeClass  = SEASON_BADGE[item.season] || 'text-gray-700 bg-gray-50';
    const seasonEmoji = SEASON_EMOJI[item.season] || '';
    const imgContent  = item.image_path
        ? `<img src="${escapeHtml(item.image_path)}" alt="${escapeHtml(item.name)}" class="w-full h-full object-cover"/>`
        : `<div class="h-36 bg-gradient-to-br ${escapeHtml(item.gradient_from)} ${escapeHtml(item.gradient_to)} flex items-center justify-center text-5xl select-none">${escapeHtml(item.emoji)}</div>`;

    return `
<div class="outfit-card bg-gray-50 rounded-2xl overflow-hidden ring-1 ring-slate-100 cursor-pointer relative" data-id="${item.id}">
  <div class="relative overflow-hidden">
    ${imgContent}
    <div class="card-overlay absolute inset-0 bg-indigo-600/80 flex items-center justify-center gap-3">
      <button class="card-edit w-8 h-8 rounded-full bg-white/20 hover:bg-white/30 flex items-center justify-center text-white transition-colors">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
      </button>
      <button class="card-delete w-8 h-8 rounded-full bg-red-500/80 hover:bg-red-500 flex items-center justify-center text-white transition-colors">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
      </button>
    </div>
  </div>
  <div class="p-3.5">
    <p class="font-bold text-gray-800 text-sm leading-tight">${escapeHtml(item.name)}</p>
    <p class="text-xs text-gray-400 mt-0.5">${escapeHtml(item.category)}</p>
    <span class="mt-2 inline-block text-xs font-semibold ${badgeClass} px-2.5 py-0.5 rounded-full">${seasonEmoji} ${escapeHtml(item.season)}</span>
  </div>
</div>`;
}

function buildSkeletonCard() {
    return `
<div class="bg-gray-50 rounded-2xl overflow-hidden ring-1 ring-slate-100">
  <div class="skeleton h-36"></div>
  <div class="p-3.5 space-y-2">
    <div class="skeleton h-3.5 w-3/4 rounded"></div>
    <div class="skeleton h-3 w-1/2 rounded"></div>
    <div class="skeleton h-5 w-16 rounded-full mt-1"></div>
  </div>
</div>`;
}

// ── Full Wardrobe ─────────────────────────────────────────────────────────────
async function loadWardrobe(season = null) {
    const result = await API.getClothing(season);
    const grid   = DOM.wardrobeGrid;
    if (!grid) return;

    const items = result.success ? result.items : [];

    if (items.length === 0) {
        grid.innerHTML = buildEmptyStateCard();
        return;
    }

    let html = items.map(item => buildWardrobeCard(item)).join('');
    html += buildEmptyStatePlaceholderCard();
    grid.innerHTML = html;

    attachCardActions(grid, false);
}

function buildWardrobeCard(item) {
    const badgeClass  = SEASON_BADGE[item.season] || 'text-gray-700 bg-gray-50';
    const seasonEmoji = SEASON_EMOJI[item.season] || '';
    const imgContent  = item.image_path
        ? `<img src="${escapeHtml(item.image_path)}" alt="${escapeHtml(item.name)}" class="w-full h-full object-cover"/>`
        : `<div class="h-44 bg-gradient-to-br ${escapeHtml(item.gradient_from)} ${escapeHtml(item.gradient_to)} flex items-center justify-center text-6xl select-none">${escapeHtml(item.emoji)}</div>`;

    return `
<div class="outfit-card bg-white rounded-2xl overflow-hidden shadow-md shadow-slate-200/60 ring-1 ring-slate-100 cursor-pointer relative" data-id="${item.id}">
  <div class="relative overflow-hidden">
    ${imgContent}
    <div class="card-overlay absolute inset-0 bg-indigo-600/80 flex items-center justify-center gap-3">
      <button class="card-edit w-9 h-9 rounded-full bg-white/20 hover:bg-white/30 flex items-center justify-center text-white transition-colors">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
      </button>
      <button class="card-delete w-9 h-9 rounded-full bg-red-500/80 hover:bg-red-500 flex items-center justify-center text-white transition-colors">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
      </button>
    </div>
  </div>
  <div class="p-4">
    <p class="font-bold text-gray-800 text-sm">${escapeHtml(item.name)}</p>
    <p class="text-xs text-gray-400 mt-0.5 mb-2.5">${escapeHtml(item.category)}</p>
    <span class="text-xs font-semibold ${badgeClass} px-2.5 py-1 rounded-full">${seasonEmoji} ${escapeHtml(item.season)}</span>
  </div>
</div>`;
}

function buildEmptyStatePlaceholderCard() {
    return `
<div class="outfit-card bg-white rounded-2xl overflow-hidden shadow-md shadow-slate-200/60 ring-1 ring-slate-100 flex flex-col items-center justify-center p-8 text-center min-h-48">
  <div class="w-16 h-16 rounded-2xl bg-indigo-50 flex items-center justify-center mb-4 text-3xl">✦</div>
  <p class="font-bold text-gray-700 text-sm">No more outfits</p>
  <p class="text-xs text-gray-400 mt-1 leading-relaxed">Start adding items<br/>from the form above</p>
</div>`;
}

function buildEmptyStateCard() {
    return `
<div class="col-span-full flex flex-col items-center justify-center py-20 text-center">
  <div class="w-24 h-24 rounded-3xl bg-indigo-50 flex items-center justify-center text-5xl mb-5">👕</div>
  <p class="font-bold text-gray-700 text-lg">No items yet</p>
  <p class="text-sm text-gray-400 mt-2">Start by adding your clothes using the form on the left.</p>
</div>`;
}

// ── Card action delegation ────────────────────────────────────────────────────
function attachCardActions(grid, isSuggested) {
    grid.addEventListener('click', async (e) => {
        const editBtn   = e.target.closest('.card-edit');
        const deleteBtn = e.target.closest('.card-delete');
        const card      = e.target.closest('[data-id]');
        if (!card) return;

        const id = parseInt(card.dataset.id);

        if (editBtn) {
            openEditModal(id);
        } else if (deleteBtn) {
            await handleDelete(id);
        }
    }, { once: false });
}

// ── Add Item ──────────────────────────────────────────────────────────────────
async function handleAddItem() {
    const name        = DOM.itemNameInput?.value.trim() || '';
    const category    = DOM.categorySelect?.value || '';
    const season      = DOM.seasonSelect?.value || '';
    const file        = DOM.photoInput?.files[0] || null;

    if (!name) {
        showToast('Please enter an item name.', false);
        return;
    }
    if (!category) {
        showToast('Please select a category.', false);
        return;
    }
    if (!season) {
        showToast('Please select a season.', false);
        return;
    }

    let imagePath = null;

    if (file) {
        if (file.size > 8 * 1024 * 1024) {
            showToast('File too large. Maximum 8MB.', false);
            return;
        }

        if (!['image/jpeg', 'image/png', 'image/gif', 'image/webp'].includes(file.type)) {
            showToast('Invalid file type. Only PNG, JPG, WEBP allowed.', false);
            return;
        }

        const uploadResult = await API.uploadClothing(file);
        if (!uploadResult.success) {
            showToast(uploadResult.message, false);
            return;
        }

        imagePath = uploadResult.path;
    }

    const result = await API.addClothing(name, category, season, imagePath);

    if (result.success) {
        showToast('Item added to wardrobe! ✦', true);

        if (DOM.itemNameInput)  DOM.itemNameInput.value = '';
        if (DOM.categorySelect) DOM.categorySelect.value = '';
        if (DOM.seasonSelect)   DOM.seasonSelect.value = '';
        if (DOM.photoInput)     DOM.photoInput.value = '';
        if (DOM.photoLabel)     DOM.photoLabel.textContent = 'Click to upload';

        await loadWardrobe(currentFilter === 'all' ? null : currentFilter);
        await updateSuggestedByWeather();
    } else {
        showToast(result.message, false);
    }
}

// ── Delete ────────────────────────────────────────────────────────────────────
async function handleDelete(id) {
    if (!confirm('Delete this item?')) return;

    const result = await API.deleteClothing(id);

    if (result.success) {
        showToast('Item removed.', true);
        await loadWardrobe(currentFilter === 'all' ? null : currentFilter);
        await updateSuggestedByWeather();
    } else {
        showToast(result.message, false);
    }
}

// ── Edit Modal ────────────────────────────────────────────────────────────────
async function openEditModal(id) {
    const result = await API.getClothing();
    const item   = result.items?.find(i => i.id == id);
    if (!item) return;

    editingId = id;

    if (DOM.editNameInput) DOM.editNameInput.value = item.name;
    if (DOM.editCategory)  DOM.editCategory.value  = item.category;
    if (DOM.editSeason)    DOM.editSeason.value    = item.season;

    DOM.editModal.classList.remove('hidden');
    DOM.editModal.style.display = 'flex';
}

function closeEditModal() {
    editingId = null;
    if (DOM.editModal) {
        DOM.editModal.classList.add('hidden');
        DOM.editModal.style.display = 'none';
    }
}

async function handleEditSave() {
    if (!editingId) return;

    const name     = DOM.editNameInput?.value.trim() || '';
    const category = DOM.editCategory?.value || '';
    const season   = DOM.editSeason?.value || '';

    if (!name) {
        showToast('Please enter an item name.', false);
        return;
    }
    if (!category) {
        showToast('Please select a category.', false);
        return;
    }
    if (!season) {
        showToast('Please select a season.', false);
        return;
    }

    const result = await API.updateClothing(editingId, name, category, season);

    if (result.success) {
        showToast('Item updated.', true);
        closeEditModal();
        await loadWardrobe(currentFilter === 'all' ? null : currentFilter);
        await updateSuggestedByWeather();
    } else {
        showToast(result.message, false);
    }
}

// ── Toast ─────────────────────────────────────────────────────────────────────
function showToast(message, success = true) {
    const toast = document.createElement('div');
    toast.textContent = message;
    toast.className = `
        fixed bottom-5 right-5 z-[9999]
        px-4 py-3 rounded-xl shadow-lg text-white text-sm font-medium
        ${success ? 'bg-emerald-500' : 'bg-rose-500'}
        opacity-0 translate-y-3 transition-all duration-300
    `;

    document.body.appendChild(toast);

    requestAnimationFrame(() => {
        toast.classList.remove('opacity-0', 'translate-y-3');
    });

    setTimeout(() => {
        toast.classList.add('opacity-0', 'translate-y-3');
        setTimeout(() => toast.remove(), 300);
    }, 2200);
}

// ── Utils ─────────────────────────────────────────────────────────────────────
function escapeHtml(str) {
    return String(str ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}
