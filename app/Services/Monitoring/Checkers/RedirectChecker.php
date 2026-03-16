<?php

namespace App\Services\Monitoring\Checkers;

use App\Models\Domain;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class RedirectChecker implements CheckerInterface
{
    private const FAKE_USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/108.0.0.0 Safari/537.36';

    public function getKey(): string
    {
        return 'redirect';
    }

    public function check(Domain $domain, array $context = []): array
    {
        $history = [];
        $finalUrl = $domain->target_url;

        try {
            $response = Http::withHeaders(['User-Agent' => self::FAKE_USER_AGENT])
                ->withOptions([
                    'timeout' => $domain->timeout_seconds,
                    'allow_redirects' => [
                        'max' => $domain->max_redirects,
                        'strict' => true,
                        'referer' => true,
                        'on_redirect' => function ($request, $response, $uri) use (&$history, &$finalUrl) {
                            $history[] = [
                                'url' => (string)$request->getUri(),
                                'status_code' => $response->getStatusCode(),
                            ];
                            $finalUrl = (string)$uri;
                        },
                    ],
                ])
                ->get($domain->target_url);

            if (!empty($history)) {
                 $history[] = [
                    'url' => $finalUrl,
                    'status_code' => $response->status(),
                ];
            }

            $redirectCount = count($history) > 0 ? count($history) - 1 : 0;
            $urlsInChain = array_column($history, 'url');
            $loopDetected = count($urlsInChain) !== count(array_unique($urlsInChain));

            return [
                'redirect_ok' => true,
                'redirect_count' => $redirectCount,
                'final_url' => $redirectCount > 0 ? $finalUrl : null,
                'redirect_chain' => $history,
                'redirect_loop_detected' => $loopDetected,
                'error_message' => null,
            ];

        } catch (RequestException | ConnectionException $e) {
            return [
                'redirect_ok' => false,
                'redirect_count' => count($history),
                'final_url' => $finalUrl,
                'redirect_chain' => $history,
                'redirect_loop_detected' => true,
                'error_message' => $e->getMessage(),
            ];
        }
    }
}
