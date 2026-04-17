<?php

require __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$s = new Spreadsheet();
$sheet = $s->getActiveSheet();

// Header del proveedor
$sheet->setCellValue('A1', 'MATERIALES EL CONSTRUCTOR S.A. DE C.V.');
$sheet->setCellValue('A2', 'Sucursal Centro');
$sheet->setCellValue('A3', '');

// Encabezados
$sheet->setCellValue('A4', 'Descripción');
$sheet->setCellValue('B4', 'Cantidad');
$sheet->setCellValue('C4', 'Unidad');
$sheet->setCellValue('D4', 'Precio Unitario');

// Items con códigos de proveedor (datos "sucios")
$sheet->setCellValue('A5', '1. M-20384 Cemento Monterrey CPC 40R 50kg');
$sheet->setCellValue('B5', 10);
$sheet->setCellValue('C5', 'bulto');
$sheet->setCellValue('D5', 245.50);

$sheet->setCellValue('A6', '2. SKU-VR38 Varilla corrugada 3/8 grado 42 12m');
$sheet->setCellValue('B6', 50);
$sheet->setCellValue('C6', 'pza');
$sheet->setCellValue('D6', 189.00);

$sheet->setCellValue('A7', '3. 0045-BLK Block hueco 15x20x40 ligero');
$sheet->setCellValue('B7', 500);
$sheet->setCellValue('C7', 'pza');
$sheet->setCellValue('D7', 12.80);

$sheet->setCellValue('A8', '4. CAL-HIDRA Cal hidratada tipo N 25kg');
$sheet->setCellValue('B8', 20);
$sheet->setCellValue('C8', 'bulto');
$sheet->setCellValue('D8', 85.00);

$sheet->setCellValue('A9', '5. ARENA-RIO Arena de rio cribada m3');
$sheet->setCellValue('B9', 5);
$sheet->setCellValue('C9', 'm3');
$sheet->setCellValue('D9', 450.00);

$writer = new Xlsx($s);
$writer->save('storage/app/private/test_cotizacion.xlsx');
echo "Test file created: storage/app/private/test_cotizacion.xlsx\n";
