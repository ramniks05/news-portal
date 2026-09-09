<?php
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/helpers/query_functions.php';

header('Content-Type: application/rss+xml; charset=utf-8');

$site_name = htmlspecialchars(get_config('site_name', SITE_NAME), ENT_XML1);
$site_desc = htmlspecialchars(get_config('site_description', 'Latest news updates'), ENT_XML1);
$posts = get_latest_posts(30, 0);

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>

<rss version="2.0">
  <channel>
    <title><?= $site_name ?></title>
    <link><?= htmlspecialchars(BASE_URL, ENT_XML1) ?></link>
    <description><?= $site_desc ?></description>
    <language>en</language>
    <lastBuildDate><?= date(DATE_RSS) ?></lastBuildDate>
    <?php foreach ($posts as $post): ?>
      <item>
        <title><?= htmlspecialchars($post['title'], ENT_XML1) ?></title>
        <link><?= htmlspecialchars(article_url($post['slug']), ENT_XML1) ?></link>
        <guid><?= htmlspecialchars(article_url($post['slug']), ENT_XML1) ?></guid>
        <pubDate><?= date(DATE_RSS, strtotime($post['published_at'] ?: $post['created_at'])) ?></pubDate>
        <description><?= htmlspecialchars($post['summary'] ?? '', ENT_XML1) ?></description>
        <category><?= htmlspecialchars($post['category_name'] ?? 'News', ENT_XML1) ?></category>
      </item>
    <?php endforeach; ?>
  </channel>
</rss>
