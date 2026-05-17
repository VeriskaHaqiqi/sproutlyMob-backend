<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expert_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('university', 150)->nullable();
            $table->integer('years_of_experience')->default(0);
            $table->text('description')->nullable();
            $table->string('certificate')->nullable();
            $table->string('diploma')->nullable();
            $table->string('bank_name', 100)->nullable();
            $table->string('account_holder', 100)->nullable();
            $table->string('account_number', 50)->nullable();
            $table->decimal('session_fee', 10, 2)->default(0);
            $table->integer('session_duration')->default(30); // in minutes
            $table->boolean('instant_booking')->default(false);
            $table->enum('availability_status', ['available', 'unavailable'])->default('unavailable');
            $table->decimal('average_rating', 3, 2)->default(0);
            $table->integer('total_consultations')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expert_profiles');
    }
};