<?php
require_once 'config/database.php';
require_once 'helpers/query_functions.php';

$stmt = $conn->prepare("SELECT * FROM pages WHERE slug = 'terms-conditions'");
$stmt->execute();
$page = $stmt->fetch();

$title = $page ? $page['title'] : 'Terms & Conditions';
$content = $page ? $page['content'] : '<p class="text-center text-slate-500">Terms content coming soon...</p>';
$last_updated = $page ? date('F d, Y', strtotime($page['updated_at'])) : date('F d, Y');

$page_title = $title;
require_once 'layouts/header.php';
?>

<main class="bg-white min-h-[80vh] py-12 md:py-20">

    <div class="container mx-auto px-4 max-w-4xl">
        <div class="text-center mb-12 border-b border-slate-100 pb-10">
            <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-indigo-50 text-indigo-600 mb-4">
                <i class="fa-solid fa-scale-balanced text-xl"></i>
            </div>
            <h1 class="text-3xl md:text-5xl font-black text-slate-900 mb-4 tracking-tight"><?= htmlspecialchars($title) ?></h1>
            <p class="text-slate-500 font-medium text-sm">
                Effective Date: <span class="text-slate-800"><?= $last_updated ?></span>
            </p>
        </div>
        <div class="prose prose-slate prose-lg mx-auto
            prose-headings:font-bold prose-headings:text-slate-900 prose-headings:mt-8 prose-headings:mb-4
            prose-p:text-slate-600 prose-p:leading-7
            prose-a:text-indigo-600 prose-a:font-semibold hover:prose-a:underline
            prose-ol:list-decimal prose-ol:pl-6 prose-li:text-slate-600 prose-li:mb-2
            prose-strong:text-slate-800">

            <?= $content ?>

        </div>

    </div>
</main>

<?php include 'layouts/footer.php'; ?>