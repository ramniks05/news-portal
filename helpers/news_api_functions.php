<?php
/**
 * Demo news API helpers — RSS (no key) + NewsAPI.org + GNews.
 * Maps into existing posts columns only (no schema changes).
 */

function news_api_http_raw($url, $timeout = 15)
{
    $body = false;
    $code = 0;
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => 6,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT => 'NewsPortalDemo/1.0',
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $body = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $cerr = curl_error($ch);
        curl_close($ch);
        if ($body === false) {
            return [null, 'Network error: ' . ($cerr ?: 'request failed')];
        }
        if ($code >= 400) {
            return [null, 'HTTP ' . $code . ': ' . substr(strip_tags((string)$body), 0, 180)];
        }
    } else {
        $ctx = stream_context_create([
            'http' => [
                'timeout' => $timeout,
                'header' => "User-Agent: NewsPortalDemo/1.0\r\n",
            ],
        ]);
        $body = @file_get_contents($url, false, $ctx);
        if ($body === false || $body === '') {
            return [null, 'Could not fetch URL. Enable PHP curl or allow_url_fopen.'];
        }
    }

    return [$body, null];
}

function news_api_http_get($url, $timeout = 12)
{
    [$body, $err] = news_api_http_raw($url, $timeout);
    if ($err) {
        return [null, $err];
    }

    $data = json_decode($body, true);
    if (!is_array($data)) {
        return [null, 'Invalid JSON from news API.'];
    }

    return [$data, null];
}

function news_api_rss_presets()
{
    return [
        // India-first
        'india-bbc' => ['label' => 'India — BBC', 'url' => 'https://feeds.bbci.co.uk/news/world/asia/india/rss.xml'],
        'india-toi' => ['label' => 'India — Times of India', 'url' => 'https://timesofindia.indiatimes.com/rssfeeds/-2128936835.cms'],
        'india-toi-top' => ['label' => 'India — TOI Top Stories', 'url' => 'https://timesofindia.indiatimes.com/rssfeedstopstories.cms'],
        'india-hindu' => ['label' => 'India — The Hindu National', 'url' => 'https://www.thehindu.com/news/national/feeder/default.rss'],
        'india-express' => ['label' => 'India — Indian Express', 'url' => 'https://indianexpress.com/section/india/feed/'],
        'india-ndtv' => ['label' => 'India — NDTV', 'url' => 'https://feeds.feedburner.com/ndtvnews-india-news'],
        'india-ht' => ['label' => 'India — Hindustan Times', 'url' => 'https://www.hindustantimes.com/feeds/rss/india-news/rssfeed.xml'],
        // Global (optional)
        'bbc-asia' => ['label' => 'BBC Asia', 'url' => 'https://feeds.bbci.co.uk/news/world/asia/rss.xml'],
        'bbc-world' => ['label' => 'BBC World', 'url' => 'https://feeds.bbci.co.uk/news/world/rss.xml'],
        'bbc-tech' => ['label' => 'BBC Technology', 'url' => 'https://feeds.bbci.co.uk/news/technology/rss.xml'],
        'bbc-business' => ['label' => 'BBC Business', 'url' => 'https://feeds.bbci.co.uk/news/business/rss.xml'],
        'bbc-politics' => ['label' => 'BBC Politics', 'url' => 'https://feeds.bbci.co.uk/news/politics/rss.xml'],
        'bbc-sport' => ['label' => 'BBC Sport', 'url' => 'https://feeds.bbci.co.uk/sport/rss.xml'],
        'nyt-world' => ['label' => 'NYTimes World', 'url' => 'https://rss.nytimes.com/services/xml/rss/nyt/World.xml'],
    ];
}

function news_api_india_feed_map()
{
    return [
        'india-news' => 'india-bbc',
        'politics' => 'india-hindu',
        'business' => 'india-express',
        'sports' => 'india-toi-top',
        'technology' => 'india-ndtv',
        'world' => 'india-ht',
    ];
}

function news_api_slugify($text)
{
    $text = strtolower(trim((string)$text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    $text = trim($text, '-');
    if ($text === '') {
        $text = 'imported-story-' . time();
    }
    return substr($text, 0, 180);
}

function news_api_unique_slug(PDO $conn, $base)
{
    $slug = $base;
    $n = 1;
    $stmt = $conn->prepare('SELECT id FROM posts WHERE slug = :slug LIMIT 1');
    while (true) {
        $stmt->execute([':slug' => $slug]);
        if (!$stmt->fetch()) {
            return $slug;
        }
        $n++;
        $slug = $base . '-' . $n;
        if ($n > 50) {
            return $base . '-' . time();
        }
    }
}

/**
 * @return array{0:?array,1:?string} [articles, error]
 */
function news_api_fetch_articles($provider, $api_key, $query, $limit = 10)
{
    $provider = strtolower(trim((string)$provider));
    $api_key = trim((string)$api_key);
    $query = trim((string)$query);
    $limit = max(1, min(20, (int)$limit));

    // RSS: no API key required
    if ($provider === 'rss' || str_starts_with($provider, 'rss:')) {
        $presets = news_api_rss_presets();
        $feedKey = $provider === 'rss' ? ($query !== '' ? $query : 'bbc-asia') : substr($provider, 4);
        if (isset($presets[$feedKey])) {
            $feedUrl = $presets[$feedKey]['url'];
            $sourceName = $presets[$feedKey]['label'];
        } elseif (preg_match('#^https?://#i', $query)) {
            $feedUrl = $query;
            $sourceName = 'RSS Feed';
        } else {
            $feedUrl = $presets['bbc-asia']['url'];
            $sourceName = $presets['bbc-asia']['label'];
        }
        return news_api_fetch_rss($feedUrl, $sourceName, $limit);
    }

    if ($api_key === '') {
        return [null, 'API key is required for NewsAPI/GNews. Or choose RSS (no key) instead.'];
    }

    // If user left RSS preset selected (e.g. bbc-asia), map to a real keyword
    $presets = news_api_rss_presets();
    if ($query === '' || isset($presets[$query])) {
        $map = [
            'bbc-asia' => 'India Asia',
            'bbc-world' => 'world news',
            'bbc-tech' => 'technology',
            'bbc-business' => 'business economy',
            'bbc-politics' => 'politics',
            'bbc-sport' => 'sports',
            'nyt-world' => 'world',
        ];
        $query = $map[$query] ?? 'India';
    }

    if ($provider === 'gnews') {
        $url = 'https://gnews.io/api/v4/search?' . http_build_query([
            'q' => $query,
            'lang' => 'en',
            'max' => $limit,
            'apikey' => $api_key,
        ]);
        [$data, $err] = news_api_http_get($url);
        if ($err) {
            return [null, $err . ' Tip: on live hosting, use Provider = RSS (no key) or Full site refresh.'];
        }
        if (!empty($data['errors']) || (isset($data['message']) && empty($data['articles']))) {
            return [null, $data['errors'][0] ?? ($data['message'] ?? 'GNews error')];
        }
        $raw = $data['articles'] ?? [];
        $out = [];
        foreach ($raw as $a) {
            $row = news_api_normalize_article([
                'title' => $a['title'] ?? '',
                'description' => $a['description'] ?? '',
                'content' => $a['content'] ?? '',
                'url' => $a['url'] ?? '',
                'image' => $a['image'] ?? '',
                'source' => $a['source']['name'] ?? 'GNews',
                'publishedAt' => $a['publishedAt'] ?? '',
            ], 'gnews');
            if ($row) {
                $out[] = $row;
            }
        }
        if (empty($out)) {
            return [null, 'GNews returned 0 articles for "' . $query . '". Try another keyword.'];
        }
        return [$out, null];
    }

    // NewsAPI.org — try top-headlines first (more reliable on free plan), then everything
    return news_api_fetch_newsapi($api_key, $query, $limit);
}

function news_api_fetch_newsapi($api_key, $query, $limit = 10)
{
    $attempts = [];

    // Prefer India country headlines first for NewsAPI
    $url2 = 'https://newsapi.org/v2/top-headlines?' . http_build_query([
        'country' => 'in',
        'pageSize' => $limit,
        'apiKey' => $api_key,
    ]);
    $attempts[] = $url2;

    // Then query + India country when possible
    $url1 = 'https://newsapi.org/v2/top-headlines?' . http_build_query([
        'q' => $query !== '' ? $query : 'India',
        'country' => 'in',
        'pageSize' => $limit,
        'apiKey' => $api_key,
    ]);
    array_unshift($attempts, $url1);

    // 3) Everything search (India-biased query)
    $qEverything = trim($query) !== '' ? $query : 'India';
    if (stripos($qEverything, 'india') === false) {
        $qEverything .= ' India';
    }
    $url3 = 'https://newsapi.org/v2/everything?' . http_build_query([
        'q' => $qEverything,
        'language' => 'en',
        'sortBy' => 'publishedAt',
        'pageSize' => $limit,
        'apiKey' => $api_key,
    ]);
    $attempts[] = $url3;

    $lastErr = null;
    foreach ($attempts as $url) {
        [$data, $err] = news_api_http_get($url);
        if ($err) {
            $lastErr = $err;
            continue;
        }
        if (($data['status'] ?? '') === 'error') {
            $lastErr = $data['message'] ?? 'NewsAPI error';
            // Typical free-plan live-server block
            if (stripos($lastErr, 'localhost') !== false || stripos($lastErr, 'developers') !== false) {
                return [null, 'NewsAPI free key works only on localhost. On live Hostinger use Provider = RSS or click Full site refresh (no key needed). Detail: ' . $lastErr];
            }
            continue;
        }
        $raw = $data['articles'] ?? [];
        $out = [];
        foreach ($raw as $a) {
            $row = news_api_normalize_article([
                'title' => $a['title'] ?? '',
                'description' => $a['description'] ?? '',
                'content' => $a['content'] ?? '',
                'url' => $a['url'] ?? '',
                'image' => $a['urlToImage'] ?? '',
                'source' => $a['source']['name'] ?? 'NewsAPI',
                'publishedAt' => $a['publishedAt'] ?? '',
            ], 'newsapi');
            if ($row) {
                $out[] = $row;
            }
        }
        if (!empty($out)) {
            return [array_slice($out, 0, $limit), null];
        }
    }

    return [null, $lastErr
        ? ('NewsAPI failed: ' . $lastErr . ' — On live site prefer RSS / Full site refresh.')
        : ('No NewsAPI articles for "' . $query . '". Try keyword India or Technology, or use RSS.')];
}

/**
 * @return array{0:?array,1:?string}
 */
function news_api_fetch_rss($feedUrl, $sourceName, $limit = 10)
{
    [$xml, $err] = news_api_http_raw($feedUrl, 15);
    if ($err) {
        return [null, 'RSS fetch failed: ' . $err];
    }

    libxml_use_internal_errors(true);
    $sx = simplexml_load_string($xml);
    if (!$sx) {
        return [null, 'Could not parse RSS feed XML.'];
    }

    $items = [];
    if (isset($sx->channel->item)) {
        foreach ($sx->channel->item as $item) {
            $items[] = $item;
        }
    } elseif (isset($sx->entry)) {
        // Atom
        foreach ($sx->entry as $entry) {
            $link = '';
            if (isset($entry->link['href'])) {
                $link = (string)$entry->link['href'];
            } elseif (isset($entry->link)) {
                $link = (string)$entry->link;
            }
            $items[] = (object)[
                'title' => (string)($entry->title ?? ''),
                'description' => (string)($entry->summary ?? $entry->content ?? ''),
                'link' => $link,
                'pubDate' => (string)($entry->updated ?? $entry->published ?? ''),
            ];
        }
    }

    $out = [];
    foreach ($items as $item) {
        if (count($out) >= $limit) {
            break;
        }

        $title = trim((string)($item->title ?? ''));
        $desc = trim(strip_tags((string)($item->description ?? $item->summary ?? '')));
        $link = trim((string)($item->link ?? ''));
        $pub = trim((string)($item->pubDate ?? $item->published ?? ''));

        // media:thumbnail / enclosure image
        $image = '';
        if (isset($item->enclosure['url'])) {
            $image = (string)$item->enclosure['url'];
        }
        $media = $item->children('media', true);
        if ($image === '' && $media && isset($media->thumbnail)) {
            $attrs = $media->thumbnail->attributes();
            if ($attrs && isset($attrs['url'])) {
                $image = (string)$attrs['url'];
            }
        }
        if ($image === '' && $media && isset($media->content)) {
            $attrs = $media->content->attributes();
            if ($attrs && isset($attrs['url'])) {
                $image = (string)$attrs['url'];
            }
        }
        // Sometimes image is inside description HTML
        if ($image === '' && preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', (string)($item->description ?? ''), $m)) {
            $image = $m[1];
        }

        $row = news_api_normalize_article([
            'title' => $title,
            'description' => $desc,
            'content' => $desc,
            'url' => $link,
            'image' => $image,
            'source' => $sourceName,
            'publishedAt' => $pub ? date('c', strtotime($pub) ?: time()) : date('c'),
        ], 'rss');
        if ($row) {
            $out[] = $row;
        }
    }

    if (empty($out)) {
        return [null, 'No items found in that RSS feed.'];
    }

    return [$out, null];
}

function news_api_normalize_article(array $a, $provider)
{
    $title = trim(preg_replace('/\s+/', ' ', (string)($a['title'] ?? '')));
    if ($title === '' || strcasecmp($title, '[Removed]') === 0) {
        return null;
    }

    $desc = trim((string)($a['description'] ?? ''));
    $content = trim((string)($a['content'] ?? ''));
    $content = preg_replace('/\[\+\d+\s+chars\]/i', '', $content);
    $url = trim((string)($a['url'] ?? ''));
    $image = trim((string)($a['image'] ?? ''));
    $source = trim((string)($a['source'] ?? 'News'));
    $published = trim((string)($a['publishedAt'] ?? ''));

    $summary = $desc !== '' ? $desc : mb_substr(strip_tags($content), 0, 280);
    if (mb_strlen($summary) > 400) {
        $summary = mb_substr($summary, 0, 397) . '...';
    }

    $bodyParts = [];
    if ($desc !== '') {
        $bodyParts[] = '<p>' . htmlspecialchars($desc) . '</p>';
    }
    if ($content !== '' && $content !== $desc) {
        $bodyParts[] = '<p>' . nl2br(htmlspecialchars($content)) . '</p>';
    }
    if ($url !== '') {
        $bodyParts[] = '<p><strong>Source:</strong> ' . htmlspecialchars($source)
            . ' — <a href="' . htmlspecialchars($url) . '" target="_blank" rel="noopener noreferrer">Read original article</a></p>';
    }
    $bodyParts[] = '<p><em>Demo import via ' . htmlspecialchars($provider) . '. Replace with your own editorial copy before going live.</em></p>';

    return [
        'title' => mb_substr($title, 0, 250),
        'summary' => $summary,
        'content' => implode("\n", $bodyParts),
        'url' => $url,
        'image' => $image,
        'source' => mb_substr($source, 0, 120),
        'publishedAt' => $published,
        'provider' => $provider,
        'fingerprint' => md5(strtolower($title) . '|' . $url),
    ];
}

function news_api_resolve_featured_image($imageUrl)
{
    $imageUrl = trim((string)$imageUrl);
    if ($imageUrl === '' || !preg_match('#^https?://#i', $imageUrl)) {
        return null;
    }

    // Column is varchar(255) — keep short remote URLs as-is
    if (strlen($imageUrl) <= 255 && filter_var($imageUrl, FILTER_VALIDATE_URL)) {
        return $imageUrl;
    }

    // Longer URLs: try local download into existing uploads structure
    $bin = false;
    if (function_exists('curl_init')) {
        $ch = curl_init($imageUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT => 'NewsPortalDemo/1.0',
        ]);
        $bin = curl_exec($ch);
        $ctype = (string)curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);
    } else {
        $bin = @file_get_contents($imageUrl);
        $ctype = '';
    }

    if ($bin === false || strlen($bin) < 500) {
        return null;
    }

    $ext = 'jpg';
    if (stripos($ctype, 'png') !== false) {
        $ext = 'png';
    } elseif (stripos($ctype, 'webp') !== false) {
        $ext = 'webp';
    } elseif (stripos($ctype, 'gif') !== false) {
        $ext = 'gif';
    }

    $year = date('Y');
    $month = date('m');
    $dir = __DIR__ . "/../uploads/$year/$month/";
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    $name = time() . '_' . substr(md5($imageUrl), 0, 8) . '_feat.' . $ext;
    if (@file_put_contents($dir . $name, $bin) === false) {
        return null;
    }
    return "uploads/$year/$month/$name";
}

/**
 * Insert one normalized article into existing posts (+ optional tags).
 * Returns post id or 0 on skip/failure.
 */
function news_api_insert_post(PDO $conn, array $article, $category_id, $author_id, $status = 'draft', $add_source_tag = true)
{
    if (empty($article['title'])) {
        return 0;
    }

    $category_id = (int)$category_id;
    $author_id = (int)$author_id;
    if ($category_id < 1 || $author_id < 1) {
        return 0;
    }

    $status = in_array($status, ['published', 'draft'], true) ? $status : 'draft';
    $slug = news_api_unique_slug($conn, news_api_slugify($article['title']));
    $image = news_api_resolve_featured_image($article['image'] ?? '');
    $credit = trim(($article['source'] ?? '') . ' (API demo)');
    if (strlen($credit) > 250) {
        $credit = substr($credit, 0, 247) . '...';
    }

    $published_at = null;
    if ($status === 'published') {
        $ts = !empty($article['publishedAt']) ? strtotime($article['publishedAt']) : time();
        $published_at = date('Y-m-d H:i:s', $ts ?: time());
    }

    $content = function_exists('normalize_post_html')
        ? normalize_post_html($article['content'] ?? '')
        : ($article['content'] ?? '');

    $sql = "INSERT INTO posts (title, slug, category_id, content, summary, featured_image, image_credit, status, meta_title, meta_description, author_id, published_at)
            VALUES (:title, :slug, :cat, :content, :summary, :img, :img_credit, :status, :m_title, :m_desc, :author, :pub_date)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':title' => $article['title'],
        ':slug' => $slug,
        ':cat' => $category_id,
        ':content' => $content !== '' ? $content : '<p></p>',
        ':summary' => $article['summary'] ?? '',
        ':img' => $image,
        ':img_credit' => $credit,
        ':status' => $status,
        ':m_title' => mb_substr($article['title'], 0, 255),
        ':m_desc' => mb_substr($article['summary'] ?? '', 0, 255),
        ':author' => $author_id,
        ':pub_date' => $published_at,
    ]);

    $post_id = (int)$conn->lastInsertId();
    if ($post_id && $add_source_tag) {
        $tags = 'demo-import';
        if (!empty($article['source'])) {
            $tags .= ', ' . $article['source'];
        }
        news_api_attach_tags($conn, $post_id, $tags);
    }

    return $post_id;
}

function news_api_attach_tags(PDO $conn, $post_id, $tags_input)
{
    if ($tags_input === '') {
        return;
    }
    $tags_array = array_map('trim', explode(',', $tags_input));
    foreach ($tags_array as $tag_name) {
        if ($tag_name === '') {
            continue;
        }
        $tag_slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $tag_name));
        $tag_slug = trim($tag_slug, '-');
        if ($tag_slug === '') {
            continue;
        }

        $tag_stmt = $conn->prepare('SELECT id FROM tags WHERE slug = :slug');
        $tag_stmt->execute([':slug' => $tag_slug]);
        $tag_row = $tag_stmt->fetch(PDO::FETCH_ASSOC);

        if ($tag_row) {
            $tag_id = $tag_row['id'];
        } else {
            $ins_tag = $conn->prepare('INSERT INTO tags (name, slug) VALUES (:name, :slug)');
            $ins_tag->execute([':name' => mb_substr($tag_name, 0, 100), ':slug' => $tag_slug]);
            $tag_id = $conn->lastInsertId();
        }

        $pivot = $conn->prepare('INSERT IGNORE INTO post_tags (post_id, tag_id) VALUES (:pid, :tid)');
        $pivot->execute([':pid' => $post_id, ':tid' => $tag_id]);
    }
}

function news_api_upsert_setting(PDO $conn, $key, $value)
{
    $stmt = $conn->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (:k, :v)
        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
    $stmt->execute([':k' => $key, ':v' => $value]);
}

/**
 * Ensure demo categories exist (mapped to RSS topics). Returns slug => id.
 */
function news_api_ensure_categories(PDO $conn)
{
    $defs = [
        ['India News', 'india-news', '#4f46e5', 0],
        ['Politics', 'politics', '#ef4444', 0],
        ['Business', 'business', '#059669', 0],
        ['Sports', 'sports', '#f59e0b', 0],
        ['Technology', 'technology', '#8b5cf6', 0],
        ['World', 'world', '#0ea5e9', 0],
    ];

    // Prefer India News on menu; hide legacy Asia slug if unused
    try {
        $conn->exec("UPDATE categories SET parent_id = 0 WHERE slug = 'politics'");
        $conn->exec("UPDATE categories SET show_on_menu = 0 WHERE slug = 'asia'");
    } catch (Throwable $e) {
        // ignore
    }

    $out = [];
    foreach ($defs as [$name, $slug, $color, $parent]) {
        $stmt = $conn->prepare('SELECT id FROM categories WHERE slug = :s LIMIT 1');
        $stmt->execute([':s' => $slug]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $upd = $conn->prepare('UPDATE categories SET name = :n, color = :c, parent_id = :p, show_on_menu = 1, status = 1 WHERE id = :id');
            $upd->execute([':n' => $name, ':c' => $color, ':p' => $parent, ':id' => $row['id']]);
            $out[$slug] = (int)$row['id'];
        } else {
            $ins = $conn->prepare('INSERT INTO categories (name, slug, description, color, parent_id, show_on_menu, status) VALUES (:n, :s, :d, :c, :p, 1, 1)');
            $ins->execute([
                ':n' => $name,
                ':s' => $slug,
                ':d' => $name . ' coverage',
                ':c' => $color,
                ':p' => $parent,
            ]);
            $out[$slug] = (int)$conn->lastInsertId();
        }
    }
    return $out;
}

/**
 * Full demo refresh: wipe old posts, import RSS by category, rebuild breaking + homepage sections.
 *
 * @param string $scope 'india' (default) or 'global'
 * @return array{ok:bool,message:string,imported:int,categories:int,breaking:int}
 */
function news_api_full_site_refresh(PDO $conn, $author_id, $per_category = 6, $scope = 'india')
{
    if (!defined('BASE_URL')) {
        require_once __DIR__ . '/../config/constants.php';
    }

    $author_id = (int)$author_id;
    if ($author_id < 1) {
        return ['ok' => false, 'message' => 'Invalid author.', 'imported' => 0, 'categories' => 0, 'breaking' => 0];
    }

    $per_category = max(3, min(10, (int)$per_category));
    $scope = strtolower(trim((string)$scope)) === 'global' ? 'global' : 'india';
    $cats = news_api_ensure_categories($conn);

    if ($scope === 'india') {
        $feedMap = news_api_india_feed_map();
    } else {
        $feedMap = [
            'world' => 'bbc-world',
            'politics' => 'bbc-politics',
            'technology' => 'bbc-tech',
            'business' => 'bbc-business',
            'sports' => 'bbc-sport',
            'india-news' => 'bbc-asia',
        ];
    }

    // Wipe editorial content (keep users/settings/ads/pages)
    $conn->exec('DELETE FROM post_tags');
    $conn->exec('DELETE FROM comments');
    $conn->exec('DELETE FROM site_sections');
    $conn->exec('DELETE FROM posts');
    $conn->exec('DELETE FROM breaking_news');
    try {
        $conn->exec('ALTER TABLE posts AUTO_INCREMENT = 1');
        $conn->exec('ALTER TABLE breaking_news AUTO_INCREMENT = 1');
        $conn->exec('ALTER TABLE site_sections AUTO_INCREMENT = 1');
    } catch (Throwable $e) {
        // ignore
    }

    $imported = 0;
    $allNew = []; // [ ['id'=>, 'title'=>, 'slug'=>], ... ] newest first per fetch order
    $errors = [];

    foreach ($feedMap as $catSlug => $feedKey) {
        $catId = $cats[$catSlug] ?? 0;
        if ($catId < 1) {
            continue;
        }
        [$articles, $err] = news_api_fetch_articles('rss', '', $feedKey, $per_category);
        if ($err) {
            $errors[] = "$feedKey: $err";
            continue;
        }
        foreach ($articles as $article) {
            $id = news_api_insert_post($conn, $article, $catId, $author_id, 'published', true);
            if ($id > 0) {
                $imported++;
                $slugStmt = $conn->prepare('SELECT slug FROM posts WHERE id = :id');
                $slugStmt->execute([':id' => $id]);
                $slug = (string)$slugStmt->fetchColumn();
                $allNew[] = [
                    'id' => $id,
                    'title' => $article['title'],
                    'slug' => $slug,
                ];
                // Mark a few as featured/breaking flags on posts table
                if ($imported <= 3) {
                    $conn->prepare('UPDATE posts SET is_breaking = 1, is_featured = 1 WHERE id = :id')->execute([':id' => $id]);
                } elseif ($imported <= 8) {
                    $conn->prepare('UPDATE posts SET is_featured = 1 WHERE id = :id')->execute([':id' => $id]);
                }
            }
        }
    }

    if ($imported < 1) {
        return [
            'ok' => false,
            'message' => 'Refresh failed. Could not import feeds. ' . implode(' | ', $errors),
            'imported' => 0,
            'categories' => count($cats),
            'breaking' => 0,
        ];
    }

    // Breaking ticker from newest titles
    $breakStmt = $conn->prepare('INSERT INTO breaking_news (title, link, status) VALUES (:t, :l, 1)');
    $breaking = 0;
    foreach (array_slice($allNew, 0, 8) as $p) {
        $link = rtrim(BASE_URL, '/') . '/article/' . $p['slug'];
        if (strlen($link) > 255) {
            $link = substr($link, 0, 255);
        }
        $title = mb_substr($p['title'], 0, 250);
        $breakStmt->execute([':t' => $title, ':l' => $link]);
        $breaking++;
    }

    // Homepage sections: hero (3), slider (6), trending (5)
    $sec = $conn->prepare('INSERT INTO site_sections (section_key, post_id, sort_order) VALUES (:k, :p, :o)');
    $heroIds = array_slice(array_column($allNew, 'id'), 0, 3);
    foreach ($heroIds as $i => $pid) {
        $sec->execute([':k' => 'hero_featured', ':p' => $pid, ':o' => $i]);
    }
    $sliderIds = array_slice(array_column($allNew, 'id'), 0, 6);
    foreach ($sliderIds as $i => $pid) {
        $sec->execute([':k' => 'home_slider', ':p' => $pid, ':o' => $i]);
    }
    $trendIds = array_slice(array_column($allNew, 'id'), 0, 5);
    foreach ($trendIds as $i => $pid) {
        $sec->execute([':k' => 'trending_news', ':p' => $pid, ':o' => $i]);
        // bump views so trending sidebar looks alive
        $conn->prepare('UPDATE posts SET views = :v WHERE id = :id')->execute([
            ':v' => 200 - ($i * 25),
            ':id' => $pid,
        ]);
    }

    news_api_upsert_setting($conn, 'news_api_provider', 'rss');

    // Demo ad placeholders (skip if helper missing)
    $ads_added = 0;
    $seedAds = __DIR__ . '/../admin/seed_demo_ads.php';
    if (is_file($seedAds)) {
        require_once $seedAds;
        if (function_exists('seed_demo_ads')) {
            $ads_added = (int)seed_demo_ads($conn);
        }
    }

    $msg = "Removed old posts and refreshed with {$imported} " . ($scope === 'india' ? 'India' : 'global') . " stories across " . count($cats) . " categories, {$breaking} breaking items, plus hero/slider";
    if ($ads_added > 0) {
        $msg .= ", and {$ads_added} demo ad banner(s)";
    }
    $msg .= '.';
    if ($errors) {
        $msg .= ' Some feeds failed: ' . implode('; ', $errors);
    }

    return [
        'ok' => true,
        'message' => $msg,
        'imported' => $imported,
        'categories' => count($cats),
        'breaking' => $breaking,
        'ads' => $ads_added,
    ];
}

