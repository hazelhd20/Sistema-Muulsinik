<?php

namespace App\Repositories;

use App\DTOs\ExpenseDTO;
use App\Helpers\FileHelpers;
use App\Models\Expense;
use App\Models\ExpenseAllocation;
use App\Models\Project;
use App\Models\User;
use App\Notifications\BudgetAlert;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class ExpenseRepository
{
    /**
     * Creates an expense, handles file validation/upload,
     * distributes it across projects if requested, and triggers alerts.
     */
    public function create(ExpenseDTO $dto): Expense
    {
        return DB::transaction(function () use ($dto) {
            $receiptPath = null;
            
            if ($dto->receipt_file) {
                // Validate Magic Bytes before saving
                if (!FileHelpers::validateMagicBytes($dto->receipt_file->getRealPath())) {
                    throw new Exception('El archivo del comprobante no tiene un formato válido (Magic Bytes mismatch). Posible archivo corrupto o malicioso.');
                }
                $receiptPath = $dto->receipt_file->store('receipts', 'public');
            }

            $expenseData = $dto->toArray();
            $expenseData['project_id'] = $dto->is_distributed ? null : $dto->project_id;
            $expenseData['receipt_file'] = $receiptPath;

            $expense = Expense::create($expenseData);

            if ($dto->is_distributed) {
                $this->distributeExpense($expense, $dto->amount);
            } else {
                if ($expense->project) {
                    $this->checkBudgetAlerts($expense->project);
                }
            }

            return $expense;
        });
    }

    /**
     * Deletes a single expense.
     */
    public function delete(int $id): bool
    {
        return Expense::findOrFail($id)->delete();
    }

    /**
     * Bulk deletes expenses.
     */
    public function bulkDelete(array $ids): void
    {
        if (!empty($ids)) {
            Expense::whereIn('id', $ids)->delete();
        }
    }

    /**
     * Distributes the expense evenly across all active projects.
     */
    private function distributeExpense(Expense $expense, float $totalAmount): void
    {
        $activeProjects = Project::where('status', 'activo')->get();
        $count = $activeProjects->count();

        if ($count > 0) {
            $amountPerProject = round($totalAmount / $count, 2);
            $percentage = round(100 / $count, 2);

            foreach ($activeProjects as $project) {
                ExpenseAllocation::create([
                    'expense_id' => $expense->id,
                    'project_id' => $project->id,
                    'amount' => $amountPerProject,
                    'percentage' => $percentage,
                ]);

                // The allocation creation will automatically trigger the project's cache recalculation
                // via ExpenseAllocation observer/model events, but we need the fresh data for the alert.
                $project->refresh(); 
                $this->checkBudgetAlerts($project);
            }
        }
    }

    /**
     * Checks budget thresholds and sends notifications if needed.
     */
    private function checkBudgetAlerts(Project $project): void
    {
        $percent = $project->budget_used_percent;

        if ($percent >= 80) { // Database alert threshold
            $severity = $percent >= 100 ? 'danger' : 'warning';
            $admins = User::with('role')->get()->filter(fn ($u) => $u->hasPermission('gastos.ver') || $u->hasPermission('*'));
            Notification::send($admins, new BudgetAlert($project, $percent, $severity));
        }
    }
}
