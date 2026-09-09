<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/session_check.php';


$config_path = __DIR__ . '/../../config/constants.php';
$db_path = __DIR__ . '/../../config/database.php';
$helper_path = __DIR__ . '/../../helpers/common_functions.php';

if (file_exists($config_path)) require_once $config_path;
if (file_exists($db_path)) require_once $db_path;
if (file_exists($helper_path)) require_once $helper_path;


checkAdminSession();


$current_page = basename($_SERVER['PHP_SELF']);


$header_user = [
    'name' => $_SESSION['admin_name'] ?? 'Admin',
    'avatar' => '',
    'role' => $_SESSION['role'] ?? 'admin'
];

if (isset($_SESSION['admin_id']) && isset($conn)) {
    try {
        $stmt = $conn->prepare("SELECT name, avatar, role FROM users WHERE id = :id");
        $stmt->execute([':id' => $_SESSION['admin_id']]);
        $fetched_user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($fetched_user) {
            $header_user = $fetched_user;
        }
    } catch (PDOException $e) {
    }
}


$site_name = function_exists('get_setting') ? get_setting('site_name', 'Admin Panel') : 'Admin Panel';
$favicon_path = function_exists('get_setting') ? get_setting('favicon_path') : '';
?>
<!DOCTYPE html>
<html lang="en" class="<?= (isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'dark') ? 'dark' : '' ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($site_name) ?> - Dashboard</title>


    <?php if ($favicon_path && file_exists(__DIR__ . '/../../' . $favicon_path)): ?>
        <link rel="shortcut icon" href="<?= BASE_URL . '/' . $favicon_path ?>" type="image/x-icon">
    <?php else: ?>
        <link rel="shortcut icon" href="data:,">
    <?php endif; ?>
    <link rel="stylesheet" href="../assets/src/output.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">


    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>


    <style>
        [x-cloak] {
            display: none !important;
        }

        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }

        .no-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }


        .custom-scrollbar::-webkit-scrollbar {
            width: 5px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background-color: #334155;
            border-radius: 20px;
        }

        .custom-scrollbar {
            scrollbar-width: thin;
            scrollbar-color: #334155 transparent;
        }

        .loader-spinner {
            border: 4px solid rgba(255, 255, 255, 0.1);
            border-left-color: #4f46e5;
            border-radius: 50%;
            width: 40px;
            height: 40px;
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


        .dark .jodit-wysiwyg {
            background-color: #0f172a;
            color: #e2e8f0;
        }

        .dark .jodit-container {
            border-color: #334155;
        }

        .dark .jodit-toolbar__box,
        .dark .jodit-status-bar {
            background-color: #1e293b !important;
            border-color: #334155 !important;
        }

        .dark .jodit-toolbar-button__icon svg {
            fill: #94a3b8;
        }

        .dark .jodit-popup {
            background-color: #1e293b;
            border-color: #334155;
            color: #fff;
        }
    </style>


    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('mainLayout', () => ({
                sidebarOpen: false,
                isDark: localStorage.getItem('theme') === 'dark',
                isLoading: true,

                init() {
                    if (this.isDark) document.documentElement.classList.add('dark');

                    window.addEventListener('load', () => {
                        setTimeout(() => {
                            this.isLoading = false;
                        }, 400);
                    });
                },

                toggleTheme() {
                    this.isDark = !this.isDark;
                    localStorage.setItem('theme', this.isDark ? 'dark' : 'light');
                    if (this.isDark) {
                        document.documentElement.classList.add('dark');
                    } else {
                        document.documentElement.classList.remove('dark');
                    }
                }
            }));
        });
    </script>
</head>

<body class="bg-gray-100 text-slate-800 dark:bg-slate-900 dark:text-slate-200 font-sans antialiased transition-colors duration-300"
    x-data="mainLayout">

    <div x-show="isLoading"
        x-transition:leave="transition ease-in duration-500"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-[9999] flex flex-col items-center justify-center bg-white dark:bg-slate-900">
        <div class="loader-spinner mb-4"></div>
        <p class="text-xs font-bold tracking-widest text-slate-400 uppercase animate-pulse">Loading...</p>
    </div>

    <div class="flex h-screen overflow-hidden">

        <div x-show="sidebarOpen" @click="sidebarOpen = false" x-transition.opacity
            class="fixed inset-0 z-20 bg-black/50 lg:hidden" x-cloak></div>

        <?php include 'sidebar.php'; ?>

        <div class="relative flex flex-1 flex-col overflow-y-auto overflow-x-hidden">

            <header class="sticky top-0 z-10 flex w-full items-center justify-between bg-white px-6 py-3 shadow-sm dark:bg-slate-800 transition-colors duration-300">

                <div class="flex items-center gap-4">
                    <button @click="sidebarOpen = !sidebarOpen" class="text-slate-500 hover:text-primary focus:outline-none lg:hidden">
                        <i class="fa-solid fa-bars text-xl"></i>
                    </button>

                    <a href="<?= defined('BASE_URL') ? BASE_URL : '#' ?>" target="_blank" class="hidden sm:flex items-center gap-2 text-sm text-slate-500 hover:text-primary transition group">
                        <i class="fa-solid fa-arrow-up-right-from-square group-hover:-translate-y-0.5 transition-transform"></i>
                        Visit Site
                    </a>
                </div>

                <div class="flex items-center gap-5">

                    <button @click="toggleTheme()"
                        class="relative flex items-center gap-2 px-3 py-1.5 rounded-full border shadow-sm transition-all duration-300 group overflow-hidden"
                        :class="isDark ? 'bg-slate-800 border-slate-700 text-slate-300 hover:bg-slate-700' : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50 hover:border-slate-300'">

                        <div class="absolute inset-0 opacity-0 group-hover:opacity-10 transition-opacity bg-current"></div>

                        <div class="relative w-4 h-4 flex items-center justify-center">

                            <i class="fa-solid fa-sun absolute text-amber-500 transition-all duration-500 transform"
                                :class="isDark ? 'rotate-90 opacity-0 scale-50' : 'rotate-0 opacity-100 scale-100'"></i>

                            <i class="fa-solid fa-moon absolute text-indigo-400 transition-all duration-500 transform"
                                :class="isDark ? 'rotate-0 opacity-100 scale-100' : '-rotate-90 opacity-0 scale-50'"></i>
                        </div>

                        <span class="text-xs font-bold tracking-wide uppercase transition-colors duration-300"
                            x-text="isDark ? 'Dark' : 'Light'">
                        </span>
                    </button>

                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open" @click.outside="open = false" class="flex items-center gap-3 focus:outline-none group">

                            <div class="hidden md:block text-right leading-tight">
                                <div class="text-sm font-bold text-slate-700 dark:text-slate-200">
                                    <?= htmlspecialchars($header_user['name']) ?>
                                </div>
                                <div class="text-[10px] font-medium text-slate-400 uppercase tracking-wider">
                                    <?= htmlspecialchars($header_user['role']) ?>
                                </div>
                            </div>

                            <div class="relative">
                                <?php

                                $avatar_rel = !empty($header_user['avatar']) ? '../../' . $header_user['avatar'] : '';
                                $avatar_url = !empty($header_user['avatar']) ? '../' . $header_user['avatar'] : '';

                                if (!empty($header_user['avatar']) && file_exists($avatar_rel)):
                                ?>
                                    <img src="<?= $avatar_url ?>" alt="User" class="h-10 w-10 rounded-full object-cover border-2 border-slate-100 dark:border-slate-700 shadow-sm group-hover:border-indigo-500 transition-colors">
                                <?php else: ?>
                                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($header_user['name']) ?>&background=4f46e5&color=fff&size=128" alt="User" class="h-10 w-10 rounded-full border-2 border-slate-100 dark:border-slate-700 shadow-sm group-hover:border-indigo-500 transition-colors">
                                <?php endif; ?>

                                <span class="absolute bottom-0 right-0 block h-2.5 w-2.5 rounded-full ring-2 ring-white dark:ring-slate-800 bg-green-500"></span>
                            </div>

                            <i class="fa-solid fa-chevron-down text-[10px] text-slate-400 transition-transform duration-200" :class="open ? 'rotate-180' : ''"></i>
                        </button>

                        <div x-show="open"
                            x-transition:enter="transition ease-out duration-100"
                            x-transition:enter-start="opacity-0 scale-95"
                            x-transition:enter-end="opacity-100 scale-100"
                            class="absolute right-0 mt-3 w-48 rounded-lg bg-white py-1 shadow-xl ring-1 ring-black ring-opacity-5 dark:bg-slate-800 border border-slate-100 dark:border-slate-700 z-50 overflow-hidden"
                            x-cloak>

                            <div class="px-4 py-3 border-b border-slate-100 dark:border-slate-700 md:hidden bg-slate-50 dark:bg-slate-900/50">
                                <p class="text-sm font-bold text-slate-800 dark:text-white"><?= htmlspecialchars($header_user['name']) ?></p>
                                <p class="text-xs text-slate-500 uppercase"><?= htmlspecialchars($header_user['role']) ?></p>
                            </div>

                            <a href="profile.php" class="flex items-center px-4 py-2.5 text-sm text-slate-700 hover:bg-indigo-50 hover:text-indigo-600 dark:text-slate-300 dark:hover:bg-slate-700 transition-colors">
                                <i class="fa-regular fa-id-card mr-3 text-slate-400 w-4"></i> Profile
                            </a>
                            <a href="settings.php" class="flex items-center px-4 py-2.5 text-sm text-slate-700 hover:bg-indigo-50 hover:text-indigo-600 dark:text-slate-300 dark:hover:bg-slate-700 transition-colors">
                                <i class="fa-solid fa-sliders mr-3 text-slate-400 w-4"></i> Settings
                            </a>

                            <hr class="my-1 border-slate-100 dark:border-slate-700">

                            <button @click="$dispatch('confirm-logout')" class="w-full text-left flex items-center px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                                <i class="fa-solid fa-arrow-right-from-bracket mr-3 w-4"></i> Logout
                            </button>
                        </div>
                    </div>
                </div>
            </header>

            <main class="w-full flex-grow p-6">