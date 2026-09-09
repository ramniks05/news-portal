<?php
require_once '../config/database.php';
require_once '../helpers/query_functions.php';
require_once '../config/constants.php';

$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 10;
$limit = 6;

$sql = "SELECT p.*, c.name as category_name, c.slug as category_slug, u.name as author_name 
        FROM posts p 
        LEFT JOIN categories c ON p.category_id = c.id 
        LEFT JOIN users u ON p.author_id = u.id 
        WHERE p.status = 'published'
        ORDER BY p.published_at DESC LIMIT :limit OFFSET :offset";

$stmt = $conn->prepare($sql);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$posts = $stmt->fetchAll();

if (count($posts) > 0) {
    foreach ($posts as $post) {
?>
        <article class="flex flex-col sm:flex-row gap-4 sm:gap-6 group animate-fade-in">
            <div class="sm:w-1/3 flex-shrink-0 relative overflow-hidden rounded-lg h-48 sm:h-auto">
                <a href="<?= article_url($post['slug']) ?>">
                    <img src="<?= get_post_thumbnail($post['featured_image'] ?? '') ?>" alt="<?= htmlspecialchars($post['title']) ?>" loading="lazy" class="w-full h-full object-cover transition duration-500 group-hover:scale-105">
                </a>
            </div>
            <div class="flex-1 flex flex-col justify-center">
                <a href="<?= category_url($post['category_slug']) ?>" class="text-[10px] font-black text-indigo-600 uppercase tracking-wider mb-2 block">
                    <?= htmlspecialchars($post['category_name'] ?? 'Uncategorized') ?>
                </a>
                <h2 class="text-xl font-bold text-slate-800 mb-2 leading-snug group-hover:text-indigo-700 transition">
                    <a href="<?= article_url($post['slug']) ?>">
                        <?= htmlspecialchars($post['title']) ?>
                    </a>
                </h2>
                <p class="text-slate-500 text-sm line-clamp-2 mb-3 leading-relaxed">
                    <?= htmlspecialchars($post['summary']) ?>
                </p>
                <div class="flex items-center gap-3 text-[11px] text-slate-400 font-bold uppercase tracking-tight">
                    <span><i class="fa-regular fa-clock mr-1"></i> <?= format_post_date($post) ?></span>
                    <span>•</span>
                    <span><?= htmlspecialchars($post['author_name'] ?? 'Admin') ?></span>
                </div>
            </div>
        </article>
<?php
    }
} else {
    echo "NO_MORE";
}
?>