<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_student', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_profile_id')->constrained()->cascadeOnDelete();
            $table->string('academic_session', 20); // e.g. 2025/2026
            $table->string('semester'); // first | second
            $table->timestamps();

            $table->unique(
                ['course_id', 'student_profile_id', 'academic_session', 'semester'],
                'course_student_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_student');
    }
};
