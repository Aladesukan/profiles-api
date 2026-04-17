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
        Schema::create('profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->unique();
            $table->string('gender', 10)->nullable();
            $table->decimal('gender_probability', 5,4)->nullable();
            $table->unsignedInteger('sample_size')->nullable();
            $table->integer('age')->nullable();
            $table->string('age_group', 20)->nullable();
            $table->string('country_id',10)->nullable();
            $table->decimal('country_probability', 5,4)->nullable();
            $table->timestamps();

            $table->index(['gender', 'country_id', 'age_group']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
