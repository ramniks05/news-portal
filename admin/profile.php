<?php
require_once 'includes/header.php';
require_once '../config/database.php';

$user_id = $_SESSION['admin_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header("Location: logout.php");
    exit();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<div class="mb-6">
    <h2 class="text-2xl font-bold text-slate-800 dark:text-white">My Profile</h2>
    <p class="text-sm text-slate-500 dark:text-slate-400">Manage your account details and security settings.</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8" x-data="{ activeTab: 'general' }">

    <div class="lg:col-span-1">
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow border border-slate-200 dark:border-slate-700 p-6 text-center">

            <div class="relative inline-block mb-4">
                <?php if ($user['avatar']): ?>
                    <img src="../../<?= htmlspecialchars($user['avatar']) ?>" class="h-32 w-32 rounded-full object-cover border-4 border-slate-100 dark:border-slate-700 shadow-sm">
                <?php else: ?>
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($user['name']) ?>&background=4f46e5&color=fff&size=128" class="h-32 w-32 rounded-full border-4 border-slate-100 dark:border-slate-700 shadow-sm">
                <?php endif; ?>

                <div class="absolute bottom-0 right-0 bg-green-500 h-5 w-5 rounded-full border-2 border-white dark:border-slate-800" title="Online"></div>
            </div>

            <h3 class="text-xl font-bold text-slate-900 dark:text-white"><?= htmlspecialchars($user['name']) ?></h3>
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-4"><?= htmlspecialchars($user['email']) ?></p>

            <div class="flex justify-center gap-2 mb-6">
                <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-indigo-100 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-700">
                    <?= htmlspecialchars($user['role']) ?>
                </span>
            </div>

            <div class="border-t border-slate-100 dark:border-slate-700 pt-4 text-left">
                <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Account Info</p>
                <div class="flex justify-between text-sm mb-2">
                    <span class="text-slate-600 dark:text-slate-400">Joined:</span>
                    <span class="font-medium text-slate-800 dark:text-slate-200"><?= date('M Y', strtotime($user['created_at'])) ?></span>
                </div>
            </div>
        </div>
    </div>

    <div class="lg:col-span-2">
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow border border-slate-200 dark:border-slate-700 overflow-hidden">

            <div class="flex border-b border-slate-200 dark:border-slate-700">
                <button @click="activeTab = 'general'"
                    :class="activeTab === 'general' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400'"
                    class="flex-1 py-4 text-sm font-bold text-center border-b-2 transition-colors">
                    <i class="fa-regular fa-id-card mr-2"></i> General Info
                </button>
                <button @click="activeTab = 'security'"
                    :class="activeTab === 'security' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400'"
                    class="flex-1 py-4 text-sm font-bold text-center border-b-2 transition-colors">
                    <i class="fa-solid fa-lock mr-2"></i> Security
                </button>
            </div>

            <div class="p-6">
                <div x-show="activeTab === 'general'" x-cloak class="animate-fade-in">
                    <form action="handlers/profile_handler.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="update_info">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                        <div class="space-y-5">
                            <div>
                                <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Full Name</label>
                                <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required
                                    class="w-full rounded-md border border-slate-400 px-4 py-2.5 text-sm dark:bg-slate-900 dark:border-slate-600 dark:text-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Email Address</label>
                                <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required
                                    class="w-full rounded-md border border-slate-400 px-4 py-2.5 text-sm bg-slate-100 dark:bg-slate-900 dark:border-slate-600 dark:text-slate-400 cursor-not-allowed" readonly title="Contact Super Admin to change email">
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Short Bio</label>
                                <textarea name="bio" rows="4" class="w-full rounded-md border border-slate-400 px-4 py-2.5 text-sm dark:bg-slate-900 dark:border-slate-600 dark:text-white"><?= htmlspecialchars($user['bio']) ?></textarea>
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Update Avatar</label>
                                <input type="file" name="avatar" accept="image/*" class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-slate-700 dark:file:text-white">
                            </div>

                            <div class="pt-4 flex justify-end">
                                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2.5 px-6 rounded-md shadow transition">
                                    Save Changes
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <div x-show="activeTab === 'security'" x-cloak class="animate-fade-in">
                    <form action="handlers/profile_handler.php" method="POST">
                        <input type="hidden" name="action" value="change_password">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                        <div class="bg-amber-50 dark:bg-slate-900 border border-amber-200 dark:border-slate-700 rounded-md p-4 mb-6">
                            <p class="text-sm text-amber-800 dark:text-amber-500 font-medium">
                                <i class="fa-solid fa-shield-halved mr-2"></i> Security Note
                            </p>
                            <p class="text-xs text-slate-600 dark:text-slate-400 mt-1">
                                Choose a strong password. You will be logged out after a successful password change.
                            </p>
                        </div>

                        <div class="space-y-5" x-data="{ showOld: false, showNew: false }">

                            <div class="relative">
                                <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Current Password</label>
                                <input :type="showOld ? 'text' : 'password'" name="current_password" required
                                    class="w-full rounded-md border border-slate-400 px-4 py-2.5 text-sm pr-10 dark:bg-slate-900 dark:border-slate-600 dark:text-white">
                                <button type="button" @click="showOld = !showOld" class="absolute right-3 top-8 text-slate-400 hover:text-indigo-600">
                                    <i class="fa-solid" :class="showOld ? 'fa-eye-slash' : 'fa-eye'"></i>
                                </button>
                            </div>

                            <hr class="border-slate-200 dark:border-slate-700">

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div class="relative">
                                    <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">New Password</label>
                                    <input :type="showNew ? 'text' : 'password'" name="new_password" required minlength="8"
                                        class="w-full rounded-md border border-slate-400 px-4 py-2.5 text-sm pr-10 dark:bg-slate-900 dark:border-slate-600 dark:text-white">
                                    <button type="button" @click="showNew = !showNew" class="absolute right-3 top-8 text-slate-400 hover:text-indigo-600">
                                        <i class="fa-solid" :class="showNew ? 'fa-eye-slash' : 'fa-eye'"></i>
                                    </button>
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Confirm Password</label>
                                    <input type="password" name="confirm_password" required minlength="8"
                                        class="w-full rounded-md border border-slate-400 px-4 py-2.5 text-sm dark:bg-slate-900 dark:border-slate-600 dark:text-white">
                                </div>
                            </div>

                            <div class="pt-4 flex justify-end">
                                <button type="submit" class="bg-slate-900 hover:bg-slate-700 text-white font-bold py-2.5 px-6 rounded-md shadow transition">
                                    Update Password
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>

</div>

<?php include 'includes/footer.php'; ?>