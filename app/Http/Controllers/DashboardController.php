<?php

namespace App\Http\Controllers;

use App\Enums\DomainStatus;
use App\Models\Domain;
use App\Models\DomainCheck;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        $user = Auth::user();
        $baseQuery = $user->domains();

        // --- Get the user's domain IDs first ---
        $userDomainIds = $user->domains()->pluck('id')->toArray();

        // 1. General stats
        $stats = $baseQuery
            ->select(
                DB::raw('count(*) as total_domains'),
                DB::raw("count(case when last_status = 'healthy' then 1 end) as healthy_count"),
                DB::raw("count(case when last_status = 'degraded' then 1 end) as degraded_count"),
                DB::raw("count(case when last_status = 'unhealthy' then 1 end) as unhealthy_count")
            )
            ->first();

        // 2. Recent incidents (REMOVED FOR STABILITY)
        // $recentIncidents = [];
        // if (!empty($userDomainIds)) {
        //     $recentIncidents = DomainCheck::query()
        //         ->whereIn('domain_id', $userDomainIds)
        //         ->where('status_changed', true)
        //         ->whereIn('status', [DomainStatus::UNHEALTHY, DomainStatus::DEGRADED])
        //         ->with('domain:id,domain')
        //         ->latest('checked_at')
        //         ->limit(5)
        //         ->get()
        //         ->map(fn (DomainCheck $check) => [
        //             'domain_name' => $check->domain->domain,
        //             'status_to' => $check->status,
        //             'error_message' => $check->error_message,
        //             'checked_at' => $check->checked_at,
        //         ]);
        // }

        // 3. Domains with expiring SSL (less than 14 days)
        $expiringSslDomains = $user->domains()
            ->whereNotNull('last_ssl_expires_at')
            ->where('last_ssl_expires_at', '<', now()->addDays(14))
            ->where('last_ssl_expires_at', '>', now())
            ->orderBy('last_ssl_expires_at')
            ->limit(5)
            ->get(['id', 'domain', 'last_ssl_expires_at'])
            ->map(fn (Domain $domain) => [
                'domain_name' => $domain->domain,
                'ssl_expires_at' => $domain->last_ssl_expires_at,
                'ssl_days_remaining' => $domain->last_ssl_expires_at->diffInDays(now()),
            ]);

        // 4. Slowest domains
        $slowestDomains = $user->domains()
            ->whereNotNull('last_response_time_ms')
            ->orderByDesc('last_response_time_ms')
            ->limit(5)
            ->get(['id', 'domain', 'last_response_time_ms']);

        return response()->json([
            'stats' => $stats,
            'recent_incidents' => [], // Always return an empty array
            'expiring_ssl_domains' => $expiringSslDomains,
            'slowest_domains' => $slowestDomains,
        ]);
    }
}
