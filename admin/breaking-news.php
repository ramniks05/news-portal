<?php
require_once 'includes/header.php';
require_once '../config/database.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$stmt = $conn->query("SELECT * FROM breaking_news ORDER BY id DESC");
$tickers = $stmt->fetchAll();
?>

<div x-data="tickerManager()" class="pb-20">

    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
            <h2 class="text-xl md:text-2xl font-bold text-slate-800 dark:text-white">
                <i class="fa-solid fa-newspaper mr-2 text-red-600"></i>Breaking News
            </h2>
            <p class="text-xs md:text-sm text-slate-500 dark:text-slate-400 mt-1">Manage scrolling headlines.</p>
        </div>
        <button @click="openModal('add')"
            class="w-full md:w-auto bg-red-600 hover:bg-red-700 text-white font-bold py-2.5 px-4 rounded-lg shadow-sm flex items-center justify-center transition-transform active:scale-95">
            <i class="fa-solid fa-plus mr-2"></i> Add Flash News
        </button>
    </div>

    <div class="mb-6 bg-slate-900 text-white rounded-lg overflow-hidden shadow-md flex items-center relative h-10 border border-slate-700 mx-1 md:mx-0">
        <div class="bg-red-600 px-3 h-full flex items-center font-bold text-[10px] md:text-xs uppercase z-10 shrink-0 tracking-wider">
            Preview
        </div>
        <div class="flex-1 overflow-hidden relative pl-2">
            <div class="whitespace-nowrap animate-marquee text-xs md:text-sm font-medium pt-1">
                <?php if (count($tickers) > 0): ?>
                    <?php foreach ($tickers as $item): ?>
                        <?php if ($item['status']): ?>
                            <span class="inline-block mx-4">• <?= htmlspecialchars($item['title']) ?></span>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <span class="inline-block mx-4 opacity-50">No active news items to display...</span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-lg shadow border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-slate-600 dark:text-slate-400 whitespace-nowrap">
                <thead class="text-xs text-slate-700 uppercase bg-slate-50 dark:bg-slate-700 dark:text-slate-200">
                    <tr>
                        <th class="px-4 py-3 min-w-[200px]">Headline Text</th>
                        <th class="px-4 py-3">Link</th>
                        <th class="px-4 py-3 text-center">Status</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                    <?php foreach ($tickers as $row): ?>
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition">

                            <td class="px-4 py-3 font-medium text-slate-900 dark:text-white">
                                <div class="truncate max-w-[250px] md:max-w-md" title="<?= htmlspecialchars($row['title']) ?>">
                                    <?= htmlspecialchars($row['title']) ?>
                                </div>
                            </td>

                            <td class="px-4 py-3">
                                <?php if ($row['link']): ?>
                                    <a href="<?= htmlspecialchars($row['link']) ?>" target="_blank" class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400">
                                        <i class="fa-solid fa-link"></i> Link
                                    </a>
                                <?php else: ?>
                                    <span class="text-slate-400 text-xs">-</span>
                                <?php endif; ?>
                            </td>

                            <td class="px-4 py-3 text-center">
                                <form action="handlers/breaking_handler.php" method="POST" class="inline-block">
                                    <input type="hidden" name="action" value="toggle_status">
                                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                    <input type="hidden" name="current_status" value="<?= $row['status'] ?>">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                                    <button type="submit" class="relative inline-flex items-center cursor-pointer h-6 w-11 rounded-full transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 <?= $row['status'] ? 'bg-green-500' : 'bg-slate-200 dark:bg-slate-600' ?>">
                                        <span class="sr-only">Use setting</span>
                                        <span aria-hidden="true" class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out <?= $row['status'] ? 'translate-x-6' : 'translate-x-1' ?>"></span>
                                    </button>
                                </form>
                            </td>

                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button @click="editTicker(<?= htmlspecialchars(json_encode($row)) ?>)"
                                        class="p-2 text-indigo-600 hover:bg-indigo-50 rounded-full transition dark:hover:bg-indigo-900/30">
                                        <i class="fa-solid fa-pen"></i>
                                    </button>
                                    <button @click="$dispatch('confirm-delete', { link: 'handlers/breaking_handler.php?action=delete&id=<?= $row['id'] ?>' })"
                                        class="p-2 text-red-500 hover:bg-red-50 rounded-full transition dark:hover:bg-red-900/30">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (empty($tickers)): ?>
                        <tr>
                            <td colspan="4" class="text-center py-10 text-slate-500 flex flex-col items-center justify-center">
                                <i class="fa-regular fa-folder-open text-3xl mb-2 opacity-50"></i>
                                <span class="text-sm">No breaking news found.</span>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div x-show="isModalOpen" x-cloak class="relative z-50" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div x-show="isModalOpen" x-transition.opacity class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm transition-opacity"></div>

        <div class="fixed inset-0 z-10 overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div x-show="isModalOpen" x-transition.scale.origin.bottom @click.outside="closeModal()"
                    class="relative transform overflow-hidden rounded-xl bg-white dark:bg-slate-800 text-left shadow-xl transition-all w-full sm:max-w-lg border border-slate-200 dark:border-slate-700 mb-0 sm:mb-8">

                    <form action="handlers/breaking_handler.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="id" x-model="formData.id">
                        <input type="hidden" name="action" x-model="mode === 'add' ? 'create' : 'update'">

                        <div class="bg-white dark:bg-slate-800 px-4 py-4 border-b border-slate-100 dark:border-slate-700 flex justify-between items-center">
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white" x-text="mode === 'add' ? 'Add Flash News' : 'Edit Headline'"></h3>
                            <button type="button" @click="closeModal()" class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-500 transition">
                                <i class="fa-solid fa-xmark text-lg"></i>
                            </button>
                        </div>

                        <div class="px-4 py-6 space-y-5">
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Headline Text <span class="text-red-500">*</span></label>
                                <textarea name="title" x-model="formData.title" required maxlength="100" rows="2"
                                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-red-500 focus:ring-1 focus:ring-red-500 dark:bg-slate-900 dark:border-slate-600 dark:text-white resize-none" placeholder="Enter the breaking news text here..."></textarea>
                                <p class="text-[10px] text-slate-500 mt-1 text-right">
                                    <span x-text="formData.title.length" :class="formData.title.length > 90 ? 'text-red-500 font-bold' : ''"></span>/100
                                </p>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Target Link <span class="text-xs font-normal text-slate-500">(Optional)</span></label>
                                <div class="relative">
                                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                        <i class="fa-solid fa-link text-slate-400 text-xs"></i>
                                    </div>
                                    <input type="url" name="link" x-model="formData.link"
                                        class="w-full rounded-lg border border-slate-300 pl-8 pr-3 py-2 text-sm focus:border-red-500 focus:ring-1 focus:ring-red-500 dark:bg-slate-900 dark:border-slate-600 dark:text-white" placeholder="https://example.com/news">
                                </div>
                            </div>

                            <div class="flex items-center gap-3 bg-slate-50 dark:bg-slate-700/30 p-3 rounded-lg border border-slate-100 dark:border-slate-700">
                                <input type="checkbox" name="status" value="1" x-model="formData.status" id="modalStatus" class="w-5 h-5 text-red-600 rounded border-slate-300 focus:ring-red-600">
                                <label for="modalStatus" class="text-sm font-medium text-slate-700 dark:text-slate-300 select-none cursor-pointer">
                                    Publish Immediately
                                </label>
                            </div>
                        </div>

                        <div class="bg-gray-50 dark:bg-slate-700/50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6 gap-2">
                            <button type="submit" class="w-full sm:w-auto inline-flex justify-center rounded-lg bg-red-600 px-4 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-red-500 transition">Save Item</button>
                            <button type="button" @click="closeModal()" class="mt-3 sm:mt-0 w-full sm:w-auto inline-flex justify-center rounded-lg bg-white px-4 py-2.5 text-sm font-bold text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 dark:bg-slate-800 dark:text-slate-300 dark:ring-slate-600 dark:hover:bg-slate-700 transition">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
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
        animation: marquee 25s linear infinite;
        will-change: transform;
    }

    .animate-marquee:hover {
        animation-play-state: paused;
    }
</style>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('tickerManager', () => ({
            isModalOpen: false,
            mode: 'add',
            formData: {
                id: '',
                title: '',
                link: '',
                status: true
            },

            openModal(mode) {
                this.mode = mode;
                if (mode === 'add') this.resetForm();
                this.isModalOpen = true;
            },
            closeModal() {
                this.isModalOpen = false;
            },
            resetForm() {
                this.formData = {
                    id: '',
                    title: '',
                    link: '',
                    status: true
                };
            },
            editTicker(item) {
                this.mode = 'edit';
                this.formData = {
                    id: item.id,
                    title: item.title,
                    link: item.link || '',
                    status: item.status == 1
                };
                this.isModalOpen = true;
            }
        }));
    });
</script>

<?php include 'includes/footer.php'; ?>