<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/classes/Database.php';
require_once dirname(__DIR__) . '/classes/Auth.php';
require_once dirname(__DIR__) . '/classes/Product.php';

$auth = new Auth();
$auth->requireAuth(BASE_URL . '/login.php');

$product = new Product();
$cats    = $product->getCategories();
$pageTitle = 'Add Product';
?>
<?php include dirname(__DIR__) . '/includes/header.php'; ?>
<meta name="base-url" content="<?= BASE_URL ?>">

<div class="app-wrapper">
    <?php include dirname(__DIR__) . '/includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="topbar">
            <button class="topbar-toggle" id="sidebarToggle"><i class="bi bi-list"></i></button>
            <div class="topbar-title">Add New Product</div>
            <div class="topbar-actions">
                <a href="<?= BASE_URL ?>/products/list.php" class="btn-ghost">
                    <i class="bi bi-arrow-left"></i> Back to List
                </a>
            </div>
        </div>

        <div class="content-area">
            <form id="addProductForm" novalidate>
                <div class="row g-3">
                    <!-- Left: Main Info -->
                    <div class="col-12 col-xl-8">
                        <div class="panel mb-3">
                            <div class="panel-header">
                                <h3 class="panel-title"><i class="bi bi-info-circle me-2" style="color:var(--accent)"></i>Product Information</h3>
                            </div>
                            <div class="panel-body">
                                <div class="mb-3">
                                    <label class="form-label">Product Name <span style="color:var(--danger)">*</span></label>
                                    <input type="text" id="name" name="name" class="form-control"
                                           placeholder="e.g. iPhone 15 Pro" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Description</label>
                                    <textarea id="description" name="description" class="form-control" rows="4"
                                              placeholder="Describe the product in detail..."></textarea>
                                </div>
                                <div class="row g-3">
                                    <div class="col-6">
                                        <label class="form-label">Price (USD) <span style="color:var(--danger)">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input type="number" id="price" name="price" class="form-control"
                                                   placeholder="0.00" min="0" step="0.01" required>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label">Stock Quantity <span style="color:var(--danger)">*</span></label>
                                        <input type="number" id="stock" name="stock" class="form-control"
                                               placeholder="0" min="0" step="1" value="0" required>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Images -->
                        <div class="panel">
                            <div class="panel-header">
                                <h3 class="panel-title"><i class="bi bi-images me-2" style="color:var(--accent)"></i>Product Images</h3>
                                <span style="font-size:12px;color:var(--text-2)">First image will be primary</span>
                            </div>
                            <div class="panel-body">
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

                    <!-- Right: Meta -->
                    <div class="col-12 col-xl-4">
                        <div class="panel mb-3">
                            <div class="panel-header">
                                <h3 class="panel-title"><i class="bi bi-sliders me-2" style="color:var(--accent)"></i>Product Settings</h3>
                            </div>
                            <div class="panel-body">
                                <div class="mb-3">
                                    <label class="form-label">Category</label>
                                    <select id="category_id" name="category_id" class="form-select">
                                        <option value="">— Select Category —</option>
                                        <?php foreach ($cats as $cat): ?>
                                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="form-label">Status</label>
                                    <div class="d-flex gap-3">
                                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;color:var(--text-2);font-size:13px">
                                            <input type="radio" name="status" value="active" checked
                                                   style="accent-color:var(--accent);width:16px;height:16px"> Active
                                        </label>
                                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;color:var(--text-2);font-size:13px">
                                            <input type="radio" name="status" value="inactive"
                                                   style="accent-color:var(--accent);width:16px;height:16px"> Inactive
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="panel">
                            <div class="panel-body">
                                <button type="submit" class="btn-accent w-100 justify-content-center py-2 mb-2" id="submitBtn">
                                    <i class="bi bi-check-circle"></i> Create Product
                                </button>
                                <a href="<?= BASE_URL ?>/products/list.php" class="btn-ghost w-100 justify-content-center py-2">
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

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>

<script>
const BASE = '<?= BASE_URL ?>';

// Initialize image upload widget
initImageUpload('uploadZone', 'imagePreview', 'uploadedImages', { type: 'product', multiple: true });

// Form submit
document.getElementById('addProductForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = document.getElementById('submitBtn');

    const name  = document.getElementById('name').value.trim();
    const price = parseFloat(document.getElementById('price').value);
    const stock = parseInt(document.getElementById('stock').value);

    if (!name) { toast.error('Product name is required'); return; }
    if (isNaN(price) || price < 0) { toast.error('Please enter a valid price'); return; }

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner" style="width:16px;height:16px;border-width:2px;display:inline-block;vertical-align:middle;margin-right:6px"></span> Creating...';

    const imagesRaw = document.getElementById('uploadedImages').value;
    const images = imagesRaw ? JSON.parse(imagesRaw) : [];

    const data = {
        name,
        description: document.getElementById('description').value.trim(),
        price,
        stock: isNaN(stock) ? 0 : stock,
        category_id: document.getElementById('category_id').value || null,
        status: document.querySelector('input[name="status"]:checked').value,
        images,
    };

    const res = await apiCall(`${BASE}/api/products.php?action=create`, {
        method: 'POST',
        body: JSON.stringify(data),
    });

    if (res.success) {
        toast.success(res.message || 'Product created!');
        setTimeout(() => { window.location.href = `${BASE}/products/view.php?id=${res.id}`; }, 800);
    } else {
        toast.error(res.message || 'Failed to create product');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-circle"></i> Create Product';
    }
});
</script>
