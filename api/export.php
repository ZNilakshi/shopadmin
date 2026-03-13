<?php
require_once '../config.php';
require_once '../classes/Database.php';
require_once '../classes/Auth.php';
require_once '../classes/Product.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header('Location: ../index.php');
    exit;
}

$product  = new Product();
$format   = $_GET['export']   ?? 'csv';
$search   = $_GET['search']   ?? '';
$category = $_GET['category'] ?? '';

$rows = $product->getAllForExport($search, $category);

$filename = 'products_' . date('Y-m-d');

if ($format === 'csv') {
    // ── CSV Export ──────────────────────────
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM for Excel

    fputcsv($out, ['#', 'Product Name', 'Category', 'Price (USD)', 'Stock', 'Status', 'Date Added']);

    foreach ($rows as $i => $p) {
        fputcsv($out, [
            $i + 1,
            $p['name'],
            $p['category'] ?? 'Uncategorized',
            number_format($p['price'], 2),
            $p['stock'],
            ucfirst(str_replace('_', ' ', $p['status'])),
            date('Y-m-d', strtotime($p['created_at']))
        ]);
    }
    fclose($out);

} elseif ($format === 'pdf') {
    // ── HTML Print Page (acts as PDF) ───────
    $isAdmin = $auth->isAdmin();
    $count   = count($rows);
    $stats   = $product->getStats();

    $statusColors = ['active' => '#d1fae5', 'inactive' => '#f3f4f6', 'out_of_stock' => '#fee2e2'];
    $statusText   = ['active' => '#065f46', 'inactive' => '#6b7280', 'out_of_stock' => '#991b1b'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Product Export — ShopAdmin</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'DM Sans', sans-serif; color: #111827; background: white; font-size: 12px; }
        .page { padding: 32px; max-width: 1000px; margin: 0 auto; }

        /* Header */
        .report-header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 28px; padding-bottom: 20px; border-bottom: 3px solid #10b981; }
        .brand { font-family: 'Syne', sans-serif; font-size: 22px; font-weight: 800; color: #0d1117; display: flex; align-items: center; gap: 8px; }
        .brand-dot { width: 10px; height: 10px; background: #10b981; border-radius: 50%; }
        .report-meta { text-align: right; color: #6b7280; font-size: 11px; line-height: 1.8; }
        .report-meta strong { color: #111827; }

        /* Stats row */
        .stats-row { display: flex; gap: 12px; margin-bottom: 24px; }
        .stat-box { flex: 1; background: #f9fafb; border-radius: 8px; padding: 12px 14px; border: 1px solid #e5e7eb; }
        .stat-box .val { font-family: 'Syne', sans-serif; font-size: 20px; font-weight: 800; color: #111827; }
        .stat-box .lbl { color: #6b7280; font-size: 10px; margin-top: 2px; text-transform: uppercase; letter-spacing: 0.05em; }

        /* Table */
        table { width: 100%; border-collapse: collapse; margin-top: 4px; }
        thead tr { background: #0d1117; color: white; }
        thead th { padding: 10px 12px; text-align: left; font-family: 'Syne', sans-serif; font-size: 10px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; }
        tbody tr:nth-child(even) { background: #f9fafb; }
        tbody tr:hover { background: #f0fdf4; }
        td { padding: 9px 12px; border-bottom: 1px solid #e5e7eb; vertical-align: middle; }
        .badge { display: inline-block; padding: 3px 8px; border-radius: 12px; font-size: 10px; font-weight: 600; }
        .price { font-family: 'Syne', sans-serif; font-weight: 700; color: #10b981; }
        .stock-low { color: #d97706; font-weight: 600; }
        .stock-zero { color: #dc2626; font-weight: 700; }

        /* Footer */
        .report-footer { margin-top: 24px; padding-top: 16px; border-top: 1px solid #e5e7eb; display: flex; justify-content: space-between; color: #9ca3af; font-size: 10px; }

        /* Print controls (hidden when printing) */
        .print-bar { padding: 12px 32px; background: #0d1117; display: flex; align-items: center; gap: 12px; position: sticky; top: 0; z-index: 100; }
        .print-bar button { padding: 8px 20px; border: none; border-radius: 6px; font-family: 'DM Sans', sans-serif; font-weight: 600; cursor: pointer; font-size: 13px; display: flex; align-items: center; gap: 6px; }
        .btn-print { background: #10b981; color: white; }
        .btn-close-bar { background: transparent; color: #8b949e; border: 1px solid #30363d !important; }
        .print-bar span { color: #8b949e; font-size: 13px; margin-left: auto; }

        @media print {
            .print-bar { display: none !important; }
            body { padding: 0; }
            .page { padding: 20px; }
        }
    </style>
</head>
<body>

<!-- Print Controls -->
<div class="print-bar">
    <button class="btn-print" onclick="window.print()">🖨 Print / Save as PDF</button>
    <button class="btn-close-bar" onclick="window.close()">✕ Close</button>
    <span><?= $count ?> products exported · <?= date('F j, Y') ?></span>
</div>

<div class="page">
    <!-- Header -->
    <div class="report-header">
        <div>
            <div class="brand"><div class="brand-dot"></div>ShopAdmin</div>
            <div style="color:#6b7280;margin-top:6px;font-size:12px">Product Inventory Report</div>
        </div>
        <div class="report-meta">
            <div>Generated: <strong><?= date('F j, Y \a\t g:i A') ?></strong></div>
            <div>Exported by: <strong><?= htmlspecialchars($_SESSION['user_name']) ?></strong></div>
            <div>Role: <strong><?= ucfirst($_SESSION['user_role']) ?></strong></div>
            <?php if ($search || $category): ?>
            <div>Filters: <strong><?= $search ? "Search: $search" : '' ?> <?= $category ? "Cat: $category" : '' ?></strong></div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Summary Stats -->
    <div class="stats-row">
        <div class="stat-box">
            <div class="val"><?= $count ?></div>
            <div class="lbl">Products in Report</div>
        </div>
        <div class="stat-box">
            <div class="val" style="color:#10b981"><?= $stats['active'] ?></div>
            <div class="lbl">Active</div>
        </div>
        <div class="stat-box">
            <div class="val" style="color:#dc2626"><?= $stats['out_of_stock'] ?></div>
            <div class="lbl">Out of Stock</div>
        </div>
        <div class="stat-box">
            <div class="val" style="color:#d97706"><?= $stats['low_stock'] ?></div>
            <div class="lbl">Low Stock</div>
        </div>
        <div class="stat-box">
            <div class="val" style="color:#10b981;font-size:16px">$<?= number_format($stats['total_value'], 0) ?></div>
            <div class="lbl">Inventory Value</div>
        </div>
    </div>

    <!-- Table -->
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Product Name</th>
                <th>Category</th>
                <th>Price</th>
                <th>Stock</th>
                <th>Status</th>
                <th>Date Added</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $i => $p):
                $sc = $statusColors[$p['status']] ?? '#f3f4f6';
                $st = $statusText[$p['status']]   ?? '#6b7280';
            ?>
            <tr>
                <td style="color:#9ca3af"><?= $i+1 ?></td>
                <td style="font-weight:600"><?= htmlspecialchars($p['name']) ?></td>
                <td><?= htmlspecialchars($p['category'] ?? '—') ?></td>
                <td class="price">$<?= number_format($p['price'], 2) ?></td>
                <td class="<?= $p['stock'] == 0 ? 'stock-zero' : ($p['stock'] <= 10 ? 'stock-low' : '') ?>">
                    <?= $p['stock'] ?><?= $p['stock'] <= 10 && $p['stock'] > 0 ? ' ⚠' : '' ?>
                </td>
                <td>
                    <span class="badge" style="background:<?= $sc ?>;color:<?= $st ?>">
                        <?= ucfirst(str_replace('_',' ',$p['status'])) ?>
                    </span>
                </td>
                <td><?= date('M j, Y', strtotime($p['created_at'])) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Footer -->
    <div class="report-footer">
        <span>ShopAdmin — Inventory Management System</span>
        <span>Confidential — For internal use only</span>
        <span>Total: <?= $count ?> records</span>
    </div>
</div>

</body>
</html>
<?php
}
