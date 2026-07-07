<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fiscal_credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->boolean('is_default')->default(false);
            $table->string('inn')->nullable();
            $table->string('sno')->nullable();
            $table->string('email')->nullable();
            $table->string('payment_address')->nullable();
            $table->string('receipt_email')->nullable();
            $table->string('group_code')->nullable();
            $table->string('ffd')->nullable();
            $table->string('atol_login')->nullable();
            $table->string('atol_password')->nullable();
            $table->string('atol_token')->nullable();
            $table->timestamp('atol_token_expires')->nullable();
            $table->string('terminal')->nullable();
            $table->string('password')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        $agencies = DB::table('agencies')->get();

        foreach ($agencies as $agency) {
            $tbank = DB::table('tbank_credentials')
                ->where('agency_id', $agency->id)
                ->first();

            DB::table('fiscal_credentials')->insert([
                'agency_id' => $agency->id,
                'name' => $agency->name ?: 'Основные реквизиты',
                'is_default' => true,
                'inn' => $agency->inn,
                'sno' => $agency->sno,
                'email' => $agency->email,
                'payment_address' => $agency->payment_address,
                'receipt_email' => $agency->receipt_email ?? null,
                'group_code' => $agency->group_code,
                'ffd' => $agency->ffd,
                'atol_login' => $agency->atol_login,
                'atol_password' => $agency->atol_password,
                'atol_token' => $agency->atol_token,
                'atol_token_expires' => $agency->atol_token_expires,
                'terminal' => $tbank?->terminal,
                'password' => $tbank?->password,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Schema::table('insurers', function (Blueprint $table) {
            $table->foreignId('fiscal_credential_id')
                ->nullable()
                ->after('agency_id')
                ->constrained('fiscal_credentials')
                ->nullOnDelete();
        });

        Schema::table('receipts', function (Blueprint $table) {
            $table->foreignId('fiscal_credential_id')
                ->nullable()
                ->after('agency_id')
                ->constrained('fiscal_credentials')
                ->nullOnDelete();
        });

        Schema::table('agencies', function (Blueprint $table) {
            $table->dropColumn([
                'email',
                'sno',
                'payment_address',
                'receipt_email',
                'group_code',
                'ffd',
                'atol_login',
                'atol_password',
                'atol_token',
                'atol_token_expires',
            ]);
        });

        Schema::dropIfExists('tbank_credentials');
    }

    public function down(): void
    {
        Schema::create('tbank_credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained()->cascadeOnDelete();
            $table->string('terminal')->nullable();
            $table->string('password')->nullable();
            $table->timestamps();
        });

        Schema::table('agencies', function (Blueprint $table) {
            $table->string('email')->nullable();
            $table->string('sno')->nullable();
            $table->string('payment_address')->nullable();
            $table->string('receipt_email')->nullable();
            $table->string('group_code')->nullable();
            $table->string('ffd')->nullable();
            $table->string('atol_login')->nullable();
            $table->string('atol_password')->nullable();
            $table->string('atol_token')->nullable();
            $table->timestamp('atol_token_expires')->nullable();
        });

        $credentials = DB::table('fiscal_credentials')->where('is_default', true)->get();

        foreach ($credentials as $credential) {
            DB::table('agencies')->where('id', $credential->agency_id)->update([
                'email' => $credential->email,
                'sno' => $credential->sno,
                'payment_address' => $credential->payment_address,
                'receipt_email' => $credential->receipt_email,
                'group_code' => $credential->group_code,
                'ffd' => $credential->ffd,
                'atol_login' => $credential->atol_login,
                'atol_password' => $credential->atol_password,
                'atol_token' => $credential->atol_token,
                'atol_token_expires' => $credential->atol_token_expires,
            ]);

            if ($credential->terminal || $credential->password) {
                DB::table('tbank_credentials')->insert([
                    'agency_id' => $credential->agency_id,
                    'terminal' => $credential->terminal,
                    'password' => $credential->password,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        Schema::table('receipts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('fiscal_credential_id');
        });

        Schema::table('insurers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('fiscal_credential_id');
        });

        Schema::dropIfExists('fiscal_credentials');
    }
};
