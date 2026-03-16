<?php

namespace App\Actions\Monitoring;

use App\Models\Domain;
use App\Services\Monitoring\MonitoringService;

class RunDomainCheckAction
{
    public function __construct(private MonitoringService $monitoringService)
    {
    }

    public function execute(Domain $domain): array
    {
        return $this->monitoringService->run($domain);
    }
}
