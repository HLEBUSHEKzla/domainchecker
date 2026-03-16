<?php

namespace App\Actions\Monitoring;

use App\Models\Domain;
use App\Models\DomainCheck;
use App\Services\Monitoring\StatusCalculatorService;
use Illuminate\Support\Facades\Log;

class CreateCheckHistoryAction
{
    public function __construct(private StatusCalculatorService $statusCalculator)
    {
    }

    public function execute(Domain $domain, array $checkResult): DomainCheck
    {
        $finalStatus = $this->statusCalculator->calculate($checkResult);
        $errors = $this->aggregateErrors($checkResult);

        // --- Logging for diagnostics ---
        Log::info("Checking domain ID: {$domain->id}. Previous status: '{$domain->last_status}'. New status: '{$finalStatus->value}'.");

        $statusChanged = $domain->last_status !== null && $domain->last_status !== $finalStatus->value;

        if ($statusChanged) {
            Log::info("Status changed for domain ID: {$domain->id}. From '{$domain->last_status}' to '{$finalStatus->value}'.");
        }
        // --- End Logging ---

        // --- Create the history record ---
        $domainCheck = $domain->checks()->create([
            'status' => $finalStatus,
            'status_changed' => $statusChanged,
            'checked_at' => now(),
            'check_source' => 'scheduled',
            'check_method' => $domain->check_method,
            'dns_ok' => $checkResult['dns']['dns_ok'],
            'http_status_code' => $checkResult['http']['http_status_code'],
            'response_time_ms' => $checkResult['http']['response_time_ms'],
            'final_url' => $checkResult['redirect']['final_url'],
            'redirect_count' => $checkResult['redirect']['redirect_count'],
            'ssl_valid' => $checkResult['ssl']['ssl_valid'],
            'ssl_expires_at' => $checkResult['ssl']['ssl_expires_at'],
            'content_check_passed' => $checkResult['content']['content_check_passed'],
            'error_type' => $errors['type'],
            'error_message' => $errors['message'],
            'metadata' => $this->buildMetadata($checkResult),
        ]);

        // --- Prepare data for domain snapshot update ---
        $domainUpdateData = [
            'last_checked_at' => $domainCheck->checked_at,
            'last_status' => $domainCheck->status,
            'last_http_status_code' => $domainCheck->http_status_code,
            'last_response_time_ms' => $domainCheck->response_time_ms,
            'last_final_url' => $domainCheck->final_url,
            'last_ssl_expires_at' => $domainCheck->ssl_expires_at,
            'last_content_check_passed' => $domainCheck->content_check_passed,
            'last_dns_ok' => $domainCheck->dns_ok,
            'next_check_at' => now()->addMinutes($domain->check_interval_minutes),
        ];

        if ($statusChanged) {
            $domainUpdateData['last_status_changed_at'] = now();
        }

        // --- Update the domain's snapshot ---
        $domain->update($domainUpdateData);

        return $domainCheck;
    }

    private function aggregateErrors(array $checkResult): array
    {
        $errorMessages = [];
        $errorType = null;

        if ($checkResult['dns']['dns_error_message']) {
            $errorMessages[] = 'DNS: ' . $checkResult['dns']['dns_error_message'];
            $errorType = 'dns_error';
        }
        if ($checkResult['ssl']['ssl_error_message']) {
            $errorMessages[] = 'SSL: ' . $checkResult['ssl']['ssl_error_message'];
            $errorType = $errorType ?? 'ssl_error';
        }
        if ($checkResult['http']['network_error_message']) {
            $errorMessages[] = 'HTTP: ' . $checkResult['http']['network_error_message'];
            $errorType = $errorType ?? 'http_error';
        }
        if ($checkResult['redirect']['error_message']) {
            $errorMessages[] = 'Redirect: ' . $checkResult['redirect']['error_message'];
            $errorType = $errorType ?? 'redirect_error';
        }
        if (isset($checkResult['content']['content_check_passed']) && $checkResult['content']['content_check_passed'] === false) {
            $errorMessages[] = 'Content: ' . $checkResult['content']['content_check_details'];
            $errorType = $errorType ?? 'content_error';
        }

        return [
            'type' => $errorType,
            'message' => implode(' | ', $errorMessages) ?: null,
        ];
    }

    private function buildMetadata(array $checkResult): array
    {
        // Remove raw body to avoid storing large amounts of data
        unset($checkResult['http']['response_body']);
        return $checkResult;
    }
}
