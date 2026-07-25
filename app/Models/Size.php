<?php
// app/Models/Size.php — Candle Sizes Database Model
namespace App\Models;

require_once __DIR__ . '/../../config/database.php';

class Size {
    public static function all() {
        $conn = \get_db_connection();
        return $conn->query("SELECT * FROM sizes ORDER BY size_id DESC");
    }

    public static function save($name, $id = null) {
        $conn = \get_db_connection();
        if (!empty($id)) {
            $stmt = $conn->prepare("UPDATE sizes SET size_name=? WHERE size_id=?");
            $stmt->bind_param("si", $name, $id);
        } else {
            $stmt = $conn->prepare("INSERT INTO sizes (size_name) VALUES (?)");
            $stmt->bind_param("s", $name);
        }
        return $stmt->execute();
    }

    public static function delete($id) {
        $conn = \get_db_connection();
        $stmt = $conn->prepare("DELETE FROM sizes WHERE size_id=?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
}
?>
