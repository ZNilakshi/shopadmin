<?php
require_once 'config.php';
require_once 'classes/Database.php';
require_once 'classes/Auth.php';
require_once 'classes/Product.php';

$auth = new Auth();
$auth->requireLogin();

$id = (int)($_GET['id'] ?? 0);
$product = new Product();
$p = $product->getById($id);

if (!$p) {
    header('Location: products.php');
    exit;
}

$images = $product->getImages($id);

$pageTitle  = htmlspecialchars($p['name']);
$activePage = 'products';
include 'includes/header.php';

$statusColor = ['active' => '#d1fae5;color:#065f46', 'inactive' => '#f3f4f6;color:#6b7280', 'out_of_stock' => '#fee2e2;color:#991b1b'];
$sc = $statusColor[$p['status']] ?? $statusColor['inactive'];
?>

<div class="row justify-content-center">
    <div class="col-xl-10">
        <!-- Breadcrumb + Actions -->
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="products.php" class="text-decoration-none text-accent">Products</a></li>
                    <li class="breadcrumb-item active"><?= htmlspecialchars($p['name']) ?></li>
                </ol>
            </nav>
            <div class="d-flex gap-2">
                <a href="edit_product.php?id=<?= $id ?>" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-pencil me-1"></i>Edit
                </a>
                <?php if ($auth->isAdmin()): ?>
                <button class="btn btn-outline-danger btn-sm" onclick="confirmDelete(<?= $id ?>, '<?= addslashes($p['name']) ?>')">
                    <i class="bi bi-trash me-1"></i>Delete
                </button>
                <?php endif; ?>
            </div>
        </div>

        <div class="row g-3">
            <!-- Image Carousel -->
            <div class="col-lg-5">
                <div class="app-card p-3">
                    <?php if ($images): ?>
                    <div id="productCarousel" class="carousel slide" data-bs-ride="carousel">
                        <div class="carousel-inner">
                            <?php foreach ($images as $i => $img): ?>
                            <div class="carousel-item <?= $i === 0 ? 'active' : '' ?>">
                                <img src="uploads/<?= htmlspecialchars($img['image_path']) ?>" class="d-block w-100" style="height:320px;object-fit:cover;border-radius:10px">
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php if (count($images) > 1): ?>
                        <button class="carousel-control-prev" type="button" data-bs-target="#productCarousel" data-bs-slide="prev">
                            <span class="carousel-control-prev-icon"></span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#productCarousel" data-bs-slide="next">
                            <span class="carousel-control-next-icon"></span>
                        </button>
                        <div class="carousel-indicators position-relative mt-2" style="bottom:0">
                            <?php foreach ($images as $i => $img): ?>
                            <button type="button" data-bs-target="#productCarousel" data-bs-slide-to="<?= $i ?>" <?= $i===0?'class="active"':'' ?> style="background:#10b981;width:8px;height:8px;border-radius:50%"></button>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php else: ?>
                    <div class="d-flex align-items-center justify-content-center" style="height:280px;background:#f3f4f6;border-radius:10px">
                        <div class="text-center text-muted">
                            <i class="bi bi-image" style="font-size:3rem"></i>
                            <p class="mt-2">No images uploaded</p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Product Details -->
            <div class="col-lg-7">
                <div class="app-card h-100">
                    <div class="d-flex align-items-start justify-content-between flex-wrap gap-2 mb-3">
                        <h4 class="mb-0"><?= htmlspecialchars($p['name']) ?></h4>
                        <span class="product-badge" style="background:<?= $sc ?>">
                            <i class="bi bi-circle-fill" style="font-size:.5rem"></i>
                            <?= ucfirst(str_replace('_', ' ', $p['status'])) ?>
                        </span>
                    </div>

                    <?php if ($p['category_name']): ?>
                    <span class="badge bg-light text-dark border mb-3">
                        <i class="bi bi-tag me-1 text-accent"></i><?= htmlspecialchars($p['category_name']) ?>
                    </span>
                    <?php endif; ?>

                    <div class="mb-3">
                        <div class="stat-value" style="font-size:2.5rem;color:var(--accent)">$<?= number_format($p['price'], 2) ?></div>
                    </div>

                    <?php if ($p['description']): ?>
                    <p class="text-muted mb-4" style="line-height:1.7"><?= nl2br(htmlspecialchars($p['description'])) ?></p>
                    <?php endif; ?>

                    <div class="row g-3 mb-4">
                        <div class="col-6">
                            <div class="p-3 rounded-3" style="background:#f8fafc;border:1px solid var(--border)">
                                <div class="text-muted small mb-1">Stock</div>
                                <div class="fw-700 fs-5">
                                    <?php if ($p['stock'] === 0): ?>
                                        <span class="text-danger"><?= $p['stock'] ?> units</span>
                                    <?php elseif ($p['stock'] <= 10): ?>
                                        <span class="text-warning"><?= $p['stock'] ?> units</span>
                                    <?php else: ?>
                                        <?= $p['stock'] ?> units
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 rounded-3" style="background:#f8fafc;border:1px solid var(--border)">
                                <div class="text-muted small mb-1">Total Value</div>
                                <div class="fw-700 fs-5 text-accent">$<?= number_format($p['price'] * $p['stock'], 2) ?></div>
                            </div>
                        </div>
                    </div>

                    <div class="border-top pt-3">
                        <div class="row g-2 text-muted small">
                            <div class="col-6"><i class="bi bi-calendar3 me-1"></i>Added: <?= date('M j, Y', strtotime($p['created_at'])) ?></div>
                            <div class="col-6"><i class="bi bi-clock me-1"></i>Updated: <?= date('M j, Y', strtotime($p['updated_at'])) ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
<script>
function confirmDelete(id, name) {
    if (!confirm('Delete "' + name + '"? This cannot be undone.')) return;
    const fd = new FormData();
    fd.append('action', 'delete');
    fd.append('id', id);
    apiPost('api/products.php', fd).then(data => {
        if (data.success) {
            showToast('Product deleted.', 'success');
            setTimeout(() => window.location = 'products.php', 800);
        } else {
            showToast(data.message, 'error');
        }
    });
}
</script>
