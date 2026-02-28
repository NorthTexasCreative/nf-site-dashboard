<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sites', function (Blueprint $table) {
            $table->id();
            $table->string('wpe_site_id', 36)->unique();
            $table->foreignId('server_id')->constrained('servers')->cascadeOnDelete();
            $table->string('name');
            $table->string('group_name')->nullable();
            $table->json('tags')->nullable();
            $table->timestamp('created_at_wpe')->nullable();
            $table->boolean('sandbox')->default(false);
            $table->boolean('transferable')->default(false);
            $table->string('lifecycle_status', 32)->default('active'); // active | unknown (never hard delete)
            $table->text('notes')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sites');
    }
};
