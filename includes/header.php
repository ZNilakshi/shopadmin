<?php
// This file is included in every protected page
// $pageTitle must be set before including this file
$pageTitle  = $pageTitle  ?? 'Dashboard';
$activePage = $activePage ?? '';
$userName   = $_SESSION['user_name']    ?? 'User';
$userEmail  = $_SESSION['user_email']   ?? '';
$userPicture= $_SESSION['user_picture'] ?? null;
$userRole   = $_SESSION['user_role']    ?? 'user';

// Low-stock badge (only query once)
require_once 'config.php';
require_once 'classes/Database.php';
require_once 'classes/Product.php';
$_hProd    = new Product();
$_hStats   = $_hProd->getStats();
$_lowStock = $_hStats['low_stock'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en" id="htmlRoot">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> — ShopAdmin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@400;500;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <script>document.documentElement.setAttribute('data-theme', localStorage.getItem('theme') || 'light');</script>
    <style>
    /* ================================================
       HEADER — Deep Charcoal Sidebar + Amber Accents
       Font: Bricolage Grotesque (brand/headings) + DM Sans (body)
       ================================================ */
    :root {
        --sb-w: 258px;
        --sb-bg: #0f0f13;
        --sb-surface: #17171d;
        --sb-border: rgba(255,255,255,.07);
        --sb-text: #6b6b7a;
        --sb-text-hover: #d4d4e0;
        --sb-active-bg: rgba(251,191,36,.08);
        --sb-active-text: #fbbf24;
        --sb-active-border: #fbbf24;
        --top-h: 62px;
        --amber: #fbbf24;
    }
    body { font-family: 'DM Sans', sans-serif; background: var(--body-bg); margin: 0; }
    h1,h2,h3,h4,h5,h6 { font-family: 'Bricolage Grotesque', sans-serif; }
    .main-wrapper { margin-left: var(--sb-w); min-height: 100vh; display: flex; flex-direction: column; }

    /* ── SIDEBAR ── */
    .sb {
        position: fixed; top: 0; left: 0; bottom: 0;
        width: var(--sb-w); background: var(--sb-bg);
        display: flex; flex-direction: column;
        z-index: 1000;
        transition: transform .3s cubic-bezier(.4,0,.2,1);
        overflow: hidden;
    }
    /* Grain texture */
    .sb::before {
        content: ''; position: absolute; inset: 0; pointer-events: none; opacity: .4;
        background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.04'/%3E%3C/svg%3E");
    }
    /* Glow blob */
    .sb::after {
        content: ''; position: absolute; pointer-events: none;
        width: 220px; height: 220px;
        background: radial-gradient(circle, rgba(251,191,36,.07) 0%, transparent 70%);
        top: -60px; left: -60px;
    }

    /* Brand */
    .sb-brand {
        position: relative; z-index: 1;
        display: flex; align-items: center; gap: 11px;
        padding: 22px 20px 18px;
        border-bottom: 1px solid var(--sb-border);
        text-decoration: none;
    }
    .sb-brand-icon {
        width: 34px; height: 34px; border-radius: 10px;
        background: var(--amber); color: #000;
        display: flex; align-items: center; justify-content: center;
        font-size: 1rem; flex-shrink: 0;
        box-shadow: 0 4px 14px rgba(251,191,36,.35);
    }
    .sb-brand-name {
        font-family: 'Bricolage Grotesque', sans-serif;
        font-weight: 800; font-size: 1.05rem;
        color: #fff; letter-spacing: -.02em;
    }
    .sb-brand-name em { font-style: normal; color: var(--amber); }

    /* User card */
    .sb-user {
        position: relative; z-index: 1;
        display: flex; align-items: center; gap: 12px;
        padding: 12px 14px; margin: 10px 10px;
        border-radius: 12px;
        background: var(--sb-surface);
        border: 1px solid var(--sb-border);
    }
    .sb-av {
        width: 38px; height: 38px; border-radius: 10px;
        object-fit: cover; flex-shrink: 0;
    }
    .sb-av-ph {
        width: 38px; height: 38px; border-radius: 10px;
        background: linear-gradient(135deg, #fbbf24, #f59e0b);
        display: flex; align-items: center; justify-content: center;
        color: #000; font-weight: 800; font-size: .9rem;
        font-family: 'Bricolage Grotesque', sans-serif;
        flex-shrink: 0;
    }
    .sb-user-info { min-width: 0; flex: 1; }
    .sb-uname {
        color: #e8e8f0; font-weight: 600; font-size: .82rem;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 128px;
    }
    .sb-urole {
        display: inline-flex; align-items: center; gap: 4px;
        margin-top: 2px; font-size: .63rem; font-weight: 600;
        letter-spacing: .04em; text-transform: uppercase;
    }
    .sb-urole.admin { color: var(--amber); }
    .sb-urole.user  { color: #6b6b7a; }
    .sb-online {
        width: 7px; height: 7px; border-radius: 50%;
        background: #22c55e; margin-left: auto; flex-shrink: 0;
        box-shadow: 0 0 6px #22c55e;
    }

    /* Nav */
    .sb-nav { flex: 1; padding: 8px 10px; overflow-y: auto; scrollbar-width: none; position: relative; z-index: 1; }
    .sb-nav::-webkit-scrollbar { display: none; }
    .sb-sec {
        font-size: .62rem; font-weight: 700; letter-spacing: .12em;
        text-transform: uppercase; color: rgba(255,255,255,.2);
        padding: 14px 10px 5px;
    }
    .sb-link {
        display: flex; align-items: center; gap: 11px;
        padding: 9px 12px; border-radius: 10px;
        color: var(--sb-text); text-decoration: none;
        font-size: .85rem; font-weight: 500;
        transition: all .18s; margin-bottom: 1px; position: relative;
    }
    .sb-link i { font-size: .95rem; flex-shrink: 0; width: 18px; text-align: center; }
    .sb-link:hover { background: rgba(255,255,255,.05); color: var(--sb-text-hover); }
    .sb-link.active { background: var(--sb-active-bg); color: var(--sb-active-text); font-weight: 600; }
    .sb-link.active::before {
        content: ''; position: absolute;
        left: 0; top: 20%; bottom: 20%;
        width: 3px; border-radius: 0 2px 2px 0;
        background: var(--sb-active-border);
        box-shadow: 0 0 8px rgba(251,191,36,.6);
    }
    .sb-link.logout { color: rgba(248,113,113,.45); margin-top: 4px; }
    .sb-link.logout:hover { background: rgba(248,113,113,.08); color: #f87171; }
    .sb-badge {
        margin-left: auto; background: #ef4444; color: #fff;
        font-size: .6rem; font-weight: 700; padding: 1px 6px;
        border-radius: 100px; min-width: 18px; text-align: center;
    }
    .sb-ext { margin-left: auto; font-size: .7rem; opacity: .35; }

    /* Footer */
    .sb-footer {
        position: relative; z-index: 1;
        padding: 12px 18px; border-top: 1px solid var(--sb-border);
        display: flex; align-items: center; justify-content: space-between;
    }
    .sb-footer span { font-size: .67rem; color: rgba(255,255,255,.16); }

    /* Overlay */
    .sb-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.6); z-index: 999; backdrop-filter: blur(2px); }
    .sb-overlay.on { display: block; }

    /* ── MOBILE BAR ── */
    .mob-bar {
        background: var(--sb-bg); height: 56px;
        display: flex; align-items: center;
        padding: 0 16px; gap: 12px;
        position: sticky; top: 0; z-index: 998;
        border-bottom: 1px solid var(--sb-border);
    }
    .mob-toggle {
        width: 36px; height: 36px; border-radius: 9px;
        border: 1px solid var(--sb-border);
        background: var(--sb-surface); color: #aaa;
        display: flex; align-items: center; justify-content: center;
        cursor: pointer; font-size: 1.15rem; flex-shrink: 0;
    }
    .mob-brand {
        font-family: 'Bricolage Grotesque', sans-serif;
        font-weight: 800; font-size: .95rem; color: #fff;
        text-decoration: none; display: flex; align-items: center; gap: 8px; flex: 1;
    }
    .mob-dot {
        width: 8px; height: 8px; border-radius: 50%;
        background: var(--amber); box-shadow: 0 0 8px rgba(251,191,36,.5);
    }

    /* ── TOP BAR (desktop) ── */
    .topbar {
        height: var(--top-h);
        background: var(--card-bg);
        border-bottom: 1px solid var(--border);
        padding: 0 28px;
        display: flex; align-items: center; justify-content: space-between;
        position: sticky; top: 0; z-index: 100;
    }
    .tb-left { display: flex; align-items: center; gap: 8px; }
    .tb-eyebrow { font-size: .68rem; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; color: var(--text-muted); }
    .tb-sep { color: var(--border); }
    .tb-page { font-family: 'Bricolage Grotesque', sans-serif; font-size: .95rem; font-weight: 700; color: var(--text-main); }
    .tb-right { display: flex; align-items: center; gap: 8px; flex-shrink: 0; }

    .top-btn {
        width: 36px; height: 36px; border-radius: 9px;
        border: 1px solid var(--border); background: transparent;
        color: var(--text-muted); display: flex; align-items: center; justify-content: center;
        cursor: pointer; font-size: .92rem; transition: all .18s; text-decoration: none;
    }
    .top-btn:hover { background: var(--body-bg); color: var(--text-main); border-color: var(--text-main); }

    .dark-toggle {
        width: 36px; height: 36px; border-radius: 9px;
        border: 1px solid var(--border); background: transparent;
        color: var(--text-muted); display: flex; align-items: center; justify-content: center;
        cursor: pointer; font-size: .92rem; transition: all .18s;
    }
    .dark-toggle:hover { background: var(--body-bg); color: var(--text-main); border-color: var(--text-main); }

    .top-user {
        display: flex; align-items: center; gap: 9px;
        padding: 5px 12px 5px 5px; border-radius: 100px;
        border: 1px solid var(--border); background: transparent;
        cursor: pointer; transition: all .18s;
        font-size: .83rem; font-weight: 600; color: var(--text-main);
    }
    .top-user:hover { background: var(--body-bg); border-color: var(--text-main); }
    .top-user img { width: 28px; height: 28px; border-radius: 50%; object-fit: cover; }
    .top-user-av {
        width: 28px; height: 28px; border-radius: 50%;
        background: linear-gradient(135deg, #fbbf24, #f59e0b);
        display: flex; align-items: center; justify-content: center;
        font-size: .72rem; font-weight: 800; color: #000;
        font-family: 'Bricolage Grotesque', sans-serif; flex-shrink: 0;
    }
    .top-uname { max-width: 100px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

    /* Dark mode topbar */
    [data-theme="dark"] .topbar { background: #161b22; border-color: #21262d; }
    [data-theme="dark"] .tb-page { color: #e6edf3; }
    [data-theme="dark"] .top-btn,
    [data-theme="dark"] .dark-toggle,
    [data-theme="dark"] .top-user { border-color: #30363d; color: #8b949e; }
    [data-theme="dark"] .top-btn:hover,
    [data-theme="dark"] .dark-toggle:hover,
    [data-theme="dark"] .top-user:hover { background: #21262d; color: #e6edf3; border-color: #484f58; }
    [data-theme="dark"] .top-user { color: #e6edf3; }
    [data-theme="dark"] .dropdown-menu { background: #161b22; border-color: #30363d; }
    [data-theme="dark"] .dropdown-item { color: #c9d1d9; font-size: .85rem; }
    [data-theme="dark"] .dropdown-item:hover { background: #21262d; color: #e6edf3; }
    [data-theme="dark"] .dropdown-divider { border-color: #30363d; }

    /* Responsive */
    @media (max-width: 991.98px) {
        .sb { transform: translateX(-100%); }
        .sb.open { transform: translateX(0); box-shadow: 8px 0 32px rgba(0,0,0,.6); }
        .main-wrapper { margin-left: 0; }
    }
    @media (min-width: 992px) { .mob-bar { display: none !important; } }
    .page-content { flex: 1; padding: 28px; }
    @media (max-width: 576px) { .page-content { padding: 16px; } }
    </style>
</head>
<body>

<!-- ── Mobile Bar ─────────────────────────────── -->
<nav class="mob-bar d-lg-none">
    <button class="mob-toggle" id="sbToggle"><i class="bi bi-list"></i></button>
    <a href="dashboard.php" class="mob-brand">
        <div class="mob-dot"></div>ShopAdmin
    </a>
    <?php if ($userPicture): ?>
        <img src="uploads/<?= htmlspecialchars($userPicture) ?>" class="rounded-circle" width="30" height="30" style="object-fit:cover;border:2px solid rgba(251,191,36,.5)">
    <?php else: ?>
        <div style="width:30px;height:30px;border-radius:50%;background:linear-gradient(135deg,#fbbf24,#f59e0b);display:flex;align-items:center;justify-content:center;font-size:.72rem;font-weight:800;color:#000"><?= strtoupper(substr($userName,0,1)) ?></div>
    <?php endif; ?>
</nav>

<!-- ── Overlay ────────────────────────────────── -->
<div class="sb-overlay" id="sbOverlay"></div>

<!-- ════════════════════════════════════════════
     SIDEBAR
════════════════════════════════════════════ -->
<aside class="sb" id="sidebar">

    <a href="dashboard.php" class="sb-brand">
        <div class="sb-brand-icon"><i class="bi bi-bag-check-fill"></i></div>
        <span class="sb-brand-name">Shop<em>Admin</em></span>
    </a>

    <div class="sb-user">
        <?php if ($userPicture): ?>
            <img src="uploads/<?= htmlspecialchars($userPicture) ?>" class="sb-av" alt="">
        <?php else: ?>
            <div class="sb-av-ph"><?= strtoupper(substr($userName,0,1)) ?></div>
        <?php endif; ?>
        <div class="sb-user-info">
            <div class="sb-uname"><?= htmlspecialchars($userName) ?></div>
            <div class="sb-urole <?= $userRole === 'admin' ? 'admin' : 'user' ?>">
                <i class="bi bi-<?= $userRole === 'admin' ? 'shield-check' : 'person' ?>-fill"></i>
                <?= ucfirst($userRole) ?>
            </div>
        </div>
        <div class="sb-online"></div>
    </div>

    <nav class="sb-nav">
        <div class="sb-sec">Main</div>

        <?php if ($userRole === 'admin'): ?>
        <a href="dashboard.php" class="sb-link <?= $activePage==='dashboard'?'active':'' ?>">
            <i class="bi bi-grid-1x2-fill"></i> Dashboard
        </a>
        <?php endif; ?>

        <a href="products.php" class="sb-link <?= $activePage==='products'?'active':'' ?>">
            <i class="bi bi-boxes"></i>
            <?= $userRole==='admin'?'Products':'My Products' ?>
            <?php if ($_lowStock > 0): ?>
                <span class="sb-badge"><?= $_lowStock ?></span>
            <?php endif; ?>
        </a>

        <a href="add_product.php" class="sb-link <?= $activePage==='add_product'?'active':'' ?>">
            <i class="bi bi-plus-circle-fill"></i> Add Product
        </a>

        <a href="shop.php" class="sb-link" target="_blank">
            <i class="bi bi-shop-window"></i> View Shop
            <i class="bi bi-arrow-up-right sb-ext"></i>
        </a>

        <div class="sb-sec">Account</div>

        <a href="profile.php" class="sb-link <?= $activePage==='profile'?'active':'' ?>">
            <i class="bi bi-person-circle"></i> My Profile
        </a>

        <a href="logout.php" class="sb-link logout">
            <i class="bi bi-box-arrow-right"></i> Sign Out
        </a>
    </nav>

    <div class="sb-footer">
        <span>© <?= date('Y') ?> ShopAdmin</span>
        <span>v1.0</span>
    </div>
</aside>

<!-- ════════════════════════════════════════════
     MAIN WRAPPER
════════════════════════════════════════════ -->
<div class="main-wrapper">

    <header class="topbar d-none d-lg-flex">
        <div class="tb-left">
            <span class="tb-eyebrow">ShopAdmin</span>
            <span class="tb-sep">/</span>
            <span class="tb-page"><?= htmlspecialchars($pageTitle) ?></span>
        </div>

        <div class="tb-right">
            <button class="dark-toggle" id="darkToggle" title="Toggle theme">
                <i class="bi bi-moon-fill"></i>
            </button>
            <a href="shop.php" target="_blank" class="top-btn" title="View Shop">
                <i class="bi bi-shop-window"></i>
            </a>
            <div class="dropdown">
                <button class="top-user dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                    <?php if ($userPicture): ?>
                        <img src="uploads/<?= htmlspecialchars($userPicture) ?>">
                    <?php else: ?>
                        <div class="top-user-av"><?= strtoupper(substr($userName,0,1)) ?></div>
                    <?php endif; ?>
                    <span class="top-uname"><?= htmlspecialchars(explode(' ',$userName)[0]) ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end mt-1" style="min-width:200px;border-radius:12px;padding:6px">
                    <li class="px-3 py-2 border-bottom" style="border-color:var(--border)!important">
                        <div style="font-size:.82rem;font-weight:600;color:var(--text-main)"><?= htmlspecialchars($userName) ?></div>
                        <div style="font-size:.73rem;color:var(--text-muted)"><?= htmlspecialchars($userEmail) ?></div>
                    </li>
                    <li class="mt-1">
                        <a class="dropdown-item rounded-2" href="profile.php">
                            <i class="bi bi-person me-2 opacity-50"></i>Profile
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item rounded-2" href="dashboard.php">
                            <i class="bi bi-speedometer2 me-2 opacity-50"></i>Dashboard
                        </a>
                    </li>
                    <li><hr class="dropdown-divider my-1"></li>
                    <li>
                        <a class="dropdown-item rounded-2 text-danger" href="logout.php">
                            <i class="bi bi-box-arrow-right me-2"></i>Sign Out
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </header>

    <main class="page-content">

<script>
(function(){
    var sb  = document.getElementById('sidebar');
    var ov  = document.getElementById('sbOverlay');
    var tog = document.getElementById('sbToggle');
    if(tog) tog.addEventListener('click', function(){ sb.classList.toggle('open'); ov.classList.toggle('on'); });
    if(ov)  ov.addEventListener('click',  function(){ sb.classList.remove('open'); ov.classList.remove('on'); });
})();
</script>