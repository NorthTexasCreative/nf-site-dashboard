<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('environments', function (Blueprint $table) {
            $table->string('lifecycle_status', 32)->default('active')->after('stable_ips');
            $table->text('notes')->nullable()->after('lifecycle_status');
        });

        // Phase 2 strictness: update_method should be blank until a user sets it.
        DB::table('environments')->update(['update_method' => null]);

        // Phase 2: stop storing update_schedule_set; derive it from update_schedule being set.
        if (Schema::hasColumn('environments', 'update_schedule_set')) {
            Schema::table('environments', function (Blueprint $table) {
                $table->dropColumn('update_schedule_set');
            });
        }
    }

    public function down(): void
    {
        Schema::table('environments', function (Blueprint $table) {
            if (Schema::hasColumn('environments', 'notes')) {
                $table->dropColumn('notes');
            }
            if (Schema::hasColumn('environments', 'lifecycle_status')) {
                $table->dropColumn('lifecycle_status');
            }
        });

        // Re-adding update_schedule_set on rollback keeps schema compatible with older code.
        if (! Schema::hasColumn('environments', 'update_schedule_set')) {
            Schema::table('environments', function (Blueprint $table) {
                $table->boolean('update_schedule_set')->default(false)->after('update_schedule');
            });
        }
    }
};

