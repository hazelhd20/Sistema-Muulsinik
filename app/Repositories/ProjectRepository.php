<?php

namespace App\Repositories;

use App\DTOs\ProjectDTO;
use App\Models\Project;
use App\Models\Expense;
use App\Models\Quotation;
use App\Models\Requisition;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProjectRepository
{
    /**
     * Create a new project.
     */
    public function create(ProjectDTO $dto): Project
    {
        return Project::create($dto->toArray());
    }

    /**
     * Update an existing project.
     */
    public function update(int $id, ProjectDTO $dto): Project
    {
        $project = Project::findOrFail($id);
        $project->update($dto->toArray());
        
        return $project;
    }

    /**
     * Delete a single project.
     * Checks for dependencies before deleting.
     * Returns true if deleted, false if dependencies exist.
     */
    public function delete(int $id): bool
    {
        if ($this->hasDependencies($id)) {
            return false;
        }

        return Project::findOrFail($id)->delete();
    }

    /**
     * Bulk delete projects.
     * Returns array of successfully deleted project IDs.
     */
    public function bulkDelete(array $ids): array
    {
        $projectsToDelete = [];

        foreach ($ids as $id) {
            if (!$this->hasDependencies($id)) {
                $projectsToDelete[] = $id;
            }
        }

        if (!empty($projectsToDelete)) {
            Project::whereIn('id', $projectsToDelete)->get()->each->delete();
        }

        return $projectsToDelete;
    }

    /**
     * Check if a project has any dependencies (expenses, requisitions, quotations).
     */
    private function hasDependencies(int $projectId): bool
    {
        return Requisition::where('project_id', $projectId)->exists() ||
               Expense::where('project_id', $projectId)->exists() ||
               Quotation::where('project_id', $projectId)->exists();
    }
}
