<?php

declare(strict_types=1);

// Shared setup for the login/ pages. Each page handles its form here, before
// common/header.php sends any HTML, so redirects always work.

$appRoot = is_dir(dirname(__DIR__) . '/food/config')
    ? dirname(__DIR__)                    // local: food/ inside the project
    : dirname($_SERVER['DOCUMENT_ROOT']); // server: food/ beside public_html
require_once $appRoot . '/food/config/planner_bootstrap.php';

$pageStyles = ['assets/css/auth.css'];
$pageScripts = ['assets/js/auth.js'];
$metaRobots = 'noindex, nofollow';

/** Success / error / info messages from the previous request, plus the local test link. */
function auth_alerts(): string
{
    $html = '';
    foreach (['success' => 'status', 'info' => 'status', 'error' => 'alert'] as $type => $role) {
        if ($msg = take_flash($type)) {
            $html .= '<div class="auth-alert auth-alert--' . $type . '" role="' . $role . '">'
                . htmlspecialchars($msg) . '</div>';
        }
    }
    if ($link = take_flash('dev_link')) {
        $html .= '<div class="auth-alert auth-alert--dev" role="status">'
            . '<strong>Local testing:</strong> email not sent. Open the link instead: '
            . '<a href="' . htmlspecialchars($link) . '">' . htmlspecialchars($link) . '</a></div>';
    }
    return $html;
}

/** Remembers form input across the POST → redirect so users don't retype it. */
function old(string $field): string
{
    return htmlspecialchars((string) ($_SESSION['old_input'][$field] ?? ''));
}

function keep_old_input(array $fields): void
{
    $_SESSION['old_input'] = array_intersect_key($_POST, array_flip($fields));
}

function forget_old_input(): void
{
    unset($_SESSION['old_input']);
}
