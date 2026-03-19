<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('session_number');
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->unsignedInteger('max_capacity');
            $table->unsignedInteger('allocated_count')->default(0);
            $table->string('status')->default('pending');
            $table->timestamps();

            $table->unique(['exam_id', 'session_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_sessions');
    }
};
