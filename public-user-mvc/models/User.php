<?php
// models/User.php

require_once __DIR__ . '/../config/db.php';

class User {
    public static function getById($id) {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT id, first_name, last_name, email, phone, address FROM users WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }
}
