<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('domains', function (Blueprint $table) {
            $table->index('next_check_at');
            $table->index('last_status');
            $table->index('last_ssl_expires_at');
            $table->index(['name', 'domain']); // Composite index for search
        });

        Schema::table('domain_checks', function (Blueprint $table) {
            $table->index('status');
            $table->index('status_changed');
            $table->index('checked_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('domains', function (Blueprint $table) {
            $table->dropIndex(['next_check_at']);
            $table->dropIndex(['last_status']);
            $table->dropIndex(['last_ssl_expires_at']);
            $table->dropIndex(['domains_name_domain_index']); // Drop composite index
        });

        Schema::table('domain_checks', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['status_changed']);
            $table->dropIndex(['checked_at']);
        });
    }
};
