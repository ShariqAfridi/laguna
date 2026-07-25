<?php
// app/Models/Product.php — Candle Products Database Model
namespace App\Models;

class Product {
    public static function all() {
        $conn = \get_db_connection();
        return $conn->query("SELECT p.*, f.fragrance_name, b.box_name, c.color_name, s.size_name 
                             FROM product p 
                             LEFT JOIN fragrance f ON p.fragrance_id = f.fragrance_id 
                             LEFT JOIN boxes b ON p.box_id = b.box_id 
                             LEFT JOIN colors c ON p.color_id = c.color_id 
                             LEFT JOIN sizes s ON p.size_id = s.size_id 
                             ORDER BY p.product_id DESC");
    }

    public static function find($id) {
        $conn = \get_db_connection();
        $stmt = $conn->prepare("SELECT * FROM product WHERE product_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public static function delete($id) {
        $conn = \get_db_connection();
        $stmt = $conn->prepare("DELETE FROM product WHERE product_id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
}
?>
