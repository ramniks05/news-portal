<?php
require_once 'config/database.php';
require_once 'helpers/query_functions.php';
$search_query = $_GET['q'] ?? '';
$filter_category_slug = $_GET['cat'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;
$all_cats = $conn->query("SELECT id, name, slug FROM categories WHERE status = 1 ORDER BY name ASC")->fetchAll();
$query_parts = "
    FROM posts p 
    LEFT JOIN categories c ON p.category_id = c.id 
    LEFT JOIN users u ON p.author_id = u.id 
    WHERE p.status = 'published'
";
$params = [];

if (!empty($search_query)) {
    $query_parts .= " AND (p.title LIKE :query OR p.content LIKE :query)";
    $params[':query'] = "%{$search_query}%";
}

if (!empty($filter_category_slug)) {
    $query_parts .= " AND c.slug = :cat_slug";
    $params[':cat_slug'] = $filter_category_slug;
}
$count_stmt = $conn->prepare("SELECT COUNT(*) " . $query_parts);
$count_stmt->execute($params);
$total_results = $count_stmt->fetchColumn();
$total_pages = ceil($total_results / $limit);

$sql = "
    SELECT p.*, c.name as category_name, c.slug as category_slug, u.name as author_name 
    " . $query_parts . "
    ORDER BY p.published_at DESC 
    LIMIT :limit OFFSET :offset
";

$stmt = $conn->prepare($sql);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->execute();
$results = $stmt->fetchAll();

function get_search_pagination_url($page)
{
    $qs = $_GET;
    $qs['page'] = $page;
    return BASE_URL . "/search?" . http_build_query($qs);
}

$page_title = $search_query ? "Search: " . $search_query : "Search Articles";
require_once 'layouts/header.php';
?>

<main class="container mx-auto px-4 py-8 md:py-12">
    <div class="mb-10">
        <h1 class="text-3xl md:text-4xl font-black text-slate-900 mb-2 tracking-tight">
            Search Results
        </h1>
        <p class="text-slate-500 font-medium">
            Found <span class="text-indigo-600 font-bold"><?= number_format($total_results) ?></span> results
            <?php if ($search_query): ?>
                for "<span class="text-slate-800 italic"><?= htmlspecialchars($search_query) ?></span>"
            <?php endif; ?>
        </p>
    </div>
    <div class="bg-white p-5 rounded-2xl shadow-soft border border-slate-100 mb-12">
        <form action="<?= BASE_URL ?>/search" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <div class="md:col-span-2">
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-1">Looking for something specific?</label>
                <div class="relative group">
                    <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-indigo-500 transition-colors"></i>
                    <input type="text" name="q" value="<?= htmlspecialchars($search_query) ?>"
                        placeholder="Enter keywords..." required
                        class="w-full pl-11 pr-4 py-3.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:bg-white outline-none transition-all text-sm font-bold">
                </div>
            </div>

            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-1">Category</label>
                <select name="cat" class="w-full px-4 py-3.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none text-sm font-bold appearance-none cursor-pointer">
                    <option value="">All Sections</option>
                    <?php foreach ($all_cats as $cat): ?>
                        <option value="<?= $cat['slug'] ?>" <?= $filter_category_slug === $cat['slug'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-black py-4 rounded-xl shadow-lg shadow-indigo-100 transition-all active:scale-95 text-sm uppercase tracking-widest">
                Update Search
            </button>
        </form>
    </div>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">

        <div class="lg:col-span-2">
            <?php if (count($results) > 0): ?>
                <div class="space-y-10">
                    <?php foreach ($results as $post): ?>
                        <article class="flex flex-col sm:flex-row gap-6 group animate-fade-in">
                            <div class="sm:w-1/3 flex-shrink-0 relative overflow-hidden rounded-2xl h-52 sm:h-36 lg:h-40 shadow-sm bg-slate-100">
                                <a href="<?= article_url($post['slug']) ?>">
                                    <img src="<?= get_post_thumbnail($post['featured_image'] ?? '') ?>" alt="<?= htmlspecialchars($post['title']) ?>"
                                        loading="lazy" class="w-full h-full object-cover transition duration-500 group-hover:scale-110">
                                </a>
                            </div>

                            <div class="flex-1 flex flex-col justify-center">
                                <a href="<?= category_url($post['category_slug']) ?>"
                                    class="text-[10px] font-black text-indigo-600 uppercase tracking-wider mb-2 block">
                                    <?= htmlspecialchars($post['category_name']) ?>
                                </a>
                                <h2 class="text-xl font-bold text-slate-900 mb-2 leading-tight group-hover:text-indigo-700 transition">
                                    <a href="<?= article_url($post['slug']) ?>">
                                        <?= htmlspecialchars($post['title']) ?>
                                    </a>
                                </h2>
                                <p class="text-slate-500 text-sm line-clamp-2 mb-3 leading-relaxed">
                                    <?= htmlspecialchars($post['summary']) ?>
                                </p>
                                <div class="text-[11px] text-slate-400 font-bold uppercase tracking-tight flex items-center gap-3">
                                    <span><i class="fa-regular fa-calendar mr-1"></i> <?= format_post_date($post) ?></span>
                                    <span>•</span>
                                    <span><?= htmlspecialchars($post['author_name']) ?></span>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
                <?php if ($total_pages > 1): ?>
                    <div class="mt-16 flex justify-center items-center gap-2">
                        <?php if ($page > 1): ?>
                            <a href="<?= get_search_pagination_url($page - 1) ?>" class="w-10 h-10 flex items-center justify-center rounded-full border border-slate-200 hover:bg-indigo-600 hover:text-white transition shadow-sm">
                                <i class="fa-solid fa-chevron-left text-xs"></i>
                            </a>
                        <?php endif; ?>

                        <div class="flex gap-1">
                            <?php
                            for ($i = 1; $i <= $total_pages; $i++):
                                if ($i == 1 || $i == $total_pages || ($i >= $page - 1 && $i <= $page + 1)):
                            ?>
                                    <a href="<?= get_search_pagination_url($i) ?>"
                                        class="w-10 h-10 flex items-center justify-center rounded-full font-black text-xs transition 
                               <?= $i == $page ? 'bg-indigo-600 text-white shadow-xl shadow-indigo-100' : 'text-slate-500 hover:bg-slate-100' ?>">
                                        <?= $i ?>
                                    </a>
                            <?php endif;
                            endfor; ?>
                        </div>

                        <?php if ($page < $total_pages): ?>
                            <a href="<?= get_search_pagination_url($page + 1) ?>" class="w-10 h-10 flex items-center justify-center rounded-full border border-slate-200 hover:bg-indigo-600 hover:text-white transition shadow-sm">
                                <i class="fa-solid fa-chevron-right text-xs"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <div class="text-center py-20 bg-white rounded-3xl border border-dashed border-slate-200 shadow-soft">
                    <div class="inline-flex h-20 w-20 items-center justify-center rounded-full bg-red-50 text-red-500 mb-6">
                        <i class="fa-regular fa-face-frown-open text-4xl"></i>
                    </div>
                    <h3 class="text-2xl font-black text-slate-800 mb-2">No matches found</h3>
                    <p class="text-slate-500 max-w-sm mx-auto font-medium">
                        We couldn't find any articles for "<?= htmlspecialchars($search_query) ?>". Try different keywords or check your spelling.
                    </p>
                    <a href="<?= BASE_URL ?>" class="inline-block mt-8 bg-slate-900 text-white font-black px-8 py-3 rounded-xl hover:bg-indigo-600 transition-all">
                        Back to Homepage
                    </a>
                </div>
            <?php endif; ?>
        </div>
        <aside class="lg:col-span-1 space-y-10">
            <div class="sticky top-24">
                <div class="bg-white p-4 rounded-3xl border border-slate-200 shadow-sm text-center">
                    <span class="text-[10px] text-slate-400 uppercase tracking-widest block mb-2">- Advertisement -</span>
                    <?= get_ad('sidebar_top') ?>
                </div>
                <div class="mt-8 bg-slate-950 rounded-3xl p-8 text-white shadow-2xl">
                    <h4 class="font-black text-lg mb-6 border-l-4 border-indigo-500 pl-4 uppercase tracking-tighter">Search Tips</h4>
                    <ul class="text-xs space-y-4 text-slate-400 font-bold uppercase tracking-wide">
                        <li class="flex items-start gap-3"><i class="fa-solid fa-check text-indigo-500 mt-1"></i> Check for typos</li>
                        <li class="flex items-start gap-3"><i class="fa-solid fa-check text-indigo-500 mt-1"></i> Use broader keywords</li>
                        <li class="flex items-start gap-3"><i class="fa-solid fa-check text-indigo-500 mt-1"></i> Filter by category</li>
                    </ul>
                </div>
            </div>
        </aside>

    </div>

</main>

<?php require_once 'layouts/footer.php'; ?>