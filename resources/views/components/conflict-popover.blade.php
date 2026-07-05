{{--
┌─────────────────────────────────────────────────────────────────┐
│  x-conflict-popover — Popover de resolución de conflictos IA    │
│  Extrae los 3 popovers inline del QuotationWizard               │
├─────────────────────────────────────────────────────────────────┤
│  Props:                                                         │
│    type  → 'fuzzy-product' | 'category-conflict' | 'unit-conflict'
│    item  → el array del item del wizard                         │
│    index → índice de fila (int)                                 │
│                                                                 │
│  Slot:  el input/select/combobox que va dentro de la celda.     │
│         Agrega pr-8 al input cuando el indicador esté visible.  │
│                                                                 │
│  Uso en la tabla de items del QuotationWizard:                  │
│    @php $hasFuzzy = !($item['product_confirmed'] ?? true)       │
│        && ($item['_match']['product']['status'] ?? '') === 'fuzzy'; @endphp
│                                                                 │
│    <x-conflict-popover type="fuzzy-product" :item="$item" :index="$i">
│        <input class="input input-inline {{ $hasFuzzy ? 'pr-8' : '' }}" ...>
│    </x-conflict-popover>                                        │
└─────────────────────────────────────────────────────────────────┘
--}}

@props([
    'type',         // 'fuzzy-product' | 'category-conflict' | 'unit-conflict'
    'item',         // array del item proveniente del wizard
    'index',        // int — índice de fila en el foreach
    'triggerRight'  => 'right-2',  // posición horizontal del botón trigger
                                   // usar 'right-8' para x-custom-select (tiene chevron propio)
])

@php
// ── Evaluar si este popover debe renderizarse ──────────────────
$shouldRender = match($type) {
    'fuzzy-product' => isset($item['product_confirmed'])
                        && !$item['product_confirmed']
                        && ($item['_match']['product']['status'] ?? '') === 'fuzzy',

    'category-conflict' => isset($item['conflict']['category']),

    'unit-conflict' => isset($item['conflict']['unit']),

    default => false,
};

// ── Configuración por tipo ─────────────────────────────────────
$cfg = $shouldRender ? match($type) {

    'fuzzy-product' => [
        // Boton trigger
        'triggerIcon'  => 'sparkles',
        'triggerClass' => 'text-primary-600 hover:bg-primary-50 animate-pulse',
        'triggerTitle' => 'Coincidencia difusa detectada — click para revisar',

        // Header del popover
        'headerIcon'   => 'sparkles',
        'headerColor'  => 'text-primary-600',
        'title'        => 'Coincidencia detectada',
        'subtitle'     => '¿El producto corresponde a la base de datos?',

        // Filas de datos
        'rows' => [
            [
                'label'  => 'En cotización:',
                'value'  => $item['name'] ?? '',
                'weight' => 'font-medium',
                'color'  => 'text-text-primary',
            ],
            [
                'label'  => 'En catálogo:',
                'value'  => '"' . ($item['_match']['product']['catalog_name'] ?? '') . '"',
                'weight' => 'font-semibold',
                'color'  => 'text-text-primary',
            ],
            [
                'label'     => 'Confianza:',
                'value'     => round(($item['_match']['product']['confidence'] ?? 0) * 100) . '% de similitud',
                'weight'    => 'font-semibold',
                'color'     => 'text-primary-600',
                'separator' => true,
            ],
        ],

        // Botones de acción
        'confirmAction'  => "confirmProductAssociation({$index})",
        'confirmLabel'   => 'Confirmar y vincular',
        'confirmVariant' => 'primary',

        'cancelAction'  => "rejectProductAssociation({$index})",
        'cancelLabel'   => 'Crear como producto nuevo',
    ],

    'category-conflict' => [
        'triggerIcon'  => 'alert-triangle',
        'triggerClass' => 'text-warning hover:bg-warning-light animate-pulse',
        'triggerTitle' => 'Discrepancia en categoría — click para revisar',

        'headerIcon'   => 'help-circle',
        'headerColor'  => 'text-warning',
        'title'        => '¿Categoría diferente?',
        'subtitle'     => 'La IA sugirió una categoría distinta a la registrada en el catálogo.',

        'rows' => [
            [
                'label'  => 'Registrada:',
                'value'  => $item['conflict']['category']['registered'] ?? '',
                'weight' => 'font-medium',
                'color'  => 'text-text-primary',
            ],
            [
                'label'  => 'Propuesta IA:',
                'value'  => $item['conflict']['category']['suggested'] ?? '',
                'weight' => 'font-semibold',
                'color'  => 'text-warning',
            ],
        ],

        'confirmAction'  => "resolveProductConflict({$index}, 'category')",
        'confirmLabel'   => 'Actualizar catálogo maestro',
        'confirmVariant' => 'warning',

        'cancelAction'  => "dismissProductConflict({$index})",
        'cancelLabel'   => 'Conservar catálogo actual',
    ],

    'unit-conflict' => [
        'triggerIcon'  => 'alert-triangle',
        'triggerClass' => 'text-warning hover:bg-warning-light animate-pulse',
        'triggerTitle' => 'Discrepancia en unidad de medida — click para revisar',

        'headerIcon'   => 'help-circle',
        'headerColor'  => 'text-warning',
        'title'        => '¿Unidad diferente?',
        'subtitle'     => 'La IA sugirió una unidad de medida distinta a la registrada.',

        'rows' => [
            [
                'label'  => 'Registrada:',
                'value'  => $item['conflict']['unit']['registered'] ?? '',
                'weight' => 'font-medium',
                'color'  => 'text-text-primary',
            ],
            [
                'label'  => 'Propuesta IA:',
                'value'  => $item['conflict']['unit']['suggested'] ?? '',
                'weight' => 'font-semibold',
                'color'  => 'text-warning',
            ],
        ],

        'confirmAction'  => "resolveProductConflict({$index}, 'unit')",
        'confirmLabel'   => 'Actualizar catálogo maestro',
        'confirmVariant' => 'warning',

        'cancelAction'  => "dismissProductConflict({$index})",
        'cancelLabel'   => 'Conservar catálogo actual',
    ],

    default => [],
} : [];
@endphp

{{-- ──────────────────────────────────────────────────────────────
     Si no hay conflicto, renderizar solo el slot (sin wrapper).
     Esto mantiene el DOM limpio para filas que no tienen conflicto.
────────────────────────────────────────────────────────────────── --}}
@if(!$shouldRender)
    {{ $slot }}
@else
    <div x-data="{ open: false }"
         class="relative">

        {{-- ── Wrapper del input + botón trigger ── --}}
        <div class="relative w-full">

            {{-- Slot: el input/select/combobox del caller --}}
            {{ $slot }}

            {{-- Botón trigger — superpuesto al borde derecho del input --}}
            <button type="button"
                    x-ref="trigger"
                    @click.stop="open = !open"
                    class="absolute {{ $triggerRight }} top-1/2 -translate-y-1/2 p-1 rounded-md {{ $cfg['triggerClass'] }} transition-colors shrink-0 z-10"
                    title="{{ $cfg['triggerTitle'] }}"
                    :aria-expanded="open"
                    aria-haspopup="true">
                <x-dynamic-component :component="'lucide-' . $cfg['triggerIcon']" class="w-4 h-4" wire:ignore />
            </button>
        </div>

        {{-- ── Popover panel (Teletransportado para evitar cortes de overflow) ── --}}
        <template x-teleport="body">
            <div x-show="open"
                 x-cloak
                 x-anchor.bottom-end.offset.4="$refs.trigger"
                 @click.outside="open = false"
                 @keydown.window.escape="open = false"
                 x-transition:enter="transition-premium"
                 x-transition:enter-start="opacity-0 scale-95 translate-y-1"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-100"
                 x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                 x-transition:leave-end="opacity-0 scale-95 translate-y-1"
                 class="z-[250] w-72 rounded-xl border border-border bg-surface-card shadow-xl p-3.5"
                 style="display: none;">

                <div>

                    {{-- Header --}}
                    <div class="flex items-start gap-2.5 mb-3 pb-2.5 border-b border-border">
                        <x-dynamic-component :component="'lucide-' . $cfg['headerIcon']"
                           class="w-4 h-4 {{ $cfg['headerColor'] }} shrink-0 mt-0.5"
                           wire:ignore />
                        <div>
                            <p class="text-xs-fluid font-semibold text-text-primary leading-tight">
                                {{ $cfg['title'] }}
                            </p>
                            <p class="text-xs-fluid text-text-muted mt-0.5 leading-relaxed">
                                {{ $cfg['subtitle'] }}
                            </p>
                        </div>
                    </div>

                    {{-- Filas de datos comparativos --}}
                    <div class="space-y-1.5 mb-3">
                        @foreach($cfg['rows'] as $row)
                            <div class="flex items-start justify-between gap-2
                                {{ isset($row['separator']) && $row['separator'] ? 'pt-1.5 border-t border-border' : '' }}">
                                <span class="text-xs-fluid text-text-muted shrink-0">
                                    {{ $row['label'] }}
                                </span>
                                <span class="text-xs-fluid {{ $row['weight'] }} {{ $row['color'] }} text-right leading-tight">
                                    {{ $row['value'] }}
                                </span>
                            </div>
                        @endforeach
                    </div>

                    {{-- Acciones --}}
                    <div class="flex flex-col gap-1.5">
                        <x-button type="button"
                                wire:click="{{ $cfg['confirmAction'] }}"
                                @click="open = false"
                                :variant="$cfg['confirmVariant']"
                                class="w-full">
                            {{ $cfg['confirmLabel'] }}
                        </x-button>
                        <x-button type="button"
                                wire:click="{{ $cfg['cancelAction'] }}"
                                @click="open = false"
                                variant="secondary"
                                class="w-full">
                            {{ $cfg['cancelLabel'] }}
                        </x-button>
                    </div>

                </div>
            </div>
        </template>
    </div>
@endif
