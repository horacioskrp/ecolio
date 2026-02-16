<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('schools', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->enum('level', ['primaire', 'college', 'lycee'])->default('primaire');
            $table->string('code')->unique();
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('principal')->nullable();
            $table->text('description')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('academic_years', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('school_id');
            $table->string('year');
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('active')->default(false);
            $table->timestamps();

            $table->foreign('school_id')->references('id')->on('schools')->onDelete('cascade');
        });

        Schema::create('classes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('school_id');
            $table->uuid('academic_year_id');
            $table->string('name');
            $table->string('code')->unique();
            $table->integer('capacity')->default(40);
            $table->uuid('teacher_id')->nullable();
            $table->timestamps();

            $table->foreign('school_id')->references('id')->on('schools')->onDelete('cascade');
            $table->foreign('academic_year_id')->references('id')->on('academic_years')->onDelete('cascade');
            $table->foreign('teacher_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('classes');
        Schema::dropIfExists('academic_years');
        Schema::dropIfExists('schools');
    }
};
