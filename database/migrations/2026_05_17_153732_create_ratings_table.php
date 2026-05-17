<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultation_id')->constrained('consultations')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('expert_id')->constrained('users')->onDelete('cascade');
            $table->tinyInteger('score')->unsigned(); // 1-5
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->unique('consultation_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ratings');
    }
};