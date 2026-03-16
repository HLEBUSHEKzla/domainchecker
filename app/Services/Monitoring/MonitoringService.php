<?php

namespace App\Services\Monitoring;

use App\Models\Domain;
use App\Services\Monitoring\Checkers\CheckerInterface;

class MonitoringService
{
    /** @var CheckerInterface[] */
    private array $checkers;

    public function __construct(CheckerInterface ...$checkers)
    {
        $this->checkers = $checkers;
    }

    public function run(Domain $domain): array
    {
        $results = [];

        foreach ($this->checkers as $checker) {
            $results[$checker->getKey()] = $checker->check($domain, $results);
        }

        return $results;
    }
}
