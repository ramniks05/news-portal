<?php
/**
 * One-time CLI: import live RSS demo posts as published.
 * Usage: php admin/seed_rss_import.php
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/common_functions.php';
require_once __DIR__ . '/../helpers/news_api_functions.php';

$category_id = (int)$conn->query("SELECT id FROM categories WHERE status = 1 ORDER BY id ASC LIMIT 1")->fetchColumn();
$author_id = (int)$conn->query("SELECT id FROM users ORDER BY id ASC LIMIT 1")->fetchColumn();

if ($category_id < 1 || $author_id < 1) {
    fwrite(STDERR, "Need at least one category and one user.\n");
    exit(1);
}

[$articles, $err] = news_api_fetch_articles('rss', '', 'bbc-asia', 8);
if ($err) {
    fwrite(STDERR, "Fetch failed: $err\n");
    exit(1);
}

$imported = 0;
foreach ($articles as $article) {
    $dup = $conn->prepare('SELECT id FROM posts WHERE title = :t LIMIT 1');
    $dup->execute([':t' => $article['title']]);
    if ($dup->fetch()) {
        echo "Skip (exists): {$article['title']}\n";
        continue;
    }
    $id = news_api_insert_post($conn, $article, $category_id, $author_id, 'published', true);
    if ($id > 0) {
        $imported++;
        echo "Imported #$id: {$article['title']}\n";
    }
}

echo "Done. Imported $imported posts into category #$category_id\n";
