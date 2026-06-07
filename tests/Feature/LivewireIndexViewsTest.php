<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;
use Database\Seeders\DatabaseSeeder;

class LivewireIndexViewsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_user_index_renders()
    {
        $user = User::first();
        $this->actingAs($user);

        Livewire::test(\App\Livewire\Users\UserIndex::class)
            ->assertStatus(200);
    }

    public function test_product_index_renders()
    {
        $user = User::first();
        $this->actingAs($user);

        Livewire::test(\App\Livewire\Products\ProductIndex::class)
            ->assertStatus(200);
    }

    public function test_category_index_renders()
    {
        $user = User::first();
        $this->actingAs($user);

        Livewire::test(\App\Livewire\Products\CategoryIndex::class)
            ->assertStatus(200);
    }

    public function test_measure_index_renders()
    {
        $user = User::first();
        $this->actingAs($user);

        Livewire::test(\App\Livewire\Measures\MeasureIndex::class)
            ->assertStatus(200);
    }

    public function test_expense_index_renders()
    {
        $user = User::first();
        $this->actingAs($user);

        Livewire::test(\App\Livewire\Expenses\ExpenseIndex::class)
            ->assertStatus(200);
    }

    public function test_quick_budget_index_renders()
    {
        $user = User::first();
        $this->actingAs($user);

        Livewire::test(\App\Livewire\QuickBudgets\QuickBudgetIndex::class)
            ->assertStatus(200);
    }

    public function test_notification_index_renders()
    {
        $user = User::first();
        $this->actingAs($user);

        Livewire::test(\App\Livewire\Notifications\NotificationIndex::class)
            ->assertStatus(200);
    }

    public function test_requisition_index_renders()
    {
        $user = User::first();
        $this->actingAs($user);

        Livewire::test(\App\Livewire\Requisitions\RequisitionIndex::class)
            ->assertStatus(200);
    }

    public function test_requisitions_can_be_approved_in_bulk()
    {
        $user = User::first();
        $this->actingAs($user);

        $project = \App\Models\Project::create([
            'name' => 'Proyecto Test',
            'status' => 'activo'
        ]);

        $req1 = \App\Models\Requisition::create([
            'project_id' => $project->id,
            'status' => 'pendiente',
            'created_by' => $user->id,
            'date' => now()
        ]);
        $req2 = \App\Models\Requisition::create([
            'project_id' => $project->id,
            'status' => 'pendiente',
            'created_by' => $user->id,
            'date' => now()
        ]);

        Livewire::test(\App\Livewire\Requisitions\RequisitionIndex::class)
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

        $project = \App\Models\Project::create([
            'name' => 'Proyecto Test',
            'status' => 'activo'
        ]);

        $req = \App\Models\Requisition::create([
            'project_id' => $project->id,
            'status' => 'borrador',
            'created_by' => $user->id,
            'date' => now()
        ]);

        Livewire::test(\App\Livewire\Requisitions\RequisitionIndex::class)
            ->set('selectedRows', [(string) $req->id])
            ->call('exportSelected')
            ->assertFileDownloaded();
    }
}
