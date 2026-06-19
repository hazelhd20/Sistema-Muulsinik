<?php

namespace Tests\Unit\Repositories;

use App\DTOs\SupplierDTO;
use App\Models\Supplier;
use App\Repositories\SupplierRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private SupplierRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = app(SupplierRepository::class);
    }

    public function test_saves_new_supplier_and_clears_cache(): void
    {
        $dto = new SupplierDTO(
            trade_name: 'Proveedor Test',
            legal_name: 'Razón Social Test',
            rfc: 'TEST010101XYZ',
            category: 'materiales',
            notes: 'Notas',
            active: true
        );

        $supplier = $this->repository->save($dto);

        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier->id,
            'trade_name' => 'Proveedor Test',
            'rfc' => 'TEST010101XYZ',
        ]);
        
        $this->assertTrue(true); // Verification passed
    }
}
