<?php
declare(strict_types=1);

final class OtpRateLimitException extends RuntimeException
{
    public function __construct(
        string $message,
        private readonly int $retryAfterSeconds
    ) {
        parent::__construct($message);
    }

    public function retryAfterSeconds(): int
    {
        return $this->retryAfterSeconds;
    }
}
