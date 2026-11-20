<?php
declare(strict_types=1);

/** Read-only dashboard projections. Does not mutate users, reviews or catalogue data. */
final class AdminDashboardService
{
    private PDO $db;

    public function __construct(?PDO $connection = null)
    {
        $this->db = $connection ?? Database::connection();
    }

    /** Counts include inactive accounts/catalogue rows; recycler states use registration verification_status. */
    public function statistics(): array
    {
        $sql = <<<'SQL'
SELECT
  (SELECT COUNT(*) FROM users) AS total_users,
  (SELECT COUNT(*) FROM users WHERE role = 'ADMIN') AS admin_users,
  (SELECT COUNT(*) FROM users WHERE role = 'RECYCLER') AS recycler_users,
  (SELECT COUNT(*) FROM users WHERE role = 'PUBLIC_USER') AS public_users,
  (SELECT COUNT(*) FROM users WHERE role = 'MUNICIPAL_OFFICER') AS municipal_officers,
  (SELECT COUNT(*) FROM users WHERE role = 'COLLECTOR') AS collectors,
  (SELECT COUNT(*) FROM authorized_recyclers WHERE verification_status = 'PENDING') AS pending_recyclers,
  (SELECT COUNT(*) FROM authorized_recyclers WHERE verification_status = 'VERIFIED') AS verified_recyclers,
  (SELECT COUNT(*) FROM waste_categories) AS e_waste_categories,
  (SELECT COUNT(*) FROM e_waste_items) AS e_waste_items,
  (SELECT COUNT(*) FROM risk_rules) AS risk_rules
SQL;
        $row = $this->db->query($sql)->fetch(PDO::FETCH_ASSOC);
        if (!is_array($row)) {
            throw new RuntimeException('Dashboard statistics query returned no row.');
        }
        return array_map(static fn ($count): int => (int) $count, $row);
    }

    /** Most recent REAL events; never invent missing catalogue/risk-rule updates. */
    public function recentActivity(int $limit = 5): array
    {
        if ($limit < 1 || $limit > 25) {
            throw new InvalidArgumentException('Activity limit must be 1–25.');
        }
        // The integer is range-checked above. A bounded query per source avoids loading full histories.
        $audit = $this->db->query(<<<SQL
SELECT a.audit_id AS event_id, a.action_type, a.entity_type,
       a.created_at, COALESCE(u.full_name, 'System') AS subject
FROM audit_logs AS a
LEFT JOIN users AS u ON u.user_id = a.actor_user_id
ORDER BY a.created_at DESC, a.audit_id DESC
LIMIT {$limit}
SQL)->fetchAll(PDO::FETCH_ASSOC);

        $recyclerReviews = $this->db->query(<<<SQL
SELECT ar.user_id AS event_id, ar.company_name AS subject,
       ar.verification_status AS decision, ar.reviewed_at AS created_at
FROM authorized_recyclers AS ar
WHERE ar.reviewed_at IS NOT NULL
  AND ar.verification_status IN ('VERIFIED', 'REJECTED', 'SUSPENDED')
ORDER BY ar.reviewed_at DESC, ar.user_id DESC
LIMIT {$limit}
SQL)->fetchAll(PDO::FETCH_ASSOC);

        $createdUsers = $this->db->query(<<<SQL
SELECT user_id AS event_id, full_name AS subject, created_at
FROM users
WHERE created_by_admin_user_id IS NOT NULL
ORDER BY created_at DESC, user_id DESC
LIMIT {$limit}
SQL)->fetchAll(PDO::FETCH_ASSOC);

        $events = [];
        foreach ($audit as $row) {
            $action = (string) $row['action_type'];
            [$title, $group, $css, $icon] = self::auditPresentation($action);
            // Audit rows do not reliably contain a navigable target record.
            // Link only recognized Recycler actions to the existing review list.
            $href = str_starts_with($action, 'RECYCLER_')
                ? '/admin/recycler-verification'
                : null;
            $events[] = self::event($title, $group, $css, $icon, $row['created_at'], (int) $row['event_id'], 'audit', $href);
        }
        foreach ($recyclerReviews as $row) {
            $status = (string) $row['decision'];
            $decision = match ($status) {
                'VERIFIED' => 'verified',
                'REJECTED' => 'rejected',
                'SUSPENDED' => 'suspended',
                default => 'reviewed',
            };
            $css = $status === 'VERIFIED' ? 'activity-success' : 'activity-warning';
            $events[] = self::event(
                'Recycler “' . (string) $row['subject'] . '” ' . $decision,
                'Recycler Verification', $css, $status === 'VERIFIED' ? '✓' : '!',
                $row['created_at'], (int) $row['event_id'], 'review',
                '/admin/recycler-verification/' . (int) $row['event_id']
            );
        }
        foreach ($createdUsers as $row) {
            $events[] = self::event(
                'User account created: ' . (string) $row['subject'],
                'User Management', 'activity-info', '+',
                $row['created_at'], (int) $row['event_id'], 'account', '/admin/users'
            );
        }
        usort($events, static function (array $a, array $b): int {
            return strcmp($b['sort_time'], $a['sort_time'])
                ?: strcmp($a['source'], $b['source'])
                ?: ($b['sort_id'] <=> $a['sort_id']);
        });
        return array_slice($events, 0, $limit);
    }

    private static function event(string $title, string $group, string $css, string $icon, mixed $timestamp, int $id, string $source, ?string $href = null): array
    {
        $date = (string) ($timestamp ?? '');
        return [
            'title' => $title,
            'group' => $group,
            'class' => $css,
            'icon' => $icon,
            'time' => $date === '' ? '' : substr($date, 0, 16) . ' SLT',
            'sort_time' => $date,
            'sort_id' => $id,
            'source' => $source,
            'href' => $href,
        ];
    }

    private static function auditPresentation(string $action): array
    {
        return match ($action) {
            'RECYCLER_CONTACT_UPDATED' => ['Recycler contact person updated', 'Recycler Profile', 'activity-info', '↗'],
            'RECYCLER_PHONE_CHANGED' => ['Recycler mobile number changed', 'Recycler Profile', 'activity-info', '↗'],
            'RECYCLER_LICENCE_UPDATE_SUBMITTED' => ['Recycler licence update submitted', 'Licence Renewal', 'activity-info', '+'],
            'RECYCLER_LICENCE_UPDATE_APPROVE' => ['Recycler licence update approved', 'Licence Renewal', 'activity-success', '✓'],
            'RECYCLER_LICENCE_UPDATE_REJECT' => ['Recycler licence update rejected', 'Licence Renewal', 'activity-warning', '!'],
            'RECYCLER_CAPABILITY_CHANGE_SUBMITTED' => ['Recycler capability change requested', 'Capability Review', 'activity-info', '+'],
            'RECYCLER_CAPABILITY_CHANGE_APPROVE' => ['Recycler capability change approved', 'Capability Review', 'activity-success', '✓'],
            'RECYCLER_CAPABILITY_CHANGE_REJECT' => ['Recycler capability change rejected', 'Capability Review', 'activity-warning', '!'],
            default => [
                'Recorded action: ' . ucwords(strtolower(str_replace('_', ' ', preg_replace('/[^A-Za-z0-9_]/', '', $action)))),
                'System Audit', 'activity-info', '•',
            ],
        };
    }
}
