<?php

use App\Models\Agency;
use App\Models\User;
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
        Schema::create('receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Agency::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->string('surname')->nullable();
            $table->string('patronymic')->nullable();
            $table->string('passport')->nullable();
            $table->string('insurer_name')->nullable();
            $table->string('contract_name')->nullable();
            $table->string('contract_series')->nullable();
            $table->string('contract_number')->nullable();
            $table->string('client_email')->nullable();
            $table->string('agent_email')->nullable();
            $table->unsignedInteger('amount')->nullable();

            $table->boolean('is_draft')->default(0);
            $table->string('payment_type')->nullable();
            $table->string('status')->nullable();
            $table->string('fiscal_receipt_number')->nullable();
            $table->string('shift_number')->nullable();
            $table->string('receipt_datetime')->nullable();
            $table->string('fn_number')->nullable();
            $table->string('ecr_registration_number')->nullable();
            $table->string('fiscal_document_number')->nullable();
            $table->string('fiscal_document_attribute')->nullable();
            $table->string('ofd_receipt_url')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('receipes');
    }
};
