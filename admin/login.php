<?php
session_start();

if (isset($_SESSION['admin_id'])) {
    header("Location: dashboard.php");
    exit();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Access - Indian News</title>
    <link rel="icon" type="image/svg+xml" href="../assets/images/static/favicon.svg">

    <link rel="stylesheet" href="../assets/src/output.css">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

</head>

<body class="bg-gray-50 h-screen font-sans antialiased overflow-hidden">

    <div class="flex w-full h-full">
        <div class="hidden lg:flex w-1/2 relative bg-indigo-900 items-center justify-center overflow-hidden">
            <div class="absolute inset-0 z-0">
                <img src=""
                    alt="News Background"
                    class="w-full h-full object-cover opacity-30 mix-blend-overlay">
                <div class="absolute inset-0 bg-gradient-to-br from-indigo-900 via-indigo-900/90 to-purple-900/80"></div>
            </div>

            <div class="relative z-10 text-center px-10 animate-fade-in">
                <div class="mb-6 flex justify-center">
                    <div class="h-20 w-20 bg-white/10 backdrop-blur-sm rounded-2xl flex items-center justify-center border border-white/20 shadow-2xl">
                        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"></path>
                        </svg>
                    </div>
                </div>
                <h1 class="text-4xl font-bold text-white mb-4 tracking-tight">News Portal</h1>
                <p class="text-indigo-200 text-sm font-semibold tracking-wide mb-3">by Digital Creatorss</p>
                <p class="text-indigo-200 text-lg max-w-md mx-auto leading-relaxed">
                    Empowering journalism with truth and speed. Welcome back to your editorial workspace.
                </p>

                <div class="mt-10 flex justify-center gap-2">
                    <span class="h-1.5 w-1.5 rounded-full bg-white opacity-50"></span>
                    <span class="h-1.5 w-1.5 rounded-full bg-white opacity-100"></span>
                    <span class="h-1.5 w-1.5 rounded-full bg-white opacity-50"></span>
                </div>
            </div>
        </div>

        <div class="w-full lg:w-1/2 flex items-center justify-center bg-white p-8 sm:p-12 relative">
            <div class="absolute top-8 left-8 lg:hidden">
                <svg class="w-8 h-8 text-indigo-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"></path>
                </svg>
            </div>

            <div class="w-full max-w-md animate-fade-in-up" x-data="{ showPass: false, loading: false }">

                <div class="mb-10">
                    <h2 class="text-3xl font-bold text-slate-900 mb-2">Hello, There! 👋</h2>
                    <p class="text-slate-500 text-sm">Please enter your credentials to access the dashboard.</p>
                </div>

                <?php if (isset($_GET['error'])): ?>
                    <div class="mb-6 p-4 rounded-lg bg-red-50 border-l-4 border-red-500 flex items-start gap-3 animate-pulse">
                        <i class="fa-solid fa-circle-exclamation text-red-500 mt-0.5"></i>
                        <div class="text-sm text-red-700">
                            <?php
                            $errors = [
                                'invalid' => 'The email or password you entered is incorrect.',
                                'csrf' => 'Security token expired. Please refresh the page.',
                                'empty' => 'Email and Password fields cannot be empty.',
                                'unauthorized' => 'Access Denied. You do not have permission.',
                                'timeout' => 'Your session has expired. Please login again.',
                                'system' => 'A system error occurred. Please try again later.'
                            ];
                            echo $errors[$_GET['error']] ?? 'An unknown error occurred.';
                            ?>
                        </div>
                    </div>
                <?php endif; ?>

                <form action="handlers/auth_handler.php" method="POST" @submit="loading = true">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">

                    <div class="mb-6 relative group">
                        <label for="email" class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2 ml-1">Email Address</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400 group-focus-within:text-indigo-600 transition-colors">
                                <i class="fa-regular fa-envelope text-lg"></i>
                            </span>
                            <input type="email" name="email" id="email" required autocomplete="email"
                                class="w-full pl-11 pr-4 py-3.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-800 text-sm font-medium placeholder-slate-400 focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 focus:outline-none transition-all duration-300"
                                placeholder="name@newsportal.com">
                        </div>
                    </div>
                    <div class="mb-8 relative group">
                        <div class="flex justify-between items-center mb-2 ml-1">
                            <label for="password" class="block text-xs font-semibold text-slate-500 uppercase tracking-wider">Password</label>
                        </div>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400 group-focus-within:text-indigo-600 transition-colors">
                                <i class="fa-solid fa-lock-open text-lg"></i>
                            </span>

                            <input :type="showPass ? 'text' : 'password'" name="password" id="password" required autocomplete="current-password"
                                class="w-full pl-11 pr-12 py-3.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-800 text-sm font-medium placeholder-slate-400 focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 focus:outline-none transition-all duration-300"
                                placeholder="••••••••••••">

                            <button type="button" @click="showPass = !showPass"
                                class="absolute inset-y-0 right-0 pr-4 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none cursor-pointer">
                                <i class="fa-regular" :class="showPass ? 'fa-eye-slash' : 'fa-eye'"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" :disabled="loading"
                        class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-4 rounded-xl shadow-lg shadow-indigo-200 hover:shadow-indigo-300 transform hover:-translate-y-0.5 transition-all duration-200 flex justify-center items-center relative overflow-hidden disabled:opacity-70 disabled:cursor-not-allowed">

                        <span x-show="!loading" class="flex items-center gap-2">
                            Secure Login <i class="fa-solid fa-arrow-right-to-bracket"></i>
                        </span>

                        <span x-show="loading" class="flex items-center gap-2" x-cloak>
                            <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Verifying...
                        </span>
                    </button>

                </form>

                <div class="mt-8 text-center">
                    <p class="text-slate-400 text-xs leading-relaxed">
                        &copy; <?= date('Y') ?> News Portal<br>
                        Developed by <a href="https://digitalcreatorss.com" target="_blank" rel="noopener" class="text-indigo-500 hover:underline font-semibold">Digital Creatorss</a><br>
                        <a href="mailto:support@digitalcreatorss.com" class="hover:text-indigo-500">support@digitalcreatorss.com</a>
                    </p>
                </div>

            </div>
        </div>

    </div>

</body>

</html>