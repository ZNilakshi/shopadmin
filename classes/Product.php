<?php
class Product {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll(string $search = '', string $category = '', string $sort = 'created_at', string $order = 'DESC', int $limit = 10, int $offset = 0, bool $adminAll = false): array {
        $where = [];
        $params = [];

        // Role system: non-admins only see their own products
        if (!$adminAll && isset($_SESSION['user_role']) && $_SESSION['user_role'] !== 'admin') {
            $where[] = "p.created_by = ?";
            $params[] = $_SESSION['user_id'];
        }

        if ($search) {
            $where[] = "(p.name LIKE ? OR p.description LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        if ($category) {
            $where[] = "p.category_id = ?";
            $params[] = $category;
        }

        $whereStr = $where ? "WHERE " . implode(" AND ", $where) : "";
        $allowed = ['id', 'name', 'price', 'stock', 'status', 'created_at'];
        $sort = in_array($sort, $allowed) ? $sort : 'created_at';
        $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

        $sql = "SELECT p.*, c.name as category_name, u.name as creator_name,
                (SELECT image_path FROM product_images WHERE product_id = p.id ORDER BY sort_order ASC LIMIT 1) as thumbnail
                FROM products p LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN users u ON p.created_by = u.id
                $whereStr ORDER BY p.$sort $order LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getAllForExport(string $search = '', string $category = ''): array {
        $where = [];
        $params = [];
        if (isset($_SESSION['user_role']) && $_SESSION['user_role'] !== 'admin') {
            $where[] = "p.created_by = ?";
            $params[] = $_SESSION['user_id'];
        }
        if ($search) { $where[] = "(p.name LIKE ? OR p.description LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
        if ($category) { $where[] = "p.category_id = ?"; $params[] = $category; }
        $whereStr = $where ? "WHERE " . implode(" AND ", $where) : "";
        $stmt = $this->db->prepare(
            "SELECT p.id, p.name, c.name as category, p.price, p.stock, p.status, p.created_at
             FROM products p LEFT JOIN categories c ON p.category_id = c.id $whereStr ORDER BY p.created_at DESC"
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function reorderImages(int $productId, array $order): bool {
        foreach ($order as $item) {
            $stmt = $this->db->prepare("UPDATE product_images SET sort_order = ? WHERE id = ? AND product_id = ?");
            $stmt->execute([(int)$item['order'], (int)$item['id'], $productId]);
        }
        return true;
    }

    public function count(string $search = '', string $category = ''): int {
        $where = [];
        $params = [];
        // Role system
        if (isset($_SESSION['user_role']) && $_SESSION['user_role'] !== 'admin') {
            $where[] = "p.created_by = ?";
            $params[] = $_SESSION['user_id'];
        }
        if ($search) {
            $where[] = "(p.name LIKE ? OR p.description LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        if ($category) {
            $where[] = "p.category_id = ?";
            $params[] = $category;
        }
        $whereStr = $where ? "WHERE " . implode(" AND ", $where) : "";
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM products p $whereStr");
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public function getById(int $id): array|false {
        $stmt = $this->db->prepare(
            "SELECT p.*, c.name as category_name FROM products p
             LEFT JOIN categories c ON p.category_id = c.id WHERE p.id = ?"
        );
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function getImages(int $productId): array {
        $stmt = $this->db->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order ASC");
        $stmt->execute([$productId]);
        return $stmt->fetchAll();
    }

    public function create(array $data): int {
        $stmt = $this->db->prepare(
            "INSERT INTO products (name, description, price, stock, category_id, status, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $data['name'], $data['description'], $data['price'],
            $data['stock'], $data['category_id'] ?: null,
            $data['status'], $_SESSION['user_id']
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool {
        $stmt = $this->db->prepare(
            "UPDATE products SET name=?, description=?, price=?, stock=?,
             category_id=?, status=?, updated_at=NOW() WHERE id=?"
        );
        return $stmt->execute([
            $data['name'], $data['description'], $data['price'],
            $data['stock'], $data['category_id'] ?: null,
            $data['status'], $id
        ]);
    }

    public function delete(int $id): bool {
        $images = $this->getImages($id);
        foreach ($images as $img) {
            $path = UPLOAD_DIR . $img['image_path'];
            if (file_exists($path)) unlink($path);
        }
        $stmt = $this->db->prepare("DELETE FROM products WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function addImage(int $productId, string $imagePath, int $order = 0): int {
        $stmt = $this->db->prepare(
            "INSERT INTO product_images (product_id, image_path, sort_order) VALUES (?, ?, ?)"
        );
        $stmt->execute([$productId, $imagePath, $order]);
        return (int)$this->db->lastInsertId();
    }

    public function deleteImage(int $imageId): bool {
        $stmt = $this->db->prepare("SELECT image_path FROM product_images WHERE id = ?");
        $stmt->execute([$imageId]);
        $img = $stmt->fetch();
        if ($img) {
            $path = UPLOAD_DIR . $img['image_path'];
            if (file_exists($path)) unlink($path);
        }
        $stmt = $this->db->prepare("DELETE FROM product_images WHERE id = ?");
        return $stmt->execute([$imageId]);
    }

    public function uploadImage(array $file): array {
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file['type'], $allowed)) {
            return ['success' => false, 'message' => 'Invalid file type. JPG, PNG, GIF, WEBP only.'];
        }
        if ($file['size'] > 5 * 1024 * 1024) {
            return ['success' => false, 'message' => 'File too large. Max 5MB.'];
        }
        if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = 'prod_' . uniqid() . '.' . $ext;
        if (move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $filename)) {
            return ['success' => true, 'filename' => $filename];
        }
        return ['success' => false, 'message' => 'Upload failed.'];
    }

    public function getStats(): array {
        $db = $this->db;
        return [
            'total'       => (int)$db->query("SELECT COUNT(*) FROM products")->fetchColumn(),
            'active'      => (int)$db->query("SELECT COUNT(*) FROM products WHERE status='active'")->fetchColumn(),
            'out_of_stock'=> (int)$db->query("SELECT COUNT(*) FROM products WHERE status='out_of_stock' OR stock=0")->fetchColumn(),
            'total_value' => (float)($db->query("SELECT COALESCE(SUM(price * stock),0) FROM products")->fetchColumn()),
            'categories'  => (int)$db->query("SELECT COUNT(*) FROM categories")->fetchColumn(),
            'low_stock'   => (int)$db->query("SELECT COUNT(*) FROM products WHERE stock > 0 AND stock <= 10")->fetchColumn(),
            'total_users' => (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn(),
            'inactive'    => (int)$db->query("SELECT COUNT(*) FROM products WHERE status='inactive'")->fetchColumn(),
        ];
    }

    public function getUserStats(int $userId): array {
        $db = $this->db;
        $stmt = $db->prepare("SELECT COUNT(*) FROM products WHERE created_by = ?");
        $stmt->execute([$userId]); $total = (int)$stmt->fetchColumn();

        $stmt = $db->prepare("SELECT COUNT(*) FROM products WHERE created_by = ? AND status='active'");
        $stmt->execute([$userId]); $active = (int)$stmt->fetchColumn();

        $stmt = $db->prepare("SELECT COUNT(*) FROM products WHERE created_by = ? AND (status='out_of_stock' OR stock=0)");
        $stmt->execute([$userId]); $oos = (int)$stmt->fetchColumn();

        $stmt = $db->prepare("SELECT COALESCE(SUM(price * stock),0) FROM products WHERE created_by = ?");
        $stmt->execute([$userId]); $value = (float)$stmt->fetchColumn();

        $stmt = $db->prepare("SELECT COUNT(*) FROM products WHERE created_by = ? AND stock > 0 AND stock <= 10");
        $stmt->execute([$userId]); $low = (int)$stmt->fetchColumn();

        return ['total' => $total, 'active' => $active, 'out_of_stock' => $oos, 'total_value' => $value, 'low_stock' => $low];
    }

    public function getByCategoryForUser(int $userId): array {
        $stmt = $this->db->prepare(
            "SELECT c.name, COUNT(p.id) as count FROM categories c
             LEFT JOIN products p ON c.id = p.category_id AND p.created_by = ?
             GROUP BY c.id, c.name HAVING count > 0 ORDER BY count DESC"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function getAllUsers(): array {
        return $this->db->query(
            "SELECT u.id, u.name, u.email, u.role, u.created_at,
             COUNT(p.id) as product_count
             FROM users u LEFT JOIN products p ON u.id = p.created_by
             GROUP BY u.id ORDER BY u.created_at DESC"
        )->fetchAll();
    }

    public function getByCategory(): array {
        return $this->db->query(
            "SELECT c.name, COUNT(p.id) as count FROM categories c
             LEFT JOIN products p ON c.id = p.category_id GROUP BY c.id, c.name ORDER BY count DESC"
        )->fetchAll();
    }

    public function getCategories(): array {
        return $this->db->query("SELECT * FROM categories ORDER BY name")->fetchAll();
    }

    public function getRecentProducts(int $limit = 5): array {
        $stmt = $this->db->prepare(
            "SELECT p.*, c.name as category_name,
             (SELECT image_path FROM product_images WHERE product_id = p.id LIMIT 1) as thumbnail
             FROM products p LEFT JOIN categories c ON p.category_id = c.id
             ORDER BY p.created_at DESC LIMIT ?"
        );
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
}
