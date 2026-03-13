<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/classes/Database.php';
require_once dirname(__DIR__) . '/classes/Auth.php';
require_once dirname(__DIR__) . '/classes/Product.php';

$auth = new Auth();
$auth->requireAuth(BASE_URL . '/login.php');

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    header('Location: ' . BASE_URL . '/products/list.php');
    exit;
}

$productObj = new Product();
$product    = $productObj->getById($id);
if (!$product) {
    header('Location: ' . BASE_URL . '/products/list.php');
    exit;
}

$pageTitle = $product['name'];

$stockBadge = $product['stock'] === 0
    ? '<span class="badge-status badge-out">Out of Stock</span>'
    : ($product['stock'] < 10
        ? '<span class="badge-status badge-low">Low Stock: ' . $product['stock'] . ' left</span>'
        : '<span class="badge-status badge-active">' . $product['stock'] . ' in Stock</span>');
?>
<?php include dirname(__DIR__) . '/includes/header.php'; ?>
<meta name="base-url" content="<?= BASE_URL ?>">

<div class="app-wrapper">
    <?php include dirname(__DIR__) . '/includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="topbar">
            <button class="topbar-toggle" id="sidebarToggle"><i class="bi bi-list"></i></button>
            <div class="topbar-title">Product Details</div>
            <div class="topbar-actions">
                <a href="<?= BASE_URL ?>/products/edit.php?id=<?= $id ?>" class="btn-accent">
                    <i class="bi bi-pencil"></i> Edit
                </a>
                <a href="<?= BASE_URL ?>/products/list.php" class="btn-ghost">
                    <i class="bi bi-arrow-left"></i> Back
                </a>
            </div>
        </div>

        <div class="content-area">
            <div class="row g-3">
                <!-- Left: Images -->
                <div class="col-12 col-xl-5">
                    <div class="panel">
                        <div class="panel-body">
                            <?php if (!empty($product['images'])): ?>
                            <!-- Carousel -->
                            <div id="productCarousel" class="carousel slide detail-carousel" data-bs-ride="carousel">
                                <div class="carousel-indicators">
                                    <?php foreach ($product['images'] as $i => $img): ?>
                                    <button type="button" data-bs-target="#productCarousel"
                                            data-bs-slide-to="<?= $i ?>"
                                            class="<?= $i === 0 ? 'active' : '' ?>"
                                            style="background:var(--accent);width:8px;height:8px;border-radius:50%;border:none"></button>
                                    <?php endforeach; ?>
                                </div>
                                <div class="carousel-inner">
                                    <?php foreach ($product['images'] as $i => $img): ?>
                                    <div class="carousel-item <?= $i === 0 ? 'active' : '' ?>">
                                        <img src="<?= BASE_URL ?>/uploads/products/<?= htmlspecialchars($img['image_path']) ?>"
                                             alt="<?= htmlspecialchars($product['name']) ?>">
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php if (count($product['images']) > 1): ?>
                                <button class="carousel-control-prev" type="button" data-bs-target="#productCarousel" data-bs-slide="prev">
                                    <span class="carousel-control-prev-icon"></span>
                                </button>
                                <button class="carousel-control-next" type="button" data-bs-target="#productCarousel" data-bs-slide="next">
                                    <span class="carousel-control-next-icon"></span>
                                </button>
                                <?php endif; ?>
                            </div>

                            <!-- Thumbnails -->
                            <?php if (count($product['images']) > 1): ?>
                            <div class="d-flex gap-2 mt-2 flex-wrap">
                                <?php foreach ($product['images'] as $i => $img): ?>
                                <img src="<?= BASE_URL ?>/uploads/products/<?= htmlspecialchars($img['image_path']) ?>"
                                     alt="" class="product-thumb"
                                     style="cursor:pointer;width:50px;height:50px;<?= $i === 0 ? 'border-color:var(--accent)' : '' ?>"
                                     onclick="document.querySelector('#productCarousel').querySelectorAll('.carousel-item')[<?= $i ?>].classList.add('active'); bootstrap.Carousel.getInstance(document.getElementById('productCarousel'))?.to(<?= $i ?>)">
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>

                            <?php else: ?>
                            <div class="empty-state" style="padding:48px">
                                <i class="bi bi-image" style="font-size:64px;color:var(--text-3)"></i>
                                <p style="margin-top:12px">No images uploaded</p>
                                <a href="<?= BASE_URL ?>/products/edit.php?id=<?= $id ?>" class="btn-ghost mt-2">
                                    <i class="bi bi-upload"></i> Add Images
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Right: Details -->
                <div class="col-12 col-xl-7">
                    <div class="panel mb-3">
                        <div class="panel-body">
                            <!-- Badges -->
                            <div class="d-flex flex-wrap gap-2 mb-3">
                                <span class="badge-status badge-<?= $product['status'] ?>">
                                    <?= ucfirst($product['status']) ?>
                                </span>
                                <?php if ($product['category_name']): ?>
                                <span class="badge-status badge-cat">
                                    <i class="bi bi-tag"></i> <?= htmlspecialchars($product['category_name']) ?>
                                </span>
                                <?php endif; ?>
                                <?= $stockBadge ?>
                            </div>

                            <!-- Name & Price -->
                            <h1 style="font-size:24px;font-weight:800;margin-bottom:8px">
                                <?= htmlspecialchars($product['name']) ?>
                            </h1>

                            <div class="price-display mb-4">
                                $<?= number_format($product['price'], 2) ?>
                            </div>

                            <!-- Description -->
                            <?php if ($product['description']): ?>
                            <div style="color:var(--text-2);line-height:1.7;margin-bottom:24px;font-size:14px">
                                <?= nl2br(htmlspecialchars($product['description'])) ?>
                            </div>
                            <?php endif; ?>

                            <hr style="border-color:var(--border);margin:20px 0">

                            <!-- Meta -->
                            <dl class="detail-meta row g-0">
                                <div class="col-6">
                                    <dt>Product ID</dt>
                                    <dd class="text-mono">#<?= $product['id'] ?></dd>
                                </div>
                                <div class="col-6">
                                    <dt>Stock</dt>
                                    <dd class="text-mono <?= $product['stock'] == 0 ? 'stock-none' : ($product['stock'] < 10 ? 'stock-low' : 'stock-ok') ?> fw-bold">
                                        <?= $product['stock'] ?> units
                                    </dd>
                                </div>
                                <div class="col-6">
                                    <dt>Created</dt>
                                    <dd><?= date('M d, Y', strtotime($product['created_at'])) ?></dd>
                                </div>
                                <div class="col-6">
                                    <dt>Last Updated</dt>
                                    <dd><?= date('M d, Y', strtotime($product['updated_at'])) ?></dd>
                                </div>
                                <?php if ($product['created_by_name']): ?>
                                <div class="col-6">
                                    <dt>Added By</dt>
                                    <dd><?= htmlspecialchars($product['created_by_name']) ?></dd>
                                </div>
                                <?php endif; ?>
                                <div class="col-6">
                                    <dt>Images</dt>
                                    <dd><?= count($product['images']) ?> uploaded</dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="panel">
                        <div class="panel-body d-flex flex-wrap gap-2">
                            <a href="<?= BASE_URL ?>/products/edit.php?id=<?= $id ?>" class="btn-accent">
                                <i class="bi bi-pencil"></i> Edit Product
                            </a>
                            <?php if ($auth->isAdmin()): ?>
                            <button class="btn-ghost" style="color:var(--danger);border-color:var(--danger)"
                                    onclick="deleteThisProduct()">
                                <i class="bi bi-trash3"></i> Delete
                            </button>
                            <?php endif; ?>
                            <a href="<?= BASE_URL ?>/products/list.php" class="btn-ghost ms-auto">
                                <i class="bi bi-arrow-left"></i> Back to List
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Confirm delete modal -->
<div class="modal fade" id="confirmModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title text-danger"><i class="bi bi-trash3 me-2"></i>Delete Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="confirmModalBody">
                Delete "<?= htmlspecialchars($product['name']) ?>"? This cannot be undone.
            </div>
            <div class="modal-footer border-0 pt-0">
                <button class="btn-ghost" data-bs-dismiss="modal">Cancel</button>
                <button class="btn-accent" id="confirmDeleteBtn" style="background:var(--danger)">Delete</button>
            </div>
        </div>
    </div>
</div>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>

<script>
const BASE       = '<?= BASE_URL ?>';
const PRODUCT_ID = <?= $id ?>;

function deleteThisProduct() {
    confirmDelete('Delete "<?= addslashes($product['name']) ?>"? This cannot be undone.', async () => {
        const res = await apiCall(`${BASE}/api/products.php?action=delete&id=${PRODUCT_ID}`, {
            method: 'POST', body: '{}'
        });
        if (res.success) {
            toast.success('Product deleted');
            setTimeout(() => { window.location.href = `${BASE}/products/list.php`; }, 800);
        } else {
            toast.error(res.message || 'Delete failed');
        }
    });
}
</script>
