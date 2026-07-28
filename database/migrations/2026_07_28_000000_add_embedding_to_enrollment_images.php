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
        if (! Schema::hasTable('enrollment_images')) {
            return;
        }

        Schema::table('enrollment_images', function (Blueprint $table) {
            if (! Schema::hasColumn('enrollment_images', 'embedding')) {
                $table->json('embedding')->nullable()->after('file_path');
            }

            if (! Schema::hasColumn('enrollment_images', 'embedding_dim')) {
                $table->unsignedSmallInteger('embedding_dim')->nullable()->after('embedding');
            }

            if (! Schema::hasColumn('enrollment_images', 'bbox')) {
                $table->json('bbox')->nullable()->after('embedding_dim');
            }

            if (! Schema::hasColumn('enrollment_images', 'det_score')) {
                $table->float('det_score')->nullable()->after('bbox');
            }

            if (! Schema::hasColumn('enrollment_images', 'face_count')) {
                $table->unsignedSmallInteger('face_count')->nullable()->after('det_score');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('enrollment_images')) {
            return;
        }

        Schema::table('enrollment_images', function (Blueprint $table) {
            $columns = ['embedding', 'embedding_dim', 'bbox', 'det_score', 'face_count'];

            foreach ($columns as $column) {
                if (Schema::hasColumn('enrollment_images', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
