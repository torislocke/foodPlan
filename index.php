<?php

declare(strict_types=1);

require_once __DIR__ . '/common/header.php';

?>

    <main class="home-main">
        <h1>Meal Planning Site to meet your nutritional goals.</h1>
        <p class="home-intro">
            Build a weekly meal plan using nutrition information from the USDA food database,
            thousands of recipes, or foods and recipes you create yourself. See your total calories
            and nutrition for each day, and how each recipe serving fits your daily goals. Adjust
            your plan until you reach the recommended daily intake of vitamins, fiber and fluids
            while keeping saturated fat, sugars and sodium in check.
        </p>
        <div class="home-actions">
            <a class="btn btn-primary" href="<?= BASE_URL ?>planning.php">Start Planning</a>
            <?php if (!$currentUser): ?>
                <a class="btn btn-secondary" href="<?= BASE_URL ?>login/register.php">Create a free account</a>
            <?php endif; ?>
        </div>
    </main>

<?php require_once __DIR__ . '/common/footer.php'; ?>
