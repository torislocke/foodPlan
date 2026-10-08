<?php

declare(strict_types=1);

$appRoot = is_dir(dirname(__DIR__) . '/food/config')
    ? dirname(__DIR__)                    // local: food/ inside the project
    : dirname($_SERVER['DOCUMENT_ROOT']); // server: food/ beside public_html
require_once $appRoot . '/food/config/planner_bootstrap.php';

/**
 * Set by food/config/config.php and planner_bootstrap.php:
 * @var string     $cur_page
 * @var string     $pageTitle
 * @var string     $metaDescription
 * @var string     $canonicalUrl
 * @var array|null $currentUser
 * @var bool|null  $isPlannerPage  optional override set by the including page
 * Optional, set by the including page:
 * @var string[]   $pageStyles     extra stylesheets (paths relative to BASE_URL)
 * @var string[]   $pageScripts    extra scripts, loaded by footer.php
 * @var string     $metaRobots     e.g. 'noindex, nofollow' for account pages
 */

// Planner-only controls (week nav, print, goals, profile) are bound by
// planner.js, which only loads on the planning page. Pages may override.
$isPlannerPage ??= $cur_page === 'planning';

$navItems = [
    'index'    => ['label' => 'Home',         'href' => BASE_URL],
    'planning' => ['label' => 'Meal Planner', 'href' => BASE_URL . 'planning.php'],
];

// Account pages live in login/; send people back to this page after signing in
$isAuthPage  = str_contains(str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? ''), '/login/');
$loginReturn = $isAuthPage ? '' : '?redirect=' . urlencode($cur_page . '.php');

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> – <?= htmlspecialchars(COMPANY_NAME) ?></title>
    <meta name="description" content="<?= htmlspecialchars($metaDescription) ?>">
    <link rel="canonical" href="<?= htmlspecialchars($canonicalUrl) ?>">
    <?php if (!empty($metaRobots)): ?>
        <meta name="robots" content="<?= htmlspecialchars($metaRobots) ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/planner.css">
    <?php foreach ($pageStyles ?? [] as $style): ?>
        <link rel="stylesheet" href="<?= BASE_URL . htmlspecialchars($style) ?>">
    <?php endforeach; ?>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/print.css" media="print">
</head>

<body class="page-<?= htmlspecialchars($cur_page) ?>">

    <a class="skip-link" href="#content">Skip to content</a>

    <!-- ── Header ──────────────────────────────────────────────────────────── -->
    <header class="site-header">
        <div class="header-inner">
            <a class="logo" href="<?= BASE_URL ?>" title="Home - <?= htmlspecialchars(COMPANY_NAME) ?>">
                <span class="logo-icon">🥦</span>
                <span class="logo-text"><?= htmlspecialchars(COMPANY_NAME) ?></span>
            </a>

            <nav class="site-nav" aria-label="Main navigation">
                <?php foreach ($navItems as $page => $item): ?>
                    <a href="<?= $item['href'] ?>"
                        class="site-nav-link <?= $cur_page === $page ? 'active' : '' ?>"
                        <?= $cur_page === $page ? 'aria-current="page"' : '' ?>>
                        <?= htmlspecialchars($item['label']) ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <?php if ($isPlannerPage): ?>
                <nav class="week-nav" aria-label="Week navigation">
                    <button class="nav-btn" id="prevWeek" aria-label="Previous week">&#8249;</button>
                    <span id="weekLabel" class="week-label">Loading…</span>
                    <button class="nav-btn" id="nextWeek" aria-label="Next week">&#8250;</button>
                </nav>
            <?php endif; ?>

            <div class="header-actions">
                <?php if ($isPlannerPage): ?>
                    <button class="btn btn-outline" id="printBtn" title="Print meal plan">&#128438; Print</button>
                    <button class="btn btn-outline" id="profileBtn">&#128207; Profile</button>
                    <button class="btn btn-outline" id="goalsBtn">&#9881; Goals</button>
                <?php endif; ?>

                <?php if ($currentUser): ?>
                    <div class="auth-user">
                        <span class="user-greeting">&#128100; <?= htmlspecialchars($currentUser['first_name'] ?: $currentUser['name']) ?></span>
                        <a class="btn-text auth-logout" href="<?= BASE_URL ?>login/logout.php">Log out</a>
                    </div>
                <?php else: ?>
                    <div class="auth-links">
                        <a class="btn btn-outline btn-sm" href="<?= BASE_URL ?>login/login.php<?= $loginReturn ?>">Log In</a>
                        <a class="btn btn-primary btn-sm" href="<?= BASE_URL ?>login/register.php<?= $loginReturn ?>">Sign Up</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <div id="content"></div>

    <?php if (!$isAuthPage): // account pages show their own messages ?>
        <?php foreach (['success', 'info', 'error'] as $flashType): ?>
            <?php if ($flashMsg = take_flash($flashType)): ?>
                <div class="site-flash site-flash--<?= $flashType ?>" role="status"><?= htmlspecialchars($flashMsg) ?></div>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>
