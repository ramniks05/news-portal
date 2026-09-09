<?php
require_once 'config/database.php';
require_once 'helpers/query_functions.php';

$slug = $_GET['slug'] ?? '';
$stmt = $conn->prepare("SELECT * FROM categories WHERE slug = :slug AND status = 1 LIMIT 1");
$stmt->execute([':slug' => $slug]);
$category = $stmt->fetch();

if (!$category) {
    http_response_code(404);
    $page_title = 'Page Not Found';
    require_once __DIR__ . '/404.php';
    exit();
}

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;
$filter_date = $_GET['date'] ?? '';

// Parent pages include posts from their subcategories
$category_ids = [(int)$category['id']];
$subcategories = [];
if ((int)($category['parent_id'] ?? 0) === 0) {
    $child_stmt = $conn->prepare("SELECT id, name, slug, color FROM categories WHERE parent_id = :pid AND status = 1 ORDER BY name ASC");
    $child_stmt->execute([':pid' => $category['id']]);
    $subcategories = $child_stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($subcategories as $sub) {
        $category_ids[] = (int)$sub['id'];
    }
}

$id_placeholders = implode(',', array_fill(0, count($category_ids), '?'));

$query_parts = "FROM posts p 
                JOIN categories c ON p.category_id = c.id 
                JOIN users u ON p.author_id = u.id 
                WHERE p.category_id IN ($id_placeholders) AND p.status = 'published'";

$count_params = $category_ids;
$sql_params = $category_ids;

if (!empty($filter_date)) {
    $query_parts .= " AND DATE(p.published_at) = ?";
    $count_params[] = $filter_date;
    $sql_params[] = $filter_date;
}

$count_sql = "SELECT COUNT(*) " . $query_parts;
$count_stmt = $conn->prepare($count_sql);
$count_stmt->execute($count_params);
$total_posts = $count_stmt->fetchColumn();
$total_pages = ceil($total_posts / $limit);

$sql = "SELECT p.*, c.name as category_name, c.slug as category_slug, c.color as category_color, u.name as author_name " .
    $query_parts . " ORDER BY p.published_at DESC LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql);
$bind_index = 1;
foreach ($sql_params as $val) {
    $stmt->bindValue($bind_index++, $val);
}
$stmt->bindValue($bind_index++, $limit, PDO::PARAM_INT);
$stmt->bindValue($bind_index++, $offset, PDO::PARAM_INT);
$stmt->execute();
$posts = $stmt->fetchAll();

$page_title = $category['name'];
require_once 'layouts/header.php';
?>

<main class="container mx-auto px-4 py-8">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-10 border-b border-slate-200 pb-8">
        <div>
            <nav class="flex text-[10px] font-black uppercase tracking-widest text-slate-400 mb-3">
                <a href="<?= BASE_URL ?>" class="hover:text-indigo-600">Home</a>
                <span class="mx-3 text-slate-200">/</span>
                <span class="text-indigo-600"><?= htmlspecialchars($category['name']) ?></span>
            </nav>
            <h1 class="text-4xl font-black text-slate-900 flex items-center gap-4">
                <span class="w-2 h-10 rounded-full" style="background-color: <?= $category['color'] ?>;"></span>
                <?= htmlspecialchars($category['name']) ?>
            </h1>
            <?php if ($category['description']): ?>
                <p class="text-slate-500 text-sm mt-2 max-w-xl"><?= htmlspecialchars($category['description']) ?></p>
            <?php endif; ?>
            <?php if (!empty($subcategories)): ?>
                <div class="flex flex-wrap gap-2 mt-4">
                    <?php foreach ($subcategories as $sub): ?>
                        <a href="<?= category_url($sub['slug']) ?>"
                            class="inline-flex items-center px-3 py-1.5 rounded-full text-[11px] font-bold uppercase tracking-wide border border-slate-200 bg-white text-slate-600 hover:border-indigo-300 hover:text-indigo-600 hover:bg-indigo-50 transition">
                            <?= htmlspecialchars($sub['name']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <form action="<?= category_url($slug) ?>" method="GET" class="flex items-center gap-2 bg-slate-50 p-2 rounded-2xl border border-slate-200">
            <input type="date" name="date" value="<?= $filter_date ?>"
                class="text-xs font-bold border-none bg-transparent focus:ring-0 outline-none text-slate-700">
            <button type="submit" class="bg-indigo-600 text-white px-5 py-2 rounded-xl text-xs font-black uppercase tracking-widest hover:bg-indigo-700 transition shadow-lg shadow-indigo-100">
                Filter
            </button>
            <?php if ($filter_date): ?>
                <a href="<?= category_url($slug) ?>" class="text-red-500 p-2 hover:bg-red-50 rounded-full transition" title="Clear Filter">
                    <i class="fa-solid fa-circle-xmark"></i>
                </a>
            <?php endif; ?>
        </form>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">

        <div class="lg:col-span-2">
            <?php if (count($posts) > 0): ?>
                <div class="space-y-12">
                    <?php foreach ($posts as $post):
                        $image_src = get_post_thumbnail($post['featured_image'] ?? '');
                    ?>
                        <article class="flex flex-col sm:flex-row gap-6 md:gap-8 group animate-fade-in">
                            <div class="md:w-1/3 flex-shrink-0 relative overflow-hidden rounded-2xl h-48 md:h-36 lg:h-44 shadow-sm bg-slate-100">
                                <a href="<?= article_url($post['slug']) ?>">
                                    <img src="<?= $image_src ?>"
                                        alt="<?= htmlspecialchars($post['title']) ?>"
                                        class="w-full h-full object-cover transition duration-700 group-hover:scale-110">
                                </a>
                                <div class="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                            </div>

                            <div class="flex-1 flex flex-col justify-center">
                                <?php if (!empty($post['category_slug']) && $post['category_slug'] !== $slug): ?>
                                    <a href="<?= category_url($post['category_slug']) ?>" class="text-[10px] font-black uppercase tracking-wider mb-2 block" style="color: <?= htmlspecialchars($post['category_color'] ?? '#4f46e5') ?>">
                                        <?= htmlspecialchars($post['category_name']) ?>
                                    </a>
                                <?php endif; ?>
                                <h2 class="text-2xl font-black text-slate-900 mb-3 leading-tight group-hover:text-indigo-600 transition">
                                    <a href="<?= article_url($post['slug']) ?>">
                                        <?= htmlspecialchars($post['title']) ?>
                                    </a>
                                </h2>
                                <p class="text-slate-500 text-sm line-clamp-3 mb-5 leading-relaxed">
                                    <?= htmlspecialchars($post['summary']) ?>
                                </p>
                                <div class="flex items-center gap-4 text-[11px] text-slate-400 font-black uppercase tracking-tight">
                                    <span class="flex items-center gap-1.5"><i class="fa-solid fa-user-pen text-indigo-500"></i> <?= htmlspecialchars($post['author_name']) ?></span>
                                    <span class="text-slate-200">|</span>
                                    <span class="flex items-center gap-1.5"><i class="fa-regular fa-calendar text-indigo-500"></i> <?= format_post_date($post) ?></span>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
                <?php if ($total_pages > 1): ?>
                    <div class="mt-16 flex justify-center items-center gap-3">
                        <?php if ($page > 1): ?>
                            <a href="<?= category_url($slug) ?>?page=<?= $page - 1 ?><?= $filter_date ? '&date=' . $filter_date : '' ?>"
                                class="w-12 h-12 flex items-center justify-center rounded-2xl bg-white border border-slate-200 text-slate-600 hover:bg-indigo-600 hover:text-white hover:border-indigo-600 transition shadow-sm">
                                <i class="fa-solid fa-arrow-left"></i>
                            </a>
                        <?php endif; ?>

                        <div class="flex gap-2">
                            <?php
                            for ($i = 1; $i <= $total_pages; $i++):
                            ?>
                                <a href="<?= category_url($slug) ?>?page=<?= $i ?><?= $filter_date ? '&date=' . $filter_date : '' ?>"
                                    class="w-12 h-12 flex items-center justify-center rounded-2xl font-black text-sm transition 
                               <?= $i == $page ? 'bg-indigo-600 text-white shadow-xl shadow-indigo-100' : 'bg-white text-slate-600 border border-slate-100 hover:bg-slate-50' ?>">
                                    <?= $i ?>
                                </a>
                            <?php endfor; ?>
                        </div>

                        <?php if ($page < $total_pages): ?>
                            <a href="<?= category_url($slug) ?>?page=<?= $page + 1 ?><?= $filter_date ? '&date=' . $filter_date : '' ?>"
                                class="w-12 h-12 flex items-center justify-center rounded-2xl bg-white border border-slate-200 text-slate-600 hover:bg-indigo-600 hover:text-white hover:border-indigo-600 transition shadow-sm">
                                <i class="fa-solid fa-arrow-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <div class="text-center py-24 bg-white rounded-[2.5rem] border-2 border-dashed border-slate-200">
                    <div class="inline-flex h-24 w-24 items-center justify-center rounded-full bg-slate-50 text-slate-200 mb-6">
                        <i class="fa-solid fa-box-open text-5xl"></i>
                    </div>
                    <h3 class="text-2xl font-black text-slate-800">No articles found</h3>
                    <p class="text-slate-500 mt-2 font-medium">Try adjusting your filters or check back later.</p>
                    <a href="<?= BASE_URL ?>" class="inline-block mt-8 bg-slate-900 text-white px-8 py-3 rounded-2xl font-bold hover:bg-indigo-600 transition shadow-lg">Back to Home</a>
                </div>
            <?php endif; ?>
        </div>
        <aside class="lg:col-span-1 space-y-12">

            <div class="sticky top-24">
                <div class="bg-white p-5 rounded-[2rem] border border-slate-200 shadow-sm text-center mb-10">
                    <span class="text-[10px] text-slate-400 uppercase tracking-widest font-black block mb-3">- Advertisement -</span>
                    <?= get_ad('sidebar_top') ?>
                </div>
                <div class="bg-slate-900 rounded-[2.5rem] p-8 text-white shadow-2xl shadow-indigo-900/20">
                    <h4 class="font-black text-xl mb-6 border-l-4 border-indigo-500 pl-4 uppercase tracking-tighter italic">Explore Sections</h4>
                    <div class="flex flex-wrap gap-2">
                        <?php
                        $all_cats = $conn->query("SELECT name, slug FROM categories WHERE status = 1 LIMIT 15")->fetchAll();
                        foreach ($all_cats as $cat_link):
                        ?>
                            <a href="<?= category_url($cat_link['slug']) ?>"
                                class="text-[11px] font-black uppercase tracking-widest px-4 py-2 rounded-xl bg-slate-800 hover:bg-indigo-600 transition border border-slate-700 active:scale-95">
                                <?= htmlspecialchars($cat_link['name']) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

        </aside>
    </div>

</main>

<style>
    .line-clamp-3 {
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
</style>

<?php include 'layouts/footer.php'; ?>