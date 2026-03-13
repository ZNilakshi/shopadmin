<?php
require_once 'config.php';
require_once 'classes/Database.php';
require_once 'classes/Auth.php';
require_once 'classes/Product.php';

$auth    = new Auth();
$auth->requireLogin();

$product  = new Product();
$isAdmin  = $auth->isAdmin();
$userId   = (int)$_SESSION['user_id'];
$greeting = date('H') < 12 ? 'morning' : (date('H') < 18 ? 'afternoon' : 'evening');
$firstName = htmlspecialchars(explode(' ', $_SESSION['user_name'])[0]);

if ($isAdmin) {
    $stats  = $product->getStats();
    $recent = $product->getRecentProducts(6);
    $byCat  = $product->getByCategory();
    $users  = $product->getAllUsers();
} else {
    $stats  = $product->getUserStats($userId);
    $recent = $product->getRecentProducts(5);
    $byCat  = $product->getByCategoryForUser($userId);
}

$pageTitle  = 'Dashboard';
$activePage = 'dashboard';
include 'includes/header.php';
?>

<?php if ($isAdmin): ?>


<!-- Welcome -->
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <h4 class="mb-0">Good <?= $greeting ?>, <?= $firstName ?> </h4>
            <span class="badge bg-accent text-white"><i class="bi bi-shield-check me-1"></i>Admin</span>
        </div>
        <p class="text-muted mb-0">Full store overview — you can see everything.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <button onclick="exportCSV()" class="export-btn csv"><i class="bi bi-filetype-csv"></i> Export CSV</button>
        <button onclick="exportPDF()" class="export-btn pdf"><i class="bi bi-filetype-pdf"></i> Export PDF</button>
        <a href="add_product.php" class="btn btn-accent"><i class="bi bi-plus-lg me-2"></i>Add Product</a>
    </div>
</div>

<!-- Admin Stat Cards — 8 cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-sm-4 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:#dbeafe;color:#2563eb"><i class="bi bi-boxes"></i></div>
            <div class="stat-value"><?= $stats['total'] ?></div>
            <div class="stat-label">Total Products</div>
        </div>
    </div>
    <div class="col-6 col-sm-4 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:#d1fae5;color:#059669"><i class="bi bi-check-circle-fill"></i></div>
            <div class="stat-value"><?= $stats['active'] ?></div>
            <div class="stat-label">Active Products</div>
        </div>
    </div>
    <div class="col-6 col-sm-4 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:#fee2e2;color:#dc2626"><i class="bi bi-x-circle-fill"></i></div>
            <div class="stat-value"><?= $stats['out_of_stock'] ?></div>
            <div class="stat-label">Out of Stock</div>
        </div>
    </div>
    <div class="col-6 col-sm-4 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:#fef3c7;color:#d97706"><i class="bi bi-exclamation-triangle-fill"></i></div>
            <div class="stat-value"><?= $stats['low_stock'] ?></div>
            <div class="stat-label">Low Stock (&le;10)</div>
        </div>
    </div>
    <div class="col-6 col-sm-4 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:#ede9fe;color:#7c3aed"><i class="bi bi-tag-fill"></i></div>
            <div class="stat-value"><?= $stats['categories'] ?></div>
            <div class="stat-label">Categories</div>
        </div>
    </div>
    <div class="col-6 col-sm-4 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:#fce7f3;color:#be185d"><i class="bi bi-people-fill"></i></div>
            <div class="stat-value"><?= $stats['total_users'] ?></div>
            <div class="stat-label">Registered Users</div>
        </div>
    </div>
    <div class="col-6 col-sm-4 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:#f3f4f6;color:#6b7280"><i class="bi bi-pause-circle-fill"></i></div>
            <div class="stat-value"><?= $stats['inactive'] ?></div>
            <div class="stat-label">Inactive Products</div>
        </div>
    </div>
    <div class="col-6 col-sm-4 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:#d1fae5;color:#065f46"><i class="bi bi-currency-dollar"></i></div>
            <div class="stat-value" style="font-size:1.3rem">$<?= number_format($stats['total_value'], 0) ?></div>
            <div class="stat-label">Total Inventory Value</div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row g-3 mb-3">
    <div class="col-lg-4">
        <div class="app-card h-100">
            <div class="app-card-header">
                <h6><i class="bi bi-pie-chart-fill text-accent me-2"></i>Products by Category</h6>
            </div>
            <canvas id="categoryChart" height="200"></canvas>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="app-card h-100">
            <div class="app-card-header">
                <h6><i class="bi bi-bar-chart-fill text-accent me-2"></i>Stock Status</h6>
            </div>
            <canvas id="statusChart" height="200"></canvas>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="app-card h-100">
            <div class="app-card-header">
                <h6><i class="bi bi-graph-up-arrow text-accent me-2"></i>Value by Category</h6>
            </div>
            <canvas id="valueChart" height="200"></canvas>
        </div>
    </div>
</div>

<!-- Users Table + Recent Products -->
<div class="row g-3">
    <!-- Users Table (admin only) -->
    <div class="col-lg-7">
        <div class="app-card h-100">
            <div class="app-card-header">
                <h6><i class="bi bi-people-fill text-accent me-2"></i>All Users</h6>
                <span class="badge bg-light text-dark border"><?= count($users) ?> users</span>
            </div>
            <div class="table-responsive">
                <table class="product-table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Products</th>
                            <th>Joined</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="avatar-mini"><?= strtoupper(substr($u['name'],0,1)) ?></div>
                                    <span class="fw-600"><?= htmlspecialchars($u['name']) ?></span>
                                </div>
                            </td>
                            <td class="text-muted"><?= htmlspecialchars($u['email']) ?></td>
                            <td>
                                <span class="badge <?= $u['role']==='admin' ? 'role-admin' : 'role-user' ?>">
                                    <?= ucfirst($u['role']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border"><?= $u['product_count'] ?></span>
                            </td>
                            <td class="text-muted"><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Recent Products -->
    <div class="col-lg-5">
        <div class="app-card h-100">
            <div class="app-card-header">
                <h6><i class="bi bi-clock-history text-accent me-2"></i>Recently Added</h6>
                <a href="products.php" class="btn btn-sm btn-outline-secondary">View all</a>
            </div>
            <div class="list-group list-group-flush">
                <?php foreach ($recent as $p): ?>
                <a href="view_product.php?id=<?= $p['id'] ?>" class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-2 px-0 border-0 border-bottom">
                    <?php if ($p['thumbnail']): ?>
                        <img src="uploads/<?= htmlspecialchars($p['thumbnail']) ?>" class="product-thumb">
                    <?php else: ?>
                        <div class="product-thumb-placeholder"><i class="bi bi-image"></i></div>
                    <?php endif; ?>
                    <div class="flex-grow-1 overflow-hidden">
                        <div class="fw-600 text-truncate" style="font-size:.85rem"><?= htmlspecialchars($p['name']) ?></div>
                        <small class="text-muted">$<?= number_format($p['price'], 2) ?> · <?= htmlspecialchars($p['category_name'] ?? '—') ?></small>
                    </div>
                    <span class="badge-status badge-<?= $p['status'] ?>"><?= ucfirst(str_replace('_',' ',$p['status'])) ?></span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php else: ?>


<!-- Welcome -->
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <h4 class="mb-0">Good <?= $greeting ?>, <?= $firstName ?> </h4>
            <span class="badge bg-secondary text-white"><i class="bi bi-person-fill me-1"></i>My Store</span>
        </div>
        <p class="text-muted mb-0">Here's a summary of your products.</p>
    </div>
    <div class="d-flex gap-2">
        <button onclick="exportCSV()" class="export-btn csv"><i class="bi bi-filetype-csv"></i> CSV</button>
        <a href="add_product.php" class="btn btn-accent"><i class="bi bi-plus-lg me-2"></i>Add Product</a>
    </div>
</div>

<!-- User Stat Cards — 5 cards (MY products only) -->
<div class="row g-3 mb-4">
    <div class="col-6 col-xl">
        <div class="stat-card">
            <div class="stat-icon" style="background:#dbeafe;color:#2563eb"><i class="bi bi-boxes"></i></div>
            <div class="stat-value"><?= $stats['total'] ?></div>
            <div class="stat-label">My Products</div>
        </div>
    </div>
    <div class="col-6 col-xl">
        <div class="stat-card">
            <div class="stat-icon" style="background:#d1fae5;color:#059669"><i class="bi bi-check-circle-fill"></i></div>
            <div class="stat-value"><?= $stats['active'] ?></div>
            <div class="stat-label">Active</div>
        </div>
    </div>
    <div class="col-6 col-xl">
        <div class="stat-card">
            <div class="stat-icon" style="background:#fee2e2;color:#dc2626"><i class="bi bi-x-circle-fill"></i></div>
            <div class="stat-value"><?= $stats['out_of_stock'] ?></div>
            <div class="stat-label">Out of Stock</div>
        </div>
    </div>
    <div class="col-6 col-xl">
        <div class="stat-card">
            <div class="stat-icon" style="background:#fef3c7;color:#d97706"><i class="bi bi-exclamation-triangle-fill"></i></div>
            <div class="stat-value"><?= $stats['low_stock'] ?></div>
            <div class="stat-label">Low Stock</div>
        </div>
    </div>
    <div class="col-12 col-xl">
        <div class="stat-card">
            <div class="stat-icon" style="background:#d1fae5;color:#065f46"><i class="bi bi-currency-dollar"></i></div>
            <div class="stat-value" style="font-size:1.3rem">$<?= number_format($stats['total_value'], 0) ?></div>
            <div class="stat-label">My Inventory Value</div>
        </div>
    </div>
</div>

<!-- User Charts + Quick Actions -->
<div class="row g-3 mb-3">
    <!-- My category breakdown -->
    <div class="col-lg-5">
        <div class="app-card h-100">
            <div class="app-card-header">
                <h6><i class="bi bi-pie-chart-fill text-accent me-2"></i>My Products by Category</h6>
            </div>
            <?php if (empty($byCat)): ?>
                <div class="text-center py-4 text-muted">
                    <i class="bi bi-bar-chart" style="font-size:2.5rem"></i>
                    <p class="mt-2 mb-0">No products yet</p>
                </div>
            <?php else: ?>
                <canvas id="categoryChart" height="200"></canvas>
            <?php endif; ?>
        </div>
    </div>

    <!-- My status chart -->
    <div class="col-lg-4">
        <div class="app-card h-100">
            <div class="app-card-header">
                <h6><i class="bi bi-bar-chart-fill text-accent me-2"></i>Stock Status</h6>
            </div>
            <canvas id="statusChart" height="200"></canvas>
        </div>
    </div>

    <!-- Quick Actions card -->
    <div class="col-lg-3">
        <div class="app-card h-100">
            <div class="app-card-header">
                <h6><i class="bi bi-lightning-fill text-accent me-2"></i>Quick Actions</h6>
            </div>
            <div class="d-flex flex-column gap-2">
                <a href="add_product.php" class="btn btn-accent w-100 text-start">
                    <i class="bi bi-plus-circle me-2"></i>Add New Product
                </a>
                <a href="products.php" class="btn btn-outline-secondary w-100 text-start">
                    <i class="bi bi-boxes me-2"></i>View My Products
                </a>
                <a href="products.php?status=out_of_stock" class="btn w-100 text-start <?= $stats['out_of_stock'] > 0 ? 'btn-outline-danger' : 'btn-outline-secondary' ?>">
                    <i class="bi bi-exclamation-circle me-2"></i>Out of Stock
                    <?php if ($stats['out_of_stock'] > 0): ?>
                        <span class="badge bg-danger ms-1"><?= $stats['out_of_stock'] ?></span>
                    <?php endif; ?>
                </a>
                <a href="profile.php" class="btn btn-outline-secondary w-100 text-start">
                    <i class="bi bi-person-circle me-2"></i>Edit Profile
                </a>
                <hr class="my-1">
                <div class="p-2 rounded-3 text-center" style="background:var(--body-bg);border:1px solid var(--border)">
                    <div class="text-muted small">Logged in as</div>
                    <div class="fw-600 mt-1"><?= htmlspecialchars($_SESSION['user_name']) ?></div>
                    <div class="text-muted small"><?= htmlspecialchars($_SESSION['user_email']) ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- My Recent Products -->
<div class="app-card">
    <div class="app-card-header">
        <h6><i class="bi bi-clock-history text-accent me-2"></i>My Recent Products</h6>
        <a href="products.php" class="btn btn-sm btn-outline-secondary">View all</a>
    </div>
    <?php if (empty($recent)): ?>
        <div class="text-center py-5">
            <i class="bi bi-box-seam text-muted" style="font-size:3rem"></i>
            <p class="text-muted mt-3 mb-2">You haven't added any products yet.</p>
            <a href="add_product.php" class="btn btn-accent btn-sm">Add your first product</a>
        </div>
    <?php else: ?>
    <div class="row g-3">
        <?php foreach ($recent as $p): ?>
        <div class="col-sm-6 col-lg-4 col-xl-3">
            <a href="view_product.php?id=<?= $p['id'] ?>" class="text-decoration-none">
                <div class="p-3 rounded-3 border h-100 d-flex gap-3 align-items-center" style="transition:all .2s" onmouseover="this.style.borderColor='var(--accent)'" onmouseout="this.style.borderColor='var(--border)'">
                    <?php if ($p['thumbnail']): ?>
                        <img src="uploads/<?= htmlspecialchars($p['thumbnail']) ?>" class="product-thumb flex-shrink-0">
                    <?php else: ?>
                        <div class="product-thumb-placeholder flex-shrink-0"><i class="bi bi-image"></i></div>
                    <?php endif; ?>
                    <div class="overflow-hidden">
                        <div class="fw-600 text-truncate text-dark" style="font-size:.85rem"><?= htmlspecialchars($p['name']) ?></div>
                        <div class="text-accent fw-700">$<?= number_format($p['price'], 2) ?></div>
                        <span class="badge-status badge-<?= $p['status'] ?>" style="font-size:.7rem"><?= ucfirst(str_replace('_',' ',$p['status'])) ?></span>
                    </div>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php endif; ?>

<?php include 'includes/footer.php'; ?>

<script>
const colors = ['#10b981','#3b82f6','#f59e0b','#ef4444','#8b5cf6','#06b6d4','#f97316','#ec4899'];
const catData = <?= json_encode($byCat) ?>;

<?php if ($isAdmin): ?>
// ── Admin Charts ──────────────────────────
new Chart(document.getElementById('categoryChart'), {
    type: 'doughnut',
    data: {
        labels: catData.map(c => c.name),
        datasets: [{ data: catData.map(c => parseInt(c.count)), backgroundColor: colors, borderWidth: 0, hoverOffset: 6 }]
    },
    options: {
        plugins: { legend: { position: 'bottom', labels: { font: { size: 11 }, boxWidth: 12 } } },
        cutout: '65%'
    }
});

new Chart(document.getElementById('statusChart'), {
    type: 'bar',
    data: {
        labels: ['Active', 'Out of Stock', 'Inactive'],
        datasets: [{
            data: [<?= $stats['active'] ?>, <?= $stats['out_of_stock'] ?>, <?= $stats['inactive'] ?>],
            backgroundColor: ['#10b981','#ef4444','#9ca3af'],
            borderRadius: 8, borderWidth: 0
        }]
    },
    options: {
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' }, ticks: { precision: 0 } },
            x: { grid: { display: false } }
        }
    }
});

// Value by top categories
new Chart(document.getElementById('valueChart'), {
    type: 'bar',
    data: {
        labels: catData.slice(0,6).map(c => c.name),
        datasets: [{
            label: 'Products',
            data: catData.slice(0,6).map(c => parseInt(c.count)),
            backgroundColor: colors.slice(0,6),
            borderRadius: 8, borderWidth: 0
        }]
    },
    options: {
        indexAxis: 'y',
        plugins: { legend: { display: false } },
        scales: {
            x: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: 'rgba(0,0,0,0.05)' } },
            y: { grid: { display: false } }
        }
    }
});

<?php else: ?>
// ── User Charts ───────────────────────────
if (catData.length > 0) {
    new Chart(document.getElementById('categoryChart'), {
        type: 'doughnut',
        data: {
            labels: catData.map(c => c.name),
            datasets: [{ data: catData.map(c => parseInt(c.count)), backgroundColor: colors, borderWidth: 0, hoverOffset: 6 }]
        },
        options: {
            plugins: { legend: { position: 'bottom', labels: { font: { size: 11 }, boxWidth: 12 } } },
            cutout: '60%'
        }
    });
}

new Chart(document.getElementById('statusChart'), {
    type: 'doughnut',
    data: {
        labels: ['Active', 'Out of Stock', 'Low Stock'],
        datasets: [{
            data: [<?= $stats['active'] ?>, <?= $stats['out_of_stock'] ?>, <?= $stats['low_stock'] ?>],
            backgroundColor: ['#10b981','#ef4444','#f59e0b'],
            borderWidth: 0, hoverOffset: 6
        }]
    },
    options: {
        plugins: { legend: { position: 'bottom', labels: { font: { size: 11 }, boxWidth: 12 } } },
        cutout: '60%'
    }
});
<?php endif; ?>
</script>
