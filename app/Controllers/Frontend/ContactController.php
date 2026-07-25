<?php
class ContactController {
    public static function index() {
        require_once __DIR__ . '/../../../views/frontend/contact.php';
    }
}
ContactController::index();
?>
