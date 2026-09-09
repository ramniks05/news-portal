<?php

if (!function_exists('is_active')) {
    function is_active($target_pages)
    {
        global $current_page;

        if (!is_array($target_pages)) {
            $target_pages = [$target_pages];
        }

        if (in_array($current_page, $target_pages)) {
            return 'bg-indigo-600 text-white shadow-md shadow-indigo-500/20 border-l-0';
        }

        return 'text-slate-400 hover:bg-slate-800 hover:text-white';
    }
}


$post_related_pages = ['add-post.php', 'manage-posts.php', 'edit-post.php', 'tags.php'];
$is_post_section = in_array($current_page, $post_related_pages);
?>


<aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
    class="fixed inset-y-0 left-0 z-30 flex w-64 flex-col bg-slate-900 text-white transition-transform duration-300 ease-in-out lg:static lg:translate-x-0 border-r border-slate-800">

    <div class="flex h-16 items-center justify-center border-b border-slate-800 bg-slate-950 shadow-sm flex-shrink-0">
        <a href="dashboard.php" class="flex items-center gap-2 group">
            <h1 class="text-lg font-bold tracking-wide text-slate-100">
                ADMIN <span class="text-indigo-500">PANEL</span>
            </h1>
        </a>
    </div>

    <div class="flex-1 overflow-y-auto py-6 custom-scrollbar">
        <nav class="space-y-1 px-3">

            <a href="dashboard.php" class="group flex items-center rounded-md px-3 py-2.5 text-sm font-medium transition-all duration-200 <?= is_active('dashboard.php') ?>">
                <i class="fa-solid fa-gauge-high w-6 text-center text-lg opacity-80 group-hover:opacity-100"></i>
                <span class="ml-3">Dashboard</span>
            </a>

            <div class="mt-6 mb-2 px-3 text-xs font-semibold uppercase tracking-wider text-slate-500">
                Editorial
            </div>

            <div x-data="{ open: <?= $is_post_section ? 'true' : 'false' ?> }">
                <button @click="open = !open" type="button"
                    class="group flex w-full items-center justify-between rounded-md px-3 py-2.5 text-left text-sm font-medium text-slate-400 hover:bg-slate-800 hover:text-white transition-all">
                    <div class="flex items-center">
                        <i class="fa-solid fa-newspaper w-6 text-center text-lg opacity-80"></i>
                        <span class="ml-3">News Posts</span>
                    </div>
                    <i class="fa-solid fa-chevron-right text-xs transition-transform duration-200" :class="open ? 'rotate-90' : ''"></i>
                </button>

                <div x-show="open"
                    x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    class="mt-1 space-y-1 px-2" x-cloak>

                    <a href="add-post.php" class="group flex w-full items-center rounded-md pl-10 pr-2 py-2 text-sm font-medium transition-colors <?= is_active('add-post.php') ?>">
                        <span class="w-1.5 h-1.5 rounded-full bg-current mr-3 opacity-50"></span>
                        Add New
                    </a>

                    <a href="manage-posts.php" class="group flex w-full items-center rounded-md pl-10 pr-2 py-2 text-sm font-medium transition-colors <?= is_active(['manage-posts.php', 'edit-post.php']) ?>">
                        <span class="w-1.5 h-1.5 rounded-full bg-current mr-3 opacity-50"></span>
                        All Posts
                    </a>
                </div>
            </div>

            <a href="categories.php" class="group flex items-center rounded-md px-3 py-2.5 text-sm font-medium transition-all duration-200 <?= is_active('categories.php') ?>">
                <i class="fa-solid fa-layer-group w-6 text-center text-lg opacity-80 group-hover:opacity-100"></i>
                <span class="ml-3">Categories</span>
            </a>

            <a href="breaking-news.php" class="group flex items-center rounded-md px-3 py-2.5 text-sm font-medium transition-all duration-200 <?= is_active('breaking-news.php') ?>">
                <i class="fa-solid fa-bolt w-6 text-center text-lg opacity-80 group-hover:opacity-100"></i>
                <span class="ml-3">Breaking Ticker</span>
            </a>

            <a href="comments.php" class="group flex items-center rounded-md px-3 py-2.5 text-sm font-medium transition-all duration-200 <?= is_active('comments.php') ?>">
                <i class="fa-regular fa-comments w-6 text-center text-lg opacity-80 group-hover:opacity-100"></i>
                <span class="ml-3">Comments</span>
            </a>

            <a href="contact-messages.php" class="group flex items-center rounded-md px-3 py-2.5 text-sm font-medium transition-all duration-200 <?= is_active('contact-messages.php') ?>">
                <i class="fa-regular fa-envelope w-6 text-center text-lg opacity-80 group-hover:opacity-100"></i>
                <span class="ml-3">Contact Inbox</span>
            </a>

            <div class="mt-6 mb-2 px-3 text-xs font-semibold uppercase tracking-wider text-slate-500">
                Management
            </div>
            <a href="featured-management.php" class="group flex items-center rounded-md px-3 py-2.5 text-sm font-medium transition-all duration-200 <?= is_active('featured-management.php') ?>">
                <i class="fa-solid fa-star w-6 text-center text-lg opacity-80 group-hover:opacity-100"></i>
                <span class="ml-3">Featured & Slider</span>
            </a>

            <a href="ads-manager.php" class="group flex items-center rounded-md px-3 py-2.5 text-sm font-medium transition-all duration-200 <?= is_active('ads-manager.php') ?>">
                <i class="fa-solid fa-rectangle-ad w-6 text-center text-lg opacity-80 group-hover:opacity-100"></i>
                <span class="ml-3">Ad Manager</span>
            </a>

            <a href="subscribers.php" class="group flex items-center rounded-md px-3 py-2.5 text-sm font-medium transition-all duration-200 <?= is_active('subscribers.php') ?>">
                <i class="fa-solid fa-envelope-open-text w-6 text-center text-lg opacity-80 group-hover:opacity-100"></i>
                <span class="ml-3">Newsletter</span>
            </a>

            <div class="mt-6 mb-2 px-3 text-xs font-semibold uppercase tracking-wider text-slate-500">
                Static Pages
            </div>

            <a href="edit-page.php?slug=about-us" class="group flex items-center rounded-md px-3 py-2.5 text-sm font-medium transition-all duration-200 text-slate-400 hover:bg-slate-800 hover:text-white">
                <i class="fa-solid fa-address-card w-6 text-center text-lg opacity-80"></i>
                <span class="ml-3">About Us</span>
            </a>

            <a href="edit-page.php?slug=privacy-policy" class="group flex items-center rounded-md px-3 py-2.5 text-sm font-medium transition-all duration-200 text-slate-400 hover:bg-slate-800 hover:text-white">
                <i class="fa-solid fa-shield-halved w-6 text-center text-lg opacity-80"></i>
                <span class="ml-3">Privacy Policy</span>
            </a>
            <a href="edit-page.php?slug=terms-conditions" class="group flex items-center rounded-md px-3 py-2 text-sm font-medium transition-all duration-200 text-slate-400 hover:bg-slate-800 hover:text-white">
                <i class="fa-solid fa-file-contract w-6 text-center text-lg opacity-80"></i>
                <span class="ml-3">Terms & Conditions</span>
            </a>

            <div class="mt-6 mb-2 px-3 text-xs font-semibold uppercase tracking-wider text-slate-500">
                System
            </div>

            <a href="settings.php" class="group flex items-center rounded-md px-3 py-2.5 text-sm font-medium transition-all duration-200 <?= is_active('settings.php') ?>">
                <i class="fa-solid fa-sliders w-6 text-center text-lg opacity-80 group-hover:opacity-100"></i>
                <span class="ml-3">Site Settings</span>
            </a>

        </nav>
    </div>

    <div class="border-t border-slate-800 bg-slate-950 p-4 flex-shrink-0">
        <button type="button" @click="$dispatch('confirm-logout')"
            class="group flex w-full items-center justify-center rounded-md border border-slate-700 bg-slate-900 px-4 py-2 text-sm font-medium text-slate-300 transition-colors hover:bg-red-900/30 hover:text-red-400 hover:border-red-900/50 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-opacity-50">
            <i class="fa-solid fa-arrow-right-from-bracket mr-2"></i>
            Logout
        </button>
    </div>
</aside>