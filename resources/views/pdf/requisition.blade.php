<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Requisición {{ $requisition->number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9pt;
            color: #1a1a2e;
            background: #ffffff;
        }

        /* ── Layout ── */
        .page { padding: 28px 32px 24px; }

        /* ── Header ── */
        .header {
            display: table;
            width: 100%;
            border-bottom: 2px solid #0230c8;
            padding-bottom: 14px;
            margin-bottom: 18px;
        }
        .header-left  { display: table-cell; width: 55%; vertical-align: middle; }
        .header-right { display: table-cell; width: 45%; vertical-align: middle; text-align: right; }

        .company-name {
            font-size: 15pt;
            font-weight: bold;
            color: #0230c8;
            letter-spacing: 0.5px;
        }
        .doc-title {
            font-size: 18pt;
            font-weight: bold;
            color: #0230c8;
            line-height: 1;
        }
        .doc-number {
            font-size: 9pt;
            color: #374151;
            margin-top: 3px;
            font-weight: bold;
        }

        /* ── Status badge ── */
        .badge {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 20px;
            font-size: 7.5pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 5px;
        }
        .badge-borrador    { background: #f3f4f6; color: #6b7280; border: 1px solid #d1d5db; }
        .badge-pendiente   { background: #fffbeb; color: #92400e; border: 1px solid #fcd34d; }
        .badge-aprobada    { background: #ecfdf5; color: #065f46; border: 1px solid #6ee7b7; }
        .badge-rechazada   { background: #fef2f2; color: #991b1b; border: 1px solid #fca5a5; }

        /* ── Info grid (2 col) ── */
        .info-grid {
            display: table;
            width: 100%;
            margin-bottom: 18px;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
        }
        .info-col {
            display: table-cell;
            width: 50%;
            padding: 12px 14px;
            vertical-align: top;
        }
        .info-col + .info-col {
            border-left: 1px solid #e5e7eb;
        }
        .info-section-title {
            font-size: 7pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #9ca3af;
            margin-bottom: 7px;
            border-bottom: 1px solid #f3f4f6;
            padding-bottom: 4px;
        }
        .info-row {
            display: table;
            width: 100%;
            margin-bottom: 4px;
        }
        .info-label {
            display: table-cell;
            width: 40%;
            font-size: 8pt;
            color: #6b7280;
            vertical-align: top;
        }
        .info-value {
            display: table-cell;
            width: 60%;
            font-size: 8.5pt;
            color: #111827;
            font-weight: 500;
            vertical-align: top;
        }

        /* ── Table ── */
        .section-title {
            font-size: 8pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #6b7280;
            margin-bottom: 6px;
        }

        table.items {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.5pt;
        }
        table.items thead tr {
            background-color: #0230c8;
            color: #ffffff;
        }
        table.items thead th {
            padding: 7px 8px;
            text-align: left;
            font-size: 7.5pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        table.items thead th.text-right { text-align: right; }
        table.items thead th.text-center { text-align: center; }

        table.items tbody tr { border-bottom: 1px solid #f3f4f6; }
        table.items tbody tr:nth-child(even) { background-color: #f9fafb; }
        table.items tbody td { padding: 6px 8px; vertical-align: top; color: #374151; }
        table.items tbody td.text-right  { text-align: right; }
        table.items tbody td.text-center { text-align: center; }
        table.items tbody td.product-name { font-weight: 500; color: #111827; }
        table.items tbody td.text-muted { color: #9ca3af; font-style: italic; }

        /* ── Totals ── */
        .totals-wrap {
            display: table;
            width: 100%;
            margin-top: 12px;
        }
        .totals-spacer { display: table-cell; width: 60%; }
        .totals-box {
            display: table-cell;
            width: 40%;
            vertical-align: top;
        }
        .totals-table { width: 100%; border-collapse: collapse; }
        .totals-table td { padding: 4px 8px; font-size: 8.5pt; }
        .totals-table .label { color: #6b7280; text-align: left; }
        .totals-table .value { text-align: right; font-weight: 500; color: #374151; }
        .totals-table tr.total-row td {
            border-top: 2px solid #0230c8;
            padding-top: 6px;
            font-size: 10pt;
            font-weight: bold;
            color: #0230c8;
        }

        /* ── Annotations / Rejection ── */
        .notes-box {
            margin-top: 14px;
            padding: 10px 12px;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            background: #f9fafb;
        }
        .notes-title {
            font-size: 7.5pt;
            font-weight: bold;
            text-transform: uppercase;
            color: #9ca3af;
            letter-spacing: 0.6px;
            margin-bottom: 4px;
        }
        .notes-text { font-size: 8.5pt; color: #374151; line-height: 1.5; }

        .rejection-box {
            margin-top: 10px;
            padding: 10px 12px;
            border: 1px solid #fca5a5;
            border-radius: 6px;
            background: #fef2f2;
        }
        .rejection-title {
            font-size: 7.5pt;
            font-weight: bold;
            text-transform: uppercase;
            color: #dc2626;
            letter-spacing: 0.6px;
            margin-bottom: 4px;
        }
        .rejection-text { font-size: 8.5pt; color: #991b1b; line-height: 1.5; }

        /* ── Footer ── */
        .footer {
            margin-top: 22px;
            padding-top: 10px;
            border-top: 1px solid #e5e7eb;
            display: table;
            width: 100%;
        }
        .footer-left { display: table-cell; width: 50%; font-size: 7.5pt; color: #9ca3af; vertical-align: bottom; }
        .footer-right { display: table-cell; width: 50%; text-align: right; font-size: 7.5pt; color: #9ca3af; vertical-align: bottom; }
    </style>
</head>
<body>
<div class="page">

    {{-- ── HEADER ── --}}
    <div class="header">
        <div class="header-left">
            @if($logoData)
                <img src="{{ $logoData }}" alt="{{ $company['name'] ?? 'Muulsinik' }}" style="height:36px; width:auto;">
            @else
                <div class="company-name">{{ strtoupper($company['name'] ?? 'MUULSINIK') }}</div>
            @endif
        </div>
        <div class="header-right">
            <div class="doc-title">REQUISICIÓN</div>
            <div class="doc-number">{{ $requisition->number ?? '—' }}</div>
            @php
                $badgeClass = match($requisition->status) {
                    'borrador'  => 'badge-borrador',
                    'pendiente' => 'badge-pendiente',
                    'aprobada'  => 'badge-aprobada',
                    'rechazada' => 'badge-rechazada',
                    default     => 'badge-borrador',
                };
                $statusLabel = match($requisition->status) {
                    'borrador'  => 'Borrador',
                    'pendiente' => 'Pendiente de aprobación',
                    'aprobada'  => 'Aprobada',
                    'rechazada' => 'Rechazada',
                    default     => ucfirst($requisition->status),
                };
            @endphp
            <span class="badge {{ $badgeClass }}">{{ $statusLabel }}</span>
        </div>
    </div>

    {{-- ── INFO GRID ── --}}
    <div class="info-grid">
        <div class="info-col">
            <div class="info-section-title">Datos del Proyecto</div>
            <div class="info-row">
                <div class="info-label">Proyecto</div>
                <div class="info-value">{{ $requisition->project?->name ?? '—' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Cliente</div>
                <div class="info-value">{{ $requisition->project?->client ?? '—' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Fecha</div>
                <div class="info-value">{{ $requisition->date?->format('d/m/Y') ?? '—' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Elaboró</div>
                <div class="info-value">{{ $requisition->creator?->name ?? '—' }}</div>
            </div>
        </div>
        <div class="info-col">
            <div class="info-section-title">Proveedor / Vendedor</div>
            <div class="info-row">
                <div class="info-label">Proveedor</div>
                <div class="info-value">{{ $requisition->vendor?->supplier?->trade_name ?? '—' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Vendedor</div>
                <div class="info-value">{{ $requisition->vendor?->name ?? '—' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Teléfono</div>
                <div class="info-value">{{ $requisition->vendor?->phone ?? '—' }}</div>
            </div>
            @if($requisition->approver)
            <div class="info-row">
                <div class="info-label">Aprobó</div>
                <div class="info-value">{{ $requisition->approver->name }}</div>
            </div>
            @endif
        </div>
    </div>

    {{-- ── ITEMS TABLE ── --}}
    <div class="section-title">Productos / Materiales</div>
    <table class="items">
        <thead>
            <tr>
                <th style="width:4%">#</th>
                <th style="width:36%">Descripción</th>
                <th class="text-center" style="width:8%">Cant.</th>
                <th class="text-center" style="width:8%">Unidad</th>
                <th class="text-right" style="width:12%">P. Unit.</th>
                <th class="text-right" style="width:12%">Subtotal</th>
                <th class="text-right" style="width:8%">IVA</th>
                <th class="text-right" style="width:12%">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($requisition->items as $index => $item)
            <tr>
                <td style="color:#9ca3af">{{ $index + 1 }}</td>
                <td class="product-name">
                    {{ $item->product_name ?? $item->product?->canonical_name ?? '—' }}
                </td>
                <td class="text-center">{{ rtrim(rtrim(number_format((float)$item->quantity, 4, '.', ''), '0'), '.') }}</td>
                <td class="text-center text-muted">{{ $item->measure?->abbreviation ?? '—' }}</td>
                <td class="text-right">{{ $currency['position'] === 'before' ? $currency['symbol'] : '' }}{{ number_format((float)$item->unit_price, $currency['decimals'], '.', ',') }}{{ $currency['position'] === 'after' ? $currency['symbol'] : '' }}</td>
                <td class="text-right">{{ $currency['position'] === 'before' ? $currency['symbol'] : '' }}{{ number_format($item->line_subtotal_computed, $currency['decimals'], '.', ',') }}{{ $currency['position'] === 'after' ? $currency['symbol'] : '' }}</td>
                <td class="text-right">
                    @if($item->tax_amount !== null)
                        {{ $currency['position'] === 'before' ? $currency['symbol'] : '' }}{{ number_format((float)$item->tax_amount, $currency['decimals'], '.', ',') }}{{ $currency['position'] === 'after' ? $currency['symbol'] : '' }}
                    @else
                        <span style="color:#9ca3af">—</span>
                    @endif
                </td>
                <td class="text-right" style="font-weight:bold">{{ $currency['position'] === 'before' ? $currency['symbol'] : '' }}{{ number_format($item->line_total_computed, $currency['decimals'], '.', ',') }}{{ $currency['position'] === 'after' ? $currency['symbol'] : '' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- ── TOTALS ── --}}
    @php
        $subtotal = $requisition->subtotal;
        $tax      = $requisition->tax_amount;
        $total    = $requisition->total;
    @endphp
    <div class="totals-wrap">
        <div class="totals-spacer"></div>
        <div class="totals-box">
            <table class="totals-table">
                <tr>
                    <td class="label">Subtotal</td>
                    <td class="value">{{ $currency['position'] === 'before' ? $currency['symbol'] : '' }}{{ number_format($subtotal, $currency['decimals'], '.', ',') }}{{ $currency['position'] === 'after' ? $currency['symbol'] : '' }}</td>
                </tr>
                <tr>
                    <td class="label">IVA</td>
                    <td class="value">
                        @if($tax > 0)
                            {{ $currency['position'] === 'before' ? $currency['symbol'] : '' }}{{ number_format($tax, $currency['decimals'], '.', ',') }}{{ $currency['position'] === 'after' ? $currency['symbol'] : '' }}
                        @else
                            <span style="color:#9ca3af">—</span>
                        @endif
                    </td>
                </tr>
                <tr class="total-row">
                    <td class="label">TOTAL</td>
                    <td class="value">{{ $currency['position'] === 'before' ? $currency['symbol'] : '' }}{{ number_format($total, $currency['decimals'], '.', ',') }}{{ $currency['position'] === 'after' ? $currency['symbol'] : '' }}</td>
                </tr>
            </table>
        </div>
    </div>

    {{-- ── ANNOTATIONS ── --}}
    @if($requisition->annotations)
    <div class="notes-box">
        <div class="notes-title">Anotaciones</div>
        <div class="notes-text">{{ $requisition->annotations }}</div>
    </div>
    @endif

    {{-- ── REJECTION COMMENT ── --}}
    @if($requisition->rejection_comment)
    <div class="rejection-box">
        <div class="rejection-title">Motivo de rechazo</div>
        <div class="rejection-text">{{ $requisition->rejection_comment }}</div>
    </div>
    @endif

    {{-- ── FOOTER ── --}}
    <div class="footer">
        <div class="footer-left">
            {{ $company['name'] ?? 'Muulsinik ERP' }} &nbsp;·&nbsp; {{ $company['rfc'] ? 'RFC: '.$company['rfc'].' · ' : '' }}Generado el {{ now()->format('d/m/Y H:i') }}
        </div>
        <div class="footer-right">
            {{ $requisition->number ?? '' }} &nbsp;·&nbsp; {{ $statusLabel }}
        </div>
    </div>

</div>
</body>
</html>
