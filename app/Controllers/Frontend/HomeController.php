<?php
// app/Controllers/Frontend/HomeController.php

class HomeController {
    public static function index() {
        require_once __DIR__ . '/../../../views/frontend/home.php';
    }
}

HomeController::index();
?>
