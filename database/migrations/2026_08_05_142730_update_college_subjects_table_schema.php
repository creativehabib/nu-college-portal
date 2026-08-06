<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ১. পুরনো টেবিলটি মুছে ফেলা (ভয়ের কিছু নেই, আমরা নতুন করে বানাবো)
        Schema::dropIfExists('college_subjects');

        // ২. নতুন রিলেশনশিপ কাঠামোর টেবিল তৈরি
        Schema::create('college_subjects', function (Blueprint $table) {
            $table->id();
            // Foreign Keys
            $table->foreignId('college_id')->constrained('colleges')->cascadeOnDelete();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();

            // অন্যান্য কলাম
            $table->string('seat_capacity')->nullable();
            $table->date('aff_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('college_subjects');
    }
};
