<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\User;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('domains', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();

            // Block 1: Identification
            $table->string('name');
            $table->string('domain', 2048); // Renamed from target_url
            $table->string('host');
            $table->string('scheme');
            $table->boolean('is_active')->default(true);

            // Block 2: Check Configuration
            $table->unsignedInteger('check_interval_minutes')->default(5);
            $table->unsignedInteger('timeout_seconds')->default(30);
            $table->string('check_method')->default('GET');
            $table->boolean('follow_redirects')->default(true);
            $table->unsignedInteger('max_redirects')->default(10);
            $table->string('expected_final_url', 2048)->nullable();
            $table->text('expected_content_marker')->nullable();
            $table->boolean('content_check_enabled')->default(false);
            $table->boolean('ssl_check_enabled')->default(true);

            // Block 3: Current State Snapshot
            $table->timestamp('last_checked_at')->nullable();
            $table->string('last_status')->nullable();
            $table->unsignedInteger('last_http_status_code')->nullable();
            $table->unsignedInteger('last_response_time_ms')->nullable();
            $table->string('last_final_url', 2048)->nullable();
            $table->string('last_error_type')->nullable();
            $table->text('last_error_message')->nullable();
            $table->boolean('last_ssl_valid')->nullable();
            $table->timestamp('last_ssl_expires_at')->nullable();
            $table->boolean('last_content_check_passed')->nullable();
            $table->boolean('last_dns_ok')->nullable();
            $table->timestamp('last_status_changed_at')->nullable();

            // Block 4: Scheduling
            $table->timestamp('next_check_at')->nullable();
            $table->timestamp('last_check_job_dispatched_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('domains');
    }
};
