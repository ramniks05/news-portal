<?php
if (file_exists('../config/install.lock')) {
    header("Location: ../index.php");
    exit();
}

$php_version = phpversion();
$is_php_ok = version_compare($php_version, '7.4.0', '>=');
$is_config_writable = is_writable('../config/');
$is_uploads_writable = is_writable('../uploads/');
$is_htaccess_writable = is_writable('../');

$step = isset($_GET['finish']) ? 'success' : 1;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Wizard - News Portal | Digital Creatorss</title>
    <link rel="stylesheet" href="../assets/src/output.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] {
            display: none !important;
        }

        .custom-gradient {
            background: linear-gradient(135deg, #4f46e5 0%, #1e1b4b 100%);
        }

        .animate-in {
            animation: fadeIn 0.5s ease-out forwards;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>

<body class="bg-slate-50 min-h-screen flex items-center justify-center p-4 md:p-8 font-sans">

    <div class="max-w-3xl w-full bg-white rounded-[2.5rem] shadow-2xl overflow-hidden border border-slate-100 transition-all"
        x-data="installWizard()">

        <div class="custom-gradient p-8 text-white">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-black uppercase tracking-tight">Installation Wizard</h1>
                    <p class="text-indigo-200 text-xs mt-1 font-bold tracking-widest">News Portal by Digital Creatorss</p>
                    <p class="text-indigo-200 text-xs mt-1 font-bold tracking-widest" x-text="getStepTitle()"></p>
                </div>
                <div class="hidden sm:flex gap-2">
                    <template x-for="i in [1,2,3]">
                        <div class="h-2 rounded-full transition-all duration-500"
                            :class="step >= i ? 'w-8 bg-indigo-400' : 'w-2 bg-indigo-900'"></div>
                    </template>
                </div>
            </div>
        </div>

        <div class="p-8 md:p-12">

            <?php if ($step === 'success'): ?>
                <div class="text-center animate-in">
                    <div class="w-20 h-20 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-6 text-3xl">
                        <i class="fa-solid fa-check-double"></i>
                    </div>
                    <h2 class="text-3xl font-black text-slate-800 mb-2">Installation Complete!</h2>
                    <p class="text-slate-500 mb-10">The system has been configured successfully. You can now access your website and admin panel.</p>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <a href="../index.php" class="flex items-center justify-center gap-3 p-5 bg-white border-2 border-slate-100 rounded-2xl hover:border-indigo-500 transition-all group">
                            <i class="fa-solid fa-globe text-2xl text-slate-400 group-hover:text-indigo-600"></i>
                            <div class="text-left">
                                <span class="block font-black text-slate-800 uppercase text-[10px]">Frontend</span>
                                <span class="text-sm font-bold text-slate-500">Visit Website</span>
                            </div>
                        </a>
                        <a href="../admin/login.php" class="flex items-center justify-center gap-3 p-5 bg-indigo-600 rounded-2xl hover:bg-indigo-700 transition-all shadow-xl shadow-indigo-100 group">
                            <i class="fa-solid fa-user-shield text-2xl text-white/80"></i>
                            <div class="text-left text-white">
                                <span class="block font-black text-white/60 uppercase text-[10px]">Backend</span>
                                <span class="text-sm font-bold">Admin Login</span>
                            </div>
                        </a>
                    </div>
                    <div class="mt-8 p-4 bg-red-50 rounded-xl border border-red-100">
                        <p class="text-xs text-red-600 font-bold"><i class="fa-solid fa-triangle-exclamation mr-1"></i> SECURITY ALERT: Please delete the <u>install</u> folder from your server immediately.</p>
                    </div>
                </div>
            <?php else: ?>

                <form action="process.php" method="POST" @submit="handleSubmit">

                    <div x-show="step === 1" class="animate-in space-y-6">
                        <h3 class="text-xl font-bold text-slate-800 mb-4">System Requirements</h3>

                        <div class="space-y-3">
                            <div class="flex items-center justify-between p-4 rounded-2xl <?= $is_php_ok ? 'bg-emerald-50 border-emerald-100' : 'bg-red-50 border-red-100' ?> border">
                                <span class="text-sm font-bold">PHP Version (7.4+)</span>
                                <span class="text-xs font-black uppercase"><?= $php_version ?> <?= $is_php_ok ? '✅' : '❌' ?></span>
                            </div>
                            <div class="flex items-center justify-between p-4 rounded-2xl <?= $is_config_writable ? 'bg-emerald-50 border-emerald-100' : 'bg-red-50 border-red-100' ?> border">
                                <span class="text-sm font-bold">Config Folder Writable</span>
                                <span class="text-xs font-black uppercase"><?= $is_config_writable ? 'Yes' : 'No' ?></span>
                            </div>
                            <div class="flex items-center justify-between p-4 rounded-2xl <?= $is_uploads_writable ? 'bg-emerald-50 border-emerald-100' : 'bg-red-50 border-red-100' ?> border">
                                <span class="text-sm font-bold">Uploads Folder Writable</span>
                                <span class="text-xs font-black uppercase"><?= $is_uploads_writable ? 'Yes' : 'No' ?></span>
                            </div>
                        </div>

                        <?php if (!$is_php_ok || !$is_config_writable || !$is_uploads_writable): ?>
                            <div class="p-4 bg-amber-50 text-amber-800 rounded-xl text-xs leading-relaxed">
                                <i class="fa-solid fa-circle-info mr-1"></i> Please fix the errors above to continue. You might need to set folder permissions (CHMOD) to 755 or 777.
                            </div>
                        <?php else: ?>
                            <div class="mt-8 flex justify-end">
                                <button type="button" @click="step = 2" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-10 rounded-xl transition">Start Installation</button>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div x-show="step === 2" x-cloak class="animate-in space-y-6">
                        <div class="flex items-center justify-between border-b pb-4">
                            <h3 class="text-xl font-bold text-slate-800">Database Configuration</h3>
                            <div class="flex gap-2">
                                <span class="px-2 py-1 bg-slate-100 rounded text-[10px] font-black text-slate-500 uppercase">Step 2 of 3</span>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-[10px] font-black text-slate-500 uppercase mb-2">DB Host</label>
                                <input type="text" name="db_host" x-model="db.host" required class="w-full px-4 py-3 rounded-xl border-2 border-slate-100 focus:border-indigo-500 outline-none transition">
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-slate-500 uppercase mb-2">Database Name</label>
                                <input type="text" name="db_name" x-model="db.name" required class="w-full px-4 py-3 rounded-xl border-2 border-slate-100 focus:border-indigo-500 outline-none transition">
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-slate-500 uppercase mb-2">DB Username</label>
                                <input type="text" name="db_user" x-model="db.user" required class="w-full px-4 py-3 rounded-xl border-2 border-slate-100 focus:border-indigo-500 outline-none transition">
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-slate-500 uppercase mb-2">DB Password</label>
                                <input type="password" name="db_pass" x-model="db.pass" class="w-full px-4 py-3 rounded-xl border-2 border-slate-100 focus:border-indigo-500 outline-none transition">
                            </div>
                        </div>

                        <div class="mt-8 flex justify-between">
                            <button type="button" @click="step = 1" class="text-slate-400 font-bold hover:text-indigo-600 transition">Back</button>
                            <button type="button" @click="validateDbStep" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-10 rounded-xl transition shadow-lg">Next Step</button>
                        </div>
                    </div>
                    <div x-show="step === 3" x-cloak class="animate-in space-y-6">
                        <h3 class="text-xl font-bold text-slate-800 border-b pb-4">Configuration & Admin</h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="md:col-span-2">
                                <label class="block text-[10px] font-black text-slate-500 uppercase mb-2">Website Name</label>
                                <input type="text" name="site_name" x-model="admin.site_name" required placeholder="e.g. My News Portal" class="w-full px-4 py-3 rounded-xl border-2 border-slate-100 focus:border-indigo-500 outline-none transition">
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-[10px] font-black text-slate-500 uppercase mb-2">Database Collation</label>
                                <select name="db_collation" class="w-full px-4 py-3 rounded-xl border-2 border-slate-100 focus:border-indigo-500 outline-none">
                                    <option value="utf8mb4_unicode_ci">utf8mb4_unicode_ci (Most Compatible)</option>
                                    <option value="utf8mb4_general_ci">utf8mb4_general_ci (Fastest)</option>
                                    <option value="utf8mb4_0900_ai_ci">utf8mb4_0900_ai_ci (Recommended for MySQL 8.0+)</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-[10px] font-black text-slate-500 uppercase mb-2">Admin Name</label>
                                <input type="text" name="admin_name" x-model="admin.name" required placeholder="Super Admin" class="w-full px-4 py-3 rounded-xl border-2 border-slate-100 focus:border-indigo-500 outline-none transition">
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-slate-500 uppercase mb-2">Admin Email</label>
                                <input type="email" name="admin_email" x-model="admin.email" required class="w-full px-4 py-3 rounded-xl border-2 border-slate-100 focus:border-indigo-500 outline-none transition">
                            </div>
                            <div class="relative">
                                <label class="block text-[10px] font-black text-slate-500 uppercase mb-2">Password</label>
                                <input :type="showPass ? 'text' : 'password'" name="admin_pass" x-model="admin.pass" required class="w-full px-4 py-3 rounded-xl border-2 border-slate-100 focus:border-indigo-500 outline-none transition">
                                <button type="button" @click="showPass = !showPass" class="absolute right-4 top-10 text-slate-400 hover:text-indigo-600">
                                    <i class="fa-solid" :class="showPass ? 'fa-eye-slash' : 'fa-eye'"></i>
                                </button>
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-slate-500 uppercase mb-2">Confirm Password</label>
                                <input type="password" name="admin_confirm" x-model="admin.confirm" required class="w-full px-4 py-3 rounded-xl border-2 border-slate-100 focus:border-indigo-500 outline-none transition">
                            </div>
                        </div>

                        <div class="mt-8 flex justify-between items-center">
                            <button type="button" @click="step = 2" class="text-slate-400 font-bold hover:text-indigo-600">Back</button>
                            <button type="submit" :disabled="isLoading"
                                class="bg-slate-900 hover:bg-indigo-600 text-white font-bold py-4 px-12 rounded-2xl shadow-2xl transition-all flex items-center gap-3 active:scale-95 disabled:opacity-50">
                                <span x-show="!isLoading">Finish Installation</span>
                                <i x-show="isLoading" class="fa-solid fa-circle-notch fa-spin"></i>
                            </button>
                        </div>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function installWizard() {
            return {
                step: <?= is_numeric($step) ? $step : 4 ?>,
                isLoading: false,
                showPass: false,

                db: {
                    host: 'localhost',
                    name: '',
                    user: '',
                    pass: ''
                },
                admin: {
                    site_name: '',
                    name: '',
                    email: '',
                    pass: '',
                    confirm: ''
                },

                getStepTitle() {
                    const titles = {
                        1: 'Check environment compatibility',
                        2: 'Link your MySQL database',
                        3: 'Setup administrative credentials',
                        'success': 'Software ready for use'
                    };
                    return titles[this.step] || titles['success'];
                },

                validateDbStep() {
                    if (!this.db.name || !this.db.user) {
                        alert('Please fill in Database Name and Username');
                        return;
                    }
                    this.step = 3;
                },

                handleSubmit(e) {
                    if (!this.admin.site_name) {
                        e.preventDefault();
                        alert('Please enter a Website Name');
                        return;
                    }
                    if (this.admin.pass !== this.admin.confirm) {
                        e.preventDefault();
                        alert('Passwords do not match!');
                        return;
                    }
                    if (this.admin.pass.length < 6) {
                        e.preventDefault();
                        alert('Password must be at least 6 characters');
                        return;
                    }
                    this.isLoading = true;
                }
            }
        }
    </script>
</body>

</html>