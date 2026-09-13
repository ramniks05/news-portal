<?php
session_start();
require_once '../../config/database.php';
require_once '../../helpers/common_functions.php';
require_once '../../helpers/news_api_functions.php';

if (!isset($_SESSION['admin_id'])) {
    $_SESSION['error'] = 'Unauthorized access.';
    header('Location: ../import-news.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../import-news.php');
    exit();
}

if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
    $_SESSION['error'] = 'Security token mismatch. Please try again.';
    header('Location: ../import-news.php');
    exit();
}

$action = $_POST['action'] ?? '';

if ($action === 'full_refresh') {
    $confirm = trim($_POST['confirm_text'] ?? '');
    if (strcasecmp($confirm, 'REFRESH') !== 0) {
        $_SESSION['error'] = 'Type REFRESH to confirm wiping old posts and rebuilding demo content.';
        header('Location: ../import-news.php');
        exit();
    }

    $per = (int)($_POST['per_category'] ?? 6);
    $scope = ($_POST['scope'] ?? 'india') === 'global' ? 'global' : 'india';
    $result = news_api_full_site_refresh($conn, (int)$_SESSION['admin_id'], $per, $scope);
    unset($_SESSION['news_import_preview'], $_SESSION['news_import_meta']);

    if (!empty($result['ok'])) {
        $_SESSION['success'] = $result['message'];
        header('Location: ../manage-posts.php');
    } else {
        $_SESSION['error'] = $result['message'] ?? 'Refresh failed.';
        header('Location: ../import-news.php');
    }
    exit();
}

if ($action === 'save_keys') {
    $provider = in_array($_POST['news_api_provider'] ?? '', ['newsapi', 'gnews'], true)
        ? $_POST['news_api_provider']
        : 'newsapi';
    news_api_upsert_setting($conn, 'news_api_provider', $provider);
    news_api_upsert_setting($conn, 'newsapi_key', trim($_POST['newsapi_key'] ?? ''));
    news_api_upsert_setting($conn, 'gnews_key', trim($_POST['gnews_key'] ?? ''));
    $_SESSION['success'] = 'News API settings saved.';
    header('Location: ../import-news.php');
    exit();
}

if ($action === 'fetch') {
    $provider = in_array($_POST['provider'] ?? '', ['rss', 'newsapi', 'gnews'], true) ? $_POST['provider'] : 'rss';
    $query = trim($_POST['query_text'] ?? '');
    if ($query === '') {
        $query = trim($_POST['query'] ?? '');
    }
            if ($provider === 'rss' && $query === '') {
                $query = 'india-bbc';
            }
            // NewsAPI/GNews need a real keyword — never send empty
            if ($provider !== 'rss' && $query === '') {
                $query = 'India';
            }
    $limit = (int)($_POST['limit'] ?? 8);
    $key = trim($_POST['api_key'] ?? '');
    if ($key === '' && $provider !== 'rss') {
        $key = $provider === 'gnews'
            ? trim(get_setting('gnews_key', ''))
            : trim(get_setting('newsapi_key', ''));
    }

    // Remember last used provider + key for convenience
    news_api_upsert_setting($conn, 'news_api_provider', $provider);
    if ($provider === 'gnews' && $key !== '') {
        news_api_upsert_setting($conn, 'gnews_key', $key);
    } elseif ($provider === 'newsapi' && $key !== '') {
        news_api_upsert_setting($conn, 'newsapi_key', $key);
    }

    [$articles, $err] = news_api_fetch_articles($provider, $key, $query, $limit);

    // Live Hostinger: NewsAPI free keys often fail — auto-fall back to RSS so content still loads
    $usedFallback = false;
    if ($err && in_array($provider, ['newsapi', 'gnews'], true)) {
        [$articles, $rssErr] = news_api_fetch_articles('rss', '', 'india-bbc', $limit);
        if (!$rssErr && !empty($articles)) {
            $usedFallback = true;
            $err = null;
            $provider = 'rss';
            $query = 'india-bbc';
        } else {
            $err = $err . ($rssErr ? ' | RSS fallback also failed: ' . $rssErr : '');
        }
    }

    if ($err) {
        $_SESSION['error'] = $err;
        unset($_SESSION['news_import_preview']);
        header('Location: ../import-news.php');
        exit();
    }

    $clean = [];
    foreach ($articles as $a) {
        if (is_array($a) && !empty($a['title'])) {
            $clean[] = $a;
        }
    }

    if (empty($clean)) {
        $_SESSION['error'] = 'No articles found for that query. Try keyword "India", or use Provider = RSS / Full site refresh.';
        unset($_SESSION['news_import_preview']);
        header('Location: ../import-news.php');
        exit();
    }

    $_SESSION['news_import_preview'] = $clean;
    $_SESSION['news_import_meta'] = [
        'provider' => $provider,
        'query' => $query,
        'category_id' => (int)($_POST['category_id'] ?? 0),
        'status' => in_array($_POST['status'] ?? '', ['draft', 'published'], true) ? $_POST['status'] : 'draft',
    ];
    $_SESSION['success'] = count($clean) . ' articles fetched' . ($usedFallback ? ' via RSS fallback (NewsAPI blocked on this server)' : '') . '. Select which ones to import.';
    header('Location: ../import-news.php');
    exit();
}

if ($action === 'import') {
    $preview = $_SESSION['news_import_preview'] ?? [];
    $meta = $_SESSION['news_import_meta'] ?? [];
    $selected = $_POST['selected'] ?? [];
    $category_id = (int)($_POST['category_id'] ?? ($meta['category_id'] ?? 0));
    $status = in_array($_POST['status'] ?? '', ['draft', 'published'], true)
        ? $_POST['status']
        : ($meta['status'] ?? 'draft');

    if (empty($preview) || !is_array($preview)) {
        $_SESSION['error'] = 'Nothing to import. Fetch articles first.';
        header('Location: ../import-news.php');
        exit();
    }

    if ($category_id < 1) {
        $cats = news_api_ensure_categories($conn);
        $category_id = (int)($cats['world'] ?? reset($cats) ?: 0);
    }

    if ($category_id < 1) {
        $_SESSION['error'] = 'No category found. Go to Categories and add one (e.g. World), then try again.';
        header('Location: ../import-news.php');
        exit();
    }

    $catCheck = $conn->prepare('SELECT id FROM categories WHERE id = :id');
    $catCheck->execute([':id' => $category_id]);
    if (!$catCheck->fetch()) {
        $cats = news_api_ensure_categories($conn);
        $category_id = (int)($cats['world'] ?? reset($cats) ?: 0);
    }
    // Reactivate if needed
    $conn->prepare('UPDATE categories SET status = 1, show_on_menu = 1 WHERE id = :id')->execute([':id' => $category_id]);

    $catCheck = $conn->prepare('SELECT id FROM categories WHERE id = :id AND status = 1');
    $catCheck->execute([':id' => $category_id]);
    if (!$catCheck->fetch()) {
        $_SESSION['error'] = 'Invalid category selected.';
        header('Location: ../import-news.php');
        exit();
    }

    if (!is_array($selected) || count($selected) === 0) {
        $_SESSION['error'] = 'Select at least one article to import.';
        header('Location: ../import-news.php');
        exit();
    }

    $imported = 0;
    $skipped = 0;
    try {
        foreach ($selected as $fp) {
            $article = null;
            foreach ($preview as $row) {
                if (($row['fingerprint'] ?? '') === $fp) {
                    $article = $row;
                    break;
                }
            }
            if (!$article) {
                $skipped++;
                continue;
            }

            // Skip near-duplicates by title
            $dup = $conn->prepare('SELECT id FROM posts WHERE title = :t LIMIT 1');
            $dup->execute([':t' => $article['title']]);
            if ($dup->fetch()) {
                $skipped++;
                continue;
            }

            $id = news_api_insert_post(
                $conn,
                $article,
                $category_id,
                (int)$_SESSION['admin_id'],
                $status,
                true
            );
            if ($id > 0) {
                $imported++;
            } else {
                $skipped++;
            }
        }
    } catch (Throwable $e) {
        error_log('News import error: ' . $e->getMessage());
        $_SESSION['error'] = 'Import failed: ' . $e->getMessage();
        header('Location: ../import-news.php');
        exit();
    }

    unset($_SESSION['news_import_preview'], $_SESSION['news_import_meta']);
    $_SESSION['success'] = "Imported {$imported} post(s) into your existing structure"
        . ($skipped ? " ({$skipped} skipped)" : '')
        . '. Review them under All Posts.';
    header('Location: ../manage-posts.php');
    exit();
}

$_SESSION['error'] = 'Unknown action.';
header('Location: ../import-news.php');
exit();
