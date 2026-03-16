<?php

namespace App\Services\Monitoring\StatusDeterminers;

use App\Enums\DomainStatus;

abstract class AbstractStatusDeterminer implements StatusDeterminerInterface
{
    private ?StatusDeterminerInterface $nextHandler = null;

    public function setNext(StatusDeterminerInterface $handler): StatusDeterminerInterface
    {
        $this->nextHandler = $handler;
        return $handler;
    }

    public function handle(array $checkResult): ?DomainStatus
    {
        if ($this->nextHandler) {
            return $this->nextHandler->handle($checkResult);
        }

        return null;
    }
}
