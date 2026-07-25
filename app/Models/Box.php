<?php
// app/Models/Box.php — Box Packaging Database Model
namespace App\Models;

require_once __DIR__ . '/../../config/database.php';

class Box {
    public static function all() {
        $conn = \get_db_connection();
        return $conn->query("SELECT box_id, box_name FROM boxes ORDER BY box_id DESC");
    }

    public static function save($name, $id = null) {
        $conn = \get_db_connection();
        if (!empty($id)) {
            $stmt = $conn->prepare("UPDATE boxes SET box_name=? WHERE box_id=?");
            $stmt->bind_param("si", $name, $id);
        } else {
            $stmt = $conn->prepare("INSERT INTO boxes (box_name) VALUES (?)");
            $stmt->bind_param("s", $name);
        }
        return $stmt->execute();
    }

    public static function delete($id) {
        $conn = \get_db_connection();
        $stmt = $conn->prepare("DELETE FROM boxes WHERE box_id=?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
}
?>
