<?php

declare(strict_types=1);

require_once __DIR__ . '/app/config/planner_bootstrap.php';

unset($_SESSION['user_id']);

header('Location: ' . BASE_URL . 'planning.php');
exit;
