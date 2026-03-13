<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/classes/Database.php';
require_once __DIR__ . '/classes/Auth.php';

$auth = new Auth();
$auth->requireGuest(BASE_URL . '/dashboard.php');

$pageTitle = 'Login';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="base-url" content="<?= BASE_URL ?>">
    <title>Login — ShopAdmin</title>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🛍️</text></svg>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=IBM+Plex+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>

<div class="auth-page">
    <div class="auth-card">
        <!-- Logo -->
        <div class="auth-logo">
            <div class="logo-icon"><i class="bi bi-shop-window"></i></div>
            <h1>Welcome back</h1>
            <p>Sign in to your ShopAdmin account</p>
        </div>

        <!-- Form -->
        <form id="loginForm" novalidate>
            <div class="mb-3">
                <label class="form-label">Email Address</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    <input type="email" id="email" name="email" class="form-control"
                           placeholder="admin@shop.com" required autocomplete="email">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="password" id="password" name="password" class="form-control"
                           placeholder="••••••••" required autocomplete="current-password">
                    <button type="button" class="input-group-text" id="togglePwd" style="cursor:pointer">
                        <i class="bi bi-eye" id="eyeIcon"></i>
                    </button>
                </div>
            </div>

            <div class="d-flex align-items-center justify-content-between mb-4">
                <div class="form-check" style="margin:0">
                    <input class="form-check-input" type="checkbox" id="remember" name="remember">
                    <label class="form-check-label" for="remember" style="font-size:13px;color:var(--text-2)">
                        Remember me for 30 days
                    </label>
                </div>
            </div>

            <button type="submit" class="btn-accent w-100 justify-content-center py-2 fs-6" id="loginBtn">
                <i class="bi bi-box-arrow-in-right"></i> Sign In
            </button>
        </form>

        <div class="text-center mt-4" style="color:var(--text-2);font-size:13px">
            Don't have an account?
            <a href="<?= BASE_URL ?>/register.php" class="fw-semibold">Create one</a>
        </div>

        <!-- Demo credentials hint -->
        <div style="margin-top:20px;padding:12px;background:var(--surface-3);border-radius:var(--radius-sm);border:1px solid var(--border)">
            <p style="font-size:11px;color:var(--text-2);margin:0 0 4px;font-weight:600;text-transform:uppercase;letter-spacing:.5px">
                Demo Credentials
            </p>
            <p style="font-size:12px;color:var(--text-2);margin:0;font-family:'IBM Plex Mono',monospace">
                Admin: admin@shop.com / password<br>
                User:&nbsp; user@shop.com &nbsp;/ password
            </p>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
<script>
// Toggle password visibility
document.getElementById('togglePwd').addEventListener('click', () => {
    const pwd = document.getElementById('password');
    const icon = document.getElementById('eyeIcon');
    if (pwd.type === 'password') {
        pwd.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        pwd.type = 'password';
        icon.className = 'bi bi-eye';
    }
});

// Login form submit
document.getElementById('loginForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = document.getElementById('loginBtn');
    const email    = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;
    const remember = document.getElementById('remember').checked;

    if (!email || !password) {
        toast.error('Please fill in all fields');
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner" style="width:16px;height:16px;border-width:2px;display:inline-block;vertical-align:middle;margin-right:6px"></span> Signing in...';

    const res = await apiCall('<?= BASE_URL ?>/api/auth.php?action=login', {
        method: 'POST',
        body: JSON.stringify({ email, password, remember }),
    });

    if (res.success) {
        toast.success(res.message || 'Login successful!');
        setTimeout(() => { window.location.href = '<?= BASE_URL ?>/dashboard.php'; }, 800);
    } else {
        toast.error(res.message || 'Login failed');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-box-arrow-in-right"></i> Sign In';
    }
});
</script>
</body>
</html>
