<?php
/* ============================================================
   shop.php — Single-file Shop Homepage
   Config: update DB_ constants below to match your setup
   ============================================================ */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'shop_admin');
define('UPLOAD_URL', 'uploads/');

session_start();

// ── DB Connection ──────────────────────────────────────────
try {
    $pdo = new PDO(
        "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4",
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (PDOException $e) {
    die('<div style="font-family:sans-serif;padding:40px;text-align:center;background:#fff1f2;color:#be123c;border-radius:12px;margin:40px auto;max-width:500px">
        <h3>⚠️ Database Connection Failed</h3>
        <p style="margin-top:10px">Please update the DB_ constants at the top of shop.php</p>
        <code style="font-size:.8rem;color:#6b7280">'.$e->getMessage().'</code>
    </div>');
}

// ── Query Helpers ──────────────────────────────────────────
$search   = trim($_GET['search']   ?? '');
$category = (int)($_GET['category'] ?? 0);
$sort     = $_GET['sort']  ?? 'newest';
$page     = max(1, (int)($_GET['page'] ?? 1));
$limit    = 12;
$offset   = ($page - 1) * $limit;

$sortMap = [
    'newest'    => 'p.created_at DESC',
    'price_asc' => 'p.price ASC',
    'price_desc'=> 'p.price DESC',
    'name'      => 'p.name ASC',
];
$orderBy = $sortMap[$sort] ?? 'p.created_at DESC';

// Build WHERE
$where  = ["p.status = 'active'"];
$params = [];
if ($search)   { $where[] = "(p.name LIKE ? OR p.description LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
if ($category) { $where[] = "p.category_id = ?"; $params[] = $category; }
$whereSQL = "WHERE " . implode(" AND ", $where);

// Fetch products
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM products p $whereSQL");
$countStmt->execute($params);
$total      = (int)$countStmt->fetchColumn();
$totalPages = (int)ceil($total / $limit);

$stmt = $pdo->prepare(
    "SELECT p.*, c.name AS category_name,
     (SELECT image_path FROM product_images WHERE product_id = p.id ORDER BY sort_order ASC LIMIT 1) AS thumbnail
     FROM products p LEFT JOIN categories c ON p.category_id = c.id
     $whereSQL ORDER BY $orderBy LIMIT ? OFFSET ?"
);
$stmt->execute(array_merge($params, [$limit, $offset]));
$products = $stmt->fetchAll();

// Categories with counts
$cats = $pdo->query(
    "SELECT c.id, c.name, COUNT(p.id) as cnt
     FROM categories c
     INNER JOIN products p ON p.category_id = c.id AND p.status = 'active'
     GROUP BY c.id, c.name ORDER BY cnt DESC"
)->fetchAll();

// Stats for hero
$stats = $pdo->query(
    "SELECT
        COUNT(*) as total,
        SUM(CASE WHEN status='active' THEN 1 ELSE 0 END) as active,
        COUNT(DISTINCT category_id) as cats
     FROM products"
)->fetch();

// Featured (hero mosaic)
$featured = $pdo->query(
    "SELECT p.name,
     (SELECT image_path FROM product_images WHERE product_id = p.id ORDER BY sort_order ASC LIMIT 1) AS thumbnail
     FROM products p WHERE p.status='active' AND p.stock > 0
     ORDER BY p.id DESC LIMIT 4"
)->fetchAll();

// Current category name
$catName = '';
if ($category) {
    foreach ($cats as $c) { if ($c['id'] === $category) { $catName = $c['name']; break; } }
}

// Auth check (optional — just shows "Dashboard" link if logged in)
$isLoggedIn = isset($_SESSION['user_id']);
$isAdmin    = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
?>
<!DOCTYPE html>
<html lang="en" id="htmlRoot">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $catName ? htmlspecialchars($catName).' — ' : '' ?>Shop</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;800;900&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<script>document.documentElement.setAttribute('data-theme',localStorage.getItem('shopTheme')||'light');</script>
<style>
/* ================================================
   Single-file Shop — Playfair + Outfit
   Ink & Gold palette — light/dark modes
   ================================================ */
:root{
    --ink:#0a0a0a; --ink-soft:#3d3d3d; --muted:#888;
    --surface:#fff; --bg:#f7f6f3; --bg2:#eeece8;
    --border:#e2dfd9; --gold:#c9a84c; --gold-lt:#f5efd6;
    --r:14px; --nav-h:68px;
    --ease:cubic-bezier(.34,1.2,.64,1);
}
[data-theme=dark]{
    --ink:#f0ece3; --ink-soft:#b0a89a; --muted:#666;
    --surface:#1a1916; --bg:#111109; --bg2:#1e1c18;
    --border:#2e2c27; --gold:#d4aa55; --gold-lt:#2a2415;
}
*{box-sizing:border-box;margin:0;padding:0;}
html{scroll-behavior:smooth;}
body{font-family:'Outfit',sans-serif;background:var(--bg);color:var(--ink);min-height:100vh;}
h1,h2,h3,.pf{font-family:'Playfair Display',serif;}
a{color:inherit;text-decoration:none;}
img{display:block;}

/* ── NAV ── */
.nav{
    position:sticky;top:0;z-index:200;height:var(--nav-h);
    background:var(--surface);border-bottom:1px solid var(--border);
    display:flex;align-items:center;gap:20px;
    padding:0 clamp(16px,4vw,56px);
}
.nav-brand{
    font-family:'Playfair Display',serif;font-size:1.5rem;
    font-weight:900;letter-spacing:-.02em;white-space:nowrap;
}
.nav-brand span{color:var(--gold);}
.nav-search{flex:1;max-width:400px;position:relative;}
.nav-search i{
    position:absolute;left:14px;top:50%;transform:translateY(-50%);
    color:var(--muted);font-size:.9rem;pointer-events:none;
}
.nav-search input{
    width:100%;padding:9px 14px 9px 38px;
    border:1.5px solid var(--border);border-radius:100px;
    background:var(--bg);color:var(--ink);
    font-family:'Outfit',sans-serif;font-size:.88rem;outline:none;
    transition:border-color .2s,box-shadow .2s;
}
.nav-search input::placeholder{color:var(--muted);}
.nav-search input:focus{border-color:var(--gold);box-shadow:0 0 0 3px rgba(201,168,76,.12);}
.nav-end{display:flex;align-items:center;gap:10px;margin-left:auto;}
.icon-btn{
    width:38px;height:38px;border-radius:50%;
    border:1.5px solid var(--border);background:transparent;color:var(--ink-soft);
    display:flex;align-items:center;justify-content:center;
    cursor:pointer;font-size:1rem;transition:all .2s;
}
.icon-btn:hover{background:var(--ink);color:var(--surface);border-color:var(--ink);}
.btn-pill{
    padding:8px 20px;border-radius:100px;
    background:var(--ink);color:var(--surface);
    font-family:'Outfit',sans-serif;font-weight:600;
    font-size:.82rem;border:none;cursor:pointer;
    transition:all .2s;white-space:nowrap;
}
.btn-pill:hover{background:var(--gold);color:var(--ink);}
.cart-wrap{position:relative;}
.cart-dot{
    position:absolute;top:-4px;right:-4px;
    width:17px;height:17px;border-radius:50%;
    background:var(--gold);color:var(--ink);
    font-size:.6rem;font-weight:700;
    display:flex;align-items:center;justify-content:center;
    opacity:0;transform:scale(0);
    transition:all .3s var(--ease);
}
.cart-dot.on{opacity:1;transform:scale(1);}

/* ── HERO ── */
.hero{
    background:var(--ink);
    padding:70px clamp(20px,5vw,80px) 64px;
    display:grid;grid-template-columns:1fr 1fr;
    gap:48px;align-items:center;
    position:relative;overflow:hidden;min-height:400px;
}
.hero::before{
    content:'';position:absolute;inset:0;pointer-events:none;
    background:
        radial-gradient(ellipse 55% 75% at 85% 50%,rgba(201,168,76,.2) 0%,transparent 70%),
        radial-gradient(ellipse 35% 55% at 8% 85%,rgba(201,168,76,.09) 0%,transparent 60%);
}
.hero-body{position:relative;z-index:1;}
.eyebrow{
    font-family:'Outfit',sans-serif;font-size:.72rem;font-weight:600;
    letter-spacing:.18em;text-transform:uppercase;color:var(--gold);
    display:flex;align-items:center;gap:10px;margin-bottom:18px;
}
.eyebrow::before{content:'';width:28px;height:1.5px;background:var(--gold);}
.hero h1{
    font-size:clamp(2.2rem,4.5vw,3.8rem);font-weight:900;
    color:#fff;line-height:1.08;letter-spacing:-.03em;margin-bottom:18px;
}
.hero h1 em{font-style:italic;color:var(--gold);}
.hero-sub{color:#aaa;font-size:.95rem;line-height:1.75;max-width:400px;margin-bottom:28px;}
.hero-cta{
    display:inline-flex;align-items:center;gap:10px;
    padding:14px 28px;border-radius:100px;
    background:var(--gold);color:var(--ink);
    font-weight:700;font-size:.95rem;
    transition:all .25s;
}
.hero-cta:hover{background:#fff;color:var(--ink);transform:translateY(-2px);}
.hero-cta i{transition:transform .2s;}
.hero-cta:hover i{transform:translateX(4px);}
.hero-nums{
    display:flex;gap:28px;margin-top:32px;
    padding-top:28px;border-top:1px solid rgba(255,255,255,.1);
}
.hero-num .v{
    font-family:'Playfair Display',serif;font-size:1.75rem;
    font-weight:800;color:#fff;line-height:1;
}
.hero-num .l{font-size:.72rem;color:#777;margin-top:4px;letter-spacing:.05em;}
.mosaic{
    position:relative;z-index:1;
    display:grid;grid-template-columns:1fr 1fr;
    grid-template-rows:185px 120px;gap:10px;
}
.mc{
    border-radius:12px;overflow:hidden;
    background:rgba(255,255,255,.06);
    border:1px solid rgba(255,255,255,.07);
}
.mc.tall{grid-row:span 2;}
.mc img{width:100%;height:100%;object-fit:cover;opacity:.82;}
.mc-ph{
    width:100%;height:100%;
    display:flex;align-items:center;justify-content:center;
    color:rgba(255,255,255,.18);font-size:2rem;
}

/* ── CAT STRIP ── */
.cat-strip{
    padding:20px clamp(16px,4vw,56px);
    display:flex;align-items:center;gap:8px;
    overflow-x:auto;scrollbar-width:none;
    border-bottom:1px solid var(--border);background:var(--surface);
}
.cat-strip::-webkit-scrollbar{display:none;}
.chip{
    flex-shrink:0;padding:7px 16px;border-radius:100px;
    border:1.5px solid var(--border);background:transparent;
    color:var(--ink-soft);font-family:'Outfit',sans-serif;
    font-size:.8rem;font-weight:500;cursor:pointer;
    text-decoration:none;transition:all .2s;white-space:nowrap;
    display:inline-flex;align-items:center;gap:5px;
}
.chip:hover{border-color:var(--gold);color:var(--gold);}
.chip.on{background:var(--ink);color:var(--surface);border-color:var(--ink);}
.chip .n{
    font-size:.68rem;
    background:rgba(255,255,255,.15);
    padding:1px 5px;border-radius:100px;
}
.chip:not(.on) .n{background:var(--bg2);}

/* ── MAIN ── */
.main{padding:36px clamp(16px,4vw,56px);max-width:1440px;margin:0 auto;}
.toolbar{
    display:flex;align-items:center;justify-content:space-between;
    margin-bottom:28px;flex-wrap:wrap;gap:12px;
}
.toolbar-title{
    font-family:'Playfair Display',serif;font-size:1.3rem;font-weight:700;
}
.toolbar-title small{
    font-family:'Outfit',sans-serif;font-size:.82rem;
    font-weight:400;color:var(--muted);margin-left:8px;
}
.sort-sel{
    padding:8px 16px;border-radius:100px;
    border:1.5px solid var(--border);background:var(--surface);
    color:var(--ink);font-family:'Outfit',sans-serif;font-size:.83rem;
    outline:none;cursor:pointer;
}
.sort-sel:focus{border-color:var(--gold);}

/* ── GRID ── */
.grid{
    display:grid;
    grid-template-columns:repeat(auto-fill,minmax(250px,1fr));
    gap:22px;
}
.card{
    background:var(--surface);border-radius:var(--r);
    border:1px solid var(--border);overflow:hidden;
    transition:transform .3s var(--ease),box-shadow .3s;
    cursor:pointer;position:relative;
}
.card:hover{transform:translateY(-5px);box-shadow:0 18px 44px rgba(0,0,0,.11);}
.card:hover .cact{opacity:1;transform:translateY(0);}
.card:hover .cimg img{transform:scale(1.06);}
.cimg{
    position:relative;aspect-ratio:4/3;
    overflow:hidden;background:var(--bg2);
}
.cimg img{width:100%;height:100%;object-fit:cover;transition:transform .5s ease;}
.cimg-ph{
    width:100%;height:100%;display:flex;align-items:center;
    justify-content:center;color:var(--muted);font-size:2.8rem;
    background:linear-gradient(135deg,var(--bg2),var(--border));
}
.cat-pill{
    position:absolute;top:11px;left:11px;
    padding:3px 10px;border-radius:100px;
    background:rgba(255,255,255,.92);backdrop-filter:blur(6px);
    font-size:.68rem;font-weight:600;color:var(--ink-soft);
    border:1px solid rgba(0,0,0,.05);
}
[data-theme=dark] .cat-pill{background:rgba(20,18,14,.85);color:var(--muted);}
.low-pill{
    position:absolute;top:11px;right:11px;
    padding:3px 9px;border-radius:100px;
    background:#fff3cd;color:#856404;
    font-size:.65rem;font-weight:700;
}
.cact{
    position:absolute;bottom:0;left:0;right:0;
    padding:9px;display:flex;gap:7px;
    opacity:0;transform:translateY(7px);
    transition:all .22s;
}
.btn-qv{
    flex:1;padding:9px;border-radius:8px;
    background:rgba(10,10,10,.82);backdrop-filter:blur(8px);
    color:#fff;border:none;font-family:'Outfit',sans-serif;
    font-size:.8rem;font-weight:600;cursor:pointer;
    transition:background .18s;
}
.btn-qv:hover{background:var(--gold);color:var(--ink);}
.btn-wl{
    width:38px;border-radius:8px;
    background:rgba(255,255,255,.92);border:none;
    color:var(--ink-soft);cursor:pointer;
    display:flex;align-items:center;justify-content:center;
    font-size:.95rem;transition:all .2s;
}
.btn-wl:hover,.btn-wl.on{color:#e11d48;background:#fff1f2;}
.cbody{padding:15px 16px 17px;}
.c-cat{
    font-size:.68rem;font-weight:600;color:var(--gold);
    letter-spacing:.08em;text-transform:uppercase;margin-bottom:5px;
}
.c-name{
    font-family:'Playfair Display',serif;font-size:1rem;font-weight:700;
    color:var(--ink);line-height:1.3;margin-bottom:10px;
    display:-webkit-box;-webkit-line-clamp:2;
    -webkit-box-orient:vertical;overflow:hidden;
}
.c-pr-row{display:flex;align-items:center;justify-content:space-between;margin-top:4px;}
.c-price{
    font-family:'Playfair Display',serif;font-size:1.25rem;font-weight:800;
}
.btn-add{
    width:34px;height:34px;border-radius:50%;
    background:var(--ink);color:var(--surface);
    border:none;cursor:pointer;
    display:flex;align-items:center;justify-content:center;
    font-size:.9rem;transition:all .25s var(--ease);
}
.btn-add:hover{background:var(--gold);color:var(--ink);transform:scale(1.15);}
.btn-add.ok{background:#16a34a;animation:pop .4s;}
@keyframes pop{0%{transform:scale(1)}50%{transform:scale(1.3)}100%{transform:scale(1)}}
.stk-bar{margin-top:9px;}
.stk-lbl{
    display:flex;justify-content:space-between;
    font-size:.67rem;color:var(--muted);margin-bottom:3px;
}
.stk-track{height:3px;background:var(--border);border-radius:2px;overflow:hidden;}
.stk-fill{height:100%;background:var(--gold);border-radius:2px;}

/* ── EMPTY ── */
.empty{text-align:center;padding:80px 20px;}
.empty i{font-size:3.8rem;color:var(--border);display:block;margin-bottom:18px;}
.empty h5{font-family:'Playfair Display',serif;font-size:1.5rem;margin-bottom:8px;}
.empty p{color:var(--muted);margin-bottom:22px;}
.btn-outline{
    padding:9px 24px;border-radius:100px;
    border:1.5px solid var(--border);background:transparent;
    color:var(--ink);font-family:'Outfit',sans-serif;
    font-weight:600;cursor:pointer;transition:all .2s;text-decoration:none;
    display:inline-block;
}
.btn-outline:hover{background:var(--ink);color:var(--surface);border-color:var(--ink);}

/* ── PAGINATION ── */
.pages{display:flex;justify-content:center;gap:6px;margin-top:52px;flex-wrap:wrap;}
.pg{
    min-width:40px;height:40px;border-radius:100px;
    border:1.5px solid var(--border);background:var(--surface);
    color:var(--ink-soft);font-family:'Outfit',sans-serif;
    font-size:.83rem;font-weight:500;cursor:pointer;
    display:inline-flex;align-items:center;justify-content:center;
    padding:0 13px;text-decoration:none;transition:all .2s;
}
.pg:hover{border-color:var(--gold);color:var(--gold);}
.pg.on{background:var(--ink);color:var(--surface);border-color:var(--ink);}
.pg.off{opacity:.35;pointer-events:none;}

/* ── QUICK VIEW ── */
.qv-bg{
    position:fixed;inset:0;background:rgba(0,0,0,.55);
    backdrop-filter:blur(4px);z-index:500;
    opacity:0;pointer-events:none;transition:opacity .25s;
}
.qv-bg.on{opacity:1;pointer-events:all;}
.qv-sheet{
    position:fixed;bottom:0;left:50%;
    transform:translateX(-50%) translateY(105%);
    width:min(860px,98vw);max-height:90vh;overflow-y:auto;
    background:var(--surface);border-radius:20px 20px 0 0;
    z-index:501;transition:transform .35s cubic-bezier(.32,1,.4,1);
}
.qv-sheet.on{transform:translateX(-50%) translateY(0);}
.qv-handle{width:38px;height:4px;border-radius:2px;background:var(--border);margin:14px auto 0;}
.qv-x{
    position:absolute;top:12px;right:16px;
    width:30px;height:30px;border-radius:50%;
    background:var(--bg);border:1px solid var(--border);
    color:var(--muted);cursor:pointer;
    display:flex;align-items:center;justify-content:center;
    font-size:1.05rem;transition:all .2s;
}
.qv-x:hover{background:var(--ink);color:#fff;border-color:var(--ink);}
.qv-in{padding:22px 30px 32px;display:grid;grid-template-columns:1fr 1fr;gap:32px;}
.qv-photo{border-radius:12px;overflow:hidden;aspect-ratio:1;background:var(--bg2);}
.qv-photo img{width:100%;height:100%;object-fit:cover;}
.qv-photo-ph{
    width:100%;height:100%;display:flex;align-items:center;
    justify-content:center;color:var(--muted);font-size:5rem;
}
.qv-cat-l{
    font-size:.7rem;font-weight:600;letter-spacing:.1em;
    text-transform:uppercase;color:var(--gold);margin-bottom:8px;
}
.qv-name{
    font-family:'Playfair Display',serif;
    font-size:1.75rem;font-weight:800;line-height:1.2;margin-bottom:10px;
}
.qv-price{
    font-family:'Playfair Display',serif;
    font-size:2.1rem;font-weight:900;margin-bottom:14px;
}
.qv-desc{color:var(--ink-soft);font-size:.9rem;line-height:1.75;margin-bottom:18px;}
.qv-grid{display:grid;grid-template-columns:1fr 1fr;gap:9px;margin-bottom:22px;}
.qv-box{
    background:var(--bg);border-radius:10px;
    padding:11px 13px;border:1px solid var(--border);
}
.qv-box-l{font-size:.65rem;color:var(--muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:3px;}
.qv-box-v{font-weight:700;font-size:.92rem;}
.btn-lg{
    width:100%;padding:13px;border-radius:100px;
    background:var(--ink);color:var(--surface);
    font-family:'Outfit',sans-serif;font-weight:700;font-size:.93rem;
    border:none;cursor:pointer;transition:all .2s;
    display:flex;align-items:center;justify-content:center;gap:9px;
}
.btn-lg:hover{background:var(--gold);color:var(--ink);}
.btn-lg-out{
    width:100%;padding:11px;border-radius:100px;
    border:1.5px solid var(--border);background:transparent;
    color:var(--ink-soft);font-family:'Outfit',sans-serif;
    font-weight:600;font-size:.83rem;cursor:pointer;
    transition:all .2s;margin-top:9px;text-decoration:none;
    display:flex;align-items:center;justify-content:center;gap:7px;
}
.btn-lg-out:hover{border-color:var(--gold);color:var(--gold);}

/* ── CART DRAWER ── */
.cart-bg{
    position:fixed;inset:0;background:rgba(0,0,0,.45);
    z-index:600;opacity:0;pointer-events:none;transition:opacity .25s;
}
.cart-bg.on{opacity:1;pointer-events:all;}
.cart-dr{
    position:fixed;top:0;right:0;bottom:0;
    width:min(390px,100vw);background:var(--surface);
    z-index:601;transform:translateX(100%);
    transition:transform .35s cubic-bezier(.32,1,.4,1);
    display:flex;flex-direction:column;
    border-left:1px solid var(--border);
}
.cart-dr.on{transform:translateX(0);}
.cart-hd{
    padding:18px 22px;border-bottom:1px solid var(--border);
    display:flex;align-items:center;justify-content:space-between;
}
.cart-hd h5{font-family:'Playfair Display',serif;font-size:1.2rem;font-weight:800;margin:0;}
.cart-body{flex:1;overflow-y:auto;padding:14px 22px;}
.ci{display:flex;gap:13px;padding:13px 0;border-bottom:1px solid var(--border);align-items:flex-start;}
.ci-img{
    width:64px;height:64px;border-radius:9px;
    overflow:hidden;background:var(--bg2);flex-shrink:0;
}
.ci-img img{width:100%;height:100%;object-fit:cover;}
.ci-img-ph{width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:var(--muted);font-size:1.3rem;}
.ci-name{font-weight:600;font-size:.87rem;line-height:1.3;margin-bottom:3px;}
.ci-price{color:var(--gold);font-weight:700;font-size:.87rem;}
.ci-qty{display:flex;align-items:center;gap:7px;margin-top:7px;}
.qb{
    width:24px;height:24px;border-radius:50%;
    border:1px solid var(--border);background:var(--bg);
    cursor:pointer;font-size:.85rem;font-weight:700;
    display:flex;align-items:center;justify-content:center;color:var(--ink);
    transition:all .2s;
}
.qb:hover{background:var(--ink);color:#fff;border-color:var(--ink);}
.ci-rm{
    margin-left:auto;cursor:pointer;color:var(--muted);
    font-size:.95rem;transition:color .2s;
    border:none;background:none;padding:4px;flex-shrink:0;
}
.ci-rm:hover{color:#e11d48;}
.cart-empty-st{text-align:center;padding:56px 20px;}
.cart-empty-st i{font-size:3.2rem;color:var(--border);display:block;margin-bottom:14px;}
.cart-empty-st p{color:var(--muted);}
.cart-ft{padding:18px 22px;border-top:1px solid var(--border);}
.cart-tot{display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;}
.cart-tot-l{font-size:.83rem;color:var(--muted);}
.cart-tot-v{font-family:'Playfair Display',serif;font-size:1.45rem;font-weight:800;}
.btn-co{
    width:100%;padding:13px;border-radius:100px;
    background:var(--gold);color:var(--ink);
    font-family:'Outfit',sans-serif;font-weight:700;font-size:.93rem;
    border:none;cursor:pointer;transition:all .2s;
}
.btn-co:hover{background:var(--ink);color:var(--surface);}

/* ── FOOTER ── */
footer{
    margin-top:72px;padding:44px clamp(16px,4vw,56px) 30px;
    background:var(--ink);
}
.ft-in{
    display:grid;grid-template-columns:2fr 1fr 1fr;
    gap:40px;max-width:1440px;margin:0 auto 36px;
}
.ft-brand{
    font-family:'Playfair Display',serif;font-size:1.4rem;
    font-weight:900;color:#fff;margin-bottom:10px;
}
.ft-brand span{color:var(--gold);}
.ft-desc{color:rgba(255,255,255,.42);line-height:1.72;font-size:.85rem;max-width:280px;}
.ft-col h6{
    font-size:.7rem;font-weight:600;letter-spacing:.1em;
    text-transform:uppercase;color:rgba(255,255,255,.35);margin-bottom:14px;
}
.ft-col a{
    display:block;color:rgba(255,255,255,.45);
    margin-bottom:9px;font-size:.86rem;transition:color .2s;
}
.ft-col a:hover{color:var(--gold);}
.ft-bot{
    max-width:1440px;margin:0 auto;padding-top:22px;
    border-top:1px solid rgba(255,255,255,.07);
    display:flex;justify-content:space-between;
    align-items:center;flex-wrap:wrap;gap:8px;
    color:rgba(255,255,255,.3);font-size:.8rem;
}

/* ── TOAST ── */
.toast-wrap{
    position:fixed;bottom:22px;left:50%;transform:translateX(-50%);
    z-index:800;display:flex;flex-direction:column;
    align-items:center;gap:9px;pointer-events:none;
}
.t{
    padding:11px 20px;border-radius:100px;
    background:var(--ink);color:#fff;
    font-family:'Outfit',sans-serif;font-weight:600;font-size:.85rem;
    display:flex;align-items:center;gap:7px;
    opacity:0;transform:translateY(18px);
    transition:all .3s var(--ease);white-space:nowrap;
}
.t.on{opacity:1;transform:translateY(0);}
.t.gold{background:var(--gold);color:var(--ink);}

/* ── FADE IN ── */
.fi{opacity:0;transform:translateY(18px);animation:fiu .45s forwards;}
@keyframes fiu{to{opacity:1;transform:translateY(0)}}

/* ── RESPONSIVE ── */
@media(max-width:768px){
    .hero{grid-template-columns:1fr;padding:44px 20px 38px;}
    .mosaic{display:none;}
    .hero h1{font-size:2.1rem;}
    .qv-in{grid-template-columns:1fr;}
    .qv-photo{max-height:260px;}
    .ft-in{grid-template-columns:1fr;}
}
@media(max-width:460px){
    .grid{grid-template-columns:repeat(2,1fr);gap:11px;}
    .cbody{padding:10px 11px 13px;}
    .c-name{font-size:.88rem;}
}
</style>
</head>
<body>

<!-- ══ NAVBAR ══════════════════════════════════════════════ -->
<nav class="nav">
    <a href="shop.php" class="nav-brand">Shop<span>.</span></a>

    <form method="GET" class="nav-search">
        <?php if($category): ?><input type="hidden" name="category" value="<?= $category ?>"><?php endif; ?>
        <i class="bi bi-search"></i>
        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search products…" autocomplete="off">
    </form>

    <div class="nav-end">
        <button class="icon-btn" id="themeBtn" title="Toggle theme"><i class="bi bi-moon-fill"></i></button>
        <div class="cart-wrap">
            <button class="icon-btn" id="cartBtn" title="Cart"><i class="bi bi-bag"></i></button>
            <div class="cart-dot" id="cartDot">0</div>
        </div>
        <?php if($isLoggedIn): ?>
            <a href="dashboard.php" class="btn-pill"><i class="bi bi-speedometer2 me-1"></i>Dashboard</a>
        <?php else: ?>
            <a href="index.php" class="btn-pill">Sign In</a>
        <?php endif; ?>
    </div>
</nav>

<!-- ══ HERO ════════════════════════════════════════════════ -->
<?php if(!$search && !$category): ?>
<section class="hero">
    <div class="hero-body">
        <div class="eyebrow">New Collection <?= date('Y') ?></div>
        <h1>Discover <em>Curated</em><br>Products</h1>
        <p class="hero-sub">From electronics to lifestyle — everything you need, beautifully organized and ready to explore.</p>
        <a href="#products" class="hero-cta">Shop Now <i class="bi bi-arrow-right"></i></a>
        <div class="hero-nums">
            <div class="hero-num"><div class="v"><?= $stats['total'] ?>+</div><div class="l">Products</div></div>
            <div class="hero-num"><div class="v"><?= $stats['cats'] ?></div><div class="l">Categories</div></div>
            <div class="hero-num"><div class="v"><?= $stats['active'] ?></div><div class="l">In Stock</div></div>
        </div>
    </div>
    <div class="mosaic">
        <?php foreach(array_pad($featured, 4, null) as $idx => $f): ?>
        <div class="mc <?= $idx===0?'tall':'' ?>">
            <?php if($f && $f['thumbnail']): ?>
                <img src="<?= UPLOAD_URL.htmlspecialchars($f['thumbnail']) ?>" alt="" loading="lazy">
            <?php else: ?>
                <div class="mc-ph"><i class="bi bi-bag-heart"></i></div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- ══ CATEGORY STRIP ═══════════════════════════════════════ -->
<div class="cat-strip">
    <a href="shop.php<?= $search?'?search='.urlencode($search):'' ?>" class="chip <?= !$category?'on':'' ?>">
        <i class="bi bi-grid-3x3-gap-fill"></i> All
    </a>
    <?php foreach($cats as $c): ?>
    <a href="shop.php?category=<?= $c['id'] ?><?= $search?'&search='.urlencode($search):'' ?>"
       class="chip <?= $category==$c['id']?'on':'' ?>">
        <?= htmlspecialchars($c['name']) ?> <span class="n"><?= $c['cnt'] ?></span>
    </a>
    <?php endforeach; ?>
</div>

<!-- ══ PRODUCTS ═════════════════════════════════════════════ -->
<main class="main" id="products">
    <div class="toolbar">
        <div class="toolbar-title">
            <?= $search ? 'Results for "'.htmlspecialchars($search).'"' : ($catName ? htmlspecialchars($catName) : 'All Products') ?>
            <small><?= $total ?> item<?= $total!==1?'s':'' ?></small>
        </div>
        <form method="GET">
            <?php if($search):  ?><input type="hidden" name="search"   value="<?= htmlspecialchars($search) ?>"><?php endif; ?>
            <?php if($category):?><input type="hidden" name="category" value="<?= $category ?>"><?php endif; ?>
            <select class="sort-sel" name="sort" onchange="this.form.submit()">
                <option value="newest"     <?= $sort==='newest'    ?'selected':'' ?>>Newest First</option>
                <option value="price_asc"  <?= $sort==='price_asc' ?'selected':'' ?>>Price: Low → High</option>
                <option value="price_desc" <?= $sort==='price_desc'?'selected':'' ?>>Price: High → Low</option>
                <option value="name"       <?= $sort==='name'      ?'selected':'' ?>>Name A–Z</option>
            </select>
        </form>
    </div>

    <?php if(empty($products)): ?>
    <div class="empty">
        <i class="bi bi-search"></i>
        <h5>No products found</h5>
        <p><?= $search ? 'Try a different keyword or browse by category.' : 'Nothing here yet.' ?></p>
        <a href="shop.php" class="btn-outline">Browse All</a>
    </div>
    <?php else: ?>

    <div class="grid">
        <?php foreach($products as $i => $p): ?>
        <div class="card fi" style="animation-delay:<?= min($i*.05,.45) ?>s"
            data-id="<?= $p['id'] ?>"
            data-name="<?= htmlspecialchars($p['name'],ENT_QUOTES) ?>"
            data-price="<?= $p['price'] ?>"
            data-cat="<?= htmlspecialchars($p['category_name']??'',ENT_QUOTES) ?>"
            data-desc="<?= htmlspecialchars($p['description']??'',ENT_QUOTES) ?>"
            data-stock="<?= $p['stock'] ?>"
            data-thumb="<?= $p['thumbnail'] ? UPLOAD_URL.htmlspecialchars($p['thumbnail'],ENT_QUOTES) : '' ?>">

            <div class="cimg">
                <?php if($p['thumbnail']): ?>
                    <img src="<?= UPLOAD_URL.htmlspecialchars($p['thumbnail']) ?>" alt="<?= htmlspecialchars($p['name']) ?>" loading="lazy">
                <?php else: ?>
                    <div class="cimg-ph"><i class="bi bi-image"></i></div>
                <?php endif; ?>
                <?php if($p['category_name']): ?><div class="cat-pill"><?= htmlspecialchars($p['category_name']) ?></div><?php endif; ?>
                <?php if($p['stock']>0 && $p['stock']<=5): ?><div class="low-pill">Only <?= $p['stock'] ?> left!</div><?php endif; ?>
                <div class="cact">
                    <button class="btn-qv" onclick="openQV(this.closest('.card'))"><i class="bi bi-eye me-1"></i>Quick View</button>
                    <button class="btn-wl" onclick="toggleWL(this);event.stopPropagation()" title="Save"><i class="bi bi-heart"></i></button>
                </div>
            </div>

            <div class="cbody">
                <?php if($p['category_name']): ?><div class="c-cat"><?= htmlspecialchars($p['category_name']) ?></div><?php endif; ?>
                <div class="c-name"><?= htmlspecialchars($p['name']) ?></div>
                <?php if($p['stock']>0 && $p['stock']<=20): ?>
                <div class="stk-bar">
                    <div class="stk-lbl"><span>Stock</span><span><?= $p['stock'] ?></span></div>
                    <div class="stk-track"><div class="stk-fill" style="width:<?= min($p['stock']/20*100,100) ?>%"></div></div>
                </div>
                <?php endif; ?>
                <div class="c-pr-row" style="margin-top:11px">
                    <div class="c-price">$<?= number_format($p['price'],2) ?></div>
                    <button class="btn-add" onclick="addToCart(this.closest('.card'));event.stopPropagation()" title="Add to cart">
                        <i class="bi bi-plus"></i>
                    </button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <?php if($totalPages > 1):
        $q = $_GET; ?>
    <div class="pages">
        <?php $q['page']=$page-1; ?>
        <a href="?<?= http_build_query($q) ?>" class="pg <?= $page<=1?'off':'' ?>"><i class="bi bi-chevron-left"></i></a>
        <?php for($pg=max(1,$page-2);$pg<=min($totalPages,$page+2);$pg++): $q['page']=$pg; ?>
        <a href="?<?= http_build_query($q) ?>" class="pg <?= $pg==$page?'on':'' ?>"><?= $pg ?></a>
        <?php endfor; ?>
        <?php $q['page']=$page+1; ?>
        <a href="?<?= http_build_query($q) ?>" class="pg <?= $page>=$totalPages?'off':'' ?>"><i class="bi bi-chevron-right"></i></a>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</main>

<!-- ══ FOOTER ═══════════════════════════════════════════════ -->
<footer>
    <div class="ft-in">
        <div>
            <div class="ft-brand">Shop<span>.</span></div>
            <p class="ft-desc">A curated collection of quality products, beautifully organized and ready to explore.</p>
        </div>
        <div class="ft-col">
            <h6>Categories</h6>
            <?php foreach(array_slice($cats,0,6) as $c): ?>
            <a href="shop.php?category=<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></a>
            <?php endforeach; ?>
        </div>
        <div class="ft-col">
            <h6>Account</h6>
            <?php if($isLoggedIn): ?>
            <a href="dashboard.php">Dashboard</a>
            <a href="profile.php">My Profile</a>
            <a href="logout.php">Sign Out</a>
            <?php else: ?>
            <a href="index.php">Sign In</a>
            <a href="index.php">Register</a>
            <?php endif; ?>
        </div>
    </div>
    <div class="ft-bot">
        <span>© <?= date('Y') ?> Shop. All rights reserved.</span>
        <span>Powered by ShopAdmin</span>
    </div>
</footer>

<!-- ══ QUICK VIEW ════════════════════════════════════════════ -->
<div class="qv-bg" id="qvBg" onclick="closeQV()"></div>
<div class="qv-sheet" id="qvSheet">
    <div class="qv-handle"></div>
    <button class="qv-x" onclick="closeQV()"><i class="bi bi-x"></i></button>
    <div class="qv-in">
        <div class="qv-photo" id="qvPhoto"></div>
        <div>
            <div class="qv-cat-l" id="qvCat"></div>
            <div class="qv-name"  id="qvName"></div>
            <div class="qv-price" id="qvPrice"></div>
            <div class="qv-desc"  id="qvDesc"></div>
            <div class="qv-grid">
                <div class="qv-box"><div class="qv-box-l">Stock</div><div class="qv-box-v" id="qvStock"></div></div>
                <div class="qv-box"><div class="qv-box-l">Category</div><div class="qv-box-v" id="qvCatV"></div></div>
            </div>
            <button class="btn-lg" id="qvAdd"><i class="bi bi-bag-plus"></i> Add to Cart</button>
            <a href="#" class="btn-lg-out" id="qvLink"><i class="bi bi-box-arrow-up-right"></i> Full Details</a>
        </div>
    </div>
</div>

<!-- ══ CART DRAWER ═══════════════════════════════════════════ -->
<div class="cart-bg" id="cartBg" onclick="closeCart()"></div>
<div class="cart-dr" id="cartDr">
    <div class="cart-hd">
        <h5><i class="bi bi-bag me-2"></i>Cart</h5>
        <button class="icon-btn" onclick="closeCart()"><i class="bi bi-x"></i></button>
    </div>
    <div class="cart-body" id="cartBody"></div>
    <div class="cart-ft" id="cartFt" style="display:none">
        <div class="cart-tot">
            <span class="cart-tot-l">Total</span>
            <span class="cart-tot-v" id="cartTot">$0.00</span>
        </div>
        <button class="btn-co" onclick="toast('Checkout coming soon! 🛒','gold')">Checkout <i class="bi bi-arrow-right ms-1"></i></button>
    </div>
</div>

<!-- ══ TOAST ═════════════════════════════════════════════════ -->
<div class="toast-wrap" id="tw"></div>

<script>
/* ── Cart ───────────────────────────────────── */
let cart = JSON.parse(localStorage.getItem('cart')||'[]');
const saveCart = () => localStorage.setItem('cart', JSON.stringify(cart));
const getItem  = id => cart.find(i=>i.id===id);

function addToCart(card) {
    const id    = +card.dataset.id;
    const stock = +card.dataset.stock;
    const ex    = getItem(id);
    if (ex) { if(ex.qty<stock) ex.qty++; else{toast('No more stock ⚠️');return;} }
    else cart.push({id,name:card.dataset.name,price:+card.dataset.price,thumb:card.dataset.thumb,stock,qty:1});
    saveCart(); updateBadge(); renderCart();
    const btn = card.querySelector('.btn-add');
    if(btn){btn.classList.add('ok');btn.innerHTML='<i class="bi bi-check"></i>';setTimeout(()=>{btn.classList.remove('ok');btn.innerHTML='<i class="bi bi-plus"></i>';},950);}
    toast('Added to cart 🛒','gold');
}

function changeQty(id, d) {
    const it = getItem(id);
    if(!it) return;
    it.qty = Math.max(1, Math.min(it.qty+d, it.stock));
    saveCart(); updateBadge(); renderCart();
}
function removeItem(id) { cart=cart.filter(i=>i.id!==id); saveCart(); updateBadge(); renderCart(); }

function updateBadge() {
    const n = cart.reduce((s,i)=>s+i.qty,0);
    const el = document.getElementById('cartDot');
    el.textContent = n;
    el.classList.toggle('on', n>0);
}

function renderCart() {
    const body = document.getElementById('cartBody');
    const ft   = document.getElementById('cartFt');
    if(!cart.length) {
        body.innerHTML = `<div class="cart-empty-st"><i class="bi bi-bag-x"></i><p>Your cart is empty</p></div>`;
        ft.style.display='none'; return;
    }
    ft.style.display='block';
    let tot=0;
    body.innerHTML = cart.map(it=>{
        tot += it.price*it.qty;
        return `<div class="ci">
            <div class="ci-img">${it.thumb?`<img src="${it.thumb}" alt="">`:`<div class="ci-img-ph"><i class="bi bi-image"></i></div>`}</div>
            <div style="flex:1">
                <div class="ci-name">${it.name}</div>
                <div class="ci-price">$${(it.price*it.qty).toFixed(2)}</div>
                <div class="ci-qty">
                    <button class="qb" onclick="changeQty(${it.id},-1)">−</button>
                    <span style="font-weight:600;font-size:.88rem;min-width:18px;text-align:center">${it.qty}</span>
                    <button class="qb" onclick="changeQty(${it.id},1)">+</button>
                </div>
            </div>
            <button class="ci-rm" onclick="removeItem(${it.id})"><i class="bi bi-trash"></i></button>
        </div>`;
    }).join('');
    document.getElementById('cartTot').textContent = '$'+tot.toFixed(2);
}

function openCart()  { document.getElementById('cartDr').classList.add('on'); document.getElementById('cartBg').classList.add('on'); renderCart(); }
function closeCart() { document.getElementById('cartDr').classList.remove('on'); document.getElementById('cartBg').classList.remove('on'); }
document.getElementById('cartBtn').addEventListener('click', openCart);

/* ── Quick View ─────────────────────────────── */
function openQV(card) {
    const thumb = card.dataset.thumb;
    const stock = +card.dataset.stock;
    document.getElementById('qvPhoto').innerHTML  = thumb ? `<img src="${thumb}">` : `<div class="qv-photo-ph"><i class="bi bi-image"></i></div>`;
    document.getElementById('qvCat').textContent  = card.dataset.cat || 'Product';
    document.getElementById('qvName').textContent = card.dataset.name;
    document.getElementById('qvPrice').textContent= '$'+parseFloat(card.dataset.price).toFixed(2);
    document.getElementById('qvDesc').textContent = card.dataset.desc || 'No description available.';
    document.getElementById('qvStock').textContent= stock>0?(stock+' units'+(stock<=5?' — Hurry!':'')):'Out of stock';
    document.getElementById('qvStock').style.color= stock===0?'#dc2626':(stock<=5?'#d97706':'');
    document.getElementById('qvCatV').textContent = card.dataset.cat || '—';
    document.getElementById('qvLink').href        = `view_product.php?id=${card.dataset.id}`;
    document.getElementById('qvAdd').onclick      = () => { addToCart(card); toast('Added to cart! 🛒','gold'); };
    document.getElementById('qvBg').classList.add('on');
    document.getElementById('qvSheet').classList.add('on');
    document.body.style.overflow='hidden';
}
function closeQV() {
    document.getElementById('qvBg').classList.remove('on');
    document.getElementById('qvSheet').classList.remove('on');
    document.body.style.overflow='';
}

/* ── Wishlist ───────────────────────────────── */
let wl = JSON.parse(localStorage.getItem('wl')||'[]');
function toggleWL(btn) {
    const id = +btn.closest('.card').dataset.id;
    const ic = btn.querySelector('i');
    if(wl.includes(id)){wl=wl.filter(i=>i!==id);btn.classList.remove('on');ic.className='bi bi-heart';toast('Removed from saved');}
    else{wl.push(id);btn.classList.add('on');ic.className='bi bi-heart-fill';toast('Saved! ❤️','gold');}
    localStorage.setItem('wl',JSON.stringify(wl));
}
document.querySelectorAll('.card').forEach(c=>{
    if(wl.includes(+c.dataset.id)){const b=c.querySelector('.btn-wl');if(b){b.classList.add('on');b.querySelector('i').className='bi bi-heart-fill';}}
});

/* ── Theme ──────────────────────────────────── */
function applyTheme(t){
    document.documentElement.setAttribute('data-theme',t);
    const btn=document.getElementById('themeBtn');
    if(btn) btn.innerHTML=t==='dark'?'<i class="bi bi-sun-fill"></i>':'<i class="bi bi-moon-fill"></i>';
}
applyTheme(localStorage.getItem('shopTheme')||'light');
document.getElementById('themeBtn').addEventListener('click',()=>{
    const next=document.documentElement.getAttribute('data-theme')==='dark'?'light':'dark';
    applyTheme(next); localStorage.setItem('shopTheme',next);
});

/* ── Toast ──────────────────────────────────── */
function toast(msg, type='') {
    const el=document.createElement('div');
    el.className='t '+type;
    el.innerHTML=`<i class="bi bi-${type==='gold'?'bag-check':'info-circle'}"></i> ${msg}`;
    document.getElementById('tw').appendChild(el);
    requestAnimationFrame(()=>el.classList.add('on'));
    setTimeout(()=>{el.classList.remove('on');setTimeout(()=>el.remove(),380);},2500);
}

/* ── Smooth scroll hero CTA ─────────────────── */
document.querySelector('.hero-cta')?.addEventListener('click', e=>{
    e.preventDefault();
    document.getElementById('products').scrollIntoView({behavior:'smooth'});
});

/* ── Init ───────────────────────────────────── */
updateBadge();
</script>
</body>
</html>