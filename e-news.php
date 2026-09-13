<?php
require_once 'config/database.php';
require_once 'helpers/query_functions.php';
require_once 'helpers/weather_functions.php';

$page_title = 'E-News / E-Paper';
$editions = get_latest_e_news(50);
require_once 'layouts/header.php';
?>

<main class="container mx-auto px-4 py-10 md:py-14">
    <nav class="flex text-[10px] font-black uppercase tracking-widest text-slate-400 mb-6">
        <a href="<?= BASE_URL ?>" class="hover:text-indigo-600">Home</a>
        <span class="mx-3 text-slate-200">/</span>
        <span class="text-indigo-600">E-News PDF</span>
    </nav>

    <div class="mb-10 max-w-3xl">
        <h1 class="text-3xl md:text-5xl font-black text-slate-900 mb-3">E-News / E-Paper</h1>
        <p class="text-slate-500 text-sm md:text-base leading-relaxed">
            Download the latest newspaper editions in PDF. Perfect for local channels who also publish a print or digital paper.
        </p>
    </div>

    <?php if (empty($editions)): ?>
        <div class="rounded-3xl border border-dashed border-slate-200 bg-white p-10 text-center">
            <i class="fa-regular fa-file-pdf text-4xl text-slate-300 mb-4"></i>
            <p class="text-slate-500 font-medium">No e-news editions uploaded yet.</p>
            <p class="text-xs text-slate-400 mt-2">Admin can upload PDFs from Admin → E-News PDF.</p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($editions as $ed): ?>
                <article class="bg-white border border-slate-100 rounded-3xl overflow-hidden shadow-sm hover:shadow-md transition">
                    <div class="h-44 bg-slate-100 flex items-center justify-center relative overflow-hidden">
                        <?php if (!empty($ed['cover_image'])): ?>
                            <img src="<?= BASE_URL . '/' . ltrim($ed['cover_image'], '/') ?>" alt="" class="w-full h-full object-cover">
                        <?php else: ?>
                            <i class="fa-solid fa-file-pdf text-5xl text-rose-500"></i>
                        <?php endif; ?>
                    </div>
                    <div class="p-5">
                        <p class="text-[10px] font-black uppercase tracking-widest text-indigo-600 mb-2">
                            <?= date('d M Y', strtotime($ed['edition_date'])) ?>
                        </p>
                        <h2 class="font-bold text-slate-900 mb-4 line-clamp-2"><?= htmlspecialchars($ed['title']) ?></h2>
                        <a href="<?= BASE_URL . '/' . ltrim($ed['pdf_path'], '/') ?>" target="_blank" rel="noopener"
                            class="inline-flex items-center gap-2 text-xs font-black uppercase tracking-widest text-white bg-rose-600 hover:bg-rose-500 px-4 py-2.5 rounded-xl">
                            <i class="fa-solid fa-download"></i> Download PDF
                        </a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<?php require_once 'layouts/footer.php'; ?>
