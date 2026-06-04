<?php

namespace Tests\Feature;

use App\Models\ExpenseCategory;
use App\Models\IncomeCategory;
use App\Models\User;
use App\Support\NavigationMenu;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase6Epic1FoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_navigation_config_defines_required_groups(): void
    {
        $groups = config('navigation.groups');

        $this->assertArrayHasKey('main', $groups);
        $this->assertArrayHasKey('management', $groups);
        $this->assertArrayHasKey('configurations', $groups);
        $this->assertArrayHasKey('finance', $groups);
        $this->assertArrayHasKey('reports', $groups);

        $this->assertSame('Main Features', $groups['main']['label']);
        $this->assertSame('Management', $groups['management']['label']);
        $this->assertSame('Configurations', $groups['configurations']['label']);
        $this->assertSame('Finance', $groups['finance']['label']);
        $this->assertSame('Reports', $groups['reports']['label']);
    }

    public function test_navigation_menu_filters_items_by_role(): void
    {
        $cashier = User::factory()->create(['role' => User::ROLE_CASHIER]);
        $groups = NavigationMenu::groupsFor($cashier);

        $labels = collect($groups)
            ->flatMap(fn (array $group): array => collect($group['items'])->pluck('label')->all())
            ->all();

        $this->assertContains('POS', $labels);
        $this->assertContains('Income', $labels);
        $this->assertNotContains('Products', $labels);
        $this->assertNotContains('Stock Reports', $labels);
        $this->assertNotContains('Users', $labels);
    }

    public function test_admin_dashboard_renders_grouped_sidebar(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Main Features')
            ->assertSee('Management')
            ->assertSee('Configurations')
            ->assertSee('Finance')
            ->assertSee('Reports')
            ->assertSee('Go to POS')
            ->assertSee('Purchase Receipts')
            ->assertSee('Users');
    }

    public function test_stock_manager_dashboard_hides_finance_and_pos_links(): void
    {
        $stockManager = User::factory()->create(['role' => User::ROLE_STOCK_MANAGER]);

        $this->actingAs($stockManager)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Management')
            ->assertSee('Stock Reports')
            ->assertDontSee('Go to POS')
            ->assertDontSee('Finance Summary')
            ->assertDontSee(route('patients.index'));
    }

    public function test_database_seeder_seeds_finance_categories(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertTrue(IncomeCategory::query()->where('name', 'Consultation Fee')->exists());
        $this->assertTrue(ExpenseCategory::query()->where('name', 'Rent')->exists());
    }
}
