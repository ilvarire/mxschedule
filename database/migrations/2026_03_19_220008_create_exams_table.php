<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('academic_session', 20);
            $table->string('semester'); // first | second
            $table->date('exam_date');
            $table->unsignedInteger('duration_minutes');
            $table->unsignedInteger('buffer_minutes')->default(15);
            $table->time('start_time');
            $table->unsignedInteger('total_registered_students')->default(0);
            $table->string('status')->default('draft');
            $table->timestamp('scheduled_at')->nullable();
            $table->foreignId('scheduled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exams');
    }
};
