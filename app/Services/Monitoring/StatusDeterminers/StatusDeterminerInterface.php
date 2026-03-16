<?php

namespace App\Services\Monitoring\StatusDeterminers;

use App\Enums\DomainStatus;

interface StatusDeterminerInterface
{
    public function setNext(StatusDeterminerInterface $handler): StatusDeterminerInterface;

    public function handle(array $checkResult): ?DomainStatus;
}
