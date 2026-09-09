<?php
require_once 'config/database.php';
require_once 'helpers/query_functions.php';

$page_title = "Contact Us";
require_once 'layouts/header.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$email = get_config('contact_email', 'contact@newsportal.com');
$phone = get_config('contact_phone', '+91 98765 43210');
$address = get_config('contact_address', 'Kolkata, West Bengal, India');
$whatsapp = get_config('contact_whatsapp', '');
?>

<main class="bg-white min-h-[70vh] py-16 md:py-24">
    <div class="container mx-auto px-4 max-w-5xl">

        <div class="text-center mb-14 animate-fade-in">
            <h2 class="text-indigo-600 font-black text-xs uppercase tracking-[0.3em] mb-4">Get in Touch</h2>
            <h1 class="text-4xl md:text-5xl font-black text-slate-900 tracking-tighter">
                We'd love to <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 to-rose-500">hear from you.</span>
            </h1>
            <p class="text-slate-500 mt-6 text-base max-w-2xl mx-auto font-medium">
                Have a news tip, feedback, or advertising inquiry? Send a message or use the channels below.
            </p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-5 gap-10 items-start mb-16">
            <div class="lg:col-span-3 bg-slate-50 border border-slate-100 rounded-3xl p-6 md:p-8" x-data="contactForm()">
                <h3 class="text-sm font-black uppercase tracking-widest text-slate-800 mb-6">Send a message</h3>
                <form @submit.prevent="submitForm" class="space-y-4">
                    <div class="hidden" aria-hidden="true">
                        <input type="text" x-model="form.website" tabindex="-1" autocomplete="off">
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Name *</label>
                            <input type="text" x-model="form.name" required maxlength="100"
                                class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-white text-sm font-medium focus:ring-2 focus:ring-indigo-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Email *</label>
                            <input type="email" x-model="form.email" required maxlength="150"
                                class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-white text-sm font-medium focus:ring-2 focus:ring-indigo-500 outline-none">
                        </div>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Subject</label>
                        <input type="text" x-model="form.subject" maxlength="200" placeholder="News tip / Advertising / Feedback"
                            class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-white text-sm font-medium focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Message *</label>
                        <textarea x-model="form.message" required rows="5" maxlength="5000"
                            class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-white text-sm font-medium focus:ring-2 focus:ring-indigo-500 outline-none resize-y"
                            placeholder="Write your message..."></textarea>
                    </div>
                    <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                        <button type="submit" :disabled="loading"
                            class="inline-flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-60 text-white text-xs font-black uppercase tracking-widest px-6 py-3 rounded-xl shadow-lg shadow-indigo-100 transition">
                            <span x-show="!loading">Send Message</span>
                            <span x-show="loading" x-cloak>Sending...</span>
                        </button>
                        <p x-show="message" x-cloak class="text-sm font-medium"
                            :class="status === 'success' ? 'text-green-600' : 'text-red-600'"
                            x-text="message"></p>
                    </div>
                </form>
            </div>

            <div class="lg:col-span-2 space-y-4">
                <div class="p-6 rounded-3xl bg-white border border-slate-100 shadow-soft text-center">
                    <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <i class="fa-solid fa-envelope-open-text"></i>
                    </div>
                    <h3 class="font-bold text-slate-900 mb-1">Email</h3>
                    <a href="mailto:<?= htmlspecialchars($email) ?>" class="text-indigo-600 text-sm font-bold hover:underline"><?= htmlspecialchars($email) ?></a>
                </div>
                <div class="p-6 rounded-3xl bg-white border border-slate-100 shadow-soft text-center">
                    <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <i class="fa-solid fa-phone-volume"></i>
                    </div>
                    <h3 class="font-bold text-slate-900 mb-1">Phone</h3>
                    <a href="tel:<?= htmlspecialchars($phone) ?>" class="text-emerald-600 text-sm font-bold hover:underline"><?= htmlspecialchars($phone) ?></a>
                </div>
                <div class="p-6 rounded-3xl bg-white border border-slate-100 shadow-soft text-center">
                    <div class="w-12 h-12 bg-rose-50 text-rose-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <i class="fa-solid fa-location-dot"></i>
                    </div>
                    <h3 class="font-bold text-slate-900 mb-1">Office</h3>
                    <address class="not-italic text-rose-600 text-sm font-bold leading-relaxed"><?= nl2br(htmlspecialchars($address)) ?></address>
                </div>
                <?php if ($whatsapp): ?>
                    <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $whatsapp) ?>" target="_blank"
                        class="flex items-center justify-center gap-3 px-6 py-4 bg-[#25D366] text-white rounded-full font-bold shadow-lg shadow-green-200 hover:scale-105 transition-transform">
                        <i class="fa-brands fa-whatsapp text-xl"></i> Chat on WhatsApp
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
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
.shadow-soft { box-shadow: 0 10px 30px -5px rgba(0,0,0,.04); }
.animate-fade-in { animation: fadeIn .8s ease-out forwards; }
@keyframes fadeIn { from { opacity:0; transform:translateY(20px);} to { opacity:1; transform:none;} }
</style>

<?php include 'layouts/footer.php'; ?>
