<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // যদি ডাটাবেস ফ্রেস করতে চান, তবে আগেরটি ড্রপ করে নতুনভাবে করতে পারেন
        Schema::dropIfExists('colleges');

        Schema::create('colleges', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('nu_id')->unique()->comment('ID from NU API');
            $table->string('college_code')->unique();
            $table->string('college_name')->nullable();
            $table->string('college_name_bn')->nullable();
            $table->string('email')->nullable();
            $table->string('alt_email')->nullable();
            $table->string('role')->nullable();
            $table->string('approved')->nullable();
            $table->string('otp')->nullable();
            $table->string('password')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('colleges');
    }
};
