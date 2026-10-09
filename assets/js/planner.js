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
    fitness:      null,     // BMI & calorie result for the saved profile
    microTargets: null,     // { basis, personal, nutrients: [{ key, label, unit, group, target, upper }] }
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
    renderCustomFoodMicroFields();
    await loadFitness();
    render();
}

// ── API helpers ────────────────────────────────────────────────────────────── //
const api = CONFIG.baseUrl + 'api/';

async function loadGoals() {
    try {
        const r = await fetch(api + 'goals.php');
        const { micronutrient_targets, ...goals } = await r.json();
        state.goals        = goals;
        state.microTargets = micronutrient_targets || null;
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
    let micros = {};
    if (f.micronutrients) {
        if (typeof f.micronutrients === 'string') {
            try { micros = JSON.parse(f.micronutrients); } catch { micros = {}; }
        } else {
            micros = f.micronutrients;
        }
    }

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
            ...micros,
        },
    };
}

// ── Render ─────────────────────────────────────────────────────────────────── //
function render() {
    renderProfileCard();
    renderWeekNav();
    renderDayTabs();
    renderMealGrid();
    renderNutritionPanel();
    renderMicronutrientPanel();
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

// Vitamins & minerals for the active day vs. recommended daily intake.
// green = target met, yellow = below target, red = above the upper limit.
function renderMicronutrientPanel() {
    const el = document.getElementById('microSummary');
    const t  = state.microTargets;
    if (!t) { el.innerHTML = ''; return; }

    const sum      = state.summaries[state.activeDay] || {};
    const totals   = sum.micros || {};
    const reported = sum.micro_reported || {};
    const foods    = sum.food_count || 0;

    const rows = t.nutrients.map(n => {
        const val    = totals[n.key] || 0;
        const pct    = val / n.target;
        const status = n.upper && val > n.upper ? 'warning' : pct >= 1 ? 'good' : 'caution';
        // Some foods that do carry vitamin data didn't report this nutrient
        const partial = (reported[n.key] || 0) < (sum.micro_food_count || 0);
        return { ...n, val, pct, status, partial };
    });

    const met  = rows.filter(r => r.status === 'good').length;
    const over = rows.filter(r => r.status === 'warning').length;
    const noData = foods - (sum.micro_food_count || 0);

    const fmt = v => v === 0 ? '0' : v >= 100 ? Math.round(v).toLocaleString() : v >= 10 ? v.toFixed(0) : v >= 1 ? v.toFixed(1) : v.toFixed(2);
    const icon = { good: '✓', caution: '!', warning: '⚠' };
    const tip  = r => r.status === 'warning'
        ? `Above the ${fmt(r.upper)} ${r.unit} upper limit`
        : r.status === 'good' ? 'Daily target met' : `${fmt(Math.max(0, r.target - r.val))} ${r.unit} to go`;

    const group = (key, title) => `
        <details class="micro-group" open>
            <summary>${title}</summary>
            ${rows.filter(r => r.group === key).map(r => `
            <div class="micro-row micro-${r.status}" title="${tip(r)}">
                <div class="micro-label">
                    <span class="micro-icon" aria-hidden="true">${icon[r.status]}</span>
                    <span class="micro-name">${esc(r.label)}${r.partial
                        ? `<sup class="micro-partial" title="Reported by ${reported[r.key] || 0} of ${sum.micro_food_count} foods">*</sup>` : ''}</span>
                    <span class="micro-value">${fmt(r.val)} / ${fmt(r.target)} ${r.unit}</span>
                    <span class="micro-pct">${Math.round(r.pct * 100)}%</span>
                </div>
                <div class="progress-bar-track">
                    <div class="progress-bar-fill ${r.status === 'warning' ? 'over' : r.status === 'caution' ? 'warning' : ''}"
                         style="width:${Math.min(100, Math.round(r.pct * 100))}%"></div>
                </div>
                <span class="sr-only">${tip(r)}</span>
            </div>`).join('')}
        </details>`;

    el.innerHTML = `
        <div class="micro-summary">
            <span class="micro-count"><strong>${met}</strong> of ${rows.length} daily targets met</span>
            ${over ? `<span class="micro-over">⚠ ${over} above upper limit</span>` : ''}
        </div>
        <p class="micro-basis">${esc(t.basis)}${t.personal ? ''
            : ` · <button type="button" class="btn-text" data-open-profile>add your profile</button> for personal targets`}</p>
        ${foods === 0 ? '<p class="micro-note">Add foods to this day to see how close you are to each target.</p>' : ''}
        ${noData > 0 ? `<p class="micro-note">${noData} of ${foods} food${foods > 1 ? 's have' : ' has'} no vitamin &amp; mineral data
            (custom foods, or foods added before this feature), so totals may be low.</p>` : ''}
        ${group('vitamins', 'Vitamins')}
        ${group('minerals', 'Minerals')}
        ${rows.some(r => r.partial)
            ? '<p class="micro-footnote">* Not reported by every food today, so the total may be low.</p>' : ''}`;
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

// Build the vitamin/mineral inputs in the custom-food form from the same
// nutrient list used for daily targets (so labels/units/grouping stay in sync).
function renderCustomFoodMicroFields() {
    const grid = document.getElementById('cfMicroGrid');
    const nutrients = state.microTargets?.nutrients;
    if (!grid || !nutrients) return;

    const section = (title, key) => `
        <div class="cf-section-label">${esc(title)} — per serving (optional)</div>
        <div class="cf-grid">
            ${nutrients.filter(n => n.group === key).map(n => `
            <div class="cf-row">
                <label for="cf_micro_${n.key}">${esc(n.label)} (${esc(n.unit)})</label>
                <input type="number" id="cf_micro_${n.key}" name="micro_${n.key}"
                    min="0" step="any" placeholder="—">
            </div>`).join('')}
        </div>`;

    grid.innerHTML = section('Vitamins', 'vitamins') + section('Minerals', 'minerals');
}

function collectCustomFoodMicros() {
    const nutrients = state.microTargets?.nutrients || [];
    const micros = {};
    nutrients.forEach(n => {
        const el = document.getElementById(`cf_micro_${n.key}`);
        if (el && el.value !== '') micros[n.key] = parseFloat(el.value);
    });
    return micros;
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
    const cfServingUnitEl = document.getElementById('cfServingUnit');
    if (/^\d+\.?\d*$/.test(cfServingUnitEl.value.trim())) {
        cfServingUnitEl.focus();
        toast('Unit should be a measurement like g, oz or cup — not a number.');
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
        micronutrients:  collectCustomFoodMicros(),
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
            toast(r.error || 'Could not add food. Please try again.');
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

// ── Fitness profile (BMI & calorie needs) ──────────────────────────────────── //
const CM_PER_IN = 2.54;
const LB_PER_KG = 2.20462;

let fitnessChoice = null;       // weight_goal key picked from the results, e.g. 'maintain'
let fitnessUnits  = 'imperial'; // which set of height/weight fields is showing

function setUnits(units) {
    fitnessUnits = units;
    document.querySelector(`#fitnessForm input[name=units][value=${units}]`).checked = true;
    document.querySelectorAll('#fitnessForm [data-units]').forEach(el => {
        el.hidden = el.dataset.units !== units;
    });
    try { localStorage.setItem('plannerUnits', units); } catch {}
}

// Switching units carries the entered values across
function convertFitnessUnits(units) {
    const p = readFitnessForm();
    setUnits(units);
    fillFitnessMeasurements(p.height_cm, p.weight_kg);
}

function fillFitnessMeasurements(heightCm, weightKg) {
    const h = parseFloat(heightCm), w = parseFloat(weightKg);
    const totalIn = h / CM_PER_IN;
    document.getElementById('fitCm').value     = h ? Math.round(h * 2) / 2 : '';
    document.getElementById('fitKg').value     = w ? Math.round(w * 2) / 2 : '';
    document.getElementById('fitFeet').value   = h ? Math.floor(totalIn / 12) : '';
    document.getElementById('fitInches').value = h ? Math.round((totalIn % 12) * 2) / 2 : '';
    document.getElementById('fitLb').value     = w ? Math.round(w * LB_PER_KG) : '';
}

function fillFitnessForm(g) {
    fillFitnessMeasurements(g.height_cm, g.weight_kg);
    document.getElementById('fitAge').value      = g.age || '';
    document.getElementById('fitGender').value   = g.gender || '';
    document.getElementById('fitActivity').value = g.activity_level || 'sedentary';
    fitnessChoice = g.weight_goal || null;
}

// Always returns metric values for the API
function readFitnessForm() {
    const num = id => parseFloat(document.getElementById(id).value);
    let height_cm, weight_kg;
    if (fitnessUnits === 'metric') {
        height_cm = num('fitCm');
        weight_kg = num('fitKg');
    } else {
        const ft = num('fitFeet'), inch = num('fitInches') || 0;
        height_cm = ft ? (ft * 12 + inch) * CM_PER_IN : NaN;
        weight_kg = num('fitLb') / LB_PER_KG;
    }
    return {
        height_cm: Number.isFinite(height_cm) ? Math.round(height_cm * 10) / 10 : null,
        weight_kg: Number.isFinite(weight_kg) ? Math.round(weight_kg * 10) / 10 : null,
        age:       parseInt(document.getElementById('fitAge').value, 10) || null,
        gender:    document.getElementById('fitGender').value || null,
        activity:  document.getElementById('fitActivity').value,
    };
}

// Highlights empty required fields and names them; returns true when all are filled
function checkFitnessFields() {
    const required = fitnessUnits === 'metric'
        ? [['fitCm', 'height'], ['fitKg', 'weight']]
        : [['fitFeet', 'height (feet)'], ['fitLb', 'weight']];
    required.push(['fitAge', 'age'], ['fitGender', 'gender']);

    const missing = [];
    required.forEach(([id, label]) => {
        const el    = document.getElementById(id);
        const empty = el.value.trim() === '';
        el.classList.toggle('field-missing', empty);
        el.setAttribute('aria-invalid', empty);
        if (empty) missing.push(label);
    });

    const errEl = document.getElementById('fitnessError');
    if (missing.length) {
        const list = missing.length === 1 ? missing[0]
            : missing.slice(0, -1).join(', ') + ' and ' + missing.at(-1);
        errEl.textContent = `Please enter your ${list}.`;
        errEl.hidden = false;
        document.getElementById(required.find(([, l]) => l === missing[0])[0]).focus();
    }
    return missing.length === 0;
}

function clearFitnessMissing() {
    document.querySelectorAll('#fitnessForm .field-missing').forEach(el => {
        el.classList.remove('field-missing');
        el.removeAttribute('aria-invalid');
    });
}

function isFitnessComplete(p) {
    return p.height_cm && p.weight_kg && p.age && p.gender;
}

async function fetchFitness(profile) {
    const r = await fetch(api + 'fitness.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(profile),
    });
    const d = await r.json();
    if (!r.ok) throw new Error(d.error || 'Could not calculate.');
    return d;
}

// Profile as stored with the goals → the shape fitness.php expects
function savedProfile(g = state.goals) {
    return {
        height_cm: parseFloat(g.height_cm) || null,
        weight_kg: parseFloat(g.weight_kg) || null,
        age:       parseInt(g.age, 10) || null,
        gender:    g.gender || null,
        activity:  g.activity_level || 'sedentary',
    };
}

async function loadFitness() {
    const p = savedProfile();
    state.fitness = null;
    if (!isFitnessComplete(p)) return;
    try { state.fitness = await fetchFitness(p); } catch {}
}

async function calcFitness({ quiet = false } = {}) {
    const errEl   = document.getElementById('fitnessError');
    const profile = readFitnessForm();
    errEl.hidden  = true;

    if (!isFitnessComplete(profile)) {
        if (!quiet) checkFitnessFields();
        return;
    }

    const btn = document.getElementById('calcFitnessBtn');
    btn.disabled = true;
    try {
        renderFitnessResults(await fetchFitness(profile));
    } catch (e) {
        errEl.textContent = e.message || 'Network error. Please try again.';
        errEl.hidden = false;
    } finally {
        btn.disabled = false;
    }
}

function renderFitnessResults(d) {
    const el = document.getElementById('fitnessResults');
    const weekly = lb => lb === 0 ? 'keep current weight'
        : `${lb > 0 ? '+' : '−'}${Math.abs(lb)} lb / week`;

    el.innerHTML = `
        <div class="bmi-card bmi-${d.bmi_status}">
            <div class="bmi-value">${d.bmi}</div>
            <div>
                <div class="bmi-label">BMI · ${esc(d.bmi_category)}</div>
                <div class="bmi-meta">Resting burn ${d.bmr.toLocaleString()} kcal ·
                    with activity ${d.tdee.toLocaleString()} kcal / day</div>
            </div>
        </div>
        <p class="plan-prompt">Choose a calorie target. It becomes your daily goal when you save.</p>
        <div class="plan-options" role="radiogroup" aria-label="Weight goal">
            ${d.plans.map(p => `
                <button type="button" class="plan-option ${p.key === fitnessChoice ? 'selected' : ''}"
                        role="radio" aria-checked="${p.key === fitnessChoice}"
                        data-key="${p.key}" data-calories="${p.calories}">
                    <span class="plan-label">${esc(p.label)}</span>
                    <span class="plan-cal">${p.calories.toLocaleString()} kcal</span>
                    <span class="plan-rate">${weekly(p.weekly_lb)}</span>
                    ${p.floored ? `<span class="plan-floor">Limited to the ${d.min_calories.toLocaleString()} kcal safe minimum</span>` : ''}
                </button>`).join('')}
        </div>`;
    el.hidden = false;

    el.querySelectorAll('.plan-option').forEach(b => b.addEventListener('click', () => choosePlan(b)));
}

function choosePlan(btn) {
    fitnessChoice = btn.dataset.key;
    document.querySelectorAll('#fitnessResults .plan-option').forEach(b => {
        const on = b === btn;
        b.classList.toggle('selected', on);
        b.setAttribute('aria-checked', on);
    });
}

// ── Profile card (sidebar) & modal ─────────────────────────────────────────── //
function renderProfileCard() {
    const el = document.getElementById('profileCard');
    const f  = state.fitness;

    if (!f) {
        el.className = 'profile-card profile-card--empty';
        el.innerHTML = `
            <p class="profile-card-title">Personalize your calorie goal</p>
            <p class="profile-card-text">Enter your height, weight, age and gender to see your BMI
                and the calories you need to lose, maintain or gain weight.</p>
            <button type="button" class="btn btn-primary btn-sm" data-open-profile>Enter my details</button>`;
        return;
    }

    const g      = state.goals;
    const plan   = f.plans.find(p => p.key === g.weight_goal);
    const inches = Math.round(parseFloat(g.height_cm) / CM_PER_IN);
    const stats  = `${Math.floor(inches / 12)}′${inches % 12}″ · `
        + `${Math.round(parseFloat(g.weight_kg) * LB_PER_KG)} lb · ${g.age} yrs · ${g.gender === 'male' ? 'Male' : 'Female'}`;

    el.className = 'profile-card';
    el.innerHTML = `
        <div class="profile-card-head">
            <span class="bmi-chip bmi-${f.bmi_status}">BMI ${f.bmi}</span>
            <span class="profile-card-cat">${esc(f.bmi_category)}</span>
            <button type="button" class="btn-text" data-open-profile>Edit</button>
        </div>
        <p class="profile-card-text">${stats}</p>
        <p class="profile-card-text">${plan
            ? `Goal: <strong>${esc(plan.label)}</strong> · ${plan.calories.toLocaleString()} kcal / day`
            : `Maintenance: <strong>${f.tdee.toLocaleString()} kcal / day</strong>`}</p>`;
}

function openProfileModal() {
    fillFitnessForm(state.goals);
    clearFitnessMissing();
    document.getElementById('fitnessError').hidden   = true;
    document.getElementById('fitnessResults').hidden = true;
    calcFitness({ quiet: true });
    showModal('profileModalBackdrop');
}

async function handleSaveProfile() {
    const errEl   = document.getElementById('fitnessError');
    const profile = readFitnessForm();
    if (!isFitnessComplete(profile)) {
        checkFitnessFields();
        return;
    }

    const data = {
        ...state.goals,
        height_cm:      profile.height_cm,
        weight_kg:      profile.weight_kg,
        age:            profile.age,
        gender:         profile.gender,
        activity_level: profile.activity,
        weight_goal:    fitnessChoice,
    };

    // The chosen calorie target becomes the goal; macros follow USDA guidelines
    const picked = document.querySelector('#fitnessResults .plan-option.selected');
    if (picked) {
        const cal = parseInt(picked.dataset.calories, 10);
        const { sodium_mg, ...macros } = calcUsda(cal);
        Object.assign(data, { calories: cal }, macros);
    }

    const btn = document.getElementById('saveProfileBtn');
    btn.disabled = true;
    try {
        await saveGoals(data);
        await loadGoals(); // also refreshes the vitamin & mineral targets
        await loadFitness();
        hideModal('profileModalBackdrop');
        render();
        toast(picked ? `Profile saved. Daily goal set to ${data.calories.toLocaleString()} kcal.` : 'Profile saved!');
    } catch {
        errEl.textContent = 'Could not save. Please try again.';
        errEl.hidden = false;
    } finally {
        btn.disabled = false;
    }
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
    // Keep the saved profile; a hand-edited calorie goal no longer matches a weight-goal plan
    const g = state.goals;
    Object.assign(data, {
        height_cm:      g.height_cm ?? null,
        weight_kg:      g.weight_kg ?? null,
        age:            g.age ?? null,
        gender:         g.gender ?? null,
        activity_level: g.activity_level ?? null,
        weight_goal:    data.calories === parseFloat(g.calories) ? (g.weight_goal ?? null) : null,
    });
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

    // Fitness profile → BMI & calorie targets
    let savedUnits = 'imperial';
    try { savedUnits = localStorage.getItem('plannerUnits') || 'imperial'; } catch {}
    setUnits(savedUnits === 'metric' ? 'metric' : 'imperial');
    document.querySelectorAll('#fitnessForm input[name=units]').forEach(r => {
        r.addEventListener('change', () => convertFitnessUnits(r.value));
    });
    document.getElementById('calcFitnessBtn').addEventListener('click', () => calcFitness());
    document.getElementById('fitnessForm').addEventListener('input', e => {
        if (e.target.classList.contains('field-missing') && e.target.value.trim() !== '') {
            e.target.classList.remove('field-missing');
            e.target.removeAttribute('aria-invalid');
        }
    });

    // Profile modal — opened from the header, the sidebar card, or the goals modal
    document.getElementById('profileBtn').addEventListener('click', openProfileModal);
    ['profileCard', 'microSummary'].forEach(id => {
        document.getElementById(id).addEventListener('click', e => {
            if (e.target.closest('[data-open-profile]')) openProfileModal();
        });
    });
    document.getElementById('goalsOpenProfile').addEventListener('click', () => {
        hideModal('goalsModalBackdrop');
        openProfileModal();
    });
    document.getElementById('closeProfileModal').addEventListener('click', () => hideModal('profileModalBackdrop'));
    document.getElementById('cancelProfile').addEventListener('click', () => hideModal('profileModalBackdrop'));
    document.getElementById('saveProfileBtn').addEventListener('click', handleSaveProfile);
    document.getElementById('fitnessForm').addEventListener('keydown', e => {
        if (e.key === 'Enter' && e.target.tagName === 'INPUT') { e.preventDefault(); calcFitness(); }
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
}

// ── Boot ───────────────────────────────────────────────────────────────────── //
document.addEventListener('DOMContentLoaded', init);
