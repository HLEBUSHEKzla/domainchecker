<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Domain extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'domain',
        'host',
        'scheme',
        'is_active',
        'check_interval_minutes',
        'timeout_seconds',
        'check_method',
        'follow_redirects',
        'max_redirects',
        'expected_final_url',
        'expected_content_marker',
        'content_check_enabled',
        'ssl_check_enabled',
        'last_checked_at',
        'last_status',
        'last_http_status_code',
        'last_response_time_ms',
        'last_final_url',
        'last_ssl_expires_at',
        'last_content_check_passed',
        'last_dns_ok',
        'next_check_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'follow_redirects' => 'boolean',
        'content_check_enabled' => 'boolean',
        'ssl_check_enabled' => 'boolean',
        'last_checked_at' => 'datetime',
        'last_ssl_expires_at' => 'datetime',
        'last_status_changed_at' => 'datetime',
        'next_check_at' => 'datetime',
        'last_check_job_dispatched_at' => 'datetime',
    ];

    /**
     * Get the full target URL including the scheme.
     */
    protected function targetUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => "{$this->scheme}://{$this->domain}",
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the check history for the domain.
     */
    public function checks(): HasMany
    {
        return $this->hasMany(DomainCheck::class)->latest('checked_at');
    }

    /**
     * Get the latest check for the domain.
     */
    public function latestCheck(): HasOne
    {
        return $this->hasOne(DomainCheck::class)->latestOfMany('checked_at');
    }
}
