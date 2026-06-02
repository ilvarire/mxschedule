<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_allocations', function (Blueprint $table) {
            $table->index(['exam_session_id', 'student_profile_id'], 'alloc_session_student_index');
            $table->index(['exam_session_id', 'system_id'], 'alloc_session_system_index');
            $table->dropUnique('alloc_session_student_unique');
            $table->dropUnique('alloc_session_system_unique');
        });
    }

    public function down(): void
    {
        Schema::table('exam_allocations', function (Blueprint $table) {
            $table->unique(['exam_session_id', 'student_profile_id'], 'alloc_session_student_unique');
            $table->unique(['exam_session_id', 'system_id'], 'alloc_session_system_unique');
            $table->dropIndex('alloc_session_student_index');
            $table->dropIndex('alloc_session_system_index');
        });
    }
};
