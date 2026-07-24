<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use App\Core\Database;
use PDO;

final class Feedback extends Model
{
    public static function create(array $data): bool
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("INSERT INTO feedbacks (user_id, feedback_type, request_id, message) VALUES (:user_id, :feedback_type, :request_id, :message)");
        
        $stmt->bindParam(':user_id', $data['user_id'], PDO::PARAM_INT);
        $stmt->bindParam(':feedback_type', $data['feedback_type'], PDO::PARAM_STR);
        
        if (!empty($data['request_id'])) {
            $stmt->bindParam(':request_id', $data['request_id'], PDO::PARAM_STR);
        } else {
            $stmt->bindValue(':request_id', null, PDO::PARAM_NULL);
        }
        
        $stmt->bindParam(':message', $data['message'], PDO::PARAM_STR);
        return $stmt->execute();
    }
}
