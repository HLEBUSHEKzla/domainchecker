<?php

namespace App\Services\Monitoring\StatusDeterminers;

use App\Enums\DomainStatus;

class ContentStatusDeterminer extends AbstractStatusDeterminer
{
    public function handle(array $checkResult): ?DomainStatus
    {
        $contentCheckPassed = $checkResult['content']['content_check_passed'] ?? null;

        if ($contentCheckPassed === false) {
            return DomainStatus::DEGRADED;
        }

        return parent::handle($checkResult);
    }
}
