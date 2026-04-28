<?php
session_start();
require_once 'config.php';
require_once 'DB_Ops.php';

$db = new Database();

$weatherResult  = $db->getWeatherData();
$weather        = ($weatherResult['success'] && !empty($weatherResult['data'])) ? $weatherResult['data'] : DEFAULT_WEATHER;

$suggestedResult = $db->getSuggestedOutfits();
$suggestedItems  = $suggestedResult['success'] ? $suggestedResult['items'] : [];

$wardrobeResult = $db->getClothing();
$wardrobeItems  = $wardrobeResult['success'] ? $wardrobeResult['items'] : [];

$categoryEmojis = CATEGORY_EMOJIS;
$seasonEmojis   = SEASON_EMOJIS;
$seasonBadge    = SEASON_BADGE_CLASSES;
$categories     = CLOTHING_CATEGORIES;
$seasons        = CLOTHING_SEASONS;

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES | ENT_HTML5, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Outfit Planner</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;1,9..40,300&display=swap" rel="stylesheet"/>
  <style>
    * { font-family: 'DM Sans', sans-serif; }
    h1,h2,h3,.font-display { font-family: 'Syne', sans-serif; }

    body {
      background: #f0f4ff;
      background-image:
        radial-gradient(ellipse at 20% 0%,  rgba(99,102,241,.12) 0%, transparent 60%),
        radial-gradient(ellipse at 80% 10%, rgba(59,130,246,.10) 0%, transparent 55%);
      min-height: 100vh;
    }

    .glass-header {
      background: rgba(255,255,255,.72);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      border-bottom: 1px solid rgba(255,255,255,.6);
    }

    .weather-gradient {
      background: linear-gradient(135deg, #3b82f6 0%, #4f46e5 60%, #6366f1 100%);
      position: relative; overflow: hidden;
    }
    .weather-gradient::before {
      content:''; position:absolute; top:-40%; right:-10%;
      width:340px; height:340px;
      background:rgba(255,255,255,.07); border-radius:50%;
    }
    .weather-gradient::after {
      content:''; position:absolute; bottom:-30%; left:-5%;
      width:240px; height:240px;
      background:rgba(255,255,255,.05); border-radius:50%;
    }

    @keyframes float  { 0%,100%{transform:translateY(0)}  50%{transform:translateY(-10px)} }
    @keyframes fadeUp { from{opacity:0;transform:translateY(24px)} to{opacity:1;transform:translateY(0)} }
    @keyframes shimmer{ 0%{background-position:-600px 0} 100%{background-position:600px 0} }

    .float-anim  { animation: float 4s ease-in-out infinite; }
    .fade-up-1   { animation: fadeUp .55s ease both; animation-delay:.05s; }
    .fade-up-2   { animation: fadeUp .55s ease both; animation-delay:.18s; }
    .fade-up-3   { animation: fadeUp .55s ease both; animation-delay:.30s; }
    .fade-up-4   { animation: fadeUp .55s ease both; animation-delay:.42s; }

    .skeleton {
      background: linear-gradient(90deg,#e2e8f0 25%,#f1f5f9 50%,#e2e8f0 75%);
      background-size: 600px 100%;
      animation: shimmer 1.6s infinite linear;
      border-radius: 10px;
    }

    .drop-zone {
      border: 2px dashed #c7d2fe;
      transition: border-color .3s, background .3s;
    }
    .drop-zone:hover { border-color:#6366f1; background:rgba(99,102,241,.04); }

    .custom-input:focus {
      outline: none;
      box-shadow: 0 0 0 3px rgba(99,102,241,.18);
      border-color: #6366f1;
    }

    .btn-primary {
      background: linear-gradient(135deg,#3b82f6,#6366f1);
      transition: transform .2s, box-shadow .2s, filter .2s;
    }
    .btn-primary:hover  { transform:scale(1.03); box-shadow:0 8px 28px rgba(99,102,241,.38); filter:brightness(1.06); }
    .btn-primary:active { transform:scale(0.98); }

    .outfit-card { transition: transform .28s cubic-bezier(.34,1.56,.64,1), box-shadow .28s ease; }
    .outfit-card:hover { transform:translateY(-6px) scale(1.025); box-shadow:0 20px 48px rgba(79,70,229,.15); }
    .outfit-card:hover .card-overlay { opacity:1; }
    .card-overlay { opacity:0; transition:opacity .25s ease; }

    .weather-card-wrap:hover .weather-inner { transform:scale(1.015); }
    .weather-inner { transition:transform .35s cubic-bezier(.34,1.56,.64,1); }

    select {
      -webkit-appearance:none; -moz-appearance:none; appearance:none;
      background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%236366f1' stroke-width='2.5'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
      background-repeat:no-repeat; background-position:right 14px center;
    }

    #editModal {
      display:none; position:fixed; inset:0; z-index:100;
      background:rgba(0,0,0,.45);
      align-items:center; justify-content:center;
    }

    ::-webkit-scrollbar       { width:6px; }
    ::-webkit-scrollbar-track { background:transparent; }
    ::-webkit-scrollbar-thumb { background:#c7d2fe; border-radius:8px; }
  </style>
</head>
<body class="min-h-screen">

  <!-- ═══ HEADER ═══ -->
  <header class="glass-header sticky top-0 z-50 px-4 sm:px-6 py-3.5">
    <div class="max-w-6xl mx-auto flex items-center justify-between gap-4">
      <div class="flex items-center gap-2.5 shrink-0">
        <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center shadow-md shadow-indigo-200">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"/>
          </svg>
        </div>
        <span class="font-bold text-gray-800 text-lg tracking-tight" style="font-family:'Syne',sans-serif;">Outfit<span class="text-indigo-500">Planner</span></span>
      </div>
      <div class="relative w-full max-w-xs">
        <span class="absolute inset-y-0 left-3.5 flex items-center pointer-events-none text-indigo-400">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
            <circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="m21 21-4.35-4.35"/>
          </svg>
        </span>
        <input id="citySearchInput" type="text" placeholder="Search city…"
          class="custom-input w-full pl-10 pr-4 py-2.5 rounded-xl bg-white/80 border border-gray-200 text-sm text-gray-700 placeholder-gray-400 shadow-sm transition-all duration-200"/>
      </div>
    </div>
  </header>

  <!-- ═══ MAIN ═══ -->
  <main class="max-w-6xl mx-auto px-4 sm:px-6 py-10 space-y-12">

    <!-- ── WEATHER HERO ── -->
    <section class="fade-up-1 weather-card-wrap">
      <div class="weather-inner weather-gradient rounded-3xl p-8 sm:p-12 shadow-2xl shadow-indigo-300/40 text-white text-center relative">
        <div class="absolute inset-0 rounded-3xl ring-1 ring-white/10 pointer-events-none"></div>
        <div id="weatherEmoji" class="float-anim inline-block mb-4 text-7xl select-none drop-shadow-lg"><?= h($weather['emoji']) ?></div>
        <p id="weatherTemp" class="font-extrabold leading-none tracking-tighter drop-shadow-sm text-8xl sm:text-9xl" style="font-family:'Syne',sans-serif;"><?= h($weather['temperature']) ?>°</p>
        <p id="weatherCondition" class="mt-3 text-2xl font-semibold text-blue-100 tracking-wide"><?= h($weather['condition_text']) ?></p>
        <div class="mt-4 inline-flex items-center gap-1.5 text-blue-200 text-sm font-medium">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
          </svg>
          <span id="weatherCity"><?= h($weather['city']) ?><?= !empty($weather['country']) ? ', ' . h($weather['country']) : '' ?></span>
        </div>
        <div class="mt-8 grid grid-cols-3 gap-4 max-w-sm mx-auto">
          <div class="bg-white/10 backdrop-blur-sm rounded-2xl px-3 py-4">
            <p class="text-xs text-blue-200 uppercase tracking-widest mb-1">Humidity</p>
            <p id="weatherHumidity" class="text-xl font-bold"><?= h($weather['humidity']) ?>%</p>
          </div>
          <div class="bg-white/10 backdrop-blur-sm rounded-2xl px-3 py-4">
            <p class="text-xs text-blue-200 uppercase tracking-widest mb-1">Wind</p>
            <p id="weatherWind" class="text-xl font-bold"><?= h($weather['wind_speed']) ?> km/h</p>
          </div>
          <div class="bg-white/10 backdrop-blur-sm rounded-2xl px-3 py-4">
            <p class="text-xs text-blue-200 uppercase tracking-widest mb-1">UV Index</p>
            <p id="weatherUV" class="text-xl font-bold"><?= h($weather['uv_index']) ?></p>
          </div>
        </div>
      </div>
    </section>

    <!-- ── TWO-COLUMN LAYOUT ── -->
    <div class="grid grid-cols-1 lg:grid-cols-5 gap-8">

      <!-- ── ADD CLOTHING ── -->
      <section class="fade-up-2 lg:col-span-2">
        <div class="bg-white rounded-3xl shadow-lg shadow-slate-200/70 p-6 sm:p-8 ring-1 ring-slate-100 h-full">
          <div class="flex items-center gap-3 mb-7">
            <div class="w-8 h-8 rounded-lg bg-indigo-50 flex items-center justify-center">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
              </svg>
            </div>
            <h2 class="font-bold text-xl text-gray-800" style="font-family:'Syne',sans-serif;">Add New Item</h2>
          </div>

          <div class="space-y-4">
            <div>
              <label class="block text-xs font-semibold text-gray-500 uppercase tracking-widest mb-1.5">Item Name</label>
              <input id="itemNameInput" type="text" placeholder="e.g. White Oxford Shirt"
                class="custom-input w-full px-4 py-2.5 rounded-xl border border-gray-200 bg-gray-50 text-sm text-gray-700 placeholder-gray-400 transition-all duration-200"/>
            </div>

            <div>
              <label class="block text-xs font-semibold text-gray-500 uppercase tracking-widest mb-1.5">Category</label>
              <select id="categorySelect" class="custom-input w-full px-4 py-2.5 pr-10 rounded-xl border border-gray-200 bg-gray-50 text-sm text-gray-700 transition-all duration-200 cursor-pointer">
                <option value="" disabled selected>Select category…</option>
                <?php foreach ($categories as $cat): ?>
                <option value="<?= h($cat) ?>"><?= h(($categoryEmojis[$cat] ?? '') . ' ' . $cat) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div>
              <label class="block text-xs font-semibold text-gray-500 uppercase tracking-widest mb-1.5">Season</label>
              <select id="seasonSelect" class="custom-input w-full px-4 py-2.5 pr-10 rounded-xl border border-gray-200 bg-gray-50 text-sm text-gray-700 transition-all duration-200 cursor-pointer">
                <option value="" disabled selected>Select season…</option>
                <?php foreach ($seasons as $s): ?>
                <option value="<?= h($s) ?>"><?= h(($seasonEmojis[$s] ?? '') . ' ' . $s) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div>
              <label class="block text-xs font-semibold text-gray-500 uppercase tracking-widest mb-1.5">Photo</label>
              <label id="photoDropZone" class="drop-zone flex flex-col items-center justify-center gap-2 rounded-2xl bg-indigo-50/40 p-6 cursor-pointer text-center">
                <div class="w-12 h-12 rounded-2xl bg-white shadow-sm flex items-center justify-center text-indigo-400">
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                  </svg>
                </div>
                <div>
                  <p id="photoLabel" class="text-sm font-semibold text-indigo-600">Click to upload</p>
                  <p class="text-xs text-gray-400 mt-0.5">or drag &amp; drop here</p>
                </div>
                <p class="text-xs text-gray-400">PNG, JPG, WEBP up to 8MB</p>
                <input id="photoInput" type="file" accept="image/*" class="hidden"/>
              </label>
            </div>

            <button id="addItemBtn" class="btn-primary w-full py-3 rounded-xl text-white text-sm font-semibold tracking-wide shadow-lg shadow-indigo-200/60 mt-2">
              Add to Wardrobe ✦
            </button>
          </div>
        </div>
      </section>

      <!-- ── SUGGESTED OUTFITS ── -->
      <section class="fade-up-3 lg:col-span-3">
        <div class="bg-white rounded-3xl shadow-lg shadow-slate-200/70 p-6 sm:p-8 ring-1 ring-slate-100 h-full">
          <div class="flex items-center justify-between mb-7">
            <div class="flex items-center gap-3">
              <div class="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                </svg>
              </div>
              <h2 class="font-bold text-xl text-gray-800" style="font-family:'Syne',sans-serif;">Suggested Outfits</h2>
            </div>
            <span id="suggestedCount" class="text-xs font-semibold text-indigo-500 bg-indigo-50 px-3 py-1 rounded-full">
              <?= count($suggestedItems) ?> item<?= count($suggestedItems) !== 1 ? 's' : '' ?>
            </span>
          </div>

          <div id="suggestedGrid" class="grid grid-cols-2 gap-4">
            <?php
            $slots         = 4;
            $suggestedSlice = array_slice($suggestedItems, 0, $slots);
            foreach ($suggestedSlice as $item):
                $badgeClass = $seasonBadge[$item['season']] ?? 'text-gray-700 bg-gray-50';
                $sEmoji     = $seasonEmojis[$item['season']] ?? '';
                $imgContent = $item['image_path']
                    ? '<img src="' . h($item['image_path']) . '" alt="' . h($item['name']) . '" class="w-full h-full object-cover"/>'
                    : '<div class="h-36 bg-gradient-to-br ' . h($item['gradient_from']) . ' ' . h($item['gradient_to']) . ' flex items-center justify-center text-5xl select-none">' . h($item['emoji']) . '</div>';
            ?>
            <div class="outfit-card bg-gray-50 rounded-2xl overflow-hidden ring-1 ring-slate-100 cursor-pointer relative" data-id="<?= (int)$item['id'] ?>">
              <div class="relative overflow-hidden">
                <?= $imgContent ?>
                <div class="card-overlay absolute inset-0 bg-indigo-600/80 flex items-center justify-center gap-3">
                  <button class="card-edit  w-8 h-8 rounded-full bg-white/20 hover:bg-white/30 flex items-center justify-center text-white transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                  </button>
                  <button class="card-delete w-8 h-8 rounded-full bg-red-500/80 hover:bg-red-500 flex items-center justify-center text-white transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                  </button>
                </div>
              </div>
              <div class="p-3.5">
                <p class="font-bold text-gray-800 text-sm leading-tight"><?= h($item['name']) ?></p>
                <p class="text-xs text-gray-400 mt-0.5"><?= h($item['category']) ?></p>
                <span class="mt-2 inline-block text-xs font-semibold <?= $badgeClass ?> px-2.5 py-0.5 rounded-full"><?= h($sEmoji . ' ' . $item['season']) ?></span>
              </div>
            </div>
            <?php endforeach; ?>

            <?php for ($i = count($suggestedSlice); $i < $slots; $i++): ?>
            <div class="bg-gray-50 rounded-2xl overflow-hidden ring-1 ring-slate-100">
              <div class="skeleton h-36"></div>
              <div class="p-3.5 space-y-2">
                <div class="skeleton h-3.5 w-3/4 rounded"></div>
                <div class="skeleton h-3 w-1/2 rounded"></div>
                <div class="skeleton h-5 w-16 rounded-full mt-1"></div>
              </div>
            </div>
            <?php endfor; ?>
          </div>
        </div>
      </section>

    </div><!-- end two-column -->

    <!-- ── FULL WARDROBE ── -->
    <section class="fade-up-4">
      <div class="flex items-center justify-between mb-6">
        <div>
          <h2 class="font-bold text-2xl text-gray-800" style="font-family:'Syne',sans-serif;">Full Wardrobe</h2>
          <p class="text-sm text-gray-400 mt-1">All your saved clothing items</p>
        </div>
        <div class="hidden sm:flex items-center gap-2">
          <button class="filter-btn text-xs font-semibold px-4 py-2 rounded-full bg-indigo-600 text-white shadow-md shadow-indigo-200" data-filter="all">All</button>
          <button class="filter-btn text-xs font-semibold px-4 py-2 rounded-full bg-white text-gray-500 hover:bg-gray-50 border border-gray-200 transition-colors" data-filter="Summer">Summer</button>
          <button class="filter-btn text-xs font-semibold px-4 py-2 rounded-full bg-white text-gray-500 hover:bg-gray-50 border border-gray-200 transition-colors" data-filter="Autumn">Autumn</button>
          <button class="filter-btn text-xs font-semibold px-4 py-2 rounded-full bg-white text-gray-500 hover:bg-gray-50 border border-gray-200 transition-colors" data-filter="Winter">Winter</button>
          <button class="filter-btn text-xs font-semibold px-4 py-2 rounded-full bg-white text-gray-500 hover:bg-gray-50 border border-gray-200 transition-colors" data-filter="Spring">Spring</button>
        </div>
      </div>

      <div id="wardrobeGrid" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-5">
        <?php foreach ($wardrobeItems as $item):
            $badgeClass = $seasonBadge[$item['season']] ?? 'text-gray-700 bg-gray-50';
            $sEmoji     = $seasonEmojis[$item['season']] ?? '';
            $imgContent = $item['image_path']
                ? '<img src="' . h($item['image_path']) . '" alt="' . h($item['name']) . '" class="w-full h-full object-cover"/>'
                : '<div class="h-44 bg-gradient-to-br ' . h($item['gradient_from']) . ' ' . h($item['gradient_to']) . ' flex items-center justify-center text-6xl select-none">' . h($item['emoji']) . '</div>';
        ?>
        <div class="outfit-card bg-white rounded-2xl overflow-hidden shadow-md shadow-slate-200/60 ring-1 ring-slate-100 cursor-pointer relative" data-id="<?= (int)$item['id'] ?>">
          <div class="relative overflow-hidden">
            <?= $imgContent ?>
            <div class="card-overlay absolute inset-0 bg-indigo-600/80 flex items-center justify-center gap-3">
              <button class="card-edit  w-9 h-9 rounded-full bg-white/20 hover:bg-white/30 flex items-center justify-center text-white transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
              </button>
              <button class="card-delete w-9 h-9 rounded-full bg-red-500/80 hover:bg-red-500 flex items-center justify-center text-white transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
              </button>
            </div>
          </div>
          <div class="p-4">
            <p class="font-bold text-gray-800 text-sm"><?= h($item['name']) ?></p>
            <p class="text-xs text-gray-400 mt-0.5 mb-2.5"><?= h($item['category']) ?></p>
            <span class="text-xs font-semibold <?= $badgeClass ?> px-2.5 py-1 rounded-full"><?= h($sEmoji . ' ' . $item['season']) ?></span>
          </div>
        </div>
        <?php endforeach; ?>

        <div class="outfit-card bg-white rounded-2xl overflow-hidden shadow-md shadow-slate-200/60 ring-1 ring-slate-100 flex flex-col items-center justify-center p-8 text-center min-h-48">
          <div class="w-16 h-16 rounded-2xl bg-indigo-50 flex items-center justify-center mb-4 text-3xl">✦</div>
          <p class="font-bold text-gray-700 text-sm">No more outfits</p>
          <p class="text-xs text-gray-400 mt-1 leading-relaxed">Start adding items<br/>from the form above</p>
        </div>
      </div>
    </section>

  </main>

  <!-- ═══ FOOTER ═══ -->
  <footer class="mt-16 border-t border-gray-200/70 py-8 px-4">
    <div class="max-w-6xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-gray-400">
      <div class="flex items-center gap-2">
        <div class="w-5 h-5 rounded-md bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"/>
          </svg>
        </div>
        <span class="font-semibold text-gray-500" style="font-family:'Syne',sans-serif;">OutfitPlanner</span>
      </div>
      <p>© 2026 OutfitPlanner. Dress for the weather, every day.</p>
      <div class="flex items-center gap-4">
        <a href="#" class="hover:text-indigo-500 transition-colors">Privacy</a>
        <a href="#" class="hover:text-indigo-500 transition-colors">Terms</a>
        <a href="#" class="hover:text-indigo-500 transition-colors">Support</a>
      </div>
    </div>
  </footer>

  <!-- ═══ EDIT MODAL ═══ -->
  <div id="editModal">
    <div class="bg-white rounded-3xl shadow-2xl p-8 w-full max-w-sm mx-4 ring-1 ring-slate-100">
      <div class="flex items-center justify-between mb-6">
        <h3 class="font-bold text-xl text-gray-800" style="font-family:'Syne',sans-serif;">Edit Item</h3>
        <button id="editCancelBtn" class="w-8 h-8 rounded-full bg-gray-100 hover:bg-gray-200 flex items-center justify-center text-gray-500 transition-colors text-lg leading-none">×</button>
      </div>
      <div class="space-y-4">
        <div>
          <label class="block text-xs font-semibold text-gray-500 uppercase tracking-widest mb-1.5">Item Name</label>
          <input id="editNameInput" type="text" class="custom-input w-full px-4 py-2.5 rounded-xl border border-gray-200 bg-gray-50 text-sm text-gray-700 transition-all duration-200"/>
        </div>
        <div>
          <label class="block text-xs font-semibold text-gray-500 uppercase tracking-widest mb-1.5">Category</label>
          <select id="editCategorySelect" class="custom-input w-full px-4 py-2.5 pr-10 rounded-xl border border-gray-200 bg-gray-50 text-sm text-gray-700 transition-all duration-200 cursor-pointer">
            <?php foreach ($categories as $cat): ?>
            <option value="<?= h($cat) ?>"><?= h(($categoryEmojis[$cat] ?? '') . ' ' . $cat) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="block text-xs font-semibold text-gray-500 uppercase tracking-widest mb-1.5">Season</label>
          <select id="editSeasonSelect" class="custom-input w-full px-4 py-2.5 pr-10 rounded-xl border border-gray-200 bg-gray-50 text-sm text-gray-700 transition-all duration-200 cursor-pointer">
            <?php foreach ($seasons as $s): ?>
            <option value="<?= h($s) ?>"><?= h(($seasonEmojis[$s] ?? '') . ' ' . $s) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <button id="editSaveBtn" class="btn-primary w-full py-3 rounded-xl text-white text-sm font-semibold tracking-wide shadow-lg shadow-indigo-200/60">
          Save Changes ✦
        </button>
      </div>
    </div>
  </div>

  <script src="API_Ops.js"></script>
  <script src="app.js"></script>
</body>
</html>
