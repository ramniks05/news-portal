<?php

function get_setting($key, $default = '')
{
    global $conn;

    if (!isset($conn)) {
        return $default;
    }

    static $settings_cache = null;

    if ($settings_cache === null) {
        try {
            $stmt = $conn->query("SELECT setting_key, setting_value FROM settings");
            $settings_cache = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        } catch (PDOException $e) {
            return $default;
        }
    }

    return isset($settings_cache[$key]) ? $settings_cache[$key] : $default;
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

if (!function_exists('get_post_thumbnail')) {
    function get_post_thumbnail($path)
    {
        if (!empty($path)) {
            $path = trim($path);
            if (preg_match('#^https?://#i', $path)) {
                return $path;
            }
            $relative = ltrim(str_replace('\\', '/', $path), '/');
            $full = __DIR__ . '/../' . $relative;
            if (is_file($full)) {
                return BASE_URL . '/' . $relative;
            }
            if (strpos($relative, 'uploads/') === 0) {
                return BASE_URL . '/' . $relative;
            }
        }
        return BASE_URL . '/assets/images/static/placeholder.jpg';
    }
}

function format_date($date, $format = 'M d, Y')
{
    if (empty($date) || $date === '0000-00-00 00:00:00') {
        return '';
    }
    $ts = strtotime($date);
    if ($ts === false || $ts <= 0) {
        return '';
    }
    return date($format, $ts);
}

if (!function_exists('format_post_date')) {
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
        return format_date($date, $format);
    }
}

function truncate_text($text, $limit = 100)
{
    $text = strip_tags($text);
    if (strlen($text) <= $limit) {
        return $text;
    }
    $last_space = strrpos(substr($text, 0, $limit), ' ');
    return substr($text, 0, $last_space) . '...';
}

function clean_input($data)
{
    return htmlspecialchars(strip_tags(trim($data)));
}

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

function get_client_ip()
{
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

function is_active_page($slug)
{
    $current = basename($_SERVER['PHP_SELF'], ".php");
    $request_slug = $_GET['slug'] ?? '';

    if ($request_slug === $slug) {
        return true;
    }

    if ($current === $slug) {
        return true;
    }

    return false;
}
