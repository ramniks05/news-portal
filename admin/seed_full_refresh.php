<?php
/**
 * Full demo content refresh via CLI.
 * php admin/seed_full_refresh.php
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../helpers/common_functions.php';
require_once __DIR__ . '/../helpers/news_api_functions.php';

$author_id = (int)$conn->query('SELECT id FROM users ORDER BY id ASC LIMIT 1')->fetchColumn();
$result = news_api_full_site_refresh($conn, $author_id, 6);
echo ($result['ok'] ? "OK: " : "FAIL: ") . $result['message'] . "\n";
echo "imported={$result['imported']} categories={$result['categories']} breaking={$result['breaking']}\n";
exit($result['ok'] ? 0 : 1);
