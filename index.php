<?php
// Before install completes, send visitors to the setup wizard
if (!file_exists(__DIR__ . '/config/install.lock') || !file_exists(__DIR__ . '/config/database.php')) {
    header('Location: install/');
    exit();
}

$page_title = "Home";
require_once 'layouts/header.php';

$total_published = count_published_posts();

$hero_posts = get_section_posts('hero_featured');
$slider_posts = get_section_posts('home_slider');
$curated_trending = get_section_posts('trending_news');

// Fallback hero from latest posts when curator is empty
if (empty($hero_posts) && $total_published > 0) {
    $hero_posts = get_latest_posts(3, 0);
}

$main_feat = $hero_posts[0] ?? null;
$sub_feat = array_slice($hero_posts, 1, 2);

// Fallback slider — allow overlap when there are few posts
if (empty($slider_posts) && $total_published > 0) {
    $hero_ids = array_column($hero_posts, 'id');
    $slider_posts = get_latest_posts(6, 0, $hero_ids);
    if (empty($slider_posts)) {
        $slider_posts = get_latest_posts(6, 0);
    }
}

if (empty($curated_trending)) {
    $trending_stmt = $conn->query("SELECT title, slug, views FROM posts WHERE status='published' ORDER BY views DESC LIMIT 5");
    $curated_trending = $trending_stmt->fetchAll(PDO::FETCH_ASSOC);
}

$exclude_ids = array_unique(array_filter(array_merge(
    array_column($hero_posts, 'id'),
    array_column($slider_posts, 'id')
)));

$latest = get_latest_posts(10, 0, $exclude_ids);

// Keep Recent Stories filled even when posts are already used above
if (empty($latest) && $total_published > 0) {
    $latest = get_latest_posts(10, 0);
}
?>

<main class="container mx-auto px-4 py-6 md:py-10">
    <?php if ($total_published === 0): ?>
        <section class="mb-16 rounded-3xl border border-dashed border-slate-200 bg-white px-6 py-16 text-center shadow-sm">
            <div class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-full bg-indigo-50 text-indigo-600">
                <i class="fa-regular fa-newspaper text-2xl"></i>
            </div>
            <h2 class="text-2xl font-black text-slate-900 mb-3">No stories published yet</h2>
            <p class="text-slate-500 text-sm max-w-lg mx-auto mb-8 leading-relaxed">
                Your news portal is ready. Publish the first article from the admin panel, then optionally feature it on the homepage.
            </p>
            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                <a href="<?= BASE_URL ?>/admin/add-post.php" class="inline-flex items-center justify-center gap-2 rounded-full bg-indigo-600 px-6 py-3 text-xs font-black uppercase tracking-widest text-white hover:bg-indigo-700 transition">
                    <i class="fa-solid fa-pen-nib"></i> Write first post
                </a>
                <a href="<?= BASE_URL ?>/admin/featured-management.php" class="inline-flex items-center justify-center gap-2 rounded-full border border-slate-200 bg-white px-6 py-3 text-xs font-black uppercase tracking-widest text-slate-700 hover:border-indigo-300 hover:text-indigo-600 transition">
                    Homepage curator
                </a>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($main_feat): ?>
        <section class="grid grid-cols-1 <?= !empty($sub_feat) ? 'lg:grid-cols-3' : '' ?> gap-6 mb-12">
            <a href="<?= article_url($main_feat['slug']) ?>" class="<?= !empty($sub_feat) ? 'lg:col-span-2' : '' ?> group relative rounded-3xl overflow-hidden shadow-2xl h-[280px] md:h-[420px] block transition-all duration-500 hover:shadow-indigo-200/50" style="max-height:420px">
                <img src="<?= get_post_thumbnail($main_feat['featured_image'] ?? '') ?>" alt="<?= htmlspecialchars($main_feat['title']) ?>"
                    class="absolute inset-0 w-full h-full object-cover transition duration-700 group-hover:scale-105" style="object-fit:cover;width:100%;height:100%">
                <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/30 to-transparent"></div>

                <div class="absolute bottom-0 left-0 p-6 md:p-10 w-full">
                    <span class="inline-block bg-red-600 text-white text-[10px] font-bold px-3 py-1 rounded-full mb-4 uppercase tracking-widest shadow-lg">
                        <?= htmlspecialchars($main_feat['category_name'] ?? 'Breaking') ?>
                    </span>
                    <h2 class="text-2xl md:text-4xl font-extrabold text-white leading-tight mb-4 drop-shadow-lg line-clamp-2">
                        <?= htmlspecialchars($main_feat['title']) ?>
                    </h2>
                    <div class="text-slate-300 text-xs md:text-sm flex items-center gap-4 font-medium">
                        <span class="flex items-center gap-1.5"><i class="fa-solid fa-user-pen text-indigo-400"></i> <?= htmlspecialchars($main_feat['author_name'] ?? 'News Desk') ?></span>
                        <span class="opacity-30">|</span>
                        <span class="flex items-center gap-1.5"><i class="fa-regular fa-calendar"></i> <?= format_post_date($main_feat) ?></span>
                    </div>
                </div>
            </a>

            <?php if (!empty($sub_feat)): ?>
            <div class="flex flex-col gap-6">
                <?php foreach ($sub_feat as $post): ?>
                    <a href="<?= article_url($post['slug']) ?>" class="relative flex-1 rounded-3xl overflow-hidden shadow-lg group min-h-[180px] md:min-h-[200px] block transition-all duration-300 hover:shadow-indigo-100" style="min-height:180px;max-height:210px">
                        <img src="<?= get_post_thumbnail($post['featured_image'] ?? '') ?>" alt="<?= htmlspecialchars($post['title']) ?>"
                            class="absolute inset-0 w-full h-full object-cover transition duration-500 group-hover:scale-110" style="object-fit:cover;width:100%;height:100%">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/20 to-transparent"></div>
                        <div class="absolute bottom-0 p-5">
                            <span class="text-[9px] font-bold text-white bg-indigo-600 px-2 py-0.5 rounded-full mb-2 inline-block uppercase tracking-wider">
                                <?= htmlspecialchars($post['category_name'] ?? '') ?>
                            </span>
                            <h3 class="text-lg font-bold text-white leading-snug group-hover:text-indigo-200 transition line-clamp-2">
                                <?= htmlspecialchars($post['title']) ?>
                            </h3>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <?php if ($slider_posts): ?>
        <section class="mb-16" x-data="{ 
        next() { this.$refs.slider.scrollLeft += this.$refs.slider.clientWidth; if(this.$refs.slider.scrollLeft + this.$refs.slider.clientWidth >= this.$refs.slider.scrollWidth) this.$refs.slider.scrollLeft = 0; },
        prev() { this.$refs.slider.scrollLeft -= this.$refs.slider.clientWidth; if(this.$refs.slider.scrollLeft <= 0) this.$refs.slider.scrollLeft = this.$refs.slider.scrollWidth; },
        init() { setInterval(() => { this.next() }, 5000); }
    }">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-black text-slate-900 uppercase tracking-tighter italic border-l-4 border-red-600 pl-3">
                    Top Picks for you
                </h3>
                <div class="flex gap-2">
                    <button @click="prev" class="h-9 w-9 rounded-full bg-white border border-slate-200 flex items-center justify-center hover:bg-indigo-600 hover:text-white transition shadow-sm"><i class="fa-solid fa-chevron-left text-xs"></i></button>
                    <button @click="next" class="h-9 w-9 rounded-full bg-white border border-slate-200 flex items-center justify-center hover:bg-indigo-600 hover:text-white transition shadow-sm"><i class="fa-solid fa-chevron-right text-xs"></i></button>
                </div>
            </div>

            <div class="relative overflow-hidden">
                <div x-ref="slider" class="flex gap-5 overflow-x-auto no-scrollbar snap-x snap-mandatory scroll-smooth">
                    <?php foreach ($slider_posts as $post): ?>
                        <div class="snap-start flex-shrink-0 w-[260px] md:w-[300px] bg-white rounded-2xl border border-slate-100 overflow-hidden shadow-sm hover:shadow-md transition-all group">
                            <div class="h-44 overflow-hidden relative">
                                <img src="<?= get_post_thumbnail($post['featured_image'] ?? '') ?>" alt="<?= htmlspecialchars($post['title']) ?>" class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                                <span class="absolute top-3 left-3 bg-black/50 backdrop-blur-md text-white text-[8px] font-bold px-2 py-0.5 rounded uppercase tracking-widest"><?= htmlspecialchars($post['category_name'] ?? '') ?></span>
                            </div>
                            <div class="p-4">
                                <h4 class="font-bold text-slate-800 text-sm leading-snug line-clamp-2 group-hover:text-indigo-600">
                                    <a href="<?= article_url($post['slug']) ?>"><?= htmlspecialchars($post['title']) ?></a>
                                </h4>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($total_published > 0): ?>
    <div class="mb-16 text-center overflow-hidden">
        <span class="text-[10px] text-slate-400 uppercase tracking-widest block mb-2">- Advertisement -</span>
        <?= get_ad('header_top') ?>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">

        <div class="lg:col-span-2" x-data="newsLoader()">
            <div class="flex items-center justify-between mb-8 border-b-2 border-slate-100 pb-2">
                <h3 class="text-2xl font-black text-slate-900 flex items-center gap-3">
                    <span class="w-2 h-8 bg-indigo-600 rounded-full"></span>
                    Recent Stories
                </h3>
            </div>

            <div id="posts-container" class="space-y-10">
                <?php if (empty($latest)): ?>
                    <p class="text-slate-500 text-sm py-8">No recent stories to show yet.</p>
                <?php endif; ?>
                <?php foreach ($latest as $post): ?>
                    <article class="flex flex-col md:flex-row gap-6 group animate-fade-in">
                        <div class="md:w-1/3 flex-shrink-0 relative overflow-hidden rounded-2xl h-48 md:h-36 lg:h-44 shadow-sm bg-slate-100">
                            <a href="<?= article_url($post['slug']) ?>">
                                <img src="<?= get_post_thumbnail($post['featured_image'] ?? '') ?>" alt="<?= htmlspecialchars($post['title']) ?>"
                                    loading="lazy" class="w-full h-full object-cover transition duration-500 group-hover:scale-110">
                            </a>
                        </div>
                        <div class="flex-1 flex flex-col justify-center">
                            <a href="<?= category_url($post['category_slug'] ?? '') ?>"
                                class="text-[10px] font-black text-indigo-600 uppercase tracking-wider mb-2 block">
                                <?= htmlspecialchars($post['category_name'] ?? 'Uncategorized') ?>
                            </a>
                            <h2 class="text-xl font-bold text-slate-900 mb-2 leading-tight group-hover:text-indigo-700 transition">
                                <a href="<?= article_url($post['slug']) ?>">
                                    <?= htmlspecialchars($post['title']) ?>
                                </a>
                            </h2>
                            <p class="text-slate-500 text-sm line-clamp-2 mb-3 leading-relaxed">
                                <?= htmlspecialchars($post['summary'] ?? '') ?>
                            </p>
                            <div class="flex items-center gap-3 text-[11px] text-slate-400 font-bold uppercase">
                                <span><i class="fa-regular fa-clock mr-1 text-indigo-500"></i> <?= format_post_date($post) ?></span>
                                <span>•</span>
                                <span><?= htmlspecialchars($post['author_name'] ?? 'Admin') ?></span>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
            <div class="mt-16 text-center" x-show="hasMore">
                <button @click="loadMore()"
                    :disabled="isLoading"
                    class="group inline-flex items-center gap-3 px-10 py-4 bg-slate-900 text-white rounded-full font-black text-sm uppercase tracking-widest hover:bg-indigo-600 transition-all shadow-xl disabled:opacity-50">
                    <span x-show="!isLoading">Load More News</span>
                    <span x-show="isLoading" class="flex items-center gap-2">
                        <i class="fa-solid fa-circle-notch fa-spin text-lg"></i> Fetching...
                    </span>
                    <i class="fa-solid fa-arrow-down animate-bounce group-hover:animate-none" x-show="!isLoading"></i>
                </button>
            </div>

            <div x-show="!hasMore" class="mt-12 text-center py-10 border-t border-dashed border-slate-200">
                <p class="text-slate-400 font-bold italic tracking-tighter">You've reached the end of today's updates.</p>
            </div>
        </div>

        <aside class="lg:col-span-1 space-y-12">
            <div class="bg-white p-4 rounded-3xl border border-slate-200 shadow-sm text-center">
                <span class="text-[10px] text-slate-400 uppercase tracking-widest block mb-2">- Advertisement -</span>
                <?= get_ad('sidebar_top') ?>
            </div>
            <div class="bg-white border border-slate-100 rounded-3xl p-7 shadow-soft">
                <h4 class="text-sm font-black text-slate-900 mb-6 uppercase tracking-widest border-b border-slate-50 pb-3">Follow Us</h4>
                <div class="grid grid-cols-2 gap-4">
                    <a href="<?= get_config('social_facebook') ?>" target="_blank" class="flex items-center justify-center gap-2 bg-[#1877F2] text-white py-3 rounded-2xl text-xs font-bold hover:shadow-lg transition active:scale-95"><i class="fa-brands fa-facebook-f"></i> FB</a>
                    <a href="<?= get_config('social_twitter') ?>" target="_blank" class="flex items-center justify-center gap-2 bg-black text-white py-3 rounded-2xl text-xs font-bold hover:shadow-lg transition active:scale-95"><i class="fa-brands fa-x-twitter"></i> X</a>
                    <a href="<?= get_config('social_instagram') ?>" target="_blank" class="flex items-center justify-center gap-2 bg-gradient-to-tr from-[#f9ce34] via-[#ee2a7b] to-[#6228d7] text-white py-3 rounded-2xl text-xs font-bold hover:shadow-lg transition active:scale-95"><i class="fa-brands fa-instagram"></i> INSTA</a>
                    <a href="<?= get_config('social_youtube') ?>" target="_blank" class="flex items-center justify-center gap-2 bg-[#FF0000] text-white py-3 rounded-2xl text-xs font-bold hover:shadow-lg transition active:scale-95"><i class="fa-brands fa-youtube"></i> LIVE</a>
                </div>
            </div>
            <div class="bg-slate-950 rounded-3xl overflow-hidden shadow-2xl">
                <div class="bg-indigo-600 px-6 py-5">
                    <h4 class="font-black text-white text-sm uppercase tracking-widest flex items-center gap-2">
                        <i class="fa-solid fa-fire-flame-curved animate-pulse"></i> Trending Now
                    </h4>
                </div>
                <div class="divide-y divide-slate-800">
                    <?php if (empty($curated_trending)): ?>
                        <p class="p-5 text-sm text-slate-500">No trending stories yet.</p>
                    <?php endif; ?>
                    <?php foreach ($curated_trending as $index => $t_post): ?>
                        <div class="flex gap-4 p-5 hover:bg-slate-900 transition group items-start">
                            <span class="text-3xl font-black text-slate-700 italic group-hover:text-indigo-500 transition-colors">0<?= $index + 1 ?></span>
                            <div>
                                <h5 class="text-sm font-bold text-slate-200 leading-tight group-hover:text-white transition line-clamp-2">
                                    <a href="<?= article_url($t_post['slug']) ?>"><?= htmlspecialchars($t_post['title']) ?></a>
                                </h5>
                                <?php if (isset($t_post['views'])): ?>
                                    <span class="text-[10px] text-slate-500 mt-2 block font-bold uppercase"><i class="fa-regular fa-eye mr-1"></i> <?= number_format($t_post['views']) ?> Views</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="sticky top-24">
                <div class="bg-white p-4 rounded-3xl border border-slate-200 shadow-sm text-center">
                    <span class="text-[10px] text-slate-400 uppercase tracking-widest block mb-2">- Advertisement -</span>
                    <?= get_ad('sidebar_bottom') ?>
                </div>
            </div>

        </aside>
    </div>
    <?php endif; ?>

</main>
<script>
    function newsLoader() {
        return {
            offset: <?= max(count($latest), 10) ?>,
            isLoading: false,
            hasMore: <?= !empty($latest) && $total_published > count($latest) ? 'true' : 'false' ?>,
            async loadMore() {
                if (this.isLoading || !this.hasMore) return;
                this.isLoading = true;
                try {
                    const response = await fetch(`<?= BASE_URL ?>/handlers/ajax_load_posts.php?offset=${this.offset}`);
                    const html = await response.text();
                    if (html.trim() === "NO_MORE") {
                        this.hasMore = false;
                    } else {
                        document.getElementById('posts-container').insertAdjacentHTML('beforeend', html);
                        this.offset += 6;
                    }
                } catch (err) {
                    console.error("Failed to load posts:", err);
                } finally {
                    this.isLoading = false;
                }
            }
        }
    }
</script>

<style>
    .no-scrollbar::-webkit-scrollbar {
        display: none;
    }

    .no-scrollbar {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }

    .shadow-soft {
        box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.04);
    }
</style>

<?php require_once 'layouts/footer.php'; ?>
