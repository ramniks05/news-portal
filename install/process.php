<?php
/**
 * Kept for compatibility. Prefer posting to index.php.
 * Hostinger sometimes resets POSTs to process.php.
 */
require_once __DIR__ . '/install_runner.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

try {
    news_portal_run_install($_POST);
    header('Location: index.php?finish=success');
    exit;
} catch (Throwable $e) {
    http_response_code(200);
    echo news_portal_install_error_html($e->getMessage());
    exit;
}
