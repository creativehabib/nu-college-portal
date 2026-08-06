<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('colleges', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('nu_id')->unique()->comment('ID from NU API');
            $table->string('college_code')->unique();
            $table->string('college_name')->nullable();
            $table->string('college_name_bn')->nullable();
            $table->string('email')->nullable();
            $table->string('principal_name')->nullable();
            $table->string('college_phone')->nullable();
            $table->string('college_mobile')->nullable();
            $table->text('address')->nullable();
            $table->string('upazilla')->nullable();
            $table->string('eiin')->nullable();
            $table->string('establish_year')->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('colleges');
    }
};
