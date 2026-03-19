<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('system_id')->constrained()->cascadeOnDelete();
            $table->foreignId('hall_id')->constrained()->cascadeOnDelete(); // denormalized
            $table->string('seat_status')->default('allocated');
            $table->timestamp('checked_in_at')->nullable();
            $table->foreignId('checked_in_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reassigned_from_id')->nullable()->constrained('exam_allocations')->nullOnDelete();
            $table->timestamps();

            // One slot per student per session
            $table->unique(['exam_session_id', 'student_profile_id'], 'alloc_session_student_unique');
            // One student per system per session
            $table->unique(['exam_session_id', 'system_id'], 'alloc_session_system_unique');
            // Fast queries by student
            $table->index(['student_profile_id', 'seat_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_allocations');
    }
};
