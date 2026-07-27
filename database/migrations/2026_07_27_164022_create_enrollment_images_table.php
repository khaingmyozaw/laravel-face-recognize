<?php

use App\Models\Enrollments;
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
        Schema::create('enrollment_images', function (Blueprint $table) {
            $table->id();
            $table->string('file_path');
            $table->foreignIdFor(Enrollments::class, 'enrollment_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enrollment_images');
    }
};
