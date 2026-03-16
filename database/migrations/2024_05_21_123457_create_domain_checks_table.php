<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Domain;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('domain_checks', function (Blueprint $table) {
            // --- Block 1: Identification ---
            $table->id();
            $table->foreignIdFor(Domain::class)->constrained()->cascadeOnDelete();
            $table->timestamp('checked_at');
            $table->string('status'); // e.g., 'healthy', 'degraded', 'unhealthy', 'unknown'
            $table->boolean('status_changed')->default(false);

            // --- Block 2: Check Context ---
            $table->string('check_source')->default('scheduled'); // 'manual', 'scheduled', 'retry'
            $table->string('check_method'); // 'GET', 'HEAD'

            // --- Core Results from Layers ---
            // DNS Layer
            $table->boolean('dns_ok')->nullable();

            // HTTP/Network Layer
            $table->unsignedInteger('http_status_code')->nullable();
            $table->unsignedInteger('response_time_ms')->nullable();
            $table->string('final_url', 2048)->nullable();
            $table->unsignedInteger('redirect_count')->nullable();

            // SSL Layer
            $table->boolean('ssl_valid')->nullable();
            $table->timestamp('ssl_expires_at')->nullable();

            // Content Layer
            $table->boolean('content_check_passed')->nullable();

            // --- High-level Error ---
            $table->string('error_type')->nullable(); // e.g., 'dns_error', 'timeout', 'ssl_error'
            $table->text('error_message')->nullable();

            // --- Metadata for all other details ---
            $table->json('metadata')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('domain_checks');
    }
};
