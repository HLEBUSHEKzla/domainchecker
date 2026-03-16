<?php

namespace App\Services\Monitoring\StatusDeterminers;

use App\Enums\DomainStatus;

class HttpStatusCodeDeterminer extends AbstractStatusDeterminer
{
    public function handle(array $checkResult): ?DomainStatus
    {
        $statusCode = $checkResult['http']['http_status_code'] ?? null;

        if ($statusCode === null || $statusCode >= 500) {
            return DomainStatus::UNHEALTHY;
        }

        if ($statusCode >= 400) {
            return DomainStatus::DEGRADED;
        }

        return parent::handle($checkResult);
    }
}
