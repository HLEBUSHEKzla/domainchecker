<?php

namespace App\Services\Monitoring\Checkers;

use App\Models\Domain;
use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler;

class ContentChecker implements CheckerInterface
{
    private const PARKED_KEYWORDS = ['domain is parked', 'domain for sale', 'buy this domain'];
    private const SUSPENDED_KEYWORDS = ['account suspended', 'site suspended', 'this account has been suspended'];
    private const CHALLENGE_KEYWORDS = ['checking your browser', 'ddos protection by', 'cloudflare', "i'm under attack"];

    public function getKey(): string
    {
        return 'content';
    }

    public function check(Domain $domain, array $context = []): array
    {
        $htmlBody = $context['http']['response_body'] ?? null;
        $expectedMarker = $domain->expected_content_marker;

        if (empty($htmlBody)) {
            return $this->buildErrorResponse('HTML body was empty.');
        }

        try {
            $crawler = new Crawler($htmlBody);
            $pageText = strtolower($crawler->text(''));
            $titleNode = $crawler->filter('title');
            $title = $titleNode->count() > 0 ? $titleNode->first()->text() : null;
            $descriptionNode = $crawler->filter('meta[name="description"]');
            $description = $descriptionNode->count() > 0 ? $descriptionNode->first()->attr('content') : null;
            $h1Node = $crawler->filter('h1');
            $h1 = $h1Node->count() > 0 ? $h1Node->first()->text() : null;

            $markerFound = null;
            if ($expectedMarker) {
                $markerFound = Str::contains($pageText, strtolower($expectedMarker));
            }

            $parkedDetected = Str::contains($pageText, self::PARKED_KEYWORDS);
            $suspendedDetected = Str::contains($pageText, self::SUSPENDED_KEYWORDS);
            $challengeDetected = Str::contains($pageText, self::CHALLENGE_KEYWORDS);

            $negativeSignal = $parkedDetected || $suspendedDetected || $challengeDetected;
            $checkPassed = $this->determinePassStatus($markerFound, $negativeSignal, $expectedMarker);
            $details = $this->generateDetails($checkPassed, $markerFound, $negativeSignal, $expectedMarker);

            return [
                'content_check_passed' => $checkPassed,
                'content_check_details' => $details,
                'page_title' => $title,
                'meta_description' => $description,
                'h1' => $h1,
                'seo_excerpt' => sprintf("Title: %s | Description: %s | H1: %s", $title ?? 'N/A', $description ?? 'N/A', $h1 ?? 'N/A'),
                'expected_marker_found' => $markerFound,
                'parked_page_detected' => $parkedDetected,
                'suspended_page_detected' => $suspendedDetected,
                'challenge_page_detected' => $challengeDetected,
            ];
        } catch (\Exception $e) {
            return $this->buildErrorResponse('Failed to parse HTML content: ' . $e->getMessage());
        }
    }

    private function determinePassStatus(?bool $markerFound, bool $negativeSignal, ?string $expectedMarker): bool
    {
        if ($negativeSignal) return false;
        if ($expectedMarker) return $markerFound;
        return true;
    }

    private function generateDetails(bool $checkPassed, ?bool $markerFound, bool $negativeSignal, ?string $expectedMarker): string
    {
        if ($negativeSignal) return 'Negative signal detected (parked, suspended, or challenge page).';
        if ($expectedMarker) return $markerFound ? "Expected marker '{$expectedMarker}' was found." : "Expected marker '{$expectedMarker}' was NOT found.";
        return 'No specific marker was checked. Page passed basic content integrity check (no negative signals found).';
    }

    private function buildErrorResponse(string $message): array
    {
        return [
            'content_check_passed' => false,
            'content_check_details' => $message,
            'page_title' => null,
            'meta_description' => null,
            'h1' => null,
            'seo_excerpt' => 'Could not generate excerpt.',
            'expected_marker_found' => null,
            'parked_page_detected' => false,
            'suspended_page_detected' => false,
            'challenge_page_detected' => false,
        ];
    }
}
