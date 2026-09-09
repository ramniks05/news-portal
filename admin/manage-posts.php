<?php
require_once 'includes/header.php';
require_once '../config/database.php';

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$where_clause = "";
$params = [];

if (!empty($search)) {
    $where_clause = " WHERE p.title LIKE :search OR c.name LIKE :search OR u.name LIKE :search ";
    $params[':search'] = "%$search%";
}

$count_sql = "SELECT COUNT(*) FROM posts p 
              LEFT JOIN categories c ON p.category_id = c.id 
              LEFT JOIN users u ON p.author_id = u.id 
              $where_clause";
$count_stmt = $conn->prepare($count_sql);
$count_stmt->execute($params);
$total_posts = $count_stmt->fetchColumn();
$total_pages = ceil($total_posts / $limit);

$sql = "SELECT p.*, c.name as category_name, u.name as author_name 
        FROM posts p 
        LEFT JOIN categories c ON p.category_id = c.id 
        LEFT JOIN users u ON p.author_id = u.id 
        $where_clause
        ORDER BY p.created_at DESC 
        LIMIT :limit OFFSET :offset";

$stmt = $conn->prepare($sql);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$posts = $stmt->fetchAll();

function get_pagination_url($p, $s)
{
    return "?page=$p" . ($s ? "&search=" . urlencode($s) : "");
}
?>

<div x-data="{ 
    searchQuery: '<?= htmlspecialchars($search) ?>',
    performSearch() {
        window.location.href = '?page=1&search=' + encodeURIComponent(this.searchQuery);
    }
}">

    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-bold text-slate-800 dark:text-white">News Management</h2>
            <p class="text-sm text-slate-500">Manage, edit, or delete your articles (Total: <?= $total_posts ?>)</p>
        </div>

        <div class="flex flex-col sm:flex-row items-center gap-3">
            <div class="relative w-full sm:w-64">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                    <i class="fa-solid fa-magnifying-glass text-sm"></i>
                </span>
                <input
                    type="text"
                    x-model="searchQuery"
                    @input.debounce.500ms="performSearch()"
                    placeholder="Search posts..."
                    class="w-full pl-10 pr-4 py-2 border border-slate-300 dark:border-slate-600 rounded-md bg-white dark:bg-slate-700 text-sm focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                <template x-if="searchQuery.length > 0">
                    <button @click="searchQuery = ''; performSearch()" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-red-500">
                        <i class="fa-solid fa-circle-xmark"></i>
                    </button>
                </template>
            </div>

            <a href="add-post.php" class="w-full sm:w-auto bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-md shadow flex items-center justify-center transition-transform hover:scale-105">
                <i class="fa-solid fa-plus mr-2"></i> Create New
            </a>
        </div>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-lg shadow border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-slate-600 dark:text-slate-400">
                <thead class="text-xs text-slate-700 uppercase bg-slate-50 dark:bg-slate-700 dark:text-slate-200">
                    <tr>
                        <th class="px-6 py-4 w-16">S.N.</th>
                        <th class="px-6 py-4">Title</th>
                        <th class="px-6 py-4">Author</th>
                        <th class="px-6 py-4">Category</th>
                        <th class="px-6 py-4 text-center">Stats</th>
                        <th class="px-6 py-4 text-center">Status</th>
                        <th class="px-6 py-4">Date</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                    <?php if (count($posts) > 0): ?>
                        <?php foreach ($posts as $index => $post):
                            $serial = $total_posts - ($offset + $index);
                        ?>
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                                <td class="px-6 py-4 font-mono text-xs text-slate-400 font-bold"><?= $serial ?></td>
                                <td class="px-6 py-4 max-w-xs">
                                    <div class="font-bold text-slate-800 dark:text-white truncate" title="<?= htmlspecialchars($post['title']) ?>">
                                        <?= htmlspecialchars($post['title']) ?>
                                    </div>
                                    <div class="flex gap-2 mt-1">
                                        <?php if ($post['is_breaking']): ?><span class="text-[9px] bg-red-100 text-red-600 px-1.5 py-0.5 rounded font-bold uppercase tracking-wider">Breaking</span><?php endif; ?>
                                        <?php if ($post['is_featured']): ?><span class="text-[9px] bg-amber-100 text-amber-600 px-1.5 py-0.5 rounded font-bold uppercase tracking-wider">Featured</span><?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <div class="h-7 w-7 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-[10px] font-black border border-indigo-200">
                                            <?= strtoupper(substr($post['author_name'], 0, 1)) ?>
                                        </div>
                                        <span class="text-xs font-medium"><?= htmlspecialchars($post['author_name']) ?></span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 px-2.5 py-1 rounded text-[11px] font-bold">
                                        <?= htmlspecialchars($post['category_name'] ?? 'General') ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex items-center justify-center gap-1.5 text-slate-500" title="Views">
                                        <i class="fa-regular fa-eye text-xs"></i>
                                        <span class="text-xs font-mono font-bold"><?= number_format($post['views']) ?></span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <?php if ($post['status'] === 'published'): ?>
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-green-50 px-2.5 py-1 text-xs font-bold text-green-700 ring-1 ring-inset ring-green-600/20 uppercase tracking-tight">Published</span>
                                    <?php elseif ($post['status'] === 'archived'): ?>
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-600 ring-1 ring-inset ring-slate-400/20 uppercase tracking-tight">Archived</span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-bold text-amber-700 ring-1 ring-inset ring-amber-600/20 uppercase tracking-tight">Draft</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-[11px] whitespace-nowrap leading-tight">
                                    <span class="font-bold text-slate-700 dark:text-slate-300"><?= date('d M, Y', strtotime($post['created_at'])) ?></span><br>
                                    <span class="text-slate-400 font-mono"><?= date('h:i A', strtotime($post['created_at'])) ?></span>
                                </td>
                                <td class="px-6 py-4 text-right whitespace-nowrap">
                                    <div class="flex justify-end gap-2">
                                        <a href="edit-post.php?id=<?= $post['id'] ?>" class="h-8 w-8 flex items-center justify-center rounded-md bg-indigo-50 text-indigo-600 hover:bg-indigo-600 hover:text-white transition-all shadow-sm" title="Edit Article">
                                            <i class="fa-solid fa-pen-to-square text-xs"></i>
                                        </a>
                                        <button @click="$dispatch('confirm-delete', { link: 'handlers/post_handler.php?action=delete&id=<?= $post['id'] ?>' })"
                                            class="h-8 w-8 flex items-center justify-center rounded-md bg-red-50 text-red-500 hover:bg-red-600 hover:text-white transition-all shadow-sm" title="Delete">
                                            <i class="fa-solid fa-trash-can text-xs"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="px-6 py-20 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="h-16 w-16 bg-slate-100 dark:bg-slate-700 rounded-full flex items-center justify-center mb-4 text-slate-300">
                                        <i class="fa-solid fa-magnifying-glass text-3xl"></i>
                                    </div>
                                    <h3 class="text-lg font-bold text-slate-400">No posts found.</h3>
                                    <?php if ($search): ?>
                                        <a href="manage-posts.php" class="mt-2 text-indigo-600 font-bold hover:underline">Clear Search Filter</a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if ($total_pages > 1): ?>
        <div class="mt-6 flex flex-col sm:flex-row items-center justify-between gap-4">
            <p class="text-xs text-slate-500 font-bold order-2 sm:order-1">
                PAGE <?= $page ?> OF <?= $total_pages ?> <span class="mx-1 text-slate-300">|</span> <?= $total_posts ?> ARTICLES
            </p>

            <div class="flex items-center gap-1 order-1 sm:order-2">

                <?php if ($page > 1): ?>
                    <a href="<?= get_pagination_url($page - 1, $search) ?>" class="px-3 py-1.5 border border-slate-300 dark:border-slate-600 rounded text-xs font-bold text-slate-600 dark:text-slate-400 hover:bg-white dark:hover:bg-slate-700 transition-all">
                        <i class="fa-solid fa-angle-left"></i>
                    </a>
                <?php endif; ?>

                <?php
                $range = 1;
                for ($i = 1; $i <= $total_pages; $i++) {
                    if ($i == 1 || $i == $total_pages || ($i >= $page - $range && $i <= $page + $range)) {
                        $active = ($i == $page) ? 'bg-indigo-600 border-indigo-600 text-white shadow-md' : 'bg-white dark:bg-slate-800 border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-700';
                        echo '<a href="' . get_pagination_url($i, $search) . '" class="px-3.5 py-1.5 border rounded text-xs font-bold transition-all ' . $active . '">' . $i . '</a>';
                    } elseif ($i == $page - $range - 1 || $i == $page + $range + 1) {
                        echo '<span class="px-1 text-slate-400 text-xs">...</span>';
                    }
                }
                ?>

                <?php if ($page < $total_pages): ?>
                    <a href="<?= get_pagination_url($page + 1, $search) ?>" class="px-3 py-1.5 border border-slate-300 dark:border-slate-600 rounded text-xs font-bold text-slate-600 dark:text-slate-400 hover:bg-white dark:hover:bg-slate-700 transition-all">
                        <i class="fa-solid fa-angle-right"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

</div>

<?php include 'includes/footer.php'; ?>