<?php
// models/Feedback.php

require_once __DIR__ . '/../config/db.php';

class Feedback {
    public static function create($data) {
        $db = Database::getConnection();
        $stmt = $db->prepare("INSERT INTO feedbacks (user_id, feedback_type, request_id, message) VALUES (:user_id, :feedback_type, :request_id, :message)");
        
        $stmt->bindParam(':user_id', $data['user_id'], PDO::PARAM_INT);
        $stmt->bindParam(':feedback_type', $data['feedback_type'], PDO::PARAM_STR);
        
        // Handle optional request ID
        if (!empty($data['request_id']) && $data['request_id'] !== 'Select request') {
            $stmt->bindParam(':request_id', $data['request_id'], PDO::PARAM_STR);
        } else {
            $stmt->bindValue(':request_id', null, PDO::PARAM_NULL);
        }
        
        $stmt->bindParam(':message', $data['message'], PDO::PARAM_STR);
        return $stmt->execute();
    }
}
