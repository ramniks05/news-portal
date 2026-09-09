<?php
require_once 'config/database.php';
require_once 'helpers/query_functions.php';

$slug = $_GET['slug'] ?? '';
$post = get_post_by_slug($slug);

if (!$post) {
    http_response_code(404);
    $page_title = 'Page Not Found';
    require_once __DIR__ . '/404.php';
    exit();
}

$conn->prepare("UPDATE posts SET views = views + 1 WHERE id = :id")->execute([':id' => $post['id']]);

$tags = get_post_tags($post['id']);
$comments = get_post_comments($post['id']);
$trending = get_trending_news(5);
$related = get_related_posts($post['id'], $post['category_id'] ?? null, 3);

$page_title = $post['title'];
$read_mins = estimate_reading_time($post['content'] ?? '');
require_once 'layouts/header.php';

$share_url = article_url($post['slug']);
?>

<main class="container mx-auto px-4 py-8 md:py-12">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">

        <article class="lg:col-span-2">
            <nav class="flex text-[10px] font-black uppercase tracking-widest text-slate-400 mb-6 overflow-x-auto whitespace-nowrap pb-2">
                <a href="<?= BASE_URL ?>" class="hover:text-indigo-600 transition">Home</a>
                <span class="mx-3 text-slate-200">/</span>
                <a href="<?= category_url($post['category_slug']) ?>" class="hover:text-indigo-600 transition"><?= htmlspecialchars($post['category_name']) ?></a>
                <span class="mx-3 text-slate-200">/</span>
                <span class="text-indigo-600 truncate max-w-[12rem] md:max-w-xs"><?= htmlspecialchars($post['title']) ?></span>
            </nav>

            <h1 class="text-3xl md:text-5xl font-black text-slate-900 leading-tight mb-6">
                <?= htmlspecialchars($post['title']) ?>
            </h1>

            <div class="flex flex-wrap items-center justify-between gap-4 mb-8 border-y border-slate-100 py-6">
                <div class="flex items-center gap-3">
                    <a href="<?= author_url($post['author_id'], $post['author_name']) ?>" class="flex items-center gap-3 group">
                    <?php if ($post['author_avatar']): ?>
                        <img src="<?= BASE_URL . '/' . $post['author_avatar'] ?>" class="h-12 w-12 rounded-full object-cover ring-2 ring-slate-100 group-hover:ring-indigo-200 transition">
                    <?php else: ?>
                        <div class="h-12 w-12 rounded-full bg-indigo-600 text-white flex items-center justify-center font-bold text-xl ring-2 ring-indigo-100">
                            <?= strtoupper(substr($post['author_name'], 0, 1)) ?>
                        </div>
                    <?php endif; ?>
                    <div>
                        <p class="text-sm font-black text-slate-900 leading-none mb-1 group-hover:text-indigo-600 transition"><?= htmlspecialchars($post['author_name']) ?></p>
                        <p class="text-xs font-medium text-slate-500"><?= format_post_date($post, 'F d, Y • h:i A') ?> · <?= $read_mins ?> min read</p>
                    </div>
                    </a>
                </div>
                <div class="flex items-center gap-2" x-data="{ copied: false }">
                    <span class="text-[10px] font-black text-slate-400 mr-2 hidden sm:block uppercase tracking-tighter">Spread the word:</span>
                    <a href="https://wa.me/?text=<?= urlencode($post['title'] . ' ' . $share_url) ?>" target="_blank" class="h-10 w-10 rounded-full bg-[#25D366] text-white flex items-center justify-center hover:scale-110 transition shadow-lg"><i class="fa-brands fa-whatsapp text-lg"></i></a>
                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($share_url) ?>" target="_blank" class="h-10 w-10 rounded-full bg-[#1877F2] text-white flex items-center justify-center hover:scale-110 transition shadow-lg"><i class="fa-brands fa-facebook-f text-lg"></i></a>
                    <a href="https://twitter.com/intent/tweet?text=<?= urlencode($post['title']) ?>&url=<?= urlencode($share_url) ?>" target="_blank" class="h-10 w-10 rounded-full bg-black text-white flex items-center justify-center hover:scale-110 transition shadow-lg"><i class="fa-brands fa-x-twitter text-sm"></i></a>
                    <button type="button" @click="navigator.clipboard.writeText('<?= htmlspecialchars($share_url, ENT_QUOTES) ?>'); copied = true; setTimeout(() => copied = false, 2000)"
                        class="h-10 w-10 rounded-full bg-slate-800 text-white flex items-center justify-center hover:scale-110 transition shadow-lg" :title="copied ? 'Copied!' : 'Copy link'">
                        <i class="fa-solid" :class="copied ? 'fa-check' : 'fa-link'"></i>
                    </button>
                </div>
            </div>
            <?php if ($post['featured_image']): ?>
                <figure class="mb-10 group overflow-hidden rounded-3xl bg-slate-100 max-h-[420px]">
                    <img src="<?= get_post_thumbnail($post['featured_image'] ?? '') ?>" alt="<?= htmlspecialchars($post['title']) ?>" class="w-full max-h-[420px] object-cover shadow-2xl transition duration-500">
                    <?php if (!empty($post['image_credit'])): ?>
                        <figcaption class="text-center text-[10px] uppercase font-bold tracking-widest text-slate-400 mt-4 italic">
                            Image Credit / Source: <?= htmlspecialchars($post['image_credit']) ?>
                        </figcaption>
                    <?php endif; ?>
                </figure>
            <?php endif; ?>
            <div class="article-body prose prose-indigo prose-lg max-w-none text-slate-800 leading-relaxed mb-12
                prose-headings:font-black prose-headings:tracking-tight
                prose-p:mb-6 prose-img:rounded-2xl prose-img:shadow-lg prose-img:max-h-[420px] prose-img:object-cover prose-img:w-full">
                <?= render_post_content($post['content'] ?? '') ?>
            </div>
            <div class="my-12 p-6 bg-slate-50 rounded-3xl border border-slate-100 text-center">
                <span class="text-[10px] text-slate-400 uppercase tracking-widest block mb-4">- Advertisement -</span>
                <?= get_ad('article_bottom') ?>
            </div>
            <?php if ($tags): ?>
                <div class="flex flex-wrap items-center gap-3 mb-12">
                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest mr-2"><i class="fa-solid fa-tags mr-1"></i> Topics:</span>
                    <?php foreach ($tags as $tag): ?>
                        <a href="<?= tag_url($tag['slug']) ?>" class="bg-white border border-slate-200 hover:border-indigo-500 hover:text-indigo-600 px-4 py-1.5 rounded-full text-xs font-bold text-slate-600 transition shadow-sm">
                            #<?= htmlspecialchars($tag['name']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php
            if (empty($_SESSION['csrf_token'])) {
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            }
            $comment_count = count($comments);
            ?>
            <section class="border-t border-slate-100 pt-10 mb-12" id="comments" x-data="commentForm()">
                <div class="flex items-center justify-between mb-8">
                    <h3 class="text-xl font-black text-slate-900 uppercase tracking-tight italic">
                        Comments <span class="text-slate-400 font-bold not-italic text-base">(<?= $comment_count ?>)</span>
                    </h3>
                </div>

                <?php if ($comment_count > 0): ?>
                    <div class="space-y-6 mb-10">
                        <?php foreach ($comments as $comment): ?>
                            <div class="bg-white border border-slate-100 rounded-2xl p-5 shadow-sm">
                                <div class="flex items-start gap-3 mb-3">
                                    <div class="h-10 w-10 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold flex-shrink-0">
                                        <?= strtoupper(substr($comment['name'], 0, 1)) ?>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="font-bold text-slate-900 text-sm"><?= htmlspecialchars($comment['name']) ?></p>
                                        <p class="text-[11px] text-slate-400 font-medium"><?= date('M d, Y · h:i A', strtotime($comment['created_at'])) ?></p>
                                    </div>
                                </div>
                                <p class="text-slate-600 text-sm leading-relaxed whitespace-pre-wrap"><?= htmlspecialchars($comment['content']) ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-sm text-slate-500 mb-8">No comments yet. Be the first to share your thoughts.</p>
                <?php endif; ?>

                <div class="bg-slate-50 border border-slate-100 rounded-3xl p-6 md:p-8">
                    <h4 class="text-sm font-black uppercase tracking-widest text-slate-800 mb-2">Leave a comment</h4>
                    <p class="text-xs text-slate-500 mb-6">Comments are reviewed before they appear publicly.</p>

                    <form @submit.prevent="submitComment" class="space-y-4">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                        <!-- Honeypot -->
                        <div class="hidden" aria-hidden="true">
                            <label>Website</label>
                            <input type="text" x-model="formData.website" tabindex="-1" autocomplete="off">
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Name *</label>
                                <input type="text" x-model="formData.name" required maxlength="100"
                                    class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-white text-sm font-medium focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                            </div>
                            <div>
                                <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Email * <span class="normal-case tracking-normal font-medium text-slate-400">(not published)</span></label>
                                <input type="email" x-model="formData.email" required maxlength="150"
                                    class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-white text-sm font-medium focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                            </div>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Comment *</label>
                            <textarea x-model="formData.content" required rows="4" maxlength="2000"
                                class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-white text-sm font-medium focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none resize-y"
                                placeholder="Share your thoughts..."></textarea>
                        </div>

                        <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                            <button type="submit" :disabled="loading"
                                class="inline-flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-60 text-white text-xs font-black uppercase tracking-widest px-6 py-3 rounded-xl shadow-lg shadow-indigo-100 transition">
                                <span x-show="!loading">Post Comment</span>
                                <span x-show="loading" x-cloak>Submitting...</span>
                            </button>
                            <p x-show="message" x-cloak
                                class="text-sm font-medium"
                                :class="status === 'success' ? 'text-green-600' : 'text-red-600'"
                                x-text="message"></p>
                        </div>
                    </form>
                </div>
            </section>

            <div class="border-t border-slate-100 pt-10">
                <h3 class="text-xl font-black text-slate-900 mb-8 uppercase tracking-tight italic">Recommended For You</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <?php foreach ($related as $rel): ?>
                        <div class="group">
                            <div class="overflow-hidden rounded-2xl mb-4 aspect-video shadow-md">
                                <a href="<?= article_url($rel['slug']) ?>">
                                    <img src="<?= get_post_thumbnail($rel['featured_image'] ?? '') ?>" class="w-full h-full object-cover transition duration-500 group-hover:scale-110">
                                </a>
                            </div>
                            <h4 class="font-bold text-sm leading-snug group-hover:text-indigo-600 transition">
                                <a href="<?= article_url($rel['slug']) ?>"><?= htmlspecialchars($rel['title']) ?></a>
                            </h4>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </article>

        <aside class="lg:col-span-1 space-y-12">
            <div class="sticky top-24">

                <div class="bg-white p-4 rounded-3xl border border-slate-200 shadow-sm text-center mb-12">
                    <span class="text-[10px] text-slate-400 uppercase tracking-widest block mb-3">- Advertisement -</span>
                    <?= get_ad('sidebar_top') ?>
                </div>

                <div class="bg-slate-950 rounded-[2.5rem] overflow-hidden shadow-2xl">
                    <div class="bg-indigo-600 px-7 py-6">
                        <h4 class="font-black text-white text-xs uppercase tracking-[0.2em] flex items-center gap-3">
                            <i class="fa-solid fa-fire-flame-curved animate-pulse"></i> Trending Now
                        </h4>
                    </div>
                    <div class="divide-y divide-slate-800">
                        <?php foreach ($trending as $index => $t_post): ?>
                            <a href="<?= article_url($t_post['slug']) ?>" class="flex gap-5 p-6 hover:bg-slate-900 transition group items-center">
                                <span class="text-3xl font-black text-slate-800 italic group-hover:text-indigo-500 transition-colors">0<?= $index + 1 ?></span>
                                <div class="min-w-0">
                                    <h5 class="text-sm font-bold text-slate-200 leading-tight group-hover:text-white transition line-clamp-2">
                                        <?= htmlspecialchars($t_post['title']) ?>
                                    </h5>
                                    <span class="text-[9px] font-black text-slate-500 mt-2 block uppercase tracking-widest"><?= number_format($t_post['views']) ?> Readers</span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="mt-12 bg-white border border-slate-100 rounded-[2rem] p-8 shadow-soft text-center">
                    <h4 class="text-[10px] font-black text-slate-400 mb-6 uppercase tracking-[0.3em]">Explore More</h4>
                    <div class="flex flex-wrap justify-center gap-2">
                        <?php
                        $quick_cats = $conn->query("SELECT name, slug FROM categories WHERE status=1 LIMIT 8")->fetchAll();
                        foreach ($quick_cats as $qc): ?>
                            <a href="<?= category_url($qc['slug']) ?>" class="text-[10px] font-black px-4 py-2 rounded-xl bg-slate-50 text-slate-500 hover:bg-indigo-600 hover:text-white transition uppercase border border-slate-100 shadow-sm"><?= $qc['name'] ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>

            </div>
        </aside>

    </div>
</main>

<script>
    function commentForm() {
        return {
            loading: false,
            message: '',
            status: '',
            formData: {
                post_id: <?= (int)$post['id'] ?>,
                name: '',
                email: '',
                content: '',
                website: '',
                csrf_token: '<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES) ?>'
            },
            async submitComment() {
                if (!this.formData.name || !this.formData.email || !this.formData.content) return;
                this.loading = true;
                this.message = '';

                const fd = new FormData();
                for (let key in this.formData) {
                    fd.append(key, this.formData[key]);
                }
                fd.append('action', 'submit_comment');

                try {
                    const res = await fetch('<?= BASE_URL ?>/handlers/public_comment_handler.php', {
                        method: 'POST',
                        body: fd,
                        credentials: 'same-origin'
                    });
                    const result = await res.json();
                    this.status = result.status;
                    this.message = result.message;
                    if (result.status === 'success') {
                        this.formData.content = '';
                        this.formData.website = '';
                    }
                } catch (e) {
                    this.status = 'error';
                    this.message = 'System Error. Try later.';
                } finally {
                    this.loading = false;
                }
            }
        }
    }
</script>

<?php require_once 'layouts/footer.php'; ?>