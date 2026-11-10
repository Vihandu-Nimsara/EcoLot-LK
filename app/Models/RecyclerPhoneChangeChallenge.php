<?php
declare(strict_types=1);

final class RecyclerPhoneChangeChallenge extends Model
{
    protected string $table = 'recycler_phone_change_challenges';
    protected string $primaryKey = 'challenge_id';
    private int $recyclerUserId;

    public function __construct(?PDO $connection = null)
    {
        if (Auth::role() !== 'RECYCLER' || (int) Auth::id() < 1) {
            throw new LogicException('An authenticated recycler is required.');
        }

        $this->recyclerUserId = (int) Auth::id();
        parent::__construct($connection);
    }

    /** Unresolved includes expired challenges until explicitly invalidated. */
    public function findActive(): ?array
    {
        return $this->activeChallenge(false);
    }

    /** Service must lock the users row first, including when no challenge exists. */
    public function lockActive(): ?array
    {
        $this->requireTransaction();
        return $this->activeChallenge(true);
    }

    /** Hashing and password confirmation belong to the Service, before insertion. */
    public function createChallenge(
        string $originalMobileNumber,
        string $proposedMobileNumber,
        string $otpHash,
        string $sessionContextHash,
        string $passwordStateFingerprint,
        string $expiresAt
    ): int {
        $this->requireTransaction();
        $this->requireNormalizedNumber($originalMobileNumber);
        $this->requireNormalizedNumber($proposedMobileNumber);

        if ($originalMobileNumber === $proposedMobileNumber) {
            throw new InvalidArgumentException('The proposed mobile number must differ.');
        }
        if ((password_get_info($otpHash)['algoName'] ?? 'unknown') === 'unknown') {
            throw new InvalidArgumentException('A supported password_hash OTP hash is required.');
        }
        foreach ([$sessionContextHash, $passwordStateFingerprint] as $hash) {
            if (preg_match('/\A[0-9a-f]{64}\z/', $hash) !== 1) {
                throw new InvalidArgumentException('A SHA-256 hexadecimal binding is required.');
            }
        }

        return parent::create([
            'recycler_user_id' => $this->owner(),
            'original_mobile_number' => $originalMobileNumber,
            'proposed_mobile_number' => $proposedMobileNumber,
            'otp_hash' => $otpHash,
            'session_context_hash' => $sessionContextHash,
            'password_state_fingerprint' => $passwordStateFingerprint,
            'expires_at' => $expiresAt,
        ]);
    }

    /** Includes failed, invalidated, expired and verified rows; never counts only SENT. */
    public function countRecentForAccount(string $since): int
    {
        return (int) $this->query(
            'SELECT COUNT(*) FROM recycler_phone_change_challenges
             WHERE recycler_user_id = :owner AND created_at >= :since',
            ['owner' => $this->owner(), 'since' => $since]
        )->fetchColumn();
    }

    /** Global aggregate for destination abuse limits; exposes no other account rows. */
    public function countRecentForDestination(string $proposedMobileNumber, string $since): int
    {
        $this->owner();
        $this->requireNormalizedNumber($proposedMobileNumber);

        return (int) $this->query(
            'SELECT COUNT(*) FROM recycler_phone_change_challenges
             WHERE proposed_mobile_number = :mobile AND created_at >= :since',
            ['mobile' => $proposedMobileNumber, 'since' => $since]
        )->fetchColumn();
    }

    /** Cooldown uses reservations, including cancelled or failed deliveries. */
    public function latestAttemptCreatedAt(): ?string
    {
        $value = $this->query(
            'SELECT MAX(created_at) FROM recycler_phone_change_challenges
             WHERE recycler_user_id = :owner',
            ['owner' => $this->owner()]
        )->fetchColumn();

        return $value === false || $value === null ? null : (string) $value;
    }

    /** Late acceptance records delivery without reviving a cancelled challenge. */
    public function markSmsAccepted(int $challengeId): bool
    {
        return $this->transition(
            "UPDATE recycler_phone_change_challenges
             SET delivery_status = 'SENT', sent_at = CURRENT_TIMESTAMP
             WHERE challenge_id = :challenge AND recycler_user_id = :owner
               AND delivery_status = 'PENDING' AND verified_at IS NULL",
            $challengeId
        );
    }

    /** Preserve any earlier cancellation timestamp, even for late gateway failures. */
    public function recordDeliveryFailure(int $challengeId): bool
    {
        return $this->transition(
            "UPDATE recycler_phone_change_challenges
             SET delivery_status = 'FAILED',
                 invalidated_at = COALESCE(invalidated_at, CURRENT_TIMESTAMP)
             WHERE challenge_id = :challenge AND recycler_user_id = :owner
               AND delivery_status = 'PENDING' AND verified_at IS NULL",
            $challengeId
        );
    }

    /** Service must invalidate on the fifth failure and commit failed attempts. */
    public function incrementFailedAttempts(int $challengeId): bool
    {
        return $this->transition(
            "UPDATE recycler_phone_change_challenges
             SET attempt_count = attempt_count + 1
             WHERE challenge_id = :challenge AND recycler_user_id = :owner
               AND verified_at IS NULL AND invalidated_at IS NULL
               AND delivery_status = 'SENT' AND expires_at > CURRENT_TIMESTAMP
               AND attempt_count < 5",
            $challengeId
        );
    }

    /** Cancel or replace only the specified owned challenge; retain its history. */
    public function invalidate(int $challengeId): bool
    {
        return $this->transition(
            'UPDATE recycler_phone_change_challenges
             SET invalidated_at = CURRENT_TIMESTAMP
             WHERE challenge_id = :challenge AND recycler_user_id = :owner
               AND verified_at IS NULL AND invalidated_at IS NULL',
            $challengeId
        );
    }

    public function invalidateExpired(): int
    {
        $this->requireTransaction();
        return $this->query(
            'UPDATE recycler_phone_change_challenges
             SET invalidated_at = CURRENT_TIMESTAMP
             WHERE recycler_user_id = :owner AND expires_at <= CURRENT_TIMESTAMP
               AND verified_at IS NULL AND invalidated_at IS NULL',
            ['owner' => $this->owner()]
        )->rowCount();
    }

    /** Service verifies OTP/bindings first; consume alongside the users update. */
    public function markVerified(int $challengeId): bool
    {
        return $this->transition(
            "UPDATE recycler_phone_change_challenges
             SET verified_at = CURRENT_TIMESTAMP
             WHERE challenge_id = :challenge AND recycler_user_id = :owner
               AND verified_at IS NULL AND invalidated_at IS NULL
               AND delivery_status = 'SENT' AND expires_at > CURRENT_TIMESTAMP
               AND attempt_count < 5",
            $challengeId
        );
    }

    // Disable inherited unscoped access and generic writes, including deletion.
    public function all(): array
    {
        throw new LogicException('Use the recycler-scoped challenge methods.');
    }

    public function find(int|string $id): ?array
    {
        throw new LogicException('Use findActive() or lockActive().');
    }

    public function create(array $attributes): int
    {
        throw new LogicException('Use createChallenge().');
    }

    public function update(int|string $id, array $attributes): bool
    {
        throw new LogicException('Use the guarded challenge transitions.');
    }

    public function delete(int|string $id): bool
    {
        throw new LogicException('Challenge history must be retained.');
    }

    private function activeChallenge(bool $lock): ?array
    {
        $row = $this->query(
            'SELECT challenge_id, recycler_user_id, original_mobile_number,
                    proposed_mobile_number, otp_hash, session_context_hash,
                    password_state_fingerprint, attempt_count, created_at,
                    expires_at, delivery_status, sent_at, verified_at, invalidated_at
             FROM recycler_phone_change_challenges
             WHERE active_recycler_user_id = :owner
             LIMIT 1' . ($lock ? ' FOR UPDATE' : ''),
            ['owner' => $this->owner()]
        )->fetch();

        return $row === false ? null : $row;
    }

    private function transition(string $sql, int $challengeId): bool
    {
        $this->requireTransaction();
        return $this->query($sql, [
            'challenge' => $challengeId,
            'owner' => $this->owner(),
        ])->rowCount() === 1;
    }

    private function owner(): int
    {
        if (Auth::role() !== 'RECYCLER' || (int) Auth::id() !== $this->recyclerUserId) {
            throw new LogicException('The authenticated recycler context has changed.');
        }

        return $this->recyclerUserId;
    }

    private function requireTransaction(): void
    {
        if (!$this->db->inTransaction()) {
            throw new LogicException('Challenge updates require a Service-owned transaction.');
        }
    }

    private function requireNormalizedNumber(string $mobileNumber): void
    {
        if (MobileNumber::normalize($mobileNumber) !== $mobileNumber) {
            throw new InvalidArgumentException('A normalized mobile number is required.');
        }
    }
}
