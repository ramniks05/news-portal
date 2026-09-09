<?php
require_once 'includes/header.php';
require_once '../config/database.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$filter = $_GET['filter'] ?? 'new';
$allowed = ['new', 'read', 'archived', 'all'];
if (!in_array($filter, $allowed, true)) {
    $filter = 'new';
}

$stats = $conn->query("
    SELECT
        SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END) as new_count,
        SUM(CASE WHEN status = 'read' THEN 1 ELSE 0 END) as read_count,
        SUM(CASE WHEN status = 'archived' THEN 1 ELSE 0 END) as archived,
        COUNT(*) as total
    FROM contact_messages
")->fetch(PDO::FETCH_ASSOC);

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

$where = 'WHERE 1=1';
$params = [];
if ($filter !== 'all') {
    $where = 'WHERE status = :status';
    $params[':status'] = $filter;
}

$count_stmt = $conn->prepare("SELECT COUNT(*) FROM contact_messages $where");
$count_stmt->execute($params);
$total_rows = (int)$count_stmt->fetchColumn();
$total_pages = max(1, (int)ceil($total_rows / $limit));

$list_sql = "SELECT * FROM contact_messages $where ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
$stmt = $conn->prepare($list_sql);
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
$csrf = $_SESSION['csrf_token'];
?>

<div class="mb-6">
    <h2 class="text-2xl font-bold text-slate-800 dark:text-white">Contact Messages</h2>
    <p class="text-sm text-slate-500 dark:text-slate-400">Messages submitted from the public contact form.</p>
</div>

<?php if (!empty($_SESSION['success'])): ?>
    <div class="mb-4 rounded-md bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm"><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
<?php endif; ?>
<?php if (!empty($_SESSION['error'])): ?>
    <div class="mb-4 rounded-md bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
<?php endif; ?>

<div class="flex flex-wrap gap-2 mb-6">
    <?php
    $tabs = [
        'new' => ['New', (int)($stats['new_count'] ?? 0)],
        'read' => ['Read', (int)($stats['read_count'] ?? 0)],
        'archived' => ['Archived', (int)($stats['archived'] ?? 0)],
        'all' => ['All', (int)($stats['total'] ?? 0)],
    ];
    foreach ($tabs as $key => [$label, $count]):
    ?>
        <a href="?filter=<?= $key ?>" class="px-3 py-1.5 rounded-md text-sm font-medium <?= $filter === $key ? 'bg-indigo-600 text-white' : 'bg-white border border-slate-200 text-slate-600 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-300' ?>">
            <?= $label ?> (<?= $count ?>)
        </a>
    <?php endforeach; ?>
</div>

<div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
    <?php if (empty($messages)): ?>
        <p class="p-8 text-center text-slate-500 text-sm">No messages in this folder.</p>
    <?php else: ?>
        <div class="divide-y divide-slate-100 dark:divide-slate-700">
            <?php foreach ($messages as $msg): ?>
                <div class="p-5 <?= $msg['status'] === 'new' ? 'bg-indigo-50/40 dark:bg-indigo-900/10' : '' ?>">
                    <div class="flex flex-col md:flex-row md:items-start justify-between gap-4">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2 mb-1">
                                <p class="font-bold text-slate-900 dark:text-white"><?= htmlspecialchars($msg['name']) ?></p>
                                <a href="mailto:<?= htmlspecialchars($msg['email']) ?>" class="text-sm text-indigo-600 hover:underline"><?= htmlspecialchars($msg['email']) ?></a>
                                <span class="text-[10px] uppercase font-bold tracking-wider text-slate-400"><?= htmlspecialchars($msg['status']) ?></span>
                            </div>
                            <?php if (!empty($msg['subject'])): ?>
                                <p class="text-sm font-semibold text-slate-700 dark:text-slate-200 mb-2"><?= htmlspecialchars($msg['subject']) ?></p>
                            <?php endif; ?>
                            <p class="text-sm text-slate-600 dark:text-slate-300 whitespace-pre-wrap"><?= htmlspecialchars($msg['message']) ?></p>
                            <p class="text-[11px] text-slate-400 mt-3"><?= date('M d, Y · h:i A', strtotime($msg['created_at'])) ?><?php if ($msg['ip_address']): ?> · IP <?= htmlspecialchars($msg['ip_address']) ?><?php endif; ?></p>
                        </div>
                        <div class="flex flex-wrap gap-2 flex-shrink-0">
                            <?php if ($msg['status'] !== 'read'): ?>
                                <form method="post" action="handlers/contact_message_handler.php">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                                    <input type="hidden" name="id" value="<?= (int)$msg['id'] ?>">
                                    <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
                                    <input type="hidden" name="action" value="read">
                                    <button class="px-3 py-1.5 text-xs font-bold rounded-md bg-emerald-50 text-emerald-700 border border-emerald-200">Mark Read</button>
                                </form>
                            <?php endif; ?>
                            <?php if ($msg['status'] !== 'archived'): ?>
                                <form method="post" action="handlers/contact_message_handler.php">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                                    <input type="hidden" name="id" value="<?= (int)$msg['id'] ?>">
                                    <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
                                    <input type="hidden" name="action" value="archive">
                                    <button class="px-3 py-1.5 text-xs font-bold rounded-md bg-slate-50 text-slate-700 border border-slate-200">Archive</button>
                                </form>
                            <?php endif; ?>
                            <form method="post" action="handlers/contact_message_handler.php" onsubmit="return confirm('Delete this message permanently?')">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                                <input type="hidden" name="id" value="<?= (int)$msg['id'] ?>">
                                <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
                                <input type="hidden" name="action" value="delete">
                                <button class="px-3 py-1.5 text-xs font-bold rounded-md bg-red-50 text-red-700 border border-red-200">Delete</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php if ($total_pages > 1): ?>
    <div class="mt-6 flex justify-center gap-2">
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="?filter=<?= urlencode($filter) ?>&page=<?= $i ?>" class="px-3 py-1.5 rounded-md text-sm <?= $i === $page ? 'bg-indigo-600 text-white' : 'bg-white border border-slate-200' ?>"><?= $i ?></a>
        <?php endfor; ?>
    </div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
