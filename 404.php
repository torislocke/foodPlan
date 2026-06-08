<?php
http_response_code(404);
include __DIR__ . '/common/header.php';
?>
<main>
    <div class="thank-you-card">
        <h1>Page Not Found</h1>
        <p>Oops, the page you’re looking for doesn’t exist.</p>
        <a href="<?= BASE_URL; ?>/index.php"
            class="nav-item <?= $cur_page === 'index.php' ? 'active' : '' ?>"
            title="Home - <?= COMPANY_NAME ?>">
            Return Home
        </a>
    </div>
</main>
<?php require_once __DIR__ . "/common/footer.php"; ?>