<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include_once("db.php");

$toast_message = '';
$toast_type = 'success';

// Detect base directory
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
if (substr($scriptDir, -6) === '/logic') { $scriptDir = substr($scriptDir, 0, -6); }
$base = ($scriptDir === '/' || $scriptDir === '.') ? '' : $scriptDir;

// Handle Actions: Status Toggle, Role Toggle, Delete
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $user_id = intval($_POST['user_id'] ?? 0);

    if ($user_id > 0) {
        if ($action === 'update_status') {
            $new_status = $_POST['status'] ?? 'active';
            if (in_array($new_status, ['active', 'inactive', 'banned'])) {
                $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
                $stmt->bind_param("si", $new_status, $user_id);
                if ($stmt->execute()) {
                    $_SESSION['toast'] = ['msg' => 'User status updated successfully!', 'type' => 'success'];
                }
                $stmt->close();
            }
        } elseif ($action === 'update_role') {
            $new_role = $_POST['role'] ?? 'customer';
            if (in_array($new_role, ['admin', 'customer'])) {
                $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
                $stmt->bind_param("si", $new_role, $user_id);
                if ($stmt->execute()) {
                    $_SESSION['toast'] = ['msg' => 'User role updated successfully!', 'type' => 'success'];
                }
                $stmt->close();
            }
        } elseif ($action === 'delete_user') {
            // Check if user is not deleting themselves
            $stmt_check = $conn->prepare("SELECT username FROM users WHERE id = ?");
            $stmt_check->bind_param("i", $user_id);
            $stmt_check->execute();
            $u_res = $stmt_check->get_result()->fetch_assoc();
            $stmt_check->close();

            if ($u_res && isset($_SESSION['admin_name']) && $_SESSION['admin_name'] === $u_res['username']) {
                $_SESSION['toast'] = ['msg' => 'You cannot delete your own logged-in admin account!', 'type' => 'error'];
            } else {
                $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
                $stmt->bind_param("i", $user_id);
                if ($stmt->execute()) {
                    $_SESSION['toast'] = ['msg' => 'User deleted successfully!', 'type' => 'success'];
                } else {
                    $_SESSION['toast'] = ['msg' => 'Error deleting user: ' . $conn->error, 'type' => 'error'];
                }
                $stmt->close();
            }
        }
    }
    header("Location: " . $base . "/users");
    exit();
}

// Read toast message from session if set
if (isset($_SESSION['toast'])) {
    $toast_message = $_SESSION['toast']['msg'];
    $toast_type = $_SESSION['toast']['type'];
    unset($_SESSION['toast']);
}

// Filtering & Search
$search = trim($_GET['search'] ?? '');
$role_filter = trim($_GET['role'] ?? '');
$status_filter = trim($_GET['status'] ?? '');

$where_clauses = [];
$params = [];
$types = "";

if (!empty($search)) {
    $where_clauses[] = "(u.username LIKE ? OR u.full_name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
    $search_param = '%' . $search . '%';
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ssss";
}

if (!empty($role_filter)) {
    $where_clauses[] = "u.role = ?";
    $params[] = $role_filter;
    $types .= "s";
}

if (!empty($status_filter)) {
    $where_clauses[] = "u.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

$where_sql = count($where_clauses) > 0 ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Summary Metrics Query
$metrics = [
    'total' => 0,
    'customers' => 0,
    'admins' => 0,
    'active' => 0
];
$m_res = $conn->query("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN role = 'customer' THEN 1 ELSE 0 END) as customers,
    SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admins,
    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active
    FROM users");
if ($m_res && $row = $m_res->fetch_assoc()) {
    $metrics['total'] = (int)$row['total'];
    $metrics['customers'] = (int)$row['customers'];
    $metrics['admins'] = (int)$row['admins'];
    $metrics['active'] = (int)$row['active'];
}

// Main Users Query with Order Stats
$query = "
    SELECT 
        u.*,
        COUNT(o.id) as order_count,
        COALESCE(SUM(o.total), 0) as total_spent
    FROM users u
    LEFT JOIN orders o ON o.user_id = u.id OR (o.email IS NOT NULL AND o.email = u.email)
    $where_sql
    GROUP BY u.id
    ORDER BY u.created_at DESC
";

$stmt = $conn->prepare($query);
if (!empty($types)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$users_result = $stmt->get_result();
$users = [];
while ($row = $users_result->fetch_assoc()) {
    $users[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management — LVB Atelier Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <style>
        :root {
            --bg-body: #f4f7f6;
            --card-bg: #ffffff;
            --primary: #0b506e;
            --primary-light: #e6f0f5;
            --text-dark: #1e293b;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --radius: 10px;
            --shadow: 0 4px 12px rgba(0,0,0,0.04);
        }

        .main-content {
            margin-left: 260px;
            padding: 30px;
            background: var(--bg-body);
            min-height: 100vh;
            transition: all 0.3s ease;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 15px;
            }
        }

        /* Page Header */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .page-header h1 {
            font-size: 24px;
            font-weight: 700;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .page-header p {
            color: var(--text-muted);
            font-size: 14px;
            margin-top: 4px;
        }

        /* Toast Alert */
        .toast {
            position: fixed;
            top: 25px;
            right: 25px;
            padding: 14px 20px;
            border-radius: 8px;
            color: #fff;
            font-weight: 500;
            z-index: 9999;
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideIn 0.3s ease-out;
        }

        .toast.success { background: #059669; }
        .toast.error { background: #dc2626; }

        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        /* Metric Cards */
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }

        .metric-card {
            background: var(--card-bg);
            padding: 20px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .metric-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .metric-icon.blue { background: #e0f2fe; color: #0284c7; }
        .metric-icon.purple { background: #f3e8ff; color: #9333ea; }
        .metric-icon.gold { background: #fef3c7; color: #d97706; }
        .metric-icon.green { background: #dcfce7; color: #16a34a; }

        .metric-info h3 {
            font-size: 22px;
            font-weight: 700;
            color: var(--text-dark);
        }

        .metric-info p {
            font-size: 13px;
            color: var(--text-muted);
            margin-top: 2px;
        }

        /* Controls Card */
        .controls-card {
            background: var(--card-bg);
            padding: 18px 20px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
            margin-bottom: 25px;
        }

        .filter-form {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
        }

        .search-box {
            position: relative;
            flex: 1;
            min-width: 240px;
        }

        .search-box input {
            width: 100%;
            padding: 10px 14px 10px 38px;
            border-radius: 6px;
            border: 1px solid var(--border-color);
            outline: none;
            font-size: 14px;
            transition: all 0.2s;
        }

        .search-box input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(11, 80, 110, 0.1);
        }

        .search-box i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
        }

        .filter-select {
            padding: 10px 14px;
            border-radius: 6px;
            border: 1px solid var(--border-color);
            font-size: 14px;
            color: var(--text-dark);
            outline: none;
            background: #fff;
        }

        .btn-filter, .btn-reset {
            padding: 10px 18px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-filter {
            background: var(--primary);
            color: #fff;
        }
        .btn-filter:hover { background: #083c53; }

        .btn-reset {
            background: #e2e8f0;
            color: var(--text-dark);
        }
        .btn-reset:hover { background: #cbd5e1; }

        /* Table Card */
        .table-card {
            background: var(--card-bg);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
            overflow: hidden;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        .users-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 14px;
        }

        .users-table th {
            background: #f8fafc;
            padding: 14px 18px;
            font-weight: 600;
            color: var(--text-muted);
            border-bottom: 1px solid var(--border-color);
            white-space: nowrap;
        }

        .users-table td {
            padding: 16px 18px;
            border-bottom: 1px solid var(--border-color);
            vertical-align: middle;
        }

        .users-table tr:last-child td {
            border-bottom: none;
        }

        .users-table tr:hover {
            background: #fafafa;
        }

        /* User Profile Badge */
        .user-profile {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .avatar-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary-light);
            color: var(--primary);
            font-weight: 700;
            font-size: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-transform: uppercase;
            border: 1px solid rgba(11, 80, 110, 0.2);
        }

        .user-name {
            font-weight: 600;
            color: var(--text-dark);
            font-size: 14px;
        }

        .user-username {
            font-size: 12px;
            color: var(--text-muted);
        }

        /* Badges */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: capitalize;
        }

        .badge-admin { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
        .badge-customer { background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }

        .badge-active { background: #dcfce7; color: #15803d; }
        .badge-inactive { background: #f1f5f9; color: #64748b; }
        .badge-banned { background: #fee2e2; color: #b91c1c; }

        /* Action Buttons */
        .actions-cell {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-action {
            width: 32px;
            height: 32px;
            border-radius: 6px;
            border: 1px solid var(--border-color);
            background: #fff;
            color: var(--text-muted);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-action:hover {
            border-color: var(--primary);
            color: var(--primary);
            background: var(--primary-light);
        }

        .btn-action.danger:hover {
            border-color: var(--danger);
            color: var(--danger);
            background: #fee2e2;
        }

        .status-select {
            padding: 4px 8px;
            border-radius: 6px;
            border: 1px solid var(--border-color);
            font-size: 12px;
            outline: none;
            background: #fff;
            cursor: pointer;
        }

        /* Modal styling */
        .modal-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.5);
            backdrop-filter: blur(4px);
            z-index: 9990;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .modal-overlay.open { display: flex; }

        .modal-card {
            background: #fff;
            border-radius: 12px;
            max-width: 500px;
            width: 100%;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            overflow: hidden;
            animation: modalFadeIn 0.2s ease-out;
        }

        @keyframes modalFadeIn {
            from { transform: scale(0.95); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }

        .modal-header {
            padding: 18px 24px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #f8fafc;
        }

        .modal-header h3 {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-dark);
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 20px;
            color: var(--text-muted);
            cursor: pointer;
        }

        .modal-body {
            padding: 24px;
        }

        .detail-row {
            display: flex;
            margin-bottom: 12px;
            border-bottom: 1px dashed var(--border-color);
            padding-bottom: 8px;
        }

        .detail-row:last-child { border-bottom: none; }

        .detail-label {
            width: 140px;
            font-weight: 600;
            color: var(--text-muted);
            font-size: 13px;
        }

        .detail-val {
            flex: 1;
            color: var(--text-dark);
            font-size: 13px;
            word-break: break-word;
        }
    </style>
</head>
<body>

<div class="main-content">

    <?php if (!empty($toast_message)): ?>
        <div class="toast <?php echo $toast_type; ?>" id="toastBox">
            <i class="fas fa-check-circle"></i>
            <span><?php echo htmlspecialchars($toast_message); ?></span>
        </div>
    <?php endif; ?>

    <!-- Page Header -->
    <div class="page-header">
        <div>
            <h1><i class="fas fa-users-cog" style="color:var(--primary);"></i> User Management</h1>
            <p>View, manage roles, update account status, and track registered customer activity.</p>
        </div>
    </div>

    <!-- Summary Metrics -->
    <div class="metrics-grid">
        <div class="metric-card">
            <div class="metric-icon blue"><i class="fas fa-users"></i></div>
            <div class="metric-info">
                <h3><?php echo number_format($metrics['total']); ?></h3>
                <p>Total Registered Users</p>
            </div>
        </div>

        <div class="metric-card">
            <div class="metric-icon purple"><i class="fas fa-user-tag"></i></div>
            <div class="metric-info">
                <h3><?php echo number_format($metrics['customers']); ?></h3>
                <p>Customers</p>
            </div>
        </div>

        <div class="metric-card">
            <div class="metric-icon gold"><i class="fas fa-user-shield"></i></div>
            <div class="metric-info">
                <h3><?php echo number_format($metrics['admins']); ?></h3>
                <p>Administrators</p>
            </div>
        </div>

        <div class="metric-card">
            <div class="metric-icon green"><i class="fas fa-user-check"></i></div>
            <div class="metric-info">
                <h3><?php echo number_format($metrics['active']); ?></h3>
                <p>Active Accounts</p>
            </div>
        </div>
    </div>

    <!-- Search & Filter Controls -->
    <div class="controls-card">
        <form method="GET" class="filter-form">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" name="search" placeholder="Search by name, username, email, phone..." value="<?php echo htmlspecialchars($search); ?>">
            </div>

            <select name="role" class="filter-select">
                <option value="">All Roles</option>
                <option value="customer" <?php echo $role_filter === 'customer' ? 'selected' : ''; ?>>Customer</option>
                <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>Admin</option>
            </select>

            <select name="status" class="filter-select">
                <option value="">All Statuses</option>
                <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                <option value="banned" <?php echo $status_filter === 'banned' ? 'selected' : ''; ?>>Banned</option>
            </select>

            <button type="submit" class="btn-filter"><i class="fas fa-filter"></i> Filter</button>
            <?php if (!empty($search) || !empty($role_filter) || !empty($status_filter)): ?>
                <a href="<?php echo $base; ?>/users" class="btn-reset"><i class="fas fa-times"></i> Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Users Table -->
    <div class="table-card">
        <div class="table-wrapper">
            <table class="users-table">
                <thead>
                    <tr>
                        <th>User Profile</th>
                        <th>Email & Phone</th>
                        <th>Location</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Orders / Spent</th>
                        <th>Joined Date</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($users) === 0): ?>
                        <tr>
                            <td colspan="8" style="text-align:center; padding: 40px; color: var(--text-muted);">
                                <i class="fas fa-user-slash" style="font-size: 32px; margin-bottom: 10px; display:block;"></i>
                                No users found matching your query criteria.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $u): ?>
                            <?php 
                                $display_name = !empty($u['full_name']) ? $u['full_name'] : (!empty($u['first_name']) ? $u['first_name'] . ' ' . $u['last_name'] : $u['username']);
                                $initials = strtoupper(substr($display_name ?? 'U', 0, 1));
                                $loc = array_filter([$u['city'], $u['state'], $u['country']]);
                                $location_str = count($loc) > 0 ? implode(', ', $loc) : 'N/A';
                            ?>
                            <tr>
                                <td>
                                    <div class="user-profile">
                                        <div class="avatar-circle"><?php echo htmlspecialchars($initials); ?></div>
                                        <div>
                                            <div class="user-name"><?php echo htmlspecialchars($display_name); ?></div>
                                            <div class="user-username">@<?php echo htmlspecialchars($u['username'] ?? 'user_' . $u['id']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div><i class="fas fa-envelope" style="color:var(--text-muted); font-size:12px;"></i> <?php echo htmlspecialchars($u['email']); ?></div>
                                    <?php if (!empty($u['phone'])): ?>
                                        <div style="font-size:12px; color:var(--text-muted); margin-top:2px;">
                                            <i class="fas fa-phone" style="font-size:11px;"></i> <?php echo htmlspecialchars($u['phone']); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span style="font-size: 13px; color: var(--text-dark);">
                                        <i class="fas fa-map-marker-alt" style="color: #ef4444; font-size: 12px;"></i> 
                                        <?php echo htmlspecialchars($location_str); ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" inline-form style="display:inline-block;">
                                        <input type="hidden" name="action" value="update_role">
                                        <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                        <select name="role" class="status-select" onchange="this.form.submit()">
                                            <option value="customer" <?php echo $u['role'] === 'customer' ? 'selected' : ''; ?>>Customer</option>
                                            <option value="admin" <?php echo $u['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                        </select>
                                    </form>
                                </td>
                                <td>
                                    <form method="POST" inline-form style="display:inline-block;">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                        <select name="status" class="status-select" onchange="this.form.submit()">
                                            <option value="active" <?php echo $u['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                            <option value="inactive" <?php echo $u['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                            <option value="banned" <?php echo $u['status'] === 'banned' ? 'selected' : ''; ?>>Banned</option>
                                        </select>
                                    </form>
                                </td>
                                <td>
                                    <div><strong><?php echo number_format($u['order_count']); ?></strong> orders</div>
                                    <div style="font-size:12px; color:var(--success); font-weight:600;">$<?php echo number_format($u['total_spent'], 2); ?></div>
                                </td>
                                <td>
                                    <span style="font-size:13px; color:var(--text-muted);">
                                        <?php echo date('M d, Y', strtotime($u['created_at'])); ?>
                                    </span>
                                </td>
                                <td style="text-align: right;">
                                    <div class="actions-cell" style="justify-content: flex-end;">
                                        <button type="button" class="btn-action" title="View Profile" onclick="openProfileModal(<?php echo htmlspecialchars(json_encode($u)); ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                            <input type="hidden" name="action" value="delete_user">
                                            <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                            <button type="submit" class="btn-action danger" title="Delete User">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- User Profile Modal -->
<div class="modal-overlay" id="profileModal">
    <div class="modal-card">
        <div class="modal-header">
            <h3>User Profile Details</h3>
            <button class="modal-close" onclick="closeProfileModal()">&times;</button>
        </div>
        <div class="modal-body" id="modalBody">
            <!-- Dynamic profile content filled by JS -->
        </div>
    </div>
</div>

<script>
// Auto-hide toast notification
window.addEventListener('load', function() {
    const t = id => document.getElementById(id);
    const toast = t('toastBox');
    if (toast) {
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transition = 'opacity 0.5s ease';
            setTimeout(() => toast.remove(), 500);
        }, 4000);
    }
});

function openProfileModal(u) {
    const modal = document.getElementById('profileModal');
    const body = document.getElementById('modalBody');

    const addressFull = [u.address, u.apartment, u.city, u.state, u.zip, u.country].filter(Boolean).join(', ');

    body.innerHTML = `
        <div class="detail-row"><div class="detail-label">User ID:</div><div class="detail-val">#${u.id}</div></div>
        <div class="detail-row"><div class="detail-label">Full Name:</div><div class="detail-val"><strong>${u.full_name || u.first_name || 'N/A'}</strong></div></div>
        <div class="detail-row"><div class="detail-label">Username:</div><div class="detail-val">@${u.username || 'N/A'}</div></div>
        <div class="detail-row"><div class="detail-label">Email:</div><div class="detail-val">${u.email}</div></div>
        <div class="detail-row"><div class="detail-label">Phone:</div><div class="detail-val">${u.phone || 'N/A'}</div></div>
        <div class="detail-row"><div class="detail-label">Role:</div><div class="detail-val"><span class="badge badge-${u.role}">${u.role}</span></div></div>
        <div class="detail-row"><div class="detail-label">Status:</div><div class="detail-val"><span class="badge badge-${u.status}">${u.status}</span></div></div>
        <div class="detail-row"><div class="detail-label">Shipping Address:</div><div class="detail-val">${addressFull || 'No address saved.'}</div></div>
        <div class="detail-row"><div class="detail-label">Total Orders:</div><div class="detail-val">${u.order_count} orders ($${parseFloat(u.total_spent).toFixed(2)})</div></div>
        <div class="detail-row"><div class="detail-label">Registered On:</div><div class="detail-val">${new Date(u.created_at).toLocaleString()}</div></div>
    `;

    modal.classList.add('open');
}

function closeProfileModal() {
    document.getElementById('profileModal').classList.remove('open');
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeProfileModal();
});
</script>

</body>
</html>
