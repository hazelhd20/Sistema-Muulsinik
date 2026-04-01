# Especificación de Requerimientos de Software (ERS)
## Sistema de Gestión para Constructora Muulsinik

**Versión:** 1.0  
**Fecha:** 27 de marzo de 2026  
**Estado:** Borrador inicial  
**Elaborado por:** Equipo de Desarrollo  

---

## 1. Introducción

### 1.1 Propósito

El presente documento tiene como objetivo definir los requerimientos funcionales y no funcionales del Sistema de Gestión para la Constructora Muulsinik. Sirve como contrato técnico entre el cliente y el equipo de desarrollo, estableciendo el alcance, las restricciones y las características esperadas del sistema en su primera etapa de implementación.

### 1.2 Alcance del Sistema

El sistema, denominado internamente **Muulsinik ERP v1**, es una aplicación web orientada a la gestión operativa de proyectos de construcción. Cubre cinco módulos principales: gestión de proyectos, control de gastos y presupuestos, gestión documental, administración de proveedores y compras, y módulo de requisiciones. El sistema está diseñado bajo un enfoque progresivo y práctico, priorizando funcionalidades esenciales con capacidad de escalar en versiones futuras.

### 1.3 Definiciones y Acrónimos

| Término | Definición |
|---|---|
| ERS | Especificación de Requerimientos de Software |
| OCR | Optical Character Recognition (Reconocimiento Óptico de Caracteres) |
| RF | Requerimiento Funcional |
| RNF | Requerimiento No Funcional |
| CRUD | Create, Read, Update, Delete (operaciones básicas de datos) |
| Homologación | Proceso de vincular productos de distintos proveedores bajo una denominación común |
| Requisición | Documento interno que consolida productos solicitados para un proyecto |
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

Muulsinik ERP v1 es un sistema web de nueva creación que no forma parte de un sistema mayor existente. Su objetivo principal es centralizar la información operativa de la constructora, eliminando el uso disperso de hojas de cálculo y documentos físicos. En etapas futuras, podrá integrarse con sistemas contables o plataformas de facturación electrónica.

### 2.2 Funciones Principales del Sistema

A nivel general, el sistema permitirá:

- Gestionar proyectos de construcción con seguimiento de presupuesto y avance.
- Registrar y controlar gastos operativos, distribuyéndolos entre proyectos activos.
- Almacenar y organizar documentos relevantes (contratos, planos, permisos).
- Administrar proveedores, contactos y órdenes de compra.
- Procesar cotizaciones en formato PDF, JPG o XLSX mediante extracción de texto y OCR.
- Generar requisiciones internas de materiales con soporte de edición y exportación.
- Homologar productos de distintos proveedores bajo una denominación común.
- Emitir alertas cuando el gasto alcance un porcentaje determinado del presupuesto.
- Controlar el acceso mediante roles de usuario con distintos niveles de permisos.

### 2.3 Clases de Usuarios

| Rol | Descripción | Nivel de Acceso |
|---|---|---|
| Administrador | Gestiona usuarios, roles y configuración global del sistema | Total |
| Gerente de Proyecto | Supervisa proyectos, aprueba requisiciones y revisa presupuestos | Alto |
| Comprador / Encargado de Compras | Crea y gestiona requisiciones, proveedores y cotizaciones | Medio-alto |
| Supervisor de Campo | Registra gastos y solicita materiales desde obra | Medio |
| Contador / Finanzas | Consulta reportes financieros y controla presupuestos | Medio (solo lectura en módulos operativos) |

### 2.4 Entorno Operativo

El sistema operará como aplicación web accesible desde navegadores modernos (Chrome, Firefox, Edge, Safari). Será responsivo para su uso en dispositivos móviles y tabletas, dado que los supervisores de campo pueden requerir acceso desde obra. El despliegue se realizará en la nube, con opciones de bajo costo descritas en la sección de stack tecnológico.

### 2.5 Restricciones de Diseño e Implementación

- Primera etapa: sistema monolítico con arquitectura MVC, sin microservicios.
- Desarrollo progresivo: se implementarán primero los módulos de mayor impacto operativo.
- Sin integraciones externas en v1 (no se conecta a SAT, ERP contable ni bancos).
- El sistema debe ser mantenible por un equipo pequeño (1–3 desarrolladores).

### 2.6 Suposiciones y Dependencias

- La constructora cuenta con acceso a internet estable en sus oficinas principales.
- Los documentos de cotización son proporcionados en los formatos acordados (PDF, JPG, XLSX).
- El cliente designará a un usuario administrador responsable de la configuración inicial.

---

## 3. Requerimientos Funcionales

Los requerimientos funcionales se identifican con el formato **RF-[Módulo]-[Número]**.

---

### 3.1 Módulo: Autenticación y Gestión de Usuarios

**RF-AUTH-01 — Inicio de sesión seguro**  
El sistema deberá autenticar a los usuarios mediante correo electrónico y contraseña. Las contraseñas deberán almacenarse con hash bcrypt. Se implementará protección contra ataques de fuerza bruta mediante bloqueo temporal tras cinco intentos fallidos.

**RF-AUTH-02 — Gestión de roles y permisos**  
El administrador podrá crear, editar y desactivar usuarios. Cada usuario tendrá asignado un rol que determina las vistas y acciones disponibles. Los roles predefinidos son: Administrador, Gerente de Proyecto, Comprador, Supervisor de Campo y Contador.

**RF-AUTH-03 — Selección de proyecto activo**  
Una vez autenticado, el usuario podrá seleccionar el proyecto activo desde un selector global visible en la interfaz. Las operaciones de registro de gastos, requisiciones y compras quedarán asociadas al proyecto activo seleccionado.

**RF-AUTH-04 — Restablecimiento de contraseña**  
El sistema enviará un enlace de restablecimiento de contraseña al correo del usuario mediante un token de un solo uso con vigencia de 60 minutos.

---

### 3.2 Módulo de Gestión de Proyectos

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

**RF-DOC-01 — Repositorio de documentos por proyecto**  
Cada proyecto contará con un repositorio de archivos donde podrán cargarse documentos en formatos PDF, DOCX, XLSX, JPG y PNG. Los documentos se organizarán por categorías: contratos, planos, permisos, cotizaciones y otros.

**RF-DOC-02 — Visualización de documentos**  
Los documentos PDF e imágenes podrán visualizarse directamente en el navegador sin necesidad de descarga. Los demás formatos ofrecerán opción de descarga.

**RF-DOC-03 — Control de versiones básico**  
Al cargar una nueva versión de un documento existente, el sistema conservará la versión anterior y registrará la fecha y el usuario que realizó la actualización.

**RF-DOC-04 — Búsqueda de documentos**  
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

**RF-REQ-01 — Creación de requisiciones**  
El usuario podrá crear una requisición de materiales indicando: proyecto asociado, descripción general, lista de productos (nombre, cantidad, unidad de medida, precio estimado, proveedor sugerido) y fecha de necesidad.

**RF-REQ-02 — Flujo de aprobación**  
Una requisición recién creada tendrá estado "Pendiente". El Gerente de Proyecto o Administrador podrá aprobarla o rechazarla. Una vez aprobada, podrá convertirse en orden de compra.

**RF-REQ-03 — Carga de cotizaciones**  
El sistema permitirá adjuntar cotizaciones de proveedores en los siguientes formatos:

- **XLSX:** Los datos se extraerán directamente de las celdas de la hoja de cálculo mediante mapeo configurable de columnas.
- **PDF con texto digital:** El sistema extraerá el texto directamente del documento y aplicará un parser para identificar productos, cantidades y precios.
- **PDF escaneado / JPG:** El sistema aplicará OCR (basado en Tesseract.js en el cliente o una API de OCR en la nube) para extraer el texto del documento y luego procesarlo con el mismo parser.

**RF-REQ-04 — Procesamiento y estructuración de cotizaciones**  
Tras la extracción del texto, el sistema identificará y estructurará la siguiente información: nombre del proveedor, nombre de la tienda/sucursal, proyecto asociado, lista de productos (nombre, cantidad, precio unitario, precio total). Si algún campo no puede identificarse, se dejará vacío y se notificará al usuario para que lo complete manualmente.

**RF-REQ-05 — Homologación de productos**  
El sistema contará con un catálogo maestro de productos. Cuando un producto de una cotización no coincida exactamente con uno del catálogo, el sistema ofrecerá sugerencias de coincidencia por similitud de nombre. El usuario podrá vincular el producto de la cotización con el producto del catálogo (homologación), de modo que ambos nombres apunten al mismo artículo en reportes y gráficos consolidados.

**RF-REQ-06 — Edición manual de requisiciones procesadas**  
La información extraída de las cotizaciones se presentará en un formulario editable antes de guardarse. El usuario podrá modificar cualquier campo, agregar o eliminar productos y corregir datos incorrectos.

**RF-REQ-07 — Visualización, edición y exportación de requisiciones**  
Las requisiciones guardadas podrán visualizarse en formato de tabla con todos sus productos y detalles. El sistema permitirá exportar la requisición en formato PDF (con diseño de documento formal) y en XLSX (para procesamiento externo).

**RF-REQ-08 — Notificación de campos incompletos**  
Cuando una cotización procesada tenga campos vacíos (proveedor no identificado, proyecto no asignado, etc.), el sistema mostrará una alerta visible en la interfaz y listará los campos pendientes de completar.

---

### 3.7 Módulo de Reportes y Analítica

**RF-REP-01 — Reporte de gastos globales**  
El sistema generará un reporte anual de gastos operativos con distribución entre proyectos activos, visualizado mediante gráficas de barras y pastel.

**RF-REP-02 — Reporte consolidado de compras**  
Mostrará el total de compras agrupado por proveedor, vendedor, categoría de producto y proyecto en un período seleccionable.

**RF-REP-03 — Reporte de productos homologados**  
Listará los productos del catálogo maestro indicando cuántos nombres alternativos tiene cada uno y qué proveedores los comercializan, con el precio promedio registrado.

**RF-REP-04 — Exportación de reportes**  
Todos los reportes del sistema serán exportables en formato PDF y XLSX.

---

## 4. Requerimientos No Funcionales

Los requerimientos no funcionales se identifican con el formato **RNF-[Categoría]-[Número]**.

---

### 4.1 Rendimiento

**RNF-REND-01** — El sistema deberá responder a las solicitudes HTTP en menos de 2 segundos para el 95% de las operaciones bajo carga normal (hasta 20 usuarios concurrentes).

**RNF-REND-02** — El procesamiento de un archivo XLSX o PDF con texto digital no deberá exceder los 10 segundos. El procesamiento mediante OCR podrá tomar hasta 30 segundos; durante este tiempo el sistema mostrará un indicador de progreso.

**RNF-REND-03** — La interfaz de usuario deberá cargar por primera vez en menos de 3 segundos en una conexión de 10 Mbps.

---

### 4.2 Seguridad

**RNF-SEG-01** — Toda la comunicación entre el cliente y el servidor deberá realizarse sobre HTTPS (TLS 1.2 o superior).

**RNF-SEG-02** — Las contraseñas de los usuarios deberán almacenarse utilizando el algoritmo bcrypt con un factor de costo mínimo de 12.

**RNF-SEG-03** — El sistema deberá implementar protección contra las vulnerabilidades OWASP Top 10, incluyendo inyección SQL, XSS y CSRF.

**RNF-SEG-04** — Las sesiones de usuario expirarán automáticamente tras 8 horas de inactividad.

**RNF-SEG-05** — Los archivos cargados al sistema deberán validarse en tipo MIME y tamaño antes de ser procesados. El tamaño máximo por archivo será de 20 MB.

**RNF-SEG-06** — El sistema deberá llevar un registro de auditoría (log) de las acciones críticas: inicio de sesión, aprobación/rechazo de requisiciones, modificación de presupuestos y eliminación de registros.

---

### 4.3 Usabilidad

**RNF-USA-01** — La interfaz deberá ser responsiva y funcionar correctamente en resoluciones desde 375px (móvil) hasta 1920px (escritorio).

**RNF-USA-02** — El sistema deberá guiar al usuario mediante mensajes de error descriptivos y sugerencias de corrección, evitando mensajes genéricos como "Error 500".

**RNF-USA-03** — Las acciones destructivas (eliminar proyectos, rechazar requisiciones) deberán requerir confirmación explícita del usuario antes de ejecutarse.

**RNF-USA-04** — El sistema deberá mostrar estados de carga (skeleton loaders o spinners) para todas las operaciones que tarden más de 500 ms.

**RNF-USA-05** — La interfaz deberá seguir principios de accesibilidad WCAG 2.1 nivel AA, incluyendo contraste de colores de al menos 4.5:1 para texto normal.

---

### 4.4 Confiabilidad y Disponibilidad

**RNF-CONF-01** — El sistema deberá tener una disponibilidad mínima del 99% mensual, excluyendo ventanas de mantenimiento programado.

**RNF-CONF-02** — Se realizarán respaldos automáticos diarios de la base de datos. Los respaldos se conservarán durante 30 días.

**RNF-CONF-03** — En caso de error inesperado en el servidor, el sistema deberá registrar el error en un log centralizado y mostrar al usuario una página de error amigable, sin exponer información técnica sensible.

---

### 4.5 Mantenibilidad y Escalabilidad

**RNF-MANT-01** — El código fuente deberá seguir los estándares de codificación del framework seleccionado (PSR-12 para PHP/Laravel) y estar documentado con comentarios en funciones públicas y rutas de la API.

**RNF-MANT-02** — La arquitectura del sistema deberá permitir agregar nuevos módulos sin modificar los módulos existentes (principio Open/Closed).

**RNF-MANT-03** — El sistema deberá contar con un entorno de pruebas (staging) separado del entorno de producción.

**RNF-MANT-04** — La base de datos deberá diseñarse con migraciones versionadas para facilitar actualizaciones sin pérdida de datos.

---

### 4.6 Portabilidad

**RNF-PORT-01** — El sistema deberá funcionar correctamente en los navegadores Chrome 110+, Firefox 110+, Edge 110+ y Safari 16+.

**RNF-PORT-02** — El entorno de despliegue deberá ser reproducible mediante contenedores Docker, permitiendo su ejecución en cualquier proveedor de nube o servidor dedicado.

---

## 5. Stack Tecnológico Propuesto

Se propone el siguiente stack tecnológico orientado a la simplicidad de desarrollo, interfaz moderna y opciones de despliegue económicas.

---

### 5.1 Backend

| Componente | Tecnología | Justificación |
|---|---|---|
| Framework principal | **Laravel 11** (PHP 8.3) | Ecosistema maduro, convenciones claras, ORM Eloquent, sistema de autenticación integrado (Laravel Sanctum), soporte oficial de largo plazo. Cumple con el requerimiento del cliente. |
| Autenticación | **Laravel Sanctum** | Tokens de sesión para aplicaciones web, sin necesidad de JWT complejo en v1. |
| Procesamiento de colas | **Laravel Queue + Redis** | Para el procesamiento asíncrono de OCR y extracción de cotizaciones sin bloquear la interfaz. |
| Extracción PDF con texto | **smalot/pdfparser** | Librería PHP para extraer texto de PDFs digitales sin dependencias externas. |
| OCR de imágenes/PDFs escaneados | **Google Cloud Vision API** (o **AWS Textract** como alternativa) | Alta precisión en OCR con soporte para español. Costo por uso, sin inversión inicial. Alternativa open source: Tesseract a través de `thiagoalessio/tesseract_ocr` si se prefiere evitar costos de API. |
| Procesamiento XLSX | **PhpSpreadsheet** | Librería oficial de PHP para leer y escribir archivos Excel, sin dependencia de Office. |
| Exportación a PDF | **DomPDF** (Barryvdh) | Genera PDFs a partir de vistas Blade, con soporte de estilos CSS básicos. |

---

### 5.2 Frontend

| Componente | Tecnología | Justificación |
|---|---|---|
| Framework UI | **Livewire 3** (con Alpine.js) | Permite construir interfaces reactivas y dinámicas directamente con PHP/Blade, sin separar completamente el frontend. Ideal para equipos pequeños que ya usan Laravel. Reduce la complejidad de mantener una SPA separada. |
| Estilos | **Tailwind CSS v4** | Framework utilitario con excelente soporte de diseño moderno, responsivo y temas dark/light. Se integra nativamente con Laravel y Livewire. |
| Componentes UI | **Flux UI** (componentes oficiales de Livewire) o **Mary UI** | Conjunto de componentes preconstruidos (tablas, modales, formularios, dashboards) que aceleran el desarrollo sin sacrificar personalización. |
| Gráficas | **Chart.js** (vía Alpine.js) | Librería ligera para gráficas de barras, pastel y líneas. Sin dependencias pesadas. |
| Íconos | **Lucide Icons** | Set de íconos SVG consistente y moderno, disponible como paquete npm o CDN. |

> **Alternativa a Livewire:** Si el equipo prefiere una SPA más interactiva, se puede optar por **Inertia.js + Vue 3** manteniendo Laravel como backend. Esta opción ofrece mayor fluidez en la navegación pero requiere gestionar el frontend por separado.

---

### 5.3 Base de Datos

| Componente | Tecnología | Justificación |
|---|---|---|
| Motor principal | **MySQL 8** o **PostgreSQL 15** | MySQL es ampliamente soportado en todos los proveedores de hosting económicos. PostgreSQL es recomendado si se planean consultas analíticas complejas o uso de tipos JSON avanzados. |
| Cache y colas | **Redis** | Cache de sesiones, resultados de reportes frecuentes y gestión de colas de procesamiento. |

---

### 5.4 Infraestructura y Despliegue

Se priorizan opciones económicas y de fácil gestión:

| Opción | Proveedor | Costo Estimado | Adecuado Para |
|---|---|---|---|
| **Opción A (Recomendada)** | **Railway.app** | ~$5–15 USD/mes | Despliegue con Docker, base de datos incluida, sin configuración de servidor. Ideal para v1. |
| **Opción B** | **Render.com** | ~$7–20 USD/mes | Similar a Railway, con plan gratuito limitado. Buen soporte para Laravel. |
| **Opción C** | **DigitalOcean Droplet** | ~$12–24 USD/mes | VPS con mayor control, escalable. Requiere configuración de servidor (Nginx, PHP-FPM). |
| **Opción D** | **AWS EC2 t3.micro + RDS** | ~$15–30 USD/mes | Mayor control y escalabilidad. Más configuración inicial. Recomendado si se planea escalar significativamente. |

**Recomendación para v1:** Railway.app o Render.com ofrecen la menor fricción de despliegue para un equipo pequeño, con integración directa desde repositorios Git y soporte de Dockerfiles generados por Laravel Sail.

---

### 5.5 Herramientas de Desarrollo

| Herramienta | Uso |
|---|---|
| Laravel Sail | Entorno de desarrollo local con Docker |
| Laravel Telescope | Debugging y monitoreo en desarrollo |
| Pest PHP | Framework de pruebas unitarias y de integración |
| GitHub Actions | CI/CD: ejecución automática de pruebas y despliegue |

---

## 6. Arquitectura General del Sistema

### 6.1 Descripción de la Arquitectura

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
│   principal)   │ │               │ │  (S3/local)      │
└────────────────┘ └────────────────┘ └─────────────────┘
```

### 6.2 Flujo de Procesamiento de Cotizaciones

El siguiente diagrama describe el flujo de procesamiento de un archivo de cotización desde su carga hasta su presentación como requisición editable:

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
│         Detección de tipo de archivo       │
├──────────────┬────────────────┬────────────┤
│    XLSX      │   PDF digital  │ PDF / JPG  │
│              │  (con texto)   │ escaneado  │
└──────┬───────┴───────┬────────┴─────┬──────┘
       │               │              │
       ▼               ▼              ▼
PhpSpreadsheet    PDFParser      OCR Service
(lectura de       (extracción   (Tesseract /
 celdas)          de texto)     Cloud Vision)
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
            │  Requisición         │
            │  guardada en BD      │
            │  (exportable en      │
            │   PDF y XLSX)        │
            └──────────────────────┘
```

### 6.3 Esquema Simplificado de Base de Datos

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
  id, project_id, name, category, file_path, version, uploaded_by, created_at

suppliers ──────────────────────────────────────────────────────────────
  id, trade_name, legal_name, rfc, category, contact_info (JSON)

vendors ────────────────────────────────────────────────────────────────
  id, supplier_id, name, phone, email

products (catálogo maestro) ────────────────────────────────────────────
  id, canonical_name, unit, description, category

product_aliases ────────────────────────────────────────────────────────
  id, product_id, alias_name, supplier_id

requisitions ───────────────────────────────────────────────────────────
  id, project_id, description, status, created_by, approved_by, date

requisition_items ──────────────────────────────────────────────────────
  id, requisition_id, product_id, quantity, unit, unit_price, supplier_id

purchase_orders ────────────────────────────────────────────────────────
  id, requisition_id, supplier_id, project_id, total, status, date

quotations ─────────────────────────────────────────────────────────────
  id, requisition_id, supplier_id, file_path, file_type, processed_at
```

---

## 7. Restricciones del Proyecto

- **Alcance de v1:** El sistema no incluirá facturación electrónica (CFDI), integración con SAT, módulo contable completo ni aplicación móvil nativa.
- **Idioma:** La interfaz del sistema será en español mexicano.
- **Presupuesto de despliegue:** Se prioriza el uso de infraestructura con costo inferior a 30 USD/mes en la etapa inicial.
- **Equipo de desarrollo:** El sistema está diseñado para ser construido y mantenido por un equipo de 1 a 3 desarrolladores.
- **Escalabilidad diferida:** La transición a microservicios o una SPA completa (React/Vue desacoplado) queda fuera del alcance de v1 y se considerará en versiones posteriores.

---

## 8. Criterios de Aceptación

El sistema será considerado aceptable para su entrega en v1 cuando:

1. Los cinco módulos principales sean funcionales y permitan operaciones CRUD completas.
2. El procesamiento de cotizaciones en los tres formatos (XLSX, PDF digital, PDF/JPG escaneado) funcione correctamente para al menos el 90% de los documentos de prueba proporcionados por el cliente.
3. La homologación de productos permita vincular al menos dos nombres alternativos a un producto del catálogo maestro.
4. El sistema de alertas de presupuesto envíe notificaciones correctamente al alcanzar los umbrales configurados.
5. Los roles de usuario restrinjan el acceso según los permisos definidos en la sección 2.3.
6. Los reportes de compras por proveedor y vendedor sean correctamente exportados en PDF y XLSX.
7. El sistema opere correctamente en Chrome, Firefox y Edge en versiones actuales.
8. El tiempo de respuesta de las operaciones principales no exceda los 2 segundos bajo condiciones normales de uso.

---

## 9. Apéndice — Glosario Extendido

| Término | Definición |
|---|---|
| Requisición | Documento interno que lista los materiales o servicios requeridos para un proyecto, previo a la generación de una orden de compra formal. |
| Homologación | Proceso de vincular múltiples nombres comerciales de un mismo producto bajo una denominación canónica en el catálogo maestro, facilitando la comparación de precios y la generación de reportes consolidados. |
| Cotización | Documento enviado por un proveedor con los precios y condiciones para el suministro de productos o servicios solicitados. |
| Catálogo maestro | Base de datos centralizada de productos reconocidos por el sistema, a la cual se vinculan los nombres alternativos provenientes de distintos proveedores. |
| OCR | Tecnología que permite extraer texto legible de imágenes digitales o documentos escaneados, convirtiendo contenido visual en texto procesable. |
| Pipeline de procesamiento | Secuencia automatizada de pasos que transforma un archivo de cotización crudo en una requisición estructurada y editable. |
| Período cerrado | Mes o período contable marcado como definitivo, en el que no se permiten modificaciones retroactivas de registros de gastos. |
| Staging | Entorno de pruebas que replica la configuración de producción, utilizado para validar cambios antes de su publicación al sistema en uso. |

