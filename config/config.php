<?php
// ============================================
// Application Configuration
// ============================================

// Database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'shop_admin_db');

// App
define('APP_NAME', 'ShopAdmin');
define('BASE_URL', 'http://localhost/shop-admin'); // Change this to your URL

// File Upload
define('UPLOAD_DIR', realpath(__DIR__ . '/../uploads') . DIRECTORY_SEPARATOR);
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);

// Session
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => false,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
