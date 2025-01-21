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
        Schema::create('agencies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('inn')->nullable();
            $table->string('email')->nullable();
            $table->string('sno')->nullable();
            $table->string('payment_address')->nullable();

            $table->string('group_code')->nullable();
            $table->string('ffd')->nullable();
            $table->string('atol_login')->nullable();
            $table->string('atol_password')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agencies');
    }
};
