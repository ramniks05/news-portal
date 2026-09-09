<?php
/**
 * Custom 404 page.
 * Can be included directly (article/category miss) or hit via Apache ErrorDocument.
 */
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/config/constants.php';
}
if (!function_exists('get_latest_posts')) {
    require_once __DIR__ . '/helpers/query_functions.php';
}

http_response_code(404);
$page_title = $page_title ?? 'Page Not Found';

// Avoid double header if somehow nested
if (!defined('NP_404_RENDERED')) {
    define('NP_404_RENDERED', true);
}

$suggest = [];
try {
    $suggest = get_latest_posts(4, 0);
} catch (Throwable $e) {
    $suggest = [];
}

require_once __DIR__ . '/layouts/header.php';
?>

<main class="container mx-auto px-4 py-16 md:py-24">
    <div class="max-w-2xl mx-auto text-center mb-14">
        <p class="text-[11px] font-black uppercase tracking-[0.25em] text-indigo-600 mb-4">Error 404</p>
        <h1 class="text-4xl md:text-5xl font-black text-slate-900 mb-4 tracking-tight">Page not found</h1>
        <p class="text-slate-500 text-sm md:text-base leading-relaxed mb-8">
            The page or story you are looking for does not exist, was moved, or is no longer published.
        </p>
        <div class="flex flex-col sm:flex-row gap-3 justify-center">
            <a href="<?= BASE_URL ?>" class="inline-flex items-center justify-center gap-2 rounded-full bg-indigo-600 px-6 py-3 text-xs font-black uppercase tracking-widest text-white hover:bg-indigo-700 transition shadow-lg shadow-indigo-100">
                <i class="fa-solid fa-house"></i> Back to Home
            </a>
            <a href="<?= BASE_URL ?>/search" class="inline-flex items-center justify-center gap-2 rounded-full border border-slate-200 bg-white px-6 py-3 text-xs font-black uppercase tracking-widest text-slate-700 hover:border-indigo-300 hover:text-indigo-600 transition">
                <i class="fa-solid fa-magnifying-glass"></i> Search news
            </a>
        </div>
    </div>

    <?php if (!empty($suggest)): ?>
        <div class="max-w-4xl mx-auto">
            <h2 class="text-sm font-black uppercase tracking-widest text-slate-400 mb-6 text-center">Try these latest stories</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <?php foreach ($suggest as $post): ?>
                    <a href="<?= article_url($post['slug']) ?>" class="group flex gap-4 rounded-2xl border border-slate-100 bg-white p-4 shadow-sm hover:shadow-md hover:border-indigo-100 transition">
                        <div class="h-20 w-28 flex-shrink-0 overflow-hidden rounded-xl bg-slate-100">
                            <img src="<?= get_post_thumbnail($post['featured_image'] ?? '') ?>" alt="<?= htmlspecialchars($post['title']) ?>" class="h-full w-full object-cover transition duration-500 group-hover:scale-105" loading="lazy">
                        </div>
                        <div class="min-w-0 text-left">
                            <p class="text-[10px] font-black uppercase tracking-wider text-indigo-600 mb-1"><?= htmlspecialchars($post['category_name'] ?? 'News') ?></p>
                            <h3 class="text-sm font-bold text-slate-900 leading-snug line-clamp-2 group-hover:text-indigo-600 transition"><?= htmlspecialchars($post['title']) ?></h3>
                            <p class="text-[11px] text-slate-400 mt-2 font-medium"><?= format_post_date($post) ?></p>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</main>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>
