<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/classes/Database.php';
require_once dirname(__DIR__) . '/classes/Auth.php';
require_once dirname(__DIR__) . '/classes/Product.php';

$auth = new Auth();
$auth->requireAuth(BASE_URL . '/login.php');

$product = new Product();
$cats    = $product->getCategories();
$pageTitle = 'Products';
?>
<?php include dirname(__DIR__) . '/includes/header.php'; ?>
<meta name="base-url" content="<?= BASE_URL ?>">

<div class="app-wrapper">
    <?php include dirname(__DIR__) . '/includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="topbar">
            <button class="topbar-toggle" id="sidebarToggle"><i class="bi bi-list"></i></button>
            <div class="topbar-title">Products</div>
            <div class="topbar-actions">
                <a href="<?= BASE_URL ?>/products/add.php" class="btn-accent">
                    <i class="bi bi-plus-lg"></i> Add Product
                </a>
            </div>
        </div>

        <div class="content-area">
            <div class="panel">
                <!-- Filter Bar -->
                <div class="filter-bar">
                    <div class="search-wrap">
                        <i class="bi bi-search"></i>
                        <input type="text" id="searchInput" class="form-control search-input"
                               placeholder="Search products...">
                    </div>

                    <select id="categoryFilter" class="form-select" style="width:160px">
                        <option value="">All Categories</option>
                        <?php foreach ($cats as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <select id="statusFilter" class="form-select" style="width:140px">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>

                    <select id="perPageSelect" class="form-select" style="width:100px">
                        <option value="10">10 / page</option>
                        <option value="25">25 / page</option>
                        <option value="50">50 / page</option>
                    </select>

                    <button id="resetFilters" class="btn-ghost" title="Reset">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </button>
                </div>

                <!-- Table -->
                <div class="table-wrap">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th style="width:44px"></th>
                                <th class="sortable" data-col="name">
                                    Product <i class="bi bi-arrow-down-up" style="font-size:10px;opacity:.5"></i>
                                </th>
                                <th class="sortable" data-col="price">
                                    Price <i class="bi bi-arrow-down-up" style="font-size:10px;opacity:.5"></i>
                                </th>
                                <th class="sortable" data-col="stock">
                                    Stock <i class="bi bi-arrow-down-up" style="font-size:10px;opacity:.5"></i>
                                </th>
                                <th>Category</th>
                                <th class="sortable" data-col="status">
                                    Status <i class="bi bi-arrow-down-up" style="font-size:10px;opacity:.5"></i>
                                </th>
                                <th class="sortable" data-col="created_at">
                                    Added <i class="bi bi-arrow-down-up" style="font-size:10px;opacity:.5"></i>
                                </th>
                                <th style="text-align:right">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="productTableBody">
                            <tr><td colspan="8"><div class="spinner-wrap"><div class="spinner"></div> Loading...</div></td></tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="pagination-wrap">
                    <div class="pagination-info" id="paginationInfo"></div>
                    <div class="pagination-btns" id="paginationBtns"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Confirm Delete Modal -->
<div class="modal fade" id="confirmModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title text-danger"><i class="bi bi-trash3 me-2"></i>Delete Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="confirmModalBody">
                Are you sure you want to delete this product? This action cannot be undone.
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
const BASE = '<?= BASE_URL ?>';
const isAdmin = <?= $auth->isAdmin() ? 'true' : 'false' ?>;

let state = {
    search: '', category: '', status: '',
    sort: 'created_at', order: 'DESC',
    page: 1, perPage: 10,
    total: 0, lastPage: 1,
};

async function loadProducts() {
    const tbody = document.getElementById('productTableBody');
    tbody.innerHTML = '<tr><td colspan="8"><div class="spinner-wrap"><div class="spinner"></div> Loading...</div></td></tr>';

    const params = new URLSearchParams({
        action: 'list',
        search:   state.search,
        category: state.category,
        status:   state.status,
        sort:     state.sort,
        order:    state.order,
        page:     state.page,
        per_page: state.perPage,
    });

    const res = await apiCall(`${BASE}/api/products.php?${params}`);
    if (!res.success) { toast.error(res.message || 'Failed to load'); return; }

    const { data: products, total, page, per_page, last_page } = res.data;
    state.total = total; state.lastPage = last_page;

    if (products.length === 0) {
        tbody.innerHTML = `<tr><td colspan="8">
            <div class="empty-state">
                <i class="bi bi-box-seam"></i>
                <h3>No products found</h3>
                <p>Try adjusting your search or filters</p>
                <a href="${BASE}/products/add.php" class="btn-accent mt-2"><i class="bi bi-plus"></i> Add Product</a>
            </div></td></tr>`;
    } else {
        tbody.innerHTML = products.map(p => {
            const img = p.primary_image
                ? `<img src="${BASE}/uploads/products/${p.primary_image}" class="product-thumb" alt="">`
                : `<div class="product-thumb-placeholder"><i class="bi bi-image"></i></div>`;

            const stockCls = p.stock == 0 ? 'stock-none' : (p.stock < 10 ? 'stock-low' : 'stock-ok');
            const catBadge = p.category_name
                ? `<span class="badge-status badge-cat">${p.category_name}</span>`
                : '<span style="color:var(--text-3)">—</span>';

            const deleteBtn = isAdmin
                ? `<button class="btn-icon danger" onclick="deleteProduct(${p.id},'${p.name.replace(/'/g,"\\'")}')"><i class="bi bi-trash3"></i></button>`
                : '';

            const added = new Date(p.created_at).toLocaleDateString('en-US', {month:'short',day:'numeric',year:'numeric'});

            return `<tr>
                <td>${img}</td>
                <td>
                    <div style="font-weight:600;font-size:13px">${p.name}</div>
                    <div style="font-size:11px;color:var(--text-3);margin-top:2px">ID #${p.id}</div>
                </td>
                <td class="text-mono" style="color:var(--accent);font-weight:600">$${parseFloat(p.price).toFixed(2)}</td>
                <td class="text-mono ${stockCls} fw-semibold">${p.stock}</td>
                <td>${catBadge}</td>
                <td><span class="badge-status badge-${p.status}">${p.status.charAt(0).toUpperCase()+p.status.slice(1)}</span></td>
                <td style="font-size:12px;color:var(--text-2)">${added}</td>
                <td style="text-align:right">
                    <div class="d-flex justify-content-end gap-1">
                        <a href="${BASE}/products/view.php?id=${p.id}" class="btn-icon success" title="View"><i class="bi bi-eye"></i></a>
                        <a href="${BASE}/products/edit.php?id=${p.id}" class="btn-icon" title="Edit"><i class="bi bi-pencil"></i></a>
                        ${deleteBtn}
                    </div>
                </td>
            </tr>`;
        }).join('');
    }

    renderPagination();
}

function renderPagination() {
    const info = document.getElementById('paginationInfo');
    const btns = document.getElementById('paginationBtns');
    const from = (state.page - 1) * state.perPage + 1;
    const to   = Math.min(state.page * state.perPage, state.total);

    info.textContent = state.total > 0 ? `Showing ${from}–${to} of ${state.total} products` : 'No products found';

    btns.innerHTML = '';
    const addBtn = (label, page, disabled, active) => {
        const btn = document.createElement('button');
        btn.className = 'page-btn' + (active ? ' active' : '');
        btn.innerHTML = label;
        btn.disabled = disabled;
        btn.addEventListener('click', () => { state.page = page; loadProducts(); });
        btns.appendChild(btn);
    };

    addBtn('<i class="bi bi-chevron-left"></i>', state.page - 1, state.page === 1, false);

    const start = Math.max(1, state.page - 2);
    const end   = Math.min(state.lastPage, start + 4);
    for (let i = start; i <= end; i++) addBtn(i, i, false, i === state.page);

    addBtn('<i class="bi bi-chevron-right"></i>', state.page + 1, state.page >= state.lastPage, false);
}

// Sort
document.querySelectorAll('.sortable').forEach(th => {
    th.addEventListener('click', () => {
        const col = th.dataset.col;
        if (state.sort === col) state.order = state.order === 'ASC' ? 'DESC' : 'ASC';
        else { state.sort = col; state.order = 'ASC'; }

        document.querySelectorAll('.sortable').forEach(t => t.classList.remove('sort-active'));
        th.classList.add('sort-active');
        state.page = 1;
        loadProducts();
    });
});

// Search with debounce
let searchTimer;
document.getElementById('searchInput').addEventListener('input', e => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        state.search = e.target.value.trim();
        state.page = 1;
        loadProducts();
    }, 400);
});

// Filters
document.getElementById('categoryFilter').addEventListener('change', e => { state.category = e.target.value; state.page = 1; loadProducts(); });
document.getElementById('statusFilter').addEventListener('change', e => { state.status = e.target.value; state.page = 1; loadProducts(); });
document.getElementById('perPageSelect').addEventListener('change', e => { state.perPage = parseInt(e.target.value); state.page = 1; loadProducts(); });

// Reset
document.getElementById('resetFilters').addEventListener('click', () => {
    state = { search:'', category:'', status:'', sort:'created_at', order:'DESC', page:1, perPage:10, total:0, lastPage:1 };
    document.getElementById('searchInput').value = '';
    document.getElementById('categoryFilter').value = '';
    document.getElementById('statusFilter').value = '';
    document.getElementById('perPageSelect').value = '10';
    loadProducts();
});

// Delete
function deleteProduct(id, name) {
    confirmDelete(`Delete "${name}"? This will remove all images and cannot be undone.`, async () => {
        const res = await apiCall(`${BASE}/api/products.php?action=delete&id=${id}`, { method: 'POST', body: '{}' });
        if (res.success) { toast.success('Product deleted'); loadProducts(); }
        else toast.error(res.message || 'Delete failed');
    });
}

// Initial load
loadProducts();
</script>
