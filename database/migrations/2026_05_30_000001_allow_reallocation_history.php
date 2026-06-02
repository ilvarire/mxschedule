<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasIndex('exam_allocations', 'alloc_session_student_index')) {
            Schema::table('exam_allocations', function (Blueprint $table) {
                $table->index(['exam_session_id', 'student_profile_id'], 'alloc_session_student_index');
            });
        }

        if (! Schema::hasIndex('exam_allocations', 'alloc_session_system_index')) {
            Schema::table('exam_allocations', function (Blueprint $table) {
                $table->index(['exam_session_id', 'system_id'], 'alloc_session_system_index');
            });
        }

        if (Schema::hasIndex('exam_allocations', 'alloc_session_student_unique')) {
            Schema::table('exam_allocations', function (Blueprint $table) {
                $table->dropUnique('alloc_session_student_unique');
            });
        }

        if (Schema::hasIndex('exam_allocations', 'alloc_session_system_unique')) {
            Schema::table('exam_allocations', function (Blueprint $table) {
                $table->dropUnique('alloc_session_system_unique');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasIndex('exam_allocations', 'alloc_session_student_unique')) {
            Schema::table('exam_allocations', function (Blueprint $table) {
                $table->unique(['exam_session_id', 'student_profile_id'], 'alloc_session_student_unique');
            });
        }

        if (! Schema::hasIndex('exam_allocations', 'alloc_session_system_unique')) {
            Schema::table('exam_allocations', function (Blueprint $table) {
                $table->unique(['exam_session_id', 'system_id'], 'alloc_session_system_unique');
            });
        }

        if (Schema::hasIndex('exam_allocations', 'alloc_session_student_index')) {
            Schema::table('exam_allocations', function (Blueprint $table) {
                $table->dropIndex('alloc_session_student_index');
            });
        }

        if (Schema::hasIndex('exam_allocations', 'alloc_session_system_index')) {
            Schema::table('exam_allocations', function (Blueprint $table) {
                $table->dropIndex('alloc_session_system_index');
            });
        }
    }
};
