<?php
namespace App\Models;

class Address {
    private static function ensureTableExists() {
        $conn = \get_db_connection();
        $sql = "CREATE TABLE IF NOT EXISTS customer_addresses (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            title VARCHAR(50) DEFAULT 'Home',
            full_name VARCHAR(100) DEFAULT '',
            address TEXT NOT NULL,
            city VARCHAR(100) NOT NULL,
            state VARCHAR(100) DEFAULT 'CA',
            zip VARCHAR(20) NOT NULL,
            country VARCHAR(100) DEFAULT 'USA',
            is_default TINYINT(1) DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        $conn->query($sql);
    }

    public static function getByUser(int $userId): array {
        if ($userId <= 0) return [];
        self::ensureTableExists();
        $conn = \get_db_connection();

        $stmt = $conn->prepare("SELECT * FROM customer_addresses WHERE user_id = ? ORDER BY is_default DESC, id DESC");
        if ($stmt) {
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $res = $stmt->get_result();
            $addresses = [];
            while ($row = $res->fetch_assoc()) {
                $addresses[] = $row;
            }
            return $addresses;
        }
        return [];
    }

    public static function save(int $userId, array $data): bool {
        if ($userId <= 0) return false;
        self::ensureTableExists();
        $conn = \get_db_connection();

        $title    = trim($data['title'] ?? 'Home');
        $fullName = trim($data['full_name'] ?? '');
        $address  = trim($data['address'] ?? '');
        $city     = trim($data['city'] ?? '');
        $state    = trim($data['state'] ?? 'CA');
        $zip      = trim($data['zip'] ?? '');
        $country  = trim($data['country'] ?? 'USA');
        $isDefault = !empty($data['is_default']) ? 1 : 0;

        if (empty($address) || empty($city) || empty($zip)) {
            return false;
        }

        if ($isDefault === 1) {
            $conn->query("UPDATE customer_addresses SET is_default = 0 WHERE user_id = {$userId}");
        }

        // Check if updating existing or inserting new
        $addressId = intval($data['id'] ?? 0);
        if ($addressId > 0) {
            $stmt = $conn->prepare("UPDATE customer_addresses SET title=?, full_name=?, address=?, city=?, state=?, zip=?, country=?, is_default=? WHERE id=? AND user_id=?");
            if ($stmt) {
                $stmt->bind_param("sssssssiii", $title, $fullName, $address, $city, $state, $zip, $country, $isDefault, $addressId, $userId);
                return $stmt->execute();
            }
        } else {
            $stmt = $conn->prepare("INSERT INTO customer_addresses (user_id, title, full_name, address, city, state, zip, country, is_default) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("isssssssi", $userId, $title, $fullName, $address, $city, $state, $zip, $country, $isDefault);
                return $stmt->execute();
            }
        }
        return false;
    }

    public static function delete(int $userId, int $addressId): bool {
        if ($userId <= 0 || $addressId <= 0) return false;
        self::ensureTableExists();
        $conn = \get_db_connection();
        $stmt = $conn->prepare("DELETE FROM customer_addresses WHERE id = ? AND user_id = ?");
        if ($stmt) {
            $stmt->bind_param("ii", $addressId, $userId);
            return $stmt->execute();
        }
        return false;
    }
}
?>
