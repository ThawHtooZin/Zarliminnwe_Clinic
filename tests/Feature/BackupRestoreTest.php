<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\ProductCategory;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class BackupRestoreTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_cashier_cannot_access_backup_restore(): void
    {
        $cashier = User::factory()->create(['role' => User::ROLE_CASHIER]);

        $this->actingAs($cashier)
            ->get(route('backup-restore.index'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_admin_can_view_backup_restore_page(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->get(route('backup-restore.index'))
            ->assertOk()
            ->assertSee('Backup & Restore')
            ->assertSee('Product Catalog')
            ->assertSee('Full database');
    }

    public function test_suppliers_csv_export_import_round_trip(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        Supplier::query()->create([
            'name' => 'Alpha Pharma',
            'phone' => '091',
            'email' => 'alpha@test.com',
            'is_active' => true,
        ]);

        $csv = $this->actingAs($admin)
            ->get(route('backup-restore.export.csv', 'suppliers'))
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString('#TABLE:suppliers', $csv);
        $this->assertStringContainsString('Alpha Pharma', $csv);

        Supplier::query()->delete();

        $file = UploadedFile::fake()->createWithContent('suppliers.csv', $csv);

        $this->actingAs($admin)
            ->post(route('backup-restore.import', 'suppliers'), ['file' => $file])
            ->assertRedirect(route('backup-restore.index'))
            ->assertSessionHas('status');

        $this->assertTrue(Supplier::query()->where('name', 'Alpha Pharma')->exists());

        $this->assertTrue(
            AuditLog::query()->where('action', 'backup.dataset.imported')->exists(),
        );
    }

    public function test_catalog_csv_export_after_seed(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->assertTrue(ProductCategory::query()->exists());

        $csv = $this->actingAs($admin)
            ->get(route('backup-restore.export.csv', 'catalog'))
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString('#TABLE:product_categories', $csv);
    }
}
