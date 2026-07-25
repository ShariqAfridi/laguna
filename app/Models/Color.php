<?php
// app/Models/Color.php — Candle Colors Database Model
namespace App\Models;

require_once __DIR__ . '/../../config/database.php';

class Color {
    public static function all() {
        $conn = \get_db_connection();
        return $conn->query("SELECT * FROM colors ORDER BY color_id DESC");
    }

    public static function save($name, $id = null) {
        $conn = \get_db_connection();
        if (!empty($id)) {
            $stmt = $conn->prepare("UPDATE colors SET color_name=? WHERE color_id=?");
            $stmt->bind_param("si", $name, $id);
        } else {
            $stmt = $conn->prepare("INSERT INTO colors (color_name) VALUES (?)");
            $stmt->bind_param("s", $name);
        }
        return $stmt->execute();
    }

    public static function delete($id) {
        $conn = \get_db_connection();
        $stmt = $conn->prepare("DELETE FROM colors WHERE color_id=?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
}
?>
