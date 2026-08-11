<?php
// app/Models/Product.php — Candle Products Database Model
namespace App\Models;

class Product {
    public static function all() {
        $conn = \get_db_connection();
        return $conn->query("SELECT p.*, f.fragrance_name 
                             FROM products p 
                             LEFT JOIN fragrances f ON p.fragrance_id = f.fragrance_id 
                             ORDER BY p.product_id DESC");
    }

    public static function find($id) {
        $conn = \get_db_connection();
        $stmt = $conn->prepare("SELECT * FROM products WHERE product_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public static function delete($id) {
        $conn = \get_db_connection();
        $stmt = $conn->prepare("DELETE FROM products WHERE product_id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
}
?>

