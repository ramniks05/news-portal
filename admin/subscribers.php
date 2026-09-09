<?php
require_once 'includes/header.php';
require_once '../config/database.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$stats = $conn->query("
    SELECT 
        SUM(CASE WHEN is_verified = 1 AND status = 'active' THEN 1 ELSE 0 END) as verified,
        SUM(CASE WHEN is_verified = 0 AND status = 'active' THEN 1 ELSE 0 END) as unverified,
        SUM(CASE WHEN status = 'unsubscribed' THEN 1 ELSE 0 END) as unsubscribed
    FROM newsletter_subscribers
")->fetch(PDO::FETCH_ASSOC);

$filter_type = $_GET['filter'] ?? 'verified';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 15;
$offset = ($page - 1) * $limit;

$where_clause = "WHERE 1";
if ($filter_type === 'verified') $where_clause = "WHERE is_verified = 1 AND status = 'active'";
if ($filter_type === 'unverified') $where_clause = "WHERE is_verified = 0 AND status = 'active'";
if ($filter_type === 'unsubscribed') $where_clause = "WHERE status = 'unsubscribed'";

$total_rows = $conn->query("SELECT COUNT(*) FROM newsletter_subscribers $where_clause")->fetchColumn();
$total_pages = ceil($total_rows / $limit);

$stmt = $conn->prepare("SELECT * FROM newsletter_subscribers $where_clause ORDER BY id DESC LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$subscribers = $stmt->fetchAll();
?>

<link rel="stylesheet" href="https://unpkg.com/jodit@4.0.0-beta.24/es2021/jodit.min.css" />
<script src="https://unpkg.com/jodit@4.0.0-beta.24/es2021/jodit.min.js"></script>

<div x-data="subscriberManager()">

    <div class="mb-8 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-800 dark:text-white">Newsletter & Campaigns</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400">Manage subscribers and send email blasts.</p>
        </div>
        <div class="flex gap-3">
            <a href="handlers/newsletter_handler.php?action=export" class="inline-flex items-center justify-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-md shadow-sm transition-colors">
                <i class="fa-solid fa-file-csv mr-2"></i> Export
            </a>
            <button @click="isComposeModalOpen = true" class="inline-flex items-center justify-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-md shadow-sm transition-all">
                <i class="fa-solid fa-paper-plane mr-2"></i> New Campaign
            </button>
        </div>
    </div>

    <div class="border-b border-slate-200 dark:border-slate-700 mb-6">
        <nav class="-mb-px flex flex-wrap gap-x-8 gap-y-2" aria-label="Tabs">
            <?php
            $tabs = [
                'verified' => ['label' => 'Verified', 'icon' => 'fa-circle-check', 'color' => 'green', 'count' => $stats['verified'] ?? 0],
                'unverified' => ['label' => 'Unverified', 'icon' => 'fa-clock', 'color' => 'amber', 'count' => $stats['unverified'] ?? 0],
                'unsubscribed' => ['label' => 'Unsubscribed', 'icon' => 'fa-bell-slash', 'color' => 'slate', 'count' => $stats['unsubscribed'] ?? 0],
            ];
            foreach ($tabs as $key => $tab):
                $active = ($filter_type === $key) ? "border-indigo-500 text-indigo-600 dark:text-indigo-400" : "border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300";
            ?>
                <a href="?filter=<?= $key ?>" class="<?= $active ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center">
                    <i class="fa-regular <?= $tab['icon'] ?> mr-2 text-<?= $tab['color'] ?>-500"></i>
                    <?= $tab['label'] ?>
                    <span class="ml-2 bg-<?= $tab['color'] ?>-100 text-<?= $tab['color'] ?>-600 py-0.5 px-2 rounded-full text-xs"><?= $tab['count'] ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-lg shadow border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-slate-600 dark:text-slate-400">
                <thead class="text-xs text-slate-700 uppercase bg-slate-50 dark:bg-slate-700 dark:text-slate-200">
                    <tr>
                        <th class="px-6 py-3">Email Address</th>
                        <th class="px-6 py-3">Status</th>
                        <th class="px-6 py-3">Subscribed On</th>
                        <th class="px-6 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                    <?php if (count($subscribers) > 0): ?>
                        <?php foreach ($subscribers as $sub): ?>
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition">
                                <td class="px-6 py-4 font-bold text-slate-900 dark:text-white"><?= htmlspecialchars($sub['email']) ?></td>
                                <td class="px-6 py-4">
                                    <?php if ($filter_type == 'verified'): ?>
                                        <span class="text-xs font-medium text-green-700 bg-green-50 px-2 py-1 rounded-full border border-green-200">Verified</span>
                                    <?php elseif ($filter_type == 'unverified'): ?>
                                        <span class="text-xs font-medium text-amber-700 bg-amber-50 px-2 py-1 rounded-full border border-amber-200">Pending Verification</span>
                                    <?php else: ?>
                                        <span class="text-xs font-medium text-slate-500 bg-slate-100 px-2 py-1 rounded-full border border-slate-200">Unsubscribed</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-slate-500"><?= date('M d, Y h:i A', strtotime($sub['created_at'])) ?></td>
                                <td class="px-6 py-4 text-right">
                                    <button @click="$dispatch('confirm-delete', { link: 'handlers/newsletter_handler.php?action=delete&id=<?= $sub['id'] ?>' })"
                                        class="text-slate-400 hover:text-red-600 transition-colors" title="Delete">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center py-10 text-slate-500">No subscribers in this category.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if ($total_pages > 1): ?>
            <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700 flex justify-between items-center text-xs">
                <span>Page <?= $page ?> of <?= $total_pages ?></span>
                <div class="flex gap-1">
                    <?php if ($page > 1): ?><a href="?filter=<?= $filter_type ?>&page=<?= $page - 1 ?>" class="px-3 py-1 border rounded bg-white hover:bg-slate-50 dark:bg-slate-800 dark:border-slate-600">Prev</a><?php endif; ?>
                    <?php if ($page < $total_pages): ?><a href="?filter=<?= $filter_type ?>&page=<?= $page + 1 ?>" class="px-3 py-1 border rounded bg-white hover:bg-slate-50 dark:bg-slate-800 dark:border-slate-600">Next</a><?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div x-show="isComposeModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div x-show="isComposeModalOpen" x-transition.opacity class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm"></div>
        <div class="flex min-h-full items-center justify-center p-4">
            <div x-show="isComposeModalOpen" x-transition.scale.origin.bottom @click.outside="isComposeModalOpen = false"
                class="relative transform overflow-hidden rounded-lg bg-white dark:bg-slate-800 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-3xl border border-slate-200 dark:border-slate-700">

                <div class="bg-indigo-900 px-6 py-4 flex justify-between items-center">
                    <h3 class="text-base font-bold text-white">New Email Campaign</h3>
                    <button @click="isComposeModalOpen = false" class="text-indigo-200 hover:text-white"><i class="fa-solid fa-xmark"></i></button>
                </div>

                <div class="p-6 space-y-4">
                    <input type="text" x-model="subject" class="w-full text-lg rounded border border-slate-400 p-3 dark:bg-slate-900 dark:border-slate-600 dark:text-white" placeholder="Email Subject Line">
                    <textarea id="emailEditor"></textarea>
                </div>

                <div class="bg-gray-50 dark:bg-slate-900 px-6 py-4 flex flex-row-reverse border-t border-slate-200 dark:border-slate-700">
                    <button @click="openConfirmModal()" class="inline-flex w-full justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-bold text-white shadow-sm hover:bg-indigo-500 sm:ml-3 sm:w-auto">Next: Confirm & Send</button>
                    <button @click="isComposeModalOpen = false" class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-4 py-2 text-sm font-bold text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 sm:mt-0 sm:w-auto dark:bg-slate-800 dark:text-white">Cancel</button>
                </div>
            </div>
        </div>
    </div>

    <div x-show="isConfirmModalOpen" x-cloak class="fixed inset-0 z-[60] overflow-y-auto">
        <div x-show="isConfirmModalOpen" x-transition.opacity class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm"></div>
        <div class="flex min-h-full items-center justify-center p-4">
            <div x-show="isConfirmModalOpen" x-transition.scale.origin.bottom @click.outside="!isSending && (isConfirmModalOpen = false)"
                class="relative transform overflow-hidden rounded-lg bg-white dark:bg-slate-800 text-center shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">

                <div x-show="!isSending && !isCompleted" class="p-8">
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-indigo-100 dark:bg-indigo-900/20 text-indigo-600 mb-5">
                        <i class="fa-solid fa-paper-plane text-3xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white">Ready to Send?</h3>
                    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                        This campaign will be sent to <strong><span x-text="totalCount"></span></strong> verified subscribers. This action cannot be undone.
                    </p>
                    <div class="mt-6 flex justify-center gap-3">
                        <button @click="isConfirmModalOpen = false" class="px-5 py-2 rounded-md bg-white ring-1 ring-slate-300 text-slate-700 font-bold text-sm">Cancel</button>
                        <button @click="startCampaign()" class="px-5 py-2 rounded-md bg-indigo-600 text-white font-bold text-sm shadow-sm">Confirm & Start Sending</button>
                    </div>
                </div>

                <div x-show="isSending" class="p-8">
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white">Sending...</h3>
                    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Processed <span x-text="sentCount"></span> of <span x-text="totalCount"></span> subscribers.</p>
                    <div class="w-full bg-slate-200 rounded-full h-2.5 dark:bg-slate-700 mt-4">
                        <div class="bg-indigo-600 h-2.5 rounded-full" :style="`width: ${percentage}%`"></div>
                    </div>
                </div>

                <div x-show="isCompleted" class="p-8">
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-green-100 text-green-600 mb-5"><i class="fa-solid fa-check text-3xl"></i></div>
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white">Campaign Sent!</h3>
                    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">The newsletter has been delivered.</p>
                    <button @click="isConfirmModalOpen = false; location.reload()" class="mt-6 px-5 py-2 rounded-md bg-indigo-600 text-white font-bold text-sm">Done</button>
                </div>

            </div>
        </div>
    </div>

</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('subscriberManager', () => ({
            isComposeModalOpen: false,
            isConfirmModalOpen: false,
            isSending: false,
            isCompleted: false,
            subject: '',
            editor: null,
            totalCount: <?= (int)($stats['verified'] ?? 0) ?>,
            sentCount: 0,
            batchSize: 20,

            init() {
                this.editor = Jodit.make('#emailEditor', {
                    height: 350
                });
            },

            get percentage() {
                if (this.totalCount === 0) return 100;
                return Math.round((this.sentCount / this.totalCount) * 100);
            },

            openConfirmModal() {
                if (!this.subject || !this.editor.value) {
                    alert('Subject and Content are required.');
                    return;
                }
                if (this.totalCount === 0) {
                    alert('There are no verified subscribers to send to.');
                    return;
                }
                this.isComposeModalOpen = false;
                this.isConfirmModalOpen = true;
            },

            async startCampaign() {
                this.isSending = true;
                this.processBatch(0);
            },

            async processBatch(offset) {
                const formData = new FormData();
                formData.append('action', 'send_batch');
                formData.append('offset', offset);
                formData.append('batch_size', this.batchSize);
                formData.append('subject', this.subject);
                formData.append('content', this.editor.value);
                formData.append('csrf_token', '<?= $_SESSION['csrf_token'] ?>');

                try {
                    const response = await fetch('handlers/newsletter_handler.php', {
                        method: 'POST',
                        body: formData
                    });
                    const result = await response.json();

                    if (result.status === 'success') {
                        this.sentCount += result.sent_in_batch;
                        if (this.sentCount >= this.totalCount) {
                            this.isSending = false;
                            this.isCompleted = true;
                        } else {
                            this.processBatch(this.sentCount);
                        }
                    } else {
                        throw new Error(result.message);
                    }
                } catch (e) {
                    alert('Error: ' + e.message);
                    this.isSending = false;
                    this.isConfirmModalOpen = false;
                }
            }
        }));
    });
</script>

<?php include 'includes/footer.php'; ?>