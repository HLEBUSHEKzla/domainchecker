<?php

namespace App\Console\Commands;

use App\Jobs\ProcessDomainCheckJob;
use App\Models\Domain;
use Illuminate\Console\Command;

class DispatchChecksCommand extends Command
{
    protected $signature = 'monitoring:dispatch-checks';
    protected $description = 'Dispatch jobs for domains that are due for a check';

    public function handle(): void
    {
        $this->info('Running dispatch command. Current time: ' . now()->toIso8601String());

        $query = Domain::query()
            ->where('is_active', true)
            ->where(function ($query) {
                $query->where('next_check_at', '<=', now())
                      ->orWhereNull('next_check_at');
            });

        $this->info('SQL Query: ' . $query->toSql());

        $domains = $query->get();

        if ($domains->isEmpty()) {
            $this->info('No domains are due for a check.');
            return;
        }

        $this->info("Found {$domains->count()} domains to check. Dispatching jobs...");

        foreach ($domains as $domain) {
            $this->info("Dispatching job for domain ID: {$domain->id} ({$domain->domain}) with next_check_at: " . $domain->next_check_at?->toIso8601String());
            ProcessDomainCheckJob::dispatch($domain);
        }

        $this->info('All jobs have been dispatched.');
    }
}
