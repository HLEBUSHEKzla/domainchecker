<?php

namespace App\Services\Monitoring;

use App\Enums\DomainStatus;
use App\Services\Monitoring\StatusDeterminers\ContentStatusDeterminer;
use App\Services\Monitoring\StatusDeterminers\DnsStatusDeterminer;
use App\Services\Monitoring\StatusDeterminers\HttpStatusCodeDeterminer;
use App\Services\Monitoring\StatusDeterminers\SslStatusDeterminer;

class StatusCalculatorService
{
    private DnsStatusDeterminer $chain;

    public function __construct(
        DnsStatusDeterminer $dns,
        SslStatusDeterminer $ssl,
        HttpStatusCodeDeterminer $http,
        ContentStatusDeterminer $content
    ) {
        // Build the chain of responsibility
        $this->chain = $dns;
        $dns->setNext($ssl)
            ->setNext($http)
            ->setNext($content);
    }

    public function calculate(array $checkResult): DomainStatus
    {
        $status = $this->chain->handle($checkResult);

        // If no handler in the chain returned a status, it means everything is fine.
        return $status ?? DomainStatus::HEALTHY;
    }
}
