<?php

namespace App\Enums;

enum DomainStatus: string
{
    case HEALTHY = 'healthy';
    case DEGRADED = 'degraded';
    case UNHEALTHY = 'unhealthy';
    case MISCONFIGURED = 'misconfigured';
    case UNKNOWN = 'unknown';
}
