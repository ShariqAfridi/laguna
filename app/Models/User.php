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

    public static function findById(int $id): ?array {
        if ($id <= 0) return null;
        self::ensureAvatarColumnExists();
        $conn = \get_db_connection();
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $res = $stmt->get_result();
            return $res ? $res->fetch_assoc() : null;
        }
        return null;
    }

    public static function findByEmailOrUsername(string $identifier): ?array {
        $identifier = trim(strtolower($identifier));
        if (empty($identifier)) return null;
        $conn = \get_db_connection();
        $stmt = $conn->prepare("SELECT * FROM users WHERE LOWER(email) = ? OR LOWER(username) = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("ss", $identifier, $identifier);
            $stmt->execute();
            $res = $stmt->get_result();
            return $res ? $res->fetch_assoc() : null;
        }
        return null;
    }

    public static function findByEmail(string $email): ?array {
        return self::findByEmailOrUsername($email);
    }

    public static function authenticate(string $identifier, string $password): ?array {
        $user = self::findByEmailOrUsername($identifier);
        if (!$user) return null;

        $storedPass = $user['password'] ?? '';
        // Support password_hash, plaintext, and md5 password hashing
        $valid = password_verify($password, $storedPass) || ($password === $storedPass) || (md5($password) === $storedPass);
        if ($valid) {
            return $user;
        }
        return null;
    }

    public static function register(string $fullName, string $email, string $password, string $phone = ''): array {
        $conn = \get_db_connection();
        $email = trim(strtolower($email));
        $fullName = trim($fullName);

        if (empty($fullName) || empty($email) || empty($password)) {
            return ['success' => false, 'error' => 'All required fields must be filled.'];
        }

        if (self::findByEmail($email)) {
            return ['success' => false, 'error' => 'An account with this email address already exists.'];
        }

        $username = strtolower(explode('@', $email)[0]);
        $hashedPass = password_hash($password, PASSWORD_DEFAULT);
        $role = 'customer';
        $status = 'active';

        $stmt = $conn->prepare("INSERT INTO users (username, full_name, email, password, phone, role, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        if ($stmt) {
            $stmt->bind_param("sssssss", $username, $fullName, $email, $hashedPass, $phone, $role, $status);
            if ($stmt->execute()) {
                $newId = $conn->insert_id;
                return ['success' => true, 'user_id' => $newId, 'user' => self::findById($newId)];
            }
        }
        return ['success' => false, 'error' => 'Failed to create account. Please try again.'];
    }

    private static function ensureAvatarColumnExists() {
        $conn = \get_db_connection();
        $res = $conn->query("SHOW COLUMNS FROM users LIKE 'avatar'");
        if ($res && $res->num_rows === 0) {
            $conn->query("ALTER TABLE users ADD COLUMN avatar VARCHAR(255) NULL AFTER email");
        }
    }

    public static function updateProfile(int $userId, array $data): bool {
        if ($userId <= 0) return false;
        self::ensureAvatarColumnExists();
        $conn = \get_db_connection();

        $fullName = trim($data['full_name'] ?? '');
        $email    = trim(strtolower($data['email'] ?? ''));
        $phone    = trim($data['phone'] ?? '');
        $city     = trim($data['city'] ?? '');
        $address  = trim($data['address'] ?? '');
        $avatar   = trim($data['avatar'] ?? '');

        if (empty($fullName) || empty($email)) return false;

        if (!empty($avatar)) {
            $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, city = ?, address = ?, avatar = ? WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("ssssssi", $fullName, $email, $phone, $city, $address, $avatar, $userId);
                return $stmt->execute();
            }
        } else {
            $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, city = ?, address = ? WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("sssssi", $fullName, $email, $phone, $city, $address, $userId);
                return $stmt->execute();
            }
        }
        return false;
    }

    public static function updatePassword(int $userId, string $newPassword): bool {
        if ($userId <= 0 || empty($newPassword)) return false;
        $conn = \get_db_connection();
        $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("si", $hashed, $userId);
            return $stmt->execute();
        }
        return false;
    }
}
?>

