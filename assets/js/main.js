/* ============================================
   ShopAdmin — Main JavaScript
   ============================================ */

// ── Toast ──────────────────────────────────
function showToast(message, type = 'success') {
    const el = document.getElementById('appToast');
    const msg = document.getElementById('toastMessage');
    if (!el || !msg) return;
    el.className = 'toast align-items-center border-0 ' + type;
    msg.textContent = message;
    const toast = new bootstrap.Toast(el, { delay: 3500 });
    toast.show();
}

// ── Sidebar Toggle (Mobile) ─────────────────
const sidebarToggle = document.getElementById('sidebarToggle');
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('sidebarOverlay');

if (sidebarToggle) {
    sidebarToggle.addEventListener('click', () => {
        sidebar.classList.toggle('open');
        overlay.classList.toggle('active');
    });
}
if (overlay) {
    overlay.addEventListener('click', () => {
        sidebar.classList.remove('open');
        overlay.classList.remove('active');
    });
}

// ── AJAX Helper ────────────────────────────
async function apiPost(url, formData) {
    try {
        const res = await fetch(url, { method: 'POST', body: formData });
        return await res.json();
    } catch (e) {
        return { success: false, message: 'Network error. Please try again.' };
    }
}

async function apiGet(url) {
    try {
        const res = await fetch(url);
        return await res.json();
    } catch (e) {
        return { success: false, message: 'Network error.' };
    }
}

// ── Image Preview ───────────────────────────
function initImageUpload(inputId, previewId) {
    const input = document.getElementById(inputId);
    const preview = document.getElementById(previewId);
    if (!input || !preview) return;

    const zone = input.closest('.upload-zone') || input.parentElement;

    zone.addEventListener('click', () => input.click());
    zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('drag-over'); });
    zone.addEventListener('dragleave', () => zone.classList.remove('drag-over'));
    zone.addEventListener('drop', e => {
        e.preventDefault();
        zone.classList.remove('drag-over');
        const dt = new DataTransfer();
        [...(input.files || [])].forEach(f => dt.items.add(f));
        [...e.dataTransfer.files].forEach(f => dt.items.add(f));
        input.files = dt.files;
        renderPreviews(input, preview);
    });

    input.addEventListener('change', () => renderPreviews(input, preview));
}

function renderPreviews(input, preview) {
    preview.innerHTML = '';
    [...input.files].forEach((file, i) => {
        if (!file.type.startsWith('image/')) return;
        const reader = new FileReader();
        reader.onload = e => {
            const div = document.createElement('div');
            div.className = 'img-preview-item';
            div.innerHTML = `
                <img src="${e.target.result}" alt="preview">
                <button type="button" class="del-btn" data-index="${i}" title="Remove">
                    <i class="bi bi-x"></i>
                </button>`;
            div.querySelector('.del-btn').addEventListener('click', () => {
                const dt = new DataTransfer();
                [...input.files].filter((_, idx) => idx !== i).forEach(f => dt.items.add(f));
                input.files = dt.files;
                renderPreviews(input, preview);
            });
            preview.appendChild(div);
        };
        reader.readAsDataURL(file);
    });
}

// ── Confirm Delete ──────────────────────────
function confirmDelete(id, name) {
    if (!confirm(`Delete "${name}"? This cannot be undone.`)) return;
    const fd = new FormData();
    fd.append('action', 'delete');
    fd.append('id', id);
    apiPost('api/products.php', fd).then(data => {
        if (data.success) {
            showToast('Product deleted.', 'success');
            const row = document.getElementById('row-' + id);
            if (row) row.remove();
            // update count if shown
            const countEl = document.getElementById('totalCount');
            if (countEl) countEl.textContent = parseInt(countEl.textContent) - 1;
        } else {
            showToast(data.message || 'Delete failed.', 'error');
        }
    });
}

// ── Delete Image (Edit Page) ────────────────
function deleteExistingImage(imageId, el) {
    if (!confirm('Delete this image?')) return;
    const fd = new FormData();
    fd.append('action', 'delete_image');
    fd.append('image_id', imageId);
    apiPost('api/products.php', fd).then(data => {
        if (data.success) {
            el.closest('.img-preview-item').remove();
            showToast('Image removed.', 'success');
        } else {
            showToast(data.message, 'error');
        }
    });
}

// ── Table Sort ──────────────────────────────
function initTableSort() {
    const headers = document.querySelectorAll('.product-table th[data-sort]');
    const params = new URLSearchParams(window.location.search);
    const currentSort = params.get('sort') || 'created_at';
    const currentOrder = params.get('order') || 'DESC';

    headers.forEach(th => {
        const col = th.dataset.sort;
        const icon = th.querySelector('.sort-icon');
        if (col === currentSort) {
            th.classList.add('active-sort');
            if (icon) icon.className = 'bi sort-icon ms-1 ' +
                (currentOrder === 'ASC' ? 'bi-sort-up' : 'bi-sort-down');
        }
        th.addEventListener('click', () => {
            const order = (col === currentSort && currentOrder === 'ASC') ? 'DESC' : 'ASC';
            params.set('sort', col);
            params.set('order', order);
            params.set('page', '1');
            window.location.search = params.toString();
        });
    });
}

// ── Search Debounce ─────────────────────────
function initSearch(inputId) {
    const input = document.getElementById(inputId);
    if (!input) return;
    let timer;
    input.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(() => {
            const params = new URLSearchParams(window.location.search);
            params.set('search', input.value);
            params.set('page', '1');
            window.location.search = params.toString();
        }, 500);
    });
}

// ── Login / Register form ───────────────────
function initAuthForms() {
    const loginForm = document.getElementById('loginForm');
    const registerForm = document.getElementById('registerForm');

    if (loginForm) {
        loginForm.addEventListener('submit', async e => {
            e.preventDefault();
            const btn = loginForm.querySelector('[type=submit]');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Signing in...';
            const data = await apiPost('api/auth.php', new FormData(loginForm));
            btn.disabled = false;
            btn.innerHTML = 'Sign In';
            if (data.success) {
                showToast('Welcome back!', 'success');
                setTimeout(() => window.location = 'dashboard.php', 700);
            } else {
                showToast(data.message, 'error');
                loginForm.querySelector('[name=email]').classList.add('is-invalid');
                loginForm.querySelector('[name=password]').classList.add('is-invalid');
            }
        });
    }

    if (registerForm) {
        registerForm.addEventListener('submit', async e => {
            e.preventDefault();
            const btn = registerForm.querySelector('[type=submit]');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Creating...';
            const data = await apiPost('api/auth.php', new FormData(registerForm));
            btn.disabled = false;
            btn.innerHTML = 'Create Account';
            if (data.success) {
                showToast(data.message, 'success');
                setTimeout(() => {
                    document.querySelector('[data-tab="login"]').click();
                }, 1200);
            } else {
                showToast(data.message, 'error');
            }
        });
    }
}

// ── Init on DOM Ready ───────────────────────
document.addEventListener('DOMContentLoaded', () => {
    initTableSort();
    initSearch('searchInput');
    initAuthForms();

    // Image upload zones
    initImageUpload('productImages', 'imagePreview');

    // Category filter
    const catFilter = document.getElementById('categoryFilter');
    if (catFilter) {
        catFilter.addEventListener('change', () => {
            const params = new URLSearchParams(window.location.search);
            params.set('category', catFilter.value);
            params.set('page', '1');
            window.location.search = params.toString();
        });
    }

    // Auth tabs
    const tabBtns = document.querySelectorAll('.auth-tab-btn');
    tabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            tabBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            const target = btn.dataset.tab;
            document.querySelectorAll('.auth-panel').forEach(p => {
                p.style.display = p.id === target + 'Panel' ? 'block' : 'none';
            });
        });
    });

    // Profile picture upload
    const picBtn = document.querySelector('.avatar-upload-btn');
    if (picBtn) {
        picBtn.addEventListener('click', () => document.getElementById('picInput').click());
        document.getElementById('picInput')?.addEventListener('change', async function() {
            const fd = new FormData();
            fd.append('action', 'update_picture');
            fd.append('picture', this.files[0]);
            const data = await apiPost('api/profile.php', fd);
            if (data.success) {
                showToast('Profile picture updated!', 'success');
                const img = document.querySelector('.profile-avatar-wrap img');
                if (img) img.src = data.url + '?' + Date.now();
            } else {
                showToast(data.message, 'error');
            }
        });
    }

    // Multi-step form
    initMultiStep();
});

// ── Multi-step form ─────────────────────────
function initMultiStep() {
    const form = document.getElementById('multiStepForm');
    if (!form) return;

    let currentStep = 1;
    const steps = form.querySelectorAll('.form-step');
    const dots = document.querySelectorAll('.step-dot');
    const lines = document.querySelectorAll('.step-line');

    function showStep(n) {
        steps.forEach((s, i) => s.style.display = (i === n - 1) ? 'block' : 'none');
        dots.forEach((d, i) => {
            d.classList.toggle('active', i === n - 1);
            d.classList.toggle('done', i < n - 1);
        });
        lines.forEach((l, i) => l.classList.toggle('done', i < n - 1));
        currentStep = n;
    }

    showStep(1);

    form.querySelectorAll('.btn-next').forEach(btn => {
        btn.addEventListener('click', () => {
            if (currentStep < steps.length) showStep(currentStep + 1);
        });
    });
    form.querySelectorAll('.btn-prev').forEach(btn => {
        btn.addEventListener('click', () => {
            if (currentStep > 1) showStep(currentStep - 1);
        });
    });
}

/* ============================================
   BONUS FEATURES
   ============================================ */

// ── Dark Mode ───────────────────────────────
function initDarkMode() {
    // Apply saved theme immediately (icon + attribute)
    const theme = localStorage.getItem('theme') || 'light';
    applyTheme(theme);

    // Wire up the toggle button
    const toggle = document.getElementById('darkToggle');
    if (toggle) {
        // Remove any duplicate listeners by replacing the element
        const fresh = toggle.cloneNode(true);
        toggle.parentNode.replaceChild(fresh, toggle);
        fresh.addEventListener('click', () => {
            const current = document.documentElement.getAttribute('data-theme') || 'light';
            const next = current === 'dark' ? 'light' : 'dark';
            applyTheme(next);
            localStorage.setItem('theme', next);
        });
    }
}

function applyTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    // Update whichever toggle button is on the page
    const toggle = document.getElementById('darkToggle');
    if (toggle) {
        toggle.innerHTML = theme === 'dark'
            ? '<i class="bi bi-sun-fill"></i>'
            : '<i class="bi bi-moon-fill"></i>';
        toggle.title = theme === 'dark' ? 'Switch to Light Mode' : 'Switch to Dark Mode';
    }
}

// ── CSV Export ──────────────────────────────
function exportCSV() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', 'csv');
    window.location = 'api/export.php?' + params.toString();
}

// ── PDF Export ──────────────────────────────
function exportPDF() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', 'pdf');
    window.open('api/export.php?' + params.toString(), '_blank');
}

// ── Image Drag & Drop Reorder (SortableJS) ──
function initSortableImages(containerId, productId) {
    const container = document.getElementById(containerId);
    if (!container || typeof Sortable === 'undefined') return;

    Sortable.create(container, {
        animation: 150,
        ghostClass: 'sortable-ghost',
        dragClass: 'sortable-drag',
        handle: '.drag-handle',
        onEnd: () => {
            updateOrderBadges(container);
        }
    });
}

function updateOrderBadges(container) {
    const items = container.querySelectorAll('.sortable-img-item');
    items.forEach((item, i) => {
        const badge = item.querySelector('.order-badge');
        if (badge) badge.textContent = i === 0 ? '★ Main' : '#' + (i + 1);
        if (i === 0) item.classList.add('primary-image');
        else item.classList.remove('primary-image');
    });
}

async function saveImageOrder(productId) {
    const container = document.getElementById('sortableImages');
    if (!container) return;
    const items = container.querySelectorAll('.sortable-img-item');
    const order = [...items].map((el, i) => ({
        id: el.dataset.imageId,
        order: i
    }));

    const btn = document.getElementById('saveOrderBtn');
    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="bi bi-arrow-repeat spin me-1"></i>Saving...'; }

    const fd = new FormData();
    fd.append('action', 'reorder_images');
    fd.append('product_id', productId);
    fd.append('order', JSON.stringify(order));
    const data = await apiPost('api/products.php', fd);

    if (btn) { btn.disabled = false; btn.innerHTML = '<i class="bi bi-check2 me-1"></i>Order Saved!'; }
    showToast(data.success ? 'Image order saved!' : data.message, data.success ? 'success' : 'error');
    setTimeout(() => {
        if (btn) btn.innerHTML = '<i class="bi bi-arrows-move me-1"></i>Save Order';
    }, 2000);
}

// initDarkMode() is called directly in each page's <script> tag.
// Do NOT call it here to avoid double-binding the click listener.