<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'shop_admin');
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('UPLOAD_URL', 'uploads/');
define('APP_NAME', 'ShopAdmin');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
