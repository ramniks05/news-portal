<?php
require_once 'config/database.php';
require_once 'helpers/query_functions.php';

$page_title = 'Advertise With Us';
require_once 'layouts/header.php';

$email = get_config('sales_email', get_config('contact_email', 'support@digitalcreatorss.com'));
$phone = get_config('sales_phone', get_config('contact_phone', '+91-8851613806'));
$whatsapp_raw = get_config('sales_whatsapp', get_config('contact_whatsapp', '918851613806'));
$whatsapp = preg_replace('/\D+/', '', $whatsapp_raw);
$address = get_config('contact_address', 'Greater Noida West, Gautam Budh Nagar, UP');
$site = get_config('site_name', 'News Portal');
$phone_tel = preg_replace('/[^\d+]/', '', $phone);

$wa_msg = urlencode("Hello {$site} team, I want to advertise on your news portal. Please share ad packages and rates.");

// Nice display number: +91 88516 13806
$wa_display = $whatsapp_raw;
if (preg_match('/^91(\d{5})(\d{5})$/', $whatsapp, $m)) {
    $wa_display = '+91 ' . $m[1] . ' ' . $m[2];
} elseif (preg_match('/^(\d{5})(\d{5})$/', $whatsapp, $m)) {
    $wa_display = '+91 ' . $m[1] . ' ' . $m[2];
} elseif ($whatsapp !== '') {
    $wa_display = '+' . $whatsapp;
}
?>

<main class="bg-white min-h-[70vh]">
    <section class="border-b border-slate-100">
        <div class="container mx-auto px-4 py-14 md:py-20 max-w-5xl animate-fade-in">
            <p class="text-indigo-600 font-black text-[11px] uppercase tracking-[0.3em] mb-4">Advertise</p>
            <h1 class="text-4xl md:text-5xl font-black text-slate-900 tracking-tighter leading-tight mb-5 max-w-3xl">
                Grow with <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 to-rose-500"><?= htmlspecialchars($site) ?></span>
            </h1>
            <p class="text-slate-500 text-base md:text-lg max-w-2xl leading-relaxed mb-8 font-medium">
                Put your brand in front of local readers on homepage, articles, sidebar, breaking ticker, and e-news editions.
                Simple packages. Fast go-live. Real human support from Digital Creatorss.
            </p>
            <div class="flex flex-col sm:flex-row gap-3">
                <a href="https://wa.me/<?= htmlspecialchars($whatsapp) ?>?text=<?= $wa_msg ?>" target="_blank" rel="noopener"
                    class="inline-flex items-center justify-center gap-2 bg-[#25D366] hover:bg-emerald-500 text-white text-xs font-black uppercase tracking-widest px-6 py-3.5 rounded-full shadow-lg shadow-green-100 transition">
                    <i class="fa-brands fa-whatsapp text-lg"></i> Get packages on WhatsApp
                </a>
                <a href="#ad-inquiry" class="inline-flex items-center justify-center gap-2 bg-slate-900 hover:bg-indigo-600 text-white text-xs font-black uppercase tracking-widest px-6 py-3.5 rounded-full transition">
                    Request a callback
                </a>
            </div>
        </div>
    </section>

    <section class="container mx-auto px-4 py-14 md:py-16 max-w-5xl">
        <div class="mb-8 border-b border-slate-100 pb-4">
            <h2 class="text-2xl md:text-3xl font-black text-slate-900 tracking-tight">Where your ad appears</h2>
            <p class="text-slate-500 text-sm mt-2">Pick a placement. We’ll confirm size, duration, and pricing for your city.</p>
        </div>

        <div class="divide-y divide-slate-100">
            <?php
            $slots = [
                ['01', 'Homepage header banner', 'Highest visibility on the main news feed — ideal for launches and festivals.'],
                ['02', 'Homepage sidebar', 'Stays visible beside recent stories, weather, and trending.'],
                ['03', 'Article page unit', 'Shown while readers finish stories — strong for local shops & services.'],
                ['04', 'Breaking ticker sponsor', 'Brand association with live breaking updates.'],
                ['05', 'E-News / PDF edition', 'Mention or listing with downloadable newspaper editions.'],
                ['06', 'Sponsored story', 'Native article format with your message, reviewed by our desk.'],
            ];
            foreach ($slots as $s): ?>
                <div class="py-5 md:py-6 flex flex-col md:flex-row md:items-start gap-3 md:gap-8 group">
                    <span class="text-indigo-600 font-black text-sm tracking-widest md:w-12 flex-shrink-0"><?= $s[0] ?></span>
                    <div class="flex-1">
                        <h3 class="font-black text-slate-900 text-lg group-hover:text-indigo-600 transition"><?= $s[1] ?></h3>
                        <p class="text-slate-500 text-sm mt-1 leading-relaxed max-w-2xl"><?= $s[2] ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="bg-slate-50 border-y border-slate-100">
        <div class="container mx-auto px-4 py-14 md:py-16 max-w-5xl">
            <h2 class="text-2xl md:text-3xl font-black text-slate-900 tracking-tight mb-8">Why brands choose us</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div>
                    <p class="text-indigo-600 font-black text-xs uppercase tracking-widest mb-2">Audience</p>
                    <p class="text-slate-700 text-sm leading-relaxed font-medium">Local readers who open the site for city news, politics, and daily updates — not random scroll traffic.</p>
                </div>
                <div>
                    <p class="text-indigo-600 font-black text-xs uppercase tracking-widest mb-2">Flexibility</p>
                    <p class="text-slate-700 text-sm leading-relaxed font-medium">Weekly, monthly, or campaign runs. Image banner or HTML/AdSense-ready slots managed from admin.</p>
                </div>
                <div>
                    <p class="text-indigo-600 font-black text-xs uppercase tracking-widest mb-2">Support</p>
                    <p class="text-slate-700 text-sm leading-relaxed font-medium">WhatsApp desk for creative size, go-live, and renewals. Optional help with banner design.</p>
                </div>
            </div>
        </div>
    </section>

    <section id="ad-inquiry" class="container mx-auto px-4 py-14 md:py-20 max-w-5xl">
        <div class="grid grid-cols-1 lg:grid-cols-5 gap-10 items-start">
            <div class="lg:col-span-2">
                <h2 class="text-2xl md:text-3xl font-black text-slate-900 tracking-tight mb-3">Advertising desk</h2>
                <p class="text-slate-500 text-sm leading-relaxed mb-6">
                    Tell us your business, city, and preferred dates. We’ll reply with available slots and rates.
                </p>
                <div class="space-y-4 text-sm">
                    <a href="mailto:<?= htmlspecialchars($email) ?>" class="flex items-start gap-3 group">
                        <span class="h-10 w-10 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center flex-shrink-0"><i class="fa-solid fa-envelope"></i></span>
                        <span>
                            <span class="block text-[10px] font-black uppercase tracking-widest text-slate-400">Email</span>
                            <span class="font-bold text-slate-800 group-hover:text-indigo-600 transition"><?= htmlspecialchars($email) ?></span>
                        </span>
                    </a>
                    <a href="tel:<?= htmlspecialchars($phone_tel) ?>" class="flex items-start gap-3 group">
                        <span class="h-10 w-10 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0"><i class="fa-solid fa-phone"></i></span>
                        <span>
                            <span class="block text-[10px] font-black uppercase tracking-widest text-slate-400">Phone</span>
                            <span class="font-bold text-slate-800 group-hover:text-indigo-600 transition"><?= htmlspecialchars($phone) ?></span>
                        </span>
                    </a>
                    <div class="flex items-start gap-3">
                        <span class="h-10 w-10 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center flex-shrink-0"><i class="fa-solid fa-location-dot"></i></span>
                        <span>
                            <span class="block text-[10px] font-black uppercase tracking-widest text-slate-400">Office</span>
                            <span class="font-bold text-slate-800 leading-relaxed"><?= nl2br(htmlspecialchars($address)) ?></span>
                        </span>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-3">
                <div class="rounded-[2rem] border border-emerald-100 bg-emerald-50 p-7 md:p-9 shadow-sm">
                    <div class="flex items-center gap-3 mb-5">
                        <span class="h-12 w-12 rounded-2xl bg-[#25D366] text-white flex items-center justify-center text-2xl shadow-lg">
                            <i class="fa-brands fa-whatsapp"></i>
                        </span>
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-widest text-emerald-600">Quick start</p>
                            <h3 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight">WhatsApp advertising desk</h3>
                        </div>
                    </div>

                    <p class="text-slate-600 text-sm leading-relaxed mb-6 max-w-lg">
                        Fastest way to get the rate card. Send your <strong>business name</strong>, <strong>city</strong>, and how many weeks you want to run.
                    </p>

                    <div class="bg-white border border-slate-100 rounded-2xl px-4 py-3 mb-5 inline-flex items-center gap-3">
                        <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">Number</span>
                        <span class="font-bold text-slate-900 text-base tracking-wide"><?= htmlspecialchars($wa_display) ?></span>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-3">
                        <a href="https://wa.me/<?= htmlspecialchars($whatsapp) ?>?text=<?= $wa_msg ?>" target="_blank" rel="noopener"
                            class="inline-flex items-center justify-center gap-2 bg-[#25D366] hover:bg-emerald-700 text-white text-xs font-black uppercase tracking-widest px-6 py-3.5 rounded-full shadow-lg transition">
                            <i class="fa-brands fa-whatsapp text-lg"></i> Chat on WhatsApp
                        </a>
                        <a href="<?= BASE_URL ?>/contact-us"
                            class="inline-flex items-center justify-center gap-2 bg-white hover:bg-slate-50 text-slate-800 border border-slate-200 text-xs font-black uppercase tracking-widest px-6 py-3.5 rounded-full transition">
                            Use contact form
                        </a>
                    </div>

                    <p class="text-xs text-slate-400 mt-5">
                        On the form, set subject to <span class="font-bold text-slate-600">Advertising</span>.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <section class="border-t border-slate-100 bg-slate-50">
        <div class="container mx-auto px-4 py-10 max-w-5xl flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <p class="text-sm text-slate-500 font-medium">Want a full news website like this for your channel?</p>
            <a href="<?= BASE_URL ?>/get-portal" class="inline-flex items-center gap-2 text-xs font-black uppercase tracking-widest text-indigo-600 hover:text-indigo-700">
                View portal demo <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>
    </section>
</main>

<style>
.animate-fade-in { animation: fadeIn .7s ease-out forwards; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: none; } }
</style>

<?php require_once 'layouts/footer.php'; ?>
