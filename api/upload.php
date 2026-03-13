<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Auth.php';

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->check()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$type = ($_GET['type'] ?? 'product') === 'profile' ? 'profile' : 'product';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['image'])) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded']);
    exit;
}

$file = $_FILES['image'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    $errors = [
        UPLOAD_ERR_INI_SIZE   => 'File exceeds server limit',
        UPLOAD_ERR_FORM_SIZE  => 'File exceeds form limit',
        UPLOAD_ERR_PARTIAL    => 'File only partially uploaded',
        UPLOAD_ERR_NO_FILE    => 'No file uploaded',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing temp directory',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file',
    ];
    echo json_encode(['success' => false, 'message' => $errors[$file['error']] ?? 'Upload error']);
    exit;
}

if ($file['size'] > MAX_FILE_SIZE) {
    echo json_encode(['success' => false, 'message' => 'File too large. Max size: 5MB']);
    exit;
}

// Validate MIME type using finfo
$finfo    = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->file($file['tmp_name']);
if (!in_array($mimeType, ALLOWED_IMAGE_TYPES)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type. Allowed: JPEG, PNG, GIF, WebP']);
    exit;
}

$ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$filename = uniqid('img_', true) . '.' . $ext;
$subDir   = $type === 'profile' ? 'profiles' : 'products';
$dir      = UPLOAD_DIR . $subDir . DIRECTORY_SEPARATOR;

if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
}

if (!move_uploaded_file($file['tmp_name'], $dir . $filename)) {
    echo json_encode(['success' => false, 'message' => 'Failed to save uploaded file']);
    exit;
}

echo json_encode([
    'success'  => true,
    'filename' => $filename,
    'url'      => BASE_URL . '/uploads/' . $subDir . '/' . $filename,
    'message'  => 'File uploaded successfully',
]);
