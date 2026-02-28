<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('environments', function (Blueprint $table) {
            $table->id();
            $table->string('wpe_install_id', 36)->unique();
            $table->foreignId('site_id')->constrained('sites')->cascadeOnDelete();
            $table->string('name');
            $table->string('environment', 32); // production, staging, development
            $table->string('cname')->nullable();
            $table->string('php_version', 16)->nullable();
            $table->boolean('is_multisite')->default(false);
            $table->string('status', 32)->nullable();
            $table->string('primary_domain')->nullable();
            $table->string('wp_version', 32)->nullable();
            $table->json('stable_ips')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamp('last_detail_sync_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('environments');
    }
};
