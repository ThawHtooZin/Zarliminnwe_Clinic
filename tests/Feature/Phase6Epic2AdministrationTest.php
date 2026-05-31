<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase6Epic2AdministrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_roles_permissions_and_users_tables_exist(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->assertDatabaseHas('roles', ['slug' => Role::SLUG_ADMIN]);
        $this->assertGreaterThan(0, Role::query()->count());
        $this->assertGreaterThan(0, \App\Models\Permission::query()->count());
        $this->assertNotNull($admin->role_id);
    }

    public function test_cashier_cannot_access_admin_users(): void
    {
        $cashier = User::factory()->create(['role' => User::ROLE_CASHIER]);

        $this->actingAs($cashier)
            ->get(route('admin.users.index'))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('error', 'You do not have access to this page.');
    }

    public function test_admin_can_create_update_and_deactivate_user(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $cashierRoleId = Role::query()->where('slug', Role::SLUG_CASHIER)->value('id');

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Front Desk',
                'email' => 'frontdesk@example.com',
                'role_id' => $cashierRoleId,
                'is_active' => '1',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertRedirect(route('admin.users.index'));

        $user = User::query()->where('email', 'frontdesk@example.com')->firstOrFail();

        $this->actingAs($admin)
            ->put(route('admin.users.update', $user), [
                'name' => 'Front Desk Updated',
                'email' => 'frontdesk@example.com',
                'role_id' => $cashierRoleId,
                'is_active' => '0',
            ])
            ->assertRedirect(route('admin.users.index'));

        $user->refresh();

        $this->assertSame('Front Desk Updated', $user->name);
        $this->assertFalse($user->is_active);
        $this->assertTrue(AuditLog::query()->where('action', 'user.created')->exists());
        $this->assertTrue(AuditLog::query()->where('action', 'user.updated')->exists());
    }

    public function test_inactive_user_cannot_login(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_CASHIER,
            'is_active' => false,
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_admin_can_reset_user_password(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $user = User::factory()->create(['role' => User::ROLE_CASHIER]);

        $this->actingAs($admin)
            ->post(route('admin.users.reset-password', $user), [
                'password' => 'reset-password',
                'password_confirmation' => 'reset-password',
            ])
            ->assertRedirect(route('admin.users.edit', $user));

        $this->assertTrue(AuditLog::query()->where('action', 'user.password_reset')->exists());

        $this->post('/logout');

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'reset-password',
        ])->assertRedirect(route('dashboard'));
    }

    public function test_admin_can_update_role_permissions(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $cashierRole = Role::query()->where('slug', Role::SLUG_CASHIER)->firstOrFail();
        $posPermissionId = \App\Models\Permission::query()->where('slug', 'route.sales.pos')->value('id');
        $posScreenPermissionId = \App\Models\Permission::query()->where('slug', 'screen.sales.pos')->value('id');

        $this->actingAs($admin)
            ->put(route('admin.roles.update', $cashierRole), [
                'permission_ids' => [$posScreenPermissionId, $posPermissionId],
            ])
            ->assertRedirect(route('admin.roles.edit', $cashierRole));

        $this->assertEqualsCanonicalizing(
            [$posScreenPermissionId, $posPermissionId],
            $cashierRole->fresh()->permissions()->pluck('permissions.id')->all()
        );
        $this->assertTrue(AuditLog::query()->where('action', 'role.permissions_updated')->exists());
    }

    public function test_stock_manager_still_blocked_from_pos_route(): void
    {
        $stockManager = User::factory()->create(['role' => User::ROLE_STOCK_MANAGER]);

        $this->actingAs($stockManager)
            ->get(route('sales.pos'))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('error', 'You do not have access to this page.');
    }

    public function test_route_requires_related_screen_permission(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $cashierRole = Role::query()->where('slug', Role::SLUG_CASHIER)->firstOrFail();

        $routePermissionId = \App\Models\Permission::query()->where('slug', 'route.sales.pos')->value('id');

        $this->actingAs($admin)
            ->put(route('admin.roles.update', $cashierRole), [
                'permission_ids' => [$routePermissionId],
            ])
            ->assertRedirect(route('admin.roles.edit', $cashierRole));

        $cashier = User::factory()->create(['role_id' => $cashierRole->id]);

        $this->actingAs($cashier)
            ->get(route('sales.pos'))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('error', 'You do not have access to this page.');
    }

    public function test_role_update_auto_removes_route_when_related_screen_not_selected(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $cashierRole = Role::query()->where('slug', Role::SLUG_CASHIER)->firstOrFail();
        $posRoutePermissionId = \App\Models\Permission::query()->where('slug', 'route.sales.pos')->value('id');

        $this->actingAs($admin)
            ->put(route('admin.roles.update', $cashierRole), [
                'permission_ids' => [$posRoutePermissionId],
            ])
            ->assertRedirect(route('admin.roles.edit', $cashierRole));

        $this->assertSame([], $cashierRole->fresh()->permissions()->pluck('permissions.id')->all());
    }

    public function test_admin_sidebar_shows_user_and_role_management_links(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Users')
            ->assertSee('Roles & Permissions');
    }
}
