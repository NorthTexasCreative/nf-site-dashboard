<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wpe_sync_runs', function (Blueprint $table) {
            $table->id();
            $table->string('sync_type', 64);
            $table->foreignId('triggered_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->string('status', 32);
            $table->integer('duration_seconds')->nullable();
            $table->integer('accounts_count')->nullable();
            $table->integer('sites_count')->nullable();
            $table->integer('environments_count')->nullable();
            $table->string('message', 500)->nullable();
            $table->longText('output')->nullable();
            $table->longText('error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wpe_sync_runs');
    }
};
