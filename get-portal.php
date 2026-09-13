<?php
/**
 * Product demo / sales page — share this link in DMs.
 */
require_once 'config/database.php';
require_once 'helpers/query_functions.php';

$page_title = 'Get This News Portal';
require_once 'layouts/header.php';

$demo_on = get_config('demo_mode', '1') === '1';
$demo_email = get_config('demo_admin_email', 'demo@digitalcreatorss.com');
$demo_pass = get_config('demo_admin_password', 'Demo@12345');
$wa = preg_replace('/\D+/', '', get_config('sales_whatsapp', get_config('contact_whatsapp', '918851613806')));
$phone = get_config('sales_phone', get_config('contact_phone', '+91-8851613806'));
$email = get_config('sales_email', get_config('contact_email', 'support@digitalcreatorss.com'));
$site = get_config('site_name', 'News Portal');
?>

<main class="bg-slate-50">
    <section class="relative overflow-hidden bg-slate-950 text-white">
        <div class="absolute inset-0 bg-gradient-to-br from-indigo-700/40 via-slate-950 to-rose-700/20"></div>
        <div class="container mx-auto px-4 py-16 md:py-24 relative">
            <p class="text-[11px] font-black uppercase tracking-[0.25em] text-indigo-300 mb-4">Digital Creatorss Product Demo</p>
            <h1 class="text-4xl md:text-6xl font-black tracking-tight max-w-4xl leading-tight mb-6">
                Launch your local news channel website in days — not months.
            </h1>
            <p class="text-slate-300 text-base md:text-lg max-w-2xl mb-8 leading-relaxed">
                This live demo is a ready News Portal CMS for city channels, newspapers, and digital newsrooms.
                We customize branding, categories, weather city, e-paper PDF, ads, and domain for your channel.
            </p>
            <div class="flex flex-col sm:flex-row gap-3">
                <a href="<?= BASE_URL ?>/admin/login.php" class="inline-flex items-center justify-center gap-2 bg-indigo-500 hover:bg-indigo-400 text-white font-black uppercase tracking-widest text-xs px-6 py-4 rounded-xl">
                    <i class="fa-solid fa-lock-open"></i> Try Admin Panel
                </a>
                <a href="https://wa.me/<?= htmlspecialchars($wa) ?>?text=<?= urlencode('Hi Digital Creatorss, I saw the News Portal demo and want a customized version for my channel.') ?>"
                    target="_blank" rel="noopener"
                    class="inline-flex items-center justify-center gap-2 bg-emerald-500 hover:bg-emerald-400 text-white font-black uppercase tracking-widest text-xs px-6 py-4 rounded-xl">
                    <i class="fa-brands fa-whatsapp"></i> WhatsApp Us to Customize
                </a>
                <a href="<?= BASE_URL ?>/" class="inline-flex items-center justify-center gap-2 border border-white/20 hover:bg-white/10 text-white font-black uppercase tracking-widest text-xs px-6 py-4 rounded-xl">
                    View Live Website
                </a>
            </div>
        </div>
    </section>

    <?php if ($demo_on): ?>
    <section class="container mx-auto px-4 -mt-8 relative z-10 mb-12">
        <div class="bg-white border border-indigo-100 shadow-xl rounded-3xl p-6 md:p-8 grid grid-cols-1 md:grid-cols-3 gap-6 items-center">
            <div class="md:col-span-2">
                <h2 class="text-xl font-black text-slate-900 mb-2">Try it yourself (Demo Admin)</h2>
                <p class="text-sm text-slate-500 mb-4">Login, add a post, upload e-news PDF, and explore settings. This is a demo — please don’t change password.</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                    <div class="rounded-2xl bg-slate-50 border border-slate-100 p-4">
                        <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Demo URL</p>
                        <a class="font-bold text-indigo-600 break-all" href="<?= BASE_URL ?>/admin/login.php"><?= htmlspecialchars(BASE_URL) ?>/admin/login.php</a>
                    </div>
                    <div class="rounded-2xl bg-slate-50 border border-slate-100 p-4">
                        <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Email</p>
                        <p class="font-bold text-slate-800"><?= htmlspecialchars($demo_email) ?></p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 border border-slate-100 p-4 sm:col-span-2">
                        <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Password</p>
                        <p class="font-bold text-slate-800 tracking-wide"><?= htmlspecialchars($demo_pass) ?></p>
                    </div>
                </div>
            </div>
            <div class="text-center md:text-right">
                <a href="<?= BASE_URL ?>/admin/login.php" class="inline-flex items-center justify-center gap-2 w-full md:w-auto bg-slate-900 hover:bg-indigo-600 text-white font-black uppercase tracking-widest text-xs px-6 py-4 rounded-xl">
                    Open Admin Login
                </a>
                <p class="text-[11px] text-slate-400 mt-3">Need help? We guide you on a call.</p>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <section class="container mx-auto px-4 py-10 md:py-16">
        <h2 class="text-3xl font-black text-slate-900 mb-3">What your channel gets</h2>
        <p class="text-slate-500 mb-10 max-w-2xl">Built for local / regional news teams who want a clean website + full admin control.</p>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            <?php
            $features = [
                ['fa-newspaper', 'News CMS', 'Publish articles, drafts, featured images (upload or URL), tags, authors.'],
                ['fa-layer-group', 'Categories + Subcategories', 'Dropdown menu for sections like City, Politics, Sports, Crime.'],
                ['fa-file-pdf', 'E-News / E-Paper PDF', 'Upload daily newspaper PDF editions for readers to download.'],
                ['fa-cloud-sun', 'Weather widget', 'Show local city weather on homepage sidebar.'],
                ['fa-comments', 'Comments + Contact Inbox', 'Moderate reader comments and manage contact form messages.'],
                ['fa-bullhorn', 'Ads + Breaking ticker', 'Sell ad slots and run a live breaking-news bar.'],
                ['fa-envelope-open-text', 'Newsletter', 'Collect subscribers and send campaigns (SMTP).'],
                ['fa-mobile-screen', 'Mobile-ready design', 'Looks good on phone — most local readers browse on mobile.'],
                ['fa-language', 'SEO + RSS + Sitemap', 'Clean URLs, Google Analytics, RSS feed, XML sitemap.'],
            ];
            foreach ($features as $f): ?>
                <div class="bg-white border border-slate-100 rounded-3xl p-6 shadow-sm">
                    <div class="h-11 w-11 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center mb-4">
                        <i class="fa-solid <?= $f[0] ?>"></i>
                    </div>
                    <h3 class="font-black text-slate-900 mb-2"><?= $f[1] ?></h3>
                    <p class="text-sm text-slate-500 leading-relaxed"><?= $f[2] ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="bg-white border-y border-slate-100">
        <div class="container mx-auto px-4 py-14">
            <h2 class="text-3xl font-black text-slate-900 mb-3">We customize it for your brand</h2>
            <p class="text-slate-500 mb-8 max-w-2xl">Share this demo link in WhatsApp/DM. When they like it, we deliver a customized portal.</p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <?php foreach ([
                    'Your logo, colors, and channel name',
                    'City / district categories you need',
                    'Weather city set to your area',
                    'Domain + Hostinger / Linux hosting setup',
                    'Admin training for your team',
                    'Optional: extra pages, reporters login, custom sections',
                ] as $item): ?>
                    <div class="flex items-start gap-3 p-4 rounded-2xl bg-slate-50 border border-slate-100">
                        <i class="fa-solid fa-check text-emerald-500 mt-1"></i>
                        <span class="font-medium text-slate-700"><?= $item ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="container mx-auto px-4 py-14">
        <div class="rounded-3xl bg-slate-950 text-white p-8 md:p-12 grid grid-cols-1 lg:grid-cols-2 gap-8 items-center">
            <div>
                <h2 class="text-3xl font-black mb-3">Ready to sell / ready to buy?</h2>
                <p class="text-slate-300 mb-6">Send this page link to clients. Let them try the admin. Then close with a customization package.</p>
                <ul class="space-y-2 text-sm text-slate-300 mb-6">
                    <li><i class="fa-solid fa-phone text-indigo-400 mr-2"></i><?= htmlspecialchars($phone) ?></li>
                    <li><i class="fa-solid fa-envelope text-indigo-400 mr-2"></i><?= htmlspecialchars($email) ?></li>
                    <li><i class="fa-brands fa-whatsapp text-emerald-400 mr-2"></i>WhatsApp: <?= htmlspecialchars($wa) ?></li>
                </ul>
            </div>
            <div class="flex flex-col gap-3">
                <a href="https://wa.me/<?= htmlspecialchars($wa) ?>?text=<?= urlencode('I want a customized News Portal for my channel. Demo: ' . BASE_URL . '/get-portal') ?>"
                    target="_blank" class="text-center bg-emerald-500 hover:bg-emerald-400 text-white font-black uppercase tracking-widest text-xs px-6 py-4 rounded-xl">
                    Chat on WhatsApp
                </a>
                <a href="mailto:<?= htmlspecialchars($email) ?>?subject=News%20Portal%20Customization"
                    class="text-center bg-white/10 hover:bg-white/15 text-white font-black uppercase tracking-widest text-xs px-6 py-4 rounded-xl">
                    Email Digital Creatorss
                </a>
                <a href="<?= BASE_URL ?>/e-news" class="text-center border border-white/20 hover:bg-white/10 text-white font-black uppercase tracking-widest text-xs px-6 py-4 rounded-xl">
                    See E-News PDF section
                </a>
            </div>
        </div>
    </section>
</main>

<?php require_once 'layouts/footer.php'; ?>
