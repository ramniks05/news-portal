<?php
require_once 'includes/header.php';
require_once '../config/database.php';
require_once '../helpers/common_functions.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<div x-data="settingsManager()">

    <div class="mb-8 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-800 dark:text-white">System Configuration</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400">Control core settings, API keys, and email servers.</p>
        </div>
        <button @click="validateAndSave()"
            class="lg:hidden w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 rounded-md shadow flex justify-center items-center transition-all active:scale-95">
            <i class="fa-solid fa-floppy-disk mr-2"></i> Save Changes
        </button>
    </div>

    <div class="flex flex-col lg:flex-row gap-8 pb-20">
        <div class="w-full lg:w-64 flex-shrink-0 z-30 lg:static sticky top-0 sm:top-[70px] transition-all">
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow border border-slate-200 dark:border-slate-700 overflow-hidden">
                <nav class="flex flex-row lg:flex-col overflow-x-auto lg:overflow-visible no-scrollbar scroll-smooth" aria-label="Tabs">
                    <?php
                    $tabs = [
                        'general' => ['icon' => 'fa-sliders', 'label' => 'General'],
                        'media'   => ['icon' => 'fa-photo-film', 'label' => 'Media'],
                        'contact' => ['icon' => 'fa-address-card', 'label' => 'Contact'],
                        'social'  => ['icon' => 'fa-share-nodes', 'label' => 'Social'],
                        'seo'     => ['icon' => 'fa-globe', 'label' => 'SEO'],
                        'scripts' => ['icon' => 'fa-code', 'label' => 'Scripts'],
                        'smtp'    => ['icon' => 'fa-server', 'label' => 'SMTP'],
                    ];

                    foreach ($tabs as $key => $tab): ?>
                        <button @click="activeTab = '<?= $key ?>'; window.scrollTo({top: 0, behavior: 'smooth'})"
                            :class="activeTab === '<?= $key ?>' 
                            ? 'border-indigo-600 text-indigo-700 bg-indigo-50 dark:bg-slate-700 dark:text-indigo-400' 
                            : 'border-transparent text-slate-600 hover:bg-slate-50 dark:text-slate-400 dark:hover:bg-slate-700'"
                            class="group flex items-center justify-center lg:justify-start whitespace-nowrap px-4 py-3 lg:px-5 lg:py-4 text-sm font-medium transition-all 
                               border-b-4 lg:border-b-0 lg:border-l-4 
                               flex-shrink-0 flex-1 lg:flex-none lg:w-full">

                            <i class="fa-solid <?= $tab['icon'] ?> text-base lg:text-lg lg:w-6 text-center"
                                :class="activeTab === '<?= $key ?>' ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-400 group-hover:text-slate-500'"></i>

                            <span class="ml-2 lg:ml-3"><?= $tab['label'] ?></span>
                        </button>
                    <?php endforeach; ?>
                </nav>

                <div class="lg:hidden bg-slate-50 dark:bg-slate-900 border-t border-slate-200 dark:border-slate-700 py-1.5 text-center">
                    <p class="text-[10px] text-slate-400 dark:text-slate-500 flex justify-center items-center gap-2">
                        <i class="fa-solid fa-angles-left text-[9px] opacity-50"></i>
                        Swipe to see more
                        <i class="fa-solid fa-angles-right text-[9px] opacity-50"></i>
                    </p>
                </div>
            </div>
        </div>

        <div class="flex-1">
            <form id="settingsForm" action="handlers/settings_handler.php" method="POST" enctype="multipart/form-data"
                autocomplete="off"
                class="bg-white dark:bg-slate-800 rounded-lg shadow border border-slate-200 dark:border-slate-700 p-6 sm:p-8 relative">

                <input style="display:none" type="text" name="fakeusernameremembered" />
                <input style="display:none" type="password" name="fakepasswordremembered" />

                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                <div x-show="activeTab === 'general'" data-tab="general" x-cloak class="space-y-6 animate-fade-in">
                    <div class="border-b border-slate-200 dark:border-slate-700 pb-4 mb-4">
                        <h3 class="text-xl font-bold text-slate-800 dark:text-white">General Information</h3>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Website Name <span class="text-red-500">*</span></label>
                            <input type="text" name="site_name" required value="<?= htmlspecialchars(get_setting('site_name')) ?>"
                                class="w-full rounded-md border border-slate-400 px-4 py-3 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 dark:bg-slate-900 dark:border-slate-600 dark:text-white shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Tagline</label>
                            <input type="text" name="site_tagline" value="<?= htmlspecialchars(get_setting('site_tagline')) ?>"
                                class="w-full rounded-md border border-slate-400 px-4 py-3 text-sm dark:bg-slate-900 dark:border-slate-600 dark:text-white shadow-sm">
                        </div>
                    </div>

                    <div x-data="{ copyrightText: '<?= htmlspecialchars(get_setting('footer_copyright'), ENT_QUOTES) ?>' }">
                        <div class="flex justify-between items-center mb-2">
                            <label class="block text-sm font-bold text-slate-700 dark:text-slate-300">
                                Footer Copyright <span class="text-red-500">*</span>
                            </label>
                            <span class="text-xs font-mono font-bold transition-colors"
                                :class="copyrightText.length >= 40 ? 'text-red-500' : 'text-slate-400'">
                                <span x-text="copyrightText.length"></span>/40
                            </span>
                        </div>

                        <input type="text"
                            name="footer_copyright"
                            x-model="copyrightText"
                            maxlength="40"
                            required
                            class="w-full rounded-md border border-slate-400 px-4 py-3 text-sm dark:bg-slate-900 dark:border-slate-600 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 placeholder-slate-400"
                            placeholder="(c) 2026 Digital Creatorss">

                        <p class="text-[10px] text-slate-500 mt-1">Keep it short. Example: &copy; 2026 News Portal.</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Default Timezone</label>
                            <select name="timezone" class="w-full rounded-md border border-slate-400 px-4 py-3 text-sm bg-white dark:bg-slate-900 dark:border-slate-600 dark:text-white">
                                <option value="Asia/Kolkata" <?= get_setting('timezone') == 'Asia/Kolkata' ? 'selected' : '' ?>>Asia/Kolkata (IST)</option>
                                <option value="UTC" <?= get_setting('timezone') == 'UTC' ? 'selected' : '' ?>>UTC</option>
                            </select>
                        </div>
                        <div class="flex items-center justify-between bg-slate-50 dark:bg-slate-700/50 p-4 rounded-md border border-slate-200 dark:border-slate-600">
                            <div>
                                <span class="block text-sm font-bold text-slate-800 dark:text-white">Maintenance Mode</span>
                                <span class="text-xs text-slate-500 dark:text-slate-400">Show "Under Maintenance" to visitors.</span>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="hidden" name="maintenance_mode" value="0">
                                <input type="checkbox" name="maintenance_mode" value="1" class="sr-only peer" <?= get_setting('maintenance_mode') == '1' ? 'checked' : '' ?>>
                                <div class="w-11 h-6 bg-slate-300 peer-focus:outline-none rounded-full peer dark:bg-slate-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                            </label>
                        </div>
                    </div>

                    <div class="border-t border-slate-200 dark:border-slate-700 pt-6 mt-2">
                        <h4 class="text-sm font-bold text-slate-800 dark:text-white mb-4">Weather widget</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">City name</label>
                                <input type="text" name="weather_city" value="<?= htmlspecialchars(get_setting('weather_city', 'Mumbai')) ?>" class="w-full rounded-md border border-slate-400 px-3 py-2 text-sm dark:bg-slate-900 dark:border-slate-600 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Latitude</label>
                                <input type="text" name="weather_lat" value="<?= htmlspecialchars(get_setting('weather_lat', '19.0760')) ?>" class="w-full rounded-md border border-slate-400 px-3 py-2 text-sm dark:bg-slate-900 dark:border-slate-600 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Longitude</label>
                                <input type="text" name="weather_lon" value="<?= htmlspecialchars(get_setting('weather_lon', '72.8777')) ?>" class="w-full rounded-md border border-slate-400 px-3 py-2 text-sm dark:bg-slate-900 dark:border-slate-600 dark:text-white">
                            </div>
                        </div>
                        <p class="text-[11px] text-slate-400 mt-2">Find lat/lon on Google Maps → right click place → coordinates.</p>
                    </div>

                    <div class="border-t border-slate-200 dark:border-slate-700 pt-6 mt-2">
                        <h4 class="text-sm font-bold text-slate-800 dark:text-white mb-2">Sales demo credentials (public page)</h4>
                        <p class="text-xs text-slate-500 mb-4">Shown on <code>/get-portal</code> when Demo Mode is ON. Create matching admin user in Users / Profile.</p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="flex items-center justify-between bg-slate-50 dark:bg-slate-700/50 p-4 rounded-md border border-slate-200 dark:border-slate-600 md:col-span-2">
                                <div>
                                    <span class="block text-sm font-bold text-slate-800 dark:text-white">Show demo login on Get Portal page</span>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="hidden" name="demo_mode" value="0">
                                    <input type="checkbox" name="demo_mode" value="1" class="sr-only peer" <?= get_setting('demo_mode', '1') == '1' ? 'checked' : '' ?>>
                                    <div class="w-11 h-6 bg-slate-300 peer-focus:outline-none rounded-full peer dark:bg-slate-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                                </label>
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Demo email (display)</label>
                                <input type="email" name="demo_admin_email" value="<?= htmlspecialchars(get_setting('demo_admin_email', 'demo@digitalcreatorss.com')) ?>" class="w-full rounded-md border border-slate-400 px-3 py-2 text-sm dark:bg-slate-900 dark:border-slate-600 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Demo password (display)</label>
                                <input type="text" name="demo_admin_password" value="<?= htmlspecialchars(get_setting('demo_admin_password', 'Demo@12345')) ?>" class="w-full rounded-md border border-slate-400 px-3 py-2 text-sm dark:bg-slate-900 dark:border-slate-600 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Sales WhatsApp</label>
                                <input type="text" name="sales_whatsapp" value="<?= htmlspecialchars(get_setting('sales_whatsapp', '918851613806')) ?>" class="w-full rounded-md border border-slate-400 px-3 py-2 text-sm dark:bg-slate-900 dark:border-slate-600 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Sales phone</label>
                                <input type="text" name="sales_phone" value="<?= htmlspecialchars(get_setting('sales_phone', '+91-8851613806')) ?>" class="w-full rounded-md border border-slate-400 px-3 py-2 text-sm dark:bg-slate-900 dark:border-slate-600 dark:text-white">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Sales email</label>
                                <input type="email" name="sales_email" value="<?= htmlspecialchars(get_setting('sales_email', 'support@digitalcreatorss.com')) ?>" class="w-full rounded-md border border-slate-400 px-3 py-2 text-sm dark:bg-slate-900 dark:border-slate-600 dark:text-white">
                            </div>
                        </div>
                    </div>
                </div>

                <div x-show="activeTab === 'media'" data-tab="media" x-cloak class="space-y-6 animate-fade-in">
                    <div class="border-b border-slate-200 dark:border-slate-700 pb-4 mb-4">
                        <h3 class="text-xl font-bold text-slate-800 dark:text-white">Logos & Icons</h3>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="bg-slate-50 dark:bg-slate-700/30 p-5 rounded-lg border border-slate-200 dark:border-slate-600">
                            <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-3">Website Logo</label>
                            <?php if (get_setting('logo_path')): ?>
                                <div class="mb-4 p-2 bg-white dark:bg-slate-800 border border-slate-200 rounded w-fit">
                                    <img src="<?= BASE_URL . '/' . htmlspecialchars(get_setting('logo_path')) ?>" alt="Logo" class="h-12 object-contain bg-gray-50 border rounded p-1">
                                </div>
                            <?php endif; ?>
                            <input type="file" name="site_logo" accept=".png, .jpg, .jpeg, .webp" class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-xs file:font-bold file:bg-indigo-600 file:text-white hover:file:bg-indigo-700">
                            <p class="text-xs text-slate-500 mt-2">Max 2MB. Format: PNG, JPG, WebP.</p>
                        </div>

                        <div class="bg-slate-50 dark:bg-slate-700/30 p-5 rounded-lg border border-slate-200 dark:border-slate-600">
                            <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-3">Favicon</label>
                            <?php if (get_setting('favicon_path')): ?>
                                <div class="mb-4 p-2 bg-white dark:bg-slate-800 border border-slate-200 rounded w-fit">
                                    <img src="<?= BASE_URL . '/' . htmlspecialchars(get_setting('favicon_path')) ?>" alt="Favicon" class="h-8 w-8 object-contain bg-gray-50 border rounded p-1">
                                </div>
                            <?php endif; ?>
                            <input type="file" name="site_favicon" accept=".ico, .png" class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-xs file:font-bold file:bg-indigo-600 file:text-white hover:file:bg-indigo-700">
                            <p class="text-xs text-slate-500 mt-2">Max 100KB. Format: ICO, PNG.</p>
                        </div>
                    </div>
                </div>
                <div x-show="activeTab === 'contact'" data-tab="contact" x-cloak class="space-y-6 animate-fade-in">
                    <div class="border-b border-slate-200 dark:border-slate-700 pb-4 mb-4">
                        <h3 class="text-xl font-bold text-slate-800 dark:text-white">Contact Details</h3>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div x-data="{ emailError: false }">
                            <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">
                                Public Email <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <input type="email" name="contact_email" required
                                    value="<?= htmlspecialchars(get_setting('contact_email')) ?>"
                                    pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$"
                                    @input="emailError = !$event.target.checkValidity()"
                                    @blur="emailError = !$event.target.checkValidity()"
                                    title="Must contain @ and a domain (e.g. .com)"
                                    class="w-full rounded-md border border-slate-400 px-4 py-3 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 dark:bg-slate-900 dark:border-slate-600 dark:text-white invalid:border-red-500 invalid:text-red-600 focus:invalid:border-red-500 focus:invalid:ring-red-500 placeholder-slate-400"
                                    placeholder="info@example.com">

                                <div x-show="emailError" x-cloak class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                    <i class="fa-solid fa-circle-exclamation text-red-500"></i>
                                </div>
                            </div>
                            <p x-show="emailError" x-cloak class="mt-1 text-xs text-red-500 font-bold">
                                Invalid email! Must include '@' and a domain like '.com'
                            </p>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Phone Number</label>
                            <input type="text" name="contact_phone" value="<?= htmlspecialchars(get_setting('contact_phone')) ?>"
                                class="w-full rounded-md border border-slate-400 px-4 py-3 text-sm dark:bg-slate-900 dark:border-slate-600 dark:text-white">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">WhatsApp Number</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-green-600"><i class="fa-brands fa-whatsapp text-lg"></i></span>
                                <input type="text" name="contact_whatsapp" value="<?= htmlspecialchars(get_setting('contact_whatsapp')) ?>" placeholder="+91..."
                                    class="w-full rounded-md border border-slate-400 pl-10 pr-4 py-3 text-sm focus:border-green-500 focus:ring-1 focus:ring-green-500 dark:bg-slate-900 dark:border-slate-600 dark:text-white">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Office Address</label>
                            <textarea name="contact_address" rows="2" class="w-full rounded-md border border-slate-400 px-4 py-3 text-sm dark:bg-slate-900 dark:border-slate-600 dark:text-white"><?= htmlspecialchars(get_setting('contact_address')) ?></textarea>
                        </div>
                    </div>
                </div>
                <div x-show="activeTab === 'social'" data-tab="social" x-cloak class="space-y-6 animate-fade-in">
                    <div class="border-b border-slate-200 dark:border-slate-700 pb-4 mb-4">
                        <h3 class="text-xl font-bold text-slate-800 dark:text-white">Social Media Links</h3>
                        <p class="text-sm text-slate-500">Links must start with https://</p>
                    </div>

                    <?php
                    $socials = [
                        'social_facebook'  => ['Facebook Page', 'fa-facebook', 'text-blue-600', 'https://facebook.com/yourpage'],
                        'social_twitter'   => ['X (Twitter)', 'fa-x-twitter', 'text-black dark:text-white', 'https://x.com/yourhandle'],
                        'social_instagram' => ['Instagram', 'fa-instagram', 'text-pink-600', 'https://instagram.com/yourhandle'],
                        'social_youtube'   => ['YouTube Channel', 'fa-youtube', 'text-red-600', 'https://youtube.com/@channel'],
                    ];
                    foreach ($socials as $key => $info): ?>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-1"><?= $info[0] ?></label>
                            <div class="flex rounded-md shadow-sm">
                                <span class="inline-flex items-center px-4 rounded-l-md border border-r-0 border-slate-400 bg-slate-100 dark:bg-slate-700 dark:border-slate-600">
                                    <i class="fa-brands <?= $info[1] ?> <?= $info[2] ?> text-lg"></i>
                                </span>
                                <input type="url" name="<?= $key ?>" value="<?= htmlspecialchars(get_setting($key)) ?>" placeholder="<?= $info[3] ?>"
                                    class="flex-1 min-w-0 block w-full px-4 py-3 rounded-none rounded-r-md border border-slate-400 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 text-sm dark:bg-slate-900 dark:border-slate-600 dark:text-white">
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div x-show="activeTab === 'seo'" data-tab="seo" x-cloak class="space-y-6 animate-fade-in">
                    <div class="border-b border-slate-200 dark:border-slate-700 pb-4 mb-4">
                        <h3 class="text-xl font-bold text-slate-800 dark:text-white">SEO Configuration</h3>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Home Meta Description</label>
                        <textarea name="site_description" rows="3" maxlength="160"
                            class="w-full rounded-md border border-slate-400 px-4 py-3 text-sm focus:border-indigo-500 dark:bg-slate-900 dark:border-slate-600 dark:text-white"
                            placeholder="Describe your site in 160 characters..."><?= htmlspecialchars(get_setting('site_description')) ?></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Meta Keywords (Comma separated)</label>
                        <input type="text" name="site_keywords" value="<?= htmlspecialchars(get_setting('site_keywords')) ?>"
                            class="w-full rounded-md border border-slate-400 px-4 py-3 text-sm dark:bg-slate-900 dark:border-slate-600 dark:text-white"
                            placeholder="news, india, breaking, politics">
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Google Analytics ID</label>
                        <input type="text" name="google_analytics_id" value="<?= htmlspecialchars(get_setting('google_analytics_id')) ?>"
                            class="w-full rounded-md border border-slate-400 px-4 py-3 text-sm font-mono dark:bg-slate-900 dark:border-slate-600 dark:text-white"
                            placeholder="G-XXXXXXXXXX">
                    </div>
                </div>
                <div x-show="activeTab === 'scripts'" data-tab="scripts" x-cloak class="space-y-6 animate-fade-in">
                    <div class="border-b border-slate-200 dark:border-slate-700 pb-4 mb-4">
                        <h3 class="text-xl font-bold text-slate-800 dark:text-white">Global Scripts</h3>
                        <p class="text-sm text-slate-500">Add Google AdSense auto-ads or tracking pixels here.</p>
                    </div>

                    <div class="bg-amber-50 border-l-4 border-amber-500 p-4 mb-4 text-amber-800 text-sm">
                        <strong>Note:</strong> These scripts run on every page. For specific ad banners, use the <a href="ads-manager.php" class="underline font-bold">Ad Manager</a>.
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Header Scripts (Inside &lt;head&gt;)</label>
                        <textarea name="header_scripts" rows="6"
                            class="w-full rounded-md border border-slate-400 px-4 py-3 text-xs font-mono bg-slate-50 dark:bg-slate-900 dark:border-slate-600 dark:text-slate-300"
                            placeholder="<script>...</script>"><?= htmlspecialchars(get_setting('header_scripts')) ?></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Footer Scripts (Before &lt;/body&gt;)</label>
                        <textarea name="footer_scripts" rows="6"
                            class="w-full rounded-md border border-slate-400 px-4 py-3 text-xs font-mono bg-slate-50 dark:bg-slate-900 dark:border-slate-600 dark:text-slate-300"
                            placeholder="<script>...</script>"><?= htmlspecialchars(get_setting('footer_scripts')) ?></textarea>
                    </div>
                </div>
                <div x-show="activeTab === 'smtp'" data-tab="smtp" x-cloak class="space-y-6 animate-fade-in">
                    <div class="border-b border-slate-200 dark:border-slate-700 pb-4 mb-4">
                        <h3 class="text-xl font-bold text-slate-800 dark:text-white">SMTP Server Settings</h3>
                        <p class="text-sm text-slate-500">Required for sending Newsletters and Verification emails.</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">SMTP Host</label>
                            <input type="text" name="smtp_host" value="<?= htmlspecialchars(get_setting('smtp_host')) ?>"
                                autocomplete="off" placeholder="smtp.gmail.com"
                                class="w-full rounded-md border border-slate-400 px-4 py-3 text-sm dark:bg-slate-900 dark:border-slate-600 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">SMTP Port</label>
                            <input type="number" name="smtp_port" value="<?= htmlspecialchars(get_setting('smtp_port')) ?>"
                                autocomplete="off" placeholder="587"
                                class="w-full rounded-md border border-slate-400 px-4 py-3 text-sm dark:bg-slate-900 dark:border-slate-600 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">SMTP Username</label>
                            <input type="text" name="smtp_user" value="<?= htmlspecialchars(get_setting('smtp_user')) ?>"
                                autocomplete="off"
                                class="w-full rounded-md border border-slate-400 px-4 py-3 text-sm dark:bg-slate-900 dark:border-slate-600 dark:text-white">
                        </div>

                        <div class="relative">
                            <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">SMTP Password</label>
                            <div class="relative">
                                <input :type="showSmtpPass ? 'text' : 'password'" name="smtp_pass" value="<?= htmlspecialchars(get_setting('smtp_pass')) ?>"
                                    autocomplete="new-password"
                                    class="w-full rounded-md border border-slate-400 px-4 py-3 text-sm pr-10 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 dark:bg-slate-900 dark:border-slate-600 dark:text-white">
                                <button type="button" @click="showSmtpPass = !showSmtpPass" class="absolute right-3 top-3 text-slate-400 hover:text-indigo-600 focus:outline-none">
                                    <i class="fa-solid" :class="showSmtpPass ? 'fa-eye-slash' : 'fa-eye'"></i>
                                </button>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Encryption Method</label>
                            <select name="smtp_encryption" class="w-full rounded-md border border-slate-400 px-4 py-3 text-sm bg-white dark:bg-slate-900 dark:border-slate-600 dark:text-white">
                                <option value="tls" <?= get_setting('smtp_encryption') == 'tls' ? 'selected' : '' ?>>TLS (Standard)</option>
                                <option value="ssl" <?= get_setting('smtp_encryption') == 'ssl' ? 'selected' : '' ?>>SSL (Secure)</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4 border-t border-slate-100 dark:border-slate-700">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Sender Name</label>
                            <input type="text" name="smtp_from_name" value="<?= htmlspecialchars(get_setting('smtp_from_name')) ?>"
                                class="w-full rounded-md border border-slate-400 px-4 py-3 text-sm dark:bg-slate-900 dark:border-slate-600 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Sender Email (From)</label>
                            <input type="email" name="smtp_from_email" value="<?= htmlspecialchars(get_setting('smtp_from_email')) ?>"
                                class="w-full rounded-md border border-slate-400 px-4 py-3 text-sm dark:bg-slate-900 dark:border-slate-600 dark:text-white">
                        </div>
                    </div>
                </div>
                <div class="hidden lg:flex mt-8 pt-6 border-t border-slate-200 dark:border-slate-700 justify-end sticky bottom-0 bg-white dark:bg-slate-800 py-4 -mb-6 sm:-mb-8 rounded-b-lg z-10">
                    <button type="button" @click="validateAndSave()"
                        class="bg-indigo-700 hover:bg-indigo-800 text-white font-bold py-3 px-8 rounded-md shadow-lg hover:shadow-xl transition-all flex items-center transform hover:-translate-y-0.5 active:scale-95">
                        <i class="fa-solid fa-floppy-disk mr-2"></i> Save Configuration
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('settingsManager', () => ({
            activeTab: 'general',
            showSmtpPass: false,

            validateAndSave() {
                const form = document.getElementById('settingsForm');
                const requiredInputs = form.querySelectorAll('input[required], select[required], textarea[required]');
                let firstError = null;

                for (const input of requiredInputs) {
                    if (!input.value.trim()) {
                        firstError = input;
                        break;
                    }
                }

                if (firstError) {
                    const label = firstError.previousElementSibling ? firstError.previousElementSibling.innerText : 'A required field';
                    const parentTab = firstError.closest('[data-tab]');
                    if (parentTab) {
                        this.activeTab = parentTab.getAttribute('data-tab');
                    }

                    window.dispatchEvent(new CustomEvent('notify', {
                        detail: {
                            message: `${label.replace('*', '').trim()} cannot be empty!`,
                            type: 'error'
                        }
                    }));

                    setTimeout(() => {
                        firstError.focus();
                        firstError.classList.add('ring-2', 'ring-red-500', 'border-red-500');
                        firstError.addEventListener('input', function() {
                            this.classList.remove('ring-2', 'ring-red-500', 'border-red-500');
                        }, {
                            once: true
                        });
                    }, 100);

                } else {
                    form.submit();
                }
            }
        }));
    });
</script>

<?php include 'includes/footer.php'; ?>