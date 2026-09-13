<?php
$footer_logo = get_config('logo_path');
$site_name = get_config('site_name', 'News Portal');
$footer_desc = get_config('site_description', 'Your trusted source for the latest news updates.');
$footer_cats = get_menu_categories();
$whatsapp_no = get_config('contact_whatsapp');
$whatsapp_message = urlencode("Hello $site_name Team, I have an inquiry.");

$social_links = [
    ['fa-facebook-f', get_config('social_facebook'), 'bg-[#1877F2]'],
    ['fa-x-twitter', get_config('social_twitter'), 'bg-black'],
    ['fa-instagram', get_config('social_instagram'), 'bg-gradient-to-tr from-[#f9ce34] via-[#ee2a7b] to-[#6228d7]'],
    ['fa-youtube', get_config('social_youtube'), 'bg-[#FF0000]']
];
?>

<style>
    .floating-whatsapp {
        position: fixed;
        bottom: 25px;
        right: 25px;
        z-index: 1000;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        animation: pulse 2s infinite cubic-bezier(0.66, 0, 0, 1);
    }

    .floating-whatsapp:hover {
        transform: scale(1.1) rotate(10deg);
        animation: none;
    }

    @keyframes pulse {

        0%,
        100% {
            transform: scale(1);
            box-shadow: 0 0 0 0 rgba(37, 211, 102, 0.7);
        }

        50% {
            transform: scale(1.05);
            box-shadow: 0 0 0 15px rgba(37, 211, 102, 0);
        }
    }
</style>

<footer class="bg-slate-900 text-slate-400 mt-auto pt-16 pb-8 border-t border-slate-800">
    <div class="container mx-auto px-4">
        <?php if (!empty($footer_cats)): ?>
            <div class="py-8 border-y border-slate-800/50 mb-12">
                <h3 class="text-white font-black mb-5 uppercase text-[11px] tracking-widest flex items-center gap-2">
                    <span class="w-1.5 h-3 bg-red-500 rounded-full"></span> Top Categories
                </h3>
                <ul class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-10 gap-x-6 gap-y-3 text-xs font-semibold">
                    <?php foreach ($footer_cats as $cat): ?>
                        <li>
                            <a href="<?= category_url($cat['slug']) ?>" class="hover:text-indigo-400 transition-colors flex items-center group">
                                <i class="fa-solid fa-angle-right text-[8px] mr-2 text-slate-700 group-hover:text-indigo-500 transition-transform group-hover:translate-x-1"></i>
                                <?= htmlspecialchars($cat['name']) ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-10 mb-12">

            <div class="space-y-6">
                <div>
                    <a href="<?= BASE_URL ?>" class="inline-block transition-opacity hover:opacity-80">
                        <?php if ($footer_logo): ?>
                            <img src="<?= BASE_URL . '/' . $footer_logo ?>" alt="<?= htmlspecialchars($site_name) ?>" class="h-10 w-auto object-contain">
                        <?php else: ?>
                            <h2 class="text-2xl font-black text-white tracking-tighter uppercase">
                                NEWS<span class="text-indigo-500">PORTAL</span>
                            </h2>
                        <?php endif; ?>
                    </a>

                    <?php
                    $tagline = get_config('site_tagline');
                    if ($tagline):
                    ?>
                        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mt-3">
                            <?= htmlspecialchars($tagline) ?>
                        </p>
                    <?php endif; ?>
                </div>
                <p class="text-xs leading-relaxed text-slate-400 max-w-xs pt-4 border-t border-slate-800">
                    <?= htmlspecialchars($footer_desc) ?>
                </p>

                <div class="flex items-center gap-3 pt-2">
                    <?php foreach ($social_links as $soc): if ($soc[1]): ?>
                            <a href="<?= $soc[1] ?>" target="_blank"
                                class="h-9 w-9 rounded-full flex items-center justify-center text-white transition-all hover:scale-110 shadow-md <?= $soc[2] ?>">
                                <i class="fa-brands <?= $soc[0] ?> text-sm"></i>
                            </a>
                    <?php endif;
                    endforeach; ?>
                </div>
            </div>

            <div class="lg:pl-8">
                <h3 class="text-white font-bold mb-6 uppercase text-[11px] tracking-widest flex items-center gap-2">
                    <span class="w-1 h-3 bg-indigo-500 rounded-full"></span> Quick Links
                </h3>
                <ul class="space-y-3 text-xs font-semibold">
                    <li><a href="<?= page_url('about-us') ?>" class="hover:text-indigo-400 transition-colors">About Us</a></li>
                    <li><a href="<?= page_url('contact-us') ?>" class="hover:text-indigo-400 transition-colors">Contact Us</a></li>
                    <li><a href="<?= BASE_URL ?>/advertise" class="hover:text-indigo-400 transition-colors">Advertise</a></li>
                    <li><a href="<?= BASE_URL ?>/e-news" class="hover:text-indigo-400 transition-colors">E-News PDF</a></li>
                    <li><a href="<?= BASE_URL ?>/get-portal" class="hover:text-indigo-400 transition-colors">Get This Portal</a></li>
                    <li><a href="<?= page_url('privacy-policy') ?>" class="hover:text-indigo-400 transition-colors">Privacy Policy</a></li>
                    <li><a href="<?= page_url('terms') ?>" class="hover:text-indigo-400 transition-colors">Terms & Conditions</a></li>
                    <li><a href="<?= BASE_URL ?>/rss" class="hover:text-indigo-400 transition-colors">RSS Feed</a></li>
                    <li><a href="<?= BASE_URL ?>/sitemap.xml" class="hover:text-indigo-400 transition-colors">Sitemap</a></li>
                </ul>
            </div>

            <div id="newsletter" class="lg:col-span-2 bg-slate-800/40 p-6 rounded-2xl border border-slate-700/50">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-8">

                    <div x-data="newsletterForm()">
                        <h3 class="text-white font-bold mb-2 text-sm">Join Our Newsletter</h3>
                        <p class="text-[11px] text-slate-500 mb-4 leading-normal">Verified news alerts directly in your inbox.</p>

                        <form @submit.prevent="submitSubscription" class="space-y-3">
                            <div class="relative group">
                                <i class="fa-regular fa-envelope absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 text-xs group-focus-within:text-indigo-400 transition-colors"></i>
                                <input type="email" x-model="email" required placeholder="Email Address"
                                    class="w-full pl-10 pr-4 py-3 bg-slate-900 border border-slate-700 rounded-xl text-xs text-white focus:outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all shadow-inner">
                            </div>
                            <button type="submit" :disabled="loading"
                                class="w-full bg-indigo-600 hover:bg-indigo-700 text-white text-[11px] font-black py-3 rounded-xl transition-all active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed shadow-lg shadow-indigo-900/20">
                                <span x-show="!loading" class="tracking-widest">SUBSCRIBE NOW</span>
                                <span x-show="loading" x-cloak class="tracking-widest"><i class="fa-solid fa-circle-notch fa-spin"></i> PROCESSING...</span>
                            </button>

                            <div x-show="message" x-cloak
                                x-transition:enter="transition ease-out duration-300"
                                x-transition:enter-start="opacity-0 -translate-y-1"
                                x-transition:enter-end="opacity-100 translate-y-0"
                                :class="status === 'success' ? 'bg-green-500/10 text-green-500 border-green-500/20' : 'bg-red-500/10 text-red-500 border-red-500/20'"
                                class="p-3 rounded-lg border text-[10px] font-bold text-center uppercase tracking-wider">
                                <i class="fa-solid mr-1" :class="status === 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation'"></i>
                                <span x-text="message"></span>
                            </div>
                        </form>
                    </div>
                    <div class="border-l border-slate-700/50 pl-6 space-y-4">
                        <h3 class="text-white font-bold text-sm">Contact Details</h3>
                        <p class="text-xs flex items-start gap-3">
                            <i class="fa-solid fa-envelope mt-1 text-indigo-500 flex-shrink-0"></i>
                            <a href="mailto:<?= get_config('contact_email') ?>" class="hover:text-white"><?= get_config('contact_email') ?></a>
                        </p>
                        <p class="text-xs flex items-start gap-3">
                            <i class="fa-solid fa-phone mt-1 text-indigo-500 flex-shrink-0"></i>
                            <a href="tel:<?= get_config('contact_phone') ?>" class="hover:text-white"><?= get_config('contact_phone') ?></a>
                        </p>
                        <p class="text-xs flex items-start gap-3">
                            <i class="fa-solid fa-location-dot mt-1 text-indigo-500 flex-shrink-0"></i>
                            <span class="leading-normal"><?= get_config('contact_address') ?></span>
                        </p>
                    </div>
                </div>
            </div>

        </div>

        <div class="border-t border-slate-800/50 pt-8 flex flex-col md:flex-row justify-between items-center gap-6">
            <div class="text-[10px] font-bold text-slate-500 tracking-wider text-center md:text-left space-y-1">
                <div><?= get_config('footer_copyright') ?: ('© ' . date('Y') . ' ' . htmlspecialchars($site_name) . '. All rights reserved.') ?></div>
                <div class="font-medium text-slate-600">
                    Developed by
                    <a href="https://digitalcreatorss.com" target="_blank" rel="noopener" class="text-indigo-400 hover:text-white transition">Digital Creatorss</a>
                    · <a href="mailto:support@digitalcreatorss.com" class="hover:text-white transition">support@digitalcreatorss.com</a>
                </div>
            </div>

            <button onclick="window.scrollTo({top: 0, behavior: 'smooth'})"
                class="group flex items-center gap-2 text-[10px] font-black text-slate-500 hover:text-white transition-colors uppercase tracking-widest">
                Back to Top
                <span class="h-8 w-8 rounded-full bg-slate-800 flex items-center justify-center group-hover:bg-indigo-600 transition-all">
                    <i class="fa-solid fa-arrow-up text-xs"></i>
                </span>
            </button>
        </div>
    </div>
</footer>

<?php if ($whatsapp_no): ?>
    <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $whatsapp_no) ?>?text=<?= $whatsapp_message ?>"
        target="_blank"
        class="floating-whatsapp bg-[#25D366] h-16 w-16 rounded-full flex items-center justify-center text-white shadow-xl hover:shadow-2xl active:scale-95">
        <i class="fa-brands fa-whatsapp text-4xl"></i>
    </a>
<?php endif; ?>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('newsletterForm', () => ({
            email: '',
            loading: false,
            message: '',
            status: '',
            async submitSubscription() {
                this.loading = true;
                this.message = '';
                const formData = new FormData();
                formData.append('email', this.email);
                formData.append('action', 'subscribe');

                try {
                    const res = await fetch('<?= BASE_URL ?>/admin/handlers/newsletter_handler.php', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await res.json();

                    this.message = data.message;
                    this.status = data.status;

                    if (data.status === 'success') this.email = '';
                    setTimeout(() => this.message = '', 6000);
                } catch (err) {
                    this.message = 'Network error. Please try again.';
                    this.status = 'error';
                } finally {
                    this.loading = false;
                }
            }
        }));
    });
</script>

<?= get_config('footer_scripts') ?>
</body>

</html>