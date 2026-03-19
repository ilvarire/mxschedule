<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('systems', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hall_id')->constrained()->cascadeOnDelete();
            $table->string('system_code', 20)->unique();
            $table->string('label', 50)->nullable();
            $table->string('status')->default('active'); // active | inactive | faulty
            $table->text('status_note')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->index(['hall_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('systems');
    }
};
