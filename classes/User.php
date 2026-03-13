<?php
class User {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getById(int $id): array|false {
        $stmt = $this->db->prepare(
            "SELECT id, name, email, role, profile_picture, created_at FROM users WHERE id = ?"
        );
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function updateProfile(int $id, string $name, string $email): array {
        $check = $this->db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $check->execute([$email, $id]);
        if ($check->fetch()) {
            return ['success' => false, 'message' => 'Email already in use.'];
        }
        $stmt = $this->db->prepare("UPDATE users SET name=?, email=?, updated_at=NOW() WHERE id=?");
        $stmt->execute([$name, $email, $id]);
        $_SESSION['user_name']  = $name;
        $_SESSION['user_email'] = $email;
        return ['success' => true, 'message' => 'Profile updated successfully.'];
    }

    public function updatePassword(int $id, string $current, string $newPass): array {
        $stmt = $this->db->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        if (!password_verify($current, $user['password'])) {
            return ['success' => false, 'message' => 'Current password is incorrect.'];
        }
        $hashed = password_hash($newPass, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare("UPDATE users SET password=? WHERE id=?");
        $stmt->execute([$hashed, $id]);
        return ['success' => true, 'message' => 'Password updated successfully.'];
    }

    public function updateProfilePicture(int $id, array $file): array {
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file['type'], $allowed)) {
            return ['success' => false, 'message' => 'Invalid file type.'];
        }
        if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = 'avatar_' . $id . '_' . time() . '.' . $ext;
        if (move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $filename)) {
            $stmt = $this->db->prepare("UPDATE users SET profile_picture=? WHERE id=?");
            $stmt->execute([$filename, $id]);
            $_SESSION['user_picture'] = $filename;
            return ['success' => true, 'filename' => $filename];
        }
        return ['success' => false, 'message' => 'Upload failed.'];
    }

    public function getAll(): array {
        return $this->db->query(
            "SELECT id, name, email, role, created_at FROM users ORDER BY id DESC"
        )->fetchAll();
    }
}
