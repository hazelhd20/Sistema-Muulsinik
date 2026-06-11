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
}
