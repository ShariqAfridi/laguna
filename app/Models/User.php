<?php
// app/Models/User.php — User Data Access Model
namespace App\Models;

class User {
    public static function getAllExcept($excludeUserId = 0, $excludeAdminName = '', $excludeAdminEmail = '') {
        $conn = \get_db_connection();
        $sql = "SELECT id, username, email, role, status, created_at FROM users WHERE 1=1";
        
        $excludes = [];
        if (!empty($excludeUserId)) {
            $excludes[] = "id != " . intval($excludeUserId);
        }
        if (!empty($excludeAdminName)) {
            $excludes[] = "username != '" . $conn->real_escape_string($excludeAdminName) . "'";
        }
        if (!empty($excludeAdminEmail)) {
            $excludes[] = "email != '" . $conn->real_escape_string($excludeAdminEmail) . "'";
        }
        $excludes[] = "username != 'laguna'";
        $excludes[] = "email != 'admin@lagunavibe.com'";
        
        if (!empty($excludes)) {
            $sql .= " AND (" . implode(" AND ", $excludes) . ")";
        }
        $sql .= " ORDER BY id DESC";
        
        return $conn->query($sql);
    }

    public static function updateStatus(int $userId, string $status): bool {
        $allowed = ['active', 'inactive', 'banned'];
        if ($userId <= 0 || !in_array($status, $allowed, true)) {
            return false;
        }
        $conn = \get_db_connection();
        $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("si", $status, $userId);
            return $stmt->execute();
        }
        return false;
    }

    public static function updateRole(int $userId, string $role): bool {
        $allowed = ['admin', 'customer'];
        if ($userId <= 0 || !in_array($role, $allowed, true)) {
            return false;
        }
        $conn = \get_db_connection();
        $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("si", $role, $userId);
            return $stmt->execute();
        }
        return false;
    }

    public static function deleteUser(int $userId, string $adminName = ''): string {
        if ($userId <= 0) return 'error';
        $conn = \get_db_connection();
        
        $stmtCheck = $conn->prepare("SELECT username FROM users WHERE id = ?");
        if ($stmtCheck) {
            $stmtCheck->bind_param("i", $userId);
            $stmtCheck->execute();
            $res = $stmtCheck->get_result()->fetch_assoc();
            $stmtCheck->close();

            if ($res && !empty($adminName) && $adminName === $res['username']) {
                return 'self_delete_error';
            }
        }

        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $userId);
            if ($stmt->execute()) {
                return 'deleted';
            }
        }
        return 'error';
    }
}
?>

