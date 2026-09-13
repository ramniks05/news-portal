<?php
require_once 'includes/header.php';
require_once '../config/database.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$editions = [];
try {
    $editions = $conn->query("SELECT * FROM e_news_editions ORDER BY edition_date DESC, id DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $_SESSION['error'] = 'E-News table missing. Import the latest schema update first.';
}
?>

<div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
        <h2 class="text-2xl font-bold text-slate-800 dark:text-white">E-News PDF</h2>
        <p class="text-sm text-slate-500">Upload daily / weekly newspaper PDF editions for the public E-News page.</p>
    </div>
</div>

<?php if (!empty($_SESSION['success'])): ?>
    <div class="mb-4 rounded-md bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm"><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
<?php endif; ?>
<?php if (!empty($_SESSION['error'])): ?>
    <div class="mb-4 rounded-md bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-1 bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 p-5 shadow-sm">
        <h3 class="font-bold text-slate-800 dark:text-white mb-4">Upload new edition</h3>
        <form action="handlers/e_news_handler.php" method="post" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <input type="hidden" name="action" value="create">
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Title</label>
                <input type="text" name="title" required placeholder="City Edition - <?= date('d M Y') ?>"
                    class="w-full rounded-md border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Edition date</label>
                <input type="date" name="edition_date" required value="<?= date('Y-m-d') ?>"
                    class="w-full rounded-md border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">PDF file</label>
                <input type="file" name="pdf_file" accept="application/pdf" required class="w-full text-sm">
                <p class="text-[11px] text-slate-400 mt-1">Max 25MB PDF.</p>
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Cover image (optional)</label>
                <input type="file" name="cover_image" accept="image/jpeg,image/png,image/webp" class="w-full text-sm">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Status</label>
                <select name="status" class="w-full rounded-md border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-sm">
                    <option value="published">Published</option>
                    <option value="draft">Draft</option>
                </select>
            </div>
            <button class="w-full bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold py-2.5 rounded-md">Upload edition</button>
        </form>
    </div>

    <div class="lg:col-span-2 bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 overflow-hidden shadow-sm">
        <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700">
            <h3 class="font-bold text-slate-800 dark:text-white">Uploaded editions</h3>
        </div>
        <?php if (empty($editions)): ?>
            <p class="p-8 text-center text-sm text-slate-500">No editions yet.</p>
        <?php else: ?>
            <div class="divide-y divide-slate-100 dark:divide-slate-700">
                <?php foreach ($editions as $ed): ?>
                    <div class="p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div>
                            <p class="font-bold text-slate-800 dark:text-white"><?= htmlspecialchars($ed['title']) ?></p>
                            <p class="text-xs text-slate-500 mt-1"><?= date('d M Y', strtotime($ed['edition_date'])) ?> · <?= htmlspecialchars($ed['status']) ?></p>
                        </div>
                        <div class="flex gap-2">
                            <a href="../<?= htmlspecialchars($ed['pdf_path']) ?>" target="_blank" class="px-3 py-1.5 text-xs font-bold rounded-md bg-slate-100 text-slate-700">View PDF</a>
                            <form method="post" action="handlers/e_news_handler.php" onsubmit="return confirm('Delete this edition?')">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int)$ed['id'] ?>">
                                <button class="px-3 py-1.5 text-xs font-bold rounded-md bg-red-50 text-red-700">Delete</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
