<?php
require_once 'includes/header.php';
require_once '../config/database.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$locations = [
    'header_top'     => 'Header (Top Leaderboard)',
    'sidebar_top'    => 'Sidebar (Top Widget)',
    'sidebar_bottom' => 'Sidebar (Bottom Widget)',
    'article_top'    => 'Article (Below Title)',
    'article_middle' => 'Article (Between Content)',
    'article_bottom' => 'Article (End of Post)',
    'footer_top'     => 'Footer (Wide Banner)'
];

$stmt = $conn->query("SELECT * FROM ads ORDER BY id DESC");
$all_ads = $stmt->fetchAll(PDO::FETCH_ASSOC);

$grouped_ads = [];
foreach ($all_ads as $ad) {
    $grouped_ads[$ad['location']][] = $ad;
}
?>

<div x-data="adManager()">
    <div class="mb-8 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-800 dark:text-white">Ads Manager</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400">Manage banner positions and sizes.</p>
        </div>
        <button @click="openModal('add')"
            class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2.5 px-5 rounded-lg shadow-lg flex items-center transition-transform hover:-translate-y-0.5">
            <i class="fa-solid fa-plus mr-2"></i> Create Ad
        </button>
    </div>
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">

        <?php foreach ($locations as $loc_key => $loc_label): ?>
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 flex flex-col h-full">
                <div class="bg-slate-50 dark:bg-slate-900/50 px-5 py-3 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center">
                    <div class="flex items-center gap-2">
                        <div class="w-2 h-2 rounded-full bg-indigo-500"></div>
                        <h3 class="font-bold text-slate-700 dark:text-slate-200 text-sm uppercase tracking-wide">
                            <?= $loc_label ?>
                        </h3>
                    </div>
                    <button @click="openModal('add', '<?= $loc_key ?>')" class="text-xs font-bold text-indigo-600 hover:text-indigo-800 hover:underline">
                        + Add Here
                    </button>
                </div>
                <div class="p-5 flex-1 space-y-4">
                    <?php if (isset($grouped_ads[$loc_key]) && count($grouped_ads[$loc_key]) > 0): ?>
                        <?php foreach ($grouped_ads[$loc_key] as $ad): ?>

                            <div class="group relative bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-600 rounded-lg p-3 hover:shadow-md transition-all duration-200 hover:border-indigo-300">

                                <div class="flex justify-between items-start mb-3">
                                    <div>
                                        <h4 class="font-bold text-slate-800 dark:text-white text-sm line-clamp-1">
                                            <?= htmlspecialchars($ad['name']) ?>
                                        </h4>
                                        <span class="text-[10px] uppercase font-bold tracking-wider px-1.5 py-0.5 rounded border mt-1 inline-block
                                    <?= $ad['type'] === 'image' ? 'bg-purple-50 text-purple-700 border-purple-200' : 'bg-amber-50 text-amber-700 border-amber-200' ?>">
                                            <i class="fa-solid <?= $ad['type'] === 'image' ? 'fa-image' : 'fa-code' ?> mr-1"></i> <?= $ad['type'] ?>
                                        </span>
                                    </div>

                                    <form action="handlers/ad_handler.php" method="POST">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="id" value="<?= $ad['id'] ?>">
                                        <input type="hidden" name="current_status" value="<?= $ad['status'] ?>">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                                        <button type="submit" class="relative inline-flex items-center cursor-pointer group/toggle" title="Click to Toggle">
                                            <div class="w-9 h-5 rounded-full peer-focus:outline-none transition-colors duration-200 
                                        <?= $ad['status'] == 1 ? 'bg-green-500' : 'bg-slate-300 dark:bg-slate-600' ?>">
                                            </div>
                                            <span class="absolute left-[2px] top-[2px] bg-white border border-gray-300 rounded-full h-4 w-4 transition-transform duration-200 shadow-sm 
                                        <?= $ad['status'] == 1 ? 'translate-x-full border-white' : 'translate-x-0' ?>">
                                            </span>
                                        </button>
                                    </form>
                                </div>
                                <div class="bg-slate-100 dark:bg-slate-900 rounded-md h-24 w-full flex items-center justify-center overflow-hidden border border-slate-100 dark:border-slate-700 relative">
                                    <?php if ($ad['type'] === 'image'): ?>
                                        <img src="../../<?= htmlspecialchars($ad['image_path']) ?>" class="max-h-full max-w-full object-contain">
                                    <?php else: ?>
                                        <i class="fa-solid fa-code text-3xl text-slate-300 dark:text-slate-600"></i>
                                        <span class="text-xs text-slate-400 absolute bottom-2">Script Code</span>
                                    <?php endif; ?>
                                </div>

                                <div class="absolute top-2 right-12 flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity bg-white/90 dark:bg-slate-800/90 rounded-md shadow-sm p-1 backdrop-blur-sm">
                                    <button @click="editAd(<?= htmlspecialchars(json_encode($ad)) ?>)" class="w-7 h-7 flex items-center justify-center rounded text-indigo-600 hover:bg-indigo-50 transition">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>
                                    <button @click="$dispatch('confirm-delete', { link: 'handlers/ad_handler.php?action=delete&id=<?= $ad['id'] ?>' })" class="w-7 h-7 flex items-center justify-center rounded text-red-600 hover:bg-red-50 transition">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </div>

                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="h-full flex flex-col items-center justify-center text-center py-8 opacity-50 border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-lg">
                            <span class="text-xs text-slate-400">Empty Slot</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div x-show="isModalOpen" x-cloak class="relative z-50" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div x-show="isModalOpen" x-transition.opacity class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm transition-opacity"></div>

        <div class="fixed inset-0 z-10 overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                <div x-show="isModalOpen" @click.outside="closeModal()"
                    x-transition.scale.origin.bottom
                    class="relative transform overflow-hidden rounded-xl bg-white dark:bg-slate-800 text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg border border-slate-200 dark:border-slate-700">

                    <form action="handlers/ad_handler.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="id" x-model="formData.id">
                        <input type="hidden" name="action" x-model="mode === 'add' ? 'create' : 'update'">

                        <div class="bg-indigo-600 px-6 py-4 flex justify-between items-center">
                            <h3 class="text-lg font-bold text-white flex items-center gap-2">
                                <i class="fa-solid" :class="mode === 'add' ? 'fa-plus-circle' : 'fa-pen-to-square'"></i>
                                <span x-text="mode === 'add' ? 'Create New Ad' : 'Edit Advertisement'"></span>
                            </h3>
                            <button type="button" @click="closeModal()" class="text-indigo-200 hover:text-white"><i class="fa-solid fa-xmark text-xl"></i></button>
                        </div>

                        <div class="p-6 space-y-5">
                            <div>
                                <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-1">Ad Name</label>
                                <input type="text" name="name" x-model="formData.name" required placeholder="e.g. Diwali Sale"
                                    class="w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm dark:bg-slate-900 dark:border-slate-600 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-1">Placement</label>
                                <select name="location" x-model="formData.location" class="w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm bg-white dark:bg-slate-900 dark:border-slate-600 dark:text-white">
                                    <?php foreach ($locations as $key => $val): ?>
                                        <option value="<?= $key ?>"><?= $val ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Ad Type</label>
                                <div class="grid grid-cols-2 gap-4">

                                    <label class="relative cursor-pointer border rounded-md p-3 text-center transition overflow-hidden"
                                        :class="formData.type === 'code' ? 'bg-indigo-50 border-indigo-500 text-indigo-700 ring-1 ring-indigo-500' : 'bg-slate-50 dark:bg-slate-700 border-slate-200 dark:border-slate-600 text-slate-500 hover:bg-slate-100'">

                                        <input type="radio" name="type" value="code" x-model="formData.type" class="sr-only">

                                        <div class="absolute top-0 right-0 bg-emerald-500 text-white text-[9px] font-bold px-2 py-0.5 rounded-bl-lg shadow-sm">
                                            RECOMMENDED
                                        </div>

                                        <i class="fa-solid fa-code mb-2 block text-lg mt-1"></i>
                                        <span class="text-xs font-bold">Script / AdSense</span>
                                    </label>
                                    <label class="cursor-pointer border rounded-md p-3 text-center transition"
                                        :class="formData.type === 'image' ? 'bg-indigo-50 border-indigo-500 text-indigo-700 ring-1 ring-indigo-500' : 'bg-slate-50 dark:bg-slate-700 border-slate-200 dark:border-slate-600 text-slate-500 hover:bg-slate-100'">

                                        <input type="radio" name="type" value="image" x-model="formData.type" class="sr-only">

                                        <i class="fa-regular fa-image mb-2 block text-lg mt-1"></i>
                                        <span class="text-xs font-bold">Image Banner</span>
                                    </label>

                                </div>
                            </div>

                            <div x-show="formData.type === 'image'" class="space-y-4 bg-slate-50 dark:bg-slate-700/30 p-4 rounded-lg border border-slate-100 dark:border-slate-600">
                                <div>
                                    <div class="flex justify-between mb-1">
                                        <label class="block text-xs font-bold text-slate-500 uppercase">Upload Banner</label>

                                        <span class="text-xs font-bold text-indigo-600 bg-indigo-50 px-2 rounded" x-text="'Size: ' + recommendedSize"></span>
                                    </div>
                                    <input type="file" name="ad_image" accept="image/*" class="block w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded file:border-0 file:text-xs file:font-bold file:bg-indigo-600 file:text-white hover:file:bg-indigo-700 cursor-pointer">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Destination URL</label>
                                    <input type="url" name="destination_url" x-model="formData.destination_url" placeholder="https://"
                                        class="w-full rounded border border-slate-300 px-3 py-2 text-sm dark:bg-slate-900 dark:border-slate-600 dark:text-white">
                                </div>
                            </div>

                            <div x-show="formData.type === 'code'">
                                <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-1">Custom Code / AdSense Code</label>
                                <textarea name="code" x-model="formData.code" rows="5" class="w-full rounded-md border border-slate-300 px-3 py-2 text-xs font-mono dark:bg-slate-900 dark:border-slate-600 dark:text-slate-300"></textarea>
                            </div>

                            <label class="flex items-center gap-3 cursor-pointer p-3 rounded-lg border border-slate-200 dark:border-slate-600">
                                <input type="checkbox" name="status" value="1" x-model="formData.status" class="w-5 h-5 text-indigo-600 rounded">
                                <span class="text-sm font-bold text-slate-700 dark:text-slate-300">Set as Active</span>
                            </label>

                        </div>

                        <div class="bg-gray-50 dark:bg-slate-900 px-6 py-4 flex flex-row-reverse border-t border-slate-200 dark:border-slate-700">
                            <button type="submit" class="inline-flex w-full justify-center rounded-md bg-indigo-600 px-5 py-2.5 text-sm font-bold text-white shadow-md hover:bg-indigo-700 sm:ml-3 sm:w-auto">Save Changes</button>
                            <button type="button" @click="closeModal()" class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-4 py-2.5 text-sm font-bold text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto dark:bg-slate-800 dark:text-white dark:ring-slate-600">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('adManager', () => ({
            isModalOpen: false,
            mode: 'add',
            formData: {
                id: '',
                name: '',
                location: 'header_top',
                type: 'image',
                destination_url: '',
                code: '',
                status: true
            },

            sizeMap: {
                'header_top': '728x90px (Leaderboard)',
                'sidebar_top': '300x250px (Medium Rectangle)',
                'sidebar_bottom': '300x600px (Wide Skyscraper)',
                'article_top': '728x90px or Responsive',
                'article_middle': '300x250px (In-Article)',
                'article_bottom': '728x90px (Leaderboard)',
                'footer_top': '970x90px (Large Leaderboard)'
            },

            get recommendedSize() {
                return this.sizeMap[this.formData.location] || 'Responsive / Any Size';
            },

            openModal(mode, preselectedLoc = null) {
                this.mode = mode;
                if (mode === 'add') {
                    this.resetForm();
                    if (preselectedLoc) this.formData.location = preselectedLoc;
                }
                this.isModalOpen = true;
            },

            closeModal() {
                this.isModalOpen = false;
            },

            resetForm() {
                this.formData = {
                    id: '',
                    name: '',
                    location: 'header_top',
                    type: 'image',
                    destination_url: '',
                    code: '',
                    status: true
                };
            },

            editAd(ad) {
                this.mode = 'edit';
                this.formData = {
                    id: ad.id,
                    name: ad.name,
                    location: ad.location,
                    type: ad.type,
                    destination_url: ad.destination_url || '',
                    code: ad.code || '',
                    status: ad.status == 1
                };
                this.isModalOpen = true;
            }
        }));
    });
</script>

<?php include 'includes/footer.php'; ?>