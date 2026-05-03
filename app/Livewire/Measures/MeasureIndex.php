<?php

namespace App\Livewire\Measures;

use App\Models\Measure;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

class MeasureIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public string $name = '';
    public string $abbreviation = '';
    public ?int $editingId = null;
    public bool $showCreateModal = false;

    protected $rules = [
        'name' => 'required|string|max:255',
        'abbreviation' => 'nullable|string|max:50',
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->reset(['name', 'abbreviation', 'editingId']);
        $this->showCreateModal = true;
    }

    public function openEditModal(int $id): void
    {
        $measure = Measure::findOrFail($id);
        $this->editingId = $measure->id;
        $this->name = $measure->name;
        $this->abbreviation = $measure->abbreviation ?? '';
        $this->showCreateModal = true;
    }

    public function save(): void
    {
        $this->validate();

        if ($this->editingId) {
            Measure::findOrFail($this->editingId)->update([
                'name' => $this->name,
                'abbreviation' => $this->abbreviation ?: null,
            ]);
            session()->flash('success', 'Medida actualizada exitosamente.');
        } else {
            Measure::create([
                'name' => $this->name,
                'abbreviation' => $this->abbreviation ?: null,
            ]);
            session()->flash('success', 'Medida creada exitosamente.');
        }

        $this->reset(['name', 'abbreviation', 'editingId', 'showCreateModal']);
    }

    public function delete(int $id): void
    {
        Measure::findOrFail($id)->delete();
        session()->flash('success', 'Medida eliminada exitosamente.');
    }

    #[Layout('components.layouts.app')]
    #[Title('Catálogo de Medidas')]
    public function render()
    {
        $measures = Measure::where('name', 'like', '%' . $this->search . '%')
            ->orWhere('abbreviation', 'like', '%' . $this->search . '%')
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.measures.measure-index', compact('measures'));
    }
}
