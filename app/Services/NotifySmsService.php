<?php
declare(strict_types=1);

final class NotifySmsService
{
    private const SUCCESS_STATUS = 'success';
    private const SUCCESS_DATA = 'Sent';

    private array $config;

    public function __construct(?array $config = null)
    {
        $rootPath = defined('APP_ROOT')
            ? APP_ROOT
            : dirname(__DIR__, 2);

        $this->config = $config ?? require $rootPath . '/config/notify.php';
    }

    public function sendRegistrationOtp(
        string $mobileNumber,
        string $otp
    ): array {
        if (preg_match('/^947\d{8}$/', $mobileNumber) !== 1) {
            throw new InvalidArgumentException(
                'Notify.lk requires a normalized Sri Lankan mobile number.'
            );
        }

        if (preg_match('/^\d{6}$/', $otp) !== 1) {
            throw new InvalidArgumentException(
                'The registration OTP must contain exactly six digits.'
            );
        }

        $reference = bin2hex(random_bytes(8));

        if (!$this->hasValidConfiguration()) {
            $this->logFailure(
                $reference,
                $mobileNumber,
                'configuration'
            );

            return $this->failure($reference, 'configuration');
        }

        $senderId = (string) $this->config['sender_id'];

        if (strcasecmp($senderId, 'NotifyDEMO') === 0) {
            $this->logFailure(
                $reference,
                $mobileNumber,
                'demo_sender_not_allowed'
            );

            return $this->failure(
                $reference,
                'demo_sender_not_allowed'
            );
        }

        $message = sprintf(
            'Your EcoLot-LK verification code is %s. '
            . 'It expires in 5 minutes. Do not share this code.',
            $otp
        );

        $curl = curl_init();

        if ($curl === false) {
            $this->logFailure(
                $reference,
                $mobileNumber,
                'client_initialization'
            );

            return $this->failure($reference, 'client_initialization');
        }

        $payload = http_build_query(
            [
                'user_id' => (string) $this->config['user_id'],
                'api_key' => (string) $this->config['api_key'],
                'sender_id' => $senderId,
                'to' => $mobileNumber,
                'message' => $message,
            ],
            '',
            '&',
            PHP_QUERY_RFC3986
        );

        curl_setopt_array($curl, [
            CURLOPT_URL => (string) $this->config['endpoint'],
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => (int) (
                $this->config['connect_timeout_seconds'] ?? 5
            ),
            CURLOPT_TIMEOUT => (int) (
                $this->config['request_timeout_seconds'] ?? 10
            ),
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Content-Type: application/x-www-form-urlencoded',
            ],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        $responseBody = curl_exec($curl);
        $curlErrorNumber = curl_errno($curl);
        $httpStatus = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        curl_close($curl);

        if ($responseBody === false || $curlErrorNumber !== 0) {
            $this->logFailure(
                $reference,
                $mobileNumber,
                'network',
                $httpStatus,
                $curlErrorNumber
            );

            return $this->failure($reference, 'network');
        }

        try {
            $response = json_decode(
                (string) $responseBody,
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (JsonException) {
            $this->logFailure(
                $reference,
                $mobileNumber,
                'invalid_json',
                $httpStatus
            );

            return $this->failure($reference, 'invalid_json');
        }

        $confirmedSuccess = $httpStatus >= 200
            && $httpStatus < 300
            && is_array($response)
            && ($response['status'] ?? null) === self::SUCCESS_STATUS
            && ($response['data'] ?? null) === self::SUCCESS_DATA;

        if (!$confirmedSuccess) {
            $this->logFailure(
                $reference,
                $mobileNumber,
                'provider_rejected',
                $httpStatus
            );

            return $this->failure($reference, 'provider_rejected');
        }

        return [
            'success' => true,
            'reference' => $reference,
            'category' => null,
        ];
    }

    private function hasValidConfiguration(): bool
    {
        foreach (['endpoint', 'user_id', 'api_key', 'sender_id'] as $key) {
            if (
                !isset($this->config[$key])
                || !is_string($this->config[$key])
                || trim($this->config[$key]) === ''
            ) {
                return false;
            }
        }

        return str_starts_with(
            (string) $this->config['endpoint'],
            'https://'
        );
    }

    private function failure(string $reference, string $category): array
    {
        return [
            'success' => false,
            'reference' => $reference,
            'category' => $category,
        ];
    }

    private function logFailure(
        string $reference,
        string $mobileNumber,
        string $category,
        int $httpStatus = 0,
        int $curlErrorNumber = 0
    ): void {
        error_log(sprintf(
            'Notify SMS failure [%s] mobile=%s category=%s http_status=%d curl_errno=%d',
            $reference,
            $this->maskMobileNumber($mobileNumber),
            $category,
            $httpStatus,
            $curlErrorNumber
        ));
    }

    private function maskMobileNumber(string $mobileNumber): string
    {
        return substr($mobileNumber, 0, 4)
            . '*****'
            . substr($mobileNumber, -2);
    }
}
