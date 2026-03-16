<?php

namespace App\Services\Monitoring\Checkers;

use App\Models\Domain;
use Illuminate\Support\Arr;

class DnsChecker implements CheckerInterface
{
    public function getKey(): string
    {
        return 'dns';
    }

    public function check(Domain $domain, array $context = []): array
    {
        $host = $domain->host;
        $records = @dns_get_record($host, DNS_A);

        if ($records === false || empty($records)) {
            return [
                'dns_ok' => false,
                'resolved_ips' => [],
                'resolved_ip' => null,
                'dns_result_type' => 'NXDOMAIN_OR_FAILURE',
                'dns_error_message' => "Failed to resolve A records for host: {$host}",
            ];
        }

        $ips = Arr::pluck($records, 'ip');

        return [
            'dns_ok' => true,
            'resolved_ips' => $ips,
            'resolved_ip' => $ips[0] ?? null,
            'dns_result_type' => 'OK',
            'dns_error_message' => null,
        ];
    }
}
