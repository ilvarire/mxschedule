<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_reminder_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_allocation_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('hours_before');
            $table->timestamp('sent_at');
            $table->timestamps();

            $table->unique(['exam_allocation_id', 'hours_before'], 'exam_reminder_unique');
            $table->index(['hours_before', 'sent_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_reminder_logs');
    }
};
