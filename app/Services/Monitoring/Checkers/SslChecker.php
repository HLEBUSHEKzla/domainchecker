<?php

namespace App\Services\Monitoring\Checkers;

use App\Models\Domain;
use Carbon\Carbon;

class SslChecker implements CheckerInterface
{
    public function getKey(): string
    {
        return 'ssl';
    }

    public function check(Domain $domain, array $context = []): array
    {
        $host = $domain->host;
        $timeoutSeconds = $domain->timeout_seconds;

        $streamContext = stream_context_create([
            'ssl' => [
                'capture_peer_cert' => true,
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ]);

        $socket = @stream_socket_client(
            "ssl://{$host}:443",
            $errno,
            $errstr,
            $timeoutSeconds,
            STREAM_CLIENT_CONNECT,
            $streamContext
        );

        if (!$socket) {
            return $this->buildErrorResponse("Connection failed: ({$errno}) {$errstr}");
        }

        $params = stream_context_get_params($socket);
        fclose($socket);

        if (!isset($params['options']['ssl']['peer_certificate'])) {
            return $this->buildErrorResponse('Could not retrieve SSL certificate.');
        }

        $cert = $params['options']['ssl']['peer_certificate'];
        $certInfo = openssl_x509_parse($cert);

        if ($certInfo === false) {
            return $this->buildErrorResponse('Failed to parse SSL certificate.');
        }

        $validTo = Carbon::createFromTimestamp($certInfo['validTo_time_t']);
        $isExpired = $validTo->isPast();
        $daysRemaining = (int) now()->diffInDays($validTo, false);
        $issuer = $certInfo['issuer']['CN'] ?? $certInfo['issuer']['O'] ?? 'Unknown Issuer';
        $hostnameMatch = $this->verifyHostname($host, $certInfo);
        $isValid = !$isExpired && $hostnameMatch;

        return [
            'ssl_valid' => $isValid,
            'ssl_expires_at' => $validTo->toIso8601String(),
            'ssl_days_remaining' => $daysRemaining,
            'ssl_error_message' => $this->getErrorMessage($isExpired, $hostnameMatch),
            'ssl_hostname_match' => $hostnameMatch,
            'ssl_issuer' => $issuer,
        ];
    }

    private function verifyHostname(string $host, array $certInfo): bool
    {
        $commonName = $certInfo['subject']['CN'] ?? null;
        if ($this->hostnameMatchesPattern($host, $commonName)) return true;

        $altNames = $certInfo['extensions']['subjectAltName'] ?? '';
        $altNamesArray = array_map('trim', explode(',', $altNames));

        foreach ($altNamesArray as $altName) {
            $altName = str_replace('DNS:', '', $altName);
            if ($this->hostnameMatchesPattern($host, $altName)) return true;
        }

        return false;
    }

    private function hostnameMatchesPattern(string $host, ?string $pattern): bool
    {
        if ($pattern === null) return false;
        if (strtolower($host) === strtolower($pattern)) return true;
        if (strpos($pattern, '*.') === 0) {
            $pattern = substr($pattern, 2);
            $hostSub = substr($host, strpos($host, '.') + 1);
            return strtolower($hostSub) === strtolower($pattern);
        }
        return false;
    }

    private function getErrorMessage(bool $isExpired, bool $hostnameMatch): ?string
    {
        if ($isExpired) return 'Certificate has expired.';
        if (!$hostnameMatch) return 'Hostname mismatch (certificate is for another domain).';
        return null;
    }

    private function buildErrorResponse(string $message): array
    {
        return [
            'ssl_valid' => false,
            'ssl_expires_at' => null,
            'ssl_days_remaining' => null,
            'ssl_error_message' => $message,
            'ssl_hostname_match' => null,
            'ssl_issuer' => null,
        ];
    }
}
