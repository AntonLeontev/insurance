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
        Schema::create('agency_user', function (Blueprint $table) {
            $table->foreignIdFor(Agency::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();
            $table->string('role');
        });

        User::get(['id', 'agency_id', 'role'])->each(function (User $user) {
            $user->agencies()->attach($user->agency_id, ['role' => $user->role]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agency_user');
    }
};
