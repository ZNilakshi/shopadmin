<?php
require_once '../config.php';
require_once '../classes/Database.php';
require_once '../classes/Auth.php';
require_once '../classes/Product.php';

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

$product = new Product();
$method  = $_SERVER['REQUEST_METHOD'];
$action  = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'list':
        $search   = $_GET['search'] ?? '';
        $category = $_GET['category'] ?? '';
        $sort     = $_GET['sort'] ?? 'created_at';
        $order    = $_GET['order'] ?? 'DESC';
        $page     = max(1, (int)($_GET['page'] ?? 1));
        $limit    = (int)($_GET['limit'] ?? 10);
        $offset   = ($page - 1) * $limit;
        $items    = $product->getAll($search, $category, $sort, $order, $limit, $offset);
        $total    = $product->count($search, $category);
        echo json_encode(['success' => true, 'data' => $items, 'total' => $total, 'pages' => ceil($total / $limit)]);
        break;

    case 'get':
        $id = (int)($_GET['id'] ?? 0);
        $item = $product->getById($id);
        if (!$item) { echo json_encode(['success' => false, 'message' => 'Product not found.']); exit; }
        $item['images'] = $product->getImages($id);
        echo json_encode(['success' => true, 'data' => $item]);
        break;

    case 'create':
        $data = [
            'name'        => trim($_POST['name'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'price'       => (float)($_POST['price'] ?? 0),
            'stock'       => (int)($_POST['stock'] ?? 0),
            'category_id' => (int)($_POST['category_id'] ?? 0),
            'status'      => $_POST['status'] ?? 'active',
        ];
        if (!$data['name']) { echo json_encode(['success' => false, 'message' => 'Product name is required.']); exit; }
        $id = $product->create($data);
        // Handle images
        if (isset($_FILES['images'])) {
            $files = $_FILES['images'];
            $count = count($files['name']);
            for ($i = 0; $i < $count; $i++) {
                if ($files['error'][$i] === 0) {
                    $file = [
                        'name'     => $files['name'][$i],
                        'type'     => $files['type'][$i],
                        'tmp_name' => $files['tmp_name'][$i],
                        'error'    => $files['error'][$i],
                        'size'     => $files['size'][$i],
                    ];
                    $result = $product->uploadImage($file);
                    if ($result['success']) {
                        $product->addImage($id, $result['filename'], $i);
                    }
                }
            }
        }
        echo json_encode(['success' => true, 'message' => 'Product created successfully.', 'id' => $id]);
        break;

    case 'update':
        $id   = (int)($_POST['id'] ?? 0);
        $data = [
            'name'        => trim($_POST['name'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'price'       => (float)($_POST['price'] ?? 0),
            'stock'       => (int)($_POST['stock'] ?? 0),
            'category_id' => (int)($_POST['category_id'] ?? 0),
            'status'      => $_POST['status'] ?? 'active',
        ];
        if (!$data['name']) { echo json_encode(['success' => false, 'message' => 'Product name is required.']); exit; }
        $product->update($id, $data);
        // Handle new images
        if (isset($_FILES['images'])) {
            $files = $_FILES['images'];
            $count = count($files['name']);
            $existing = count($product->getImages($id));
            for ($i = 0; $i < $count; $i++) {
                if ($files['error'][$i] === 0) {
                    $file = [
                        'name'     => $files['name'][$i],
                        'type'     => $files['type'][$i],
                        'tmp_name' => $files['tmp_name'][$i],
                        'error'    => $files['error'][$i],
                        'size'     => $files['size'][$i],
                    ];
                    $result = $product->uploadImage($file);
                    if ($result['success']) {
                        $product->addImage($id, $result['filename'], $existing + $i);
                    }
                }
            }
        }
        echo json_encode(['success' => true, 'message' => 'Product updated successfully.']);
        break;

    case 'delete':
        if (!$auth->isAdmin()) { echo json_encode(['success' => false, 'message' => 'Admin only.']); exit; }
        $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
        $product->delete($id);
        echo json_encode(['success' => true, 'message' => 'Product deleted.']);
        break;

    case 'delete_image':
        $imageId = (int)($_POST['image_id'] ?? 0);
        $product->deleteImage($imageId);
        echo json_encode(['success' => true, 'message' => 'Image deleted.']);
        break;

    case 'reorder_images':
        $productId = (int)($_POST['product_id'] ?? 0);
        $orderJson = $_POST['order'] ?? '[]';
        $order = json_decode($orderJson, true);
        if (!is_array($order)) { echo json_encode(['success' => false, 'message' => 'Invalid order data.']); exit; }
        $product->reorderImages($productId, $order);
        echo json_encode(['success' => true, 'message' => 'Image order updated.']);
        break;

    case 'stats':
        echo json_encode(['success' => true, 'data' => $product->getStats()]);
        break;

    case 'categories':
        echo json_encode(['success' => true, 'data' => $product->getCategories()]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
}
