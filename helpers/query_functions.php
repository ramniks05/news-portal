<?php
require_once __DIR__ . '/../config/database.php';

if (!function_exists('normalize_post_html')) {
    function normalize_post_html($html)
    {
        $html = trim((string)$html);
        if ($html === '') {
            return '';
        }

        $was_escaped = (bool)preg_match('/&lt;\s*\/?\s*[a-z!]/i', $html);

        for ($i = 0; $i < 3; $i++) {
            if (!preg_match('/&lt;\s*\/?\s*[a-z!]/i', $html)) {
                break;
            }
            $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        $html = preg_replace('#</?(?:html|head|body)\b[^>]*>#i', '', $html);
        $html = preg_replace('#<!--\s*(?:Start|End)Fragment\s*-->#i', '', $html);
        $html = preg_replace('#<script\b[^>]*>[\s\S]*?</script>#i', '', $html);
        $html = preg_replace('#<style\b[^>]*>[\s\S]*?</style>#i', '', $html);

        if ($was_escaped && preg_match('#^<p>([\s\S]*)</p>$#i', trim($html), $m)) {
            $html = $m[1];
        }

        if ($was_escaped) {
            $html = preg_replace('/\sstyle\s*=\s*("|\')(?:\\\\.|(?!\1).)*\1/i', '', $html);
            $html = preg_replace('/\s(?:class|id)\s*=\s*("|\')(?:\\\\.|(?!\1).)*\1/i', '', $html);
        }

        $html = preg_replace_callback('#<img\b([^>]*)>#i', function ($m) {
            $attrs = $m[1];
            $attrs = preg_replace('/\s(?:width|height)\s*=\s*("|\')[^"\']*\1/i', '', $attrs);
            $attrs = preg_replace('/\sstyle\s*=\s*("|\')(?:\\\\.|(?!\1).)*\1/i', '', $attrs);
            if (!preg_match('/\sclass\s*=/i', $attrs)) {
                $attrs .= ' class="max-w-full h-auto rounded-xl"';
            }
            if (!preg_match('/\sloading\s*=/i', $attrs)) {
                $attrs .= ' loading="lazy"';
            }
            return '<img' . $attrs . '>';
        }, $html);

        return trim($html);
    }
}

if (!function_exists('render_post_content')) {
    function render_post_content($html)
    {
        return normalize_post_html($html);
    }
}

function check_maintenance_mode()
{
    if (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false || strpos($_SERVER['REQUEST_URI'], 'login.php') !== false) {
        return;
    }

    $is_maintenance = get_config('maintenance_mode', '0');

    if ($is_maintenance == '1') {
        if (file_exists(__DIR__ . '/../maintenance.php')) {
            include_once __DIR__ . '/../maintenance.php';
            exit();
        } else {
            die("Site is under maintenance. Please check back later.");
        }
    }
}

function get_config($key, $default = '')
{
    global $conn;
    static $config_cache = [];

    if (empty($config_cache)) {
        try {
            $stmt = $conn->query("SELECT setting_key, setting_value FROM settings");
            $config_cache = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        } catch (Exception $e) {
            return $default;
        }
    }
    return $config_cache[$key] ?? $default;
}

function article_url($slug)
{
    return BASE_URL . "/article/" . $slug;
}

function category_url($slug)
{
    return BASE_URL . "/category/" . $slug;
}

function page_url($slug)
{
    return BASE_URL . "/" . $slug;
}

function get_section_posts($key)
{
    global $conn;
    $stmt = $conn->prepare("
        SELECT p.*, c.name as category_name, c.slug as category_slug, u.name as author_name 
        FROM site_sections s
        JOIN posts p ON s.post_id = p.id
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN users u ON p.author_id = u.id
        WHERE s.section_key = :key AND p.status = 'published'
        ORDER BY s.sort_order ASC
    ");
    $stmt->execute([':key' => $key]);
    return $stmt->fetchAll();
}

function get_trending_news($limit = 5)
{
    global $conn;

    $curated = get_section_posts('trending_news');
    if (!empty($curated)) {
        return array_slice($curated, 0, $limit);
    }

    $stmt = $conn->prepare("SELECT title, slug, views, featured_image FROM posts WHERE status = 'published' ORDER BY views DESC LIMIT :limit");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function get_latest_posts($limit = 10, $offset = 0, $exclude_ids = [])
{
    global $conn;
    $exclude_sql = "";

    if (!empty($exclude_ids)) {
        $ids = implode(',', array_map('intval', $exclude_ids));
        $exclude_sql = "AND p.id NOT IN ($ids)";
    }

    $sql = "SELECT p.*, c.name as category_name, c.slug as category_slug, c.color as category_color, u.name as author_name 
            FROM posts p 
            LEFT JOIN categories c ON p.category_id = c.id 
            LEFT JOIN users u ON p.author_id = u.id 
            WHERE p.status = 'published' $exclude_sql
            ORDER BY COALESCE(p.published_at, p.created_at) DESC LIMIT :limit OFFSET :offset";

    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Related articles: prefer same category, then fill with latest.
 */
function get_related_posts($post_id, $category_id = null, $limit = 3)
{
    global $conn;
    $post_id = (int)$post_id;
    $limit = max(1, (int)$limit);
    $related = [];

    if (!empty($category_id)) {
        $stmt = $conn->prepare("
            SELECT p.*, c.name as category_name, c.slug as category_slug, u.name as author_name
            FROM posts p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN users u ON p.author_id = u.id
            WHERE p.status = 'published' AND p.category_id = :cat AND p.id != :id
            ORDER BY COALESCE(p.published_at, p.created_at) DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':cat', (int)$category_id, PDO::PARAM_INT);
        $stmt->bindValue(':id', $post_id, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $related = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    if (count($related) < $limit) {
        $need = $limit - count($related);
        $exclude = array_merge([$post_id], array_column($related, 'id'));
        $more = get_latest_posts($need, 0, $exclude);
        $related = array_merge($related, $more);
    }

    return array_slice($related, 0, $limit);
}

function count_published_posts()
{
    global $conn;
    return (int)$conn->query("SELECT COUNT(*) FROM posts WHERE status = 'published'")->fetchColumn();
}

function tag_url($slug)
{
    return BASE_URL . '/tag/' . $slug;
}

function author_url($author_id, $author_name = '')
{
    $slug = trim(strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', (string)$author_name)), '-');
    if ($slug === '') {
        return BASE_URL . '/author/' . (int)$author_id;
    }
    return BASE_URL . '/author/' . (int)$author_id . '-' . $slug;
}

function estimate_reading_time($html, $wpm = 200)
{
    $text = trim(strip_tags((string)$html));
    if ($text === '') {
        return 1;
    }
    $words = str_word_count($text);
    return max(1, (int)ceil($words / max(1, $wpm)));
}

function get_posts_by_tag_slug($slug, $limit = 10, $offset = 0)
{
    global $conn;
    $sql = "SELECT p.*, c.name as category_name, c.slug as category_slug, u.name as author_name
            FROM posts p
            JOIN post_tags pt ON pt.post_id = p.id
            JOIN tags t ON t.id = pt.tag_id
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN users u ON p.author_id = u.id
            WHERE t.slug = :slug AND p.status = 'published'
            ORDER BY COALESCE(p.published_at, p.created_at) DESC
            LIMIT :limit OFFSET :offset";
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':slug', $slug);
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function count_posts_by_tag_slug($slug)
{
    global $conn;
    $stmt = $conn->prepare("
        SELECT COUNT(*) FROM posts p
        JOIN post_tags pt ON pt.post_id = p.id
        JOIN tags t ON t.id = pt.tag_id
        WHERE t.slug = :slug AND p.status = 'published'
    ");
    $stmt->execute([':slug' => $slug]);
    return (int)$stmt->fetchColumn();
}

function get_posts_by_author($author_id, $limit = 10, $offset = 0)
{
    global $conn;
    $sql = "SELECT p.*, c.name as category_name, c.slug as category_slug, u.name as author_name
            FROM posts p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN users u ON p.author_id = u.id
            WHERE p.author_id = :aid AND p.status = 'published'
            ORDER BY COALESCE(p.published_at, p.created_at) DESC
            LIMIT :limit OFFSET :offset";
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':aid', (int)$author_id, PDO::PARAM_INT);
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function count_posts_by_author($author_id)
{
    global $conn;
    $stmt = $conn->prepare("SELECT COUNT(*) FROM posts WHERE author_id = :aid AND status = 'published'");
    $stmt->execute([':aid' => (int)$author_id]);
    return (int)$stmt->fetchColumn();
}

function get_author_by_id($author_id)
{
    global $conn;
    $stmt = $conn->prepare("SELECT id, name, email, bio, avatar, role, status, created_at FROM users WHERE id = :id AND status = 1 LIMIT 1");
    $stmt->execute([':id' => (int)$author_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!function_exists('get_post_thumbnail')) {
function get_post_thumbnail($path)
{
    if (!empty($path)) {
        $path = trim($path);
        // External CDN / pasted image URL
        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }
        $relative = ltrim(str_replace('\\', '/', $path), '/');
        $full = __DIR__ . '/../' . $relative;
        if (is_file($full)) {
            return BASE_URL . '/' . $relative;
        }
        // Path stored but file missing — still prefer absolute URL over broken relative paths
        if (strpos($relative, 'uploads/') === 0) {
            return BASE_URL . '/' . $relative;
        }
    }
    return BASE_URL . '/assets/images/static/placeholder.jpg';
}
}

/**
 * Top-level menu categories with optional children for dropdowns.
 * Only 1 level of nesting is supported.
 */
function get_menu_categories($limit = 7)
{
    global $conn;

    $stmt = $conn->prepare("
        SELECT id, name, slug, color 
        FROM categories 
        WHERE show_on_menu = 1 AND status = 1 AND (parent_id IS NULL OR parent_id = 0)
        ORDER BY id ASC 
        LIMIT :limit
    ");
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->execute();
    $parents = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($parents)) {
        return [];
    }

    $parent_ids = array_column($parents, 'id');
    $placeholders = implode(',', array_fill(0, count($parent_ids), '?'));

    $child_stmt = $conn->prepare("
        SELECT id, name, slug, color, parent_id 
        FROM categories 
        WHERE show_on_menu = 1 AND status = 1 AND parent_id IN ($placeholders)
        ORDER BY name ASC
    ");
    $child_stmt->execute($parent_ids);
    $children = $child_stmt->fetchAll(PDO::FETCH_ASSOC);

    $by_parent = [];
    foreach ($children as $child) {
        $by_parent[(int)$child['parent_id']][] = $child;
    }

    foreach ($parents as &$parent) {
        $parent['children'] = $by_parent[(int)$parent['id']] ?? [];
    }
    unset($parent);

    return $parents;
}

function get_category_child_ids($category_id)
{
    global $conn;
    $stmt = $conn->prepare("SELECT id FROM categories WHERE parent_id = :pid AND status = 1");
    $stmt->execute([':pid' => (int)$category_id]);
    return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

/**
 * Flat list for selects: parents first, children indented.
 * Returns [id, name, parent_id, label]
 */
function get_categories_for_select($exclude_id = null)
{
    global $conn;
    $sql = "SELECT id, name, parent_id FROM categories WHERE status = 1";
    $params = [];
    if ($exclude_id) {
        $sql .= " AND id != :exclude";
        $params[':exclude'] = (int)$exclude_id;
    }
    $sql .= " ORDER BY name ASC";
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $all = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $parents = [];
    $children = [];
    foreach ($all as $row) {
        $pid = (int)($row['parent_id'] ?? 0);
        if ($pid === 0) {
            $parents[] = $row;
        } else {
            $children[$pid][] = $row;
        }
    }

    $result = [];
    foreach ($parents as $parent) {
        $result[] = [
            'id' => $parent['id'],
            'name' => $parent['name'],
            'parent_id' => 0,
            'label' => $parent['name'],
        ];
        if (!empty($children[$parent['id']])) {
            foreach ($children[$parent['id']] as $child) {
                $result[] = [
                    'id' => $child['id'],
                    'name' => $child['name'],
                    'parent_id' => (int)$parent['id'],
                    'label' => '— ' . $child['name'],
                ];
            }
        }
    }

    // Orphaned children (parent inactive/missing) still selectable
    foreach ($children as $pid => $list) {
        $parent_exists = false;
        foreach ($parents as $p) {
            if ((int)$p['id'] === (int)$pid) {
                $parent_exists = true;
                break;
            }
        }
        if (!$parent_exists) {
            foreach ($list as $child) {
                $result[] = [
                    'id' => $child['id'],
                    'name' => $child['name'],
                    'parent_id' => (int)$pid,
                    'label' => $child['name'],
                ];
            }
        }
    }

    return $result;
}

function get_breaking_news($limit = 10)
{
    global $conn;
    $stmt = $conn->prepare("SELECT title, link FROM breaking_news WHERE status = 1 ORDER BY id DESC LIMIT :limit");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function get_post_by_slug($slug)
{
    global $conn;
    $stmt = $conn->prepare("
        SELECT p.*, c.name as category_name, c.slug as category_slug, c.color as category_color, u.name as author_name, u.avatar as author_avatar, u.bio as author_bio
        FROM posts p 
        LEFT JOIN categories c ON p.category_id = c.id 
        LEFT JOIN users u ON p.author_id = u.id 
        WHERE p.slug = :slug AND p.status = 'published' LIMIT 1
    ");
    $stmt->execute([':slug' => $slug]);
    return $stmt->fetch();
}

function get_post_tags($post_id)
{
    global $conn;
    $stmt = $conn->prepare("SELECT t.name, t.slug FROM tags t JOIN post_tags pt ON t.id = pt.tag_id WHERE pt.post_id = :pid");
    $stmt->execute([':pid' => $post_id]);
    return $stmt->fetchAll();
}

function get_post_comments($post_id)
{
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM comments WHERE post_id = :pid AND status = 'approved' ORDER BY created_at DESC");
    $stmt->execute([':pid' => $post_id]);
    return $stmt->fetchAll();
}

function get_ad($location)
{
    global $conn;
    try {
        $stmt = $conn->prepare("SELECT * FROM ads WHERE location = :loc AND status = 1 ORDER BY RAND() LIMIT 1");
        $stmt->execute([':loc' => $location]);
        $ad = $stmt->fetch();

        if (!$ad) return '';

        if ($ad['type'] === 'image') {
            $img_url = (strpos($ad['image_path'], 'http') === 0) ? $ad['image_path'] : BASE_URL . '/' . $ad['image_path'];
            return '<div class="ad-slot text-center">
                        <a href="' . htmlspecialchars($ad['destination_url']) . '" target="_blank" rel="nofollow">
                            <img src="' . $img_url . '" alt="Advertisement" class="mx-auto rounded-xl shadow-sm hover:opacity-90 transition max-w-full h-auto">
                        </a>
                    </div>';
        } else {
            return '<div class="ad-slot overflow-hidden flex justify-center w-full">' . $ad['code'] . '</div>';
        }
    } catch (Exception $e) {
        return '';
    }
}

function truncate_text($text, $limit = 100)
{
    if (mb_strlen($text) <= $limit) return $text;
    return mb_substr(strip_tags($text), 0, $limit) . '...';
}

/**
 * Safe display date — never show Unix epoch (Jan 1, 1970).
 * Accepts a date string or a post row (uses published_at, then created_at).
 */
function format_post_date($date_or_post, $format = 'M d, Y')
{
    $date = null;
    if (is_array($date_or_post)) {
        $date = $date_or_post['published_at'] ?? null;
        if (empty($date) || $date === '0000-00-00 00:00:00') {
            $date = $date_or_post['created_at'] ?? null;
        }
    } else {
        $date = $date_or_post;
    }

    if (empty($date) || $date === '0000-00-00 00:00:00') {
        return '';
    }

    $ts = strtotime($date);
    if ($ts === false || $ts <= 0) {
        return '';
    }

    return date($format, $ts);
}
