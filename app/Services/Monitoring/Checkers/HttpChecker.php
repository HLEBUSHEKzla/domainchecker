<?php

namespace App\Services\Monitoring\Checkers;

use App\Models\Domain;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class HttpChecker implements CheckerInterface
{
    private const FAKE_USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/108.0.0.0 Safari/537.36';

    public function getKey(): string
    {
        return 'http';
    }

    public function check(Domain $domain, array $context = []): array
    {
        $startTime = microtime(true);

        try {
            $response = Http::withHeaders(['User-Agent' => self::FAKE_USER_AGENT])
                ->withOptions(['timeout' => $domain->timeout_seconds])
                ->send(strtoupper($domain->check_method), $domain->target_url);

            $duration = (int)((microtime(true) - $startTime) * 1000);

            return [
                'http_ok' => $response->successful(),
                'http_status_code' => $response->status(),
                'response_time_ms' => $duration,
                'response_headers' => $response->headers(),
                'response_body' => $response->body(),
                'content_type' => $response->header('Content-Type'),
                'network_error_message' => null,
            ];

        } catch (ConnectionException | RequestException $e) {
            $duration = (int)((microtime(true) - $startTime) * 1000);
            return $this->buildErrorResponse($e->getMessage(), $duration);
        }
    }

    private function buildErrorResponse(string $message, int $duration): array
    {
        return [
            'http_ok' => false,
            'http_status_code' => null,
            'response_time_ms' => $duration,
            'response_headers' => [],
            'response_body' => null,
            'content_type' => null,
            'network_error_message' => $message,
        ];
    }
}
