</main>

<footer class="mt-auto w-full border-t border-slate-200 bg-white p-6 text-center text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400 transition-colors">
    &copy; <?= date('Y') ?> <strong><?= function_exists('get_setting') ? htmlspecialchars(get_setting('site_name', 'News Portal')) : 'News Portal' ?></strong>. All rights reserved.
    <span class="mx-2 text-slate-300 dark:text-slate-600">|</span>
    Developed by <a href="https://digitalcreatorss.com" target="_blank" rel="noopener" class="font-semibold text-indigo-600 hover:underline dark:text-indigo-400">Digital Creatorss</a>
</footer>
</div>
</div>

<div x-data="{ open: false, link: '' }"
    @confirm-delete.window="open = true; link = $event.detail.link"
    x-show="open"
    class="relative z-50" aria-labelledby="modal-title" role="dialog" aria-modal="true" x-cloak>

    <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm transition-opacity"></div>
    <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
            <div @click.outside="open = false" class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg dark:bg-slate-800">
                <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4 dark:bg-slate-800">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10 dark:bg-red-900/20">
                            <i class="fa-solid fa-trash-can text-red-600"></i>
                        </div>
                        <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left">
                            <h3 class="text-base font-semibold leading-6 text-slate-900 dark:text-white" id="modal-title">Delete Item</h3>
                            <div class="mt-2">
                                <p class="text-sm text-slate-500 dark:text-slate-400">Are you sure? This action cannot be undone.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6 dark:bg-slate-900">
                    <a :href="link" class="inline-flex w-full justify-center rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 sm:ml-3 sm:w-auto">Delete</a>
                    <button type="button" @click="open = false" class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto dark:bg-slate-700 dark:text-white dark:ring-slate-600 dark:hover:bg-slate-600">Cancel</button>
                </div>
            </div>
        </div>
    </div>
</div>


<div x-data="{ open: false }"
    @confirm-logout.window="open = true"
    x-show="open"
    class="relative z-50" aria-labelledby="logout-title" role="dialog" aria-modal="true" x-cloak>

    <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm transition-opacity"></div>
    <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
            <div @click.outside="open = false" class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-md dark:bg-slate-800">
                <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4 dark:bg-slate-800">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-amber-100 sm:mx-0 sm:h-10 sm:w-10 dark:bg-amber-900/20">
                            <i class="fa-solid fa-arrow-right-from-bracket text-amber-600"></i>
                        </div>
                        <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left">
                            <h3 class="text-lg font-semibold leading-6 text-slate-900 dark:text-white" id="logout-title">Log Out?</h3>
                            <p class="text-sm text-slate-500 dark:text-slate-400 mt-2">Are you sure you want to Log out now?</p>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6 dark:bg-slate-900">
                    <a href="logout.php" class="inline-flex w-full justify-center rounded-md bg-slate-900 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-800 sm:ml-3 sm:w-auto dark:bg-indigo-600 dark:hover:bg-indigo-500">Yes</a>
                    <button type="button" @click="open = false" class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto dark:bg-slate-700 dark:text-white dark:ring-slate-600 dark:hover:bg-slate-600">No</button>
                </div>
            </div>
        </div>
    </div>
</div>


<div x-data="{ show: false, message: '', type: 'success' }"
    @notify.window="show = true; message = $event.detail.message; type = $event.detail.type; setTimeout(() => show = false, 5000)"
    x-show="show"
    x-transition:enter="transition cubic-bezier(0.16, 1, 0.3, 1) duration-500"
    x-transition:enter-start="opacity-0 translate-y-12 scale-95"
    x-transition:enter-end="opacity-100 translate-y-0 scale-100"
    x-transition:leave="transition ease-in duration-300"
    x-transition:leave-start="opacity-100 scale-100"
    x-transition:leave-end="opacity-0 scale-90 translate-y-4"
    class="fixed bottom-6 right-6 z-[100] flex w-full max-w-sm items-start gap-4 rounded-lg bg-white p-4 shadow-2xl border border-slate-100 dark:bg-slate-800 dark:border-slate-700 dark:shadow-black/50"
    :class="type === 'success' ? 'border-l-[6px] border-l-emerald-500' : 'border-l-[6px] border-l-rose-500'"
    role="alert" x-cloak>


    <div :class="type === 'success' ? 'text-emerald-500 bg-emerald-50 dark:bg-emerald-900/30' : 'text-rose-500 bg-rose-50 dark:bg-rose-900/30'"
        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full">
        <i class="fa-solid text-lg" :class="type === 'success' ? 'fa-circle-check' : 'fa-circle-xmark'"></i>
    </div>


    <div class="flex-1 pt-0.5">
        <h3 class="font-bold text-sm"
            :class="type === 'success' ? 'text-emerald-800 dark:text-emerald-400' : 'text-rose-800 dark:text-rose-400'"
            x-text="type === 'success' ? 'Success!' : 'Error!'">
        </h3>
        <p class="mt-1 text-sm text-slate-600 dark:text-slate-300 leading-relaxed" x-text="message"></p>
    </div>

    <button @click="show = false" class="shrink-0 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
        <i class="fa-solid fa-xmark text-lg"></i>
    </button>
</div>


<script>
    document.addEventListener('alpine:init', () => {
        <?php if (isset($_SESSION['success'])): ?>
            setTimeout(() => {
                window.dispatchEvent(new CustomEvent('notify', {
                    detail: {
                        message: '<?= addslashes($_SESSION['success']); ?>',
                        type: 'success'
                    }
                }));
            }, 300);
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            setTimeout(() => {
                window.dispatchEvent(new CustomEvent('notify', {
                    detail: {
                        message: '<?= addslashes($_SESSION['error']); ?>',
                        type: 'error'
                    }
                }));
            }, 300);
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
    });
</script>
</body>

</html>