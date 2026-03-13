<?php
require_once 'config.php';
require_once 'classes/Database.php';
require_once 'classes/Auth.php';
require_once 'classes/Product.php';

$auth = new Auth();
$auth->requireLogin();

$product    = new Product();
$categories = $product->getCategories();

$pageTitle  = 'Add Product';
$activePage = 'add_product';
include 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-xl-9">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="products.php" class="text-decoration-none text-accent">Products</a></li>
                <li class="breadcrumb-item active">Add New</li>
            </ol>
        </nav>

        <!-- Step Indicator -->
        <div class="step-indicator mb-4">
            <div>
                <div class="step-dot active">1</div>
                <div class="step-label">Basic Info</div>
            </div>
            <div class="step-line"></div>
            <div>
                <div class="step-dot">2</div>
                <div class="step-label">Pricing & Stock</div>
            </div>
            <div class="step-line"></div>
            <div>
                <div class="step-dot">3</div>
                <div class="step-label">Images</div>
            </div>
        </div>

        <form id="multiStepForm" enctype="multipart/form-data" novalidate>

            <!-- Step 1: Basic Info -->
            <div class="form-step">
                <div class="app-card">
                    <h6 class="mb-4"><i class="bi bi-info-circle-fill text-accent me-2"></i>Basic Information</h6>
                    <div class="mb-3">
                        <label class="form-label">Product Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. iPhone 15 Pro" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="4" placeholder="Describe your product..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select name="category_id" class="form-select">
                            <option value="">— Select Category —</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="d-flex justify-content-end">
                        <button type="button" class="btn btn-accent btn-next">Next: Pricing <i class="bi bi-arrow-right ms-1"></i></button>
                    </div>
                </div>
            </div>

            <!-- Step 2: Pricing & Stock -->
            <div class="form-step">
                <div class="app-card">
                    <h6 class="mb-4"><i class="bi bi-currency-dollar text-accent me-2"></i>Pricing & Stock</h6>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Price (USD) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" name="price" class="form-control" placeholder="0.00" step="0.01" min="0" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Stock Quantity <span class="text-danger">*</span></label>
                            <input type="number" name="stock" class="form-control" placeholder="0" min="0" required>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="out_of_stock">Out of Stock</option>
                        </select>
                    </div>
                    <div class="d-flex justify-content-between">
                        <button type="button" class="btn btn-outline-secondary btn-prev"><i class="bi bi-arrow-left me-1"></i>Back</button>
                        <button type="button" class="btn btn-accent btn-next">Next: Images <i class="bi bi-arrow-right ms-1"></i></button>
                    </div>
                </div>
            </div>

            <!-- Step 3: Images -->
            <div class="form-step">
                <div class="app-card">
                    <h6 class="mb-4"><i class="bi bi-images text-accent me-2"></i>Product Images</h6>

                    <div class="upload-zone mb-3" id="uploadZone">
                        <input type="file" name="images[]" id="productImages" multiple accept="image/*" style="display:none">
                        <i class="bi bi-cloud-arrow-up-fill"></i>
                        <p class="text-muted mb-1">Click or drag & drop images here</p>
                        <small class="text-muted">JPG, PNG, WEBP — Max 5MB each</small>
                    </div>

                    <div class="image-preview-grid" id="imagePreview"></div>

                    <div class="d-flex justify-content-between mt-4">
                        <button type="button" class="btn btn-outline-secondary btn-prev"><i class="bi bi-arrow-left me-1"></i>Back</button>
                        <button type="button" class="btn btn-accent" id="submitBtn">
                            <i class="bi bi-check-circle me-2"></i>Create Product
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
document.getElementById('submitBtn').addEventListener('click', async () => {
    const form = document.getElementById('multiStepForm');
    const name = form.querySelector('[name=name]').value.trim();
    if (!name) { showToast('Product name is required.', 'error'); return; }
    const price = form.querySelector('[name=price]').value;
    if (!price || parseFloat(price) < 0) { showToast('Enter a valid price.', 'error'); return; }

    const btn = document.getElementById('submitBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Creating...';

    const fd = new FormData(form);
    fd.append('action', 'create');

    const data = await apiPost('api/products.php', fd);
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-check-circle me-2"></i>Create Product';

    if (data.success) {
        showToast('Product created successfully!', 'success');
        setTimeout(() => window.location = 'view_product.php?id=' + data.id, 800);
    } else {
        showToast(data.message || 'Failed to create product.', 'error');
    }
});
</script>
