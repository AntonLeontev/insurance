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
        Schema::table('receipts', function (Blueprint $table) {
            $table->bigInteger('fiscal_document_number')->nullable()->change();
            $table->bigInteger('fiscal_document_attribute')->nullable()->change();
            $table->bigInteger('fiscal_receipt_number')->nullable()->change();
            $table->bigInteger('shift_number')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('receipts', function (Blueprint $table) {
            $table->string('fiscal_document_number')->nullable()->change();
            $table->string('fiscal_document_attribute')->nullable()->change();
            $table->string('fiscal_receipt_number')->nullable()->change();
            $table->string('shift_number')->nullable()->change();
        });
    }
};
