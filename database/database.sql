
CREATE DATABASE IF NOT EXISTS shop_admin CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE shop_admin;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','user') DEFAULT 'user',
    profile_picture VARCHAR(255) DEFAULT NULL,
    remember_token VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    stock INT NOT NULL DEFAULT 0,
    category_id INT DEFAULT NULL,
    status ENUM('active','inactive','out_of_stock') DEFAULT 'active',
    created_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE product_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Seed: password = "password"
INSERT INTO users (name, email, password, role) VALUES
('Admin User', 'admin@shop.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('Normal User', 'user@shop.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user');

INSERT INTO categories (name) VALUES
('Electronics'), ('Clothing'), ('Food & Beverages'), ('Books'),
('Furniture'), ('Sports'), ('Beauty & Health'), ('Toys & Games');

INSERT INTO products (name, description, price, stock, category_id, status, created_by) VALUES
('iPhone 15 Pro', 'Latest Apple smartphone with titanium design and A17 Pro chip.', 1299.99, 45, 1, 'active', 1),
('Samsung 65" QLED 4K TV', 'Crystal-clear 4K Smart TV with Quantum HDR.', 899.00, 12, 1, 'active', 1),
('Sony WH-1000XM5 Headphones', 'Industry-leading noise canceling wireless headphones, 30hr battery.', 349.99, 28, 1, 'active', 1),
('Nike Air Max 270', 'Premium running shoes with Max Air cushioning.', 159.99, 78, 2, 'active', 1),
('Levi''s 501 Original Jeans', 'The original straight fit jean. 100% cotton denim.', 79.99, 120, 2, 'active', 1),
('Organic Ethiopian Coffee 1kg', 'Single-origin specialty coffee with notes of blueberry and jasmine.', 24.99, 200, 3, 'active', 1),
('Python Crash Course', 'Hands-on introduction to programming with Python.', 39.99, 0, 4, 'out_of_stock', 1),
('Ergonomic Office Chair', 'Lumbar support, adjustable height. 5-year warranty.', 399.00, 8, 5, 'active', 1),
('Yoga Mat Premium', 'Non-slip 6mm eco-friendly TPE mat with alignment lines.', 49.99, 55, 6, 'active', 1),
('Vitamin C Serum 30ml', 'Brightening serum with 20% Vitamin C. Dermatologist tested.', 34.99, 92, 7, 'active', 1),
('LEGO Technic Set', '1172-piece building set with working mechanics.', 89.99, 3, 8, 'active', 1),
('MacBook Air M2', 'Supercharged by M2 chip. 13.6-inch Liquid Retina display.', 1099.00, 0, 1, 'out_of_stock', 1),
('Wireless Gaming Mouse', 'Ultra-lightweight 61g gaming mouse, 70hr battery, 25600 DPI.', 79.99, 34, 1, 'active', 1),
('Standing Desk 140cm', 'Electric height-adjustable desk. Memory presets, supports 80kg.', 549.00, 6, 5, 'inactive', 1);
