# Auditoría Profesional UX/UI — Muulsinik ERP

> **Evaluador**: Senior UX/UI Designer · SaaS / ERP Enterprise  
> **Fecha**: 3 de Junio 2026  
> **Alcance**: Layout principal, Dashboard, Requisiciones, Proveedores, Proyectos, Gastos, Catálogos (Categorías, Medidas), Búsqueda Global, Componentes compartidos  
> **Referencia**: Linear, Stripe Dashboard, Notion, Jira, Procore, Google Workspace, Figma

---

## Puntuación General

| Dimensión | Puntuación | Veredicto |
|---|---|---|
| **UI (Diseño Visual)** | **74 / 100** | Sólida base industrial-minimal, con áreas de inconsistencia y oportunidades de elevación premium |
| **UX (Experiencia de Usuario)** | **71 / 100** | Flujos funcionales pero con fricciones en descubrimiento, retroalimentación y eficiencia |
| **Accesibilidad** | **58 / 100** | Deficiencias significativas en contraste, semántica y navegación por teclado |
| **Diseño Empresarial** | **72 / 100** | Transmite profesionalismo; falta pulido para competir con SaaS de producción |
| **GENERAL** | **69 / 100** | ERP funcional con buen fundamento de diseño; requiere pulido para alcanzar calidad comercial |

---

## 1. Diseño Visual

### 1.1 Jerarquía Visual

**Severidad: Medio**

**Lo que funciona bien:**
- El sistema tipográfico fluido ([app.css:26-33](file:///c:/Users/HP/Documents/Sistemas/Sistemas-Muulsinik/resources/css/app.css#L26-L33)) con `clamp()` es una decisión sofisticada que pocos ERPs implementan
- La escala `display → h1 → h2 → h3 → body → small → xs` está bien proporcionada
- Los `stat-card` del dashboard ([dashboard.blade.php:22-63](file:///c:/Users/HP/Documents/Sistemas/Sistemas-Muulsinik/resources/views/livewire/dashboard.blade.php#L22-L63)) tienen buena jerarquía: número grande + etiqueta muted

**Problemas identificados:**
- **Las tarjetas de requisiciones tienen demasiados niveles de información** sin suficiente separación visual. En una sola tarjeta hay: título, badge, proyecto, fecha, usuario, vendedor, anotaciones, total, footer con acciones y tabla colapsable. Son ~6 niveles de profundidad informativa en un espacio comprimido
- **El "total estimado" en requisiciones** ([requisition-index.blade.php:157-160](file:///c:/Users/HP/Documents/Sistemas/Sistemas-Muulsinik/resources/views/livewire/requisitions/requisition-index.blade.php#L157-L160)) usa `text-h2` que compite visualmente con el header de la página
- **Dashboard: la sección "Métricas"** repite datos que ya están en los KPI cards de arriba (proveedores, gasto total)

**Impacto**: El usuario necesita escanear demasiada información para encontrar lo relevante. La fatiga visual aumenta con cada requisición.

**Recomendación**: Aplicar el principio de "Progressive Disclosure" de Linear — mostrar solo la información esencial en la vista de lista (número, estado, proyecto, total) y revelar detalles en una vista expandida o panel lateral.

**Referencia**: Linear muestra en la lista solo: identificador, título, estado y asignado. Todo lo demás se revela al hacer clic. Jira en su vista de board muestra máximo 3 datos por tarjeta.

---

### 1.2 Uso del Espacio en Blanco

**Severidad: Bajo-Medio**

**Lo que funciona bien:**
- El padding de cards (`1.375rem` = 22px) es apropiado
- El spacing del sidebar es limpio y bien proporcionado
- El `gap-3` entre tarjetas es correcto

**Problemas identificados:**
- **El filter panel desplegable** ([requisition-index.blade.php:78](file:///c:/Users/HP/Documents/Sistemas/Sistemas-Muulsinik/resources/views/livewire/requisitions/requisition-index.blade.php#L78)) usa `!p-4` con `!bg-surface-hover/50` — los `!important` overrides son una señal de que el spacing del componente `.card` no es flexible
- **Las tarjetas de proveedores** ([supplier-index.blade.php:59-66](file:///c:/Users/HP/Documents/Sistemas/Sistemas-Muulsinik/resources/views/livewire/suppliers/supplier-index.blade.php#L59-L66)) tienen un bloque "notas" con `mb-4` que deja espacio vacío cuando no hay notas (solo se renderiza el div wrapper vacío `.space-y-1.5.mb-4`)
- **Los modales** tienen `p-5` en el body pero el header ya tiene su propio padding (`px-5 pt-5 pb-4`), creando una doble separación visual

**Recomendación**: 
1. Condicionar el wrapper de notas con `@if($supplier->notes)` para evitar espacio fantasma
2. Crear un token de spacing para modales: `--modal-gutter: 1.25rem`

---

### 1.3 Alineación y Consistencia

**Severidad: Alto**

**Problemas identificados:**

| Inconsistencia | Donde ocurre | Impacto |
|---|---|---|
| **Vistas de listado usan formatos diferentes** | Requisiciones=cards expandibles, Gastos=tabla, Categorías=tabla, Proveedores=grid de cards | El usuario no puede predecir cómo interactuar con cada módulo |
| **Iconos de estado de requisición** usan `rounded-xl` (L108) mientras los de proveedores usan `rounded-lg` (L43) | requisition-index vs supplier-index | Micro-inconsistencia visual |
| **El botón "Nueva Manual" en requisiciones** es `btn-secondary` mientras "Subir Cotización" es `btn-primary` — pero ambos son acciones primarias de creación | [requisition-index.blade.php:21-28](file:///c:/Users/HP/Documents/Sistemas/Sistemas-Muulsinik/resources/views/livewire/requisitions/requisition-index.blade.php#L21-L28) | Confusión de jerarquía de acciones |
| **Tablas de catálogos** no tienen headers en las filas de tabla (`<th>`) alineados con el rest de tablas del dashboard | category-index vs dashboard | Densidad informativa inconsistente |

**Referencia**: Stripe Dashboard mantiene un patrón 100% consistente: cada listado principal usa tabla con columnas idénticas de padding. Linear usa una sola variación visual para todas las listas (rows con hover uniforme).

---

### 1.4 Tipografía

**Severidad: Bajo**

**Lo que funciona bien:**
- Plus Jakarta Sans es una excelente elección para ERP — geométrica pero con calidez
- La escala tipográfica fluida con `clamp()` es premium
- `tabular-nums` en valores monetarios ([dashboard.blade.php:29](file:///c:/Users/HP/Documents/Sistemas/Sistemas-Muulsinik/resources/views/livewire/dashboard.blade.php#L29)) es una decisión experta que alinea columnas numéricas

**Problemas identificados:**
- **Inter está referenciada como fallback** en `--font-sans` pero **nunca se carga** en la etiqueta `<link>` del layout ([app.blade.php:14](file:///c:/Users/HP/Documents/Sistemas/Sistemas-Muulsinik/resources/views/components/layouts/app.blade.php#L14)) — solo se carga Plus Jakarta Sans
- **El font-weight `450`** en `.nav-link` ([app.css:385](file:///c:/Users/HP/Documents/Sistemas/Sistemas-Muulsinik/resources/css/app.css#L385)) es un valor variable-font que puede no renderizar correctamente en todos los navegadores ya que Plus Jakarta Sans no tiene granularidad de 450
- **La línea de rol del usuario en sidebar** tiene un font-size inline hardcodeado: `style="font-size: 0.625rem;"` ([app.blade.php:159](file:///c:/Users/HP/Documents/Sistemas/Sistemas-Muulsinik/resources/views/components/layouts/app.blade.php#L159)) que viola los tokens de diseño

**Recomendación**: Usar `text-xs-fluid` para el rol (ya definido en el sistema), cargar Inter como fallback explícito o eliminarlo del stack, verificar renderizado del weight 450.

---

### 1.5 Paleta de Colores

**Severidad: Bajo**

**Lo que funciona bien:**
- El azul corporativo `#0230c8` como acento primario transmite seriedad y confianza
- La escala primaria de 50-900 está bien graduada
- Los colores semánticos (success, warning, danger, info) son claros y diferenciables
- El fondo `#F2F3F5` (surface-main) es un gris concreto neutro perfecto para ERP industrial

**Problemas identificados:**
- **Colores hardcodeados**: El scrollbar thumb usa `#d1d5db` y `#9ca3af` ([app.css:100-106](file:///c:/Users/HP/Documents/Sistemas/Sistemas-Muulsinik/resources/css/app.css#L100-L106)) en lugar de tokens. Las tablas usan `#F4F5F7` para border-bottom y `#FAFBFF`/`#F8F9FB` para hovers ([app.css:459-469](file:///c:/Users/HP/Documents/Sistemas/Sistemas-Muulsinik/resources/css/app.css#L459-L469)) — estos deberían ser tokens
- **La paleta de `dynamic-badge`** ([dynamic-badge.blade.php:5-16](file:///c:/Users/HP/Documents/Sistemas/Sistemas-Muulsinik/resources/views/components/dynamic-badge.blade.php#L5-L16)) usa colores de Tailwind directos (blue-50, purple-50, indigo-50...) que no necesariamente armonizan con la paleta corporativa

**Recomendación**: Migrar todos los hex hardcodeados a tokens CSS custom. Para el dynamic-badge, definir una paleta "tag" que use tonos derivados del primario.

---

### 1.6 Contraste y Accesibilidad

**Severidad: Crítico** ⚠️

**Problemas identificados:**
- `--color-text-muted: #9299A8` sobre `#FFFFFF` → **ratio ≈ 3.3:1** — **FALLA WCAG AA** para texto normal (requiere 4.5:1). Esto afecta **toda** la metadata secundaria en la app (fechas, creadores, subtítulos, labels de filtros)
- `--color-text-secondary: #52596B` sobre `#FFFFFF` → **ratio ≈ 5.2:1** — Pasa AA pero justo en el límite
- Los badges con fondo claro (`bg-amber-50` + `text-amber-600`) pueden tener ratios insuficientes
- Los iconos `opacity-40` en empty states ([empty-state.blade.php:5](file:///c:/Users/HP/Documents/Sistemas/Sistemas-Muulsinik/resources/views/components/empty-state.blade.php#L5)) son puramente decorativos pero afectan la percepción de funcionalidad

**Impacto**: Usuarios con baja visión no podrán leer metadata, labels de filtros, timestamps ni estados. Esto afecta particularmente a usuarios en obra con pantallas expuestas al sol.

**Recomendación**: 
1. Oscurecer `--color-text-muted` a `#6B7280` (ratio ≈ 4.6:1)
2. Verificar cada badge variant con herramientas como contrast-checker
3. Linear y Notion usan grises muted de `#6B6F76` (mínimo) para garantizar legibilidad

---

### 1.7 Consistencia entre Componentes

**Severidad: Medio**

| Componente | Patrón A | Patrón B |
|---|---|---|
| **Spinner de loading** | SVG inline repetido en 5+ modales | Clase `.spinner` definida en CSS pero **nunca usada** |
| **Botones de submit en modales** | Todos usan el patrón `relative` + `wire:loading` + SVG spinner | Correcto pero verboso — podría extraerse a componente |
| **Confirmaciones de eliminación** | `wire:confirm` + interceptor SweetAlert global | Consistente ✅ |
| **Empty states** | Componente `x-empty-state` reutilizado | Consistente ✅ |
| **Search inputs** | Patrón idéntico en 5 módulos | Debería ser componente (DRY) |

**Recomendación**: Crear un componente `<x-submit-button>` que encapsule el patrón de loading. Extraer el search input a un componente `<x-search-input>`. Reemplazar los SVG spinners por la clase `.spinner` ya definida.

---

## 2. Experiencia de Usuario (UX)

### 2.1 Facilidad de Aprendizaje

**Severidad: Medio**

**Lo que funciona bien:**
- El sidebar categórico (Principal → Administración → Catálogos) es claro
- Los placeholders en formularios dan contexto: "Ej. Materiales del Sureste", "Ej. Pieza, Metro"
- El atajo `Ctrl+K` para búsqueda global es un patrón power-user excelente

**Problemas identificados:**
- **No hay onboarding** ni tooltips para nuevos usuarios
- **Los action icons en cards/tablas** (pencil, trash, eye, send, check, x, file-search, file-down) requieren hover para revelar su significado via `title`. No hay labels visibles
- **El flujo de "Subir Cotización" vs "Nueva Manual"** en requisiciones no es autoexplicativo — el usuario necesita entender la diferencia conceptual sin guía

**Referencia**: Notion muestra un tour de 3 pasos en el primer uso. Linear tiene tooltips con accesos rápidos de teclado.

---

### 2.2 Flujo de Navegación

**Severidad: Medio**

**Lo que funciona bien:**
- Sidebar fijo con indicador activo (background azul + peso 600) es claro
- La búsqueda global cubre los 4 recursos principales
- Los breadcrumbs implícitos via `x-page-header subtitle` (ej: "Compras → Requisiciones") orientan al usuario

**Problemas identificados:**
- **No hay forma de navegar entre módulos relacionados sin pasar por el sidebar**. Por ejemplo, desde una requisición ver el proveedor vinculado, o desde gastos ir al proyecto. Los datos están aislados en silos
- **El dashboard no tiene acciones directas** desde las tablas recientes. Las filas no son clickeables
- **No hay breadcrumb real ni "back navigation"** en los flujos de creación (Wizard de cotización)

**Recomendación**: Hacer las filas de tabla del dashboard clickeables con `wire:navigate`. Agregar links contextuales desde requisiciones → proveedor, proyecto → gastos.

---

### 2.3 Claridad de Acciones Principales

**Severidad: Alto**

**Problemas identificados:**
- **En requisiciones, hay demasiados action icons** ([requisition-index.blade.php:179-221](file:///c:/Users/HP/Documents/Sistemas/Sistemas-Muulsinik/resources/views/livewire/requisitions/requisition-index.blade.php#L179-L221)): hasta 5 iconos (ver cotización, enviar, aprobar, rechazar, PDF, eliminar). Esto crea "button overload" 
- **Los botones de aprobar/rechazar** (check/x) son icon-only sin confirmación visual previa de qué se está haciendo — se confunden con "ver" y "cerrar"
- **Las acciones destructivas (eliminar)** están al mismo nivel visual que las no destructivas. No hay separación ni confirmación contextual previa

**Impacto**: Un usuario puede aprobar accidentalmente una requisición queriendo expandir los productos, o eliminar queriendo rechazar.

**Referencia**: Jira ofrece las acciones de cambio de estado en un dropdown "Transitions" separado de las acciones CRUD. Linear agrupa las acciones en un menú contextual `⋯` dejando solo la acción primaria visible.

**Recomendación**: 
1. Agrupar acciones secundarias en un dropdown `⋯` (kebab menu)
2. Dejar visibles solo 1-2 acciones primarias + el kebab
3. Las acciones de cambio de estado (aprobar/rechazar) deberían ir en el dropdown o como botones con texto

---

### 2.4 Reducción de Pasos Innecesarios

**Severidad: Medio**

- **La tabla colapsable de productos** en requisiciones es eficiente (click-to-expand) ✅
- **Los filtros expandibles** son buen patrón para no saturar la barra ✅
- **Sin embargo: crear un gasto requiere abrir modal → llenar 6+ campos → submit**. No hay atajos como "duplicar gasto anterior" ni "crear desde requisición"
- **Los modales de creación/edición de proyectos** son duplicados ([project-index.blade.php:137-267](file:///c:/Users/HP/Documents/Sistemas/Sistemas-Muulsinik/resources/views/livewire/projects/project-index.blade.php#L137-L267)) — podrían unificarse en un solo modal que cambie título y action según el contexto (como ya se hace en categorías y medidas)

---

### 2.5 Retroalimentación al Usuario

**Severidad: Alto**

**Lo que funciona bien:**
- Toast notifications con SweetAlert2 para success/error/warning
- Loading states en botones de submit (spinner + opacity)
- `wire:confirm` interceptado por SweetAlert para confirmaciones

**Problemas identificados:**
- **No hay skeleton loading** al navegar entre módulos. La clase `.skeleton` está definida en CSS ([app.css:679-694](file:///c:/Users/HP/Documents/Sistemas/Sistemas-Muulsinik/resources/css/app.css#L679-L694)) pero **nunca se usa** en ningún blade
- **No hay indicadores de carga para búsqueda**. Al escribir en el search, no hay feedback visual de que se está filtrando (no hay spinner ni loading bar)
- **No hay feedback de cambio de estado en requisiciones**. Después de aprobar/rechazar, solo aparece el toast — no hay animación de transición del badge de estado ni de la card
- **Las acciones de la tabla no muestran loading** (ej: al eliminar un gasto, el row desaparece sin transición)

**Referencia**: Linear usa skeleton loading para toda la app. Stripe Dashboard muestra una progress bar sutil en el top durante navegación. Notion tiene transiciones de opacity al actualizar contenido.

**Recomendación**: 
1. Implementar `wire:loading` a nivel de tabla/listado con skeleton placeholders
2. Agregar un progress bar indeterminado en el header durante Livewire requests (la animación `progress-indeterminate` ya está definida en CSS pero no se usa)
3. Agregar `x-transition` a las cards/rows al actualizar

---

### 2.6 Prevención de Errores

**Severidad: Medio**

**Lo que funciona bien:**
- Validación server-side con mensajes de error inline (`@error`)
- `wire:confirm` en acciones destructivas
- `maxlength="13"` en el campo RFC

**Problemas identificados:**
- **No hay validación en tiempo real** (client-side). El usuario debe hacer submit para ver errores
- **Los campos numéricos** (presupuesto, monto) permiten valores negativos (`type="number"` sin `min="0"`)
- **No hay protección contra double-submit** más allá del `wire:loading.attr="disabled"` — si Livewire es lento, un click rápido podría duplicar
- **El campo de fecha** no tiene restricciones (se puede poner una fecha futura para un gasto pasado)
- **No hay confirmación al cerrar un modal con datos sin guardar** (pérdida de datos silenciosa)

**Referencia**: Stripe no permite cerrar formularios con cambios pendientes sin confirmación. Google Forms muestra validación inline al blur de cada campo.

---

## 3. Arquitectura de la Información

### 3.1 Organización del Contenido

**Severidad: Medio**

**Lo que funciona bien:**
- El sidebar organiza en 3 secciones lógicas: Principal (operativo), Administración (gestión), Catálogos (configuración)
- El dashboard prioriza correctamente: KPIs → Chart → Tablas recientes

**Problemas identificados:**
- **"Cotizador" bajo "Principal"** es confuso — ¿es un módulo separado o un flujo de requisiciones?
- **"Proveedores" bajo "Administración"** — en la lógica de compras de construcción, proveedores es operativo, no administrativo
- **No hay sección de "Configuración" visible en el sidebar principal** — está escondido en el footer del sidebar al mismo nivel que logout

---

### 3.2 Prioridad de Información

**Severidad: Alto**

**Dashboard específicamente:**
- **Las métricas del panel derecho** ([dashboard.blade.php:87-141](file:///c:/Users/HP/Documents/Sistemas/Sistemas-Muulsinik/resources/views/livewire/dashboard.blade.php#L87-L141)) repiten datos de los KPI strips. "Proveedores: X" aparece en stat-card Y en métricas
- **La query directa al modelo en la vista** ([dashboard.blade.php:136](file:///c:/Users/HP/Documents/Sistemas/Sistemas-Muulsinik/resources/views/livewire/dashboard.blade.php#L136)): `Requisition::where('status', 'aprobada')->count()` — esto es un antipatrón que viola SoC y puede causar N+1 en views

**Recomendación**: Mover toda la lógica de datos al componente Livewire. Eliminar métricas duplicadas y reemplazar con información complementaria (ej: tendencia vs mes anterior, alertas de presupuesto).

---

## 4. Componentes e Interacción

### 4.1 Botones

**Severidad: Bajo** ✅

- **Bien implementados**: `btn-primary`, `btn-secondary`, `btn-danger` tienen tamaños consistentes (`min-height: 2.25rem`)
- **Focus-visible** implementado ([app.css:896-908](file:///c:/Users/HP/Documents/Sistemas/Sistemas-Muulsinik/resources/css/app.css#L896-L908)) ✅
- **States**: hover, active, disabled están definidos ✅
- **Micro-mejora**: Los icon buttons (`btn-icon-*`) no tienen `min-width`/`min-height`, lo que puede hacer que sean muy pequeños en touch — deberían ser 36×36px mínimo (estándar WCAG para touch targets)

### 4.2 Formularios

**Severidad: Medio**

- **La clase `.input`** está bien definida con focus ring, placeholder styling, y transitions ✅
- **Select nativo** tiene appearance customizado ✅
- **Falta**: error state visual en el input mismo (border rojo). Los errores se muestran debajo pero el input no cambia de estilo
- **Falta**: success state (border verde después de validación correcta)
- **El campo RFC** en proveedores no tiene validación visual de formato (pattern)

### 4.3 Tablas

**Severidad: Bajo** ✅

- **`.table-container`** y **`.table-embedded`** están muy bien implementados
- Headers uppercase con tracking y color muted siguen el patrón de Stripe ✅
- Row hover es sutil y no distrae ✅
- **Falta**: sortable columns (ninguna tabla permite ordenar por columna)
- **Falta**: selección múltiple con checkboxes para bulk actions

### 4.4 Tarjetas

- **Bien implementadas** con bordes sutiles, sombra mínima, hover elevation ✅
- **Card anatomy** bien definida: card-header, card-title, card-subtitle ✅
- **Falta**: variante de card para estado "selected" o "highlighted"

### 4.5 Modales

**Severidad: Bajo** ✅

- **El componente `x-modal`** ([modal.blade.php](file:///c:/Users/HP/Documents/Sistemas/Sistemas-Muulsinik/resources/views/components/modal.blade.php)) está muy bien:
  - Auto-focus al primer input ✅
  - Backdrop blur ✅
  - Animaciones de entrada (overlay + panel) ✅
  - Scroll para contenido largo ✅
  - Responsive sizing ✅
- **Falta**: cierre con tecla `Escape` (no hay `@keydown.esc`)
- **Falta**: trap de foco (tab cycling dentro del modal)

### 4.6 Menús

- **No existen menús contextuales** (kebab/dropdown actions). Todo son icon buttons directos
- **El custom-select** funciona como menú dropdown para filtros — bien implementado con search, fixed positioning, y repositioning ✅

### 4.7 Estados de Carga

**Severidad: Alto**

- **Definido pero no usado**: `.skeleton` shimmer effect en CSS pero cero implementaciones en Blade
- **Definido pero no usado**: `progress-indeterminate` animation
- **Botones de submit** tienen loading ✅ pero es el ÚNICO loading visual en toda la app
- **Las tablas, listas, y cards no tienen skeleton loading**, lo que causa "flashes" de contenido vacío durante navegación Livewire

### 4.8 Estados Vacíos

**Severidad: Bajo** ✅

- **`x-empty-state`** es un componente limpio y reutilizado consistentemente
- Tiene icono, título y mensaje opcional
- Acepta slot para CTAs
- **Podría mejorar**: Agregar un CTA por defecto ("Crear primer X") en lugar de solo texto informativo

### 4.9 Mensajes de Error

**Severidad: Medio**

- **Errores de validación** se muestran inline debajo de inputs ✅
- **Toasts de error** vía SweetAlert ✅
- **Falta**: error state visual en el campo (border rojo, background rojo tenue)
- **Falta**: mensaje de error general a nivel de formulario (para errores no vinculados a un campo específico)
- **Falta**: error page (404, 500) personalizada

---

## 5. Diseño Empresarial

### ¿La interfaz transmite profesionalismo?

**Puntuación: 7.2 / 10**

**Elementos que SÍ transmiten profesionalismo:**
- Tipografía Plus Jakarta Sans — moderna y seria
- Paleta corporativa azul contenida (#0230c8) — no grita, acent sutil
- Sidebar limpio con secciones categorizadas
- Tablas con headers uppercase y tracking — patrón Stripe
- Confirmaciones con SweetAlert estilizados
- Búsqueda global con `Ctrl+K` — patrón power-user

**Elementos que hacen parecer la interfaz AMATEUR:**
1. **Ausencia total de loading/skeleton states** — la app parece "pegarse" entre vistas
2. **No hay breadcrumbs** en vistas internas
3. **Los icon buttons son demasiado pequeños** (~25px touch target) para un ERP que se usa en campo
4. **No hay avatares de usuario** — solo una inicial en un cuadrado
5. **Las tablas no son ordenables ni tienen bulk actions**
6. **No hay dark mode** (opcional pero esperado en 2026)
7. **Los toasts de SweetAlert** tienen estilo genérico — no se integran visualmente con el design system
8. **El chart del dashboard** es básico (barras sin interactividad avanzada)

---

### ¿Es adecuada para uso corporativo?

**Sí, con limitaciones.** La interfaz es funcional para un equipo pequeño-mediano de construcción. Para escalar a una empresa mediana-grande, necesita:
- Roles y permisos reflejados visualmente (ya existen en lógica pero no en UI)
- Audit trail visible (quién hizo qué y cuándo)
- Exportación de datos (Excel/CSV) — no se detecta en la UI
- Multi-tenant visual

---

## 6. Accesibilidad

### 6.1 Contraste

**Severidad: Crítico** ⚠️

Ya detallado en §1.6. Los textos `text-muted` (#9299A8) no pasan WCAG AA.

### 6.2 Tamaño de Fuentes

**Severidad: Medio**

- La base es `var(--font-size-body)` = `clamp(0.8125rem, ...)` ≈ **13px** → Pasa el mínimo, pero es ajustado para uso prolongado
- `var(--font-size-xs)` = `clamp(0.6875rem, ...)` ≈ **11px** → **Demasiado pequeño** para metadata importante como fechas y creadores

**Recomendación**: El mínimo legible para texto funcional debería ser 12px. Aumentar `--font-size-xs` a `clamp(0.75rem, ...)`.

### 6.3 Navegación por Teclado

**Severidad: Alto**

- **Los modales no atrapan el foco** (no hay focus trap). El usuario puede Tab fuera del modal al contenido detrás
- **El custom-select** no tiene navegación por teclado completa (no hay arrow keys para navegar opciones)
- **Las cards expandibles** (requisiciones) no tienen `aria-expanded` ni `role="button"`
- **Los icon buttons** no tienen `aria-label` — solo `title` que no es anunciado por screen readers

### 6.4 Semántica HTML

**Severidad: Medio**

- **El sidebar** usa `<aside>` ✅ y `<nav>` ✅
- **El header** usa `<header>` ✅ y `<main>` ✅
- **Los modales** no tienen `role="dialog"` ni `aria-modal="true"`
- **Las tablas** no tienen `<caption>` ni `scope` en headers
- **Los botones de acción** no tienen `aria-label` descriptivo

### 6.5 Cumplimiento WCAG

| Criterio | Estado |
|---|---|
| 1.1.1 Non-text Content | ⚠️ Los iconos no tienen alt text |
| 1.3.1 Info and Relationships | ⚠️ Falta semántica en modales |
| 1.4.3 Contrast (Minimum) | ❌ text-muted falla AA |
| 2.1.1 Keyboard | ❌ Modales y custom selects sin trap/nav |
| 2.4.7 Focus Visible | ✅ Definido en CSS |
| 4.1.2 Name, Role, Value | ⚠️ Faltan aria-labels |

---

## 7. Responsividad

### 7.1 Escritorio

**Severidad: Bajo** ✅

- `max-w-screen-2xl` como contenedor máximo ✅
- Sidebar fijo de 240px con contenido fluido ✅
- Grids de 2-3 columnas para cards ✅
- La app está **optimizada para escritorio** como uso principal — correcto para ERP

### 7.2 Tablet

**Severidad: Medio**

- El sidebar se oculta < `lg` (1024px) con toggle hamburguesa ✅
- Los grids colapsan a 2 columnas en `md` ✅
- **Problema**: Los filtros expandibles en pantallas medianas pueden ser muy anchos (`sm:w-48` no es suficiente para 3 selects en fila)
- **Problema**: Las tablas no son scroll-horizontales en tablet estrecho

### 7.3 Móvil

**Severidad: Medio**

- **La búsqueda global tiene versión mobile** (fullscreen) ✅ — excelente patrón
- **El sidebar mobile** tiene overlay con blur ✅
- **Las tablas** no son responsive — se desbordan. Necesitan `overflow-x-auto` wrapper o convertirse a cards en mobile
- **Los action icons** son demasiado pequeños para touch (~25px vs 44px recomendado)
- **Los modales** podrían ser fullscreen en mobile (actualmente son `max-w-lg` con padding — deja muy poco espacio útil)

---

## Lista Priorizada de Mejoras

### 🔴 Prioridad Crítica (Sprint 1)

| # | Mejora | Impacto |
|---|---|---|
| 1 | **Corregir contraste de text-muted** (#9299A8 → #6B7280 mínimo) | Accesibilidad, legibilidad |
| 2 | **Implementar skeleton loading** en tablas y listados (la clase CSS ya existe) | UX, percepción de velocidad |
| 3 | **Agregar aria-labels** a todos los icon buttons y `role="dialog"` + `aria-modal` a modales | Accesibilidad |
| 4 | **Reducir action icons visibles** a 2 max + dropdown kebab `⋯` | Claridad de acciones |

### 🟠 Prioridad Alta (Sprint 2)

| # | Mejora | Impacto |
|---|---|---|
| 5 | **Extraer search input a componente** `<x-search-input>` | DRY, mantenibilidad |
| 6 | **Extraer submit button a componente** `<x-submit-button>` (reemplazar SVG spinners por `.spinner`) | DRY, consistencia |
| 7 | **Error state visual en inputs** (border rojo + background tenue) | Prevención de errores |
| 8 | **Sortable columns** en tablas principales | Eficiencia operativa |
| 9 | **Estandarizar formato de listado** (elegir cards O tabla, no ambos) | Consistencia |
| 10 | **Agregar `@keydown.esc` y focus trap** en modales | Accesibilidad |

### 🟡 Prioridad Media (Sprint 3-4)

| # | Mejora | Impacto |
|---|---|---|
| 11 | Hacer filas de tabla del dashboard clickeables | Flujo de navegación |
| 12 | Implementar progress bar en top header durante navegación | Feedback de carga |
| 13 | Unificar modales de crear/editar proyecto en uno solo | Reducción de código |
| 14 | Agregar min-width/height a icon buttons (36px mínimo) | Touch targets |
| 15 | Eliminar query directa en dashboard blade | Arquitectura, SoC |
| 16 | Tablas responsive (overflow-x o card-view en mobile) | Mobile UX |
| 17 | Corregir div vacío de notas en proveedores | Espacio visual |

### 🟢 Prioridad Baja (Backlog)

| # | Mejora | Impacto |
|---|---|---|
| 18 | Breadcrumbs en vistas de detalle/wizard | Orientación |
| 19 | Dark mode | Preferencia visual |
| 20 | Exportar datos (CSV/Excel) desde tablas | Funcionalidad enterprise |
| 21 | Toast personalizado integrado al design system (reemplazar SweetAlert toast) | Consistencia visual |
| 22 | Tooltips con atajos de teclado en acciones | Power users |
| 23 | Empty states con CTA de acción | Conversión |
| 24 | Validación client-side en blur | Prevención de errores |

---

## ¿Qué haría un diseñador senior?

### 1. **Establecería un patrón único para vistas de datos** 
Un senior elegiría UN formato (tabla o cards) como default y documentaría cuándo usar el otro. En ERP, las tablas son más eficientes para datos tabulares (gastos, categorías). Las cards son mejores para entidades con preview visual (proyectos con progress bar). Requisiciones podrían ser tabla con row expansion — como hace Jira.

### 2. **Implementaría un Command Palette** (ya a medio camino con Global Search)
La búsqueda global actual busca entidades. Un senior la convertiría en un Command Palette (como Linear `⌘K`, Figma `⌘/`) que también permita ejecutar acciones: "Crear requisición", "Ir a configuración", "Aprobar REQ-45".

### 3. **Diseñaría un sistema de feedback de 3 capas**
1. **Micro**: animación de botón al click (ya existe)
2. **Meso**: skeleton → contenido con fade-in en listas (no existe)
3. **Macro**: progress bar persistente en top header (no existe)

### 4. **Crearía un "Detail Panel" deslizante**
En lugar de modales para ver detalles, usaría un panel lateral (slide-over) como Linear/Notion. Los modales se reservarían solo para formularios de creación/edición. Esto permite mantener contexto de la lista mientras se inspeccionan detalles.

### 5. **Optimizaría la densidad informativa**
Implementaría un toggle "Compact / Comfortable" como Gmail/Google Sheets. En modo compact: menos padding, fuentes más pequeñas, más filas visibles. En modo comfortable: el diseño actual. Para un ERP de obra, la vista compact sería la preferida.

### 6. **Implementaría un sistema de notificaciones inline**
Más allá de los toasts efímeros, agregaría un centro de notificaciones persistente (ya existe el dropdown) con badges de conteo en el sidebar para módulos con pendientes (ej: "Requisiciones" con badge rojo "3" para pendientes de aprobación).

---

## ¿Qué haría falta para que esta interfaz pareciera un producto SaaS profesional?

### Nivel 1: Paridad con herramientas internas (actual → aquí estás)
- [x] Design system tokenizado
- [x] Componentes reutilizables
- [x] Layout responsive básico
- [x] Búsqueda global
- [ ] Loading states completos ← **Gap principal**

### Nivel 2: Paridad con SaaS early-stage
- [ ] Skeleton loading en toda la app
- [ ] Error boundaries y error pages personalizadas
- [ ] Sortable/filterable tables con URL state
- [ ] Keyboard navigation completa
- [ ] Command Palette
- [ ] Breadcrumbs
- [ ] Consistent list/detail pattern

### Nivel 3: Paridad con SaaS established (Linear, Procore)
- [ ] Dark mode con toggle automático
- [ ] Density toggle (compact/comfortable)
- [ ] Drag & drop reordering
- [ ] Inline editing en tablas
- [ ] Real-time collaboration indicators
- [ ] Export/import de datos
- [ ] API documentation / webhooks UI
- [ ] Changelog / release notes en la app
- [ ] Custom branded login/onboarding

### Nivel 4: Paridad con Enterprise (SAP, Oracle, Microsoft 365)
- [ ] Multi-tenant architecture UI
- [ ] SSO / SAML integration UI
- [ ] Audit trail visible
- [ ] Custom dashboards/widgets
- [ ] Role-based UI variations
- [ ] i18n (multi-language)
- [ ] Offline mode
- [ ] Print-optimized views

---

### Conclusión

Muulsinik ERP tiene un **fundamento sólido y bien pensado**. El design system en [app.css](file:///c:/Users/HP/Documents/Sistemas/Sistemas-Muulsinik/resources/css/app.css) es superior a lo que se ve en muchos ERPs internos — la tokenización, las animaciones de modal, los componentes de tabla, y la tipografía fluida demuestran criterio de diseño. 

Los **gaps principales** son de dos tipos:
1. **Infraestructura UX** que ya está definida pero no implementada (skeleton, spinner class, progress animation)
2. **Patrones de interacción avanzados** que separan una herramienta interna de un producto SaaS (command palette, keyboard nav, density toggle, consistent data views)

La inversión para pasar de **69 → 80+** es relativamente baja (Sprint 1-2 de la lista priorizada). Para llegar a **85+** se necesita el Sprint 3-4 y las recomendaciones del diseñador senior.
