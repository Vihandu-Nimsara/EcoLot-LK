<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use App\Core\Database;
use PDO;

final class EWasteRequest extends Model
{
    public static function getAllByUser(int $userId): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM ewaste_requests WHERE user_id = :user_id ORDER BY created_at DESC");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function getLatestByUser(int $userId, int $limit = 5): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM ewaste_requests WHERE user_id = :user_id ORDER BY created_at DESC LIMIT :limit");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function getById(string $id)
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM ewaste_requests WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch();
    }

    public static function generateId(): string
    {
        $year = date('Y');
        $random = rand(10000, 99999);
        return "REQ-{$year}-{$random}";
    }

    public static function create(array $data): string
    {
        $db = Database::getConnection();
        $id = self::generateId();
        
        $stmt = $db->prepare("INSERT INTO ewaste_requests 
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

    public static function update(string $id, array $data): bool
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE ewaste_requests SET 
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

    public static function getStatsByUser(int $userId): array
    {
        $db = Database::getConnection();
        
        // Total Requests
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM ewaste_requests WHERE user_id = :user_id");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $total = (int)$stmt->fetch()['total'];

        // Completed
        $stmt = $db->prepare("SELECT COUNT(*) as completed FROM ewaste_requests WHERE user_id = :user_id AND status = 'Completed'");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $completed = (int)$stmt->fetch()['completed'];

        // Pending
        $stmt = $db->prepare("SELECT COUNT(*) as pending FROM ewaste_requests WHERE user_id = :user_id AND status = 'Pending'");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $pending = (int)$stmt->fetch()['pending'];

        // Weight
        $stmt = $db->prepare("SELECT SUM(weight) as weight FROM ewaste_requests WHERE user_id = :user_id AND status = 'Completed'");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $weight = $stmt->fetch()['weight'];
        $weight = $weight ? (float)$weight : 0.0;

        return [
            'total' => $total,
            'completed' => $completed,
            'pending' => $pending,
            'weight' => $weight
        ];
    }
}
