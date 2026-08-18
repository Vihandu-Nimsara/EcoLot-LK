<?php
declare(strict_types=1);

final class SmsGatewayService
{
    private array $config;

    public function __construct(?array $config = null)
    {
        $rootPath = defined('APP_ROOT') ? APP_ROOT : dirname(__DIR__, 2);
        $this->config = $config ?? require $rootPath . '/config/sms.php';
    }

    public function sendRegistrationOtp(string $mobileNumber, string $otp): void
    {
        if (preg_match('/^947\d{8}$/', $mobileNumber) !== 1) {
            throw new InvalidArgumentException('Invalid Notify.lk recipient number.');
        }

        if (preg_match('/^\d{6}$/', $otp) !== 1) {
            throw new InvalidArgumentException('Invalid registration OTP.');
        }

        $this->assertConfigured();

        if (!function_exists('curl_init')) {
            throw new SmsGatewayException(
                'The PHP cURL extension is required to send SMS messages.'
            );
        }

        $message = sprintf(
            'Your EcoLot LK verification code is %s. It expires in 5 minutes.',
            $otp
        );
        $handle = curl_init((string) $this->config['endpoint']);

        if ($handle === false) {
            throw new SmsGatewayException(
                'The SMS gateway could not be initialized.'
            );
        }

        curl_setopt_array($handle, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'user_id' => $this->config['user_id'],
                'api_key' => $this->config['api_key'],
                'sender_id' => $this->config['sender_id'],
                'to' => $mobileNumber,
                'message' => $message,
            ], '', '&', PHP_QUERY_RFC3986),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => (int) (
                $this->config['connect_timeout_seconds'] ?? 5
            ),
            CURLOPT_TIMEOUT => (int) (
                $this->config['timeout_seconds'] ?? 12
            ),
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Content-Type: application/x-www-form-urlencoded',
            ],
        ]);

        try {
            $responseBody = curl_exec($handle);
            $httpStatus = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        } finally {
            curl_close($handle);
        }

        if ($responseBody === false) {
            throw new SmsGatewayException('The SMS gateway could not be reached.');
        }

        $response = json_decode($responseBody, true);
        $wasAccepted = $httpStatus >= 200
            && $httpStatus < 300
            && is_array($response)
            && ($response['status'] ?? null) === 'success';

        if (!$wasAccepted) {
            throw new SmsGatewayException('Notify.lk did not accept the SMS request.');
        }
    }

    private function assertConfigured(): void
    {
        foreach (['endpoint', 'user_id', 'api_key', 'sender_id'] as $key) {
            if (
                !isset($this->config[$key])
                || trim((string) $this->config[$key]) === ''
            ) {
                throw new SmsGatewayException(
                    'Notify.lk is not configured. Set the required environment variables.'
                );
            }
        }
    }
}
