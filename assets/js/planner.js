/* Nutrition Planner — front-end controller */
'use strict';

// ── State ──────────────────────────────────────────────────────────────────── //
const state = {
    weekStart:    '',   // 'YYYY-MM-DD' (always Monday)
    activeDay:    0,    // 0=Mon … 6=Sun
    goals:        {},
    entries:      [],   // all meal entries for current week
    summaries:    {},   // { dayIndex: { calories, protein_g, … } }
    addContext:   null, // { day, mealType } set when add-modal is open
    selectedFood: null, // food object from search
    customFoods:  [],   // user-defined foods loaded at init
    activeTab:    'search', // 'search' | 'myfoods'
};

const DAYS  = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
const MEALS = [
    { key: 'breakfast', label: 'Breakfast', icon: '🍳' },
    { key: 'lunch',     label: 'Lunch',     icon: '🥗' },
    { key: 'dinner',    label: 'Dinner',    icon: '🍽' },
    { key: 'snack',     label: 'Snacks',    icon: '🍎' },
];

// ── Init ───────────────────────────────────────────────────────────────────── //
async function init() {
    state.weekStart = getThisMonday(CONFIG.today);
    const todayDow  = new Date(CONFIG.today).getDay(); // 0=Sun…6=Sat
    state.activeDay = todayDow === 0 ? 6 : todayDow - 1; // convert to 0=Mon

    setupEventListeners();
    await Promise.all([loadGoals(), loadWeekData(), loadCustomFoods()]);
    render();
}

// ── API helpers ────────────────────────────────────────────────────────────── //
const api = CONFIG.baseUrl + 'api/';

async function loadGoals() {
    try {
        const r = await fetch(api + 'goals.php');
        state.goals = await r.json();
    } catch { state.goals = { calories: 2000, protein_g: 50, carbs_g: 250, fat_g: 65, fiber_g: 25, sodium_mg: 2300 }; }
}

async function saveGoals(data) {
    const r = await fetch(api + 'goals.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data),
    });
    return r.json();
}

async function loadWeekData() {
    try {
        const r = await fetch(`${api}meal-plan.php?week=${state.weekStart}`);
        const d = await r.json();
        state.entries   = d.entries   || [];
        state.summaries = d.summaries || {};
    } catch {
        state.entries   = [];
        state.summaries = {};
    }
}

async function addMealEntry(payload) {
    const r = await fetch(api + 'meal-plan.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
    });
    return r.json();
}

async function deleteMealEntry(id) {
    const r = await fetch(api + 'meal-plan.php', {
        method: 'DELETE',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id }),
    });
    return r.json();
}

async function searchFoods(q) {
    const r = await fetch(`${api}food-search.php?q=${encodeURIComponent(q)}`);
    return r.json();
}

async function loadCustomFoods() {
    try {
        const r = await fetch(api + 'custom-foods.php');
        state.customFoods = await r.json();
    } catch { state.customFoods = []; }
}

async function saveCustomFoodApi(data) {
    const r = await fetch(api + 'custom-foods.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data),
    });
    return r.json();
}

// Convert a custom_foods DB row into the standard food-object shape
function normalizeCustomFood(f) {
    return {
        fdc_id:       f.id,
        name:         f.food_name,
        brand:        f.brand_name || null,
        data_type:    'Custom',
        serving_size: parseFloat(f.serving_size),
        serving_unit: f.serving_unit,
        _isCustom:    true,
        nutrients: {
            calories:        f.calories        !== null ? parseFloat(f.calories)        : null,
            protein_g:       f.protein_g       !== null ? parseFloat(f.protein_g)       : null,
            carbs_g:         f.carbs_g         !== null ? parseFloat(f.carbs_g)         : null,
            fat_g:           f.fat_g           !== null ? parseFloat(f.fat_g)           : null,
            fiber_g:         f.fiber_g         !== null ? parseFloat(f.fiber_g)         : null,
            sodium_mg:       f.sodium_mg       !== null ? parseFloat(f.sodium_mg)       : null,
            sugar_g:         f.sugar_g         !== null ? parseFloat(f.sugar_g)         : null,
            cholesterol_mg:  f.cholesterol_mg  !== null ? parseFloat(f.cholesterol_mg)  : null,
            saturated_fat_g: f.saturated_fat_g !== null ? parseFloat(f.saturated_fat_g) : null,
        },
    };
}

// ── Render ─────────────────────────────────────────────────────────────────── //
function render() {
    renderWeekNav();
    renderDayTabs();
    renderMealGrid();
    renderNutritionPanel();
    renderWeekTable();
}

function renderWeekNav() {
    const mon = new Date(state.weekStart + 'T00:00:00');
    const sun = new Date(mon);
    sun.setDate(sun.getDate() + 6);
    document.getElementById('weekLabel').textContent =
        `Week of ${fmtShort(mon)} – ${fmtShort(sun)}`;
}

function renderDayTabs() {
    const container = document.getElementById('dayTabs');
    const mon = new Date(state.weekStart + 'T00:00:00');

    container.innerHTML = DAYS.map((day, i) => {
        const d   = new Date(mon);
        d.setDate(d.getDate() + i);
        const sum = state.summaries[i];
        const cal = sum ? Math.round(sum.calories) : null;
        const pct = cal !== null && state.goals.calories ? cal / state.goals.calories : null;
        const calClass = pct === null ? '' : pct > 1.05 ? 'goal-over' : pct >= 0.9 ? 'goal-met' : '';

        return `
        <button class="day-tab ${i === state.activeDay ? 'active' : ''} ${calClass}"
                role="tab" aria-selected="${i === state.activeDay}"
                data-day="${i}">
            <span class="tab-day">${day.slice(0,3)}</span>
            <span class="tab-date">${d.getDate()}</span>
            ${cal !== null ? `<span class="tab-cal">${cal} kcal</span>` : ''}
        </button>`;
    }).join('');

    container.querySelectorAll('.day-tab').forEach(btn => {
        btn.addEventListener('click', () => {
            state.activeDay = parseInt(btn.dataset.day, 10);
            render();
        });
    });
}

function renderMealGrid() {
    const grid = document.getElementById('mealGrid');
    const dayEntries = state.entries.filter(e => parseInt(e.day_of_week, 10) === state.activeDay);

    grid.innerHTML = MEALS.map(meal => {
        const items = dayEntries.filter(e => e.meal_type === meal.key);
        const mealCal = items.reduce((s, e) => s + (parseFloat(e.calories) || 0), 0);

        return `
        <div class="meal-card">
            <div class="meal-card-header">
                <span class="meal-icon">${meal.icon}</span>
                <span class="meal-name">${meal.label}</span>
                ${mealCal > 0 ? `<span class="meal-cal-total">${Math.round(mealCal)} kcal</span>` : ''}
            </div>
            <div class="meal-card-body">
                ${items.map(e => foodItemHtml(e)).join('')}
            </div>
            <button class="meal-add-btn" data-day="${state.activeDay}" data-meal="${meal.key}">
                + Add Food
            </button>
        </div>`;
    }).join('');

    grid.querySelectorAll('.meal-add-btn').forEach(btn => {
        btn.addEventListener('click', () => openFoodModal(parseInt(btn.dataset.day, 10), btn.dataset.meal));
    });

    grid.querySelectorAll('.delete-entry-btn').forEach(btn => {
        btn.addEventListener('click', async () => {
            const id = parseInt(btn.dataset.id, 10);
            btn.disabled = true;
            await deleteMealEntry(id);
            await loadWeekData();
            render();
            toast('Food removed.');
        });
    });
}

function foodItemHtml(e) {
    const macros = [
        e.calories   !== null ? `<span class="macro-badge cal">${Math.round(e.calories)} kcal</span>` : '',
        e.protein_g  !== null ? `<span class="macro-badge">P ${e.protein_g}g</span>` : '',
        e.carbs_g    !== null ? `<span class="macro-badge">C ${e.carbs_g}g</span>` : '',
        e.fat_g      !== null ? `<span class="macro-badge">F ${e.fat_g}g</span>` : '',
    ].filter(Boolean).join('');

    const srv = `${e.servings} × ${e.serving_size}${e.serving_unit}`;

    return `
    <div class="food-item">
        <div class="food-info">
            <div class="food-name">${esc(e.food_name)}</div>
            ${e.brand_name ? `<div class="food-brand">${esc(e.brand_name)}</div>` : ''}
            <div class="food-macros">${macros}</div>
            <div class="food-brand" style="margin-top:2px">${srv}</div>
        </div>
        <button class="btn-icon delete-entry-btn" data-id="${e.id}" title="Remove">✕</button>
    </div>`;
}

function renderNutritionPanel() {
    const sum   = state.summaries[state.activeDay] || {};
    const goals = state.goals;
    const cal   = Math.round(sum.calories || 0);
    const goalCal = goals.calories || 2000;
    const pct   = cal / goalCal;
    const status = pct > 1.05 ? 'over' : pct >= 0.85 ? 'ok' : 'low';
    const msg    = { over: '⚠ Over goal', ok: '✓ On track', low: 'Room for more' };

    const bars = [
        { key: 'protein_g',  label: 'Protein',  unit: 'g',  goal: goals.protein_g  || 50 },
        { key: 'carbs_g',    label: 'Carbs',    unit: 'g',  goal: goals.carbs_g    || 250 },
        { key: 'fat_g',      label: 'Fat',      unit: 'g',  goal: goals.fat_g      || 65 },
        { key: 'fiber_g',    label: 'Fiber',    unit: 'g',  goal: goals.fiber_g    || 25 },
        { key: 'sodium_mg',  label: 'Sodium',   unit: 'mg', goal: goals.sodium_mg  || 2300 },
    ];

    document.getElementById('nutritionSummary').innerHTML = `
    <div class="calories-display">
        <div class="calories-num">${cal.toLocaleString()}</div>
        <div class="calories-goal">of ${goalCal.toLocaleString()} kcal goal</div>
        <div class="calories-status ${status}">${msg[status]}</div>
    </div>
    ${bars.map(b => {
        const val  = Math.round((sum[b.key] || 0) * 10) / 10;
        const bpct = Math.min(1, val / b.goal);
        const cls  = bpct > 1.05 ? 'over' : bpct >= 0.7 ? '' : 'warning';
        return `
        <div class="nutrient-row">
            <div class="nutrient-label">
                <span class="nutrient-name">${b.label}</span>
                <span class="nutrient-value">${val} / ${b.goal}${b.unit}</span>
            </div>
            <div class="progress-bar-track">
                <div class="progress-bar-fill ${cls}" style="width:${Math.round(bpct*100)}%"></div>
            </div>
        </div>`;
    }).join('')}`;
}

function renderWeekTable() {
    const mon    = new Date(state.weekStart + 'T00:00:00');
    const goalCal = state.goals.calories || 2000;

    const rows = DAYS.map((day, i) => {
        const d   = new Date(mon);
        d.setDate(d.getDate() + i);
        const sum = state.summaries[i] || {};
        const cal = Math.round(sum.calories || 0);
        const pct = cal / goalCal;
        const cls = cal === 0 ? '' : pct > 1.05 ? 'cell-over' : pct >= 0.85 ? 'cell-good' : 'cell-low';
        const isToday = i === state.activeDay ? 'day-active' : '';

        return `
        <tr class="${isToday}">
            <td><strong>${day.slice(0,3)}</strong> ${d.getDate()}</td>
            <td class="${cls}">${cal > 0 ? cal.toLocaleString() : '—'}</td>
            <td>${sum.protein_g  ? Math.round(sum.protein_g)  + 'g' : '—'}</td>
            <td>${sum.carbs_g    ? Math.round(sum.carbs_g)    + 'g' : '—'}</td>
            <td>${sum.fat_g      ? Math.round(sum.fat_g)      + 'g' : '—'}</td>
            <td>${sum.fiber_g    ? Math.round(sum.fiber_g)    + 'g' : '—'}</td>
            <td>${sum.sodium_mg  ? Math.round(sum.sodium_mg)       : '—'}</td>
        </tr>`;
    }).join('');

    document.getElementById('weekSummaryTable').innerHTML = `
    <table class="week-table">
        <thead>
            <tr>
                <th>Day</th>
                <th>Calories</th>
                <th>Protein</th>
                <th>Carbs</th>
                <th>Fat</th>
                <th>Fiber</th>
                <th>Sodium (mg)</th>
            </tr>
        </thead>
        <tbody>${rows}</tbody>
    </table>`;
}

// ── Food modal — tab management ────────────────────────────────────────────── //
function setModalTab(tab) {
    state.activeTab = tab;
    document.querySelectorAll('.modal-tab-btn').forEach(b => {
        b.classList.toggle('active', b.dataset.tab === tab);
    });
    document.getElementById('searchPanel').hidden  = tab !== 'search';
    document.getElementById('myFoodsPanel').hidden = tab !== 'myfoods';

    if (tab === 'myfoods') {
        renderMyFoodsList();
    } else {
        setTimeout(() => document.getElementById('foodSearchInput').focus(), 50);
    }
}

// ── Food modal — open / close ──────────────────────────────────────────────── //
let searchTimer = null;

function openFoodModal(day, mealType) {
    state.addContext   = { day, mealType };
    state.selectedFood = null;

    const meal = MEALS.find(m => m.key === mealType);
    document.getElementById('foodModalTitle').textContent =
        `Add to ${DAYS[day]} — ${meal.label}`;

    // Reset UI
    setModalTab('search');
    document.getElementById('foodSearchInput').value = '';
    document.getElementById('searchResults').innerHTML =
        '<p class="search-hint">Type at least 2 characters. Your custom foods appear first.</p>';
    document.getElementById('selectedFoodPanel').hidden = true;
    document.getElementById('confirmFoodAdd').disabled  = true;
    document.getElementById('servingsInput').value      = '1';
    hideCustomFoodForm();

    showModal('foodModalBackdrop');
    setTimeout(() => document.getElementById('foodSearchInput').focus(), 100);
}

function closeFoodModal() {
    hideModal('foodModalBackdrop');
    state.addContext   = null;
    state.selectedFood = null;
}

function showModal(id)  { document.getElementById(id).hidden = false; }
function hideModal(id)  { document.getElementById(id).hidden = true; }

// ── Food modal — search tab ────────────────────────────────────────────────── //
function handleSearchInput(e) {
    const q = e.target.value.trim();
    clearTimeout(searchTimer);
    if (q.length < 2) {
        document.getElementById('searchResults').innerHTML =
            '<p class="search-hint">Type at least 2 characters. Your custom foods appear first.</p>';
        return;
    }
    document.getElementById('searchSpinner').hidden = false;
    searchTimer = setTimeout(() => runSearch(q), 400);
}

async function runSearch(q) {
    try {
        // Client-side filter of custom foods (instant, no extra request)
        const customMatches = state.customFoods
            .filter(f => f.food_name.toLowerCase().includes(q.toLowerCase()) ||
                         (f.brand_name && f.brand_name.toLowerCase().includes(q.toLowerCase())))
            .map(normalizeCustomFood);

        const data      = await searchFoods(q);
        const usdaFoods = data.foods || [];

        renderSearchResults([...customMatches, ...usdaFoods]);
    } catch {
        document.getElementById('searchResults').innerHTML =
            '<p class="search-empty">Search failed. Check your connection.</p>';
    } finally {
        document.getElementById('searchSpinner').hidden = true;
    }
}

function renderSearchResults(foods) {
    const el = document.getElementById('searchResults');
    if (!foods.length) {
        el.innerHTML = '<p class="search-empty">No results found. Try a different search term.</p>';
        return;
    }

    el.innerHTML = foods.map(f => {
        const cal = f.nutrients?.calories !== null && f.nutrients?.calories !== undefined
            ? `${Math.round(f.nutrients.calories)} kcal` : '—';
        const isCustom = f._isCustom || f.data_type === 'Custom';
        const per = (isCustom || f.data_type === 'Branded')
            ? `per ${f.serving_size}${f.serving_unit}` : 'per 100g';
        const typeBadge = isCustom
            ? `<span class="badge-custom">Custom</span>`
            : esc(f.data_type || '');
        return `
        <div class="search-result-item" role="option" tabindex="0">
            <div class="result-info">
                <div class="result-name">${esc(f.name)}</div>
                ${f.brand ? `<div class="result-brand">${esc(f.brand)}</div>` : ''}
                <div class="result-brand">${typeBadge} · ${per}</div>
            </div>
            <span class="result-cal">${cal}</span>
        </div>`;
    }).join('');

    el.querySelectorAll('.search-result-item').forEach((item, idx) => {
        const food = foods[idx];
        item.addEventListener('click', () => selectFoodItem(food));
        item.addEventListener('keydown', e => { if (e.key === 'Enter') selectFoodItem(food); });
    });
}

// ── Food modal — My Custom Foods tab ──────────────────────────────────────── //
function renderMyFoodsList() {
    const listEl = document.getElementById('customFoodsList');
    if (!state.customFoods.length) {
        listEl.innerHTML = '<p class="search-hint">No custom foods yet. Click "Create New Food" to add one.</p>';
        return;
    }
    const foods = state.customFoods.map(normalizeCustomFood);
    listEl.innerHTML = foods.map(f => {
        const cal = f.nutrients.calories !== null
            ? `${Math.round(f.nutrients.calories)} kcal` : '—';
        return `
        <div class="search-result-item" role="option" tabindex="0">
            <div class="result-info">
                <div class="result-name">${esc(f.name)}</div>
                ${f.brand ? `<div class="result-brand">${esc(f.brand)}</div>` : ''}
                <div class="result-brand"><span class="badge-custom">Custom</span> · per ${f.serving_size}${f.serving_unit}</div>
            </div>
            <span class="result-cal">${cal}</span>
        </div>`;
    }).join('');

    listEl.querySelectorAll('.search-result-item').forEach((item, idx) => {
        const food = foods[idx];
        item.addEventListener('click', () => selectFoodItem(food));
        item.addEventListener('keydown', e => { if (e.key === 'Enter') selectFoodItem(food); });
    });
}

function showCustomFoodForm() {
    document.getElementById('customFoodsList').hidden    = true;
    document.getElementById('showCustomFormBtn').hidden  = true;
    document.getElementById('customFoodFormWrap').hidden = false;
    setTimeout(() => document.getElementById('cfName').focus(), 50);
}

function hideCustomFoodForm() {
    document.getElementById('customFoodsList').hidden    = false;
    document.getElementById('showCustomFormBtn').hidden  = false;
    document.getElementById('customFoodFormWrap').hidden = true;
    const form = document.getElementById('customFoodForm');
    if (form) form.reset();
}

async function handleSaveCustomFood() {
    const name     = document.getElementById('cfName').value.trim();
    const calories = document.getElementById('cfCalories').value;

    if (!name) {
        document.getElementById('cfName').focus();
        toast('Food name is required.');
        return;
    }
    if (calories === '') {
        document.getElementById('cfCalories').focus();
        toast('Calories are required.');
        return;
    }

    const numOrNull = id => {
        const v = document.getElementById(id).value;
        return v !== '' ? parseFloat(v) : null;
    };

    const data = {
        food_name:       name,
        brand_name:      document.getElementById('cfBrand').value.trim() || null,
        serving_size:    parseFloat(document.getElementById('cfServingSize').value) || 100,
        serving_unit:    document.getElementById('cfServingUnit').value.trim() || 'g',
        calories:        parseFloat(calories),
        protein_g:       numOrNull('cfProtein'),
        carbs_g:         numOrNull('cfCarbs'),
        fat_g:           numOrNull('cfFat'),
        fiber_g:         numOrNull('cfFiber'),
        sodium_mg:       numOrNull('cfSodium'),
        sugar_g:         numOrNull('cfSugar'),
        cholesterol_mg:  numOrNull('cfCholesterol'),
        saturated_fat_g: numOrNull('cfSatFat'),
    };

    const btn = document.getElementById('saveCustomFoodBtn');
    btn.disabled = true;
    try {
        const result = await saveCustomFoodApi(data);
        if (result.success && result.food) {
            state.customFoods.push(result.row); // add raw row for future filtering
            hideCustomFoodForm();
            selectFoodItem(result.food);
            toast('Custom food saved!');
        } else {
            toast(result.error || 'Could not save food. Please try again.');
        }
    } catch {
        toast('Network error. Please try again.');
    } finally {
        btn.disabled = false;
    }
}

// ── Food modal — food selection ────────────────────────────────────────────── //
function selectFoodItem(food) {
    state.selectedFood = food;

    // Hide tab panels, show selected panel
    document.getElementById('searchPanel').hidden  = true;
    document.getElementById('myFoodsPanel').hidden = true;
    document.querySelectorAll('.modal-tab-btn').forEach(b => b.setAttribute('disabled', ''));

    const isCustomOrBranded = food._isCustom || food.data_type === 'Branded' || food.data_type === 'Custom';
    const per = isCustomOrBranded
        ? `${food.serving_size}${food.serving_unit} per serving`
        : `per 100${food.serving_unit || 'g'}`;

    document.getElementById('selectedName').textContent = food.name;
    document.getElementById('selectedMeta').textContent =
        [food.brand, food.data_type, per].filter(Boolean).join(' · ');
    document.getElementById('servingUnitLabel').textContent = isCustomOrBranded
        ? `(1 serving = ${food.serving_size}${food.serving_unit})` : '(× 100g)';

    document.getElementById('servingsInput').value = '1';
    updateNutrientPreview();

    document.getElementById('selectedFoodPanel').hidden = false;
    document.getElementById('confirmFoodAdd').disabled  = false;
}

function updateNutrientPreview() {
    const food = state.selectedFood;
    if (!food) return;
    const s = parseFloat(document.getElementById('servingsInput').value) || 1;
    const n = food.nutrients;

    const cells = [
        { key: 'calories',  label: 'Calories', unit: 'kcal', round: 0 },
        { key: 'protein_g', label: 'Protein',  unit: 'g',    round: 1 },
        { key: 'carbs_g',   label: 'Carbs',    unit: 'g',    round: 1 },
        { key: 'fat_g',     label: 'Fat',       unit: 'g',    round: 1 },
        { key: 'fiber_g',   label: 'Fiber',    unit: 'g',    round: 1 },
        { key: 'sodium_mg', label: 'Sodium',   unit: 'mg',   round: 0 },
    ];

    document.getElementById('nutrientPreview').innerHTML = cells.map(c => {
        const v = n[c.key] !== null && n[c.key] !== undefined
            ? (n[c.key] * s).toFixed(c.round) : '—';
        return `<div class="np-cell"><div class="np-val">${v}</div><div class="np-name">${c.label}<br>${c.unit}</div></div>`;
    }).join('');
}

async function confirmFoodAdd() {
    const food = state.selectedFood;
    const ctx  = state.addContext;
    if (!food || !ctx) return;

    const servings = parseFloat(document.getElementById('servingsInput').value) || 1;

    const payload = {
        week_start:   state.weekStart,
        day_of_week:  ctx.day,
        meal_type:    ctx.mealType,
        fdc_id:       food.fdc_id,
        food_name:    food.name,
        brand_name:   food.brand || null,
        serving_size: food.serving_size,
        serving_unit: food.serving_unit,
        servings,
        nutrients:    food.nutrients,
    };

    document.getElementById('confirmFoodAdd').disabled = true;
    try {
        const r = await addMealEntry(payload);
        if (r.success) {
            closeFoodModal();
            await loadWeekData();
            render();
            toast('Food added!');
        } else {
            toast('Could not add food. Please try again.');
            document.getElementById('confirmFoodAdd').disabled = false;
        }
    } catch {
        toast('Network error. Please try again.');
        document.getElementById('confirmFoodAdd').disabled = false;
    }
}

// ── USDA auto-calculation ──────────────────────────────────────────────────── //

// USDA 2020-2025 Dietary Guidelines AMDR defaults
const USDA = {
    proteinPct: 0.20, // 20% of calories  (AMDR 10-35%)
    carbsPct:   0.50, // 50% of calories  (AMDR 45-65%)
    fatPct:     0.30, // 30% of calories  (AMDR 20-35%)
    fiberPer1k: 14,   // 14g per 1,000 kcal
    sodium:     2300, // 2,300 mg/day max (fixed)
};

function calcUsda(calories) {
    const cal = Math.max(500, parseFloat(calories) || 2000);
    return {
        protein_g: Math.round(cal * USDA.proteinPct / 4),
        carbs_g:   Math.round(cal * USDA.carbsPct   / 4),
        fat_g:     Math.round(cal * USDA.fatPct      / 9),
        fiber_g:   Math.round(cal * USDA.fiberPer1k  / 1000),
        sodium_mg: USDA.sodium,
    };
}

function applyUsdaToForm(calories) {
    const m = calcUsda(calories);
    document.getElementById('goalProtein').value = m.protein_g;
    document.getElementById('goalCarbs').value   = m.carbs_g;
    document.getElementById('goalFat').value     = m.fat_g;
    document.getElementById('goalFiber').value   = m.fiber_g;
    // Sodium stays as-is unless explicitly reset
    updateGoalHints(calories);
}

function updateGoalHints(calories) {
    const cal  = Math.max(500, parseFloat(calories) || 2000);
    const pPct = Math.round((parseFloat(document.getElementById('goalProtein').value) * 4 / cal) * 100);
    const cPct = Math.round((parseFloat(document.getElementById('goalCarbs').value)   * 4 / cal) * 100);
    const fPct = Math.round((parseFloat(document.getElementById('goalFat').value)     * 9 / cal) * 100);
    const fbG  = parseFloat(document.getElementById('goalFiber').value);

    document.getElementById('hintProtein').textContent = `${pPct}% of calories`;
    document.getElementById('hintCarbs').textContent   = `${cPct}% of calories`;
    document.getElementById('hintFat').textContent     = `${fPct}% of calories`;
    document.getElementById('hintFiber').textContent   = `${(fbG / cal * 1000).toFixed(1)}g per 1,000 kcal`;
}

// ── Goals modal ────────────────────────────────────────────────────────────── //
function openGoalsModal() {
    const g   = state.goals;
    const cal = g.calories || 2000;

    document.getElementById('goalCalories').value = cal;
    document.getElementById('goalProtein').value  = g.protein_g || calcUsda(cal).protein_g;
    document.getElementById('goalCarbs').value    = g.carbs_g   || calcUsda(cal).carbs_g;
    document.getElementById('goalFat').value      = g.fat_g     || calcUsda(cal).fat_g;
    document.getElementById('goalFiber').value    = g.fiber_g   || calcUsda(cal).fiber_g;
    document.getElementById('goalSodium').value   = g.sodium_mg || USDA.sodium;

    updateGoalHints(cal);
    showModal('goalsModalBackdrop');
}

async function handleSaveGoals() {
    const form = document.getElementById('goalsForm');
    const data = Object.fromEntries(
        [...form.querySelectorAll('input')].map(i => [i.name, parseFloat(i.value) || 0])
    );
    document.getElementById('saveGoalsBtn').disabled = true;
    try {
        await saveGoals(data);
        state.goals = data;
        hideModal('goalsModalBackdrop');
        render();
        toast('Goals saved!');
    } finally {
        document.getElementById('saveGoalsBtn').disabled = false;
    }
}

// ── Week navigation ────────────────────────────────────────────────────────── //
function shiftWeek(delta) {
    const d = new Date(state.weekStart + 'T00:00:00');
    d.setDate(d.getDate() + delta * 7);
    state.weekStart = d.toISOString().slice(0, 10);
    loadWeekData().then(render);
}

// ── Utility ────────────────────────────────────────────────────────────────── //
function getThisMonday(isoDate) {
    const d = new Date(isoDate + 'T00:00:00');
    const dow = d.getDay(); // 0=Sun
    const diff = dow === 0 ? -6 : 1 - dow;
    d.setDate(d.getDate() + diff);
    return d.toISOString().slice(0, 10);
}

function fmtShort(d) {
    return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
}

function esc(str) {
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

let toastTimer = null;
function toast(msg) {
    const el = document.getElementById('toast');
    el.textContent = msg;
    el.hidden = false;
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => { el.hidden = true; }, 2800);
}

// ── Auth ───────────────────────────────────────────────────────────────────── //
function showAuthError(id, msg) {
    const el = document.getElementById(id);
    el.textContent = msg;
    el.hidden = false;
}

async function handleLogin() {
    const email    = document.getElementById('loginEmail').value.trim();
    const password = document.getElementById('loginPassword').value;
    document.getElementById('loginError').hidden = true;

    if (!email || !password) {
        showAuthError('loginError', 'Please enter your email and password.');
        return;
    }

    const btn = document.getElementById('submitLogin');
    btn.disabled = true;
    try {
        const r = await fetch(CONFIG.baseUrl + 'api/auth.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'login', email, password, csrf: CONFIG.csrf }),
        });
        const d = await r.json();
        if (d.success) {
            window.location.reload();
        } else {
            showAuthError('loginError', d.error || 'Login failed. Please check your credentials.');
        }
    } catch {
        showAuthError('loginError', 'Network error. Please try again.');
    } finally {
        btn.disabled = false;
    }
}

async function handleRegister() {
    const name      = document.getElementById('regName').value.trim();
    const email     = document.getElementById('regEmail').value.trim();
    const password  = document.getElementById('regPassword').value;
    const confirmPw = document.getElementById('regConfirm').value;
    document.getElementById('registerError').hidden = true;

    if (!name || !email || !password || !confirmPw) {
        showAuthError('registerError', 'Please fill in all fields.');
        return;
    }
    if (password.length < 8) {
        showAuthError('registerError', 'Password must be at least 8 characters.');
        document.getElementById('regPassword').focus();
        return;
    }
    if (password !== confirmPw) {
        showAuthError('registerError', 'Passwords do not match.');
        document.getElementById('regConfirm').select();
        document.getElementById('regConfirm').focus();
        return;
    }

    const btn = document.getElementById('submitRegister');
    btn.disabled = true;
    try {
        const r = await fetch(CONFIG.baseUrl + 'api/auth.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'register', name, email, password, confirm: confirmPw, csrf: CONFIG.csrf }),
        });
        const d = await r.json();
        if (d.success) {
            window.location.reload();
        } else {
            showAuthError('registerError', d.error || 'Registration failed. Please try again.');
        }
    } catch {
        showAuthError('registerError', 'Network error. Please try again.');
    } finally {
        btn.disabled = false;
    }
}

function handleLogout() {
    window.location.href = CONFIG.baseUrl + 'logout.php';
}

// ── Print ──────────────────────────────────────────────────────────────────── //
function printMealPlan() {
    const mon = new Date(state.weekStart + 'T00:00:00');
    const sun = new Date(mon);
    sun.setDate(sun.getDate() + 6);

    document.getElementById('printWeekLabel').textContent =
        `Week of ${fmtShort(mon)} – ${fmtShort(sun)}`;
    document.getElementById('printUserName').textContent =
        CONFIG.user ? `Meal plan for ${CONFIG.user.name}` : '';

    const g = state.goals;
    document.getElementById('printGoalLine').textContent =
        `Daily goal: ${g.calories || 2000} kcal · Protein ${g.protein_g || 50}g · Carbs ${g.carbs_g || 250}g · Fat ${g.fat_g || 65}g`;

    const daysHtml = DAYS.map((dayName, i) => {
        const d = new Date(mon);
        d.setDate(d.getDate() + i);
        const dayEntries = state.entries.filter(e => parseInt(e.day_of_week, 10) === i);
        const sum        = state.summaries[i] || {};

        const mealsHtml = MEALS.map(meal => {
            const items = dayEntries.filter(e => e.meal_type === meal.key);
            if (!items.length) return '';
            return `
            <div class="pd-meal">
                <div class="pd-meal-name">${meal.label}</div>
                ${items.map(e => `
                <div class="pd-food">
                    <span class="pd-food-name">${esc(e.food_name)}</span>${e.brand_name ? ` <span class="pd-food-brand">(${esc(e.brand_name)})</span>` : ''}
                    <span class="pd-food-detail">${e.servings}× ${e.serving_size}${e.serving_unit}${e.calories ? ` · ${Math.round(e.calories)} kcal` : ''}</span>
                </div>`).join('')}
            </div>`;
        }).filter(Boolean).join('');

        const calText = sum.calories ? `${Math.round(sum.calories)} kcal` : '';

        return `
        <div class="print-day">
            <div class="pd-header">
                <span class="pd-day-name">${dayName}</span>
                <span class="pd-date">${fmtShort(d)}</span>
                <span class="pd-cal">${calText}</span>
            </div>
            <div class="pd-meals">${mealsHtml || '<p class="pd-empty">No meals planned</p>'}</div>
        </div>`;
    }).join('');

    document.getElementById('printDays').innerHTML = daysHtml;
    window.print();
}

// ── Event listeners ────────────────────────────────────────────────────────── //
function setupEventListeners() {
    // Week nav
    document.getElementById('prevWeek').addEventListener('click', () => shiftWeek(-1));
    document.getElementById('nextWeek').addEventListener('click', () => shiftWeek(1));

    // Goals modal
    document.getElementById('goalsBtn').addEventListener('click', openGoalsModal);
    document.getElementById('closeGoalsModal').addEventListener('click', () => hideModal('goalsModalBackdrop'));
    document.getElementById('cancelGoals').addEventListener('click', () => hideModal('goalsModalBackdrop'));
    document.getElementById('saveGoalsBtn').addEventListener('click', handleSaveGoals);

    // Auto-calculate macros when calorie goal changes
    document.getElementById('goalCalories').addEventListener('input', e => {
        applyUsdaToForm(e.target.value);
    });

    // Update percentage hints when a macro is manually edited
    ['goalProtein','goalCarbs','goalFat','goalFiber'].forEach(id => {
        document.getElementById(id).addEventListener('input', () => {
            updateGoalHints(document.getElementById('goalCalories').value);
        });
    });

    // Reset all macros to USDA guidelines
    document.getElementById('resetUsda').addEventListener('click', () => {
        applyUsdaToForm(document.getElementById('goalCalories').value);
        document.getElementById('goalSodium').value = USDA.sodium;
    });

    // Food modal
    document.getElementById('closeFoodModal').addEventListener('click', closeFoodModal);
    document.getElementById('cancelFoodAdd').addEventListener('click', closeFoodModal);
    document.getElementById('confirmFoodAdd').addEventListener('click', confirmFoodAdd);
    document.getElementById('foodSearchInput').addEventListener('input', handleSearchInput);
    document.getElementById('servingsInput').addEventListener('input', updateNutrientPreview);
    document.getElementById('clearSelection').addEventListener('click', () => {
        state.selectedFood = null;
        document.getElementById('selectedFoodPanel').hidden = true;
        document.getElementById('confirmFoodAdd').disabled  = true;
        // Restore tab buttons and the previously active tab
        document.querySelectorAll('.modal-tab-btn').forEach(b => b.removeAttribute('disabled'));
        setModalTab(state.activeTab);
    });

    // Modal tab switching
    document.querySelectorAll('.modal-tab-btn').forEach(btn => {
        btn.addEventListener('click', () => setModalTab(btn.dataset.tab));
    });

    // Custom food form — show / hide
    document.getElementById('showCustomFormBtn').addEventListener('click', showCustomFoodForm);
    document.getElementById('cancelCustomFood').addEventListener('click', hideCustomFoodForm);
    document.getElementById('saveCustomFoodBtn').addEventListener('click', handleSaveCustomFood);

    // Close modals on backdrop click
    document.getElementById('foodModalBackdrop').addEventListener('click', e => {
        if (e.target === e.currentTarget) closeFoodModal();
    });
    document.getElementById('goalsModalBackdrop').addEventListener('click', e => {
        if (e.target === e.currentTarget) hideModal('goalsModalBackdrop');
    });

    // Escape key closes any open modal
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
            if (!document.getElementById('foodModalBackdrop').hidden)     closeFoodModal();
            if (!document.getElementById('goalsModalBackdrop').hidden)    hideModal('goalsModalBackdrop');
            if (!document.getElementById('loginModalBackdrop').hidden)    hideModal('loginModalBackdrop');
            if (!document.getElementById('registerModalBackdrop').hidden) hideModal('registerModalBackdrop');
        }
    });

    // Week summary toggle
    const toggle = document.getElementById('weekSummaryToggle');
    const body   = document.getElementById('weekSummaryBody');
    toggle.addEventListener('click', () => {
        const open = !body.hidden;
        body.hidden = open;
        toggle.setAttribute('aria-expanded', String(!open));
        toggle.textContent = `Week Overview ${open ? '▾' : '▴'}`;
    });

    // Print
    document.getElementById('printBtn').addEventListener('click', printMealPlan);

    // Auth — elements are conditional on PHP login state
    const loginBtn    = document.getElementById('loginBtn');
    const registerBtn = document.getElementById('registerBtn');
    const logoutBtn   = document.getElementById('logoutBtn');
    if (loginBtn)    loginBtn.addEventListener('click', () => showModal('loginModalBackdrop'));
    if (registerBtn) registerBtn.addEventListener('click', () => showModal('registerModalBackdrop'));
    if (logoutBtn)   logoutBtn.addEventListener('click', handleLogout);

    document.getElementById('closeLoginModal').addEventListener('click', () => hideModal('loginModalBackdrop'));
    document.getElementById('cancelLogin').addEventListener('click', () => hideModal('loginModalBackdrop'));
    document.getElementById('submitLogin').addEventListener('click', handleLogin);
    document.getElementById('switchToRegister').addEventListener('click', () => {
        hideModal('loginModalBackdrop');
        showModal('registerModalBackdrop');
    });

    document.getElementById('closeRegisterModal').addEventListener('click', () => hideModal('registerModalBackdrop'));
    document.getElementById('cancelRegister').addEventListener('click', () => hideModal('registerModalBackdrop'));
    document.getElementById('submitRegister').addEventListener('click', handleRegister);
    document.getElementById('switchToLogin').addEventListener('click', () => {
        hideModal('registerModalBackdrop');
        showModal('loginModalBackdrop');
    });

    document.getElementById('loginModalBackdrop').addEventListener('click', e => {
        if (e.target === e.currentTarget) hideModal('loginModalBackdrop');
    });
    document.getElementById('registerModalBackdrop').addEventListener('click', e => {
        if (e.target === e.currentTarget) hideModal('registerModalBackdrop');
    });

    // Show / hide password toggles
    document.querySelectorAll('.pw-toggle').forEach(btn => {
        btn.addEventListener('click', () => {
            const input   = document.getElementById(btn.dataset.target);
            const showing = input.type === 'text';
            input.type    = showing ? 'password' : 'text';
            btn.textContent = showing ? 'Show' : 'Hide';
        });
    });

    // Live password-match indicator on the confirm field
    function checkPasswordMatch() {
        const pw   = document.getElementById('regPassword').value;
        const cfm  = document.getElementById('regConfirm').value;
        const hint = document.getElementById('pwMatchHint');
        document.getElementById('registerError').hidden = true;
        if (!cfm) { hint.hidden = true; return; }
        const match = pw === cfm;
        hint.hidden      = false;
        hint.textContent = match ? '✓ Passwords match' : '✗ Passwords do not match';
        hint.className   = 'pw-match-hint ' + (match ? 'pw-match-ok' : 'pw-match-err');
    }
    document.getElementById('regPassword').addEventListener('input', checkPasswordMatch);
    document.getElementById('regConfirm').addEventListener('input', checkPasswordMatch);
}

// ── Boot ───────────────────────────────────────────────────────────────────── //
document.addEventListener('DOMContentLoaded', init);
