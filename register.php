<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/classes/Database.php';
require_once __DIR__ . '/classes/Auth.php';

$auth = new Auth();
$auth->requireGuest(BASE_URL . '/dashboard.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="base-url" content="<?= BASE_URL ?>">
    <title>Register — ShopAdmin</title>
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
        <div class="auth-logo">
            <div class="logo-icon"><i class="bi bi-person-plus-fill"></i></div>
            <h1>Create Account</h1>
            <p>Join ShopAdmin to manage your inventory</p>
        </div>

        <form id="registerForm" novalidate>
            <div class="mb-3">
                <label class="form-label">Full Name</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                    <input type="text" id="name" name="name" class="form-control"
                           placeholder="John Doe" required autocomplete="name">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Email Address</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    <input type="email" id="email" name="email" class="form-control"
                           placeholder="you@example.com" required autocomplete="email">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="password" id="password" name="password" class="form-control"
                           placeholder="Minimum 8 characters" required autocomplete="new-password">
                    <button type="button" class="input-group-text" id="togglePwd" style="cursor:pointer">
                        <i class="bi bi-eye" id="eyeIcon"></i>
                    </button>
                </div>
                <!-- Strength bar -->
                <div id="strengthBar" style="height:3px;border-radius:4px;margin-top:8px;background:var(--border);overflow:hidden">
                    <div id="strengthFill" style="height:100%;width:0;transition:all .3s ease;border-radius:4px"></div>
                </div>
                <div id="strengthLabel" style="font-size:11px;color:var(--text-3);margin-top:3px"></div>
            </div>

            <div class="mb-4">
                <label class="form-label">Confirm Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-shield-lock"></i></span>
                    <input type="password" id="confirm" name="confirm" class="form-control"
                           placeholder="Repeat your password" required>
                </div>
            </div>

            <button type="submit" class="btn-accent w-100 justify-content-center py-2 fs-6" id="registerBtn">
                <i class="bi bi-person-plus"></i> Create Account
            </button>
        </form>

        <div class="text-center mt-4" style="color:var(--text-2);font-size:13px">
            Already have an account?
            <a href="<?= BASE_URL ?>/login.php" class="fw-semibold">Sign in</a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
<script>
document.getElementById('togglePwd').addEventListener('click', () => {
    const pwd  = document.getElementById('password');
    const icon = document.getElementById('eyeIcon');
    pwd.type = pwd.type === 'password' ? 'text' : 'password';
    icon.className = pwd.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
});

// Password strength
document.getElementById('password').addEventListener('input', function () {
    const val = this.value;
    const fill = document.getElementById('strengthFill');
    const label = document.getElementById('strengthLabel');
    let score = 0;
    if (val.length >= 8) score++;
    if (/[A-Z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;
    const widths = ['0%', '25%', '50%', '75%', '100%'];
    const colors = ['', '#f85149', '#d29922', '#58a6ff', '#3fb950'];
    const labels = ['', 'Weak', 'Fair', 'Good', 'Strong'];
    fill.style.width = widths[score];
    fill.style.background = colors[score];
    label.textContent = val.length ? labels[score] : '';
    label.style.color = colors[score];
});

document.getElementById('registerForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn  = document.getElementById('registerBtn');
    const name     = document.getElementById('name').value.trim();
    const email    = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;
    const confirm  = document.getElementById('confirm').value;

    if (!name || !email || !password) { toast.error('All fields are required'); return; }
    if (password !== confirm) { toast.error('Passwords do not match'); return; }
    if (password.length < 8) { toast.error('Password must be at least 8 characters'); return; }

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner" style="width:16px;height:16px;border-width:2px;display:inline-block;vertical-align:middle;margin-right:6px"></span> Creating account...';

    const res = await apiCall('<?= BASE_URL ?>/api/auth.php?action=register', {
        method: 'POST',
        body: JSON.stringify({ name, email, password }),
    });

    if (res.success) {
        toast.success(res.message || 'Account created!');
        setTimeout(() => { window.location.href = '<?= BASE_URL ?>/dashboard.php'; }, 900);
    } else {
        toast.error(res.message || 'Registration failed');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-person-plus"></i> Create Account';
    }
});
</script>
</body>
</html>
