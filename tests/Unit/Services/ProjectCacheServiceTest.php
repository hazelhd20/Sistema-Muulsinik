<?php

namespace Tests\Unit\Services;

use App\Models\Expense;
use App\Models\Project;
use App\Models\Requisition;
use App\Models\RequisitionItem;
use App\Services\ProjectCacheService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectCacheServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProjectCacheService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ProjectCacheService::class);
    }

    public function test_recalculates_total_expenses_correctly(): void
    {
        $user = \App\Models\User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $project = Project::create([
            'name' => 'Project Test',
            'client' => 'Client X',
            'status' => 'activo',
            'total_expenses_cache' => 0
        ]);

        // Direct expense
        Expense::create([
            'project_id' => $project->id,
            'date' => now(),
            'concept' => 'Gasto directo',
            'amount' => 100.50,
            'user_id' => $user->id
        ]);

        // Approved requisition
        $requisition = Requisition::create([
            'project_id' => $project->id,
            'number' => 'REQ-0001',
            'status' => 'aprobada',
            'created_by' => $user->id,
            'date' => now(),
        ]);

        RequisitionItem::create([
            'requisition_id' => $requisition->id,
            'description' => 'Item',
            'quantity' => 2,
            'unit_price' => 50, // 100 total
            'tax_amount' => 16, // 116 total
        ]);

        $this->service->recalculateTotalExpenses($project);

        $this->assertEquals(216.50, $project->fresh()->total_expenses_cache);
    }
}
