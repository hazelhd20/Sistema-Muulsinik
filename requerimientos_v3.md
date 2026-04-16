# Especificación de Requerimientos de Software (ERS)
## Sistema de Gestión para Constructora Muulsinik

**Versión:** 3.0  
**Fecha:** 7 de abril de 2026  
**Estado:** Borrador revisado  
**Elaborado por:** Equipo de Desarrollo  

---

## 1. Introducción

### 1.1 Propósito

El presente documento define los requerimientos funcionales y no funcionales del Sistema de Gestión para la Constructora Muulsinik. Sirve como referencia técnica entre el cliente y el equipo de desarrollo, estableciendo el alcance, las restricciones y las características esperadas del sistema en su primera etapa de implementación, con un enfoque realista acorde al contexto de una residencia profesional.

### 1.2 Alcance del Sistema

El sistema, denominado internamente **Muulsinik ERP v1**, es una aplicación web orientada a la gestión operativa de proyectos de construcción. Su función central —y principal objetivo de esta etapa— es la **carga y procesamiento automático de cotizaciones** en distintos formatos (PDF, JPG, XLSX), con el fin de generar requisiciones de materiales de forma automatizada, minimizando la captura manual de datos.

El objetivo **no es desarrollar un sistema completo y altamente complejo desde la primera versión**, sino resolver de manera efectiva la problemática principal identificada: transformar cotizaciones enviadas por proveedores en requisiciones estructuradas, editables y trazables, aprovechando automatización mediante procesamiento de datos y OCR.

Adicionalmente, se han considerado cuatro módulos complementarios a partir de necesidades expresadas por el cliente: gestión de proyectos, control de gastos y presupuestos, gestión documental, y administración de proveedores y compras. Estos módulos están concebidos como **soporte al flujo principal** y con la intención de permitir una futura expansión del sistema; no constituyen el núcleo de esta etapa de desarrollo.

El sistema está diseñado bajo un **enfoque progresivo y práctico**: se prioriza que cada módulo implementado sea verdaderamente funcional, esté bien organizado y ofrezca una experiencia de usuario intuitiva y fácil de usar. La integración entre módulos es un aspecto clave del diseño, buscando evitar duplicidad de información y mantener coherencia en los datos a lo largo del sistema.

### 1.3 Definiciones y Acrónimos

| Término | Definición |
|---|---|
| ERS | Especificación de Requerimientos de Software |
| OCR | Optical Character Recognition (Reconocimiento Óptico de Caracteres) |
| RF | Requerimiento Funcional |
| RNF | Requerimiento No Funcional |
| CRUD | Create, Read, Update, Delete (operaciones básicas de datos) |
| Homologación | Proceso de vincular productos de distintos proveedores bajo una denominación común |
| Requisición | Documento interno que consolida productos solicitados para un proyecto, generado automáticamente a partir de cotizaciones procesadas |
| Pipeline de procesamiento | Secuencia automatizada de pasos que transforma un archivo de cotización en una requisición estructurada y editable |
| Stack | Conjunto de tecnologías utilizadas en el desarrollo del sistema |
| CDN | Content Delivery Network |

### 1.4 Referencias

- IEEE Std 830-1998: Recommended Practice for Software Requirements Specifications
- IEEE Std 29148-2018: Systems and Software Engineering — Requirements Engineering

### 1.5 Visión General del Documento

El documento se organiza en las siguientes secciones: descripción general del sistema, requerimientos funcionales por módulo, requerimientos no funcionales, propuesta de stack tecnológico, arquitectura general del sistema y restricciones del proyecto.

---

## 2. Descripción General del Sistema

### 2.1 Perspectiva del Producto

Muulsinik ERP v1 es un sistema web de nueva creación que no forma parte de un sistema mayor existente. Su objetivo principal es **automatizar la generación de requisiciones a partir de cotizaciones cargadas por los usuarios**, eliminando el uso disperso de hojas de cálculo y la captura manual de datos. En etapas futuras, el sistema podrá integrar módulos adicionales o conectarse con plataformas contables o de facturación electrónica.

El corazón del sistema es el **pipeline de procesamiento de cotizaciones**: el usuario carga un archivo (PDF, JPG o XLSX) enviado por un proveedor, el sistema extrae automáticamente la información relevante (productos, cantidades, precios, proveedor, tienda y proyecto), la estructura en una requisición editable y la almacena lista para su revisión, aprobación y exportación.

Los módulos complementarios (gestión de proyectos, control de gastos, gestión documental y administración de proveedores) se integran directamente con el módulo de requisiciones para enriquecer el contexto de cada operación —por ejemplo, vinculando automáticamente una cotización procesada al proyecto activo y al proveedor correspondiente— sin duplicar información ni generar inconsistencias en los datos.

### 2.2 Funciones Principales del Sistema

A nivel general, el sistema permitirá:

- **Cargar cotizaciones** en formato PDF (digital o escaneado), JPG y XLSX, y procesarlas automáticamente para generar requisiciones.
- Extraer texto de PDFs digitales directamente; aplicar OCR a imágenes y documentos escaneados.
- Leer datos directamente de celdas en archivos XLSX.
- Estructurar la información extraída (proveedor, tienda, proyecto, productos, cantidades, precios) y presentarla en un formulario editable.
- Notificar al usuario cuando algún campo no pueda identificarse automáticamente, para completarlo manualmente.
- Homologar productos de distintos proveedores bajo un nombre canónico común, facilitando la comparación y la generación de reportes consolidados.
- Gestionar proyectos de construcción con seguimiento de presupuesto y avance.
- Registrar y controlar gastos operativos, distribuyéndolos entre proyectos activos.
- Almacenar y organizar documentos relevantes (contratos, planos, permisos, cotizaciones), manteniendo una relación directa entre las cotizaciones procesadas y sus requisiciones generadas.
- Administrar proveedores, vendedores y órdenes de compra.
- Emitir alertas cuando el gasto alcance un porcentaje determinado del presupuesto.
- Generar reportes de compras por proveedor y por vendedor.
- Controlar el acceso mediante roles de usuario con distintos niveles de permisos.

### 2.3 Roles de Usuario

Para una constructora de tamaño pequeño, se definen tres roles funcionales que cubren todas las necesidades operativas sin complejidad innecesaria:

| Rol | Descripción | Nivel de Acceso |
|---|---|---|
| **Administrador** | Gestiona usuarios, roles, configuración global, catálogo de productos y homologaciones. Accede a todos los módulos. | Total |
| **Encargado de Compras** | Carga cotizaciones, procesa requisiciones, administra proveedores y vendedores, genera órdenes de compra y consulta reportes. | Alto |
| **Supervisor / Operativo** | Consulta proyectos activos, registra gastos, revisa requisiciones y documentos asociados a su proyecto. | Medio (lectura en reportes, escritura en gastos y solicitudes) |

> **Nota:** El Administrador puede asignar o revocar roles a cualquier usuario desde el panel de configuración. Los permisos de cada rol son configurables por el administrador para adaptarse a futuros ajustes operativos sin necesidad de intervención del equipo de desarrollo.

### 2.4 Entorno Operativo

El sistema operará como aplicación web accesible desde navegadores modernos (Chrome, Firefox, Edge, Safari). Será responsivo para su uso en dispositivos móviles y tabletas, dado que los supervisores de campo pueden requerir acceso desde obra. El despliegue se realizará en la nube, con opciones de bajo costo descritas en la sección de stack tecnológico.

### 2.5 Restricciones de Diseño e Implementación

- Primera etapa: sistema monolítico con arquitectura MVC, sin microservicios.
- Desarrollo progresivo: se implementarán primero los módulos de mayor impacto operativo (requisiciones y procesamiento de cotizaciones), seguidos por los módulos de soporte.
- Sin integraciones externas en v1 (no se conecta a SAT, ERP contable ni bancos).
- El sistema debe ser mantenible por un equipo pequeño (1–3 desarrolladores).
- El alcance de desarrollo está ajustado al tiempo y recursos disponibles en el contexto de una residencia profesional, priorizando calidad funcional sobre cantidad de características.

### 2.6 Suposiciones y Dependencias

- La constructora cuenta con acceso a internet estable en sus oficinas principales.
- Los documentos de cotización son proporcionados en los formatos acordados (PDF, JPG, XLSX).
- El cliente designará a un usuario con rol Administrador responsable de la configuración inicial.

---

## 3. Requerimientos Funcionales

Los requerimientos funcionales se identifican con el formato **RF-[Módulo]-[Número]**.

---

### 3.1 Módulo: Autenticación y Gestión de Usuarios

**RF-AUTH-01 — Inicio de sesión seguro**  
El sistema deberá autenticar a los usuarios mediante correo electrónico y contraseña. Las contraseñas deberán almacenarse con hash bcrypt. Se implementará protección contra ataques de fuerza bruta mediante bloqueo temporal tras cinco intentos fallidos.

**RF-AUTH-02 — Gestión de roles y permisos**  
El administrador podrá crear, editar y desactivar usuarios. Cada usuario tendrá asignado un rol que determina las vistas y acciones disponibles. Los roles predefinidos son: Administrador, Encargado de Compras y Supervisor / Operativo.

**RF-AUTH-03 — Selección de proyecto activo**  
Una vez autenticado, el usuario seleccionará el proyecto activo mediante un selector global visible en la barra de navegación superior. Todas las operaciones de registro de gastos, carga de cotizaciones, requisiciones y compras quedarán automáticamente asociadas al proyecto activo. El usuario podrá cambiar de proyecto activo en cualquier momento sin necesidad de cerrar sesión.

**RF-AUTH-04 — Restablecimiento de contraseña**  
El sistema enviará un enlace de restablecimiento de contraseña al correo del usuario mediante un token de un solo uso con vigencia de 60 minutos.

---

### 3.2 Módulo de Gestión de Proyectos

Este módulo actúa como contexto organizador para el resto del sistema. Cada proyecto constituye el punto de referencia al que se vinculan requisiciones, gastos, documentos y compras, garantizando coherencia en los datos entre módulos.

**RF-PROY-01 — Registro de proyectos**  
El sistema permitirá crear proyectos con los siguientes campos: nombre, descripción, cliente, fecha de inicio, fecha estimada de término, presupuesto total asignado y estado (activo, en pausa, completado, cancelado).

**RF-PROY-02 — Seguimiento de avance presupuestal**  
El sistema calculará automáticamente el porcentaje de presupuesto consumido en tiempo real, sumando todos los gastos registrados vinculados al proyecto. Este indicador será visible en el panel principal del proyecto.

**RF-PROY-03 — Generación de presupuestos rápidos**  
Para proyectos de menor escala, el sistema ofrecerá un asistente de presupuesto rápido basado en una lista de conceptos y costos unitarios. El presupuesto generado podrá exportarse en PDF.

**RF-PROY-04 — Dashboard por proyecto**  
Cada proyecto contará con un panel de resumen que mostrará: presupuesto total, gasto acumulado, gasto del mes actual, número de requisiciones pendientes y documentos recientes.

**RF-PROY-05 — Historial de proyectos**  
El sistema conservará el historial completo de proyectos finalizados, permitiendo consultar su información de manera de solo lectura.

---

### 3.3 Módulo de Control de Gastos y Presupuestos

**RF-GASTO-01 — Registro de gastos operativos**  
El sistema permitirá registrar gastos con los siguientes atributos: concepto, monto, fecha, categoría, proyecto asociado, comprobante adjunto (imagen o PDF) y usuario que lo registra.

**RF-GASTO-02 — Distribución de gastos entre proyectos**  
Un gasto podrá asignarse a uno o más proyectos activos durante el año fiscal. En caso de distribución entre varios proyectos, el usuario deberá especificar el porcentaje o monto asignado a cada uno.

**RF-GASTO-03 — Alertas de presupuesto**  
El sistema enviará una notificación interna (y opcionalmente por correo) cuando el gasto acumulado de un proyecto alcance el 70%, el 90% y el 100% del presupuesto asignado. Los umbrales de alerta serán configurables por el administrador.

**RF-GASTO-04 — Reporte de gastos por proveedor y por vendedor**  
El módulo generará reportes consolidados que mostrarán el total de compras realizadas por proveedor y por vendedor en un período definido. Los reportes serán exportables en PDF y XLSX.

**RF-GASTO-05 — Cierre de período**  
El sistema permitirá marcar un período mensual como cerrado para efectos de control contable, impidiendo la modificación retroactiva de registros en dicho período.

---

### 3.4 Módulo de Gestión Documental

Este módulo está **directamente integrado con el módulo de requisiciones**: cuando una cotización es procesada y se genera una requisición, el archivo original (PDF, JPG o XLSX) queda automáticamente almacenado y vinculado a esa requisición dentro del repositorio documental del proyecto. Esto evita la duplicidad de cargas manuales y garantiza trazabilidad entre el documento fuente y la requisición resultante.

Los demás tipos de documentos (contratos, planos, permisos) se gestionan de forma independiente dentro del repositorio por proyecto, sin duplicar la lógica del módulo de requisiciones.

**RF-DOC-01 — Repositorio de documentos por proyecto**  
Cada proyecto contará con un repositorio de archivos donde podrán cargarse documentos en formatos PDF, DOCX, XLSX, JPG y PNG. Los documentos se organizarán por categorías: contratos, planos, permisos, cotizaciones y otros.

**RF-DOC-02 — Vinculación automática de cotizaciones procesadas**  
Cuando el usuario cargue una cotización en el módulo de requisiciones y esta sea procesada exitosamente, el archivo original quedará registrado automáticamente en la categoría "Cotizaciones" del repositorio documental del proyecto activo, vinculado a la requisición generada. El usuario no necesitará subirlo manualmente al módulo documental.

**RF-DOC-03 — Visualización de documentos**  
Los documentos PDF e imágenes podrán visualizarse directamente en el navegador sin necesidad de descarga. Los demás formatos ofrecerán opción de descarga.

**RF-DOC-04 — Control de versiones básico**  
Al cargar una nueva versión de un documento existente, el sistema conservará la versión anterior y registrará la fecha y el usuario que realizó la actualización.

**RF-DOC-05 — Búsqueda de documentos**  
El sistema permitirá buscar documentos por nombre, categoría, proyecto y fecha de carga.

---

### 3.5 Módulo de Administración de Proveedores y Compras

**RF-PROV-01 — Registro de proveedores**  
El sistema permitirá registrar proveedores con los siguientes campos: nombre comercial, razón social, RFC, giro, dirección, teléfono, correo electrónico, página web, contacto principal y categoría de productos/servicios que ofrece.

**RF-PROV-02 — Registro de vendedores por proveedor**  
Cada proveedor podrá tener uno o más vendedores registrados con nombre, teléfono y correo electrónico.

**RF-PROV-03 — Historial de compras por proveedor**  
El sistema mostrará el historial completo de compras realizadas a cada proveedor, con filtros por proyecto, período y monto.

**RF-PROV-04 — Registro de órdenes de compra**  
El sistema permitirá generar órdenes de compra asociadas a un proveedor y un proyecto. Una orden de compra podrá originarse desde una requisición aprobada o crearse de forma independiente.

**RF-PROV-05 — Reporte de compras por proveedor y vendedor**  
El sistema generará reportes que consoliden el total de compras agrupado por proveedor y por vendedor, exportables en PDF y XLSX.

---

### 3.6 Módulo de Requisiciones

Este módulo es el **núcleo funcional del sistema** y la principal razón de ser de esta etapa de desarrollo. Su objetivo es transformar automáticamente las cotizaciones enviadas por proveedores en requisiciones estructuradas, editables y exportables, reduciendo al mínimo la captura manual de información.

#### 3.6.1 Pipeline de Procesamiento de Cotizaciones

**RF-REQ-01 — Carga de archivos de cotización**  
El sistema permitirá al usuario cargar archivos de cotización directamente desde la pantalla de nueva requisición. Se aceptarán los siguientes formatos:

- **XLSX:** Los datos se leerán directamente de las celdas de la hoja de cálculo. El sistema permitirá configurar el mapeo de columnas (ej. columna A = nombre del producto, columna B = cantidad) para adaptarse a distintos formatos de proveedores.
- **PDF con texto digital:** El sistema detectará que el documento contiene texto seleccionable y extraerá su contenido directamente, sin necesidad de OCR.
- **PDF escaneado / JPG:** El sistema detectará la ausencia de texto digital y aplicará OCR (Reconocimiento Óptico de Caracteres) para extraer el contenido. Se utilizará Google Cloud Vision API o AWS Textract como servicio de OCR en la nube, con Tesseract como alternativa de código abierto.

**RF-REQ-02 — Detección automática del tipo de documento**  
El sistema identificará automáticamente el tipo de procesamiento requerido según el formato y contenido del archivo cargado, sin que el usuario necesite seleccionarlo manualmente.

**RF-REQ-03 — Extracción y estructuración de información**  
Tras el procesamiento del archivo, el sistema identificará y estructurará los siguientes campos:

| Campo | Descripción |
|---|---|
| Proveedor | Nombre del proveedor o empresa que emite la cotización |
| Tienda / Sucursal | Nombre de la sucursal o punto de venta, si aplica |
| Proyecto asociado | Proyecto al cual se vinculará la requisición (tomado del proyecto activo o seleccionado manualmente) |
| Productos | Lista de artículos cotizados |
| Cantidad | Cantidad solicitada por producto |
| Unidad de medida | Unidad (piezas, metros, litros, etc.) |
| Precio unitario | Precio por unidad de cada producto |
| Precio total | Precio total por renglón (cantidad × precio unitario) |

Si algún campo no puede identificarse automáticamente, se dejará vacío y el sistema notificará al usuario para que lo complete manualmente (ver RF-REQ-06).

**RF-REQ-04 — Indicador de progreso durante el procesamiento**  
Durante el procesamiento del archivo (especialmente en OCR, que puede tomar hasta 30 segundos), el sistema mostrará un indicador de progreso visible, informando al usuario que el proceso está en curso.

**RF-REQ-05 — Formulario editable previo al guardado**  
La información extraída se presentará en un formulario editable antes de guardarse como requisición. El usuario podrá:

- Modificar cualquier campo extraído incorrectamente.
- Agregar productos que no hayan sido detectados.
- Eliminar productos duplicados o irrelevantes.
- Asignar o cambiar el proveedor, tienda y proyecto asociado.
- Confirmar o corregir cantidades y precios.

**RF-REQ-06 — Notificación de campos incompletos**  
Cuando una cotización procesada tenga campos vacíos (proveedor no identificado, proyecto no asignado, productos sin precio, etc.), el sistema mostrará una alerta visible en la parte superior del formulario editable y listará los campos pendientes de completar, indicando el renglón o campo específico que requiere atención.

**RF-REQ-07 — Homologación de productos**  
El sistema contará con un catálogo maestro de productos. Cuando un producto extraído de la cotización no coincida exactamente con un producto del catálogo, el sistema ofrecerá sugerencias de coincidencia por similitud de nombre. El usuario podrá:

- Vincular el producto de la cotización con un producto existente del catálogo (homologación).
- Crear un nuevo producto en el catálogo directamente desde el formulario.
- Dejar el producto sin homologar de forma temporal.

La homologación es fundamental para la generación de reportes y gráficos consolidados, ya que un mismo artículo puede aparecer con nombres distintos según el proveedor.

#### 3.6.2 Gestión de Requisiciones

**RF-REQ-08 — Creación manual de requisiciones**  
Además del flujo automatizado, el usuario podrá crear requisiciones de forma manual ingresando directamente los campos: proyecto asociado, descripción general, lista de productos (nombre, cantidad, unidad, precio estimado, proveedor sugerido) y fecha de necesidad.

**RF-REQ-09 — Flujo de aprobación simplificado**  
Una requisición recién creada tendrá estado **"Borrador"**. El flujo de estados es el siguiente:

```
Borrador → Pendiente de aprobación → Aprobada → Convertida en Orden de Compra
                                   ↘ Rechazada (con comentario obligatorio)
```

El Administrador o el Encargado de Compras podrán aprobar o rechazar una requisición. Al rechazar, el sistema solicitará un comentario indicando el motivo.

**RF-REQ-10 — Visualización de requisiciones**  
Las requisiciones guardadas podrán visualizarse en formato de tabla con todos sus productos, cantidades, precios y estado actual. El usuario podrá filtrar por proyecto, fecha, proveedor y estado.

**RF-REQ-11 — Exportación de requisiciones**  
El sistema permitirá exportar cualquier requisición en:

- **PDF:** Con diseño de documento formal (logotipo, datos del proyecto, tabla de productos, totales).
- **XLSX:** Para procesamiento externo o revisión en Excel.

---

### 3.7 Módulo de Reportes y Analítica

**RF-REP-01 — Reporte de gastos globales**  
El sistema generará un reporte anual de gastos operativos con distribución entre proyectos activos, visualizado mediante gráficas de barras y pastel.

**RF-REP-02 — Reporte consolidado de compras**  
Mostrará el total de compras agrupado por proveedor, vendedor, categoría de producto y proyecto en un período seleccionable.

**RF-REP-03 — Reporte de productos homologados**  
Listará los productos del catálogo maestro indicando cuántos nombres alternativos tiene cada uno, qué proveedores los comercializan y el precio promedio registrado.

**RF-REP-04 — Exportación de reportes**  
Todos los reportes del sistema serán exportables en formato PDF y XLSX.

---

## 4. Flujo de Trabajo del Sistema

Esta sección describe los flujos de trabajo principales del sistema, diseñados para ser lo más intuitivos y eficientes posible para los usuarios.

---

### 4.1 Flujo Principal: De Cotización a Requisición

Este es el flujo central del sistema y debe sentirse fluido y directo para el usuario:

```
1. Usuario selecciona el proyecto activo (selector en barra superior)
       │
       ▼
2. Accede al módulo de Requisiciones → "Nueva Requisición"
       │
       ▼
3. Arrastra o selecciona el archivo de cotización (PDF / JPG / XLSX)
       │
       ▼
4. El sistema detecta automáticamente el tipo de archivo y procesa:
   ├── XLSX        → Lee celdas directamente (PhpSpreadsheet)
   ├── PDF digital → Extrae texto (PDFParser)
   └── PDF/JPG escaneado → Aplica OCR (Cloud Vision / Tesseract)
       │
       ▼
5. Se muestra el formulario pre-llenado con la información extraída:
   - Alerta si hay campos vacíos o sin identificar
   - Sugerencias de homologación para productos no reconocidos
       │
       ▼
6. El usuario revisa, corrige y completa los campos necesarios
       │
       ▼
7. El usuario guarda la requisición (estado: "Pendiente de aprobación")
   → El archivo original queda vinculado automáticamente al repositorio documental del proyecto
       │
       ▼
8. El Administrador / Encargado de Compras aprueba o rechaza
       │
       ▼
9. Requisición aprobada → Se puede exportar (PDF / XLSX) o convertir en Orden de Compra
```

### 4.2 Flujo de Registro de Gastos

```
1. Usuario selecciona el proyecto activo
       │
       ▼
2. Accede al módulo de Gastos → "Nuevo Gasto"
       │
       ▼
3. Completa: concepto, monto, fecha, categoría, comprobante (opcional)
       │
       ▼
4. El gasto queda registrado y el presupuesto del proyecto se actualiza en tiempo real
       │
       ▼
5. Si el gasto acumulado supera el 70% / 90% / 100% del presupuesto:
   → El sistema envía notificación interna (y correo opcional)
```

### 4.3 Flujo de Gestión de Proveedores

```
1. Acceder al módulo de Proveedores
       │
       ▼
2. Registrar o buscar proveedor existente
       │
       ├── Agregar / editar vendedores del proveedor
       ├── Consultar historial de compras
       └── Ver requisiciones y órdenes de compra asociadas
```

---

## 5. Requerimientos No Funcionales

Los requerimientos no funcionales se identifican con el formato **RNF-[Categoría]-[Número]**.

---

### 5.1 Rendimiento

**RNF-REND-01** — El sistema deberá responder a las solicitudes HTTP en menos de 2 segundos para el 95% de las operaciones bajo carga normal (hasta 20 usuarios concurrentes).

**RNF-REND-02** — El procesamiento de un archivo XLSX o PDF con texto digital no deberá exceder los 10 segundos. El procesamiento mediante OCR podrá tomar hasta 30 segundos; durante este tiempo el sistema mostrará un indicador de progreso visible al usuario.

**RNF-REND-03** — La interfaz de usuario deberá cargar por primera vez en menos de 3 segundos en una conexión de 10 Mbps.

---

### 5.2 Seguridad

**RNF-SEG-01** — Toda la comunicación entre el cliente y el servidor deberá realizarse sobre HTTPS (TLS 1.2 o superior).

**RNF-SEG-02** — Las contraseñas de los usuarios deberán almacenarse utilizando el algoritmo bcrypt con un factor de costo mínimo de 12.

**RNF-SEG-03** — El sistema deberá implementar protección contra las vulnerabilidades OWASP Top 10, incluyendo inyección SQL, XSS y CSRF.

**RNF-SEG-04** — Las sesiones de usuario expirarán automáticamente tras 8 horas de inactividad.

**RNF-SEG-05** — Los archivos cargados al sistema deberán validarse en tipo MIME y tamaño antes de ser procesados. El tamaño máximo por archivo será de 20 MB.

**RNF-SEG-06** — El sistema deberá llevar un registro de auditoría (log) de las acciones críticas: inicio de sesión, aprobación/rechazo de requisiciones, modificación de presupuestos y eliminación de registros.

---

### 5.3 Usabilidad

**RNF-USA-01** — La interfaz deberá ser responsiva y funcionar correctamente en resoluciones desde 375px (móvil) hasta 1920px (escritorio).

**RNF-USA-02** — El flujo de carga y procesamiento de cotizaciones deberá completarse en no más de 3 pasos visibles para el usuario: (1) seleccionar archivo, (2) revisar y corregir datos extraídos, (3) guardar requisición.

**RNF-USA-03** — El sistema deberá guiar al usuario mediante mensajes de error descriptivos y sugerencias de corrección, evitando mensajes genéricos como "Error 500".

**RNF-USA-04** — Las acciones destructivas (eliminar proyectos, rechazar requisiciones) deberán requerir confirmación explícita del usuario antes de ejecutarse.

**RNF-USA-05** — El sistema deberá mostrar estados de carga (skeleton loaders o spinners) para todas las operaciones que tarden más de 500 ms.

**RNF-USA-06** — La interfaz deberá seguir principios de accesibilidad WCAG 2.1 nivel AA, incluyendo contraste de colores de al menos 4.5:1 para texto normal.

**RNF-USA-07** — El selector de proyecto activo deberá estar siempre visible en la barra de navegación principal, permitiendo al usuario cambiar de proyecto sin perder el contexto de la pantalla actual.

---

### 5.4 Confiabilidad y Disponibilidad

**RNF-CONF-01** — El sistema deberá tener una disponibilidad mínima del 99% mensual, excluyendo ventanas de mantenimiento programado.

**RNF-CONF-02** — Se realizarán respaldos automáticos diarios de la base de datos. Los respaldos se conservarán durante 30 días.

**RNF-CONF-03** — En caso de error inesperado en el servidor, el sistema deberá registrar el error en un log centralizado y mostrar al usuario una página de error amigable, sin exponer información técnica sensible.

---

### 5.5 Mantenibilidad y Escalabilidad

**RNF-MANT-01** — El código fuente deberá seguir los estándares de codificación del framework seleccionado (PSR-12 para PHP/Laravel) y estar documentado con comentarios en funciones públicas y rutas de la API.

**RNF-MANT-02** — La arquitectura del sistema deberá permitir agregar nuevos módulos sin modificar los módulos existentes (principio Open/Closed).

**RNF-MANT-03** — El sistema deberá contar con un entorno de pruebas (staging) separado del entorno de producción.

**RNF-MANT-04** — La base de datos deberá diseñarse con migraciones versionadas para facilitar actualizaciones sin pérdida de datos.

---

### 5.6 Portabilidad

**RNF-PORT-01** — El sistema deberá funcionar correctamente en los navegadores Chrome 110+, Firefox 110+, Edge 110+ y Safari 16+.

**RNF-PORT-02** — El entorno de despliegue deberá ser reproducible mediante contenedores Docker, permitiendo su ejecución en cualquier proveedor de nube o servidor dedicado.

---

## 6. Stack Tecnológico Propuesto

Se propone el siguiente stack tecnológico orientado a la simplicidad de desarrollo, interfaz moderna y opciones de despliegue económicas.

---

### 6.1 Backend

| Componente | Tecnología | Justificación |
|---|---|---|
| Framework principal | **Laravel 11** (PHP 8.3) | Ecosistema maduro, convenciones claras, ORM Eloquent, sistema de autenticación integrado (Laravel Sanctum), soporte oficial de largo plazo. |
| Autenticación | **Laravel Sanctum** | Tokens de sesión para aplicaciones web, sin necesidad de JWT complejo en v1. |
| Procesamiento de colas | **Laravel Queue + Redis** | Para el procesamiento asíncrono de OCR y extracción de cotizaciones sin bloquear la interfaz del usuario. |
| Extracción PDF con texto | **smalot/pdfparser** | Librería PHP para extraer texto de PDFs digitales sin dependencias externas. |
| OCR de imágenes/PDFs escaneados | **Google Cloud Vision API** (o **AWS Textract** como alternativa) | Alta precisión en OCR con soporte para español. Costo por uso, sin inversión inicial. Alternativa open source: Tesseract a través de `thiagoalessio/tesseract_ocr`. |
| Procesamiento XLSX | **PhpSpreadsheet** | Librería oficial de PHP para leer y escribir archivos Excel, sin dependencia de Office. |
| Exportación a PDF | **DomPDF** (Barryvdh) | Genera PDFs a partir de vistas Blade, con soporte de estilos CSS básicos. |

---

### 6.2 Frontend

| Componente | Tecnología | Justificación |
|---|---|---|
| Framework UI | **Livewire 3** (con Alpine.js) | Permite construir interfaces reactivas y dinámicas directamente con PHP/Blade, sin separar completamente el frontend. Ideal para equipos pequeños que ya usan Laravel. |
| Estilos | **Tailwind CSS v4** | Framework utilitario con excelente soporte de diseño moderno, responsivo y temas dark/light. |
| Componentes UI | **Flux UI** (componentes oficiales de Livewire) o **Mary UI** | Conjunto de componentes preconstruidos (tablas, modales, formularios, dashboards) que aceleran el desarrollo. |
| Gráficas | **Chart.js** (vía Alpine.js) | Librería ligera para gráficas de barras, pastel y líneas. |
| Íconos | **Lucide Icons** | Set de íconos SVG consistente y moderno, disponible como paquete npm o CDN. |

> **Alternativa a Livewire:** Si el equipo prefiere una SPA más interactiva, se puede optar por **Inertia.js + Vue 3** manteniendo Laravel como backend.

---

### 6.3 Base de Datos

| Componente | Tecnología | Justificación |
|---|---|---|
| Motor principal | **MySQL 8** o **PostgreSQL 15** | MySQL es ampliamente soportado en todos los proveedores de hosting económicos. PostgreSQL es recomendado para consultas analíticas complejas. |
| Cache y colas | **Redis** | Cache de sesiones, resultados de reportes frecuentes y gestión de colas de procesamiento. |

---

### 6.4 Infraestructura y Despliegue

| Opción | Proveedor | Costo Estimado | Adecuado Para |
|---|---|---|---|
| **Opción A (Recomendada)** | **Railway.app** | ~$5–15 USD/mes | Despliegue con Docker, base de datos incluida, sin configuración de servidor. Ideal para v1. |
| **Opción B** | **Render.com** | ~$7–20 USD/mes | Similar a Railway, con plan gratuito limitado. Buen soporte para Laravel. |
| **Opción C** | **DigitalOcean Droplet** | ~$12–24 USD/mes | VPS con mayor control, escalable. Requiere configuración de servidor (Nginx, PHP-FPM). |
| **Opción D** | **AWS EC2 t3.micro + RDS** | ~$15–30 USD/mes | Mayor control y escalabilidad. Recomendado si se planea escalar significativamente. |

**Recomendación para v1:** Railway.app o Render.com ofrecen la menor fricción de despliegue para un equipo pequeño, con integración directa desde repositorios Git.

---

### 6.5 Herramientas de Desarrollo

| Herramienta | Uso |
|---|---|
| Laravel Sail | Entorno de desarrollo local con Docker |
| Laravel Telescope | Debugging y monitoreo en desarrollo |
| Pest PHP | Framework de pruebas unitarias y de integración |
| GitHub Actions | CI/CD: ejecución automática de pruebas y despliegue |

---

## 7. Arquitectura General del Sistema

### 7.1 Descripción de la Arquitectura

El sistema seguirá una arquitectura **Monolítica MVC** en su primera versión, utilizando el patrón **Model-View-Controller** de Laravel con la capa de presentación gestionada por **Livewire**. Esta decisión prioriza la velocidad de desarrollo, la simplicidad de mantenimiento y la reducción de complejidad operativa para un equipo pequeño.

```
┌───────────────────────────────────────────────────────────────────┐
│                        NAVEGADOR / CLIENTE                        │
│         (Chrome, Firefox, Edge, Safari — Desktop y Móvil)         │
└─────────────────────────────┬─────────────────────────────────────┘
                              │ HTTPS
┌─────────────────────────────▼─────────────────────────────────────┐
│                      SERVIDOR WEB (Nginx)                         │
└─────────────────────────────┬─────────────────────────────────────┘
                              │
┌─────────────────────────────▼─────────────────────────────────────┐
│                     APLICACIÓN LARAVEL 11                         │
│  ┌─────────────────────────────────────────────────────────────┐  │
│  │                    CAPA DE RUTAS                            │  │
│  │        web.php (Livewire)  ·  api.php (Sanctum)            │  │
│  └────────────────────────┬────────────────────────────────────┘  │
│                           │                                       │
│  ┌────────────────────────▼────────────────────────────────────┐  │
│  │                  CAPA DE CONTROLADORES                      │  │
│  │   Proyectos · Gastos · Documentos · Proveedores · Requisic. │  │
│  └────────────────────────┬────────────────────────────────────┘  │
│                           │                                       │
│  ┌────────────────────────▼────────────────────────────────────┐  │
│  │                    CAPA DE SERVICIOS                        │  │
│  │  DocumentParser · OCRService · HomologationService          │  │
│  │  BudgetAlertService · ReportService · ExportService         │  │
│  └────────────────────────┬────────────────────────────────────┘  │
│                           │                                       │
│  ┌────────────────────────▼────────────────────────────────────┐  │
│  │                   CAPA DE MODELOS (ORM)                     │  │
│  │  Project · Expense · Document · Supplier · Requisition      │  │
│  │  Product · ProductAlias · PurchaseOrder · User · Role       │  │
│  └────────────────────────┬────────────────────────────────────┘  │
│                           │                                       │
│  ┌────────────────────────▼────────────────────────────────────┐  │
│  │                   SISTEMA DE COLAS                          │  │
│  │        Jobs: ProcessOCR · ParseQuotation · SendAlert        │  │
│  └────────────────────────┬────────────────────────────────────┘  │
└───────────────────────────┼────────────────────────────────────────┘
                            │
          ┌─────────────────┼─────────────────┐
          │                 │                 │
┌─────────▼──────┐ ┌────────▼───────┐ ┌──────▼──────────┐
│   MySQL / PG   │ │     Redis      │ │  Almacenamiento  │
│  (Base datos   │ │  (Cache/Colas) │ │  de archivos     │
│   principal)   │ │                │ │  (S3/local)      │
└────────────────┘ └────────────────┘ └─────────────────┘
```

### 7.2 Flujo de Procesamiento de Cotizaciones

El siguiente diagrama describe el flujo completo desde la carga del archivo hasta la generación de la requisición:

```
USUARIO carga archivo (PDF / JPG / XLSX)
        │
        ▼
┌───────────────────┐
│  Validación de    │
│  tipo MIME y      │──── Error ────► Notificación al usuario
│  tamaño (≤20 MB)  │
└─────────┬─────────┘
          │ Válido
          ▼
┌────────────────────────────────────────────┐
│         Detección automática del tipo      │
├──────────────┬────────────────┬────────────┤
│    XLSX      │   PDF digital  │ PDF / JPG  │
│              │  (con texto)   │ escaneado  │
└──────┬───────┴───────┬────────┴─────┬──────┘
       │               │              │
       ▼               ▼              ▼
PhpSpreadsheet    PDFParser      OCR Service
(lectura de       (extracción   (Cloud Vision /
 celdas)          de texto)      Tesseract)
       │               │              │
       └───────────────┴──────────────┘
                       │
                       ▼
            ┌──────────────────────┐
            │  DocumentParser      │
            │  (identifica campos: │
            │  proveedor, tienda,  │
            │  proyecto, productos,│
            │  cantidades, precios)│
            └──────────┬───────────┘
                       │
                       ▼
            ┌──────────────────────┐
            │  Homologación        │◄──── Catálogo maestro
            │  de productos        │      de productos
            │  (sugerencias por    │
            │   similitud)         │
            └──────────┬───────────┘
                       │
                       ▼
            ┌──────────────────────┐
            │  Formulario editable │
            │  con campos pre-     │
            │  llenados y alertas  │
            │  de campos vacíos    │
            └──────────┬───────────┘
                       │
                       ▼
            ┌──────────────────────┐
            │  Requisición         │──► Archivo vinculado
            │  guardada en BD      │    automáticamente en
            │  (exportable en      │    repositorio documental
            │   PDF y XLSX)        │    del proyecto
            └──────────────────────┘
```

### 7.3 Esquema Simplificado de Base de Datos

Las entidades principales del sistema y sus relaciones son las siguientes:

```
users ──────────────────────────────────────────────────────────────────
  id, name, email, password, role_id, active

roles ──────────────────────────────────────────────────────────────────
  id, name, permissions (JSON)

projects ───────────────────────────────────────────────────────────────
  id, name, description, client, budget, start_date, end_date, status

expenses ───────────────────────────────────────────────────────────────
  id, concept, amount, date, category, project_id, user_id, receipt_file

documents ──────────────────────────────────────────────────────────────
  id, project_id, name, category, file_path, version, uploaded_by,
  requisition_id (nullable — vinculación directa con requisición origen),
  created_at

suppliers ──────────────────────────────────────────────────────────────
  id, trade_name, legal_name, rfc, category, contact_info (JSON)

vendors ────────────────────────────────────────────────────────────────
  id, supplier_id, name, phone, email

products (catálogo maestro) ────────────────────────────────────────────
  id, canonical_name, unit, description, category

product_aliases ────────────────────────────────────────────────────────
  id, product_id, alias_name, supplier_id

quotations ─────────────────────────────────────────────────────────────
  id, requisition_id, supplier_id, file_path, file_type, processed_at

requisitions ───────────────────────────────────────────────────────────
  id, project_id, description, status, created_by, approved_by, date

requisition_items ──────────────────────────────────────────────────────
  id, requisition_id, product_id, quantity, unit, unit_price, supplier_id

purchase_orders ────────────────────────────────────────────────────────
  id, requisition_id, supplier_id, project_id, total, status, date
```

> **Nota sobre la integración documental:** El campo `requisition_id` en la tabla `documents` permite vincular directamente un archivo (cotización) con la requisición que generó, sin duplicar registros. Cuando el archivo es cargado desde el módulo de requisiciones, este campo se llena automáticamente. Los documentos cargados manualmente desde el módulo documental (contratos, planos, etc.) tienen este campo en `null`.

---

## 8. Restricciones del Proyecto

- **Alcance de v1:** El sistema no incluirá facturación electrónica (CFDI), integración con SAT, módulo contable completo ni aplicación móvil nativa.
- **Idioma:** La interfaz del sistema será en español mexicano.
- **Presupuesto de despliegue:** Se prioriza el uso de infraestructura con costo inferior a 30 USD/mes en la etapa inicial.
- **Equipo de desarrollo:** El sistema está diseñado para ser construido y mantenido por un equipo de 1 a 3 desarrolladores.
- **Contexto de residencia profesional:** El alcance del sistema está ajustado a los tiempos y recursos disponibles en este contexto académico-profesional. Se prioriza que los módulos implementados sean completamente funcionales, estén bien integrados y ofrezcan una experiencia de usuario clara, por encima de incorporar más módulos con implementación parcial.
- **Escalabilidad diferida:** La transición a microservicios o una SPA completa (React/Vue desacoplado) queda fuera del alcance de v1 y se considerará en versiones posteriores.

---

## 9. Criterios de Aceptación

El sistema será considerado aceptable para su entrega en v1 cuando:

1. Los módulos implementados sean completamente funcionales y permitan operaciones CRUD completas en cada uno.
2. El procesamiento de cotizaciones en los tres formatos (XLSX, PDF digital, PDF/JPG escaneado) funcione correctamente para al menos el 90% de los documentos de prueba proporcionados por el cliente.
3. El flujo de carga de cotización a requisición editable se complete en no más de 3 pasos visibles para el usuario.
4. La homologación de productos permita vincular al menos dos nombres alternativos a un producto del catálogo maestro.
5. Las cotizaciones procesadas queden vinculadas automáticamente al repositorio documental del proyecto correspondiente, sin necesidad de carga manual adicional.
6. El sistema de alertas de presupuesto envíe notificaciones correctamente al alcanzar los umbrales configurados.
7. Los roles de usuario (Administrador, Encargado de Compras, Supervisor / Operativo) restrinjan el acceso según los permisos definidos en la sección 2.3.
8. Los reportes de compras por proveedor y vendedor sean correctamente exportados en PDF y XLSX.
9. El sistema opere correctamente en Chrome, Firefox y Edge en versiones actuales.
10. El tiempo de respuesta de las operaciones principales no exceda los 2 segundos bajo condiciones normales de uso.

---

## 10. Apéndice — Glosario Extendido

| Término | Definición |
|---|---|
| Requisición | Documento interno que lista los materiales o servicios requeridos para un proyecto, generado automáticamente a partir de una cotización procesada o creado manualmente, previo a la generación de una orden de compra formal. |
| Homologación | Proceso de vincular múltiples nombres comerciales de un mismo producto bajo una denominación canónica en el catálogo maestro, facilitando la comparación de precios y la generación de reportes consolidados. |
| Cotización | Documento enviado por un proveedor con los precios y condiciones para el suministro de productos o servicios solicitados. Puede estar en formato PDF (digital o escaneado), JPG o XLSX. |
| Catálogo maestro | Base de datos centralizada de productos reconocidos por el sistema, a la cual se vinculan los nombres alternativos provenientes de distintos proveedores. |
| OCR | Tecnología que permite extraer texto legible de imágenes digitales o documentos escaneados, convirtiendo contenido visual en texto procesable. |
| Pipeline de procesamiento | Secuencia automatizada de pasos que transforma un archivo de cotización crudo en una requisición estructurada y editable: validación → detección de tipo → extracción → estructuración → homologación → formulario editable → guardado. |
| Proyecto activo | Proyecto seleccionado globalmente por el usuario en la barra de navegación, al cual se asocian automáticamente todas las operaciones realizadas durante la sesión. |
| Período cerrado | Mes o período contable marcado como definitivo, en el que no se permiten modificaciones retroactivas de registros de gastos. |
| Staging | Entorno de pruebas que replica la configuración de producción, utilizado para validar cambios antes de su publicación al sistema en uso. |
| Vinculación documental | Relación directa entre un archivo de cotización almacenado en el repositorio documental y la requisición que fue generada a partir de él, establecida automáticamente por el sistema al momento del guardado. |
