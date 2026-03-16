<?php

namespace App\Jobs;

use App\Actions\Monitoring\CreateCheckHistoryAction;
use App\Actions\Monitoring\RunDomainCheckAction;
use App\Models\Domain;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class ProcessDomainCheckJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public int $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(public Domain $domain)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(RunDomainCheckAction $runCheck, CreateCheckHistoryAction $createHistory): void
    {
        $lock = Cache::lock('check-domain-'.$this->domain->id, 60);

        if ($lock->get()) {
            try {
                $checkResult = $runCheck->execute($this->domain);
                $createHistory->execute($this->domain, $checkResult);
            } finally {
                $lock->release();
            }
        } else {
            // Could not obtain lock, another check is likely in progress.
            // We can release the job back to the queue with a delay.
            $this->release(30);
        }
    }
}
