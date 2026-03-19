<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_passes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_allocation_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('pass_code', 64)->unique();
            $table->text('qr_payload');
            $table->boolean('is_used')->default(false);
            $table->timestamp('used_at')->nullable();
            $table->string('pdf_path')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_passes');
    }
};
