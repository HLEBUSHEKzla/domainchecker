<?php

namespace App\Services\Monitoring\Checkers;

use App\Models\Domain;
use Illuminate\Support\Facades\Http;

class SearchVisibilityChecker implements CheckerInterface
{
    private const API_URL = 'https://safebrowsing.googleapis.com/v4/threatMatches:find';

    public function getKey(): string
    {
        return 'search';
    }

    public function check(Domain $domain, array $context = []): array
    {
        $apiKey = config('services.google.safe_browsing_api_key');

        if (empty($apiKey)) {
            return [
                'search_check_performed' => false,
                'safe_browsing_flag' => 'unknown',
                'search_reputation_note' => 'Safe Browsing API key is not configured.',
            ];
        }

        $response = Http::post(self::API_URL . '?key=' . $apiKey, [
            'client' => ['clientId' => 'domain-checker-app', 'clientVersion' => '1.0.0'],
            'threatInfo' => [
                'threatTypes' => ['MALWARE', 'SOCIAL_ENGINEERING', 'UNWANTED_SOFTWARE', 'POTENTIALLY_HARMFUL_APPLICATION'],
                'platformTypes' => ['ANY_PLATFORM'],
                'threatEntryTypes' => ['URL'],
                'threatEntries' => [['url' => $domain->target_url]],
            ],
        ]);

        if ($response->failed()) {
            return [
                'search_check_performed' => true,
                'safe_browsing_flag' => 'unknown',
                'search_reputation_note' => 'API request failed: ' . $response->reason(),
            ];
        }

        $data = $response->json();

        if (empty($data) || !isset($data['matches'])) {
            return [
                'search_check_performed' => true,
                'safe_browsing_flag' => 'clean',
                'search_reputation_note' => 'No threats found.',
            ];
        }

        $threatType = $data['matches'][0]['threatType'] ?? 'UNKNOWN';
        return [
            'search_check_performed' => true,
            'safe_browsing_flag' => 'flagged',
            'search_reputation_note' => "Threat detected: {$threatType}",
        ];
    }
}
