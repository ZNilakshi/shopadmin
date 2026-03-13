<?php
require_once 'config.php';
require_once 'classes/Database.php';
require_once 'classes/Auth.php';
require_once 'classes/User.php';

$auth = new Auth();
$auth->requireLogin();

$user     = new User();
$userData = $user->getById((int)$_SESSION['user_id']);

$pageTitle  = 'My Profile';
$activePage = 'profile';
include 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-xl-8">

        <!-- Profile Header Card -->
        <div class="app-card mb-3 text-center">
            <div class="profile-avatar-wrap mx-auto mb-3">
                <?php if ($userData['profile_picture']): ?>
                    <img src="uploads/<?= htmlspecialchars($userData['profile_picture']) ?>" alt="Profile" id="profileImg">
                <?php else: ?>
                    <div class="profile-avatar-large mx-auto"><?= strtoupper(substr($userData['name'], 0, 1)) ?></div>
                <?php endif; ?>
                <label class="avatar-upload-btn" title="Change photo">
                    <i class="bi bi-camera-fill"></i>
                    <input type="file" id="picInput" accept="image/*" style="display:none">
                </label>
            </div>
            <h5 class="mb-1"><?= htmlspecialchars($userData['name']) ?></h5>
            <p class="text-muted mb-2"><?= htmlspecialchars($userData['email']) ?></p>
            <span class="badge <?= $userData['role'] === 'admin' ? 'bg-success' : 'bg-secondary' ?>">
                <?= ucfirst($userData['role']) ?>
            </span>
            <div class="mt-3 text-muted small">
                <i class="bi bi-calendar3 me-1"></i>Joined <?= date('F Y', strtotime($userData['created_at'])) ?>
            </div>
        </div>

        <!-- Update Profile -->
        <div class="app-card mb-3">
            <h6 class="mb-4"><i class="bi bi-person-fill text-accent me-2"></i>Profile Details</h6>
            <form id="profileForm" novalidate>
                <input type="hidden" name="action" value="update_profile">
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($userData['name']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($userData['email']) ?>" required>
                    </div>
                </div>
                <div class="d-flex justify-content-end">
                    <button type="button" id="profileSaveBtn" class="btn btn-accent">
                        <i class="bi bi-check-circle me-2"></i>Save Profile
                    </button>
                </div>
            </form>
        </div>

        <!-- Change Password -->
        <div class="app-card">
            <h6 class="mb-4"><i class="bi bi-shield-lock-fill text-accent me-2"></i>Change Password</h6>
            <form id="passwordForm" novalidate>
                <input type="hidden" name="action" value="update_password">
                <div class="mb-3">
                    <label class="form-label">Current Password</label>
                    <input type="password" name="current_password" class="form-control" placeholder="Enter current password" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">New Password</label>
                    <input type="password" name="new_password" class="form-control" placeholder="Min. 6 characters" required>
                </div>
                <div class="mb-4">
                    <label class="form-label">Confirm New Password</label>
                    <input type="password" name="confirm_password" class="form-control" placeholder="Repeat new password" required>
                </div>
                <div class="d-flex justify-content-end">
                    <button type="button" id="pwdSaveBtn" class="btn btn-accent">
                        <i class="bi bi-lock me-2"></i>Update Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
document.getElementById('profileSaveBtn').addEventListener('click', async () => {
    const form = document.getElementById('profileForm');
    const btn = document.getElementById('profileSaveBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';
    const data = await apiPost('api/profile.php', new FormData(form));
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-check-circle me-2"></i>Save Profile';
    showToast(data.message, data.success ? 'success' : 'error');
});

document.getElementById('pwdSaveBtn').addEventListener('click', async () => {
    const form = document.getElementById('passwordForm');
    const btn = document.getElementById('pwdSaveBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Updating...';
    const data = await apiPost('api/profile.php', new FormData(form));
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-lock me-2"></i>Update Password';
    showToast(data.message, data.success ? 'success' : 'error');
    if (data.success) form.reset();
});

// Picture upload
document.getElementById('picInput').addEventListener('change', async function() {
    if (!this.files[0]) return;
    const fd = new FormData();
    fd.append('action', 'update_picture');
    fd.append('picture', this.files[0]);
    const data = await apiPost('api/profile.php', fd);
    if (data.success) {
        showToast('Profile picture updated!', 'success');
        const existing = document.getElementById('profileImg');
        if (existing) {
            existing.src = data.url + '?' + Date.now();
        } else {
            window.location.reload();
        }
    } else {
        showToast(data.message, 'error');
    }
});
</script>
