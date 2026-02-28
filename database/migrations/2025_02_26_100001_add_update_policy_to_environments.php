<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('environments', function (Blueprint $table) {
            $table->string('update_method', 32)->nullable()->after('last_detail_sync_at');
            $table->string('update_schedule', 32)->nullable()->after('update_method');
            $table->boolean('update_schedule_set')->default(false)->after('update_schedule');
        });

        // Set defaults for existing records
        \DB::table('environments')->where('environment', 'production')->update([
            'update_method' => 'wpe_managed',
            'update_schedule_set' => false,
        ]);
        \DB::table('environments')
            ->whereIn('environment', ['staging', 'development'])
            ->update([
                'update_method' => 'script',
                'update_schedule_set' => false,
            ]);
    }

    public function down(): void
    {
        Schema::table('environments', function (Blueprint $table) {
            $table->dropColumn(['update_method', 'update_schedule', 'update_schedule_set']);
        });
    }
};
