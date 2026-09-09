<?php
require_once 'config/database.php';
require_once 'helpers/query_functions.php';

$stmt = $conn->prepare("SELECT * FROM pages WHERE slug = 'about-us'");
$stmt->execute();
$page_data = $stmt->fetch();

$title = $page_data ? $page_data['title'] : 'About Us';
$content = $page_data ? $page_data['content'] : '<p class="text-center text-slate-500">Content coming soon...</p>';

$page_title = $title;
require_once 'layouts/header.php';
?>

<main class="bg-white min-h-[80vh]">

    <div class="relative py-20 md:py-28 bg-white overflow-hidden">
        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[800px] h-[800px] bg-slate-50 rounded-full blur-3xl opacity-60 -z-10"></div>

        <div class="container mx-auto px-4 text-center max-w-4xl">
            <h2 class="text-indigo-600 font-black text-xs uppercase tracking-[0.3em] mb-6 animate-fade-in">Our Story</h2>
            <h1 class="text-4xl md:text-6xl font-black text-slate-900 tracking-tighter leading-tight mb-8">
                Defining the <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 to-purple-500">Truth</span>, <br>
                One Story at a Time.
            </h1>
            <div class="h-1 w-20 bg-indigo-600 mx-auto rounded-full"></div>
        </div>
    </div>
    <div class="container mx-auto px-4 pb-24 max-w-4xl">
        <div class="prose prose-lg md:prose-xl prose-slate mx-auto
            prose-headings:font-bold prose-headings:tracking-tight prose-headings:text-slate-900
            prose-p:leading-relaxed prose-p:text-slate-600
            prose-a:text-indigo-600 prose-a:no-underline hover:prose-a:underline
            prose-img:rounded-2xl prose-img:shadow-xl prose-img:my-10
            prose-blockquote:border-l-4 prose-blockquote:border-indigo-500 prose-blockquote:bg-slate-50 prose-blockquote:py-2 prose-blockquote:px-6 prose-blockquote:rounded-r-lg prose-blockquote:italic">

            <?= $content ?>

        </div>
    </div>

</main>

<style>
    .animate-fade-in {
        animation: fadeIn 1s ease-out;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>

<?php include 'layouts/footer.php'; ?>