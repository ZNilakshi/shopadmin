<?php
class Auth {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function login(string $email, string $password, bool $remember = false): array {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']      = $user['id'];
            $_SESSION['user_name']    = $user['name'];
            $_SESSION['user_email']   = $user['email'];
            $_SESSION['user_role']    = $user['role'];
            $_SESSION['user_picture'] = $user['profile_picture'];

            if ($remember) {
                $token = bin2hex(random_bytes(32));
                $stmt2 = $this->db->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
                $stmt2->execute([$token, $user['id']]);
                setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/', '', false, true);
            }
            return ['success' => true, 'role' => $user['role']];
        }
        return ['success' => false, 'message' => 'Invalid email or password.'];
    }

    public function register(string $name, string $email, string $password): array {
        $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Email is already registered.'];
        }

        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
        $stmt->execute([$name, $email, $hashed]);
        return ['success' => true, 'message' => 'Account created! You can now log in.'];
    }

    public function logout(): void {
        session_destroy();
        setcookie('remember_token', '', time() - 3600, '/');
    }

    public function isLoggedIn(): bool {
        if (isset($_SESSION['user_id'])) return true;

        if (isset($_COOKIE['remember_token'])) {
            $token = $_COOKIE['remember_token'];
            $stmt = $this->db->prepare("SELECT * FROM users WHERE remember_token = ?");
            $stmt->execute([$token]);
            $user = $stmt->fetch();
            if ($user) {
                $_SESSION['user_id']      = $user['id'];
                $_SESSION['user_name']    = $user['name'];
                $_SESSION['user_email']   = $user['email'];
                $_SESSION['user_role']    = $user['role'];
                $_SESSION['user_picture'] = $user['profile_picture'];
                return true;
            }
        }
        return false;
    }

    public function requireLogin(): void {
        if (!$this->isLoggedIn()) {
            header('Location: index.php');
            exit;
        }
    }

    public function isAdmin(): bool {
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
    }
}
