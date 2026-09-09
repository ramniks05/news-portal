<?php
require_once 'includes/header.php';
require_once '../config/database.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$filter = $_GET['filter'] ?? 'pending';
$allowed_filters = ['pending', 'approved', 'spam', 'trash', 'all'];
if (!in_array($filter, $allowed_filters, true)) {
    $filter = 'pending';
}

$stats = $conn->query("
    SELECT 
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN status = 'spam' THEN 1 ELSE 0 END) as spam,
        SUM(CASE WHEN status = 'trash' THEN 1 ELSE 0 END) as trash,
        COUNT(*) as total
    FROM comments
")->fetch(PDO::FETCH_ASSOC);

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$where = 'WHERE 1=1';
$params = [];
if ($filter !== 'all') {
    $where = 'WHERE c.status = :status';
    $params[':status'] = $filter;
}

$count_sql = "SELECT COUNT(*) FROM comments c $where";
$count_stmt = $conn->prepare($count_sql);
$count_stmt->execute($params);
$total_rows = (int)$count_stmt->fetchColumn();
$total_pages = max(1, (int)ceil($total_rows / $limit));

$list_sql = "
    SELECT c.*, p.title AS post_title, p.slug AS post_slug
    FROM comments c
    LEFT JOIN posts p ON c.post_id = p.id
    $where
    ORDER BY c.created_at DESC
    LIMIT :limit OFFSET :offset
";
$stmt = $conn->prepare($list_sql);
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

$csrf = $_SESSION['csrf_token'];
?>

<div x-data="{ selectAll: false }">

    <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-800 dark:text-white">Comments</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400">Moderate reader comments before they appear on articles.</p>
        </div>
    </div>

    <?php if (!empty($_SESSION['success'])): ?>
        <div class="mb-4 rounded-md bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm dark:bg-green-900/20 dark:border-green-800 dark:text-green-300">
            <?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['error'])): ?>
        <div class="mb-4 rounded-md bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm dark:bg-red-900/20 dark:border-red-800 dark:text-red-300">
            <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <div class="border-b border-slate-200 dark:border-slate-700 mb-6">
        <nav class="-mb-px flex flex-wrap gap-x-6 gap-y-2" aria-label="Tabs">
            <?php
            $tabs = [
                'pending' => ['Pending', $stats['pending'] ?? 0, 'amber'],
                'approved' => ['Approved', $stats['approved'] ?? 0, 'green'],
                'spam' => ['Spam', $stats['spam'] ?? 0, 'rose'],
                'trash' => ['Trash', $stats['trash'] ?? 0, 'slate'],
                'all' => ['All', $stats['total'] ?? 0, 'indigo'],
            ];
            foreach ($tabs as $key => $tab):
                $active = ($filter === $key)
                    ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400'
                    : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300';
            ?>
                <a href="?filter=<?= $key ?>" class="<?= $active ?> whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm flex items-center gap-2">
                    <?= $tab[0] ?>
                    <span class="bg-<?= $tab[2] ?>-100 text-<?= $tab[2] ?>-700 dark:bg-<?= $tab[2] ?>-900/40 dark:text-<?= $tab[2] ?>-300 py-0.5 px-2 rounded-full text-xs"><?= (int)$tab[1] ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
    </div>

    <form action="handlers/comment_handler.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
        <input type="hidden" name="action" value="bulk">
        <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">

        <div class="mb-4 flex flex-col sm:flex-row gap-3 sm:items-center">
            <select name="bulk_action" class="rounded-md border border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white text-sm px-3 py-2">
                <option value="">Bulk actions</option>
                <option value="approve">Approve</option>
                <option value="spam">Mark as spam</option>
                <option value="trash">Move to trash</option>
                <option value="delete">Delete permanently</option>
            </select>
            <button type="submit" class="inline-flex items-center px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white text-sm font-medium rounded-md">
                Apply
            </button>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-lg shadow border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-slate-600 dark:text-slate-400">
                    <thead class="text-xs text-slate-700 uppercase bg-slate-50 dark:bg-slate-700 dark:text-slate-200">
                        <tr>
                            <th class="px-4 py-3 w-10">
                                <input type="checkbox" class="rounded border-slate-300"
                                    @change="selectAll = $event.target.checked; document.querySelectorAll('.comment-check').forEach(el => el.checked = selectAll)">
                            </th>
                            <th class="px-4 py-3">Comment</th>
                            <th class="px-4 py-3 w-48">Article</th>
                            <th class="px-4 py-3 w-28 text-center">Status</th>
                            <th class="px-4 py-3 w-44 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                        <?php if (empty($comments)): ?>
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-slate-400">
                                    No comments in this list.
                                </td>
                            </tr>
                        <?php endif; ?>
                        <?php foreach ($comments as $c): ?>
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/40 align-top">
                                <td class="px-4 py-4">
                                    <input type="checkbox" name="ids[]" value="<?= (int)$c['id'] ?>" class="comment-check rounded border-slate-300">
                                </td>
                                <td class="px-4 py-4">
                                    <div class="font-bold text-slate-800 dark:text-white"><?= htmlspecialchars($c['name']) ?></div>
                                    <div class="text-xs text-slate-400 mb-2"><?= htmlspecialchars($c['email']) ?> · <?= date('M d, Y H:i', strtotime($c['created_at'])) ?></div>
                                    <p class="text-sm text-slate-600 dark:text-slate-300 whitespace-pre-wrap"><?= htmlspecialchars($c['content']) ?></p>
                                </td>
                                <td class="px-4 py-4">
                                    <?php if (!empty($c['post_slug'])): ?>
                                        <a href="<?= BASE_URL ?>/article/<?= htmlspecialchars($c['post_slug']) ?>" target="_blank" class="text-indigo-600 hover:underline text-xs font-medium leading-snug block">
                                            <?= htmlspecialchars($c['post_title'] ?? 'View article') ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-xs text-slate-400">Deleted post</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <?php
                                    $badge = [
                                        'pending' => 'bg-amber-50 text-amber-700 ring-amber-600/20',
                                        'approved' => 'bg-green-50 text-green-700 ring-green-600/20',
                                        'spam' => 'bg-rose-50 text-rose-700 ring-rose-600/20',
                                        'trash' => 'bg-slate-100 text-slate-600 ring-slate-500/20',
                                    ];
                                    $cls = $badge[$c['status']] ?? $badge['pending'];
                                    ?>
                                    <span class="inline-flex rounded-full px-2 py-1 text-xs font-medium ring-1 ring-inset <?= $cls ?>">
                                        <?= htmlspecialchars($c['status']) ?>
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-right whitespace-nowrap space-x-1">
                                    <?php if ($c['status'] !== 'approved'): ?>
                                        <a href="handlers/comment_handler.php?action=approve&id=<?= (int)$c['id'] ?>&filter=<?= urlencode($filter) ?>&csrf_token=<?= urlencode($csrf) ?>"
                                            class="inline-flex p-2 text-green-600 hover:bg-green-50 rounded-full" title="Approve">
                                            <i class="fa-solid fa-check"></i>
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($c['status'] !== 'pending'): ?>
                                        <a href="handlers/comment_handler.php?action=pending&id=<?= (int)$c['id'] ?>&filter=<?= urlencode($filter) ?>&csrf_token=<?= urlencode($csrf) ?>"
                                            class="inline-flex p-2 text-amber-600 hover:bg-amber-50 rounded-full" title="Mark pending">
                                            <i class="fa-solid fa-clock"></i>
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($c['status'] !== 'spam'): ?>
                                        <a href="handlers/comment_handler.php?action=spam&id=<?= (int)$c['id'] ?>&filter=<?= urlencode($filter) ?>&csrf_token=<?= urlencode($csrf) ?>"
                                            class="inline-flex p-2 text-rose-600 hover:bg-rose-50 rounded-full" title="Spam">
                                            <i class="fa-solid fa-ban"></i>
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($c['status'] !== 'trash'): ?>
                                        <a href="handlers/comment_handler.php?action=trash&id=<?= (int)$c['id'] ?>&filter=<?= urlencode($filter) ?>&csrf_token=<?= urlencode($csrf) ?>"
                                            class="inline-flex p-2 text-slate-500 hover:bg-slate-100 rounded-full" title="Trash">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </a>
                                    <?php else: ?>
                                        <a href="handlers/comment_handler.php?action=delete&id=<?= (int)$c['id'] ?>&filter=<?= urlencode($filter) ?>&csrf_token=<?= urlencode($csrf) ?>"
                                            onclick="return confirm('Permanently delete this comment?')"
                                            class="inline-flex p-2 text-red-600 hover:bg-red-50 rounded-full" title="Delete forever">
                                            <i class="fa-solid fa-xmark"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </form>

    <?php if ($total_pages > 1): ?>
        <div class="mt-6 flex justify-center gap-2">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?filter=<?= urlencode($filter) ?>&page=<?= $i ?>"
                    class="px-3 py-1.5 rounded-md text-sm font-medium <?= $i === $page ? 'bg-indigo-600 text-white' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 dark:bg-slate-800 dark:border-slate-600 dark:text-slate-300' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
