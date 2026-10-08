<?php


require_once __DIR__ . '/common/header.php';

?>

    <!-- ── Day tabs ────────────────────────────────────────────────────────── -->
    <div class="day-tabs-wrapper">
        <div class="day-tabs" id="dayTabs" role="tablist">
            <!-- Rendered by JS -->
        </div>
    </div>

    <!-- ── Main content ────────────────────────────────────────────────────── -->
    <main class="planner-main">

        <!-- Meal grid for active day -->
        <section class="meal-grid" id="mealGrid" aria-live="polite">
            <!-- Rendered by JS -->
        </section>

        <!-- Daily nutrition summary -->
        <aside class="nutrition-panel">
            <div class="profile-card" id="profileCard">
                <!-- Rendered by JS -->
            </div>

            <h2 class="panel-title">Today's Nutrition</h2>
            <div id="nutritionSummary">
                <!-- Rendered by JS -->
            </div>

            <h2 class="panel-title micro-title">Vitamins &amp; Minerals</h2>
            <div id="microSummary">
                <!-- Rendered by JS -->
            </div>
        </aside>

    </main>

    <!-- ── Week summary ─────────────────────────────────────────────────────── -->
    <section class="week-summary-section">
        <button class="week-summary-toggle" id="weekSummaryToggle" aria-expanded="false">
            Week Overview ▾
        </button>
        <div class="week-summary-body" id="weekSummaryBody" hidden>
            <div id="weekSummaryTable"><!-- Rendered by JS --></div>
        </div>
    </section>

    <!-- ── Add food modal ──────────────────────────────────────────────────── -->
    <div class="modal-backdrop" id="foodModalBackdrop" hidden>
        <div class="modal" role="dialog" aria-modal="true" aria-labelledby="foodModalTitle" id="foodModal">
            <div class="modal-header">
                <h2 class="modal-title" id="foodModalTitle">Add Food</h2>
                <button class="modal-close" id="closeFoodModal" aria-label="Close">&#10005;</button>
            </div>

            <div class="modal-body">

                <!-- ── Tab bar ── -->
                <div class="modal-tab-bar">
                    <button type="button" class="modal-tab-btn active" data-tab="search">Search USDA</button>
                    <button type="button" class="modal-tab-btn" data-tab="myfoods">My Custom Foods</button>
                </div>

                <!-- ── Search tab ── -->
                <div id="searchPanel" class="modal-tab-panel">
                    <div class="search-row">
                        <input type="search" id="foodSearchInput" class="search-input"
                            placeholder="Search USDA database (e.g. chicken breast, oatmeal)…"
                            autocomplete="off" spellcheck="false">
                        <span class="search-spinner" id="searchSpinner" hidden>⟳</span>
                    </div>
                    <div id="searchResults" class="search-results" role="listbox">
                        <p class="search-hint">Type at least 2 characters. Your custom foods appear first.</p>
                    </div>
                </div>

                <!-- ── My Custom Foods tab ── -->
                <div id="myFoodsPanel" class="modal-tab-panel" hidden>

                    <!-- List of saved custom foods -->
                    <div class="mf-header">
                        <button type="button" class="btn btn-primary btn-sm" id="showCustomFormBtn">+ Create New Food</button>
                    </div>
                    <div id="customFoodsList" class="search-results">
                        <!-- Rendered by JS -->
                    </div>

                    <!-- Creation form (hidden until button clicked) -->
                    <div id="customFoodFormWrap" hidden>
                        <form id="customFoodForm" class="cf-form" novalidate>

                            <div class="cf-row cf-row-full">
                                <label for="cfName">Food Name <span class="cf-req">*</span></label>
                                <input type="text" id="cfName" name="food_name" required
                                    placeholder="e.g. Grandma's Banana Bread">
                            </div>

                            <div class="cf-row">
                                <label for="cfBrand">Brand / Source</label>
                                <input type="text" id="cfBrand" name="brand_name" placeholder="optional">
                            </div>

                            <div class="cf-pair">
                                <div class="cf-row">
                                    <label for="cfServingSize">Serving Size <span class="cf-req">*</span></label>
                                    <input type="number" id="cfServingSize" name="serving_size"
                                        value="100" min="0.1" step="any" required>
                                </div>
                                <div class="cf-row">
                                    <label for="cfServingUnit">Unit</label>
                                    <input type="text" id="cfServingUnit" name="serving_unit"
                                        value="g" placeholder="g, oz, cup…">
                                </div>
                            </div>

                            <div class="cf-section-label">Nutrition Facts — per serving</div>
                            <div class="cf-grid">
                                <div class="cf-row">
                                    <label for="cfCalories">Calories (kcal) <span class="cf-req">*</span></label>
                                    <input type="number" id="cfCalories" name="calories"
                                        min="0" step="1" required placeholder="0">
                                </div>
                                <div class="cf-row">
                                    <label for="cfProtein">Protein (g)</label>
                                    <input type="number" id="cfProtein" name="protein_g"
                                        min="0" step="0.1" placeholder="—">
                                </div>
                                <div class="cf-row">
                                    <label for="cfCarbs">Carbohydrates (g)</label>
                                    <input type="number" id="cfCarbs" name="carbs_g"
                                        min="0" step="0.1" placeholder="—">
                                </div>
                                <div class="cf-row">
                                    <label for="cfFat">Total Fat (g)</label>
                                    <input type="number" id="cfFat" name="fat_g"
                                        min="0" step="0.1" placeholder="—">
                                </div>
                                <div class="cf-row">
                                    <label for="cfFiber">Fiber (g)</label>
                                    <input type="number" id="cfFiber" name="fiber_g"
                                        min="0" step="0.1" placeholder="—">
                                </div>
                                <div class="cf-row">
                                    <label for="cfSodium">Sodium (mg)</label>
                                    <input type="number" id="cfSodium" name="sodium_mg"
                                        min="0" step="1" placeholder="—">
                                </div>
                                <div class="cf-row">
                                    <label for="cfSugar">Total Sugars (g)</label>
                                    <input type="number" id="cfSugar" name="sugar_g"
                                        min="0" step="0.1" placeholder="—">
                                </div>
                                <div class="cf-row">
                                    <label for="cfCholesterol">Cholesterol (mg)</label>
                                    <input type="number" id="cfCholesterol" name="cholesterol_mg"
                                        min="0" step="1" placeholder="—">
                                </div>
                                <div class="cf-row">
                                    <label for="cfSatFat">Saturated Fat (g)</label>
                                    <input type="number" id="cfSatFat" name="saturated_fat_g"
                                        min="0" step="0.1" placeholder="—">
                                </div>
                            </div>

                        </form>
                        <div class="cf-actions">
                            <button type="button" class="btn btn-secondary" id="cancelCustomFood">Back to List</button>
                            <button type="button" class="btn btn-primary" id="saveCustomFoodBtn">Save &amp; Select</button>
                        </div>
                    </div>
                </div>

                <!-- ── Selected food (shown after pick from either tab) ── -->
                <div id="selectedFoodPanel" class="selected-panel" hidden>
                    <div class="selected-header">
                        <div>
                            <p class="selected-name" id="selectedName"></p>
                            <p class="selected-meta" id="selectedMeta"></p>
                        </div>
                        <button class="btn-text" id="clearSelection">Change</button>
                    </div>

                    <div class="servings-row">
                        <label class="servings-label" for="servingsInput">
                            Servings
                            <span class="serving-unit" id="servingUnitLabel"></span>
                        </label>
                        <input type="number" id="servingsInput" class="servings-field"
                            value="1" min="0.25" max="20" step="0.25">
                    </div>

                    <div class="nutrient-preview" id="nutrientPreview"></div>
                </div>

            </div>

            <div class="modal-footer">
                <button class="btn btn-secondary" id="cancelFoodAdd">Cancel</button>
                <button class="btn btn-primary" id="confirmFoodAdd" disabled>Add to Plan</button>
            </div>
        </div>
    </div>

    <!-- ── Profile modal: body stats → BMI & calorie needs ──────────────────── -->
    <div class="modal-backdrop" id="profileModalBackdrop" hidden>
        <div class="modal" role="dialog" aria-modal="true" aria-labelledby="profileModalTitle" id="profileModal">
            <div class="modal-header">
                <h2 class="modal-title" id="profileModalTitle">My Profile</h2>
                <button class="modal-close" id="closeProfileModal" aria-label="Close">&#10005;</button>
            </div>

            <div class="modal-body">
                <p class="profile-intro">
                    Enter your details to see your BMI and how many calories you need each day
                    to lose, maintain or gain weight. Your choice becomes your daily calorie goal.
                </p>
                <form id="fitnessForm" class="fitness-form" novalidate>
                    <div class="goals-divider">
                        <span>Height, weight, age &amp; gender</span>
                        <div class="unit-toggle" role="radiogroup" aria-label="Units">
                            <label><input type="radio" name="units" value="imperial" checked> lb / ft</label>
                            <label><input type="radio" name="units" value="metric"> kg / cm</label>
                        </div>
                    </div>

                    <div class="fitness-grid">
                        <div class="cf-row" data-units="imperial">
                            <label for="fitFeet">Height</label>
                            <div class="fit-pair">
                                <div class="goal-input-wrap">
                                    <input type="number" id="fitFeet" min="3" max="8" step="1" placeholder="e.g. 5">
                                    <span class="goal-unit">ft</span>
                                </div>
                                <div class="goal-input-wrap">
                                    <input type="number" id="fitInches" min="0" max="11.9" step="0.5" placeholder="e.g. 7">
                                    <span class="goal-unit">in</span>
                                </div>
                            </div>
                        </div>
                        <div class="cf-row" data-units="metric" hidden>
                            <label for="fitCm">Height</label>
                            <div class="goal-input-wrap">
                                <input type="number" id="fitCm" min="100" max="250" step="0.5" placeholder="e.g. 170">
                                <span class="goal-unit">cm</span>
                            </div>
                        </div>

                        <div class="cf-row" data-units="imperial">
                            <label for="fitLb">Weight</label>
                            <div class="goal-input-wrap">
                                <input type="number" id="fitLb" min="66" max="660" step="0.5" placeholder="e.g. 155">
                                <span class="goal-unit">lb</span>
                            </div>
                        </div>
                        <div class="cf-row" data-units="metric" hidden>
                            <label for="fitKg">Weight</label>
                            <div class="goal-input-wrap">
                                <input type="number" id="fitKg" min="30" max="300" step="0.5" placeholder="e.g. 70">
                                <span class="goal-unit">kg</span>
                            </div>
                        </div>

                        <div class="cf-row">
                            <label for="fitAge">Age</label>
                            <div class="goal-input-wrap">
                                <input type="number" id="fitAge" min="18" max="100" step="1" placeholder="e.g. 30">
                                <span class="goal-unit">yrs</span>
                            </div>
                        </div>

                        <div class="cf-row">
                            <label for="fitGender">Gender</label>
                            <select id="fitGender" class="fit-select">
                                <option value="">Choose…</option>
                                <option value="female">Female</option>
                                <option value="male">Male</option>
                            </select>
                        </div>

                        <div class="cf-row cf-row-full">
                            <label for="fitActivity">Activity level</label>
                            <select id="fitActivity" class="fit-select">
                                <?php foreach (\Toril\Food\Service\FitnessService::ACTIVITY as $key => $a): ?>
                                    <option value="<?= $key ?>"><?= htmlspecialchars($a['label']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="auth-error" id="fitnessError" hidden></div>
                    <button type="button" class="btn btn-primary btn-sm" id="calcFitnessBtn">Calculate BMI &amp; calories</button>

                    <div id="fitnessResults" class="fitness-results" aria-live="polite" hidden>
                        <!-- Rendered by JS -->
                    </div>
                </form>
            </div>

            <div class="modal-footer">
                <button class="btn btn-secondary" id="cancelProfile">Cancel</button>
                <button class="btn btn-primary" id="saveProfileBtn">Save Profile</button>
            </div>
        </div>
    </div>

    <!-- ── Goals modal ─────────────────────────────────────────────────────── -->
    <div class="modal-backdrop" id="goalsModalBackdrop" hidden>
        <div class="modal" role="dialog" aria-modal="true" aria-labelledby="goalsModalTitle" id="goalsModal">
            <div class="modal-header">
                <h2 class="modal-title" id="goalsModalTitle">Daily Nutritional Goals</h2>
                <button class="modal-close" id="closeGoalsModal" aria-label="Close">&#10005;</button>
            </div>

            <div class="modal-body">
                <div class="profile-callout">
                    Not sure what calorie goal to use?
                    <button type="button" class="btn-text" id="goalsOpenProfile">Calculate it from your height, weight, age &amp; gender</button>
                </div>

                <div class="usda-callout">
                    <span class="usda-badge">USDA</span>
                    <span>Macros are auto-calculated from your calorie goal using the
                    <em>2020–2025 Dietary Guidelines</em> (20% protein · 50% carbs · 30% fat).
                    You can override any value manually.</span>
                </div>

                <form id="goalsForm" class="goals-form">

                    <!-- Calorie anchor -->
                    <div class="goal-row goal-row--calories">
                        <label for="goalCalories">
                            Calories
                            <span class="unit">(kcal / day)</span>
                        </label>
                        <input type="number" id="goalCalories" name="calories"
                            min="500" max="8000" step="50" value="2000">
                    </div>

                    <div class="goals-divider">
                        <span>Macronutrients — auto-calculated</span>
                        <button type="button" class="btn-text" id="resetUsda">Reset to guidelines</button>
                    </div>

                    <div class="goal-row">
                        <label for="goalProtein">
                            Protein
                            <span class="goal-hint" id="hintProtein">20% of calories</span>
                        </label>
                        <div class="goal-input-wrap">
                            <input type="number" id="goalProtein" name="protein_g"
                                min="0" max="500" step="1" value="100">
                            <span class="goal-unit">g</span>
                        </div>
                    </div>

                    <div class="goal-row">
                        <label for="goalCarbs">
                            Carbohydrates
                            <span class="goal-hint" id="hintCarbs">50% of calories</span>
                        </label>
                        <div class="goal-input-wrap">
                            <input type="number" id="goalCarbs" name="carbs_g"
                                min="0" max="1000" step="1" value="250">
                            <span class="goal-unit">g</span>
                        </div>
                    </div>

                    <div class="goal-row">
                        <label for="goalFat">
                            Total Fat
                            <span class="goal-hint" id="hintFat">30% of calories</span>
                        </label>
                        <div class="goal-input-wrap">
                            <input type="number" id="goalFat" name="fat_g"
                                min="0" max="500" step="1" value="67">
                            <span class="goal-unit">g</span>
                        </div>
                    </div>

                    <div class="goals-divider">
                        <span>Other targets</span>
                    </div>

                    <div class="goal-row">
                        <label for="goalFiber">
                            Fiber
                            <span class="goal-hint" id="hintFiber">14g per 1,000 kcal</span>
                        </label>
                        <div class="goal-input-wrap">
                            <input type="number" id="goalFiber" name="fiber_g"
                                min="0" max="200" step="1" value="28">
                            <span class="goal-unit">g</span>
                        </div>
                    </div>

                    <div class="goal-row">
                        <label for="goalSodium">
                            Sodium
                            <span class="goal-hint">2,300 mg recommended max</span>
                        </label>
                        <div class="goal-input-wrap">
                            <input type="number" id="goalSodium" name="sodium_mg"
                                min="0" max="10000" step="100" value="2300">
                            <span class="goal-unit">mg</span>
                        </div>
                    </div>

                </form>
            </div>

            <div class="modal-footer">
                <button class="btn btn-secondary" id="cancelGoals">Cancel</button>
                <button class="btn btn-primary" id="saveGoalsBtn">Save Goals</button>
            </div>
        </div>
    </div>

    <!-- ── Print view (hidden on screen, visible when printing) ────────────── -->
    <div id="printView">
        <div class="print-header">
            <div>
                <h1 class="print-title">Weekly Meal Plan</h1>
                <p class="print-week" id="printWeekLabel"></p>
            </div>
            <div class="print-meta">
                <p id="printUserName"></p>
                <p id="printGoalLine"></p>
            </div>
        </div>
        <div id="printDays"></div>
        <p class="print-footer">Generated by <?= htmlspecialchars(COMPANY_NAME) ?> &middot; <?= date('F j, Y') ?></p>
    </div>

<?php require_once __DIR__ . '/common/footer.php'; ?>