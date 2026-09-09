<?php
require_once 'includes/header.php';
require_once '../config/database.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$stmt = $conn->query("
    SELECT c.*, p.name AS parent_name 
    FROM categories c 
    LEFT JOIN categories p ON c.parent_id = p.id 
    ORDER BY COALESCE(NULLIF(c.parent_id, 0), c.id), c.parent_id ASC, c.name ASC
");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Top-level only for parent dropdown
$parent_options = $conn->query("
    SELECT id, name FROM categories 
    WHERE (parent_id IS NULL OR parent_id = 0) AND status = 1 
    ORDER BY name ASC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div x-data="categoryManager()">

    <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-800 dark:text-white">Category Management</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400">Organize news into sections and optional subcategories.</p>
        </div>
        <button @click="openModal('add')"
            class="w-full sm:w-auto bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-md shadow flex items-center justify-center transition-transform hover:scale-[1.02]">
            <i class="fa-solid fa-plus mr-2"></i> Add Category
        </button>
    </div>

    <div class="bg-blue-50 border-l-4 border-blue-500 text-blue-800 p-4 mb-6 rounded-md flex items-start gap-3 dark:bg-slate-700/50 dark:border-blue-800 dark:text-blue-300">
        <i class="fa-solid fa-info-circle text-lg flex-shrink-0 mt-0.5"></i>
        <div>
            <p class="font-bold text-sm">Menu & subcategory notes</p>
            <ul class="text-sm list-disc ml-4 mt-1 space-y-1">
                <li>Header shows up to <strong>7 top-level</strong> categories with “Show in Menu”.</li>
                <li>Subcategories appear in a <strong>dropdown</strong> under their parent (one level only).</li>
                <li>Parent category pages also list posts from their subcategories.</li>
            </ul>
        </div>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-lg shadow border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm text-left text-slate-600 dark:text-slate-400">
                <thead class="text-xs text-slate-700 uppercase bg-slate-50 dark:bg-slate-700 dark:text-slate-200">
                    <tr>
                        <th class="px-6 py-3 w-16">Color</th>
                        <th class="px-6 py-3">Name / Slug</th>
                        <th class="px-6 py-3 w-40">Parent</th>
                        <th class="px-6 py-3 w-20 text-center">In Menu</th>
                        <th class="px-6 py-3 w-20 text-center">Status</th>
                        <th class="px-6 py-3 w-20 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                    <?php foreach ($categories as $cat):
                        $is_child = (int)($cat['parent_id'] ?? 0) > 0;
                    ?>
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition">
                            <td class="px-6 py-4">
                                <span class="block w-6 h-6 rounded-full border border-slate-200 shadow-sm mx-auto" style="background-color: <?= htmlspecialchars($cat['color']) ?>;"></span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-bold text-slate-800 dark:text-white <?= $is_child ? 'pl-4 border-l-2 border-slate-200 dark:border-slate-600' : '' ?>">
                                    <?php if ($is_child): ?><span class="text-slate-400 mr-1">↳</span><?php endif; ?>
                                    <?= htmlspecialchars($cat['name']) ?>
                                </div>
                                <div class="text-xs font-mono text-slate-500 <?= $is_child ? 'pl-4' : '' ?>"><?= htmlspecialchars($cat['slug']) ?></div>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <?php if ($is_child): ?>
                                    <span class="text-slate-700 dark:text-slate-300"><?= htmlspecialchars($cat['parent_name'] ?? '—') ?></span>
                                <?php else: ?>
                                    <span class="text-slate-400">Top-level</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <?php if ($cat['show_on_menu']): ?>
                                    <span class="text-xs bg-blue-50 text-blue-600 px-2 py-1 rounded border border-blue-100 dark:bg-blue-900/30 dark:text-blue-400">Yes</span>
                                <?php else: ?>
                                    <span class="text-xs text-slate-400">No</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <?php if ($cat['status']): ?>
                                    <span class="inline-flex items-center gap-1 rounded-full bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">Active</span>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1 rounded-full bg-slate-50 px-2 py-1 text-xs font-medium text-slate-600 ring-1 ring-inset ring-slate-500/10">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-right whitespace-nowrap">
                                <button @click="editCategory(<?= htmlspecialchars(json_encode($cat), ENT_QUOTES, 'UTF-8') ?>)"
                                    class="p-2 text-indigo-600 hover:text-indigo-900 transition rounded-full hover:bg-indigo-50" title="Edit">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                                <button @click="$dispatch('confirm-delete', { link: 'handlers/category_handler.php?action=delete&id=<?= (int)$cat['id'] ?>' })"
                                    class="p-2 text-red-500 hover:text-red-700 transition rounded-full hover:bg-red-50" title="Delete">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div x-show="isModalOpen" x-cloak
        class="fixed inset-0 z-50 overflow-y-auto"
        aria-labelledby="modal-title" role="dialog" aria-modal="true">

        <div x-show="isModalOpen" x-transition.opacity
            class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm transition-opacity"></div>

        <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
            <div x-show="isModalOpen" x-transition.scale.origin.bottom
                @click.outside="closeModal()"
                class="relative transform overflow-hidden rounded-lg bg-white dark:bg-slate-800 text-left shadow-xl transition-all sm:my-8 w-full sm:max-w-lg border border-slate-200 dark:border-slate-700">

                <form action="handlers/category_handler.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="id" x-model="formData.id">
                    <input type="hidden" name="action" :value="mode === 'add' ? 'create' : 'update'">

                    <div class="bg-white dark:bg-slate-800 px-6 py-4 border-b border-slate-100 dark:border-slate-700 flex justify-between items-center">
                        <h3 class="text-lg font-semibold leading-6 text-slate-900 dark:text-white" x-text="mode === 'add' ? 'Add New Category' : 'Edit Category'"></h3>
                        <button type="button" @click="closeModal()" class="text-slate-400 hover:text-slate-500">
                            <i class="fa-solid fa-xmark text-lg"></i>
                        </button>
                    </div>

                    <div class="p-6 space-y-5">

                        <div>
                            <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Category Name <span class="text-red-500">*</span></label>
                            <input type="text" name="name" x-model="formData.name" @input="generateSlug()" required
                                class="w-full rounded-md border border-slate-400 px-4 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 dark:bg-slate-900 dark:border-slate-600 dark:text-white" placeholder="e.g. World News">
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Slug (URL)</label>
                            <input type="text" name="slug" x-model="formData.slug" required
                                class="w-full rounded-md border border-slate-400 px-4 py-2.5 text-sm bg-slate-50 font-mono text-slate-600 dark:bg-slate-900 dark:border-slate-600 dark:text-slate-400" placeholder="e.g. world-news">
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Parent Category</label>
                            <select name="parent_id" x-model="formData.parent_id"
                                class="w-full rounded-md border border-slate-400 px-4 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 dark:bg-slate-900 dark:border-slate-600 dark:text-white">
                                <option value="0">None (Top-level)</option>
                                <?php foreach ($parent_options as $opt): ?>
                                    <option value="<?= (int)$opt['id'] ?>" x-show="String(formData.id) !== '<?= (int)$opt['id'] ?>'">
                                        <?= htmlspecialchars($opt['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="text-xs text-slate-500 mt-1">Leave as top-level for main menu items. Pick a parent to make a subcategory.</p>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Short Description</label>
                            <textarea name="description" x-model="formData.description" rows="2"
                                class="w-full rounded-md border border-slate-400 px-4 py-2.5 text-sm dark:bg-slate-900 dark:border-slate-600 dark:text-white" placeholder="A brief description for SEO..."></textarea>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                            <div>
                                <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Label Color</label>
                                <div class="flex items-center gap-3">
                                    <input type="color" name="color" x-model="formData.color" class="h-10 w-full sm:w-16 rounded cursor-pointer border border-slate-300">
                                    <span class="text-xs text-slate-500 font-mono hidden sm:inline" x-text="formData.color"></span>
                                </div>
                            </div>

                            <div class="space-y-3 pt-1">
                                <label class="flex items-center gap-3 cursor-pointer p-2 -ml-2 rounded hover:bg-slate-50 dark:hover:bg-slate-700/50">
                                    <input type="checkbox" name="show_on_menu" value="1" x-model="formData.show_on_menu" class="w-4 h-4 text-indigo-600 rounded border-slate-300 focus:ring-indigo-600">
                                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Show in Main Menu</span>
                                </label>

                                <label class="flex items-center gap-3 cursor-pointer p-2 -ml-2 rounded hover:bg-slate-50 dark:hover:bg-slate-700/50">
                                    <input type="checkbox" name="status" value="1" x-model="formData.status" class="w-4 h-4 text-green-600 rounded border-slate-300 focus:ring-green-600">
                                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Active (Publishable)</span>
                                </label>
                            </div>
                        </div>

                    </div>

                    <div class="bg-gray-50 dark:bg-slate-900 px-6 py-4 flex flex-col sm:flex-row-reverse border-t border-slate-100 dark:border-slate-700">
                        <button type="submit" class="inline-flex w-full justify-center rounded-md bg-indigo-600 px-4 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-indigo-700 sm:ml-3 sm:w-auto">Save Category</button>
                        <button type="button" @click="closeModal()" class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-4 py-2.5 text-sm font-bold text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto dark:bg-slate-800 dark:text-white dark:ring-slate-600 dark:hover:bg-slate-700">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('categoryManager', () => ({
            isModalOpen: false,
            mode: 'add',
            formData: {
                id: '',
                name: '',
                slug: '',
                description: '',
                color: '#6366f1',
                parent_id: '0',
                show_on_menu: true,
                status: true
            },

            openModal(mode) {
                this.mode = mode;
                if (mode === 'add') {
                    this.resetForm();
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
                    slug: '',
                    description: '',
                    color: '#6366f1',
                    parent_id: '0',
                    show_on_menu: true,
                    status: true
                };
            },

            editCategory(category) {
                this.mode = 'edit';
                this.formData = {
                    id: category.id,
                    name: category.name,
                    slug: category.slug,
                    description: category.description || '',
                    color: category.color || '#6366f1',
                    parent_id: String(category.parent_id || 0),
                    show_on_menu: category.show_on_menu == 1,
                    status: category.status == 1
                };
                this.isModalOpen = true;
            },

            generateSlug() {
                let text = this.formData.name;
                this.formData.slug = text.toString().toLowerCase()
                    .replace(/\s+/g, '-')
                    .replace(/[^\w\-]+/g, '')
                    .replace(/\-\-+/g, '-')
                    .replace(/^-+/, '')
                    .replace(/-+$/, '');
            }
        }));
    });
</script>

<?php include 'includes/footer.php'; ?>
