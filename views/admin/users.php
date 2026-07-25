<?php
$conn = \get_db_connection();
$updateMsg = $updateMsg ?? ($_GET['msg'] ?? '');


// ─── Pagination & filters ─────────────────────────────────────────────────────
$page   = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit  = 10;
$offset = ($page - 1) * $limit;

$roleFilter   = isset($_GET['role']) ? trim($_GET['role']) : '';
$statusFilter = isset($_GET['status']) ? trim($_GET['status']) : '';
$searchTerm   = isset($_GET['search']) ? trim($_GET['search']) : '';
$msg          = isset($_GET['msg']) ? trim($_GET['msg']) : '';

// ─── Query Building ───────────────────────────────────────────────────────────
$currentUserId     = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$currentAdminName  = isset($_SESSION['admin_name']) ? mysqli_real_escape_string($conn, $_SESSION['admin_name']) : '';
$currentAdminEmail = isset($_SESSION['admin_email']) ? mysqli_real_escape_string($conn, $_SESSION['admin_email']) : '';

$excludeConds = [];
if ($currentUserId > 0) {
    $excludeConds[] = "u.id != $currentUserId";
}
if (!empty($currentAdminName) && $currentAdminName !== 'Admin') {
    $excludeConds[] = "u.username != '$currentAdminName'";
}
if (!empty($currentAdminEmail)) {
    $excludeConds[] = "u.email != '$currentAdminEmail'";
}
// Always exclude primary admin credentials
$excludeConds[] = "u.username != 'laguna'";
$excludeConds[] = "u.email != 'admin@lagunavibe.com'";

$excludeSql = " AND " . implode(" AND ", array_unique($excludeConds));

$where = "WHERE 1=1" . $excludeSql;
if (!empty($roleFilter) && $roleFilter !== 'all') {
    $r = mysqli_real_escape_string($conn, $roleFilter);
    $where .= " AND u.role = '$r'";
}
if (!empty($statusFilter) && $statusFilter !== 'all') {
    $s = mysqli_real_escape_string($conn, $statusFilter);
    $where .= " AND u.status = '$s'";
}
if (!empty($searchTerm)) {
    $st = mysqli_real_escape_string($conn, $searchTerm);
    $where .= " AND (u.username LIKE '%$st%' OR u.full_name LIKE '%$st%' OR u.email LIKE '%$st%' OR u.phone LIKE '%$st%' OR u.city LIKE '%$st%')";
}

// Total Count & Pagination
$countSql = "SELECT COUNT(*) as total FROM users u $where";
$countRes = mysqli_query($conn, $countSql);
$totalUsers = $countRes ? mysqli_fetch_assoc($countRes)['total'] : 0;
$totalPages = ceil($totalUsers / $limit);

// Main Users Data Query
$sql = "SELECT u.*, 
               COUNT(o.id) as order_count, 
               COALESCE(SUM(o.total), 0) as total_spent
        FROM users u
        LEFT JOIN orders o ON o.user_id = u.id OR (o.email IS NOT NULL AND o.email = u.email)
        $where
        GROUP BY u.id
        ORDER BY u.created_at DESC
        LIMIT $limit OFFSET $offset";

$result = mysqli_query($conn, $sql);
$users = [];
if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $users[] = $row;
    }
}

// ─── Status & Role Counts for Pills ──────────────────────────────────────────
$roleWhere = !empty($excludeSql) ? "WHERE 1=1 " . str_replace('u.', '', $excludeSql) : "";
$roleCounts = ['all' => 0, 'customer' => 0, 'admin' => 0];
$roleRes = mysqli_query($conn, "SELECT role, COUNT(*) as count FROM users $roleWhere GROUP BY role");
if ($roleRes) {
    while ($row = mysqli_fetch_assoc($roleRes)) {
        $roleCounts[$row['role']] = (int)$row['count'];
    }
}
$roleCounts['all'] = array_sum($roleCounts);

$statusCounts = ['active' => 0, 'inactive' => 0, 'banned' => 0];
$statusRes = mysqli_query($conn, "SELECT status, COUNT(*) as count FROM users $roleWhere GROUP BY status");
if ($statusRes) {
    while ($row = mysqli_fetch_assoc($statusRes)) {
        $statusCounts[$row['status']] = (int)$row['count'];
    }
}

function userQs($page, $role, $status, $search) {
    $q = '?page=' . $page;
    if (!empty($role))   $q .= '&role=' . urlencode($role);
    if (!empty($status)) $q .= '&status=' . urlencode($status);
    if (!empty($search)) $q .= '&search=' . urlencode($search);
    return $q;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users | LVB Atelier</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif; background:#f1f3f6; }
        .main-content { padding:24px 0px; }
        .users-section { background:#f7f7f7; border-radius:16px; padding:30px; min-height:80vh; color:#1a1a1a; }

        /* Toast Alert */
        .toast { display:none; padding:12px 22px; border-radius:10px; margin-bottom:20px; font-size:.9rem; align-items:center; gap:10px; }
        .toast.show { display:flex; }
        .toast.success { background:#e8f5e9; color:#2e7d32; border:1px solid #a5d6a7; }
        .toast.error { background:#ffebee; color:#c62828; border:1px solid #ef9a9a; }

        /* Header */
        .users-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:25px; flex-wrap:wrap; gap:15px; }
        .users-header h2 { font-size:1.5rem; font-weight:700; }
        .users-controls { display:flex; gap:10px; flex-wrap:wrap; }

        .search-box { display:flex; align-items:center; background:#fff; border-radius:25px; border:1px solid #e0e0e0; padding:5px 15px; }
        .search-box input { border:none; padding:8px 10px; outline:none; width:220px; font-size:14px; }
        .search-box button { background:none; border:none; color:#888; cursor:pointer; }

        .btn-reset { background:#e0e0e0; border:none; padding:8px 15px; border-radius:20px; font-size:.8rem; cursor:pointer; color:#555; text-decoration:none; display:inline-flex; align-items:center; gap:6px; }
        .btn-reset:hover { background:#ccc; }
        .export-btn { background:#2ecc71; color:#fff; border:none; padding:8px 15px; border-radius:20px; font-size:.85rem; display:flex; align-items:center; gap:8px; cursor:pointer; }
        .export-btn:hover { background:#27ae60; }

        /* Status & Role Filter Pills */
        .status-filters { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:20px; }
        .status-pill { background:#fff; border:1px solid #e0e0e0; padding:6px 16px; border-radius:30px; font-size:.8rem; color:#555; text-decoration:none; display:inline-block; transition:all .2s; }
        .status-pill:hover { background:#e8e8e8; }
        .status-pill.active { background:#1a1a1a; color:#fff; border-color:#1a1a1a; }
        .status-pill .count { background:rgba(0,0,0,.1); border-radius:20px; padding:2px 8px; margin-left:8px; font-size:.7rem; }
        .status-pill.active .count { background:rgba(255,255,255,.2); }

        /* Table Card */
        .users-card { background:#fff; border-radius:16px; overflow-x:auto; box-shadow:0 2px 10px rgba(0,0,0,.05); }
        .users-table { width:100%; border-collapse:collapse; min-width:950px; }
        .users-table th { text-align:left; padding:16px 20px; border-bottom:1px solid #f0f0f0; font-size:.8rem; color:#888; font-weight:600; text-transform:uppercase; letter-spacing:.5px; }
        .users-table td { padding:16px 20px; border-bottom:1px solid #f5f5f5; font-size:.9rem; color:#333; vertical-align:middle; }
        .users-table tr:hover td { background:#fafafa; }

        /* User Profile Pill */
        .user-flex { display:flex; align-items:center; gap:12px; }
        .user-avatar { width:38px; height:38px; border-radius:50%; background:#e3f2fd; color:#1565c0; font-weight:700; font-size:14px; display:flex; align-items:center; justify-content:center; text-transform:uppercase; border:1px solid #bbdefb; }
        .user-info-name { font-weight:600; color:#1a1a1a; font-size:.9rem; }
        .user-info-username { font-size:.75rem; color:#777; }

        /* Badges */
        .badge { display:inline-block; padding:4px 12px; border-radius:30px; font-size:.75rem; font-weight:600; white-space:nowrap; text-transform:capitalize; }
        .b-admin { background:#fff3e0; color:#e65100; border:1px solid #ffe0b2; }
        .b-customer { background:#e3f2fd; color:#1565c0; border:1px solid #bbdefb; }

        .b-active { background:#e8f5e9; color:#2e7d32; }
        .b-inactive { background:#f5f5f5; color:#616161; }
        .b-banned { background:#ffebee; color:#c62828; }

        /* Action Dropdown Menu */
        .status-wrap { position:relative; display:inline-block; }
        .status-wrap > input[type=checkbox] { display:none; }
        .status-wrap > label { cursor:pointer; background:none; border:none; font-size:1.1rem; color:#999; padding:6px 10px; border-radius:8px; display:inline-block; transition:all .2s; user-select:none; }
        .status-wrap > label:hover { background:#f0f0f0; color:#333; }
        .status-menu { display:none; position:absolute; right:0; top:38px; background:#fff; border-radius:12px; box-shadow:0 10px 30px rgba(0,0,0,.15); min-width:190px; z-index:999; overflow:hidden; }
        .status-wrap > input[type=checkbox]:checked ~ .status-menu { display:block; }
        .status-menu form { margin:0; padding:0; }
        .status-menu button { display:flex; align-items:center; gap:10px; width:100%; padding:11px 16px; border:none; background:none; cursor:pointer; font-size:.85rem; color:#333; text-align:left; transition:background .2s; }
        .status-menu button:hover { background:#f5f5f5; }
        .status-menu button i { width:18px; color:#888; font-size:.9rem; }
        .btn-cancel { color:#c62828 !important; }
        .btn-cancel i { color:#c62828 !important; }

        .view-btn { background:none; border:none; cursor:pointer; font-size:1.1rem; color:#3498db; padding:6px 10px; border-radius:8px; transition:all .2s; }
        .view-btn:hover { background:#e3f2fd; }

        /* Pagination */
        .pagination { display:flex; justify-content:center; gap:8px; margin-top:30px; flex-wrap:wrap; }
        .pagination a,.pagination span { padding:8px 14px; border-radius:10px; text-decoration:none; color:#555; background:#fff; border:1px solid #e0e0e0; font-size:.85rem; transition:all .2s; }
        .pagination a:hover { background:#1a1a1a; color:#fff; border-color:#1a1a1a; }
        .pagination .active { background:#1a1a1a; color:#fff; border-color:#1a1a1a; }
        .pagination .disabled { opacity:.5; pointer-events:none; }

        .empty-state { text-align:center; padding:60px 20px; color:#888; }
        .empty-state i { font-size:48px; margin-bottom:15px; color:#ccc; display:block; }

        /* Modal */
        .modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.55); z-index:2000; align-items:center; justify-content:center; padding:16px; }
        .modal-overlay.open { display:flex; animation:mFadeIn .2s ease; }
        @keyframes mFadeIn { from{opacity:0} to{opacity:1} }

        .modal-box { background:#fff; border-radius:16px; width:100%; max-width:540px; box-shadow:0 20px 50px rgba(0,0,0,.2); overflow:hidden; }
        .modal-header { padding:20px 24px; background:#fafafa; border-bottom:1px solid #eee; display:flex; justify-content:space-between; align-items:center; }
        .modal-header h3 { font-size:1.1rem; font-weight:700; color:#1a1a1a; }
        .modal-close { background:none; border:none; font-size:1.4rem; color:#888; cursor:pointer; }
        .modal-body { padding:24px; max-height:80vh; overflow-y:auto; }

        .detail-row { display:flex; justify-content:space-between; padding:10px 0; border-bottom:1px solid #f5f5f5; font-size:.88rem; }
        .detail-row:last-child { border-bottom:none; }
        .detail-label { color:#777; font-weight:500; width:140px; }
        .detail-val { color:#1a1a1a; font-weight:600; text-align:right; flex:1; word-break:break-word; }
    </style>
</head>
<body>

<div class="main-content">
    <div class="users-section">

        <!-- Toast Notifications -->
        <?php if (!empty($msg)): ?>
            <?php if ($msg === 'status_updated'): ?>
                <div class="toast success show"><i class="fas fa-check-circle"></i> User status updated successfully.</div>
            <?php elseif ($msg === 'role_updated'): ?>
                <div class="toast success show"><i class="fas fa-check-circle"></i> User role updated successfully.</div>
            <?php elseif ($msg === 'deleted'): ?>
                <div class="toast success show"><i class="fas fa-check-circle"></i> User deleted successfully.</div>
            <?php elseif ($msg === 'self_delete_error'): ?>
                <div class="toast error show"><i class="fas fa-exclamation-circle"></i> You cannot delete your logged-in admin account!</div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Header -->
        <div class="users-header">
            <h2>Users Management (<?php echo $totalUsers; ?>)</h2>
            <div class="users-controls">
                <form method="GET" action="" style="display:flex; gap:10px;">
                    <?php if (!empty($roleFilter)): ?><input type="hidden" name="role" value="<?php echo htmlspecialchars($roleFilter); ?>"><?php endif; ?>
                    <?php if (!empty($statusFilter)): ?><input type="hidden" name="status" value="<?php echo htmlspecialchars($statusFilter); ?>"><?php endif; ?>
                    <div class="search-box">
                        <input type="text" name="search" placeholder="Search user, email, city..." value="<?php echo htmlspecialchars($searchTerm); ?>">
                        <button type="submit"><i class="fas fa-search"></i></button>
                    </div>
                    <?php if (!empty($searchTerm) || !empty($roleFilter) || !empty($statusFilter)): ?>
                        <a href="<?php echo $base; ?>/admin/users" class="btn-reset"><i class="fas fa-times"></i> Reset</a>
                    <?php endif; ?>
                </form>
                <button class="export-btn" onclick="exportToCSV()"><i class="fas fa-file-export"></i> Export CSV</button>
            </div>
        </div>

        <!-- Role & Status Pills -->
        <div class="status-filters">
            <a href="<?php echo userQs(1, 'all', $statusFilter, $searchTerm); ?>" class="status-pill <?php echo (empty($roleFilter) || $roleFilter === 'all') ? 'active' : ''; ?>">
                All Roles <span class="count"><?php echo $roleCounts['all']; ?></span>
            </a>
            <a href="<?php echo userQs(1, 'customer', $statusFilter, $searchTerm); ?>" class="status-pill <?php echo $roleFilter === 'customer' ? 'active' : ''; ?>">
                Customers <span class="count"><?php echo $roleCounts['customer']; ?></span>
            </a>
            <a href="<?php echo userQs(1, 'admin', $statusFilter, $searchTerm); ?>" class="status-pill <?php echo $roleFilter === 'admin' ? 'active' : ''; ?>">
                Admins <span class="count"><?php echo $roleCounts['admin']; ?></span>
            </a>
            <span style="border-right:1px solid #e0e0e0; margin:0 4px;"></span>
            <a href="<?php echo userQs(1, $roleFilter, 'active', $searchTerm); ?>" class="status-pill <?php echo $statusFilter === 'active' ? 'active' : ''; ?>">
                Active <span class="count"><?php echo $statusCounts['active']; ?></span>
            </a>
            <a href="<?php echo userQs(1, $roleFilter, 'banned', $searchTerm); ?>" class="status-pill <?php echo $statusFilter === 'banned' ? 'active' : ''; ?>">
                Banned <span class="count"><?php echo $statusCounts['banned']; ?></span>
            </a>
        </div>

        <!-- Users Table Card -->
        <div class="users-card">
            <?php if (count($users) === 0): ?>
                <div class="empty-state">
                    <i class="fas fa-user-slash"></i>
                    <p>No users found matching your search or filter options.</p>
                </div>
            <?php else: ?>
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>S.No</th>
                            <th>User Name</th>
                            <th>Email / Phone</th>
                            <th>Location</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Orders / Spent</th>
                            <th>Joined</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $sno = $offset + 1;
                        foreach ($users as $u): 
                            $name = !empty($u['full_name']) ? $u['full_name'] : (!empty($u['first_name']) ? $u['first_name'] . ' ' . $u['last_name'] : $u['username']);
                            $initial = strtoupper(substr($name ?? 'U', 0, 1));
                            $locParts = array_filter([$u['city'], $u['state'], $u['country']]);
                            $locStr = count($locParts) > 0 ? implode(', ', $locParts) : 'N/A';
                            $modalId = 'modal_user_' . $u['id'];
                        ?>
                            <tr>
                                <td><strong>#<?php echo $sno++; ?></strong></td>
                                <td>
                                    <div class="user-flex">
                                        <div class="user-avatar"><?php echo htmlspecialchars($initial); ?></div>
                                        <div>
                                            <div class="user-info-name"><?php echo htmlspecialchars($name); ?></div>
                                            <div class="user-info-username">@<?php echo htmlspecialchars($u['username'] ?? 'user_' . $u['id']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div><i class="fas fa-envelope" style="color:#aaa; font-size:.75rem;"></i> <?php echo htmlspecialchars($u['email']); ?></div>
                                    <?php if (!empty($u['phone'])): ?>
                                        <div style="font-size:.8rem; color:#777; margin-top:2px;">
                                            <i class="fas fa-phone" style="font-size:.7rem;"></i> <?php echo htmlspecialchars($u['phone']); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($locStr); ?></td>
                                <td>
                                    <span class="badge <?php echo $u['role'] === 'admin' ? 'b-admin' : 'b-customer'; ?>">
                                        <?php echo ucfirst($u['role']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge <?php echo $u['status'] === 'active' ? 'b-active' : ($u['status'] === 'banned' ? 'b-banned' : 'b-inactive'); ?>">
                                        <?php echo ucfirst($u['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div><strong><?php echo (int)$u['order_count']; ?></strong> orders</div>
                                    <div style="font-size:.8rem; color:#2ecc71; font-weight:600;">$<?php echo number_format($u['total_spent'], 2); ?></div>
                                </td>
                                <td>
                                    <span style="font-size:.8rem; color:#777;">
                                        <?php echo date('M d, Y', strtotime($u['created_at'])); ?>
                                    </span>
                                </td>
                                <td style="text-align:right;">
                                    <!-- Action Eye Modal Button -->
                                    <button class="view-btn" title="View Profile" onclick="openUserModal('<?php echo $modalId; ?>')">
                                        <i class="fas fa-eye"></i>
                                    </button>

                                    <!-- Dropdown menu for role/status toggle & delete -->
                                    <div class="status-wrap">
                                        <input type="checkbox" id="chk_<?php echo $u['id']; ?>">
                                        <label for="chk_<?php echo $u['id']; ?>"><i class="fas fa-ellipsis-v"></i></label>
                                        <div class="status-menu">
                                            <!-- Toggle Status -->
                                            <form method="POST">
                                                <input type="hidden" name="action" value="update_status">
                                                <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                                <?php if ($u['status'] !== 'active'): ?>
                                                    <input type="hidden" name="status" value="active">
                                                    <button type="submit"><i class="fas fa-check-circle" style="color:#2ecc71;"></i> Set Active</button>
                                                <?php endif; ?>
                                                <?php if ($u['status'] !== 'inactive'): ?>
                                                    <input type="hidden" name="status" value="inactive">
                                                    <button type="submit"><i class="fas fa-pause-circle" style="color:#95a5a6;"></i> Set Inactive</button>
                                                <?php endif; ?>
                                                <?php if ($u['status'] !== 'banned'): ?>
                                                    <input type="hidden" name="status" value="banned">
                                                    <button type="submit"><i class="fas fa-ban" style="color:#e74c3c;"></i> Ban User</button>
                                                <?php endif; ?>
                                            </form>

                                            <!-- Toggle Role -->
                                            <form method="POST">
                                                <input type="hidden" name="action" value="update_role">
                                                <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                                <?php if ($u['role'] === 'customer'): ?>
                                                    <input type="hidden" name="role" value="admin">
                                                    <button type="submit"><i class="fas fa-user-shield" style="color:#f39c12;"></i> Make Admin</button>
                                                <?php else: ?>
                                                    <input type="hidden" name="role" value="customer">
                                                    <button type="submit"><i class="fas fa-user" style="color:#3498db;"></i> Make Customer</button>
                                                <?php endif; ?>
                                            </form>

                                            <!-- Delete User -->
                                            <form method="POST" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                                <input type="hidden" name="action" value="delete_user">
                                                <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                                <button type="submit" class="btn-cancel"><i class="fas fa-trash-alt"></i> Delete User</button>
                                            </form>
                                        </div>
                                    </div>
                                </td>
                            </tr>

                            <!-- User Detail Modal -->
                            <div class="modal-overlay" id="<?php echo $modalId; ?>">
                                <div class="modal-box">
                                    <div class="modal-header">
                                        <h3>User Profile — <?php echo htmlspecialchars($name); ?></h3>
                                        <button class="modal-close" onclick="closeUserModal('<?php echo $modalId; ?>')">&times;</button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="detail-row"><div class="detail-label">User ID</div><div class="detail-val">#<?php echo $u['id']; ?></div></div>
                                        <div class="detail-row"><div class="detail-label">Full Name</div><div class="detail-val"><?php echo htmlspecialchars($name); ?></div></div>
                                        <div class="detail-row"><div class="detail-label">Username</div><div class="detail-val">@<?php echo htmlspecialchars($u['username'] ?? 'N/A'); ?></div></div>
                                        <div class="detail-row"><div class="detail-label">Email</div><div class="detail-val"><?php echo htmlspecialchars($u['email']); ?></div></div>
                                        <div class="detail-row"><div class="detail-label">Phone</div><div class="detail-val"><?php echo htmlspecialchars($u['phone'] ?? 'N/A'); ?></div></div>
                                        <div class="detail-row"><div class="detail-label">Role</div><div class="detail-val"><span class="badge <?php echo $u['role'] === 'admin' ? 'b-admin' : 'b-customer'; ?>"><?php echo ucfirst($u['role']); ?></span></div></div>
                                        <div class="detail-row"><div class="detail-label">Account Status</div><div class="detail-val"><span class="badge <?php echo $u['status'] === 'active' ? 'b-active' : ($u['status'] === 'banned' ? 'b-banned' : 'b-inactive'); ?>"><?php echo ucfirst($u['status']); ?></span></div></div>
                                        <div class="detail-row"><div class="detail-label">Shipping Address</div><div class="detail-val"><?php echo htmlspecialchars(implode(', ', array_filter([$u['address'], $u['apartment'], $u['city'], $u['state'], $u['zip'], $u['country']])) ?: 'No address saved'); ?></div></div>
                                        <div class="detail-row"><div class="detail-label">Orders Placed</div><div class="detail-val"><?php echo (int)$u['order_count']; ?> orders ($<?php echo number_format($u['total_spent'], 2); ?>)</div></div>
                                        <div class="detail-row"><div class="detail-label">Registration Date</div><div class="detail-val"><?php echo date('M d, Y h:i A', strtotime($u['created_at'])); ?></div></div>
                                    </div>
                                </div>
                            </div>

                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="<?php echo userQs($page - 1, $roleFilter, $statusFilter, $searchTerm); ?>">&laquo; Prev</a>
                <?php else: ?>
                    <span class="disabled">&laquo; Prev</span>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <?php if ($i === $page): ?>
                        <span class="active"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="<?php echo userQs($i, $roleFilter, $statusFilter, $searchTerm); ?>"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <a href="<?php echo userQs($page + 1, $roleFilter, $statusFilter, $searchTerm); ?>">Next &raquo;</a>
                <?php else: ?>
                    <span class="disabled">Next &raquo;</span>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    </div>
</div>

<script>
/* ── Dropdown close on outside click ── */
document.addEventListener('click', function(e) {
    if (!e.target.closest('.status-wrap')) {
        document.querySelectorAll('.status-wrap input[type=checkbox]').forEach(cb => cb.checked = false);
    }
});

/* ── Auto-hide toast ── */
window.addEventListener('load', function() {
    const t = document.querySelector('.toast.show');
    if (t) setTimeout(() => t.style.display = 'none', 4000);
});

/* ── Modal open / close ── */
function openUserModal(id) {
    const el = document.getElementById(id);
    if (el) {
        el.classList.add('open');
        document.body.style.overflow = 'hidden';
    }
}
function closeUserModal(id) {
    const el = document.getElementById(id);
    if (el) {
        el.classList.remove('open');
        document.body.style.overflow = '';
    }
}
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal-overlay.open').forEach(m => {
            m.classList.remove('open');
            document.body.style.overflow = '';
        });
    }
});

/* ── CSV export ── */
function exportToCSV() {
    let csv = "S.No,Full Name,Username,Email,Phone,Location,Role,Status,Orders,Total Spent,Joined Date\n";
    document.querySelectorAll('.users-table tbody tr').forEach(row => {
        const c = row.querySelectorAll('td');
        if (c.length < 8) return;
        csv += [
            c[0].innerText.trim(),
            '"' + c[1].querySelector('.user-info-name').innerText.replace(/"/g,'""').trim() + '"',
            '"' + c[1].querySelector('.user-info-username').innerText.replace(/"/g,'""').trim() + '"',
            '"' + c[2].innerText.replace(/"/g,'""').trim() + '"',
            '"' + c[3].innerText.trim() + '"',
            '"' + c[4].innerText.trim() + '"',
            '"' + c[5].innerText.trim() + '"',
            '"' + c[6].innerText.trim() + '"',
            '"' + c[7].innerText.trim() + '"'
        ].join(',') + '\n';
    });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(new Blob([csv], {type:'text/csv;charset=utf-8;'}));
    a.download = 'users_export.csv';
    a.click();
}
</script>

</body>
</html>
