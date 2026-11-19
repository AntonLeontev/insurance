<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\CreatesAgencyReceiptContext;
use Tests\TestCase;

class DashboardVisibilityTest extends TestCase
{
    use CreatesAgencyReceiptContext;
    use RefreshDatabase;

    public function test_dashboard_is_visible_only_to_the_configured_owner(): void
    {
        config(['services.dashboard.owner_email' => 'aner-anton@yandex.ru']);

        [$agency, $owner] = $this->createAgencyWithUser();
        $owner->update(['email' => 'aner-anton@yandex.ru']);

        [$otherAgency, $other] = $this->createAgencyWithUser();

        $this->actingAs($owner)
            ->getJson('/user')
            ->assertOk()
            ->assertJsonPath('dashboard_visible', true);

        $this->actingAs($owner)
            ->postJson('/user/update', ['name' => $owner->name])
            ->assertOk()
            ->assertJsonPath('dashboard_visible', true);

        $this->actingAs($owner)
            ->getJson(route('dashboard', ['agency_id' => $agency->id]))
            ->assertOk()
            ->assertJsonStructure(['days', 'months']);

        $this->actingAs($other)
            ->getJson('/user')
            ->assertOk()
            ->assertJsonPath('dashboard_visible', false);

        $this->actingAs($other)
            ->getJson(route('dashboard', ['agency_id' => $otherAgency->id]))
            ->assertForbidden();
    }

    public function test_login_returns_dashboard_visibility_for_the_current_user(): void
    {
        config(['services.dashboard.owner_email' => 'aner-anton@yandex.ru']);

        $owner = User::factory()->create([
            'email' => 'aner-anton@yandex.ru',
            'password' => Hash::make('secret-password'),
        ]);

        $other = User::factory()->create([
            'email' => 'cashier@example.com',
            'password' => Hash::make('secret-password'),
        ]);

        $this->postJson('/login', [
            'email' => $owner->email,
            'password' => 'secret-password',
        ])->assertOk()->assertJsonPath('dashboard_visible', true);

        auth()->logout();

        $this->postJson('/login', [
            'email' => $other->email,
            'password' => 'secret-password',
        ])->assertOk()->assertJsonPath('dashboard_visible', false);
    }
}
