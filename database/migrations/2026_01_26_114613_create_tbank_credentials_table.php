<?php

use App\Models\Agency;
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
        Schema::create('tbank_credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Agency::class)->constrained()->cascadeOnDelete();
            $table->string('terminal')->nullable();
            $table->string('password')->nullable();
            $table->timestamps();
        });

        Agency::all()->each(function (Agency $agency) {
            $agency->tbankCredentials()->create([
                'terminal' => null,
                'password' => null,
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbank_credentials');
    }
};
