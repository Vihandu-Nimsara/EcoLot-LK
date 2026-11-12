<?php
declare(strict_types=1);

final class RecyclerPhoneChangeService
{
    private const SESSION_KEY = 'recycler_phone_change_context';
    private const COOLDOWN_SECONDS = 60;
    private const HOURLY_LIMIT = 5;
    private const DAILY_LIMIT = 10;
    private PDO $connection;
    private Closure $smsSender;
    private LoginRateLimiter $passwordLimiter;
    private bool $connectionUsable = true;

    /** Injected connections must use the application's Asia/Colombo DB timezone. */
    public function __construct(
        ?PDO $connection = null,
        ?callable $smsSender = null,
        ?LoginRateLimiter $passwordLimiter = null
    ) {
        $this->connection = $connection ?? Database::connection();
        $this->smsSender = Closure::fromCallable($smsSender ?? static function (string $mobile, string $otp): void {
            // Existing method sends the generic EcoLot verification message.
            (new SmsGatewayService())->sendRegistrationOtp($mobile, $otp);
        });
        $this->passwordLimiter = $passwordLimiter ?? new LoginRateLimiter();
    }

    /** Caller supplies Auth::id(), never a submitted account ID. No OTP is returned. */
    public function startChange(int $recyclerUserId, string $currentPassword, string $proposedMobile): array
    {
        $this->requireUsableConnection();
        if ($recyclerUserId < 1 || Auth::role() !== 'RECYCLER' || (int) Auth::id() !== $recyclerUserId) {
            throw new DomainException('An authenticated recycler is required.');
        }
        if ($this->connection->inTransaction()) {
            throw new LogicException('Phone change requests must own their transaction.');
        }

        $retryAfter = $this->passwordLimiter->consume(
            'phone-change:' . (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'),
            'phone-change:recycler:' . $recyclerUserId
        );
        if ($retryAfter > 0) {
            throw new OtpRateLimitException('Too many password confirmations. Please try again later.', $retryAfter);
        }

        $users = new User($this->connection);
        $user = $users->find($recyclerUserId);
        $this->requireActiveRecycler($user);
        if (!password_verify($currentPassword, (string) $user['password_hash'])) {
            throw new DomainException('Your current password is incorrect.');
        }

        $mobile = MobileNumber::normalize($proposedMobile);
        if ($mobile === null) {
            throw new DomainException('Enter a valid Sri Lankan mobile number.');
        }
        if ($mobile === MobileNumber::normalize((string) $user['mobile_number'])) {
            throw new DomainException('Enter a different mobile number.');
        }

        $challenges = new RecyclerPhoneChangeChallenge($this->connection);
        $lockName = $this->destinationLockName($mobile);
        $lock = $this->connection->prepare('SELECT GET_LOCK(:name, 5)');
        $lock->execute(['name' => $lockName]);
        if ((int) $lock->fetchColumn() !== 1) {
            throw new OtpRateLimitException('Another phone change request is in progress. Please try again.', 5);
        }

        try {
            $this->connection->beginTransaction();
            try {
                $users->lockForVerification($recyclerUserId);
                $lockedUser = $users->find($recyclerUserId);
                $this->requireActiveRecycler($lockedUser);
                if (!hash_equals((string) $user['password_hash'], (string) $lockedUser['password_hash'])
                    || $user['mobile_number'] !== $lockedUser['mobile_number']) {
                    throw new DomainException('Your account changed. Please start again.');
                }
                $originalMobile = MobileNumber::normalize((string) $lockedUser['mobile_number']);
                if ($originalMobile === null) {
                    throw new DomainException('Your recorded mobile number needs Administrator review.');
                }
                if ($users->findByMobileNumber($mobile) !== null) {
                    throw new DomainException('That mobile number is unavailable.');
                }

                $now = new DateTimeImmutable(
                    (string) $this->connection->query('SELECT CURRENT_TIMESTAMP')->fetchColumn(),
                    new DateTimeZone('Asia/Colombo')
                );
                $this->requireSendAllowed($challenges, $mobile, $now);
                $active = $challenges->lockActive();
                if ($active !== null && !$challenges->invalidate((int) $active['challenge_id'])) {
                    throw new RuntimeException('The previous phone change request could not be replaced.');
                }

                $otp = (string) random_int(100000, 999999);
                $context = bin2hex(random_bytes(32));
                $expiresAt = $now->modify('+5 minutes')->format('Y-m-d H:i:s');
                $challengeId = $challenges->createChallenge(
                    $originalMobile,
                    $mobile,
                    password_hash($otp, PASSWORD_DEFAULT),
                    hash('sha256', $context),
                    hash('sha256', (string) $lockedUser['password_hash']),
                    $expiresAt
                );
                $this->connection->commit();
            } catch (Throwable $error) {
                if ($this->connection->inTransaction()) {
                    $this->connection->rollBack();
                }
                throw $error;
            }
        } finally {
            $release = $this->connection->prepare('SELECT RELEASE_LOCK(:name)');
            $release->execute(['name' => $lockName]);
        }

        return $this->deliverChallenge($challenges, $recyclerUserId, $challengeId, $mobile, $otp, $context, $expiresAt);
    }

    private function deliverChallenge(
        RecyclerPhoneChangeChallenge $challenges,
        int $recyclerUserId,
        int $challengeId,
        string $mobile,
        string $otp,
        string $context,
        string $expiresAt
    ): array {
        // The reservation is committed and all locks are released before network IO.
        try {
            Session::put(self::SESSION_KEY, [
                'recycler_user_id' => $recyclerUserId,
                'challenge_id' => $challengeId,
                'token' => $context,
            ]);
            ($this->smsSender)($mobile, $otp);
        } catch (Throwable) {
            try {
                $this->recordDeliveryOutcome($challenges, $recyclerUserId, $challengeId, false);
            } finally {
                $pending = Session::get(self::SESSION_KEY);
                if (is_array($pending) && ($pending['challenge_id'] ?? null) === $challengeId) {
                    Session::forget(self::SESSION_KEY);
                }
            }
            // Do not propagate gateway exceptions that might contain OTPs or credentials.
            throw new RuntimeException('The verification SMS could not be sent. Please try again.');
        } finally {
            unset($otp, $context);
        }

        $this->recordDeliveryOutcome($challenges, $recyclerUserId, $challengeId, true);
        return [
            'challenge_id' => $challengeId,
            'masked_mobile' => substr($mobile, 0, 4) . '****' . substr($mobile, -3),
            'expires_at' => $expiresAt,
        ];
    }

    /** Only safe display metadata leaves the Service; the context token stays in Session. */
    public function pendingChange(int $recyclerUserId): ?array
    {
        $this->requireCaller($recyclerUserId);
        if (!is_array(Session::get(self::SESSION_KEY))) return null;
        $row = (new RecyclerPhoneChangeChallenge($this->connection))->findActive();
        try {
            $this->requireBoundChallenge($row, $recyclerUserId);
        } catch (DomainException) {
            return null;
        }
        $now = $this->databaseNow();
        $created = new DateTimeImmutable($row['created_at'], $now->getTimezone());
        return [
            'masked_mobile' => substr($row['proposed_mobile_number'], 0, 4) . '****' . substr($row['proposed_mobile_number'], -3),
            'expires_at' => $row['expires_at'],
            'retry_after' => max(0, self::COOLDOWN_SECONDS - ($now->getTimestamp() - $created->getTimestamp())),
            'can_verify' => $row['delivery_status'] === 'SENT' && $row['expires_at'] > $now->format('Y-m-d H:i:s') && (int) $row['attempt_count'] < 5,
        ];
    }

    public function verifyChange(int $recyclerUserId, string $otp): void
    {
        $this->requireCaller($recyclerUserId);
        $challenges = new RecyclerPhoneChangeChallenge($this->connection);
        $hint = $challenges->findActive();
        $this->requireBoundChallenge($hint, $recyclerUserId);
        $connectionId = (int) $this->connection->query('SELECT CONNECTION_ID()')->fetchColumn();
        $lockName = $this->acquireDestinationLock($hint['proposed_mobile_number']);
        $error = null;
        $commitAttempted = false;
        $committed = false;
        $clearContext = false;
        try {
            $this->connection->beginTransaction();
            try {
                $users = new User($this->connection);
                $users->lockForVerification($recyclerUserId);
                $user = $users->find($recyclerUserId);
                $this->requireActiveRecycler($user);
                $row = $challenges->lockActive();
                $this->requireBoundChallenge($row, $recyclerUserId);
                if ($row['proposed_mobile_number'] !== $hint['proposed_mobile_number']) {
                    throw new DomainException('Your phone change request changed. Please start again.');
                }
                if (!$this->matchesAccountState($row, $user)) {
                    $challenges->invalidate((int) $row['challenge_id']);
                    $clearContext = true;
                    $error = 'Your account changed. Please start again.';
                } elseif ($row['expires_at'] <= $this->databaseNow()->format('Y-m-d H:i:s') || (int) $row['attempt_count'] >= 5) {
                    $challenges->invalidate((int) $row['challenge_id']);
                    $clearContext = true;
                    $error = 'That code has expired or is locked. Please start again.';
                } elseif ($row['delivery_status'] !== 'SENT') {
                    $error = 'SMS delivery has not been confirmed. Please try again later.';
                } elseif (preg_match('/\A[0-9]{6}\z/', $otp) !== 1 || !password_verify($otp, $row['otp_hash'])) {
                    if (!$challenges->incrementFailedAttempts((int) $row['challenge_id'])) {
                        throw new RuntimeException('The failed attempt could not be recorded.');
                    }
                    $remaining = 4 - (int) $row['attempt_count'];
                    if ($remaining === 0) {
                        $challenges->invalidate((int) $row['challenge_id']);
                        $clearContext = true;
                    }
                    $error = $remaining > 0 ? 'Incorrect code. Attempts remaining: ' . $remaining . '.' : 'Too many incorrect codes. Please start again.';
                } elseif ($users->findByMobileNumber($row['proposed_mobile_number']) !== null) {
                    $challenges->invalidate((int) $row['challenge_id']);
                    $clearContext = true;
                    $error = 'That mobile number is unavailable. Please start again.';
                } else {
                    if (!$users->replaceRecyclerMobile($recyclerUserId, $user['mobile_number'], $row['proposed_mobile_number'])
                        || !$challenges->markVerified((int) $row['challenge_id'])) {
                        throw new RuntimeException('The phone change could not be completed.');
                    }
                }
                // Rejected guesses must commit their counters and any invalidation.
                $commitAttempted = true;
                if (!$this->connection->commit()) throw new RuntimeException('Commit was not acknowledged.');
                $committed = true;
            } catch (Throwable $failure) {
                try {
                    if ($this->connection->inTransaction() && !$this->connection->rollBack()) {
                        throw new RuntimeException('Rollback was not acknowledged.');
                    }
                } catch (Throwable) {
                    $this->discardVerificationConnection($connectionId);
                }
                if ($commitAttempted && !$committed && $error === null) {
                    Session::forget(self::SESSION_KEY);
                    throw new PhoneChangeOutcomeUnknownException('The phone change outcome could not be confirmed.');
                }
                if ($failure instanceof PDOException && (int) ($failure->errorInfo[1] ?? 0) === 1062) {
                    throw new DomainException('That mobile number is unavailable. Please try again.');
                }
                throw $failure;
            }
        } finally {
            // Cleanup cannot turn a confirmed commit into a failed verification,
            // nor replace the original transaction exception during rollback.
            try {
                $this->releaseDestinationLock($lockName);
            } catch (Throwable) {
                $this->discardVerificationConnection($connectionId);
            }
        }
        if ($error !== null) {
            if ($clearContext) Session::forget(self::SESSION_KEY);
            throw new DomainException($error);
        }
        Session::forget(self::SESSION_KEY);
    }

    /** No destination argument: resend can only use the already bound request. */
    public function resend(int $recyclerUserId): array
    {
        $this->requireCaller($recyclerUserId);
        $challenges = new RecyclerPhoneChangeChallenge($this->connection);
        $hint = $challenges->findActive();
        $this->requireBoundChallenge($hint, $recyclerUserId);
        $lockName = $this->acquireDestinationLock($hint['proposed_mobile_number']);
        try {
            $this->connection->beginTransaction();
            try {
                $users = new User($this->connection);
                $users->lockForVerification($recyclerUserId);
                $user = $users->find($recyclerUserId);
                $this->requireActiveRecycler($user);
                $row = $challenges->lockActive();
                $this->requireBoundChallenge($row, $recyclerUserId);
                if ($row['proposed_mobile_number'] !== $hint['proposed_mobile_number'] || !$this->matchesAccountState($row, $user)) {
                    throw new DomainException('Your account changed. Cancel this request and start again.');
                }
                if ($users->findByMobileNumber($row['proposed_mobile_number']) !== null) {
                    throw new DomainException('That mobile number is unavailable. Cancel this request and start again.');
                }
                $now = $this->databaseNow();
                $this->requireSendAllowed($challenges, $row['proposed_mobile_number'], $now);
                do {
                    $otp = (string) random_int(100000, 999999);
                } while (password_verify($otp, $row['otp_hash']));
                $context = bin2hex(random_bytes(32));
                $expiresAt = $now->modify('+5 minutes')->format('Y-m-d H:i:s');
                if (!$challenges->invalidate((int) $row['challenge_id'])) throw new RuntimeException('Unable to replace challenge.');
                $challengeId = $challenges->createChallenge($row['original_mobile_number'], $row['proposed_mobile_number'],
                    password_hash($otp, PASSWORD_DEFAULT), hash('sha256', $context), $row['password_state_fingerprint'], $expiresAt);
                $this->connection->commit();
            } catch (Throwable $failure) {
                if ($this->connection->inTransaction()) $this->connection->rollBack();
                throw $failure;
            }
        } finally {
            $this->releaseDestinationLock($lockName);
        }
        return $this->deliverChallenge($challenges, $recyclerUserId, $challengeId, $row['proposed_mobile_number'], $otp, $context, $expiresAt);
    }

    public function cancel(int $recyclerUserId): void
    {
        $this->requireCaller($recyclerUserId);
        $challenges = new RecyclerPhoneChangeChallenge($this->connection);
        $this->connection->beginTransaction();
        try {
            (new User($this->connection))->lockForVerification($recyclerUserId);
            $row = $challenges->lockActive();
            $this->requireBoundChallenge($row, $recyclerUserId);
            if (!$challenges->invalidate((int) $row['challenge_id'])) throw new RuntimeException('Unable to cancel challenge.');
            $this->connection->commit();
        } catch (Throwable $failure) {
            if ($this->connection->inTransaction()) $this->connection->rollBack();
            throw $failure;
        }
        Session::forget(self::SESSION_KEY);
    }

    private function requireCaller(int $recyclerUserId): void
    {
        $this->requireUsableConnection();
        if ($recyclerUserId < 1 || Auth::role() !== 'RECYCLER' || (int) Auth::id() !== $recyclerUserId) {
            throw new DomainException('An authenticated recycler is required.');
        }
        if ($this->connection->inTransaction()) throw new LogicException('Phone changes must own their transaction.');
    }

    private function requireBoundChallenge(?array $row, int $recyclerUserId): void
    {
        $context = Session::get(self::SESSION_KEY);
        if ($row === null || !is_array($context) || ($context['recycler_user_id'] ?? null) !== $recyclerUserId
            || ($context['challenge_id'] ?? null) !== (int) $row['challenge_id']
            || !is_string($context['token'] ?? null)
            || !hash_equals($row['session_context_hash'], hash('sha256', $context['token']))) {
            throw new DomainException('No matching phone change request was found. Please start again.');
        }
    }

    private function matchesAccountState(array $row, array $user): bool
    {
        return MobileNumber::normalize($user['mobile_number']) === $row['original_mobile_number']
            && MobileNumber::normalize($row['proposed_mobile_number']) === $row['proposed_mobile_number']
            && $row['proposed_mobile_number'] !== $row['original_mobile_number']
            && hash_equals($row['password_state_fingerprint'], hash('sha256', $user['password_hash']));
    }

    private function databaseNow(): DateTimeImmutable
    {
        return new DateTimeImmutable((string) $this->connection->query('SELECT CURRENT_TIMESTAMP')->fetchColumn(), new DateTimeZone('Asia/Colombo'));
    }

    private function acquireDestinationLock(string $mobile): string
    {
        $name = $this->destinationLockName($mobile);
        $statement = $this->connection->prepare('SELECT GET_LOCK(:name, 5)');
        $statement->execute(['name' => $name]);
        if ((int) $statement->fetchColumn() !== 1) throw new OtpRateLimitException('Another phone change is in progress. Please try again.', 5);
        return $name;
    }

    private function releaseDestinationLock(string $name): void
    {
        $statement = $this->connection->prepare('SELECT RELEASE_LOCK(:name)');
        $statement->execute(['name' => $name]);
        if ((int) $statement->fetchColumn() !== 1) throw new RuntimeException('Destination lock release was not acknowledged.');
    }

    private function requireUsableConnection(): void
    {
        if (!$this->connectionUsable) throw new LogicException('This phone change connection has been retired.');
    }

    private function discardVerificationConnection(int $connectionId): void
    {
        if (!$this->connectionUsable) return;
        $this->connectionUsable = false;
        error_log('Recycler phone verification cleanup failed; retiring database connection.');
        try {
            // PDO has no close() API and other objects may retain this connection.
            // Killing our own server session releases named locks and rolls back
            // any uncommitted work, even when other PHP references still exist.
            $this->connection->exec('KILL CONNECTION ' . $connectionId);
        } catch (Throwable) {
            // Self-KILL normally raises a lost-connection error. If the transport
            // was already lost, do not reuse it or mask the transaction outcome.
            error_log('Recycler phone verification connection termination was not acknowledged; server disconnect cleanup may be pending.');
        }
    }

    private function requireActiveRecycler(?array $user): void
    {
        if ($user === null || $user['role'] !== 'RECYCLER' || $user['account_status'] !== 'ACTIVE') {
            throw new DomainException('This Recycler account is not active.');
        }
        $statement = $this->connection->prepare('SELECT verification_status FROM authorized_recyclers WHERE user_id = ?'
            . ($this->connection->inTransaction() ? ' FOR UPDATE' : ''));
        $statement->execute([$user['user_id']]);
        if ($statement->fetchColumn() !== 'VERIFIED') {
            throw new DomainException('Phone changes require a verified Recycler account.');
        }
    }

    /** Named lock covers quota reads + reservation across accounts for one destination. */
    private function destinationLockName(string $mobile): string
    {
        $database = (string) $this->connection->query('SELECT DATABASE()')->fetchColumn();
        return 'ecolot:phone:' . substr(hash('sha256', $database . ':' . $mobile), 0, 48);
    }

    private function requireSendAllowed(
        RecyclerPhoneChangeChallenge $challenges,
        string $mobile,
        DateTimeImmutable $now
    ): void {
        $latest = $challenges->latestAttemptCreatedAt();
        if ($latest !== null) {
            $age = $now->getTimestamp() - (new DateTimeImmutable($latest, $now->getTimezone()))->getTimestamp();
            if ($age < self::COOLDOWN_SECONDS) {
                throw new OtpRateLimitException('Please wait before requesting another code.', self::COOLDOWN_SECONDS - max(0, $age));
            }
        }

        foreach ([['-1 hour', self::HOURLY_LIMIT, 3600], ['-1 day', self::DAILY_LIMIT, 86400]] as [$window, $limit, $retryAfter]) {
            $since = $now->modify($window)->format('Y-m-d H:i:s');
            if ($challenges->countRecentForAccount($since) >= $limit
                || $challenges->countRecentForDestination($mobile, $since) >= $limit) {
                throw new OtpRateLimitException('Too many verification codes were requested. Please try again later.', $retryAfter);
            }
        }
    }

    /** A separate short transaction records the external gateway outcome. */
    private function recordDeliveryOutcome(
        RecyclerPhoneChangeChallenge $challenges,
        int $recyclerUserId,
        int $challengeId,
        bool $accepted
    ): void {
        try {
            $this->connection->beginTransaction();
            (new User($this->connection))->lockForVerification($recyclerUserId);
            $changed = $accepted
                ? $challenges->markSmsAccepted($challengeId)
                : $challenges->recordDeliveryFailure($challengeId);
            if (!$changed) {
                throw new RuntimeException('The delivery state could not be recorded.');
            }
            $this->connection->commit();
        } catch (Throwable) {
            if ($this->connection->inTransaction()) {
                $this->connection->rollBack();
            }
            throw new RuntimeException('SMS delivery status could not be confirmed. Please try again later.');
        }
    }
}
