# Auditoría de Consistencia Cruzada — Muulsinik ERP

> **Objetivo**: Verificar que vistas con propósito similar compartan diseño, estructura y experiencia de usuario idénticos.  
> **Alcance**: 15 vistas Blade, 9 componentes compartidos, 1 layout principal  
> **Fecha**: 3 de Junio 2026

---

## Patrones Identificados

Después de analizar todas las vistas, identifiqué **6 familias de patrones** que deberían tener consistencia interna total:

| # | Patrón | Vistas que pertenecen |
|---|---|---|
| **A** | CRUD Index (listado + búsqueda + filtros + paginación + modal CRUD) | Proyectos, Proveedores, Gastos, Productos, Categorías, Medidas, Usuarios, Cotizador Index, Requisiciones |
| **B** | Full-Page Creator/Editor (formulario extenso en página completa) | Requisición Manual |
| **C** | Wizard Multi-Step (flujo de pasos con progresión) | Quotation Wizard (3 pasos), Quick Budget Wizard (sin pasos) |
| **D** | Dashboard / Data Visualization (solo lectura) | Dashboard, Reportes |
| **E** | Modal CRUD (crear/editar dentro de modal) | Proyectos, Proveedores, Gastos, Productos, Categorías, Medidas, Usuarios |
| **F** | Settings (formulario de configuración estilo Stripe) | Configuración |

---

## A. CRUD INDEX — Matriz de Consistencia

Esta es la familia más grande. Comparo los **12 aspectos clave** que deberían ser idénticos:

### Matriz comparativa

| Aspecto | Requisiciones | Proyectos | Proveedores | Gastos | Productos | Categorías | Medidas | Usuarios | Cotizador |
|---|---|---|---|---|---|---|---|---|---|
| **Page Header** | ✅ `x-page-header` | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| **Search input** | ✅ `sm:w-72` | ✅ `sm:w-72` | ✅ `sm:w-72` | ✅ `sm:w-72` | ✅ `sm:w-72` | ✅ `sm:w-72` | ✅ `sm:w-72` | ✅ `sm:w-72` | ⚠️ `flex-1 max-w-md` |
| **Filters toggle** | ✅ con counter | ✅ con counter | ❌ **No tiene** | ✅ con counter | ✅ con counter | ❌ **No tiene** | ❌ **No tiene** | ✅ con counter | ❌ **No tiene** |
| **Filter panel** | ✅ expandible | ✅ expandible | ❌ | ✅ expandible | ✅ expandible | ❌ | ❌ | ✅ expandible | ❌ |
| **Clear button** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ **No tiene** |
| **Data format** | 🟧 **Cards** | 🟧 **Card Grid** | 🟧 **Card Grid** | 🟩 **Table** | 🟩 **Table** | 🟩 **Table** | 🟩 **Table** | 🟩 **Table** | 🟩 **Table** |
| **Empty state** | ✅ `x-empty-state` | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| **Pagination** | ✅ `mt-4` | ✅ sin `mt-4` wrapper | ✅ `mt-4` | ✅ `mt-4` | ✅ `mt-4` | ✅ `mt-4` | ✅ `mt-4` | ✅ `mt-4` | ✅ `mt-4` |
| **Action icons** | `btn-icon-*` | `btn-icon-*` | `btn-icon-*` | `btn-icon-*` | `btn-icon-*` | `btn-icon-*` | `btn-icon-*` | `btn-icon-*` | `btn-icon-*` |
| **Delete confirm** | ✅ `wire:confirm` | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| **Stats cards** | ❌ | ❌ | ❌ | ✅ **3 stats** | ❌ | ❌ | ❌ | ❌ | ❌ |
| **Edit flow** | ❌ No editable | ✅ Modal | ✅ Modal | ❌ No editable | ✅ Modal | ✅ Modal | ✅ Modal | ✅ Modal | Link a wizard |

---

### Hallazgos Críticos en CRUD Index

#### 1. **Search input inconsistente en Cotizador Index**
**Severidad: Medio**

| Vista | Implementación |
|---|---|
| Todos los demás | `<div class="relative w-full sm:w-72">` — ancho fijo compacto |
| [quick-budget-index.blade.php:13](file:///c:/Users/HP/Documents/Sistemas/Sistemas-Muulsinik/resources/views/livewire/quick-budgets/quick-budget-index.blade.php#L13) | `<div class="relative flex-1 max-w-md">` — ancho fluido |

El Cotizador no usa el mismo patrón de search. Tampoco tiene `x-data="{ showFilters: false }"` en el wrapper, ni botón "Limpiar", ni botón de filtros.

**Impacto**: El usuario que use Cotizador después de Requisiciones sentirá inconsistencia. El search no se comporta igual visualmente.

---

#### 2. **Formato de datos (Tables vs Cards) — sin criterio unificado**
**Severidad: Alto**

```
Cards expandibles:  Requisiciones
Card Grid:          Proyectos, Proveedores
Table:              Gastos, Productos, Categorías, Medidas, Usuarios, Cotizador
```

**No hay un criterio consistente** de cuándo usar cards vs tablas. Comparando entidades similares:

| Entidad | Complejidad visual | Formato actual | Formato ideal |
|---|---|---|---|
| Requisiciones | Alta (status, total, items, acciones de estado) | Cards expandibles | ✅ Correcto — tiene mucha info contextual |
| Proyectos | Media (progress bar, presupuesto) | Card Grid 3 cols | ✅ Correcto — la progress bar justifica card |
| Proveedores | **Baja** (nombre, RFC, notas, vendedores) | Card Grid 3 cols | ⚠️ **Debatible** — tabla sería más eficiente |
| Gastos | Media (concepto, proyecto, monto, fecha) | Table | ✅ Correcto |
| Productos | Baja (nombre, categoría, unidad) | Table | ✅ Correcto |

**Recomendación**: Establecer regla: **Card Grid** solo cuando la entidad tiene contenido visual (progress bars, imágenes, badges complejos). **Table** para CRUDs tabulares puros. Proveedores es limítrofe — podría ser tabla.

---

#### 3. **Stats cards solo en Gastos — inconsistente**
**Severidad: Medio**

Solo [expense-index.blade.php:13-41](file:///c:/Users/HP/Documents/Sistemas/Sistemas-Muulsinik/resources/views/livewire/expenses/expense-index.blade.php#L13-L41) tiene stat cards (Gasto del mes, Total registros, Período). Ningún otro CRUD index las tiene.

| Vista | ¿Podría beneficiarse de stats? |
|---|---|
| Requisiciones | Sí — Total pendientes, Total aprobadas, Monto acumulado |
| Proyectos | Sí — Activos, Presupuesto total, % ejecución promedio |
| Proveedores | Parcial — Total proveedores, Total vendedores |
| Productos | Parcial — Total productos, Categorías usadas |

**Recomendación**: O todos los index principales tienen stats contextuales, o ninguno. Si se elige "todos", definir un patrón estándar de 3-4 stat-cards previo a la barra de búsqueda.

---

#### 4. **Filtros ausentes en módulos simples**
**Severidad: Bajo**

Categorías, Medidas, Proveedores y Cotizador no tienen panel de filtros expandible. Esto es **correcto** para Categorías y Medidas (solo tienen un campo), pero **incorrecto** para Proveedores que podrían filtrarse por categoría del proveedor.

---

#### 5. **Pagination wrapper inconsistente en Proyectos**
**Severidad: Bajo**

| Vista | Código |
|---|---|
| Todos los demás | `<div class="mt-4">{{ $X->links() }}</div>` |
| [project-index.blade.php:134](file:///c:/Users/HP/Documents/Sistemas/Sistemas-Muulsinik/resources/views/livewire/projects/project-index.blade.php#L134) | `{{ $projects->links() }}` — **sin wrapper** `<div class="mt-4">` |

---

## B & C. FLUJOS DE CREACIÓN — Comparativa Crítica

Estas son las 3 vistas que crean datos complejos (con productos/items) y deberían compartir patrones:

| Aspecto | Requisición Manual | Quotation Wizard (Paso 3) | Quick Budget Wizard |
|---|---|---|---|
| **Archivo** | [manual-requisition](file:///c:/Users/HP/Documents/Sistemas/Sistemas-Muulsinik/resources/views/livewire/requisitions/manual-requisition.blade.php) | [quotation-wizard](file:///c:/Users/HP/Documents/Sistemas/Sistemas-Muulsinik/resources/views/livewire/requisitions/quotation-wizard.blade.php) | [quick-budget-wizard](file:///c:/Users/HP/Documents/Sistemas/Sistemas-Muulsinik/resources/views/livewire/quick-budgets/quick-budget-wizard.blade.php) |

---

### Comparativa Detallada

#### Header / Navegación de regreso

| Vista | Patrón | Código |
|---|---|---|
| Manual | `x-page-header` + `btn-secondary` "Volver" con `arrow-left` | Estándar ✅ |
| Wizard | `x-page-header` con slot `heading` personalizado + `btn-icon-secondary` "Volver" | ⚠️ **Diferente** — usa icon button, no text button |
| Budget | **NO usa `x-page-header`** en absoluto | ❌ **Header completamente manual** ([L2-16](file:///c:/Users/HP/Documents/Sistemas/Sistemas-Muulsinik/resources/views/livewire/quick-budgets/quick-budget-wizard.blade.php#L2-L16)) |

> [!WARNING]
> **El Budget Wizard construye su header manualmente** con `<div class="flex items-center gap-3 mb-6">`, `<h1 class="text-h2">` y botón de guardar inline. No usa `x-page-header` y el heading usa `text-h2` en lugar de `text-h1`.

---

#### Sección "Información General"

| Aspecto | Manual | Wizard | Budget |
|---|---|---|---|
| **Section heading** | `<h3 class="text-small font-semibold">` con `border-b` manual | `<h2 class="text-h2">` sin border-bottom explícito | `<h2 class="text-h2">` con `<i data-lucide="info">` |
| **Usa `card-header`?** | ❌ No — heading manual | ❌ No para info general (sí para productos) | ❌ No |
| **Label wrapper** | `<label class="label">` directo | `<x-form-field>` componente | `<label class="label">` directo |
| **Grid layout** | `grid-cols-1 md:grid-cols-4` | `grid-cols-1 md:grid-cols-3` | `grid-cols-2` |
| **Error spacing** | `mt-1.5` | Delegado a `x-form-field` (`mt-1`) | `mt-1` |

> [!IMPORTANT]
> **Existen 3 patrones diferentes para envolver labels + inputs + errores**:
> 1. **Manual** (usado en 90% de vistas): `<label class="label">` + `<input>` + `@error() <p class="mt-1">` 
> 2. **`<x-form-field>`** (usado SOLO en Quotation Wizard paso 3): Componente que envuelve label + slot + error
> 3. **Manual con `mt-1.5`** (usado en Manual Requisition): Spacing ligeramente diferente
>
> El componente `x-form-field` existe pero solo se usa en 1 vista. Debería usarse en todas o en ninguna.

---

#### Tabla de productos / items

| Aspecto | Manual | Wizard | Budget |
|---|---|---|---|
| **Table wrapper** | `table-container` con `border` extra | `table-embedded` con `md:!overflow-visible` | `table-embedded` con overrides de borde/radius |
| **Table th styling** | **Manual styling** (`bg-surface-hover`, `px-4 py-3`, `text-left`) — **NO hereda** de `.table-container th` | Hereda de `.table-embedded th` ✅ | Hereda de `.table-embedded th` ✅ |
| **Delete button** | Siempre visible `btn-icon-danger` | Hidden on hover `opacity-0 group-hover:opacity-100` | Hidden on hover `opacity-0 group-hover:opacity-100` |
| **Input style en tabla** | N/A (datos estáticos) | Transparent bg, border on hover/focus | Transparent bg, border on hover/focus ✅ |
| **Columnas** | Producto, Cat, Cant, Unidad, P.U., Subtotal, IVA, Total, ❌ | Producto, Cat, Cant, Unidad, P.U., Subtotal, Total, ❌ | ❌, Concepto, Cant, P.U., Total |
| **Empty state** | Div personalizado con `border-dashed` | Div personalizado con `border-dashed` | Inline en tbody — `<td colspan>` con icono y texto |

> [!CAUTION]  
> **La tabla de Requisición Manual define estilos inline en los `<th>`** ([manual-requisition:104-114](file:///c:/Users/HP/Documents/Sistemas/Sistemas-Muulsinik/resources/views/livewire/requisitions/manual-requisition.blade.php#L104-L114)) con clases como `bg-surface-hover border-b border-border text-left px-4 py-3`. Esto **no hereda** de `.table-container th` definido en app.css, que ya provee `padding: 0.625rem 1.125rem`, `font-size: xs`, `text-transform: uppercase`, etc. El resultado es que esta tabla tiene tipografía normal-case mientras las tablas del rest del sistema son UPPERCASE en headers.

---

#### Sección de Totales

| Aspecto | Manual | Wizard | Budget |
|---|---|---|---|
| **Ubicación** | Card separada con `bg-surface-main border rounded-xl p-5` | Inline con `bg-surface-hover/30 p-4 rounded-xl border` | Panel lateral sticky con `bg-surface-main rounded-lg p-4` |
| **Min-width** | `min-w-[300px]` | `min-w-[280px] sm:min-w-[300px]` | N/A (col completa) |
| **Label "Total"** | `text-body font-semibold` + valor `text-h3 font-bold` | `text-small font-semibold` + valor `text-h2 font-bold text-primary-600` | `text-xs-fluid uppercase` + valor `text-3xl font-bold` |
| **Color del total** | `text-text-primary` | **`text-primary-600`** (azul) | `text-text-primary` |
| **Muestra IVA** | ✅ Sí (desglosado) | ✅ Sí (desglosado + descuentos) | ❌ No |
| **Layout** | `flex justify-end` — alineado a la derecha | `flex justify-end` — alineado a la derecha | Columna completa lateral |

> [!WARNING]
> **3 diseños completamente diferentes para la sección de totales**. El usuario que crea una requisición manual, luego sube una cotización, y luego usa el cotizador verá 3 experiencias diferentes para la misma información financiera.

---

#### Formulario para añadir ítems

| Aspecto | Manual | Wizard | Budget |
|---|---|---|---|
| **Patrón** | Formulario inline encima de la tabla con campos horizontales + botón "Añadir" | Botón "Agregar" en card-header que agrega fila vacía a la tabla | Search de producto con dropdown + botón "Concepto Manual" |
| **Cómo se editan items** | No se editan — se eliminan y re-agregan | Inline editing en las celdas de la tabla | Inline editing en las celdas de la tabla |
| **Product autocomplete** | `<datalist>` nativo HTML | `wire:model.live.debounce` con matching IA | `wire:model.live` con dropdown custom y precio histórico |

> [!IMPORTANT]
> **3 experiencias de agregar productos completamente diferentes**:
> 1. **Manual**: Formulario horizontal → tabla estática (no editable)
> 2. **Wizard**: Fila vacía se inserta → edición inline (tabla es el formulario)
> 3. **Budget**: Search bar con dropdown → edición inline (tabla es el formulario)
>
> Los usuarios que rotan entre estas vistas deben re-aprender la interacción cada vez.

---

#### Botones de acción finales (Footer)

| Aspecto | Manual | Wizard | Budget |
|---|---|---|---|
| **Layout** | `flex justify-end gap-3 pt-2` | `flex items-center justify-between pt-2` | Botón en el header (`ml-auto`) |
| **Cancelar** | `<a>` con `btn-secondary` → index | `<a>` con `btn-secondary` → index | No hay ❌ |
| **Submit** | `btn-primary` con loading SVG + `<i data-lucide="loader-2">` | `btn-primary` con loading SVG circular estándar | `btn-primary` **SIN loading indicator** |
| **Loading icon** | ⚠️ `<i data-lucide="loader-2">` (Lucide icon) | SVG circular inline (patrón estándar) | **Ninguno** |
| **Back action** | No (solo en header) | `btn-secondary` "Subir otro archivo" a la izquierda | No |

> [!CAUTION]
> **3 patrones de loading spinner diferentes**:
> 1. **Manual**: Usa `<i data-lucide="loader-2" class="animate-spin">` — un ícono de Lucide
> 2. **Wizard y todos los Modales**: SVG circular inline (`<svg class="animate-spin">`)
> 3. **Budget**: **No tiene loading** en absoluto
> 4. **CSS**: Existe la clase `.spinner` en app.css que **ninguna vista usa**

---

## E. MODALES CRUD — Matriz de Consistencia

| Aspecto | Proyectos | Proveedores | Gastos | Productos | Categorías | Medidas | Usuarios |
|---|---|---|---|---|---|---|---|
| **Wrapper** | `<x-modal>` | `<x-modal>` | `<x-modal>` | `<x-modal>` | `<x-modal>` | `<x-modal>` | `<x-modal>` |
| **Body padding** | `p-5` | `p-5` | `p-5` | `p-5` | `p-5` | `p-5` | `p-5` |
| **Form spacing** | `space-y-4` | `space-y-4` | `space-y-4` | `space-y-4` | `space-y-4` | `space-y-4` | `space-y-4` |
| **Footer** | `flex justify-end gap-3 pt-4 border-t` | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| **Cancelar** | `btn-secondary` | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| **Submit loading** | SVG spinner | SVG spinner | SVG spinner | SVG spinner | SVG spinner | SVG spinner | SVG spinner |
| **Crear/Editar toggle** | ❌ **Modales separados** | Modal unificado ✅ | N/A (solo crear) | Modal unificado ✅ | Modal unificado ✅ | Modal unificado ✅ | ❌ **Modales separados** |

### Hallazgos en Modales

#### 1. **Proyectos y Usuarios usan modales duplicados**
**Severidad: Medio**

- [project-index.blade.php:137-267](file:///c:/Users/HP/Documents/Sistemas/Sistemas-Muulsinik/resources/views/livewire/projects/project-index.blade.php#L137-L267): **2 modales** — `showEditModal` y `showCreateModal` con formularios prácticamente idénticos
- [user-index.blade.php:149-253](file:///c:/Users/HP/Documents/Sistemas/Sistemas-Muulsinik/resources/views/livewire/users/user-index.blade.php#L149-L253): **2 modales** — `showCreateModal` y `showEditModal` con formularios casi iguales

**Mientras que** Proveedores, Productos, Categorías y Medidas usan el patrón correcto: **1 solo modal** con título condicional:
```blade
:title="$editingId ? 'Editar X' : 'Nuevo X'"
```

**Impacto en mantenibilidad**: Cada cambio en el formulario de proyectos requiere editarse en 2 lugares. Viola el principio DRY y el estándar propio del sistema.

---

#### 2. **Gastos no tiene modo edición**
**Severidad: Medio**

La vista de gastos ([expense-index.blade.php](file:///c:/Users/HP/Documents/Sistemas/Sistemas-Muulsinik/resources/views/livewire/expenses/expense-index.blade.php)) solo tiene modal de **creación**. No hay forma de editar un gasto existente — solo se puede eliminar. Si comparamos con los demás CRUDs donde el pencil icon abre un modal de edición, la ausencia de esta funcionalidad es inconsistente.

---

#### 3. **Font-size inconsistente en tablas de Usuarios**
**Severidad: Bajo**

[user-index.blade.php:89](file:///c:/Users/HP/Documents/Sistemas/Sistemas-Muulsinik/resources/views/livewire/users/user-index.blade.php#L89) usa `text-sm` (Tailwind directo) en lugar de `text-small` (token del sistema). Igualmente en líneas 93, 180, 232. El resto del sistema usa los tokens tipográficos personalizados (`text-body`, `text-small`, `text-xs-fluid`).

---

## C. WIZARDS — Comparativa Detallada

| Aspecto | Quotation Wizard | Quick Budget Wizard |
|---|---|---|
| **Step indicator** | ✅ 3 pasos con circles + connector lines | ❌ **No tiene** — es single-page |
| **Layout** | Full-width single column | **2 columnas**: items (2/3) + resumen (1/3) |
| **Card anatomy** | `card-header` + `card-title` para sección productos | Manual heading con `<i>` icon inline |
| **Max-width** | Sin restricción (full layout) | `max-w-5xl mx-auto` |
| **Back navigation** | Header: `btn-icon-secondary` / Footer: `btn-secondary` | Header: `btn-icon-secondary` ✅ |
| **Save button** | Footer derecho con loading | **Header derecho** sin loading |

### Hallazgo Principal

> [!IMPORTANT]
> **Ambos "wizards" parecen aplicaciones diferentes**. El Quotation Wizard es un flujo secuencial con 3 pasos claros y procesamiento IA, mientras el Budget Wizard es un formulario de página completa con layout de 2 columnas. Si bien los propósitos son diferentes, comparten mucho en común (agregar items, calcular totales) y podrían compartir más componentes.

**Similitudes que deberían estar estandarizadas:**
1. El header "back" → ambos usan `btn-icon-secondary` ✅
2. La tabla de items → ambos usan `table-embedded` pero con customizaciones diferentes
3. El inline editing de celdas → ambos usan el mismo patrón de `border-transparent bg-transparent hover:border-border`
4. El botón de eliminar fila → ambos usan `opacity-0 group-hover:opacity-100` ✅

**Diferencias que deberían unificarse:**
1. El section heading (uno usa `card-header`/`card-title`, otro usa `<h2>` + `<i>` manual)
2. Posición del botón de guardar (footer vs header)
3. Loading indicator (uno tiene, otro no)

---

## F. PREVIEW MODAL — Duplicación

| Aspecto | En Requisition Index | En Quotation Wizard |
|---|---|---|
| **Implementación** | Inline div con Alpine | Inline div con Alpine |
| **z-index** | `z-[100]` | `z-[100]` |
| **Backdrop** | `bg-black/60 backdrop-blur-sm` | `bg-black/60` (sin blur) |
| **Panel class** | `bg-surface-card rounded-2xl shadow-xl border` | `modal-card` (clase no definida en CSS) |
| **Heading** | `text-h3 font-semibold` | `text-h2` |
| **ESC close** | ❌ No tiene | ✅ `@keydown.escape.window` |
| **Fallback** | Tiene botón "Descargar" | Tiene botón "Descargar" |

> [!CAUTION]
> **El preview modal está duplicado** en 2 vistas con diferencias de implementación. La versión del Wizard usa una clase `modal-card` que **no existe en app.css** — probablemente se renderiza sin estilos propios. La versión del Index no tiene cierre por ESC. Ambos deberían ser un componente `<x-preview-modal>`.

---

## Resumen de Inconsistencias por Categoría

### 🔴 Inconsistencias Estructurales (rompen la experiencia)

| # | Inconsistencia | Vistas afectadas | Acción |
|---|---|---|---|
| 1 | **3 experiencias diferentes para agregar items/productos** | Manual, Wizard, Budget | Estandarizar en 1-2 patrones máximo |
| 2 | **3 diseños diferentes de sección de totales** | Manual, Wizard, Budget | Crear componente `<x-totals-summary>` |
| 3 | **Budget Wizard no usa `x-page-header`** | quick-budget-wizard | Migrar al componente estándar |
| 4 | **Tabla Manual no hereda estilos del design system** | manual-requisition | Usar `table-embedded` en vez de estilos inline |
| 5 | **Preview modal duplicado con diferencias** | requisition-index, quotation-wizard | Extraer a componente `<x-preview-modal>` |
| 6 | **Modales duplicados (crear/editar)** | Proyectos, Usuarios | Unificar en 1 modal como Productos/Categorías |

### 🟠 Inconsistencias de Patrón (confunden al usuario)

| # | Inconsistencia | Vistas afectadas | Acción |
|---|---|---|---|
| 7 | **`x-form-field` usado solo en 1 vista** | quotation-wizard vs todos | Adoptar en todas las vistas o eliminar |
| 8 | **3 spinners diferentes** (Lucide, SVG, CSS `.spinner`) | manual-req, modales, CSS | Unificar en `.spinner` class |
| 9 | **Search input del Cotizador Index diferente** | quick-budget-index | Estandarizar a `sm:w-72` |
| 10 | **Stats cards solo en Gastos** | expense-index | Criterio: todos los index principales o ninguno |
| 11 | **Botón Save en header (Budget) vs footer (todos los demás)** | quick-budget-wizard | Mover al footer |
| 12 | **Budget wizard sin loading en submit** | quick-budget-wizard | Agregar el patrón estándar |

### 🟡 Inconsistencias de Token (degradan la coherencia visual)

| # | Inconsistencia | Vistas afectadas | Acción |
|---|---|---|---|
| 13 | **`text-sm` de Tailwind vs `text-small` del design system** | user-index | Reemplazar por token |
| 14 | **Error spacing `mt-1.5` vs `mt-1`** | manual-requisition vs todos | Estandarizar en `mt-1` |
| 15 | **Pagination sin wrapper en Proyectos** | project-index | Agregar `<div class="mt-4">` |
| 16 | **Total color `text-primary-600` en Wizard vs `text-text-primary` en demás** | quotation-wizard | Estandarizar color del total |
| 17 | **Empty state personalizado vs `x-empty-state`** | manual-requisition, quotation-wizard | Migrar a `x-empty-state` o crear variante `dashed` |

---

## Recomendaciones de Estandarización

### 1. Componentes a crear/extraer

| Componente | Encapsula | Usado en |
|---|---|---|
| `<x-search-input>` | Search bar con icon, clear button, debounce | 9 vistas |
| `<x-filter-bar>` | Search + Filter toggle + Clear | 6 vistas |
| `<x-submit-button>` | Botón con loading spinner integrado | Todos los modales y forms |
| `<x-totals-summary>` | Sección de subtotal/IVA/total | Manual, Wizard, Budget |
| `<x-preview-modal>` | Modal de vista previa de documento | requisition-index, quotation-wizard |
| `<x-table-inline-input>` | Input transparente para edición en tabla | Wizard, Budget |

### 2. Reglas de diseño a documentar

```
REGLA 1: Todos los page headers usan <x-page-header>
REGLA 2: Todos los labels/inputs/errors usan <x-form-field>
REGLA 3: Loading spinner usa la clase .spinner (CSS), no SVG inline
REGLA 4: Crear/Editar en 1 solo modal con título condicional
REGLA 5: Search bar = sm:w-72, siempre con Clear button
REGLA 6: Tablas de items usan .table-embedded, no estilos inline
REGLA 7: Botón Save siempre en footer, nunca en header
REGLA 8: Tokens tipográficos del sistema, nunca text-sm/text-xs de Tailwind
REGLA 9: Error margin = mt-1, consistente en todo el sistema
REGLA 10: Empty state siempre via <x-empty-state> (crear variante dashed si se necesita)
```

### 3. Priorización de correcciones

**Sprint 1 (quick wins de alto impacto):**
- [ ] Unificar modales duplicados en Proyectos y Usuarios
- [ ] Migrar tabla Manual Requisition a `table-embedded`
- [ ] Budget Wizard: usar `x-page-header`, agregar loading en submit, mover save al footer
- [ ] Reemplazar todos los SVG spinners por `.spinner` class
- [ ] Estandarizar search input del Cotizador Index

**Sprint 2 (componentes compartidos):**
- [ ] Crear `<x-submit-button>` y migrar todos los modales
- [ ] Crear `<x-search-input>` y migrar todas las vistas
- [ ] Crear `<x-preview-modal>` y eliminar duplicación
- [ ] Decidir adopción de `<x-form-field>` en todo el sistema

**Sprint 3 (unificación de flujos):**
- [ ] Estandarizar sección de totales como componente
- [ ] Alinear el patrón de agregar items entre Manual/Wizard/Budget
- [ ] Aplicar stats cards consistentemente o eliminar de Gastos
- [ ] Corregir tokens tipográficos (`text-sm` → `text-small`)
