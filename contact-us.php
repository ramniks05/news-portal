<?php
require_once 'config/database.php';
require_once 'helpers/query_functions.php';

$page_title = 'Contact Us';
require_once 'layouts/header.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$site = get_config('site_name', 'News Portal');
$email = get_config('contact_email', get_config('sales_email', 'support@digitalcreatorss.com'));
$phone = get_config('contact_phone', get_config('sales_phone', '+91-8851613806'));
$address = get_config('contact_address', 'Greater Noida West, Gautam Budh Nagar, UP');
$whatsapp_raw = get_config('contact_whatsapp', get_config('sales_whatsapp', '918851613806'));
$whatsapp = preg_replace('/\D+/', '', $whatsapp_raw);
$phone_tel = preg_replace('/[^\d+]/', '', $phone);

$wa_display = $whatsapp_raw;
if (preg_match('/^91(\d{5})(\d{5})$/', $whatsapp, $m)) {
    $wa_display = '+91 ' . $m[1] . ' ' . $m[2];
} elseif (preg_match('/^(\d{5})(\d{5})$/', $whatsapp, $m)) {
    $wa_display = '+91 ' . $m[1] . ' ' . $m[2];
} elseif ($whatsapp !== '') {
    $wa_display = '+' . $whatsapp;
}

$wa_msg = urlencode("Hello {$site} team, I have a message for your news desk.");
?>

<main class="bg-white min-h-[70vh]">
    <section class="border-b border-slate-100">
        <div class="container mx-auto px-4 py-14 md:py-20 max-w-5xl animate-fade-in">
            <p class="text-indigo-600 font-black text-[11px] uppercase tracking-[0.3em] mb-4"><?= htmlspecialchars($site) ?></p>
            <h1 class="text-4xl md:text-5xl font-black text-slate-900 tracking-tighter leading-tight mb-5 max-w-3xl">
                Talk to the <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 to-rose-500">news desk</span>
            </h1>
            <p class="text-slate-500 text-base md:text-lg max-w-2xl leading-relaxed mb-8 font-medium">
                Tips, corrections, feedback, or partnerships — send a note and we’ll get back to you.
                Looking for ads? Use our
                <a href="<?= BASE_URL ?>/advertise" class="text-indigo-600 font-bold hover:underline">Advertise</a> page.
            </p>
            <div class="flex flex-col sm:flex-row gap-3">
                <a href="#contact-form"
                    class="inline-flex items-center justify-center gap-2 bg-slate-900 hover:bg-indigo-600 text-white text-xs font-black uppercase tracking-widest px-6 py-3.5 rounded-full transition">
                    Write a message
                </a>
                <?php if ($whatsapp): ?>
                <a href="https://wa.me/<?= htmlspecialchars($whatsapp) ?>?text=<?= $wa_msg ?>" target="_blank" rel="noopener"
                    class="inline-flex items-center justify-center gap-2 bg-[#25D366] hover:bg-emerald-500 text-white text-xs font-black uppercase tracking-widest px-6 py-3.5 rounded-full shadow-lg shadow-green-100 transition">
                    <i class="fa-brands fa-whatsapp text-lg"></i> Chat on WhatsApp
                </a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section class="container mx-auto px-4 py-12 md:py-16 max-w-5xl">
        <div class="mb-8 border-b border-slate-100 pb-4">
            <h2 class="text-2xl md:text-3xl font-black text-slate-900 tracking-tight">How we can help</h2>
            <p class="text-slate-500 text-sm mt-2">Pick a topic when you write — it helps us route your message faster.</p>
        </div>
        <div class="divide-y divide-slate-100">
            <?php
            $topics = [
                ['01', 'News tip', 'Share a verified lead, photo, or video from your area.'],
                ['02', 'Correction', 'Flag a factual error or outdated detail in a published story.'],
                ['03', 'Feedback', 'Tell us what works on the site — and what we should improve.'],
                ['04', 'Partnership', 'Collaborations, syndication, or community campaigns.'],
                ['05', 'Advertising', 'Prefer the form? Set subject to Advertising, or visit Advertise.'],
            ];
            foreach ($topics as $t): ?>
                <div class="py-5 md:py-6 flex flex-col md:flex-row md:items-start gap-3 md:gap-8 group">
                    <span class="text-indigo-600 font-black text-sm tracking-widest md:w-12 flex-shrink-0"><?= $t[0] ?></span>
                    <div class="flex-1">
                        <h3 class="font-black text-slate-900 text-lg group-hover:text-indigo-600 transition"><?= $t[1] ?></h3>
                        <p class="text-slate-500 text-sm mt-1 leading-relaxed max-w-2xl"><?= $t[2] ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section id="contact-form" class="bg-slate-50 border-y border-slate-100">
        <div class="container mx-auto px-4 py-14 md:py-20 max-w-5xl">
            <div class="grid grid-cols-1 lg:grid-cols-5 gap-10 lg:gap-14 items-start">
                <div class="lg:col-span-2">
                    <h2 class="text-2xl md:text-3xl font-black text-slate-900 tracking-tight mb-3">Desk details</h2>
                    <p class="text-slate-500 text-sm leading-relaxed mb-8">
                        Prefer calling or mailing? Reach the same team that runs the editorial inbox.
                    </p>

                    <div class="space-y-5 text-sm">
                        <a href="mailto:<?= htmlspecialchars($email) ?>" class="flex items-start gap-3 group">
                            <span class="h-10 w-10 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center flex-shrink-0">
                                <i class="fa-solid fa-envelope"></i>
                            </span>
                            <span>
                                <span class="block text-[10px] font-black uppercase tracking-widest text-slate-400">Email</span>
                                <span class="font-bold text-slate-800 group-hover:text-indigo-600 transition"><?= htmlspecialchars($email) ?></span>
                            </span>
                        </a>
                        <a href="tel:<?= htmlspecialchars($phone_tel) ?>" class="flex items-start gap-3 group">
                            <span class="h-10 w-10 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                                <i class="fa-solid fa-phone"></i>
                            </span>
                            <span>
                                <span class="block text-[10px] font-black uppercase tracking-widest text-slate-400">Phone</span>
                                <span class="font-bold text-slate-800 group-hover:text-indigo-600 transition"><?= htmlspecialchars($phone) ?></span>
                            </span>
                        </a>
                        <div class="flex items-start gap-3">
                            <span class="h-10 w-10 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center flex-shrink-0">
                                <i class="fa-solid fa-location-dot"></i>
                            </span>
                            <span>
                                <span class="block text-[10px] font-black uppercase tracking-widest text-slate-400">Office</span>
                                <span class="font-bold text-slate-800 leading-relaxed"><?= nl2br(htmlspecialchars($address)) ?></span>
                            </span>
                        </div>
                    </div>

                    <?php if ($whatsapp): ?>
                    <div class="mt-10 pt-8 border-t border-slate-200">
                        <p class="text-[10px] font-black uppercase tracking-[0.25em] text-emerald-700 mb-2">Quick reply</p>
                        <p class="text-slate-700 text-sm font-medium mb-1">WhatsApp</p>
                        <p class="font-bold text-slate-900 text-base tracking-wide mb-4"><?= htmlspecialchars($wa_display) ?></p>
                        <a href="https://wa.me/<?= htmlspecialchars($whatsapp) ?>?text=<?= $wa_msg ?>" target="_blank" rel="noopener"
                            class="inline-flex items-center justify-center gap-2 bg-[#25D366] hover:bg-emerald-500 text-white text-xs font-black uppercase tracking-widest px-5 py-3 rounded-full shadow-lg shadow-green-100 transition">
                            <i class="fa-brands fa-whatsapp text-lg"></i> Open chat
                        </a>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="lg:col-span-3" x-data="contactForm()">
                    <div class="bg-white border border-slate-200/80 rounded-[1.75rem] p-6 md:p-9 shadow-sm">
                        <div class="mb-7">
                            <p class="text-[10px] font-black uppercase tracking-[0.25em] text-indigo-600 mb-2">Contact form</p>
                            <h3 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight">Send a message</h3>
                            <p class="text-slate-500 text-sm mt-2">We usually respond within one business day.</p>
                        </div>

                        <form @submit.prevent="submitForm" class="space-y-5">
                            <div class="hidden" aria-hidden="true">
                                <input type="text" x-model="form.website" tabindex="-1" autocomplete="off">
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div>
                                    <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Name *</label>
                                    <input type="text" x-model="form.name" required maxlength="100" autocomplete="name"
                                        class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50/50 text-sm font-medium focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Email *</label>
                                    <input type="email" x-model="form.email" required maxlength="150" autocomplete="email"
                                        class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50/50 text-sm font-medium focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition">
                                </div>
                            </div>

                            <div>
                                <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Subject</label>
                                <input type="text" x-model="form.subject" maxlength="200" list="contact-subjects"
                                    placeholder="News tip / Correction / Feedback / Advertising"
                                    class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50/50 text-sm font-medium focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition">
                                <datalist id="contact-subjects">
                                    <option value="News tip"></option>
                                    <option value="Correction"></option>
                                    <option value="Feedback"></option>
                                    <option value="Partnership"></option>
                                    <option value="Advertising"></option>
                                </datalist>
                            </div>

                            <div>
                                <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Message *</label>
                                <textarea x-model="form.message" required rows="6" maxlength="5000"
                                    placeholder="Share the details — city, date, and links help if this is a tip or correction."
                                    class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50/50 text-sm font-medium focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none resize-y transition"></textarea>
                            </div>

                            <div class="flex flex-col sm:flex-row sm:items-center gap-4 pt-1">
                                <button type="submit" :disabled="loading"
                                    class="inline-flex items-center justify-center gap-2 bg-slate-900 hover:bg-indigo-600 disabled:opacity-60 text-white text-xs font-black uppercase tracking-widest px-7 py-3.5 rounded-full transition">
                                    <span x-show="!loading">Send message</span>
                                    <span x-show="loading" x-cloak class="inline-flex items-center gap-2">
                                        <i class="fa-solid fa-circle-notch fa-spin"></i> Sending...
                                    </span>
                                </button>
                                <p x-show="message" x-cloak class="text-sm font-medium"
                                    :class="status === 'success' ? 'text-emerald-600' : 'text-rose-600'"
                                    x-text="message"></p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="border-t border-slate-100">
        <div class="container mx-auto px-4 py-10 max-w-5xl flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <p class="text-sm text-slate-500 font-medium">Want banner or sponsored placements on this portal?</p>
            <a href="<?= BASE_URL ?>/advertise" class="inline-flex items-center gap-2 text-xs font-black uppercase tracking-widest text-indigo-600 hover:text-indigo-700">
                View advertising options <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>
    </section>
</main>

<script>
function contactForm() {
    return {
        loading: false,
        message: '',
        status: '',
        form: {
            name: '',
            email: '',
            subject: '',
            message: '',
            website: '',
            csrf_token: '<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES) ?>'
        },
        async submitForm() {
            this.loading = true;
            this.message = '';
            const fd = new FormData();
            Object.keys(this.form).forEach(k => fd.append(k, this.form[k]));
            try {
                const res = await fetch('<?= BASE_URL ?>/handlers/contact_handler.php', {
                    method: 'POST',
                    body: fd,
                    credentials: 'same-origin'
                });
                const data = await res.json();
                this.status = data.status;
                this.message = data.message;
                if (data.status === 'success') {
                    this.form.message = '';
                    this.form.subject = '';
                    this.form.website = '';
                }
            } catch (e) {
                this.status = 'error';
                this.message = 'System error. Please try again.';
            } finally {
                this.loading = false;
            }
        }
    }
}
</script>

<style>
.animate-fade-in { animation: fadeIn .7s ease-out forwards; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: none; } }
</style>

<?php require_once 'layouts/footer.php'; ?>
