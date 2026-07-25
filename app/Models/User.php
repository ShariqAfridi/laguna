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
}

if (!class_exists('User', false)) {
    class_alias('App\Models\User', 'User');
}
?>
