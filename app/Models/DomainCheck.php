<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DomainCheck extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'domain_id',
        'checked_at',
        'status',
        'status_changed',
        'check_source',
        'check_method',
        'dns_ok',
        'http_status_code',
        'response_time_ms',
        'final_url',
        'redirect_count',
        'ssl_valid',
        'ssl_expires_at',
        'content_check_passed',
        'error_type',
        'error_message',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'checked_at' => 'datetime',
        'status_changed' => 'boolean',
        'dns_ok' => 'boolean',
        'ssl_valid' => 'boolean',
        'ssl_expires_at' => 'datetime',
        'content_check_passed' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Get the domain that owns the check.
     */
    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }
}
