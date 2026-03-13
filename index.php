<?php
require_once 'config.php';
require_once 'classes/Database.php';
require_once 'classes/Auth.php';

$auth = new Auth();
if ($auth->isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ShopAdmin — Sign In</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <script>document.documentElement.setAttribute('data-theme',localStorage.getItem('theme')||'light');</script>
</head>
<body>

<div class="login-page">
    <!-- Dark Mode Toggle (login page) -->
    <div style="position:fixed;top:16px;right:16px;z-index:999">
        <button class="dark-toggle" id="darkToggle" title="Toggle dark mode"><i class="bi bi-moon-fill"></i></button>
    </div>
    <!-- Left Panel -->
    <div class="login-left">
        <div class="login-brand">
            <i class="bi bi-bag-check-fill"></i>
            ShopAdmin
        </div>
        <div class="login-tagline">
            Manage your<br><span>inventory</span><br>with ease.
        </div>
        <p class="login-sub">A complete product management panel built for modern shops.</p>
        <div class="login-features">
            <div class="login-feature"><i class="bi bi-check-circle-fill"></i> Real-time stock tracking</div>
            <div class="login-feature"><i class="bi bi-check-circle-fill"></i> Multi-image product gallery</div>
            <div class="login-feature"><i class="bi bi-check-circle-fill"></i> Category & status filters</div>
            <div class="login-feature"><i class="bi bi-check-circle-fill"></i> Role-based access control</div>
            <div class="login-feature"><i class="bi bi-check-circle-fill"></i> Dashboard analytics & charts</div>
        </div>
    </div>

    <!-- Right Panel -->
    <div class="login-right">
        <div class="auth-box">
            <h3 class="mb-1" style="font-family:'Syne',sans-serif;font-weight:800;">Welcome back </h3>
            <p class="text-muted mb-4">Sign in to your account or create a new one.</p>

            <!-- Tabs -->
            <div class="auth-tab">
                <button class="auth-tab-btn active" data-tab="login">Sign In</button>
                <button class="auth-tab-btn" data-tab="register">Create Account</button>
            </div>

            <!-- Login Panel -->
            <div id="loginPanel" class="auth-panel">
                <form id="loginForm" novalidate>
                    <input type="hidden" name="action" value="login">
                    <div class="mb-3">
                        <label class="form-label">Email address</label>
                        <input type="email" name="email" class="form-control" placeholder="you@example.com" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <div class="position-relative">
                            <input type="password" name="password" id="loginPwd" class="form-control pe-5" placeholder="••••••••" required>
                            <button type="button" class="btn btn-link position-absolute end-0 top-50 translate-middle-y pe-3 text-muted" onclick="togglePwd('loginPwd',this)">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="d-flex align-items-center justify-content-between mb-4">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="remember" id="remember">
                            <label class="form-check-label text-muted" for="remember">
                                <i class="bi bi-shield-check text-accent me-1"></i>Remember me for 30 days
                            </label>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-accent w-100">Sign In</button>
                    <div class="text-center mt-3 text-muted small">
                        Demo: <strong>admin@shop.com</strong> / <strong>password</strong>
                    </div>
                </form>
            </div>

            <!-- Register Panel -->
            <div id="registerPanel" class="auth-panel" style="display:none">
                <form id="registerForm" novalidate>
                    <input type="hidden" name="action" value="register">
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="name" class="form-control" placeholder="John Doe" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email address</label>
                        <input type="email" name="email" class="form-control" placeholder="you@example.com" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <div class="position-relative">
                            <input type="password" name="password" id="regPwd" class="form-control pe-5" placeholder="Min. 6 characters" required>
                            <button type="button" class="btn btn-link position-absolute end-0 top-50 translate-middle-y pe-3 text-muted" onclick="togglePwd('regPwd',this)">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Confirm Password</label>
                        <input type="password" name="confirm_password" class="form-control" placeholder="Repeat password" required>
                    </div>
                    <button type="submit" class="btn btn-accent w-100">Create Account</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Toast -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index:9999">
    <div id="appToast" class="toast align-items-center border-0" role="alert">
        <div class="d-flex">
            <div class="toast-body" id="toastMessage"></div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js"></script>
<script>
initDarkMode();
function togglePwd(id, btn) {
    const input = document.getElementById(id);
    const isText = input.type === 'text';
    input.type = isText ? 'password' : 'text';
    btn.querySelector('i').className = 'bi bi-eye' + (isText ? '' : '-slash');
}
</script>
</body>
</html>
