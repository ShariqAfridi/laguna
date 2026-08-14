<?php
// config/database.php — Database Connection Factory
require_once __DIR__ . '/app.php';

function get_db_connection()
{
    static $conn = null;
    if ($conn === null) {
        $host = env('DB_HOST', 'localhost');
        $username = env('DB_USER', 'root');
        $password = env('DB_PASS', '');
        $database = env('DB_NAME', 'onlifwko_laguna');

        $conn = new mysqli($host, $username, $password, $database);
        if ($conn->connect_error) {
            error_log('Database connection failed: ' . $conn->connect_error);
            die('Database connection error. Please try again later.');
        }
        $conn->set_charset('utf8mb4');

        // Ensure schema columns are synchronized across all environments
        static $schemaChecked = false;
        if (!$schemaChecked) {
            $schemaChecked = true;

            // 1. Ensure colors table wick image columns
            $res = $conn->query("SHOW COLUMNS FROM colors LIKE 'single_wick_image'");
            if ($res && $res->num_rows === 0) {
                @$conn->query("ALTER TABLE colors ADD COLUMN single_wick_image VARCHAR(255) DEFAULT NULL");
                @$conn->query("ALTER TABLE colors ADD COLUMN double_wick_image VARCHAR(255) DEFAULT NULL");
                @$conn->query("ALTER TABLE colors ADD COLUMN triple_wick_image VARCHAR(255) DEFAULT NULL");
            }

            // 2. Ensure products table fragrance_images column and fragrance_id type
            $res_p_img = $conn->query("SHOW COLUMNS FROM products LIKE 'fragrance_images'");
            if ($res_p_img && $res_p_img->num_rows === 0) {
                @$conn->query("ALTER TABLE products ADD COLUMN fragrance_images TEXT DEFAULT NULL AFTER image");
            }

            $res_p_fid = $conn->query("SHOW COLUMNS FROM products LIKE 'fragrance_id'");
            if ($res_p_fid && $row = $res_p_fid->fetch_assoc()) {
                if (strpos(strtolower($row['Type']), 'varchar') === false) {
                    @$conn->query("ALTER TABLE products MODIFY COLUMN fragrance_id VARCHAR(255) DEFAULT NULL");
                }
            }

            // 3. Ensure fragrances table additional columns
            $res_f_sku = $conn->query("SHOW COLUMNS FROM fragrances LIKE 'sku'");
            if ($res_f_sku && $res_f_sku->num_rows === 0) {
                @$conn->query("ALTER TABLE fragrances ADD COLUMN sku VARCHAR(50) DEFAULT NULL AFTER fragrance_name");
                @$conn->query("ALTER TABLE fragrances ADD COLUMN fragrance_image VARCHAR(255) DEFAULT NULL");
                @$conn->query("ALTER TABLE fragrances ADD COLUMN scent_note_image VARCHAR(255) DEFAULT NULL");
                @$conn->query("ALTER TABLE fragrances ADD COLUMN fragrance_description TEXT DEFAULT NULL");
                @$conn->query("ALTER TABLE fragrances ADD COLUMN status TINYINT(1) DEFAULT 1");
                @$conn->query("ALTER TABLE fragrances ADD COLUMN sort_order INT(11) DEFAULT 0");
            }

            // 4. Ensure coupons table exists
            @$conn->query("CREATE TABLE IF NOT EXISTS `coupons` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `code` varchar(50) NOT NULL,
                `description` varchar(255) DEFAULT NULL,
                `type` enum('percentage','fixed') NOT NULL DEFAULT 'percentage',
                `value` decimal(10,2) NOT NULL DEFAULT '0.00',
                `min_order_amount` decimal(10,2) DEFAULT '0.00',
                `max_discount_amount` decimal(10,2) DEFAULT NULL,
                `start_date` date DEFAULT NULL,
                `end_date` date DEFAULT NULL,
                `usage_limit` int(11) DEFAULT NULL,
                `used_count` int(11) DEFAULT '0',
                `status` tinyint(1) NOT NULL DEFAULT '1',
                `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `idx_code` (`code`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
    }
    return $conn;
}
?>
