<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('college_user_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('college_id')->constrained('colleges')->cascadeOnDelete();
            $table->unsignedBigInteger('user_id')->nullable(); // এই কলামটি যোগ করুন
            $table->string('principal_name')->nullable();
            $table->string('v_principal_name')->nullable();
            $table->string('clerkname')->nullable();
            $table->string('college_phone')->nullable();
            $table->string('college_mobile')->nullable();
            $table->string('principal_mobile')->nullable();
            $table->string('vice_principal_mobile')->nullable();
            $table->string('cleark_mobile')->nullable();
            $table->text('address')->nullable();
            $table->string('upazilla')->nullable();
            $table->string('post_code')->nullable();
            $table->string('fax')->nullable();
            $table->string('male_female')->nullable();
            $table->string('col_type')->nullable();
            $table->string('college_Location')->nullable();
            $table->string('total_Land')->nullable();
            $table->string('establish_Year')->nullable();
            $table->text('background')->nullable();
            $table->string('website')->nullable();
            $table->decimal('lat', 10, 7)->nullable(); // যদি দশমিকের মান বড় হয়
            $table->decimal('lng', 10, 7)->nullable(); // যদি দশমিকের মান বড় হয়
            $table->string('logo')->nullable();
            $table->string('banner')->nullable();
            $table->string('eiin')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('college_user_details');
    }
};
