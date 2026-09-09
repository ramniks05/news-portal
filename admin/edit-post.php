<?php
require_once 'includes/header.php';
require_once '../config/database.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Invalid Post ID.";
    header("Location: manage-posts.php");
    exit();
}
$post_id = (int)$_GET['id'];

$stmt = $conn->prepare("SELECT * FROM posts WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $post_id]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$post) {
    $_SESSION['error'] = "Post not found.";
    header("Location: manage-posts.php");
    exit();
}

$cat_stmt = $conn->query("SELECT id, name, parent_id FROM categories WHERE status = 1 ORDER BY name ASC");
$raw_cats = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);
$categories = [];
$parents = [];
$children_map = [];
foreach ($raw_cats as $row) {
    $pid = (int)($row['parent_id'] ?? 0);
    if ($pid === 0) {
        $parents[] = $row;
    } else {
        $children_map[$pid][] = $row;
    }
}
foreach ($parents as $parent) {
    $categories[] = ['id' => $parent['id'], 'label' => $parent['name']];
    if (!empty($children_map[$parent['id']])) {
        foreach ($children_map[$parent['id']] as $child) {
            $categories[] = ['id' => $child['id'], 'label' => '— ' . $child['name']];
        }
    }
}

$tag_stmt = $conn->prepare("
    SELECT t.name 
    FROM tags t 
    JOIN post_tags pt ON t.id = pt.tag_id 
    WHERE pt.post_id = :pid
");
$tag_stmt->execute([':pid' => $post_id]);
$tags = $tag_stmt->fetchAll(PDO::FETCH_COLUMN);
$tags_string = implode(', ', $tags);

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<link rel="stylesheet" href="https://unpkg.com/jodit@4.0.0-beta.24/es2021/jodit.min.css" />
<script src="https://unpkg.com/jodit@4.0.0-beta.24/es2021/jodit.min.js"></script>

<style>
    .dark .jodit-container {
        background-color: #0f172a !important;
        border-color: #334155 !important;
        color: #e2e8f0 !important;
    }

    .dark .jodit-toolbar__box,
    .dark .jodit-workplace,
    .dark .jodit-status-bar {
        background-color: #0f172a !important;
        border-color: #334155 !important;
    }

    .dark .jodit-icon {
        fill: #cbd5e1 !important;
    }

    .dark .jodit-wysiwyg {
        color: #e2e8f0 !important;
    }

    [x-cloak] {
        display: none !important;
    }
</style>

<div x-data="postEditor()" class="pb-12">

    <form action="handlers/post_handler.php" method="POST" enctype="multipart/form-data" @submit="validateForm">
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6 bg-gray-100 dark:bg-slate-900 py-4 transition-colors">
            <div>
                <h2 class="text-2xl font-black text-slate-800 dark:text-white tracking-tight">Edit Article</h2>
                <a href="manage-posts.php" class="text-xs font-bold text-indigo-600 hover:text-indigo-500 uppercase tracking-wider">
                    <i class="fa-solid fa-arrow-left mr-1"></i> Back to Posts
                </a>
            </div>
            <div class="flex gap-3 w-full sm:w-auto">
                <?php if ($post['status'] === 'published'): ?>
                    <a href="../../article.php?slug=<?= $post['slug'] ?>" target="_blank" class="px-4 py-2.5 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-200 font-bold rounded-lg shadow-sm hover:bg-slate-50 transition flex items-center justify-center">
                        <i class="fa-solid fa-arrow-up-right-from-square mr-2"></i> View Live
                    </a>
                <?php endif; ?>

                <button type="submit" class="flex-1 sm:flex-none px-8 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-lg shadow-lg shadow-indigo-200 dark:shadow-none transition transform active:scale-95 flex items-center justify-center">
                    <i class="fa-solid fa-floppy-disk mr-2"></i> Update
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white dark:bg-slate-800 p-6 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
                    <label class="block text-sm font-black text-slate-700 dark:text-slate-300 mb-3 uppercase tracking-widest">Article Headline</label>
                    <input type="text" name="title" x-model="title" required
                        class="w-full text-xl font-bold px-4 py-3 rounded-lg border border-slate-400 dark:border-slate-600 focus:ring-2 focus:ring-indigo-500 dark:bg-slate-900 dark:text-white outline-none transition-all">
                    <div class="mt-4 p-2 bg-slate-50 dark:bg-slate-900/50 rounded border border-slate-100 dark:border-slate-700 flex items-center gap-2">
                        <span class="text-[10px] font-black text-slate-400 uppercase">Slug:</span>
                        <div class="flex-1 flex items-center gap-2 overflow-hidden">
                            <template x-if="!isSlugEditable">
                                <span class="text-xs font-mono text-indigo-600 truncate" x-text="slug"></span>
                            </template>
                            <template x-if="isSlugEditable">
                                <input type="text" name="slug" x-model="slug" class="w-full text-xs font-mono p-1 border rounded bg-white dark:bg-slate-800 dark:text-white outline-none focus:ring-1 focus:ring-indigo-500">
                            </template>
                        </div>
                        <button type="button" @click="isSlugEditable = !isSlugEditable" class="text-slate-400 hover:text-indigo-600 transition">
                            <i class="fa-solid" :class="isSlugEditable ? 'fa-check-circle text-green-500' : 'fa-pen-to-square'"></i>
                        </button>
                        <input type="hidden" name="slug" :value="slug" x-show="!isSlugEditable">
                    </div>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
                    <textarea id="postContent" name="content"><?= htmlspecialchars($post['content']) ?></textarea>
                </div>
                <div class="bg-white dark:bg-slate-800 p-6 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
                    <div class="flex items-center gap-2 mb-6 border-b border-slate-100 dark:border-slate-700 pb-4">
                        <i class="fa-brands fa-google text-indigo-600 text-xl"></i>
                        <h3 class="font-black text-slate-800 dark:text-white uppercase tracking-wider text-sm">Search Engine Optimization</h3>
                    </div>

                    <div class="space-y-6">
                        <div>
                            <div class="flex justify-between mb-2">
                                <label class="text-xs font-bold text-slate-500 uppercase">Meta Title</label>
                                <span class="text-[10px] font-bold" :class="metaTitle.length > 60 ? 'text-red-500' : 'text-slate-400'"><span x-text="metaTitle.length"></span>/60</span>
                            </div>
                            <input type="text" name="meta_title" x-model="metaTitle" maxlength="70"
                                class="w-full text-sm rounded-lg border border-slate-400 px-4 py-2.5 dark:bg-slate-900 dark:border-slate-600 dark:text-white outline-none">
                        </div>
                        <div>
                            <div class="flex justify-between mb-2">
                                <label class="text-xs font-bold text-slate-500 uppercase">Meta Description</label>
                                <span class="text-[10px] font-bold" :class="metaDesc.length > 160 ? 'text-red-500' : 'text-slate-400'"><span x-text="metaDesc.length"></span>/160</span>
                            </div>
                            <textarea name="meta_description" x-model="metaDesc" maxlength="200" rows="3"
                                class="w-full text-sm rounded-lg border border-slate-400 px-4 py-2.5 dark:bg-slate-900 dark:border-slate-600 dark:text-white outline-none"></textarea>
                        </div>
                    </div>
                </div>

            </div>
            <div class="space-y-6">
                <div class="bg-white dark:bg-slate-800 p-6 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
                    <h3 class="font-black text-slate-800 dark:text-white text-xs uppercase tracking-widest mb-4">Publishing</h3>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Post Status</label>
                            <select name="status" class="w-full rounded border border-slate-400 px-3 py-2.5 text-sm font-bold dark:bg-slate-900 dark:border-slate-600 dark:text-white outline-none">
                                <option value="draft" <?= $post['status'] == 'draft' ? 'selected' : '' ?>>Draft</option>
                                <option value="published" <?= $post['status'] == 'published' ? 'selected' : '' ?>>Published</option>
                                <option value="archived" <?= $post['status'] == 'archived' ? 'selected' : '' ?>>Archived</option>
                            </select>
                        </div>
                    </div>
                </div>
                <?php
                $feat = $post['featured_image'] ?? '';
                $is_external = $feat && preg_match('#^https?://#i', $feat);
                $feat_preview = $is_external ? $feat : ($feat ? '../../' . $feat : '');
                $feat_url_value = $is_external ? $feat : '';
                $feat_source = $is_external ? 'url' : 'upload';
                ?>
                <div class="bg-white dark:bg-slate-800 p-6 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-black text-slate-800 dark:text-white text-xs uppercase tracking-widest">Featured Image</h3>
                        <span x-show="imgError" x-text="imgError" x-cloak class="text-[9px] text-red-500 font-bold bg-red-50 px-2 py-0.5 rounded"></span>
                    </div>

                    <div class="flex gap-2 mb-4">
                        <button type="button" @click="imageSource = 'upload'"
                            class="flex-1 text-[10px] font-black uppercase tracking-wider py-2 rounded-lg border transition"
                            :class="imageSource === 'upload' ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-slate-50 text-slate-500 border-slate-200 dark:bg-slate-900 dark:border-slate-600'">
                            <i class="fa-solid fa-upload mr-1"></i> Upload
                        </button>
                        <button type="button" @click="imageSource = 'url'"
                            class="flex-1 text-[10px] font-black uppercase tracking-wider py-2 rounded-lg border transition"
                            :class="imageSource === 'url' ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-slate-50 text-slate-500 border-slate-200 dark:bg-slate-900 dark:border-slate-600'">
                            <i class="fa-solid fa-link mr-1"></i> Image URL
                        </button>
                    </div>

                    <input type="hidden" name="remove_featured_image" :value="removeFeaturedImage ? '1' : '0'">

                    <div x-show="imageSource === 'upload'" x-cloak>
                        <div class="border-2 border-dashed rounded-xl p-4 text-center transition cursor-pointer relative overflow-hidden"
                            :class="imgError ? 'border-red-300 bg-red-50/20' : 'border-slate-300 dark:border-slate-600 hover:bg-slate-50'"
                            @click="$refs.fileInput.click()">
                            <div x-show="imgPreview && imageSource === 'upload'" class="relative group aspect-[4/3] w-full overflow-hidden rounded-lg shadow-md border border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-900">
                                <img :src="imgPreview" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105">
                                <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex flex-col items-center justify-center text-white gap-2">
                                    <span class="text-xs font-bold uppercase tracking-wider">Change Image</span>
                                    <button type="button" @click.stop="removeImage"
                                        class="text-[10px] bg-red-600 hover:bg-red-700 px-3 py-1 rounded-full font-bold shadow-lg transition-colors active:scale-95">
                                        Remove
                                    </button>
                                </div>
                            </div>
                            <div x-show="!imgPreview || imageSource !== 'upload'" class="py-6">
                                <i class="fa-regular fa-image text-4xl mb-3 text-slate-300"></i>
                                <p class="text-xs font-bold text-slate-500">Upload new image</p>
                            </div>
                            <input type="file" x-ref="fileInput" name="featured_image" accept="image/jpeg,image/png,image/webp" class="hidden" @change="validateImage">
                        </div>
                        <p class="text-[9px] text-slate-400 mt-2 text-center">Required: 800x450px | Max 100KB</p>
                    </div>

                    <div x-show="imageSource === 'url'" x-cloak class="space-y-3">
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest">Paste image URL (CDN / external)</label>
                        <input type="url" name="featured_image_url" x-model="imageUrl" @input="previewFromUrl"
                            placeholder="https://cdn.example.com/news-image.jpg"
                            class="w-full rounded-lg border border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white px-3 py-2.5 text-xs focus:ring-1 focus:ring-indigo-500 outline-none">
                        <p class="text-[10px] text-slate-400">Full <strong>https://</strong> image link from CDN or any host.</p>
                        <div x-show="imgPreview && imageSource === 'url'" class="relative group aspect-[4/3] w-full overflow-hidden rounded-lg shadow-md border border-slate-200">
                            <img :src="imgPreview" class="w-full h-full object-cover" @error="imgError = 'URL did not load as an image'">
                            <button type="button" @click="removeImage"
                                class="absolute top-2 right-2 text-[10px] bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded-full font-bold">
                                Remove
                            </button>
                        </div>
                    </div>
                </div>
                <div class="mt-4">
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Image Credit / Source</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                            <i class="fa-solid fa-camera-retro"></i>
                        </span>
                        <input type="text" name="image_credit"
                            value="<?= isset($post) ? htmlspecialchars($post['image_credit']) : '' ?>"
                            placeholder="e.g. Getty Images / PTI"
                            class="w-full pl-9 pr-4 py-2 text-xs rounded-lg border border-slate-300 dark:bg-slate-900 dark:border-slate-600 dark:text-white focus:ring-indigo-500">
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-800 p-6 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
                    <h3 class="font-black text-slate-800 dark:text-white text-xs uppercase tracking-widest mb-4">Categorization</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Category <span class="text-red-500">*</span></label>
                            <select name="category_id" required class="w-full rounded border border-slate-400 px-3 py-2.5 text-sm font-bold dark:bg-slate-900 dark:border-slate-600 dark:text-white outline-none">
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" <?= $cat['id'] == $post['category_id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['label']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Tags (Comma separated)</label>
                            <input type="text" name="tags" value="<?= htmlspecialchars($tags_string) ?>"
                                class="w-full rounded border border-slate-400 px-3 py-2.5 text-sm dark:bg-slate-900 dark:border-slate-600 dark:text-white outline-none">
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-800 p-6 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
                    <h3 class="font-black text-slate-800 dark:text-white text-xs uppercase tracking-widest mb-4">Short Summary</h3>
                    <textarea name="summary" rows="4" maxlength="300" class="w-full rounded border border-slate-400 px-4 py-3 text-sm dark:bg-slate-900 dark:border-slate-600 dark:text-white outline-none"><?= htmlspecialchars($post['summary']) ?></textarea>
                </div>

            </div>
        </div>

        <div class="fixed bottom-0 left-0 right-0 p-4 bg-white dark:bg-slate-900 border-t border-slate-200 dark:border-slate-700 shadow-2xl lg:hidden z-40">
            <button type="submit" class="w-full bg-indigo-600 text-white font-bold py-3 rounded-lg shadow-md flex justify-center items-center">
                <i class="fa-solid fa-floppy-disk mr-2"></i> Save Article Changes
            </button>
        </div>

    </form>
</div>

<script>
    const editor = Jodit.make('#postContent', {
        height: 500,
        uploader: {
            insertImageAsBase64URI: true
        },
        theme: localStorage.getItem('theme') === 'dark' ? 'dark' : 'default',
        toolbarAdaptive: false,
        askBeforePasteHTML: false,
        askBeforePasteFromWord: false,
        processPasteHTML: true,
        defaultActionOnPaste: 'insert_clear_html',
        buttons: ['bold', 'italic', 'underline', 'strikethrough', '|', 'ul', 'ol', '|', 'font', 'fontsize', 'brush', 'paragraph', '|', 'image', 'video', 'table', 'link', '|', 'align', 'undo', 'redo', '|', 'fullsize', 'source']
    });

    function postEditor() {
        return {
            title: `<?= addslashes($post['title']) ?>`,
            slug: `<?= $post['slug'] ?>`,
            isSlugEditable: false,
            metaTitle: `<?= addslashes($post['meta_title'] ?? '') ?>`,
            metaDesc: `<?= addslashes($post['meta_description'] ?? '') ?>`,
            imgPreview: `<?= addslashes($feat_preview) ?>`,
            imgError: '',
            imageSource: `<?= $feat_source ?>`,
            imageUrl: `<?= addslashes($feat_url_value) ?>`,
            removeFeaturedImage: false,

            validateImage(e) {
                const file = e.target.files[0];
                if (!file) return;

                this.imgError = '';
                this.imageSource = 'upload';
                this.imageUrl = '';
                this.removeFeaturedImage = false;

                if (file.size > 102400) {
                    this.imgError = "Max 100KB!";
                    this.removeImage();
                    return;
                }

                const img = new Image();
                const objectUrl = URL.createObjectURL(file);
                img.onload = () => {
                    if (img.width < 800 || img.height < 450) {
                        this.imgError = `Min 800x450! (${img.width}x${img.height})`;
                        this.removeImage();
                    } else {
                        this.imgPreview = objectUrl;
                    }
                };
                img.src = objectUrl;
            },

            previewFromUrl() {
                this.imgError = '';
                this.removeFeaturedImage = false;
                if (this.$refs.fileInput) this.$refs.fileInput.value = '';
                const url = (this.imageUrl || '').trim();
                if (!url) {
                    this.imgPreview = null;
                    return;
                }
                if (!/^https?:\/\//i.test(url)) {
                    this.imgError = 'URL must start with http:// or https://';
                    this.imgPreview = null;
                    return;
                }
                this.imgPreview = url;
            },

            removeImage() {
                this.imgPreview = null;
                this.imageUrl = '';
                this.imgError = '';
                this.removeFeaturedImage = true;
                if (this.$refs.fileInput) this.$refs.fileInput.value = '';
            },

            validateForm(e) {
                const content = editor.value.trim();
                if (content === '' || content === '<p><br></p>') {
                    e.preventDefault();
                    window.dispatchEvent(new CustomEvent('notify', {
                        detail: {
                            message: 'Article content is empty!',
                            type: 'error'
                        }
                    }));
                }
            }
        }
    }
</script>

<?php include 'includes/footer.php'; ?>