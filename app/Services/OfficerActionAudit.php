<?php
declare(strict_types=1);

final class OfficerActionAudit
{
    public static function record(PDO $db, int $officer, string $entity, int $id, array $before, array $after): void
    {
        $statement=$db->prepare("INSERT INTO audit_logs (actor_user_id,action_type,entity_type,entity_id,details)
            VALUES (:actor,'OFFICER_MANAGEMENT',:entity,:id,:details)");
        $statement->execute(['actor'=>$officer,'entity'=>$entity,'id'=>$id,
            'details'=>json_encode(['before'=>$before,'after'=>$after],JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE)]);
    }

    public static function history(PDO $db, string $entity, int $id): array
    {
        $statement=$db->prepare("SELECT a.created_at,a.details,u.full_name AS officer_name
            FROM audit_logs a LEFT JOIN users u ON u.user_id=a.actor_user_id
            WHERE a.entity_type=:entity AND a.entity_id=:id AND a.action_type='OFFICER_MANAGEMENT'
            ORDER BY a.audit_id");
        $statement->execute(['entity'=>$entity,'id'=>$id]);
        $rows=$statement->fetchAll();
        foreach ($rows as &$row) $row['change']=json_decode($row['details'],true,512,JSON_THROW_ON_ERROR);
        return $rows;
    }
}
