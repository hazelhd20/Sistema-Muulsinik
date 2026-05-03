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

    protected $rules = [
        'name' => 'required|string|max:255',
        'abbreviation' => 'nullable|string|max:50',
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function save()
    {
        $this->validate();

        if ($this->editingId) {
            Measure::findOrFail($this->editingId)->update([
                'name' => $this->name,
                'abbreviation' => $this->abbreviation,
            ]);
            session()->flash('success', 'Medida actualizada exitosamente.');
        } else {
            Measure::create([
                'name' => $this->name,
                'abbreviation' => $this->abbreviation,
            ]);
            session()->flash('success', 'Medida creada exitosamente.');
        }

        $this->reset(['name', 'abbreviation', 'editingId']);
    }

    public function edit(int $id)
    {
        $measure = Measure::findOrFail($id);
        $this->editingId = $measure->id;
        $this->name = $measure->name;
        $this->abbreviation = $measure->abbreviation;
    }

    public function cancelEdit()
    {
        $this->reset(['name', 'abbreviation', 'editingId']);
    }

    public function delete(int $id)
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
