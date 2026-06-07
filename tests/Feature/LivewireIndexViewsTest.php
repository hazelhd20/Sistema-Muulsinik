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
}
