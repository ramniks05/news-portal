<?php
require_once 'includes/header.php';
require_once '../config/database.php';

$categories = $conn->query("SELECT id, name FROM categories WHERE status=1 ORDER BY name ASC")->fetchAll();

function get_detailed_slots($conn, $key)
{
    $stmt = $conn->prepare("SELECT s.post_id, p.title, p.featured_image, DATE_FORMAT(p.published_at, '%b %d, %Y') as pub_date 
                            FROM site_sections s JOIN posts p ON s.post_id = p.id 
                            WHERE s.section_key = :key ORDER BY s.sort_order ASC");
    $stmt->execute([':key' => $key]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
$current_hero = get_detailed_slots($conn, 'hero_featured');
$current_slider = get_detailed_slots($conn, 'home_slider');
?>

<div x-data="featuredManager()" class="pb-24">

    <div class="mb-10 flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-200 dark:border-slate-700 pb-6">
        <div>
            <h2 class="text-2xl font-black text-slate-800 dark:text-white tracking-tight uppercase">Homepage Curator</h2>
            <p class="text-xs font-bold text-slate-500 uppercase tracking-widest mt-1">Organize your premium content slots</p>
        </div>
        <button @click="saveAll" :disabled="isSaving"
            class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-10 rounded-xl shadow-lg shadow-indigo-100 transition-all flex items-center gap-3 disabled:opacity-50">
            <i class="fa-solid fa-floppy-disk" x-show="!isSaving"></i>
            <i class="fa-solid fa-circle-notch fa-spin" x-show="isSaving"></i>
            <span x-text="isSaving ? 'Updating...' : 'Save Changes'"></span>
        </button>
    </div>

    <div class="mb-14">
        <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.3em] mb-6 flex items-center gap-4">
            <i class="fa-solid fa-star text-amber-500 text-sm"></i> Main Hero Section (3 Slots)
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <template x-for="(slot, index) in heroSlots" :key="'hero-'+index">
                <div class="relative group bg-white dark:bg-slate-800 rounded-2xl border-2 transition-all duration-300 overflow-hidden min-h-[120px] flex items-center"
                    :class="slot.post_id ? 'border-indigo-500 shadow-md' : 'border-dashed border-slate-300 dark:border-slate-700 hover:border-indigo-400'">

                    <div class="absolute top-2 left-2 z-10">
                        <span class="bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-400 text-[8px] font-black px-1.5 py-0.5 rounded" x-text="'SLOT ' + (index + 1)"></span>
                    </div>
                    <button x-show="!slot.post_id" @click="openPicker('hero', index)"
                        class="w-full h-full flex flex-col items-center justify-center gap-2 py-8 group-hover:bg-slate-50 dark:group-hover:bg-slate-900/50 transition-colors">
                        <i class="fa-solid fa-plus-circle text-slate-300 text-xl group-hover:text-indigo-500 transition-colors"></i>
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-tighter">Assign Article</span>
                    </button>

                    <div x-show="slot.post_id" class="w-full flex items-center gap-4 p-4 animate-fade-in">
                        <img :src="'../../' + slot.image" class="h-16 w-16 rounded-xl object-cover shadow-sm border border-slate-100 dark:border-slate-600 flex-shrink-0">
                        <div class="min-w-0 pr-6">
                            <p class="text-xs font-black text-slate-800 dark:text-white line-clamp-2 leading-tight mb-1" x-text="slot.title"></p>
                            <span class="text-[9px] font-bold text-slate-400 uppercase" x-text="slot.date"></span>
                        </div>
                        <button @click.stop="clearSlot('hero', index)" class="absolute top-2 right-2 text-slate-300 hover:text-red-500 transition-colors">
                            <i class="fa-solid fa-circle-xmark text-lg"></i>
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <div>
        <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.3em] mb-6 flex items-center gap-4">
            <i class="fa-solid fa-images text-indigo-500 text-sm"></i> Top Picks Slider (10 Slots)
        </h3>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
            <template x-for="(slot, index) in sliderSlots" :key="'slider-'+index">
                <div class="relative group bg-white dark:bg-slate-800 rounded-xl border transition-all duration-300 overflow-hidden"
                    :class="slot.post_id ? 'border-indigo-500 shadow-sm' : 'border-dashed border-slate-200 dark:border-slate-700'">

                    <button x-show="!slot.post_id" @click="openPicker('slider', index)"
                        class="w-full py-6 flex flex-col items-center justify-center gap-1 hover:bg-slate-50 transition-colors">
                        <i class="fa-solid fa-plus text-slate-300 text-xs"></i>
                        <span class="text-[8px] font-black text-slate-400 uppercase" x-text="'SLOT ' + (index+1)"></span>
                    </button>

                    <div x-show="slot.post_id" class="flex flex-col animate-fade-in">
                        <div class="relative h-20 w-full">
                            <img :src="'../../' + slot.image" class="w-full h-full object-cover">
                            <div class="absolute inset-0 bg-black/20 group-hover:bg-black/0 transition-colors"></div>
                            <button @click.stop="clearSlot('slider', index)" class="absolute top-1.5 right-1.5 h-5 w-5 bg-white shadow-md text-red-500 rounded-full flex items-center justify-center">
                                <i class="fa-solid fa-xmark text-[10px]"></i>
                            </button>
                        </div>
                        <div class="p-3">
                            <p class="text-[10px] font-bold text-slate-800 dark:text-white line-clamp-1" x-text="slot.title"></p>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <div x-show="pickerOpen" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center p-4">
        <div x-show="pickerOpen" x-transition.opacity class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm"></div>

        <div x-show="pickerOpen" x-transition.scale.origin.top @click.outside="closePicker()"
            class="relative w-full max-w-3xl bg-white dark:bg-slate-800 rounded-3xl shadow-2xl border-4 border-white dark:border-slate-700 overflow-hidden flex flex-col max-h-[85vh]">

            <div class="p-6 border-b border-slate-100 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-900/50">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h4 class="text-lg font-black text-slate-800 dark:text-white tracking-tight">Select Article</h4>
                        <p class="text-[10px] font-black text-indigo-600 uppercase tracking-widest mt-1" x-text="'Assigning to ' + activeSlot.section + ' slot ' + (activeSlot.index + 1)"></p>
                    </div>
                    <button @click="closePicker()" class="h-8 w-8 flex items-center justify-center rounded-full border border-slate-200 text-slate-400 hover:bg-red-50 hover:text-red-500 transition-all"><i class="fa-solid fa-xmark"></i></button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                    <div class="relative group">
                        <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-xs group-focus-within:text-indigo-500 transition-colors"></i>
                        <input type="text" x-model="searchQuery" @input.debounce.400ms="fetchArticles()"
                            placeholder="Search headline..."
                            class="w-full pl-10 pr-4 py-3 rounded-xl border-2 border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white text-xs focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 outline-none transition-all shadow-sm">
                    </div>

                    <div class="relative">
                        <select x-model="categoryFilter" @change="fetchArticles()"
                            class="w-full px-4 py-3 rounded-xl border-2 border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white text-xs focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 outline-none transition-all shadow-sm appearance-none">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= $cat['name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                        <i class="fa-solid fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none text-[10px]"></i>
                    </div>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto p-6 bg-white dark:bg-slate-800 custom-scrollbar">
                <div x-show="loading" class="flex justify-center py-12">
                    <i class="fa-solid fa-circle-notch fa-spin text-2xl text-indigo-600"></i>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3" x-show="!loading">
                    <template x-for="post in results" :key="post.id">
                        <button @click="selectPost(post)"
                            class="flex items-center gap-3 p-2 bg-slate-50 dark:bg-slate-900/50 rounded-xl border-2 border-transparent hover:border-indigo-500 hover:bg-white dark:hover:bg-slate-700 transition-all text-left">
                            <img :src="'../../' + post.featured_image" class="h-12 w-12 rounded-lg object-cover flex-shrink-0">
                            <div class="min-w-0">
                                <h5 class="text-[11px] font-bold text-slate-800 dark:text-white line-clamp-1" x-text="post.title"></h5>
                                <p class="text-[9px] font-bold text-slate-400 mt-0.5" x-text="post.pub_date"></p>
                            </div>
                        </button>
                    </template>
                </div>

                <div x-show="!loading && results.length === 0" class="text-center py-12 text-slate-400">
                    <i class="fa-solid fa-search text-2xl mb-2 opacity-20"></i>
                    <p class="text-xs font-bold uppercase tracking-widest">No articles found</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function featuredManager() {
        return {
            isSaving: false,
            pickerOpen: false,
            loading: false,
            activeSlot: {
                section: '',
                index: null
            },
            searchQuery: '',
            categoryFilter: '',
            results: [],

            heroSlots: Array.from({
                length: 3
            }, (_, i) => {
                let d = <?= json_encode($current_hero) ?>;
                return d[i] ? {
                    post_id: d[i].post_id,
                    title: d[i].title,
                    image: d[i].featured_image,
                    date: d[i].pub_date
                } : {
                    post_id: null
                };
            }),

            sliderSlots: Array.from({
                length: 10
            }, (_, i) => {
                let d = <?= json_encode($current_slider) ?>;
                return d[i] ? {
                    post_id: d[i].post_id,
                    title: d[i].title,
                    image: d[i].featured_image,
                    date: d[i].pub_date
                } : {
                    post_id: null
                };
            }),

            async openPicker(section, index) {
                this.activeSlot = {
                    section,
                    index
                };
                this.pickerOpen = true;
                this.searchQuery = '';
                this.categoryFilter = '';
                await this.fetchArticles();
            },

            closePicker() {
                this.pickerOpen = false;
            },

            async fetchArticles() {
                this.loading = true;
                try {
                    const res = await fetch(`handlers/ajax_search_posts.php?q=${this.searchQuery}&cat_id=${this.categoryFilter}`);
                    this.results = await res.json();
                } catch (e) {
                    console.error(e);
                }
                this.loading = false;
            },

            selectPost(post) {
                const allSelected = [...this.heroSlots, ...this.sliderSlots].map(s => s.post_id);
                if (allSelected.includes(post.id)) {
                    alert("This article is already assigned elsewhere.");
                    return;
                }
                const target = this.activeSlot.section === 'hero' ? this.heroSlots : this.sliderSlots;
                target[this.activeSlot.index] = {
                    post_id: post.id,
                    title: post.title,
                    image: post.featured_image,
                    date: post.pub_date
                };
                this.closePicker();
            },

            clearSlot(section, index) {
                const target = section === 'hero' ? this.heroSlots : this.sliderSlots;
                target[index] = {
                    post_id: null
                };
            },

            async saveAll() {
                this.isSaving = true;
                const fd = new FormData();
                fd.append('csrf_token', '<?= $_SESSION['csrf_token'] ?>');
                fd.append('hero', JSON.stringify(this.heroSlots.map(s => s.post_id)));
                fd.append('slider', JSON.stringify(this.sliderSlots.map(s => s.post_id)));
                const res = await fetch('handlers/featured_handler.php', {
                    method: 'POST',
                    body: fd
                });
                const data = await res.json();
                if (data.status === 'success') {
                    window.dispatchEvent(new CustomEvent('notify', {
                        detail: {
                            message: data.message,
                            type: 'success'
                        }
                    }));
                }
                this.isSaving = false;
            }
        }
    }
</script>

<style>
    .custom-scrollbar::-webkit-scrollbar {
        width: 4px;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #e2e8f0;
        border-radius: 10px;
    }

    .no-scrollbar::-webkit-scrollbar {
        display: none;
    }

    .animate-fade-in {
        animation: fadeIn 0.4s ease-out;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(5px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>

<?php include 'includes/footer.php'; ?>