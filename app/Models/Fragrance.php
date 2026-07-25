<?php
// app/Models/Fragrance.php — Candle Fragrance Database Model
namespace App\Models;

require_once __DIR__ . '/../../config/database.php';

class Fragrance {
    public static function all() {
        $conn = \get_db_connection();
        return $conn->query("SELECT * FROM fragrance ORDER BY fragrance_id DESC");
    }

    public static function save($name, $id = null) {
        $conn = \get_db_connection();
        if (!empty($id)) {
            $stmt = $conn->prepare("UPDATE fragrance SET fragrance_name=? WHERE fragrance_id=?");
            $stmt->bind_param("si", $name, $id);
        } else {
            $stmt = $conn->prepare("INSERT INTO fragrance (fragrance_name) VALUES (?)");
            $stmt->bind_param("s", $name);
        }
        return $stmt->execute();
    }

    public static function delete($id) {
        $conn = \get_db_connection();
        $stmt = $conn->prepare("DELETE FROM fragrance WHERE fragrance_id=?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
}
?>
