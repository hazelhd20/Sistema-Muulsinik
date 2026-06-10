<?php

namespace App\Livewire\Products;

use App\Models\Product;
use Livewire\Attributes\On;
use Livewire\Component;

class ProductDetailDrawer extends Component
{
    public bool $showDetailDrawer = false;

    public ?int $showingDetailId = null;

    public ?Product $detailProduct = null;

    #[On('open-product-detail')]
    public function showDetail(int $id): void
    {
        $this->showingDetailId = $id;
        $this->detailProduct = Product::with(['category', 'measure'])->find($id);
        $this->showDetailDrawer = true;
    }

    public function render()
    {
        return view('livewire.products.product-detail-drawer');
    }
}
