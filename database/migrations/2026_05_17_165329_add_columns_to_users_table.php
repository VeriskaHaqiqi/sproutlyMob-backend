<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 20)->nullable()->after('email');
            $table->enum('gender', ['Male', 'Female', 'Other'])->nullable()->after('phone');
            $table->enum('role', ['user', 'expert'])->default('user')->after('gender');
            $table->string('profile_photo')->nullable()->after('role');
            $table->string('google_id')->nullable()->after('profile_photo');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone', 'gender', 'role', 'profile_photo', 'google_id']);
        });
    }
};