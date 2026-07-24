<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use App\Core\Database;
use PDO;

final class User extends Model
{
    public static function getById(int $id)
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT id, first_name, last_name, email, phone, address FROM users WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }
}
