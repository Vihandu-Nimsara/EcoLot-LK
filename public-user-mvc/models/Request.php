<?php
// models/Request.php

require_once __DIR__ . '/../config/db.php';

class Request {
    public static function getAllByUser($userId) {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM requests WHERE user_id = :user_id ORDER BY created_at DESC");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function getLatestByUser($userId, $limit = 5) {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM requests WHERE user_id = :user_id ORDER BY created_at DESC LIMIT :limit");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function getById($id) {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM requests WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch();
    }

    public static function generateId() {
        // Generates an ID format like REQ-2026-12345
        $year = date('Y');
        $random = rand(10000, 99999);
        return "REQ-{$year}-{$random}";
    }

    public static function create($data) {
        $db = Database::getConnection();
        $id = self::generateId();
        
        $stmt = $db->prepare("INSERT INTO requests 
            (id, user_id, postal_code_area, collection_date, pickup_address, category, other_category, quantity, weight, condition_status, note, status) 
            VALUES (:id, :user_id, :postal_code_area, :collection_date, :pickup_address, :category, :other_category, :quantity, :weight, :condition_status, :note, 'Pending')");
        
        $stmt->bindParam(':id', $id, PDO::PARAM_STR);
        $stmt->bindParam(':user_id', $data['user_id'], PDO::PARAM_INT);
        $stmt->bindParam(':postal_code_area', $data['postal_code_area'], PDO::PARAM_STR);
        $stmt->bindParam(':collection_date', $data['collection_date'], PDO::PARAM_STR);
        $stmt->bindParam(':pickup_address', $data['pickup_address'], PDO::PARAM_STR);
        $stmt->bindParam(':category', $data['category'], PDO::PARAM_STR);
        $stmt->bindParam(':other_category', $data['other_category'], PDO::PARAM_STR);
        $stmt->bindParam(':quantity', $data['quantity'], PDO::PARAM_INT);
        $stmt->bindParam(':weight', $data['weight'], PDO::PARAM_STR);
        $stmt->bindParam(':condition_status', $data['condition_status'], PDO::PARAM_STR);
        $stmt->bindParam(':note', $data['note'], PDO::PARAM_STR);
        
        $stmt->execute();
        return $id;
    }

    public static function update($id, $data) {
        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE requests SET 
            postal_code_area = :postal_code_area,
            collection_date = :collection_date,
            pickup_address = :pickup_address,
            category = :category,
            other_category = :other_category,
            quantity = :quantity,
            weight = :weight,
            condition_status = :condition_status,
            note = :note
            WHERE id = :id");
            
        $stmt->bindParam(':id', $id, PDO::PARAM_STR);
        $stmt->bindParam(':postal_code_area', $data['postal_code_area'], PDO::PARAM_STR);
        $stmt->bindParam(':collection_date', $data['collection_date'], PDO::PARAM_STR);
        $stmt->bindParam(':pickup_address', $data['pickup_address'], PDO::PARAM_STR);
        $stmt->bindParam(':category', $data['category'], PDO::PARAM_STR);
        $stmt->bindParam(':other_category', $data['other_category'], PDO::PARAM_STR);
        $stmt->bindParam(':quantity', $data['quantity'], PDO::PARAM_INT);
        $stmt->bindParam(':weight', $data['weight'], PDO::PARAM_STR);
        $stmt->bindParam(':condition_status', $data['condition_status'], PDO::PARAM_STR);
        $stmt->bindParam(':note', $data['note'], PDO::PARAM_STR);
        
        return $stmt->execute();
    }

    public static function getStatsByUser($userId) {
        $db = Database::getConnection();
        
        // Total Requests
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM requests WHERE user_id = :user_id");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $total = $stmt->fetch()['total'];

        // Completed Requests
        $stmt = $db->prepare("SELECT COUNT(*) as completed FROM requests WHERE user_id = :user_id AND status = 'Completed'");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $completed = $stmt->fetch()['completed'];

        // Pending Reviews (Pending status)
        $stmt = $db->prepare("SELECT COUNT(*) as pending FROM requests WHERE user_id = :user_id AND status = 'Pending'");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $pending = $stmt->fetch()['pending'];

        // Recycled Weight (Sum of weight of Completed requests)
        $stmt = $db->prepare("SELECT SUM(weight) as weight FROM requests WHERE user_id = :user_id AND status = 'Completed'");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $weight = $stmt->fetch()['weight'];
        $weight = $weight ? $weight : 0.0;

        return [
            'total' => $total,
            'completed' => $completed,
            'pending' => $pending,
            'weight' => $weight
        ];
    }
}
