<?php
require_once 'includes/header.php';
require_once '../config/database.php';
require_once '../helpers/common_functions.php';
require_once '../helpers/news_api_functions.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Ensure demo categories exist so import never fails with "category required"
$ensured = news_api_ensure_categories($conn);

$cat_stmt = $conn->query("SELECT id, name, parent_id FROM categories WHERE status = 1 ORDER BY name ASC");
$raw_cats = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);
$categories = [];
$parents = [];
$children_map = [];
foreach ($raw_cats as $row) {
    $pid = (int)($row['parent_id'] ?? 0);
    if ($pid === 0) {
        $parents[] = $row;
    } else {
        $children_map[$pid][] = $row;
    }
}
foreach ($parents as $parent) {
    $categories[] = ['id' => $parent['id'], 'label' => $parent['name']];
    if (!empty($children_map[$parent['id']])) {
        foreach ($children_map[$parent['id']] as $child) {
            $categories[] = ['id' => $child['id'], 'label' => '— ' . $child['name']];
        }
    }
}
// Orphans (active children whose parent inactive) still list flat
foreach ($raw_cats as $row) {
    $already = false;
    foreach ($categories as $c) {
        if ((int)$c['id'] === (int)$row['id']) {
            $already = true;
            break;
        }
    }
    if (!$already) {
        $categories[] = ['id' => $row['id'], 'label' => $row['name']];
    }
}

$provider = get_setting('news_api_provider', 'rss');
$newsapi_key = get_setting('newsapi_key', '');
$gnews_key = get_setting('gnews_key', '');
$preview = $_SESSION['news_import_preview'] ?? [];
$meta = $_SESSION['news_import_meta'] ?? [];
$default_cat = (int)($meta['category_id'] ?? 0);
if ($default_cat < 1 && !empty($categories)) {
    $default_cat = (int)$categories[0]['id'];
}
if ($default_cat < 1 && !empty($ensured['world'])) {
    $default_cat = (int)$ensured['world'];
}
$default_status = $meta['status'] ?? 'published';
$default_query = $meta['query'] ?? 'bbc-asia';
$rss_presets = [
    'india-bbc' => 'India — BBC',
    'india-toi' => 'India — Times of India',
    'india-toi-top' => 'India — TOI Top Stories',
    'india-hindu' => 'India — The Hindu National',
    'india-express' => 'India — Indian Express',
    'india-ndtv' => 'India — NDTV',
    'india-ht' => 'India — Hindustan Times',
    'bbc-asia' => 'BBC Asia',
    'bbc-world' => 'BBC World',
    'bbc-tech' => 'BBC Technology',
    'bbc-business' => 'BBC Business',
    'bbc-politics' => 'BBC Politics',
    'bbc-sport' => 'BBC Sport',
    'nyt-world' => 'NYTimes World',
];
$default_query = $meta['query'] ?? 'india-bbc';
?>

<div class="mb-8">
    <h2 class="text-2xl font-bold text-slate-800 dark:text-white">Import Demo News (API / RSS)</h2>
    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
        Pull live sample headlines into your <strong>existing</strong> posts structure.
        Use <strong>India-only refresh</strong> for Indian newspapers/feeds (recommended for your portal).
    </p>
</div>

<?php if (!empty($_SESSION['success'])): ?>
    <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-800 px-4 py-3 text-sm font-medium">
        <?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
    </div>
<?php endif; ?>
<?php if (!empty($_SESSION['error'])): ?>
    <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 text-rose-800 px-4 py-3 text-sm font-medium">
        <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
    </div>
<?php endif; ?>

<div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-5">
    <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
        <div>
            <h3 class="text-sm font-black uppercase tracking-wider text-amber-900 mb-1">Full site refresh</h3>
            <p class="text-xs text-amber-800/90 leading-relaxed max-w-2xl">
                Deletes <strong>all current posts</strong>, then imports live feeds into categories
                (India News, Politics, Business, Sports, Technology, World), rebuilds
                <strong>breaking / hero / slider / trending</strong>, and fills demo ads.
            </p>
        </div>
        <form action="handlers/news_import_handler.php" method="POST" class="flex flex-col sm:flex-row gap-2 items-stretch sm:items-end flex-shrink-0"
            onsubmit="return confirm('This will DELETE all posts and replace them with live news. Continue?');">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <input type="hidden" name="action" value="full_refresh">
            <div>
                <label class="block text-[10px] font-black uppercase tracking-widest text-amber-700/80 mb-1">Type REFRESH</label>
                <input type="text" name="confirm_text" required placeholder="REFRESH"
                    class="rounded-md border border-amber-300 px-3 py-2 text-sm w-36 bg-white">
            </div>
            <div>
                <label class="block text-[10px] font-black uppercase tracking-widest text-amber-700/80 mb-1">Scope</label>
                <select name="scope" class="rounded-md border border-amber-300 px-3 py-2 text-sm bg-white">
                    <option value="india" selected>India only</option>
                    <option value="global">Global (BBC)</option>
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-black uppercase tracking-widest text-amber-700/80 mb-1">Per category</label>
                <select name="per_category" class="rounded-md border border-amber-300 px-3 py-2 text-sm bg-white">
                    <option value="5">5</option>
                    <option value="6" selected>6</option>
                    <option value="8">8</option>
                </select>
            </div>
            <button type="submit" class="bg-amber-600 hover:bg-amber-700 text-white text-xs font-black uppercase tracking-widest px-5 py-2.5 rounded-md transition whitespace-nowrap">
                Refresh entire site
            </button>
        </form>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
    <div class="xl:col-span-1 space-y-6">
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-5 shadow-sm">
            <h3 class="text-sm font-bold text-slate-800 dark:text-white mb-3 uppercase tracking-wider">1. API keys</h3>
            <p class="text-xs text-slate-500 mb-4">
                Free keys:
                <a class="text-indigo-600 font-semibold hover:underline" href="https://newsapi.org/register" target="_blank" rel="noopener">NewsAPI.org</a>
                or
                <a class="text-indigo-600 font-semibold hover:underline" href="https://gnews.io" target="_blank" rel="noopener">GNews.io</a>.
                NewsAPI free plan works best on localhost.
            </p>
            <form action="handlers/news_import_handler.php" method="POST" class="space-y-3">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <input type="hidden" name="action" value="save_keys">
                <div>
                    <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Default provider</label>
                    <select name="news_api_provider" class="w-full rounded-md border border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white px-3 py-2 text-sm">
                        <option value="rss" <?= $provider === 'rss' ? 'selected' : '' ?>>RSS feeds (no key)</option>
                        <option value="newsapi" <?= $provider === 'newsapi' ? 'selected' : '' ?>>NewsAPI.org</option>
                        <option value="gnews" <?= $provider === 'gnews' ? 'selected' : '' ?>>GNews.io</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">NewsAPI key</label>
                    <input type="password" name="newsapi_key" value="<?= htmlspecialchars($newsapi_key) ?>" autocomplete="off"
                        class="w-full rounded-md border border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white px-3 py-2 text-sm" placeholder="••••••••">
                </div>
                <div>
                    <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">GNews key</label>
                    <input type="password" name="gnews_key" value="<?= htmlspecialchars($gnews_key) ?>" autocomplete="off"
                        class="w-full rounded-md border border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white px-3 py-2 text-sm" placeholder="••••••••">
                </div>
                <button type="submit" class="w-full bg-slate-900 hover:bg-indigo-600 text-white text-xs font-bold uppercase tracking-widest py-2.5 rounded-md transition">
                    Save keys
                </button>
            </form>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-5 shadow-sm">
            <h3 class="text-sm font-bold text-slate-800 dark:text-white mb-3 uppercase tracking-wider">2. Fetch articles</h3>
            <form action="handlers/news_import_handler.php" method="POST" class="space-y-3">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <input type="hidden" name="action" value="fetch">
                <div>
                    <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Provider</label>
                    <select name="provider" id="importProvider" class="w-full rounded-md border border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white px-3 py-2 text-sm">
                        <option value="rss" <?= ($meta['provider'] ?? $provider) === 'rss' ? 'selected' : '' ?>>RSS (recommended, no key)</option>
                        <option value="newsapi" <?= ($meta['provider'] ?? $provider) === 'newsapi' ? 'selected' : '' ?>>NewsAPI.org</option>
                        <option value="gnews" <?= ($meta['provider'] ?? $provider) === 'gnews' ? 'selected' : '' ?>>GNews.io</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">API key (NewsAPI / GNews only)</label>
                    <input type="text" name="api_key" value="" placeholder="Leave blank for RSS or saved key"
                        class="w-full rounded-md border border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">RSS feed / keyword</label>
                    <select name="query" class="w-full rounded-md border border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white px-3 py-2 text-sm mb-2">
                        <?php foreach ($rss_presets as $key => $label): ?>
                            <option value="<?= htmlspecialchars($key) ?>" <?= $default_query === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="text-[11px] text-slate-400 mb-1">For <strong>NewsAPI / GNews</strong> type a keyword here (example: <code>India</code>). RSS feed dropdown is ignored when keyword is filled.</p>
                    <input type="text" name="query_text" value="India" placeholder="Keyword e.g. India, cricket, technology"
                        class="w-full rounded-md border border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white px-3 py-2 text-sm">
                    <p class="text-[11px] text-amber-700 mt-2">Live Hostinger: NewsAPI free keys often fail. Use <strong>RSS</strong> or <strong>Full site refresh</strong>.</p>
                </div>
                <div>
                    <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">How many</label>
                    <select name="limit" class="w-full rounded-md border border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white px-3 py-2 text-sm">
                        <?php foreach ([5, 8, 10, 15, 20] as $n): ?>
                            <option value="<?= $n ?>" <?= $n === 8 ? 'selected' : '' ?>><?= $n ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Import into category *</label>
                    <select name="category_id" required class="w-full rounded-md border border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white px-3 py-2 text-sm">
                        <?php if (empty($categories)): ?>
                            <option value="">No category — open this page again after refresh</option>
                        <?php else: ?>
                            <?php foreach ($categories as $c): ?>
                                <option value="<?= (int)$c['id'] ?>" <?= $default_cat === (int)$c['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c['label']) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <p class="text-[11px] text-slate-400 mt-1">Pick any category (World, Politics, etc.). One is auto-selected.</p>
                </div>
                <div>
                    <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Status</label>
                    <select name="status" class="w-full rounded-md border border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white px-3 py-2 text-sm">
                        <option value="published" <?= $default_status === 'published' ? 'selected' : '' ?>>Published (show on site)</option>
                        <option value="draft" <?= $default_status === 'draft' ? 'selected' : '' ?>>Draft</option>
                    </select>
                </div>
                <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold uppercase tracking-widest py-2.5 rounded-md transition">
                    Fetch preview
                </button>
            </form>
            <?php if (empty($categories)): ?>
                <p class="text-xs text-rose-600 mt-3">Create at least one category first under Categories.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="xl:col-span-2">
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-5 shadow-sm min-h-[320px]">
            <div class="flex items-center justify-between gap-3 mb-4 border-b border-slate-100 dark:border-slate-700 pb-3">
                <h3 class="text-sm font-bold text-slate-800 dark:text-white uppercase tracking-wider">3. Preview & import</h3>
                <span class="text-xs text-slate-400"><?= count($preview) ?> ready</span>
            </div>

            <?php if (empty($preview)): ?>
                <div class="py-16 text-center text-slate-400 text-sm">
                    <i class="fa-solid fa-cloud-arrow-down text-3xl mb-3 opacity-40"></i>
                    <p>Fetch articles to preview titles, sources, and images here.</p>
                    <p class="text-xs mt-2">Imports use your current admin as author and your selected category.</p>
                </div>
            <?php else: ?>
                <form action="handlers/news_import_handler.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="action" value="import">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-4">
                        <div>
                            <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Category</label>
                            <select name="category_id" required class="w-full rounded-md border border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white px-3 py-2 text-sm">
                                <?php foreach ($categories as $c): ?>
                                    <option value="<?= (int)$c['id'] ?>" <?= $default_cat === (int)$c['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($c['label']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Status</label>
                            <select name="status" class="w-full rounded-md border border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white px-3 py-2 text-sm">
                                <option value="draft" <?= $default_status === 'draft' ? 'selected' : '' ?>>Draft</option>
                                <option value="published" <?= $default_status === 'published' ? 'selected' : '' ?>>Published</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex items-center justify-between mb-3">
                        <label class="text-xs font-semibold text-slate-600 dark:text-slate-300 inline-flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" checked onchange="document.querySelectorAll('.import-check').forEach(c => c.checked = this.checked)" class="rounded border-slate-300">
                            Select all
                        </label>
                        <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold uppercase tracking-widest px-4 py-2 rounded-md transition">
                            Import selected
                        </button>
                    </div>

                    <div class="space-y-3 max-h-[640px] overflow-y-auto pr-1">
                        <?php foreach ($preview as $a): ?>
                            <label class="flex gap-3 p-3 rounded-lg border border-slate-100 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-900/40 cursor-pointer">
                                <input type="checkbox" name="selected[]" value="<?= htmlspecialchars($a['fingerprint']) ?>" checked class="import-check mt-1 rounded border-slate-300">
                                <?php if (!empty($a['image'])): ?>
                                    <img src="<?= htmlspecialchars($a['image']) ?>" alt="" class="w-20 h-16 object-cover rounded-md flex-shrink-0 bg-slate-100" onerror="this.style.display='none'">
                                <?php else: ?>
                                    <div class="w-20 h-16 rounded-md bg-slate-100 dark:bg-slate-700 flex-shrink-0 flex items-center justify-center text-slate-400 text-xs">No img</div>
                                <?php endif; ?>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-bold text-slate-800 dark:text-white leading-snug"><?= htmlspecialchars($a['title']) ?></p>
                                    <p class="text-[11px] text-slate-400 mt-1"><?= htmlspecialchars($a['source'] ?? '') ?>
                                        <?php if (!empty($a['publishedAt'])): ?> · <?= htmlspecialchars(date('d M Y H:i', strtotime($a['publishedAt']) ?: time())) ?><?php endif; ?>
                                    </p>
                                    <p class="text-xs text-slate-500 mt-1 line-clamp-2"><?= htmlspecialchars($a['summary'] ?? '') ?></p>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </form>
            <?php endif; ?>
        </div>

        <p class="text-[11px] text-slate-400 mt-3 leading-relaxed">
            Mapping: API title → post title/slug, description → summary, body + source link → content, image URL → featured_image,
            source → image_credit + tags, your admin → author_id, chosen category → category_id. Duplicate titles are skipped.
        </p>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
