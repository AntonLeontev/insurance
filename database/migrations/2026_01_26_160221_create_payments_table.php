<?php

use App\Models\Receipt;
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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Receipt::class)->constrained()->cascadeOnDelete();
            $table->string('payment_id')->nullable();
            $table->string('status')->nullable();
            $table->string('redirect_url')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('expired_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
