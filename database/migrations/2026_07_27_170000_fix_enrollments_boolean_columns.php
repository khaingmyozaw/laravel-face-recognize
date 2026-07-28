<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('enrollments')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE enrollments MODIFY is_f_alive TINYINT(1) NOT NULL DEFAULT 1');
            DB::statement('ALTER TABLE enrollments MODIFY is_m_alive TINYINT(1) NOT NULL DEFAULT 1');

            return;
        }

        Schema::table('enrollments', function (Blueprint $table) {
            $table->boolean('is_f_alive')->default(true)->change();
            $table->boolean('is_m_alive')->default(true)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('enrollments')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE enrollments MODIFY is_f_alive VARCHAR(255) NOT NULL');
            DB::statement('ALTER TABLE enrollments MODIFY is_m_alive VARCHAR(255) NOT NULL');

            return;
        }

        Schema::table('enrollments', function (Blueprint $table) {
            $table->string('is_f_alive')->change();
            $table->string('is_m_alive')->change();
        });
    }
};
