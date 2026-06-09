<?php
session_start();
require '../includes/db.php';
require '../includes/auth.php';

// Secure this page - ONLY admins allowed
requireAdmin();

$page_title = 'Manage Users';
$success_msg = '';
$error_msg = '';

// Handle Delete User
if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    
    // Safety check: Prevent the admin from deleting their own account
    if ($delete_id === $_SESSION['user_id']) {
        $error_msg = "Action denied: You cannot delete your own active admin account.";
    } else {
        $del_stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $del_stmt->bind_param("i", $delete_id);
        
        if ($del_stmt->execute()) {
            $success_msg = "User account has been deleted successfully.";
        } else {
            $error_msg = "Failed to delete user. They may have active records tied to their account.";
        }
    }
}

// Fetch all registered users
$users = $conn->query("
    SELECT id, full_name, email, phone, role, created_at 
    FROM users 
    ORDER BY role ASC, created_at DESC
")->fetch_all(MYSQLI_ASSOC);

require '../includes/header.php';
?>

<section class="admin-section">
    <div class="container">
        
        <div class="admin-header">
            <h1 class="section-title">Manage Users</h1>
        </div>

        <?php if ($success_msg): ?>
            <div class="alert alert-success"><p><?= htmlspecialchars($success_msg) ?></p></div>
        <?php endif; ?>
        
        <?php if ($error_msg): ?>
            <div class="alert alert-error"><p><?= htmlspecialchars($error_msg) ?></p></div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Role</th>
                        <th>Joined Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td><strong>#<?= $u['id'] ?></strong></td>
                            <td><?= htmlspecialchars($u['full_name']) ?></td>
                            <td><a href="mailto:<?= htmlspecialchars($u['email']) ?>"><?= htmlspecialchars($u['email']) ?></a></td>
                            <td><?= htmlspecialchars($u['phone'] ?? 'N/A') ?></td>
                            <td>
                                <?php if ($u['role'] === 'admin'): ?>
                                    <span style="background: #dbeafe; color: #1e40af; padding: 4px 10px; border-radius: 20px; font-size: 0.8rem; font-weight: 600;">Admin</span>
                                <?php else: ?>
                                    <span style="background: #f1f5f9; color: #475569; padding: 4px 10px; border-radius: 20px; font-size: 0.8rem; font-weight: 600;">Customer</span>
                                <?php endif; ?>
                            </td>
                            <td><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
                            <td>
                                <?php if ($u['id'] !== $_SESSION['user_id']): ?>
                                    <a href="users.php?delete=<?= $u['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to permanently delete this user? This will also remove their cart data.');">Delete</a>
                                <?php else: ?>
                                    <span style="color: var(--gray); font-size: 0.85rem;">Current User</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    </div>
</section>

<?php require '../includes/footer.php'; ?>