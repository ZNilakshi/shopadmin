<?php
require_once 'config.php';
require_once 'classes/Database.php';
require_once 'classes/Auth.php';
require_once 'classes/Product.php';

$auth = new Auth();
$auth->requireLogin();

$product  = new Product();
$search   = $_GET['search']   ?? '';
$category = $_GET['category'] ?? '';
$sort     = $_GET['sort']     ?? 'created_at';
$order    = $_GET['order']    ?? 'DESC';
$page     = max(1, (int)($_GET['page'] ?? 1));
$limit    = 10;
$offset   = ($page - 1) * $limit;

$products   = $product->getAll($search, $category, $sort, $order, $limit, $offset);
$total      = $product->count($search, $category);
$totalPages = (int)ceil($total / $limit);
$categories = $product->getCategories();

$pageTitle  = 'Products';
$activePage = 'products';
include 'includes/header.php';
?>

<!-- Toolbar -->
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div class="d-flex flex-wrap gap-2 flex-grow-1">
        <!-- Search -->
        <div class="search-bar flex-grow-1" style="max-width:320px">
            <i class="bi bi-search"></i>
            <input type="text" id="searchInput" class="form-control" placeholder="Search products..." value="<?= htmlspecialchars($search) ?>">
        </div>
        <!-- Category Filter -->
        <select id="categoryFilter" class="form-select" style="width:auto">
            <option value="">All Categories</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>" <?= $category == $cat['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cat['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="d-flex gap-2 align-items-center">
        <span class="text-muted small" id="totalCount"><?= $total ?> products</span>
        <!-- Export Buttons -->
        <button onclick="exportCSV()" class="export-btn csv" title="Export to CSV">
            <i class="bi bi-filetype-csv"></i> CSV
        </button>
        <button onclick="exportPDF()" class="export-btn pdf" title="Export to PDF">
            <i class="bi bi-filetype-pdf"></i> PDF
        </button>
        <a href="add_product.php" class="btn btn-accent btn-sm">
            <i class="bi bi-plus-lg me-1"></i>Add New
        </a>
    </div>
</div>

<!-- Products Table -->
<div class="app-card p-0 overflow-hidden">
    <?php if (empty($products)): ?>
        <div class="text-center py-5">
            <i class="bi bi-boxes text-muted" style="font-size:3rem"></i>
            <p class="text-muted mt-3 mb-2">No products found.</p>
            <a href="add_product.php" class="btn btn-accent btn-sm">Add your first product</a>
        </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="product-table">
            <thead>
                <tr>
                    <th style="width:50px">#</th>
                    <th style="width:60px">Image</th>
                    <th data-sort="name">Name <i class="bi bi-arrow-down-up sort-icon ms-1"></i></th>
                    <th data-sort="price">Price <i class="bi bi-arrow-down-up sort-icon ms-1"></i></th>
                    <th data-sort="stock">Stock <i class="bi bi-arrow-down-up sort-icon ms-1"></i></th>
                    <th>Category</th>
                    <th data-sort="status">Status <i class="bi bi-arrow-down-up sort-icon ms-1"></i></th>
                    <?php if ($auth->isAdmin()): ?><th>Added By</th><?php endif; ?>
                    <th style="width:130px">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $i => $p): ?>
                <tr id="row-<?= $p['id'] ?>">
                    <td class="text-muted"><?= $offset + $i + 1 ?></td>
                    <td>
                        <?php if ($p['thumbnail']): ?>
                            <img src="uploads/<?= htmlspecialchars($p['thumbnail']) ?>" class="product-thumb" alt="">
                        <?php else: ?>
                            <div class="product-thumb-placeholder"><i class="bi bi-image"></i></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="view_product.php?id=<?= $p['id'] ?>" class="fw-600 text-decoration-none text-dark">
                            <?= htmlspecialchars($p['name']) ?>
                        </a>
                    </td>
                    <td><strong>$<?= number_format($p['price'], 2) ?></strong></td>
                    <td>
                        <?php if ($p['stock'] === 0): ?>
                            <span class="text-danger fw-600">0</span>
                        <?php elseif ($p['stock'] <= 10): ?>
                            <span class="text-warning fw-600"><?= $p['stock'] ?> ⚠️</span>
                        <?php else: ?>
                            <?= $p['stock'] ?>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($p['category_name'] ?? '—') ?></td>
                    <td>
                        <span class="badge-status badge-<?= $p['status'] ?>">
                            <?= ucfirst(str_replace('_', ' ', $p['status'])) ?>
                        </span>
                    </td>
                    <?php if ($auth->isAdmin()): ?>
                    <td>
                        <span class="badge badge-sm <?= isset($p['creator_name']) ? 'bg-light text-dark border' : 'bg-secondary' ?>">
                            <?= htmlspecialchars($p['creator_name'] ?? 'Unknown') ?>
                        </span>
                    </td>
                    <?php endif; ?>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="view_product.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-secondary" title="View">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="edit_product.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <?php if ($auth->isAdmin()): ?>
                            <button class="btn btn-sm btn-outline-danger" title="Delete"
                                onclick="confirmDelete(<?= $p['id'] ?>, '<?= addslashes($p['name']) ?>')">
                                <i class="bi bi-trash"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="d-flex align-items-center justify-content-between p-3 border-top">
        <small class="text-muted">
            Showing <?= $offset+1 ?>–<?= min($offset+$limit, $total) ?> of <?= $total ?>
        </small>
        <nav>
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page-1])) ?>">‹</a>
                </li>
                <?php for ($i = max(1,$page-2); $i <= min($totalPages,$page+2); $i++): ?>
                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                </li>
                <?php endfor; ?>
                <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page+1])) ?>">›</a>
                </li>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
