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

$cats      = $productObj->getCategories();
$pageTitle = 'Edit: ' . $product['name'];
?>
<?php include dirname(__DIR__) . '/includes/header.php'; ?>
<meta name="base-url" content="<?= BASE_URL ?>">

<div class="app-wrapper">
    <?php include dirname(__DIR__) . '/includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="topbar">
            <button class="topbar-toggle" id="sidebarToggle"><i class="bi bi-list"></i></button>
            <div class="topbar-title">Edit Product</div>
            <div class="topbar-actions">
                <a href="<?= BASE_URL ?>/products/view.php?id=<?= $id ?>" class="btn-ghost">
                    <i class="bi bi-eye"></i> View
                </a>
                <a href="<?= BASE_URL ?>/products/list.php" class="btn-ghost">
                    <i class="bi bi-arrow-left"></i> Back
                </a>
            </div>
        </div>

        <div class="content-area">
            <form id="editProductForm" novalidate>
                <div class="row g-3">
                    <!-- Left -->
                    <div class="col-12 col-xl-8">
                        <div class="panel mb-3">
                            <div class="panel-header">
                                <h3 class="panel-title"><i class="bi bi-info-circle me-2" style="color:var(--accent)"></i>Product Information</h3>
                            </div>
                            <div class="panel-body">
                                <div class="mb-3">
                                    <label class="form-label">Product Name <span style="color:var(--danger)">*</span></label>
                                    <input type="text" id="name" name="name" class="form-control"
                                           value="<?= htmlspecialchars($product['name']) ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Description</label>
                                    <textarea id="description" name="description" class="form-control" rows="4"><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
                                </div>
                                <div class="row g-3">
                                    <div class="col-6">
                                        <label class="form-label">Price (USD) <span style="color:var(--danger)">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input type="number" id="price" name="price" class="form-control"
                                                   value="<?= $product['price'] ?>" min="0" step="0.01" required>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label">Stock Quantity</label>
                                        <input type="number" id="stock" name="stock" class="form-control"
                                               value="<?= $product['stock'] ?>" min="0" step="1">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Images -->
                        <div class="panel">
                            <div class="panel-header">
                                <h3 class="panel-title"><i class="bi bi-images me-2" style="color:var(--accent)"></i>Product Images</h3>
                                <span style="font-size:12px;color:var(--text-2)"><?= count($product['images']) ?> existing · Add more below</span>
                            </div>
                            <div class="panel-body">
                                <!-- Existing images -->
                                <?php if (!empty($product['images'])): ?>
                                <div class="mb-3">
                                    <div class="form-label">Existing Images</div>
                                    <div class="image-preview-grid" id="existingImages">
                                        <?php foreach ($product['images'] as $i => $img): ?>
                                        <div class="image-preview-item <?= $img['is_primary'] ? 'primary-img' : '' ?>" id="img-<?= $img['id'] ?>">
                                            <img src="<?= BASE_URL ?>/uploads/products/<?= htmlspecialchars($img['image_path']) ?>" alt="Product image">
                                            <button type="button" class="remove-img"
                                                    onclick="removeExistingImage(<?= $img['id'] ?>, <?= $id ?>)">
                                                <i class="bi bi-x"></i>
                                            </button>
                                            <?php if ($img['is_primary']): ?>
                                            <div style="position:absolute;bottom:3px;left:3px;background:var(--accent);color:#fff;font-size:9px;padding:1px 5px;border-radius:4px;font-weight:700">PRIMARY</div>
                                            <?php endif; ?>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <!-- Add new images -->
                                <div class="form-label">Add More Images</div>
                                <div class="upload-zone" id="uploadZone">
                                    <i class="bi bi-cloud-upload"></i>
                                    <p>Drop images here or <strong>click to browse</strong></p>
                                    <p style="color:var(--text-3);font-size:11px;margin-top:4px">JPEG, PNG, GIF, WebP · Max 5MB per image</p>
                                </div>
                                <div class="image-preview-grid" id="imagePreview"></div>
                                <input type="hidden" id="uploadedImages" name="images">
                            </div>
                        </div>
                    </div>

                    <!-- Right -->
                    <div class="col-12 col-xl-4">
                        <div class="panel mb-3">
                            <div class="panel-header">
                                <h3 class="panel-title"><i class="bi bi-sliders me-2" style="color:var(--accent)"></i>Product Settings</h3>
                            </div>
                            <div class="panel-body">
                                <div class="mb-3">
                                    <label class="form-label">Category</label>
                                    <select id="category_id" name="category_id" class="form-select">
                                        <option value="">— No Category —</option>
                                        <?php foreach ($cats as $cat): ?>
                                            <option value="<?= $cat['id'] ?>"
                                                <?= $cat['id'] == $product['category_id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($cat['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="form-label">Status</label>
                                    <div class="d-flex gap-3">
                                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;color:var(--text-2);font-size:13px">
                                            <input type="radio" name="status" value="active"
                                                   <?= $product['status'] === 'active' ? 'checked' : '' ?>
                                                   style="accent-color:var(--accent);width:16px;height:16px"> Active
                                        </label>
                                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;color:var(--text-2);font-size:13px">
                                            <input type="radio" name="status" value="inactive"
                                                   <?= $product['status'] === 'inactive' ? 'checked' : '' ?>
                                                   style="accent-color:var(--accent);width:16px;height:16px"> Inactive
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Meta info -->
                        <div class="panel mb-3">
                            <div class="panel-body">
                                <dl class="detail-meta mb-0">
                                    <dt>Product ID</dt>
                                    <dd class="text-mono">#<?= $product['id'] ?></dd>
                                    <dt>Created</dt>
                                    <dd><?= date('M d, Y', strtotime($product['created_at'])) ?></dd>
                                    <dt>Last Updated</dt>
                                    <dd><?= date('M d, Y H:i', strtotime($product['updated_at'])) ?></dd>
                                    <?php if ($product['created_by_name']): ?>
                                    <dt>Created By</dt>
                                    <dd><?= htmlspecialchars($product['created_by_name']) ?></dd>
                                    <?php endif; ?>
                                </dl>
                            </div>
                        </div>

                        <div class="panel">
                            <div class="panel-body">
                                <button type="submit" class="btn-accent w-100 justify-content-center py-2 mb-2" id="submitBtn">
                                    <i class="bi bi-check-circle"></i> Save Changes
                                </button>
                                <a href="<?= BASE_URL ?>/products/view.php?id=<?= $id ?>" class="btn-ghost w-100 justify-content-center py-2">
                                    Cancel
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Confirm delete modal -->
<div class="modal fade" id="confirmModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title text-danger"><i class="bi bi-trash3 me-2"></i>Remove Image</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="confirmModalBody">Remove this image permanently?</div>
            <div class="modal-footer border-0 pt-0">
                <button class="btn-ghost" data-bs-dismiss="modal">Cancel</button>
                <button class="btn-accent" id="confirmDeleteBtn" style="background:var(--danger)">Remove</button>
            </div>
        </div>
    </div>
</div>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>

<script>
const BASE       = '<?= BASE_URL ?>';
const PRODUCT_ID = <?= $id ?>;

// New images upload widget
initImageUpload('uploadZone', 'imagePreview', 'uploadedImages', { type: 'product', multiple: true });

// Remove existing image
async function removeExistingImage(imageId, productId) {
    confirmDelete('Remove this image permanently?', async () => {
        const res = await apiCall(`${BASE}/api/products.php?action=delete_image&image_id=${imageId}&product_id=${productId}`, {
            method: 'POST', body: '{}'
        });
        if (res.success) {
            document.getElementById(`img-${imageId}`)?.remove();
            toast.success('Image removed');
        } else {
            toast.error(res.message || 'Failed to remove');
        }
    });
}

// Form submit
document.getElementById('editProductForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = document.getElementById('submitBtn');

    const name  = document.getElementById('name').value.trim();
    const price = parseFloat(document.getElementById('price').value);

    if (!name) { toast.error('Product name is required'); return; }
    if (isNaN(price) || price < 0) { toast.error('Please enter a valid price'); return; }

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner" style="width:16px;height:16px;border-width:2px;display:inline-block;vertical-align:middle;margin-right:6px"></span> Saving...';

    const imagesRaw = document.getElementById('uploadedImages').value;
    const images = imagesRaw ? JSON.parse(imagesRaw) : [];

    const data = {
        name,
        description: document.getElementById('description').value.trim(),
        price,
        stock: parseInt(document.getElementById('stock').value) || 0,
        category_id: document.getElementById('category_id').value || null,
        status: document.querySelector('input[name="status"]:checked').value,
        images,
    };

    const res = await apiCall(`${BASE}/api/products.php?action=update&id=${PRODUCT_ID}`, {
        method: 'POST',
        body: JSON.stringify(data),
    });

    if (res.success) {
        toast.success(res.message || 'Product updated!');
        setTimeout(() => { window.location.href = `${BASE}/products/view.php?id=${PRODUCT_ID}`; }, 800);
    } else {
        toast.error(res.message || 'Failed to update');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-circle"></i> Save Changes';
    }
});
</script>
