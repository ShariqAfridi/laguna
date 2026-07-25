<?php
namespace App\Models;

class Review {
    private static function ensureTableExists() {
        $conn = \get_db_connection();
        $sql = "CREATE TABLE IF NOT EXISTS product_reviews (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            product_name VARCHAR(150) NOT NULL,
            rating INT DEFAULT 5,
            review_text TEXT NOT NULL,
            status VARCHAR(20) DEFAULT 'approved',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        $conn->query($sql);
    }

    public static function getByUser(int $userId): array {
        if ($userId <= 0) return [];
        self::ensureTableExists();
        $conn = \get_db_connection();

        $stmt = $conn->prepare("SELECT * FROM product_reviews WHERE user_id = ? ORDER BY id DESC");
        if ($stmt) {
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $res = $stmt->get_result();
            $reviews = [];
            while ($row = $res->fetch_assoc()) {
                $reviews[] = $row;
            }
            return $reviews;
        }
        return [];
    }

    public static function submit(int $userId, string $productName, int $rating, string $reviewText): bool {
        if ($userId <= 0 || empty($productName) || empty($reviewText)) return false;
        self::ensureTableExists();
        $conn = \get_db_connection();

        $rating = max(1, min(5, $rating));
        $stmt = $conn->prepare("INSERT INTO product_reviews (user_id, product_name, rating, review_text, status) VALUES (?, ?, ?, ?, 'approved')");
        if ($stmt) {
            $stmt->bind_param("isis", $userId, $productName, $rating, $reviewText);
            return $stmt->execute();
        }
        return false;
    }
}
?>
