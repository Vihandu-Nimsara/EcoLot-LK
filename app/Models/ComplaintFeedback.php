<?php
declare(strict_types=1);

final class ComplaintFeedback extends Model
{
    protected string $table = 'complaint_feedback';
    protected string $primaryKey = 'feedback_id';

    private const TYPES = ['COMPLAINT', 'SUGGESTION', 'COMPLIMENT'];

    public function submit(int $publicUserId, string $type, ?int $requestId, string $message): int
    {
        if (!in_array($type, self::TYPES, true)) {
            throw new DomainException('Choose a feedback type.');
        }

        $message = trim($message);
        $length = mb_strlen($message);
        if ($length < 10) {
            throw new DomainException('Please write at least 10 characters.');
        }
        if ($length > 2000) {
            throw new DomainException('Your message must be 2000 characters or fewer.');
        }

        if ($requestId !== null) {
            $owned = $this->query(
                'SELECT request_id FROM e_waste_requests
                 WHERE request_id = :request_id AND public_user_id = :public_user_id LIMIT 1',
                ['request_id' => $requestId, 'public_user_id' => $publicUserId]
            )->fetch();
            if (!$owned) {
                throw new DomainException('The selected request was not found.');
            }
        }

        return (int) $this->create([
            'public_user_id' => $publicUserId,
            'request_id' => $requestId,
            'feedback_type' => $type,
            'message' => $message,
        ]);
    }

    public function forPublicUser(int $publicUserId, int $limit = 10): array
    {
        $limit = max(1, $limit);

        return $this->query(
            "SELECT feedback_id, request_id, feedback_type, message,
                    feedback_status, officer_response, submitted_at
             FROM complaint_feedback
             WHERE public_user_id = :public_user_id
             ORDER BY submitted_at DESC, feedback_id DESC
             LIMIT {$limit}",
            ['public_user_id' => $publicUserId]
        )->fetchAll();
    }
}