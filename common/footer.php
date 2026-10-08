<?php

declare(strict_types=1);

/**
 * Set by common/header.php, which must be included first:
 * @var bool       $isPlannerPage
 * @var array|null $currentUser
 */

?>

    <!-- ── Footer ──────────────────────────────────────────────────────────── -->
    <footer class="site-footer">
        <div class="footer-inner">

            <section class="footer-legend" aria-labelledby="legendTitle">
                <h2 class="footer-heading" id="legendTitle">Reading your daily totals</h2>
                <ul class="legend-list">
                    <li class="legend-item legend-good">
                        <span class="legend-swatch" aria-hidden="true"></span>
                        <span><strong>Green — Good planning.</strong>
                            You're meeting the recommended intake for vitamins, minerals, fiber and fluids,
                            and staying within limits.</span>
                    </li>
                    <li class="legend-item legend-caution">
                        <span class="legend-swatch" aria-hidden="true"></span>
                        <span><strong>Yellow — Caution.</strong>
                            Below a daily target, or getting close to a limit. Adjust your plan.</span>
                    </li>
                    <li class="legend-item legend-warning">
                        <span class="legend-swatch" aria-hidden="true"></span>
                        <span><strong>Red — Warning.</strong>
                            Over your calorie goal or a daily limit for saturated fat, added sugars or sodium.</span>
                    </li>
                </ul>
            </section>

            <section class="footer-about">
                <h2 class="footer-heading">About the targets</h2>
                <p>
                    Recommended intakes follow the
                    <a href="https://www.dietaryguidelines.gov/" target="_blank" rel="noopener">Dietary Guidelines for Americans, 2020–2025</a>
                    and FDA Daily Values. Nutrition data comes from
                    <a href="https://fdc.nal.usda.gov/" target="_blank" rel="noopener">USDA FoodData Central</a>.
                </p>
                <p class="footer-disclaimer">
                    For general planning only, not medical advice. Ask your doctor or a registered
                    dietitian about your own needs.
                </p>
            </section>

            <nav class="footer-nav" aria-label="Footer navigation">
                <h2 class="footer-heading">Links</h2>
                <a href="<?= BASE_URL ?>">Home</a>
                <a href="<?= BASE_URL ?>planning.php">Meal Planner</a>
                <?php if ($currentUser): ?>
                    <a href="<?= BASE_URL ?>login/logout.php">Log out</a>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>login/login.php">Log In</a>
                    <a href="<?= BASE_URL ?>login/register.php">Create an account</a>
                <?php endif; ?>
            </nav>

        </div>

        <p class="footer-copy">
            &copy; <?= date('Y') ?> <?= htmlspecialchars(COMPANY_NAME) ?>. All rights reserved.
        </p>
    </footer>

    <?php if ($isPlannerPage): ?>
        <!-- ── Toast notifications ─────────────────────────────────────────── -->
        <div id="toast" class="toast" aria-live="polite" hidden></div>

        <!-- Pass PHP config to JS -->
        <script>
            const CONFIG = {
                baseUrl: '<?= BASE_URL ?>',
                today: '<?= date('Y-m-d') ?>',
                csrf: '<?= $_SESSION['csrf_token'] ?>',
                user: <?= $currentUser ? json_encode(['id' => $currentUser['id'], 'name' => $currentUser['name']]) : 'null' ?>
            };
        </script>
        <script src="<?= BASE_URL ?>assets/js/planner.js"></script>
    <?php endif; ?>
    <?php foreach ($pageScripts ?? [] as $script): ?>
        <script src="<?= BASE_URL . htmlspecialchars($script) ?>"></script>
    <?php endforeach; ?>
</body>

</html>
