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

$images     = $product->getImages($id);
$categories = $product->getCategories();

$pageTitle  = 'Edit Product';
$activePage = 'products';
include 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-xl-9">
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="products.php" class="text-decoration-none text-accent">Products</a></li>
                <li class="breadcrumb-item"><a href="view_product.php?id=<?= $id ?>" class="text-decoration-none text-accent"><?= htmlspecialchars($p['name']) ?></a></li>
                <li class="breadcrumb-item active">Edit</li>
            </ol>
        </nav>

        <form id="editForm" enctype="multipart/form-data" novalidate>
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" value="<?= $id ?>">

            <!-- Basic Info -->
            <div class="app-card mb-3">
                <h6 class="mb-4"><i class="bi bi-info-circle-fill text-accent me-2"></i>Basic Information</h6>
                <div class="mb-3">
                    <label class="form-label">Product Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($p['name']) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="4"><?= htmlspecialchars($p['description'] ?? '') ?></textarea>
                </div>
                <div class="mb-0">
                    <label class="form-label">Category</label>
                    <select name="category_id" class="form-select">
                        <option value="">— Select Category —</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $p['category_id'] == $cat['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Pricing & Stock -->
            <div class="app-card mb-3">
                <h6 class="mb-4"><i class="bi bi-currency-dollar text-accent me-2"></i>Pricing & Stock</h6>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Price (USD)</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" name="price" class="form-control" value="<?= $p['price'] ?>" step="0.01" min="0" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Stock Quantity</label>
                        <input type="number" name="stock" class="form-control" value="<?= $p['stock'] ?>" min="0" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="active" <?= $p['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= $p['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                            <option value="out_of_stock" <?= $p['status'] === 'out_of_stock' ? 'selected' : '' ?>>Out of Stock</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Images -->
            <div class="app-card mb-3">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h6 class="mb-0"><i class="bi bi-images text-accent me-2"></i>Product Images</h6>
                    <?php if ($images): ?>
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-muted small"><i class="bi bi-arrows-move me-1"></i>Drag to reorder</span>
                        <button type="button" id="saveOrderBtn" class="btn-save-order" onclick="saveImageOrder(<?= $id ?>)">
                            <i class="bi bi-arrows-move me-1"></i>Save Order
                        </button>
                    </div>
                    <?php endif; ?>
                </div>

                <?php if ($images): ?>
                <p class="text-muted small mb-2">
                    <i class="bi bi-star-fill text-accent me-1"></i>First image = main product photo.
                    Drag <i class="bi bi-grip-vertical"></i> to reorder, <i class="bi bi-x text-danger"></i> to remove.
                </p>
                <div class="sortable-images" id="sortableImages">
                    <?php foreach ($images as $idx => $img): ?>
                    <div class="sortable-img-item <?= $idx === 0 ? 'primary-image' : '' ?>" id="img-<?= $img['id'] ?>" data-image-id="<?= $img['id'] ?>">
                        <img src="uploads/<?= htmlspecialchars($img['image_path']) ?>" alt="">
                        <div class="drag-handle" title="Drag to reorder"><i class="bi bi-grip-vertical"></i></div>
                        <button type="button" class="del-btn" onclick="deleteExistingImage(<?= $img['id'] ?>, this)" title="Delete image">
                            <i class="bi bi-x"></i>
                        </button>
                        <span class="order-badge"><?= $idx === 0 ? '★ Main' : '#' . ($idx + 1) ?></span>
                        <?php if ($idx === 0): ?><span class="primary-badge">Cover</span><?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <hr class="my-3">
                <?php endif; ?>

                <label class="form-label">Add More Images</label>
                <div class="upload-zone" id="uploadZone">
                    <input type="file" name="images[]" id="productImages" multiple accept="image/*" style="display:none">
                    <i class="bi bi-cloud-arrow-up-fill"></i>
                    <p class="text-muted mb-1">Click or drag & drop to add images</p>
                    <small class="text-muted">JPG, PNG, WEBP — Max 5MB each</small>
                </div>
                <div class="image-preview-grid mt-2" id="imagePreview"></div>
            </div>

            <!-- Actions -->
            <div class="d-flex gap-2 justify-content-end">
                <a href="view_product.php?id=<?= $id ?>" class="btn btn-outline-secondary">Cancel</a>
                <button type="button" id="updateBtn" class="btn btn-accent">
                    <i class="bi bi-check-circle me-2"></i>Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.2/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    initSortableImages('sortableImages', <?= $id ?>);
});

document.getElementById('updateBtn').addEventListener('click', async () => {
    const form = document.getElementById('editForm');
    const name = form.querySelector('[name=name]').value.trim();
    if (!name) { showToast('Product name is required.', 'error'); return; }

    const btn = document.getElementById('updateBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';

    const data = await apiPost('api/products.php', new FormData(form));
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-check-circle me-2"></i>Save Changes';

    if (data.success) {
        showToast('Product updated!', 'success');
        setTimeout(() => window.location = 'view_product.php?id=<?= $id ?>', 800);
    } else {
        showToast(data.message || 'Update failed.', 'error');
    }
});
</script>
