<?php
// app/Models/Accessory.php — Accessory Items Database Model
require_once __DIR__ . '/../../config/database.php';

class Accessory {
    public static function all() {
        $conn = get_db_connection();
        return $conn->query("SELECT * FROM accessory ORDER BY created_at DESC");
    }

    public static function find($id) {
        $conn = get_db_connection();
        $stmt = $conn->prepare("SELECT * FROM accessory WHERE accessory_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public static function delete($id) {
        $conn = get_db_connection();
        $stmt = $conn->prepare("DELETE FROM accessory WHERE accessory_id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
}
?>
