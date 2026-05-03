<?php

namespace App\Livewire\Documents;

use App\Models\Document;
use App\Models\Project;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class DocumentIndex extends Component
{
    use WithPagination, WithFileUploads;

    public string $search = '';
    public string $projectFilter = '';
    public string $categoryFilter = '';
    public bool $showUploadModal = false;

    // Campos del formulario
    public string $docName = '';
    public $docProjectId = '';
    public string $docCategory = 'otros';
    public $docFile = null;

    protected array $categoryLabels = [
        'contratos' => 'Contratos',
        'planos' => 'Planos',
        'permisos' => 'Permisos',
        'cotizaciones' => 'Cotizaciones',
        'otros' => 'Otros',
    ];

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function openUploadModal(): void
    {
        $this->resetForm();
        $this->showUploadModal = true;
    }

    public function uploadDocument(): void
    {
        $this->validate([
            'docName' => 'required|min:3|max:255',
            'docProjectId' => 'required|exists:projects,id',
            'docCategory' => 'required|in:contratos,planos,permisos,cotizaciones,otros',
            'docFile' => 'required|file|max:51200|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,dwg,dxf',
        ]);

        $filePath = $this->docFile->store('documents', 'public');

        // Determinar versión (RF-DOC-03)
        $latestVersion = Document::where('project_id', $this->docProjectId)
            ->where('name', $this->docName)
            ->max('version') ?? 0;

        Document::create([
            'project_id' => $this->docProjectId,
            'name' => $this->docName,
            'category' => $this->docCategory,
            'file_path' => $filePath,
            'version' => $latestVersion + 1,
            'uploaded_by' => auth()->id(),
        ]);

        $this->showUploadModal = false;
        $this->resetForm();
        session()->flash('success', 'Documento subido correctamente.');
    }

    public function deleteDocument(int $id): void
    {
        Document::findOrFail($id)->delete();
        session()->flash('success', 'Documento eliminado.');
    }

    private function resetForm(): void
    {
        $this->docName = '';
        $this->docProjectId = '';
        $this->docCategory = 'otros';
        $this->docFile = null;
    }

    #[Layout('components.layouts.app')]
    #[Title('Documentos')]
    public function render()
    {
        $documents = Document::with(['project', 'uploader'])
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->when($this->projectFilter, fn ($q) => $q->where('project_id', $this->projectFilter))
            ->when($this->categoryFilter, fn ($q) => $q->where('category', $this->categoryFilter))
            ->latest()
            ->paginate(15);

        $projects = Project::orderBy('name')->get();
        $categoryLabels = $this->categoryLabels;

        return view('livewire.documents.document-index', compact('documents', 'projects', 'categoryLabels'));
    }
}
