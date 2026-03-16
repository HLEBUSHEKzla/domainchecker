<?php

namespace App\Http\Controllers;

use App\Services\Monitoring\Checkers\ContentChecker;
use App\Services\Monitoring\Checkers\DnsChecker;
use App\Services\Monitoring\Checkers\HttpChecker;
use App\Services\Monitoring\Checkers\RedirectChecker;
use App\Services\Monitoring\Checkers\SearchVisibilityChecker;
use App\Services\Monitoring\Checkers\SslChecker;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CheckerTestController extends Controller
{
    // ... (previous methods are omitted for brevity)
    public function testDns(Request $request, DnsChecker $dnsChecker): JsonResponse
    {
        try {
            $validated = $request->validate([
                'domain' => ['required', 'string', 'max:255'],
            ]);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        $host = $this->prepareHost($validated['domain']);
        $result = $dnsChecker->check($host);

        return response()->json($result);
    }

    public function testHttp(Request $request, HttpChecker $httpChecker): JsonResponse
    {
        try {
            $validated = $request->validate([
                'domain' => ['required', 'string', 'max:255'],
                'method' => ['sometimes', 'string', Rule::in(['GET', 'HEAD'])],
                'timeout' => ['sometimes', 'integer', 'min:1', 'max:120'],
            ]);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        $url = $this->prepareUrl($validated['domain']);

        $result = $httpChecker->check(
            $url,
            $validated['method'] ?? 'GET',
            $validated['timeout'] ?? 30
        );

        // We don't return the full body in the test response for readability
        unset($result['response_body']);

        return response()->json($result);
    }

    public function testRedirect(Request $request, RedirectChecker $redirectChecker): JsonResponse
    {
        try {
            $validated = $request->validate([
                'domain' => ['required', 'string', 'max:255'],
                'max_redirects' => ['sometimes', 'integer', 'min:0', 'max:20'],
                'timeout' => ['sometimes', 'integer', 'min:1', 'max:120'],
            ]);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        $url = $this->prepareUrl($validated['domain']);

        $result = $redirectChecker->check(
            $url,
            $validated['max_redirects'] ?? 10,
            $validated['timeout'] ?? 30
        );

        return response()->json($result);
    }

    public function testSsl(Request $request, SslChecker $sslChecker): JsonResponse
    {
        try {
            $validated = $request->validate([
                'domain' => ['required', 'string', 'max:255'],
                'timeout' => ['sometimes', 'integer', 'min:1', 'max:30'],
            ]);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        $host = $this->prepareHost($validated['domain']);

        $result = $sslChecker->check(
            $host,
            $validated['timeout'] ?? 10
        );

        return response()->json($result);
    }

    public function testContent(Request $request, HttpChecker $httpChecker, ContentChecker $contentChecker): JsonResponse
    {
        try {
            $validated = $request->validate([
                'domain' => ['required', 'string', 'max:255'],
                'expected_marker' => ['nullable', 'string', 'max:255'],
            ]);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        $url = $this->prepareUrl($validated['domain']);

        // 1. Get the raw HTML from HttpChecker
        $httpResult = $httpChecker->check($url);
        $htmlBody = $httpResult['response_body'];

        // 2. Pass the HTML to ContentChecker
        $contentResult = $contentChecker->check(
            $htmlBody,
            $validated['expected_marker'] ?? null
        );

        return response()->json($contentResult);
    }

    public function testSearchVisibility(Request $request, SearchVisibilityChecker $searchChecker): JsonResponse
    {
        try {
            $validated = $request->validate([
                'domain' => ['required', 'string', 'max:255'],
            ]);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        $url = $this->prepareUrl($validated['domain']);
        $result = $searchChecker->check($url);

        return response()->json($result);
    }

    public function testAll(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'domain' => ['required', 'string', 'max:255'],
                'expected_marker' => ['nullable', 'string', 'max:255'],
            ]);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        $host = $this->prepareHost($validated['domain']);
        $url = $this->prepareUrl($validated['domain']);

        // --- Execute all checkers ---
        $dnsResult = app(DnsChecker::class)->check($host);
        $sslResult = app(SslChecker::class)->check($host);
        $httpResult = app(HttpChecker::class)->check($url);
        $redirectResult = app(RedirectChecker::class)->check($url);
        $contentResult = app(ContentChecker::class)->check(
            $httpResult['response_body'],
            $validated['expected_marker'] ?? null
        );
        $searchResult = app(SearchVisibilityChecker::class)->check($url);

        // --- Combine results ---
        $fullReport = [
            'dns' => $dnsResult,
            'ssl' => $sslResult,
            'http' => $httpResult,
            'redirect' => $redirectResult,
            'content' => $contentResult,
            'search' => $searchResult,
        ];

        // We don't need the full body in the final report
        unset($fullReport['http']['response_body']);

        return response()->json($fullReport);
    }

    private function prepareHost(string $domain): string
    {
        // Remove scheme and path, leaving only the host
        return parse_url($domain, PHP_URL_HOST) ?? $domain;
    }

    private function prepareUrl(string $domain): string
    {
        // Add https scheme if it's missing
        if (!preg_match("~^(?:f|ht)tps?://~i", $domain)) {
            return "https://" . $domain;
        }
        return $domain;
    }
}
