<?php
require_once '../config.php';
require_once '../classes/Database.php';
require_once '../classes/Auth.php';
require_once '../classes/User.php';

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

$user   = new User();
$action = $_POST['action'] ?? '';

switch ($action) {
    case 'update_profile':
        $name  = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        if (!$name || !$email) {
            echo json_encode(['success' => false, 'message' => 'Name and email are required.']);
            exit;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Invalid email address.']);
            exit;
        }
        echo json_encode($user->updateProfile((int)$_SESSION['user_id'], $name, $email));
        break;

    case 'update_password':
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        if (!$current || !$new) {
            echo json_encode(['success' => false, 'message' => 'All fields are required.']);
            exit;
        }
        if (strlen($new) < 6) {
            echo json_encode(['success' => false, 'message' => 'New password must be at least 6 characters.']);
            exit;
        }
        if ($new !== $confirm) {
            echo json_encode(['success' => false, 'message' => 'New passwords do not match.']);
            exit;
        }
        echo json_encode($user->updatePassword((int)$_SESSION['user_id'], $current, $new));
        break;

    case 'update_picture':
        if (!isset($_FILES['picture'])) {
            echo json_encode(['success' => false, 'message' => 'No file uploaded.']);
            exit;
        }
        $result = $user->updateProfilePicture((int)$_SESSION['user_id'], $_FILES['picture']);
        if ($result['success']) {
            $result['url'] = UPLOAD_URL . $result['filename'];
        }
        echo json_encode($result);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
}
