<?php
require_once 'includes/header.php';
require_once '../config/database.php';

$stats = [
    'posts' => $conn->query("SELECT COUNT(*) FROM posts")->fetchColumn(),
    'views' => $conn->query("SELECT SUM(views) FROM posts")->fetchColumn() ?? 0,
    'comments' => $conn->query("SELECT COUNT(*) FROM comments WHERE status = 'pending'")->fetchColumn(),
    'users' => $conn->query("SELECT COUNT(*) FROM newsletter_subscribers WHERE status = 'active'")->fetchColumn()
];

$recent_posts = $conn->query("
    SELECT p.id, p.title, p.status, p.views, p.created_at, c.name as category 
    FROM posts p 
    LEFT JOIN categories c ON p.category_id = c.id 
    ORDER BY p.created_at DESC LIMIT 5
")->fetchAll();

$trending_posts = $conn->query("
    SELECT title, views FROM posts 
    WHERE status = 'published' 
    ORDER BY views DESC LIMIT 5
")->fetchAll();

$cat_dist = $conn->query("
    SELECT c.name, COUNT(p.id) as count 
    FROM categories c 
    LEFT JOIN posts p ON c.id = p.category_id 
    GROUP BY c.id 
    HAVING count > 0
")->fetchAll();

$cat_labels = json_encode(array_column($cat_dist, 'name'));
$cat_data = json_encode(array_column($cat_dist, 'count'));

$hour = date('H');
$greeting = ($hour < 12) ? "Good Morning" : (($hour < 17) ? "Good Afternoon" : "Good Evening");
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="space-y-6 animate-fade-in">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-800 dark:text-white">
                <?= $greeting ?>, <?= htmlspecialchars($_SESSION['admin_name'] ?? 'Admin') ?> 👋
            </h2>
            <p class="text-sm text-slate-500 dark:text-slate-400">Here's what's happening with your news portal today.</p>
        </div>
        <div class="flex gap-3">
            <a href="add-post.php" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-md shadow transition-colors">
                <i class="fa-solid fa-pen-nib mr-2"></i> Write Post
            </a>
            <a href="<?= BASE_URL ?>" target="_blank" class="inline-flex items-center px-4 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-200 text-sm font-medium rounded-md shadow-sm hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                <i class="fa-solid fa-globe mr-2"></i> View Site
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">

        <div class="bg-white dark:bg-slate-800 p-6 rounded-lg shadow-sm border-l-4 border-indigo-500 flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Articles</p>
                <h3 class="text-2xl font-bold text-slate-800 dark:text-white mt-1"><?= number_format($stats['posts']) ?></h3>
            </div>
            <div class="h-10 w-10 rounded-full bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                <i class="fa-regular fa-newspaper text-lg"></i>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 p-6 rounded-lg shadow-sm border-l-4 border-emerald-500 flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Reads</p>
                <h3 class="text-2xl font-bold text-slate-800 dark:text-white mt-1"><?= number_format($stats['views']) ?></h3>
            </div>
            <div class="h-10 w-10 rounded-full bg-emerald-50 dark:bg-emerald-900/30 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                <i class="fa-solid fa-chart-line text-lg"></i>
            </div>
        </div>

        <a href="comments.php" class="bg-white dark:bg-slate-800 p-6 rounded-lg shadow-sm border-l-4 border-amber-500 flex items-center justify-between hover:shadow-md transition">
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Pending Comments</p>
                <h3 class="text-2xl font-bold text-slate-800 dark:text-white mt-1"><?= number_format($stats['comments']) ?></h3>
            </div>
            <div class="h-10 w-10 rounded-full bg-amber-50 dark:bg-amber-900/30 flex items-center justify-center text-amber-600 dark:text-amber-400">
                <i class="fa-regular fa-comments text-lg"></i>
            </div>
        </a>

        <div class="bg-white dark:bg-slate-800 p-6 rounded-lg shadow-sm border-l-4 border-rose-500 flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Subscribers</p>
                <h3 class="text-2xl font-bold text-slate-800 dark:text-white mt-1"><?= number_format($stats['users']) ?></h3>
            </div>
            <div class="h-10 w-10 rounded-full bg-rose-50 dark:bg-rose-900/30 flex items-center justify-center text-rose-600 dark:text-rose-400">
                <i class="fa-regular fa-envelope text-lg"></i>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <div class="bg-white dark:bg-slate-800 p-6 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 lg:col-span-2">
            <h3 class="font-bold text-slate-800 dark:text-white mb-4">Content Distribution</h3>
            <div class="relative h-64 w-full">
                <canvas id="categoryChart"></canvas>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 p-6 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700">
            <h3 class="font-bold text-slate-800 dark:text-white mb-4 flex items-center gap-2">
                <i class="fa-solid fa-fire text-red-500"></i> Trending Now
            </h3>
            <div class="space-y-4">
                <?php if (count($trending_posts) > 0): ?>
                    <?php foreach ($trending_posts as $index => $post): ?>
                        <div class="flex items-start gap-3">
                            <span class="text-2xl font-bold text-slate-200 dark:text-slate-700 leading-none">0<?= $index + 1 ?></span>
                            <div>
                                <h4 class="text-sm font-medium text-slate-800 dark:text-slate-200 line-clamp-2" title="<?= htmlspecialchars($post['title']) ?>">
                                    <?= htmlspecialchars($post['title']) ?>
                                </h4>
                                <p class="text-xs text-slate-500 mt-1"><?= number_format($post['views']) ?> reads</p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-sm text-slate-500 italic">No trending data yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center">
            <h3 class="font-bold text-slate-800 dark:text-white">Recently Published</h3>
            <a href="manage-posts.php" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">View All</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-slate-600 dark:text-slate-400">
                <thead class="text-xs text-slate-700 uppercase bg-slate-50 dark:bg-slate-700 dark:text-slate-200">
                    <tr>
                        <th class="px-6 py-3">Title</th>
                        <th class="px-6 py-3">Category</th>
                        <th class="px-6 py-3">Status</th>
                        <th class="px-6 py-3">Date</th>
                        <th class="px-6 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                    <?php if (count($recent_posts) > 0): ?>
                        <?php foreach ($recent_posts as $post): ?>
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition">
                                <td class="px-6 py-4 font-medium text-slate-900 dark:text-white max-w-xs truncate">
                                    <?= htmlspecialchars($post['title']) ?>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="bg-slate-100 text-slate-600 px-2 py-1 rounded text-xs dark:bg-slate-600 dark:text-slate-300">
                                        <?= htmlspecialchars($post['category'] ?? 'Uncategorized') ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if ($post['status'] === 'published'): ?>
                                        <span class="inline-flex items-center gap-1 rounded-full bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">Published</span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-1 text-xs font-medium text-amber-700 ring-1 ring-inset ring-amber-600/20">Draft</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?= date('M d, Y', strtotime($post['created_at'])) ?>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <a href="edit-post.php?id=<?= $post['id'] ?>" class="text-indigo-600 hover:text-indigo-900"><i class="fa-solid fa-pen-to-square"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-slate-500">No posts found. Start writing!</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('categoryChart').getContext('2d');
        const isDark = document.documentElement.classList.contains('dark');
        const textColor = isDark ? '#cbd5e1' : '#475569';
        const gridColor = isDark ? '#334155' : '#e2e8f0';

        new Chart(ctx, {
            type: 'bar', // Changed to Bar for better readability of categories
            data: {
                labels: <?= $cat_labels ?>,
                datasets: [{
                    label: 'Articles per Category',
                    data: <?= $cat_data ?>,
                    backgroundColor: '#4f46e5',
                    borderRadius: 4,
                    barThickness: 20
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: gridColor
                        },
                        ticks: {
                            color: textColor,
                            stepSize: 1
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: textColor
                        }
                    }
                }
            }
        });
    });
</script>

<?php include 'includes/footer.php'; ?>