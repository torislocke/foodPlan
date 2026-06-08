<?php
http_response_code(500);
include __DIR__ . '/common/header.php';
?>
<main>
    <div class="thank-you-card">
        <h1>Server Error</h1>
        <p>Oops, the server is having an issue - please try later.</p>
        
    </div>
</main>
<?php require_once __DIR__ . "/common/footer.php"; ?>