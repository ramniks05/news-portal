<?php
require_once 'config/database.php';
require_once 'helpers/query_functions.php';

$slug = trim($_GET['slug'] ?? '');
$stmt = $conn->prepare("SELECT * FROM tags WHERE slug = :slug LIMIT 1");
$stmt->execute([':slug' => $slug]);
$tag = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$tag) {
    http_response_code(404);
    $page_title = 'Page Not Found';
    require_once __DIR__ . '/404.php';
    exit();
}

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;
$total = count_posts_by_tag_slug($slug);
$total_pages = max(1, (int)ceil($total / $limit));
$posts = get_posts_by_tag_slug($slug, $limit, $offset);

$page_title = 'Tag: ' . $tag['name'];
require_once 'layouts/header.php';
?>

<main class="container mx-auto px-4 py-8 md:py-12">
    <nav class="flex text-[10px] font-black uppercase tracking-widest text-slate-400 mb-4">
        <a href="<?= BASE_URL ?>" class="hover:text-indigo-600">Home</a>
        <span class="mx-3 text-slate-200">/</span>
        <span class="text-indigo-600">#<?= htmlspecialchars($tag['name']) ?></span>
    </nav>

    <div class="mb-10 border-b border-slate-200 pb-8">
        <h1 class="text-3xl md:text-4xl font-black text-slate-900">#<?= htmlspecialchars($tag['name']) ?></h1>
        <p class="text-slate-500 text-sm mt-2"><?= number_format($total) ?> published <?= $total === 1 ? 'story' : 'stories' ?></p>
    </div>

    <?php if (empty($posts)): ?>
        <p class="text-slate-500 py-10">No published stories with this tag yet.</p>
    <?php else: ?>
        <div class="space-y-10 max-w-4xl">
            <?php foreach ($posts as $post): ?>
                <article class="flex flex-col sm:flex-row gap-6 group">
                    <div class="sm:w-1/3 h-44 overflow-hidden rounded-2xl bg-slate-100 flex-shrink-0">
                        <a href="<?= article_url($post['slug']) ?>">
                            <img src="<?= get_post_thumbnail($post['featured_image'] ?? '') ?>" alt="<?= htmlspecialchars($post['title']) ?>" loading="lazy" class="w-full h-full object-cover transition duration-500 group-hover:scale-105">
                        </a>
                    </div>
                    <div class="flex-1">
                        <a href="<?= category_url($post['category_slug'] ?? '') ?>" class="text-[10px] font-black uppercase tracking-wider text-indigo-600"><?= htmlspecialchars($post['category_name'] ?? '') ?></a>
                        <h2 class="text-xl font-bold text-slate-900 mt-1 mb-2 group-hover:text-indigo-600 transition">
                            <a href="<?= article_url($post['slug']) ?>"><?= htmlspecialchars($post['title']) ?></a>
                        </h2>
                        <p class="text-slate-500 text-sm line-clamp-2 mb-3"><?= htmlspecialchars($post['summary'] ?? '') ?></p>
                        <p class="text-[11px] text-slate-400 font-bold uppercase"><?= format_post_date($post) ?> · <?= htmlspecialchars($post['author_name'] ?? '') ?></p>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <?php if ($total_pages > 1): ?>
            <div class="mt-12 flex justify-center gap-2">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="<?= tag_url($slug) ?>?page=<?= $i ?>" class="px-3 py-1.5 rounded-md text-sm font-medium <?= $i === $page ? 'bg-indigo-600 text-white' : 'bg-white border border-slate-200 text-slate-600' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</main>

<?php require_once 'layouts/footer.php'; ?>
