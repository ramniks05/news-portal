<?php
$meta_title_content = isset($page_title) ? htmlspecialchars($page_title) : get_config('site_name', 'News Portal');
$meta_description_content = isset($post) && !empty($post['meta_description']) ? htmlspecialchars($post['meta_description']) : get_config('site_description', 'Your trusted source for the latest news updates.');
$meta_image_url = BASE_URL . '/' . get_config('logo_path', 'assets/images/static/social_share_default.jpg'); // ডিফল্ট বা লোগো
$meta_url_content = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
$meta_type = 'website';

if (isset($post) && is_array($post)) {
    $meta_title_content = !empty($post['meta_title']) ? htmlspecialchars($post['meta_title']) : htmlspecialchars($post['title']);
    $meta_description_content = !empty($post['meta_description']) ? htmlspecialchars($post['meta_description']) : htmlspecialchars(strip_tags($post['summary']));

    if (!empty($post['featured_image'])) {
        if (preg_match('#^https?://#i', $post['featured_image'])) {
            $meta_image_url = $post['featured_image'];
        } elseif (file_exists(__DIR__ . '/../' . $post['featured_image'])) {
            $meta_image_url = BASE_URL . '/' . $post['featured_image'];
        } else {
            $meta_image_url = BASE_URL . '/assets/images/static/social_share_default.jpg';
        }
    } else {
        $meta_image_url = BASE_URL . '/assets/images/static/social_share_default.jpg';
    }

    $meta_url_content = article_url($post['slug']);
    $meta_type = 'article';
}

$meta_description_content = substr($meta_description_content, 0, 160);
?>

<meta name="description" content="<?= $meta_description_content ?>">
<meta name="keywords" content="<?= get_config('site_keywords') ?>">
<link rel="canonical" href="<?= $meta_url_content ?>">

<meta property="og:title" content="<?= $meta_title_content ?>">
<meta property="og:description" content="<?= $meta_description_content ?>">
<meta property="og:image" content="<?= $meta_image_url ?>">
<meta property="og:url" content="<?= $meta_url_content ?>">
<meta property="og:site_name" content="<?= get_config('site_name') ?>">
<meta property="og:type" content="<?= $meta_type ?>">
<?php if (isset($post)): ?>
    <?php
    if (!empty($post['published_at']) && $post['published_at'] !== '0000-00-00 00:00:00') {
        $meta_pub_time = strtotime($post['published_at']);
    } else {
        $meta_pub_time = !empty($post['created_at']) ? strtotime($post['created_at']) : time();
    }
    if ($meta_pub_time === false || $meta_pub_time <= 0) {
        $meta_pub_time = time();
    }
    $meta_mod_time = !empty($post['updated_at']) ? strtotime($post['updated_at']) : $meta_pub_time;
    if ($meta_mod_time === false || $meta_mod_time <= 0) {
        $meta_mod_time = $meta_pub_time;
    }
    ?>
    <meta property="article:published_time" content="<?= date('c', $meta_pub_time) ?>">
    <meta property="article:modified_time" content="<?= date('c', $meta_mod_time) ?>">
    <meta property="article:author" content="<?= htmlspecialchars($post['author_name']) ?>">
    <meta property="article:section" content="<?= htmlspecialchars($post['category_name']) ?>">
<?php endif; ?>

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= $meta_title_content ?>">
<meta name="twitter:description" content="<?= $meta_description_content ?>">
<meta name="twitter:image" content="<?= $meta_image_url ?>">
<meta name="twitter:url" content="<?= $meta_url_content ?>">
<meta name="twitter:site" content="@<?= htmlspecialchars(str_replace('https://twitter.com/', '', get_config('social_twitter'))) ?>">