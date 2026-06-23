<?php

namespace Tests\Feature;

use App\Livewire\Dashboard;
use App\Livewire\Expenses\ExpenseIndex;
use App\Livewire\Measures\MeasureIndex;
use App\Livewire\Notifications\NotificationIndex;
use App\Livewire\Products\CategoryIndex;
use App\Livewire\Products\ProductIndex;
use App\Livewire\QuickBudgets\QuickBudgetIndex;
use App\Livewire\Requisitions\RequisitionIndex;
use App\Livewire\Users\UserIndex;
use App\Models\Project;
use App\Models\Requisition;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LivewireIndexViewsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_dashboard_renders()
    {
        $user = User::first();
        $this->actingAs($user);

        Livewire::test(Dashboard::class)
            ->assertStatus(200);
    }

    public function test_user_index_renders()
    {
        $user = User::first();
        $this->actingAs($user);

        Livewire::test(UserIndex::class)
            ->assertStatus(200);
    }

    public function test_user_bulk_delete_invalidates_cache_and_enforces_permissions()
    {
        $admin = User::first();
        
        $user1 = User::create([
            'name' => 'Usuario Uno',
            'email' => 'uno@muulsinik.com',
            'password' => bcrypt('password'),
            'role_id' => $admin->role_id,
            'active' => true,
        ]);
        
        $user2 = User::create([
            'name' => 'Usuario Dos',
            'email' => 'dos@muulsinik.com',
            'password' => bcrypt('password'),
            'role_id' => $admin->role_id,
            'active' => true,
        ]);

        // Let's try to delete them as an unauthorized user
        $roleWithoutDelete = \App\Models\Role::create([
            'name' => 'Sin Borrado',
            'permissions' => ['usuarios.ver']
        ]);
        $unauthorizedUser = User::create([
            'name' => 'No Borra',
            'email' => 'noborra@muulsinik.com',
            'password' => bcrypt('password'),
            'role_id' => $roleWithoutDelete->id,
            'active' => true,
        ]);

        // Populate the cache after all users are seeded
        cache()->remember('users.all.array', 3600, fn() => User::orderBy('name')->pluck('name', 'id')->toArray());
        $this->assertTrue(cache()->has('users.all.array'));

        $this->actingAs($unauthorizedUser);

        Livewire::test(UserIndex::class)
            ->set('selectedRows', [(string) $user1->id, (string) $user2->id])
            ->call('bulkDelete');

        // Verify users are not deleted and cache is intact
        $this->assertDatabaseHas('users', ['id' => $user1->id]);
        $this->assertDatabaseHas('users', ['id' => $user2->id]);
        $this->assertTrue(cache()->has('users.all.array'));

        // Log in as admin and delete them
        $this->actingAs($admin);

        Livewire::test(UserIndex::class)
            ->set('selectedRows', [(string) $user1->id, (string) $user2->id])
            ->call('bulkDelete')
            ->assertSet('selectedRows', []);

        // Verify users are soft deleted and cache is cleared
        $this->assertNotNull($user1->fresh()->deleted_at);
        $this->assertNotNull($user2->fresh()->deleted_at);
        $this->assertFalse(cache()->has('users.all.array'));
    }

    public function test_product_index_renders()
    {
        $user = User::first();
        $this->actingAs($user);

        Livewire::test(ProductIndex::class)
            ->assertStatus(200);
    }

    public function test_category_index_renders()
    {
        $user = User::first();
        $this->actingAs($user);

        Livewire::test(CategoryIndex::class)
            ->assertStatus(200);
    }

    public function test_measure_index_renders()
    {
        $user = User::first();
        $this->actingAs($user);

        Livewire::test(MeasureIndex::class)
            ->assertStatus(200);
    }

    public function test_expense_index_renders()
    {
        $user = User::first();
        $this->actingAs($user);

        Livewire::test(ExpenseIndex::class)
            ->assertStatus(200);
    }

    public function test_quick_budget_index_renders()
    {
        $user = User::first();
        $this->actingAs($user);

        Livewire::test(QuickBudgetIndex::class)
            ->assertStatus(200);
    }

    public function test_quick_budget_index_search_works_and_does_not_throw_sql_error()
    {
        $user = User::first();
        $this->actingAs($user);

        Livewire::test(QuickBudgetIndex::class)
            ->set('search', 'TestSearchQuery')
            ->assertStatus(200);
    }

    public function test_quick_budget_unauthorized_user_cannot_access_index_or_wizard()
    {
        $roleWithoutPermissions = \App\Models\Role::create([
            'name' => 'Sin Permisos',
            'permissions' => []
        ]);
        $user = User::create([
            'name' => 'Usuario Limitado',
            'email' => 'limitado@muulsinik.com',
            'password' => bcrypt('password'),
            'role_id' => $roleWithoutPermissions->id,
            'active' => true,
        ]);

        $this->actingAs($user);

        $this->get(route('cotizador.index'))->assertStatus(403);
        $this->get(route('cotizador.wizard'))->assertStatus(403);

        Livewire::test(QuickBudgetIndex::class)
            ->assertStatus(403);

        Livewire::test(\App\Livewire\QuickBudgets\QuickBudgetWizard::class)
            ->assertStatus(403);
    }

    public function test_quick_budget_delete_permissions()
    {
        $admin = User::first();

        $budget1 = \App\Models\QuickBudget::create([
            'title' => 'Cotizacion 1',
            'created_by' => $admin->id,
        ]);

        $roleWithoutDelete = \App\Models\Role::create([
            'name' => 'Solo Creador',
            'permissions' => ['cotizaciones.ver', 'cotizaciones.crear']
        ]);
        $user = User::create([
            'name' => 'Usuario Creador',
            'email' => 'creador@muulsinik.com',
            'password' => bcrypt('password'),
            'role_id' => $roleWithoutDelete->id,
            'active' => true,
        ]);

        $this->actingAs($user);

        Livewire::test(QuickBudgetIndex::class)
            ->call('deleteBudget', $budget1->id);

        $this->assertNull($budget1->fresh()->deleted_at);

        Livewire::test(QuickBudgetIndex::class)
            ->set('selectedRows', [(string) $budget1->id])
            ->call('bulkDelete');

        $this->assertNull($budget1->fresh()->deleted_at);

        $this->actingAs($admin);

        Livewire::test(QuickBudgetIndex::class)
            ->call('deleteBudget', $budget1->id);

        $this->assertNotNull($budget1->fresh()->deleted_at);

        $budget2 = \App\Models\QuickBudget::create([
            'title' => 'Cotizacion 2',
            'created_by' => $admin->id,
        ]);

        Livewire::test(QuickBudgetIndex::class)
            ->set('selectedRows', [(string) $budget2->id])
            ->call('bulkDelete');

        $this->assertNotNull($budget2->fresh()->deleted_at);
    }

    public function test_notification_index_renders()
    {
        $user = User::first();
        $this->actingAs($user);

        Livewire::test(NotificationIndex::class)
            ->assertStatus(200);
    }

    public function test_requisition_index_renders()
    {
        $user = User::first();
        $this->actingAs($user);

        Livewire::test(RequisitionIndex::class)
            ->assertStatus(200);
    }

    public function test_requisitions_can_be_approved_in_bulk()
    {
        $user = User::first();
        $this->actingAs($user);

        $project = Project::create([
            'name' => 'Proyecto Test',
            'status' => 'activo',
        ]);

        $req1 = Requisition::create([
            'project_id' => $project->id,
            'status' => 'pendiente',
            'created_by' => $user->id,
            'date' => now(),
        ]);
        $req2 = Requisition::create([
            'project_id' => $project->id,
            'status' => 'pendiente',
            'created_by' => $user->id,
            'date' => now(),
        ]);

        Livewire::test(RequisitionIndex::class)
            ->set('selectedRows', [(string) $req1->id, (string) $req2->id])
            ->call('approveSelected')
            ->assertSet('selectedRows', []);

        $this->assertEquals('aprobada', $req1->fresh()->status);
        $this->assertEquals('aprobada', $req2->fresh()->status);
    }

    public function test_requisitions_can_be_exported()
    {
        $user = User::first();
        $this->actingAs($user);

        $project = Project::create([
            'name' => 'Proyecto Test',
            'status' => 'activo',
        ]);

        $req = Requisition::create([
            'project_id' => $project->id,
            'status' => 'borrador',
            'created_by' => $user->id,
            'date' => now(),
        ]);

        Livewire::test(RequisitionIndex::class)
            ->set('selectedRows', [(string) $req->id])
            ->call('exportCsvSummary')
            ->assertFileDownloaded();
    }

    public function test_modules_require_proper_permissions()
    {
        $roleWithoutPerms = \App\Models\Role::create([
            'name' => 'Sin Accesos',
            'permissions' => []
        ]);
        $limitedUser = User::create([
            'name' => 'Usuario Limitado',
            'email' => 'limitado@muulsinik.com',
            'password' => bcrypt('password'),
            'role_id' => $roleWithoutPerms->id,
            'active' => true,
        ]);

        $this->actingAs($limitedUser);

        // Accessing Projects, Expenses, Requisitions, Reports, Clients, Products, Suppliers, Categories, Measures
        Livewire::test(\App\Livewire\Projects\ProjectIndex::class)->assertStatus(403);
        Livewire::test(ExpenseIndex::class)->assertStatus(403);
        Livewire::test(RequisitionIndex::class)->assertStatus(403);
        Livewire::test(\App\Livewire\Reports\ReportIndex::class)->assertStatus(403);
        Livewire::test(\App\Livewire\Clients\ClientIndex::class)->assertStatus(403);
        Livewire::test(ProductIndex::class)->assertStatus(403);
        Livewire::test(CategoryIndex::class)->assertStatus(403);
        Livewire::test(MeasureIndex::class)->assertStatus(403);
        Livewire::test(\App\Livewire\Suppliers\SupplierIndex::class)->assertStatus(403);
        Livewire::test(\App\Livewire\Requisitions\ManualRequisition::class)->assertStatus(403);
        Livewire::test(\App\Livewire\Requisitions\QuotationWizard::class)->assertStatus(403);
    }

    public function test_requisition_bulk_delete_invalidates_dashboard_cache()
    {
        $user = User::first();
        $this->actingAs($user);

        $project = Project::create([
            'name' => 'Proyecto Test Cache',
            'status' => 'activo',
        ]);

        $req1 = Requisition::create([
            'project_id' => $project->id,
            'status' => 'borrador',
            'created_by' => $user->id,
            'date' => now(),
        ]);
        
        $req2 = Requisition::create([
            'project_id' => $project->id,
            'status' => 'borrador',
            'created_by' => $user->id,
            'date' => now(),
        ]);

        // Populate dashboard stats cache
        \Illuminate\Support\Facades\Cache::remember('dashboard_global_stats', 3600, function () {
            return [
                'totalProjects' => Project::count(),
                'activeProjects' => Project::where('status', 'activo')->count(),
                'pendingRequisitions' => Requisition::where('status', 'pendiente')->count(),
                'approvedRequisitions' => Requisition::where('status', 'aprobada')->count(),
                'totalSuppliers' => \App\Models\Supplier::count(),
            ];
        });

        $this->assertTrue(cache()->has('dashboard_global_stats'));

        // Call deleteSelected to delete draft requisitions as admin
        Livewire::test(RequisitionIndex::class)
            ->set('selectedRows', [(string) $req1->id, (string) $req2->id])
            ->call('deleteSelected')
            ->assertSet('selectedRows', []);

        // Verify requisitions are deleted and cache is cleared
        $this->assertSoftDeleted($req1);
        $this->assertSoftDeleted($req2);
        $this->assertFalse(cache()->has('dashboard_global_stats'));
    }

    public function test_supplier_bulk_delete_invalidates_cache_and_enforces_permissions()
    {
        $admin = User::first();
        
        $supplier1 = \App\Models\Supplier::create([
            'trade_name' => 'Proveedor Uno',
            'active' => true,
        ]);
        
        $supplier2 = \App\Models\Supplier::create([
            'trade_name' => 'Proveedor Dos',
            'active' => true,
        ]);

        // Create a role that does NOT have proveedores.eliminar
        $roleWithoutDelete = \App\Models\Role::create([
            'name' => 'Sin Borrar Proveedores',
            'permissions' => ['proveedores.ver']
        ]);
        $unauthorizedUser = User::create([
            'name' => 'No Borra Proveedor',
            'email' => 'noborraprov@muulsinik.com',
            'password' => bcrypt('password'),
            'role_id' => $roleWithoutDelete->id,
            'active' => true,
        ]);

        // Populate the cache keys
        cache()->put('dashboard_global_stats', ['some' => 'data'], 3600);
        cache()->put('catalog_suppliers', ['some' => 'data'], 3600);
        cache()->put('suppliers.all.array', ['some' => 'data'], 3600);

        $this->assertTrue(cache()->has('dashboard_global_stats'));
        $this->assertTrue(cache()->has('catalog_suppliers'));
        $this->assertTrue(cache()->has('suppliers.all.array'));

        // Act as unauthorized user and try to delete
        $this->actingAs($unauthorizedUser);

        Livewire::test(\App\Livewire\Suppliers\SupplierIndex::class)
            ->set('selectedRows', [(string) $supplier1->id, (string) $supplier2->id])
            ->call('bulkDelete');

        // Verify they are not deleted
        $this->assertDatabaseHas('suppliers', ['id' => $supplier1->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('suppliers', ['id' => $supplier2->id, 'deleted_at' => null]);
        $this->assertTrue(cache()->has('dashboard_global_stats'));
        $this->assertTrue(cache()->has('catalog_suppliers'));
        $this->assertTrue(cache()->has('suppliers.all.array'));

        // Act as admin and delete
        $this->actingAs($admin);

        Livewire::test(\App\Livewire\Suppliers\SupplierIndex::class)
            ->set('selectedRows', [(string) $supplier1->id, (string) $supplier2->id])
            ->call('bulkDelete')
            ->assertSet('selectedRows', []);

        // Verify soft deleted and cache cleared
        $this->assertSoftDeleted($supplier1);
        $this->assertSoftDeleted($supplier2);
        $this->assertFalse(cache()->has('dashboard_global_stats'));
        $this->assertFalse(cache()->has('catalog_suppliers'));
        $this->assertFalse(cache()->has('suppliers.all.array'));
    }

    public function test_report_index_renders_for_authorized_user()
    {
        $user = User::first();
        $this->actingAs($user);

        // Test with different periods and tabs to execute various ReportService queries
        Livewire::test(\App\Livewire\Reports\ReportIndex::class)
            ->assertStatus(200)
            ->set('activeTab', 'overview')
            ->assertStatus(200)
            ->set('period', 'year')
            ->assertStatus(200)
            ->set('activeTab', 'suppliers')
            ->assertStatus(200)
            ->set('activeTab', 'vendors')
            ->assertStatus(200)
            ->set('activeTab', 'products')
            ->assertStatus(200);
    }

    public function test_client_bulk_delete_enforces_permissions()
    {
        $admin = User::first();
        
        $client1 = \App\Models\Client::create([
            'name' => 'Cliente Uno',
            'active' => true,
        ]);
        
        $client2 = \App\Models\Client::create([
            'name' => 'Cliente Dos',
            'active' => true,
        ]);

        // Create a role that does NOT have catalogos.eliminar
        $roleWithoutDelete = \App\Models\Role::create([
            'name' => 'Sin Borrar Catalogos',
            'permissions' => ['catalogos.ver']
        ]);
        $unauthorizedUser = User::create([
            'name' => 'No Borra Cliente',
            'email' => 'noborracliente@muulsinik.com',
            'password' => bcrypt('password'),
            'role_id' => $roleWithoutDelete->id,
            'active' => true,
        ]);

        // Act as unauthorized user and try to delete
        $this->actingAs($unauthorizedUser);

        Livewire::test(\App\Livewire\Clients\ClientIndex::class)
            ->set('selectedRows', [(string) $client1->id, (string) $client2->id])
            ->call('bulkDelete');

        // Verify they are not deleted
        $this->assertDatabaseHas('clients', ['id' => $client1->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('clients', ['id' => $client2->id, 'deleted_at' => null]);

        // Act as admin and delete
        $this->actingAs($admin);

        Livewire::test(\App\Livewire\Clients\ClientIndex::class)
            ->set('selectedRows', [(string) $client1->id, (string) $client2->id])
            ->call('bulkDelete')
            ->assertSet('selectedRows', []);

        // Verify soft deleted
        $this->assertSoftDeleted($client1);
        $this->assertSoftDeleted($client2);
    }

    public function test_product_bulk_delete_enforces_permissions()
    {
        $admin = User::first();
        
        $category = \App\Models\Category::first() ?? \App\Models\Category::create(['name' => 'Cat Test']);
        $measure = \App\Models\Measure::first() ?? \App\Models\Measure::create(['name' => 'Pza', 'abbreviation' => 'Pza']);

        $product1 = \App\Models\Product::create([
            'canonical_name' => 'Producto Uno',
            'category_id' => $category->id,
            'measure_id' => $measure->id,
            'item_type' => 'material',
        ]);
        
        $product2 = \App\Models\Product::create([
            'canonical_name' => 'Producto Dos',
            'category_id' => $category->id,
            'measure_id' => $measure->id,
            'item_type' => 'material',
        ]);

        // Create a role that does NOT have productos.eliminar
        $roleWithoutDelete = \App\Models\Role::create([
            'name' => 'Sin Borrar Productos',
            'permissions' => ['productos.ver']
        ]);
        $unauthorizedUser = User::create([
            'name' => 'No Borra Producto',
            'email' => 'noborraprod@muulsinik.com',
            'password' => bcrypt('password'),
            'role_id' => $roleWithoutDelete->id,
            'active' => true,
        ]);

        // Act as unauthorized user and try to delete
        $this->actingAs($unauthorizedUser);

        Livewire::test(\App\Livewire\Products\ProductIndex::class)
            ->set('selectedRows', [(string) $product1->id, (string) $product2->id])
            ->call('bulkDelete');

        // Verify they are not deleted
        $this->assertDatabaseHas('products', ['id' => $product1->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('products', ['id' => $product2->id, 'deleted_at' => null]);

        // Act as admin and delete
        $this->actingAs($admin);

        Livewire::test(\App\Livewire\Products\ProductIndex::class)
            ->set('selectedRows', [(string) $product1->id, (string) $product2->id])
            ->call('bulkDelete')
            ->assertSet('selectedRows', []);

        // Verify soft deleted
        $this->assertSoftDeleted($product1);
        $this->assertSoftDeleted($product2);
    }

    public function test_measure_bulk_delete_invalidates_cache_and_enforces_permissions()
    {
        $admin = User::first();
        
        $measure1 = \App\Models\Measure::create([
            'name' => 'Metro Lineal',
            'abbreviation' => 'm',
        ]);
        
        $measure2 = \App\Models\Measure::create([
            'name' => 'Kilogramo',
            'abbreviation' => 'kg',
        ]);

        // Create a role that does NOT have catalogos.editar
        $roleWithoutDelete = \App\Models\Role::create([
            'name' => 'Sin Editar Catalogos',
            'permissions' => ['catalogos.ver']
        ]);
        $unauthorizedUser = User::create([
            'name' => 'No Borra Medida',
            'email' => 'noborramedida@muulsinik.com',
            'password' => bcrypt('password'),
            'role_id' => $roleWithoutDelete->id,
            'active' => true,
        ]);

        // Populate the cache key
        cache()->put('catalog_measures', ['some' => 'data'], 3600);
        $this->assertTrue(cache()->has('catalog_measures'));

        // Act as unauthorized user and try to delete
        $this->actingAs($unauthorizedUser);

        Livewire::test(\App\Livewire\Measures\MeasureIndex::class)
            ->set('selectedRows', [(string) $measure1->id, (string) $measure2->id])
            ->call('bulkDelete');

        // Verify they are not deleted
        $this->assertDatabaseHas('measures', ['id' => $measure1->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('measures', ['id' => $measure2->id, 'deleted_at' => null]);
        $this->assertTrue(cache()->has('catalog_measures'));

        // Act as admin and delete
        $this->actingAs($admin);

        Livewire::test(\App\Livewire\Measures\MeasureIndex::class)
            ->set('selectedRows', [(string) $measure1->id, (string) $measure2->id])
            ->call('bulkDelete')
            ->assertSet('selectedRows', []);

        // Verify soft deleted and cache cleared
        $this->assertSoftDeleted($measure1);
        $this->assertSoftDeleted($measure2);
        $this->assertFalse(cache()->has('catalog_measures'));
    }

    public function test_category_bulk_delete_invalidates_cache_and_enforces_permissions()
    {
        $admin = User::first();
        
        $category1 = \App\Models\Category::create([
            'name' => 'Categoria Invalida Cache Uno',
        ]);
        
        $category2 = \App\Models\Category::create([
            'name' => 'Categoria Invalida Cache Dos',
        ]);

        // Create a role that does NOT have catalogos.editar
        $roleWithoutDelete = \App\Models\Role::create([
            'name' => 'Sin Editar Catalogos',
            'permissions' => ['catalogos.ver']
        ]);
        $unauthorizedUser = User::create([
            'name' => 'No Borra Categoria',
            'email' => 'noborracat@muulsinik.com',
            'password' => bcrypt('password'),
            'role_id' => $roleWithoutDelete->id,
            'active' => true,
        ]);

        // Populate the cache key
        cache()->put('catalog_categories', ['some' => 'data'], 3600);
        $this->assertTrue(cache()->has('catalog_categories'));

        // Act as unauthorized user and try to delete
        $this->actingAs($unauthorizedUser);

        Livewire::test(\App\Livewire\Products\CategoryIndex::class)
            ->set('selectedRows', [(string) $category1->id, (string) $category2->id])
            ->call('bulkDelete');

        // Verify they are not deleted
        $this->assertDatabaseHas('categories', ['id' => $category1->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('categories', ['id' => $category2->id, 'deleted_at' => null]);
        $this->assertTrue(cache()->has('catalog_categories'));

        // Act as admin and delete
        $this->actingAs($admin);

        Livewire::test(\App\Livewire\Products\CategoryIndex::class)
            ->set('selectedRows', [(string) $category1->id, (string) $category2->id])
            ->call('bulkDelete')
            ->assertSet('selectedRows', []);

        // Verify soft deleted and cache cleared
        $this->assertSoftDeleted($category1);
        $this->assertSoftDeleted($category2);
        $this->assertFalse(cache()->has('catalog_categories'));
    }
}





