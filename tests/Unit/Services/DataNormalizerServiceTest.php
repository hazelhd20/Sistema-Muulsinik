<?php

namespace Tests\Unit\Services;

use App\Services\DataNormalizerService;
use Tests\TestCase;

class DataNormalizerServiceTest extends TestCase
{
    private DataNormalizerService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(DataNormalizerService::class);
    }

    public function test_normalizes_text_by_removing_accents_and_lowercasing(): void
    {
        $raw = "  Ácido Úrico y Cemento  Híbrido   ";
        $normalized = $this->service->normalizeText($raw);

        $this->assertEquals("acido urico y cemento hibrido", $normalized);
    }

    public function test_title_case_formatting(): void
    {
        $raw = "cemento de alta resistencia para el muro";
        $titleCase = $this->service->normalizeTitleCase($raw);

        $this->assertEquals("Cemento de Alta Resistencia para el Muro", $titleCase);
    }
}
