<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('expert_id')->constrained('users')->onDelete('cascade');
            $table->string('topic', 200)->nullable();
            $table->decimal('fee', 10, 2);
            $table->enum('status', [
                'waiting_payment',
                'waiting_verification',
                'active',
                'completed',
                'rejected'
            ])->default('waiting_payment');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('scheduled_end_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultations');
    }
};