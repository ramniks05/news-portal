<?php
require_once 'includes/header.php';
require_once '../config/database.php';

$slug = $_GET['slug'] ?? 'about-us';

$stmt = $conn->prepare("SELECT * FROM pages WHERE slug = :slug");
$stmt->execute([':slug' => $slug]);
$page = $stmt->fetch();

if (!$page) {
    $title = ucwords(str_replace('-', ' ', $slug));
    $stmt = $conn->prepare("INSERT INTO pages (title, slug, content) VALUES (:title, :slug, '')");
    $stmt->execute([':title' => $title, ':slug' => $slug]);
    echo "<script>location.reload();</script>";
    exit();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<link rel="stylesheet" href="https://unpkg.com/jodit@4.0.0-beta.24/es2021/jodit.min.css" />
<script src="https://unpkg.com/jodit@4.0.0-beta.24/es2021/jodit.min.js"></script>

<div class="mb-6 flex justify-between items-center">
    <div>
        <h2 class="text-2xl font-bold text-slate-800 dark:text-white">Edit Page: <?= htmlspecialchars($page['title']) ?></h2>
        <p class="text-sm text-slate-500 dark:text-slate-400">Manage static content for your website.</p>
    </div>
</div>

<form action="handlers/page_handler.php" method="POST" class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-6">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
    <input type="hidden" name="id" value="<?= $page['id'] ?>">
    <input type="hidden" name="slug" value="<?= $page['slug'] ?>">

    <div class="mb-6">
        <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Page Title</label>
        <input type="text" name="title" value="<?= htmlspecialchars($page['title']) ?>" class="w-full px-4 py-2 border border-slate-300 rounded-md focus:ring-2 focus:ring-indigo-500 dark:bg-slate-900 dark:border-slate-600 dark:text-white">
    </div>

    <div class="mb-6">
        <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Page Content</label>
        <textarea id="pageEditor" name="content"><?= htmlspecialchars($page['content']) ?></textarea>
    </div>

    <div class="flex justify-end">
        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-8 rounded-md shadow transition flex items-center">
            <i class="fa-solid fa-floppy-disk mr-2"></i> Update Page
        </button>
    </div>
</form>

<script>
    const editor = Jodit.make('#pageEditor', {
        height: 500,
        uploader: {
            insertImageAsBase64URI: true
        },
        theme: localStorage.getItem('theme') === 'dark' ? 'dark' : 'default'
    });
</script>

<?php include 'includes/footer.php'; ?>