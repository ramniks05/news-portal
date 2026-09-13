<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../helpers/query_functions.php';

check_maintenance_mode();

$menu_cats = get_menu_categories();
$breaking_news = get_breaking_news();
$site_name = get_config('site_name', 'News Portal');
$site_desc = get_config('site_description');
$logo = get_config('logo_path');
$favicon = get_config('favicon_path');

$current_slug = $_GET['slug'] ?? '';

if (!function_exists('category_url')) {
    function category_url($slug)
    {
        return BASE_URL . "/category/" . $slug;
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? htmlspecialchars($page_title) . ' - ' . $site_name : $site_name ?></title>
    <?php if ($favicon): ?>
        <link rel="icon" type="image/png" href="<?= BASE_URL . '/' . $favicon ?>">
    <?php else: ?>
        <link rel="icon" type="image/x-icon" href="<?= BASE_URL ?>/assets/images/static/favicon.ico">
    <?php endif; ?>
    <?php include 'meta_tags.php'; ?>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/src/output.css">

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        [x-cloak] {
            display: none !important;
        }

        #preloader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: #ffffff;
            z-index: 9999;
            display: flex;
            justify-content: center;
            align-items: center;
            transition: opacity 0.5s ease, visibility 0.5s ease;
        }

        .loader-spin {
            width: 40px;
            height: 40px;
            border: 4px solid #e2e8f0;
            border-top: 4px solid #4f46e5;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .loaded #preloader {
            opacity: 0;
            visibility: hidden;
        }

        @keyframes marquee {
            0% {
                transform: translateX(100%);
            }

            100% {
                transform: translateX(-100%);
            }
        }

        .animate-marquee {
            display: inline-block;
            white-space: nowrap;
            animation: marquee 30s linear infinite;
        }

        .animate-marquee:hover {
            animation-play-state: paused;
        }

        .sidebar-scroll::-webkit-scrollbar {
            width: 5px;
        }

        .sidebar-scroll::-webkit-scrollbar-thumb {
            background-color: #cbd5e1;
            border-radius: 4px;
        }

        .animate-fade-in {
            animation: fadeIn 0.5s ease-in-out forwards;
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

    <link rel="alternate" type="application/rss+xml" title="<?= htmlspecialchars($site_name) ?> RSS" href="<?= BASE_URL ?>/rss">
    <?php
    $ga_id = trim((string) get_config('google_analytics_id', ''));
    if ($ga_id !== ''):
    ?>
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?= htmlspecialchars($ga_id, ENT_QUOTES) ?>"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
      gtag('config', '<?= htmlspecialchars($ga_id, ENT_QUOTES) ?>');
    </script>
    <?php endif; ?>
    <?= get_config('header_scripts') ?>
    <style>
        .article-body img { max-width: 100% !important; height: auto !important; max-height: 420px; object-fit: cover; border-radius: 1rem; }
        .article-body pre, .article-body code { white-space: pre-wrap; word-break: break-word; }
    </style>
</head>

<body class="bg-gray-50 text-slate-800 flex flex-col min-h-screen antialiased selection:bg-indigo-100 selection:text-indigo-700">
    <div id="preloader">
        <div class="flex flex-col items-center gap-4">
            <div class="loader-spin"></div>
            <p class="text-slate-500 text-xs font-medium tracking-widest uppercase animate-pulse">Loading...</p>
        </div>
    </div>

    <script>
        window.addEventListener('load', function() {
            document.body.classList.add('loaded');
        });
    </script>

    <div class="bg-slate-900 text-slate-300 text-[11px] font-medium py-2 hidden md:block border-b border-slate-800">
        <div class="container mx-auto px-4 flex justify-between items-center">
            <div class="flex items-center gap-4">
                <span><i class="fa-regular fa-clock mr-1.5 text-indigo-400"></i> <?= date('l, F j, Y') ?></span>
                <span class="text-slate-600">|</span>
                <a href="<?= BASE_URL ?>/contact-us" class="hover:text-white transition">Advertise</a>
                <span class="text-slate-600">|</span>
                <a href="<?= BASE_URL ?>/e-news" class="hover:text-white transition">E-News</a>
                <span class="text-slate-600">|</span>
                <a href="<?= BASE_URL ?>/get-portal" class="hover:text-white transition">Get Portal</a>
                <span class="text-slate-600">|</span>
                <a href="<?= BASE_URL ?>/about-us" class="hover:text-white transition">About Us</a>
                <span class="text-slate-600">|</span>
                <a href="<?= BASE_URL ?>/contact-us" class="hover:text-white transition">Contact Us</a>
            </div>
            <div class="flex gap-4">
                <?php
                $socials = [['facebook', 'social_facebook', 'hover:text-[#1877F2]'], ['x-twitter', 'social_twitter', 'hover:text-white'], ['instagram', 'social_instagram', 'hover:text-[#E4405F]'], ['youtube', 'social_youtube', 'hover:text-[#FF0000]']];
                foreach ($socials as $soc):
                    $link = get_config($soc[1]);
                    if ($link): ?>
                        <a href="<?= $link ?>" target="_blank" class="<?= $soc[2] ?> transition-colors"><i class="fa-brands fa-<?= $soc[0] ?>"></i></a>
                <?php endif;
                endforeach; ?>
            </div>
        </div>
    </div>

    <header class="bg-white shadow-soft sticky top-0 z-40 transition-all duration-300" x-data="{ mobileMenu: false, searchOpen: false }">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center h-16 md:h-20">

                <div class="flex items-center gap-4">
                    <button @click="mobileMenu = true" class="md:hidden p-2 -ml-2 text-slate-700 hover:text-indigo-600 hover:bg-slate-50 rounded-full transition">
                        <i class="fa-solid fa-bars-staggered text-xl"></i>
                    </button>

                    <a href="<?= BASE_URL ?>" class="flex-shrink-0 group">
                        <?php if ($logo): ?>
                            <img src="<?= BASE_URL . '/' . $logo ?>" alt="<?= htmlspecialchars($site_name) ?>" class="h-8 md:h-10 w-auto object-contain transition-transform group-hover:scale-105">
                        <?php else: ?>
                            <h1 class="text-2xl font-black text-slate-900 tracking-tighter uppercase">
                                NEWS<span class="text-indigo-600">PORTAL</span><span class="text-red-500 text-3xl leading-none">.</span>
                            </h1>
                        <?php endif; ?>
                    </a>
                </div>

                <nav class="hidden md:flex gap-1 items-center font-semibold text-sm text-slate-600">
                    <a href="<?= BASE_URL ?>" class="px-3 py-2 rounded-md hover:text-indigo-600 hover:bg-indigo-50 transition <?= $current_slug == '' ? 'text-indigo-600 bg-indigo-50' : '' ?>">Home</a>

                    <?php foreach ($menu_cats as $cat):
                        $child_slugs = array_column($cat['children'] ?? [], 'slug');
                        $isActive = ($current_slug === $cat['slug'] || in_array($current_slug, $child_slugs, true));
                        $hasChildren = !empty($cat['children']);
                    ?>
                        <?php if ($hasChildren): ?>
                            <div class="relative" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false">
                                <a href="<?= category_url($cat['slug']) ?>"
                                    class="inline-flex items-center gap-1 px-3 py-2 rounded-md uppercase text-[11px] tracking-wide transition hover:text-indigo-600 hover:bg-indigo-50 <?= $isActive ? 'text-indigo-600 bg-indigo-50' : '' ?>"
                                    @focus="open = true"
                                    aria-haspopup="true"
                                    :aria-expanded="open.toString()">
                                    <?= htmlspecialchars($cat['name']) ?>
                                    <i class="fa-solid fa-chevron-down text-[9px] opacity-60"></i>
                                </a>
                                <div x-show="open" x-cloak x-transition.opacity
                                    class="absolute left-0 top-full pt-1 min-w-[200px] z-50">
                                    <div class="bg-white border border-slate-100 rounded-xl shadow-lg py-2">
                                        <a href="<?= category_url($cat['slug']) ?>"
                                            class="block px-4 py-2 text-xs font-bold uppercase tracking-wide text-slate-700 hover:bg-indigo-50 hover:text-indigo-600 <?= $current_slug === $cat['slug'] ? 'bg-indigo-50 text-indigo-600' : '' ?>">
                                            All <?= htmlspecialchars($cat['name']) ?>
                                        </a>
                                        <div class="my-1 border-t border-slate-100"></div>
                                        <?php foreach ($cat['children'] as $child): ?>
                                            <a href="<?= category_url($child['slug']) ?>"
                                                class="block px-4 py-2 text-xs font-semibold uppercase tracking-wide text-slate-600 hover:bg-indigo-50 hover:text-indigo-600 <?= $current_slug === $child['slug'] ? 'bg-indigo-50 text-indigo-600' : '' ?>">
                                                <?= htmlspecialchars($child['name']) ?>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <a href="<?= category_url($cat['slug']) ?>"
                                class="px-3 py-2 rounded-md uppercase text-[11px] tracking-wide transition hover:text-indigo-600 hover:bg-indigo-50 <?= $isActive ? 'text-indigo-600 bg-indigo-50' : '' ?>">
                                <?= htmlspecialchars($cat['name']) ?>
                            </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </nav>

                <div class="flex items-center gap-2 md:gap-4">
                    <a href="<?= BASE_URL ?>/contact-us" class="relative group flex items-center">
                        <span class="absolute inset-0 rounded-full bg-amber-400 animate-ping opacity-75 group-hover:hidden"></span>
                        <div class="relative bg-amber-500 text-white px-3 py-1.5 md:px-4 md:py-2 rounded-full flex items-center gap-1.5 shadow-lg shadow-amber-200 transition-transform active:scale-95">
                            <i class="fa-solid fa-bullhorn text-[10px] md:text-xs"></i>
                            <span class="text-[10px] md:text-xs font-black uppercase tracking-tighter">Ads</span>
                        </div>
                    </a>

                    <button @click="searchOpen = !searchOpen" class="p-2 text-slate-600 hover:text-indigo-600 hover:bg-indigo-50 rounded-full transition">
                        <i class="fa-solid" :class="searchOpen ? 'fa-xmark' : 'fa-magnifying-glass'"></i>
                    </button>

                    <a href="#newsletter" class="hidden sm:inline-flex items-center gap-2 bg-slate-900 hover:bg-indigo-600 text-white text-xs font-bold px-4 py-2 rounded-full transition-colors shadow-lg shadow-indigo-500/20">
                        Subscribe <i class="fa-regular fa-envelope"></i>
                    </a>
                </div>
            </div>
        </div>

        <div x-show="searchOpen" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-2"
            @click.outside="searchOpen = false"
            class="absolute top-full left-0 w-full bg-white border-t border-slate-100 shadow-lg p-4 z-30" x-cloak>
            <div class="container mx-auto max-w-3xl">
                <form action="<?= BASE_URL ?>/search" method="GET" class="relative group">
                    <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-indigo-500 transition-colors"></i>
                    <input type="text" name="q" placeholder="Type keywords and hit enter..." autofocus
                        class="w-full pl-12 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:bg-white transition-all shadow-inner text-sm font-medium">
                </form>
            </div>
        </div>

        <div class="relative z-50 md:hidden" x-show="mobileMenu" x-cloak>
            <div x-show="mobileMenu" x-transition.opacity @click="mobileMenu = false" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm"></div>

            <div x-show="mobileMenu" x-transition:enter="transition ease-in-out duration-300 transform" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transition ease-in-out duration-300 transform" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full"
                class="fixed inset-y-0 left-0 w-[280px] bg-white shadow-2xl flex flex-col h-full">

                <div class="px-6 py-5 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                    <?php if ($logo): ?>
                        <img src="<?= BASE_URL . '/' . $logo ?>" class="h-8 object-contain">
                    <?php else: ?>
                        <span class="font-bold text-lg text-slate-800 tracking-tighter">MENU</span>
                    <?php endif; ?>
                    <button @click="mobileMenu = false" class="h-8 w-8 flex items-center justify-center rounded-full bg-white text-slate-500 hover:text-red-500 hover:bg-red-50 transition border border-slate-200 shadow-sm">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto sidebar-scroll p-4">
                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-3 px-2">Sections</h3>
                    <nav class="space-y-1">
                        <a href="<?= BASE_URL ?>" class="flex items-center px-3 py-2.5 rounded-lg text-sm font-medium text-slate-700 hover:bg-indigo-50 hover:text-indigo-600 transition <?= $current_slug == '' ? 'bg-indigo-50 text-indigo-600' : '' ?>">
                            <i class="fa-solid fa-house w-6 text-center text-slate-400"></i> Home
                        </a>
                        <?php foreach ($menu_cats as $cat):
                            $hasChildren = !empty($cat['children']);
                            $child_slugs = array_column($cat['children'] ?? [], 'slug');
                            $isActive = ($current_slug === $cat['slug'] || in_array($current_slug, $child_slugs, true));
                        ?>
                            <?php if ($hasChildren): ?>
                                <div x-data="{ open: <?= $isActive ? 'true' : 'false' ?> }" class="rounded-lg">
                                    <div class="flex items-center">
                                        <a href="<?= category_url($cat['slug']) ?>" class="flex-1 flex items-center px-3 py-2.5 rounded-lg text-sm font-medium transition <?= $current_slug === $cat['slug'] ? 'bg-indigo-50 text-indigo-600' : 'text-slate-700 hover:bg-indigo-50 hover:text-indigo-600' ?>">
                                            <i class="fa-solid fa-folder w-6 text-center text-slate-300 text-xs"></i>
                                            <?= htmlspecialchars($cat['name']) ?>
                                        </a>
                                        <button type="button" @click="open = !open" class="p-2 mr-1 text-slate-400 hover:text-indigo-600" aria-label="Toggle submenu">
                                            <i class="fa-solid fa-chevron-down text-xs transition-transform" :class="open ? 'rotate-180' : ''"></i>
                                        </button>
                                    </div>
                                    <div x-show="open" x-cloak class="ml-4 pl-2 border-l border-slate-100 space-y-0.5 pb-1">
                                        <?php foreach ($cat['children'] as $child): ?>
                                            <a href="<?= category_url($child['slug']) ?>" class="flex items-center px-3 py-2 rounded-lg text-sm font-medium transition <?= $current_slug === $child['slug'] ? 'bg-indigo-50 text-indigo-600' : 'text-slate-600 hover:bg-indigo-50 hover:text-indigo-600' ?>">
                                                <i class="fa-solid fa-angle-right w-6 text-center text-slate-300 text-xs"></i>
                                                <?= htmlspecialchars($child['name']) ?>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <a href="<?= category_url($cat['slug']) ?>" class="flex items-center px-3 py-2.5 rounded-lg text-sm font-medium transition <?= $isActive ? 'bg-indigo-50 text-indigo-600' : 'text-slate-700 hover:bg-indigo-50 hover:text-indigo-600' ?>">
                                    <i class="fa-solid fa-angle-right w-6 text-center text-slate-300 text-xs"></i>
                                    <?= htmlspecialchars($cat['name']) ?>
                                </a>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </nav>

                    <div class="my-6 border-t border-slate-100"></div>

                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-3 px-2">Pages</h3>
                    <nav class="space-y-1">
                        <a href="<?= BASE_URL ?>/about-us" class="block px-3 py-2 text-sm text-slate-600 hover:text-indigo-600 transition">About Us</a>
                        <a href="<?= BASE_URL ?>/e-news" class="block px-3 py-2 text-sm text-slate-600 hover:text-indigo-600 transition">E-News PDF</a>
                        <a href="<?= BASE_URL ?>/get-portal" class="block px-3 py-2 text-sm text-slate-600 hover:text-indigo-600 transition">Get This Portal</a>
                        <a href="<?= BASE_URL ?>/contact-us" class="block px-3 py-2 text-sm text-slate-600 hover:text-indigo-600 transition">Contact Us</a>
                        <a href="<?= BASE_URL ?>/privacy-policy" class="block px-3 py-2 text-sm text-slate-600 hover:text-indigo-600 transition">Privacy Policy</a>
                        <a href="<?= BASE_URL ?>/terms" class="block px-3 py-2 text-sm text-slate-600 hover:text-indigo-600 transition">Terms & Conditions</a>
                    </nav>
                </div>
            </div>
        </div>
    </header>

    <?php if (!empty($breaking_news)): ?>
        <div class="bg-white border-b border-slate-200 relative z-30">
            <div class="container mx-auto flex items-stretch">
                <div class="bg-red-600 text-white px-4 py-2 flex items-center justify-center font-bold text-[10px] md:text-xs uppercase tracking-wider shadow-md z-10 flex-shrink-0 relative">
                    <span class="absolute right-0 top-1/2 -translate-y-1/2 translate-x-1/2 w-3 h-3 bg-red-600 rotate-45 hidden md:block"></span>
                    <i class="fa-solid fa-bolt mr-2 animate-pulse"></i> Breaking
                </div>

                <div class="flex-1 overflow-hidden relative flex items-center py-2 bg-slate-50">
                    <div class="animate-marquee absolute whitespace-nowrap pl-4 w-full">
                        <?php foreach ($breaking_news as $news): ?>
                            <span class="inline-flex items-center mx-6 text-sm font-medium text-slate-700">
                                <i class="fa-solid fa-circle text-[4px] text-red-500 mr-3"></i>
                                <?php if ($news['link']): ?>
                                    <a href="<?= $news['link'] ?>" class="hover:text-red-600 transition"><?= htmlspecialchars($news['title']) ?></a>
                                <?php else: ?>
                                    <?= htmlspecialchars($news['title']) ?>
                                <?php endif; ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>