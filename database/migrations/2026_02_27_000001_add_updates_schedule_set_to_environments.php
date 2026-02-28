<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('environments', 'updates_schedule_set')) {
            return;
        }
        Schema::table('environments', function (Blueprint $table) {
            $table->boolean('updates_schedule_set')->default(false)->after('update_schedule');
        });
    }

    public function down(): void
    {
        Schema::table('environments', function (Blueprint $table) {
            $table->dropColumn('updates_schedule_set');
        });
    }
};
