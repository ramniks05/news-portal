<?php
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/helpers/query_functions.php';

header('Content-Type: application/xml; charset=utf-8');

$urls = [];
$urls[] = ['loc' => BASE_URL . '/', 'priority' => '1.0', 'changefreq' => 'hourly'];
$urls[] = ['loc' => BASE_URL . '/about-us', 'priority' => '0.4', 'changefreq' => 'monthly'];
$urls[] = ['loc' => BASE_URL . '/contact-us', 'priority' => '0.5', 'changefreq' => 'monthly'];
$urls[] = ['loc' => BASE_URL . '/privacy-policy', 'priority' => '0.3', 'changefreq' => 'yearly'];
$urls[] = ['loc' => BASE_URL . '/terms', 'priority' => '0.3', 'changefreq' => 'yearly'];
$urls[] = ['loc' => BASE_URL . '/rss', 'priority' => '0.4', 'changefreq' => 'hourly'];

$cats = $conn->query("SELECT slug, updated_at FROM categories WHERE status = 1")->fetchAll(PDO::FETCH_ASSOC);
foreach ($cats as $cat) {
    $urls[] = [
        'loc' => category_url($cat['slug']),
        'priority' => '0.7',
        'changefreq' => 'daily',
        'lastmod' => !empty($cat['updated_at']) ? date('c', strtotime($cat['updated_at'])) : null,
    ];
}

$posts = $conn->query("SELECT slug, updated_at, published_at FROM posts WHERE status = 'published' ORDER BY COALESCE(published_at, created_at) DESC LIMIT 5000")->fetchAll(PDO::FETCH_ASSOC);
foreach ($posts as $post) {
    $lm = $post['updated_at'] ?: $post['published_at'];
    $urls[] = [
        'loc' => article_url($post['slug']),
        'priority' => '0.8',
        'changefreq' => 'weekly',
        'lastmod' => $lm ? date('c', strtotime($lm)) : null,
    ];
}

$tags = $conn->query("SELECT slug FROM tags ORDER BY id DESC LIMIT 1000")->fetchAll(PDO::FETCH_ASSOC);
foreach ($tags as $tag) {
    $urls[] = [
        'loc' => tag_url($tag['slug']),
        'priority' => '0.5',
        'changefreq' => 'daily',
    ];
}

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>

<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach ($urls as $u): ?>
  <url>
    <loc><?= htmlspecialchars($u['loc'], ENT_XML1) ?></loc>
    <?php if (!empty($u['lastmod'])): ?><lastmod><?= $u['lastmod'] ?></lastmod><?php endif; ?>
    <changefreq><?= $u['changefreq'] ?></changefreq>
    <priority><?= $u['priority'] ?></priority>
  </url>
<?php endforeach; ?>
</urlset>
