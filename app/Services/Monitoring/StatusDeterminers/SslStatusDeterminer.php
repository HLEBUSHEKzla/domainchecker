<?php

namespace App\Services\Monitoring\StatusDeterminers;

use App\Enums\DomainStatus;

class SslStatusDeterminer extends AbstractStatusDeterminer
{
    public function handle(array $checkResult): ?DomainStatus
    {
        if (!($checkResult['ssl']['ssl_valid'] ?? true)) {
            return DomainStatus::UNHEALTHY;
        }

        return parent::handle($checkResult);
    }
}
