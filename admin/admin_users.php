<?php
require '../config/config.php';

// Check if admin is logged in
if (empty($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}

$adminRole = $_SESSION['admin_role'];

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $userId = intval($_POST['user_id'] ?? 0);
    
    if ($userId > 0) {
        switch ($action) {
            case 'toggle_status':
                $stmt = $pdo->prepare("UPDATE users SET is_active = NOT is_active WHERE id = ?");
                $stmt->execute([$userId]);
                header('Location: admin_users.php?success=user_updated');
                exit;
                break;
                
            case 'delete_user':
                if ($adminRole === 'super_admin') {
                    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                    $stmt->execute([$userId]);
                    header('Location: admin_users.php?success=user_deleted');
                    exit;
                }
                break;
                
            case 'make_admin':
                if ($adminRole === 'super_admin') {
                    $stmt = $pdo->prepare("UPDATE users SET is_admin = TRUE WHERE id = ?");
                    $stmt->execute([$userId]);
                    header('Location: admin_users.php?success=user_promoted');
                    exit;
                }
                break;
        }
    }
}

// Get search parameters
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? 'all';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

// Build query
$where = "WHERE 1=1";
$params = [];

if (!empty($search)) {
    $where .= " AND (username LIKE ? OR email LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if ($status !== 'all') {
    $where .= " AND is_active = ?";
    $params[] = ($status === 'active') ? 1 : 0;
}

// Get total count
$countQuery = "SELECT COUNT(*) FROM users $where";
$countStmt = $pdo->prepare($countQuery);
$countStmt->execute($params);
$totalUsers = $countStmt->fetchColumn();
$totalPages = ceil($totalUsers / $limit);

// Get users
$query = "SELECT id, username, email, is_active, is_admin, created_at FROM users $where ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll();

// Get user statistics
$stats = $pdo->query("
    SELECT 
        COUNT(*) as total,
        SUM(is_active) as active,
        SUM(is_admin) as admins,
        COUNT(*) - SUM(is_active) as inactive
    FROM users
")->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - BENTA Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin-users.css">

</head>
<body>
    <header class="admin-header">
        <div>
            <strong>BENTA</strong> - User Management
            <span class="admin-badge"><?= strtoupper($adminRole) ?></span>
        </div>
        <nav class="admin-nav">
            <a href="admin_dashboard.php">Dashboard</a>
            <a href="admin_users.php">Manage Users</a>
            <a href="admin_reports.php">System Reports</a>
            <a href="admin_settings.php">Settings</a>
            <a href="admin_logout.php">Logout</a>
        </nav>
    </header>
    
    <main class="container">
        <div class="page-header">
            <h1>User Management</h1>
            <p>Manage user accounts, permissions, and system access</p>
        </div>
        
        <!-- User Statistics -->
        <div class="user-stats">
            <div class="user-stat-card">
                <div class="number"><?= $stats['total'] ?></div>
                <div class="label">Total Users</div>
            </div>
            <div class="user-stat-card">
                <div class="number"><?= $stats['active'] ?></div>
                <div class="label">Active Users</div>
            </div>
            <div class="user-stat-card">
                <div class="number"><?= $stats['inactive'] ?></div>
                <div class="label">Inactive Users</div>
            </div>
            <div class="user-stat-card">
                <div class="number"><?= $stats['admins'] ?></div>
                <div class="label">Admin Users</div>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="filters">
            <form method="GET" class="filter-row">
                <div class="filter-group">
                    <label for="search">Search Users</label>
                    <input type="text" name="search" id="search" value="<?= e($search) ?>" placeholder="Username or email">
                </div>
                <div class="filter-group">
                    <label for="status">Status</label>
                    <select name="status" id="status">
                        <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>All Users</option>
                        <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active Only</option>
                        <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive Only</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="admin_users.php" class="btn btn-secondary">Clear</a>
            </form>
        </div>
        
        <!-- Users Table -->
        <div class="card">
            <div class="card-header">
                <h2>Users (<?= $totalUsers ?> total)</h2>
                <span class="chart-info">Page <?= $page ?> of <?= $totalPages ?></span>
            </div>
            
            <?php if (empty($users)): ?>
                <div class="empty-state">
                    <div class="empty-icon">👥</div>
                    <h3>No users found</h3>
                    <p><?= !empty($search) ? 'Try adjusting your search criteria' : 'No users have registered yet' ?></p>
                </div>
            <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Role</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= $user['id'] ?></td>
                            <td><?= e($user['username']) ?></td>
                            <td><?= e($user['email']) ?></td>
                            <td>
                                <span class="<?= $user['is_active'] ? 'status-active' : 'status-inactive' ?>">
                                    <?= $user['is_active'] ? 'Active' : 'Inactive' ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($user['is_admin']): ?>
                                    <span class="admin-indicator">ADMIN</span>
                                <?php else: ?>
                                    User
                                <?php endif; ?>
                            </td>
                            <td><?= date('M d, Y', strtotime($user['created_at'])) ?></td>
                            <td>
                                <div class="user-actions">
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <button type="submit" class="btn btn-xs <?= $user['is_active'] ? 'btn-warning' : 'btn-success' ?>">
                                            <?= $user['is_active'] ? 'Deactivate' : 'Activate' ?>
                                        </button>
                                    </form>
                                    
                                    <?php if ($adminRole === 'super_admin' && !$user['is_admin']): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                        <input type="hidden" name="action" value="make_admin">
                                        <button type="submit" class="btn btn-xs btn-info" onclick="return confirm('Make this user an admin?')">
                                            Make Admin
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                    
                                    <?php if ($adminRole === 'super_admin'): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                        <input type="hidden" name="action" value="delete_user">
                                        <button type="submit" class="btn btn-xs btn-danger" onclick="return confirm('Delete this user permanently? This cannot be undone!')">
                                            Delete
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?>&search=<?= e($search) ?>&status=<?= e($status) ?>">« Previous</a>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="current"><?= $i ?></span>
                        <?php else: ?>
                            <a href="?page=<?= $i ?>&search=<?= e($search) ?>&status=<?= e($status) ?>"><?= $i ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?= $page + 1 ?>&search=<?= e($search) ?>&status=<?= e($status) ?>">Next »</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </main>
    
    <script src="../assets/js/animations.js"></script>
</body>
</html>
