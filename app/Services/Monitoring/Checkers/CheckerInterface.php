<?php

namespace App\Services\Monitoring\Checkers;

use App\Models\Domain;

interface CheckerInterface
{
    /**
     * Get the key for the checker's result.
     */
    public function getKey(): string;

    /**
     * Run the check.
     *
     * @param Domain $domain The domain to check.
     * @param array $context Context from previous checkers (e.g., HTTP body).
     * @return array The result of the check.
     */
    public function check(Domain $domain, array $context = []): array;
}
