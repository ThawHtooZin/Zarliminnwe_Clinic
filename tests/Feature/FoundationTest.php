<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_guest_is_redirected_to_login_for_dashboard(): void
    {
        $this->get('/dashboard')
            ->assertRedirect('/login');
    }

    public function test_user_can_login_and_view_dashboard(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'password',
            'role' => User::ROLE_ADMIN,
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);

        $this->get('/dashboard')
            ->assertOk()
            ->assertSee('Dashboard')
            ->assertSee('Go to POS')
            ->assertDontSee('New Purchase Receipt');
    }

    public function test_user_has_minimal_role_support(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_STOCK_MANAGER,
        ]);

        $this->assertTrue($user->hasRole(User::ROLE_STOCK_MANAGER));
        $this->assertFalse($user->isAdmin());
    }
}
