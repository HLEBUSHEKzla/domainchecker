<?php

namespace App\Services\Monitoring\StatusDeterminers;

use App\Enums\DomainStatus;

class DnsStatusDeterminer extends AbstractStatusDeterminer
{
    public function handle(array $checkResult): ?DomainStatus
    {
        if (!($checkResult['dns']['dns_ok'] ?? true)) {
            return DomainStatus::UNHEALTHY;
        }

        return parent::handle($checkResult);
    }
}
