diff --git a/requerimientos.md b/requerimientos_v2.md
index 53be798..c925997 100644
--- a/requerimientos.md
+++ b/requerimientos_v2.md
@@ -1,9 +1,9 @@
 # Especificaci├│n de Requerimientos de Software (ERS)
 ## Sistema de Gesti├│n para Constructora Muulsinik
 
-**Versi├│n:** 1.0  
-**Fecha:** 27 de marzo de 2026  
-**Estado:** Borrador inicial  
+**Versi├│n:** 2.0  
+**Fecha:** 1 de abril de 2026  
+**Estado:** Borrador revisado  
 **Elaborado por:** Equipo de Desarrollo  
 
 ---
@@ -16,7 +16,9 @@ ### 1.1 Prop├│sito
 
 ### 1.2 Alcance del Sistema
 
-El sistema, denominado internamente **Muulsinik ERP v1**, es una aplicaci├│n web orientada a la gesti├│n operativa de proyectos de construcci├│n. Cubre cinco m├│dulos principales: gesti├│n de proyectos, control de gastos y presupuestos, gesti├│n documental, administraci├│n de proveedores y compras, y m├│dulo de requisiciones. El sistema est├í dise├▒ado bajo un enfoque progresivo y pr├íctico, priorizando funcionalidades esenciales con capacidad de escalar en versiones futuras.
+El sistema, denominado internamente **Muulsinik ERP v1**, es una aplicaci├│n web orientada a la gesti├│n operativa de proyectos de construcci├│n. Su funci├│n central es la **carga y procesamiento autom├ítico de cotizaciones** en distintos formatos (PDF, JPG, XLSX), con el objetivo de generar requisiciones de materiales de forma automatizada, minimizando la captura manual de datos.
+
+El sistema cubre cinco m├│dulos principales: gesti├│n de proyectos, control de gastos y presupuestos, gesti├│n documental, administraci├│n de proveedores y compras, y m├│dulo de requisiciones. Est├í dise├▒ado bajo un enfoque progresivo y pr├íctico, priorizando funcionalidades esenciales con capacidad de escalar en versiones futuras.
 
 ### 1.3 Definiciones y Acr├│nimos
 
@@ -28,7 +30,8 @@ ### 1.3 Definiciones y Acr├│nimos
 | RNF | Requerimiento No Funcional |
 | CRUD | Create, Read, Update, Delete (operaciones b├ísicas de datos) |
 | Homologaci├│n | Proceso de vincular productos de distintos proveedores bajo una denominaci├│n com├║n |
-| Requisici├│n | Documento interno que consolida productos solicitados para un proyecto |
+| Requisici├│n | Documento interno que consolida productos solicitados para un proyecto, generado autom├íticamente a partir de cotizaciones procesadas |
+| Pipeline de procesamiento | Secuencia automatizada de pasos que transforma un archivo de cotizaci├│n en una requisici├│n estructurada y editable |
 | Stack | Conjunto de tecnolog├¡as utilizadas en el desarrollo del sistema |
 | CDN | Content Delivery Network |
 
@@ -47,31 +50,39 @@ ## 2. Descripci├│n General del Sistema
 
 ### 2.1 Perspectiva del Producto
 
-Muulsinik ERP v1 es un sistema web de nueva creaci├│n que no forma parte de un sistema mayor existente. Su objetivo principal es centralizar la informaci├│n operativa de la constructora, eliminando el uso disperso de hojas de c├ílculo y documentos f├¡sicos. En etapas futuras, podr├í integrarse con sistemas contables o plataformas de facturaci├│n electr├│nica.
+Muulsinik ERP v1 es un sistema web de nueva creaci├│n que no forma parte de un sistema mayor existente. Su objetivo principal es **automatizar la generaci├│n de requisiciones a partir de cotizaciones cargadas por los usuarios**, eliminando el uso disperso de hojas de c├ílculo y la captura manual de datos. En etapas futuras, podr├í integrarse con sistemas contables o plataformas de facturaci├│n electr├│nica.
+
+El coraz├│n del sistema es el **pipeline de procesamiento de cotizaciones**: el usuario carga un archivo (PDF, JPG o XLSX) enviado por un proveedor, el sistema extrae autom├íticamente la informaci├│n relevante (productos, cantidades, precios, proveedor, tienda y proyecto), la estructura en una requisici├│n editable y la almacena lista para su revisi├│n, aprobaci├│n y exportaci├│n.
 
 ### 2.2 Funciones Principales del Sistema
 
 A nivel general, el sistema permitir├í:
 
+- **Cargar cotizaciones** en formato PDF (digital o escaneado), JPG y XLSX, y procesarlas autom├íticamente para generar requisiciones.
+- Extraer texto de PDFs digitales directamente; aplicar OCR a im├ígenes y documentos escaneados.
+- Leer datos directamente de celdas en archivos XLSX.
+- Estructurar la informaci├│n extra├¡da (proveedor, tienda, proyecto, productos, cantidades, precios) y presentarla en un formulario editable.
+- Notificar al usuario cuando alg├║n campo no pueda identificarse autom├íticamente, para completarlo manualmente.
+- Homologar productos de distintos proveedores bajo un nombre can├│nico com├║n.
 - Gestionar proyectos de construcci├│n con seguimiento de presupuesto y avance.
 - Registrar y controlar gastos operativos, distribuy├®ndolos entre proyectos activos.
-- Almacenar y organizar documentos relevantes (contratos, planos, permisos).
-- Administrar proveedores, contactos y ├│rdenes de compra.
-- Procesar cotizaciones en formato PDF, JPG o XLSX mediante extracci├│n de texto y OCR.
-- Generar requisiciones internas de materiales con soporte de edici├│n y exportaci├│n.
-- Homologar productos de distintos proveedores bajo una denominaci├│n com├║n.
+- Almacenar y organizar documentos relevantes (contratos, planos, permisos, cotizaciones).
+- Administrar proveedores, vendedores y ├│rdenes de compra.
 - Emitir alertas cuando el gasto alcance un porcentaje determinado del presupuesto.
+- Generar reportes de compras por proveedor y por vendedor.
 - Controlar el acceso mediante roles de usuario con distintos niveles de permisos.
 
-### 2.3 Clases de Usuarios
+### 2.3 Roles de Usuario
+
+Para una constructora de tama├▒o peque├▒o, se definen tres roles funcionales que cubren todas las necesidades operativas sin complejidad innecesaria:
 
 | Rol | Descripci├│n | Nivel de Acceso |
 |---|---|---|
-| Administrador | Gestiona usuarios, roles y configuraci├│n global del sistema | Total |
-| Gerente de Proyecto | Supervisa proyectos, aprueba requisiciones y revisa presupuestos | Alto |
-| Comprador / Encargado de Compras | Crea y gestiona requisiciones, proveedores y cotizaciones | Medio-alto |
-| Supervisor de Campo | Registra gastos y solicita materiales desde obra | Medio |
-| Contador / Finanzas | Consulta reportes financieros y controla presupuestos | Medio (solo lectura en m├│dulos operativos) |
+| **Administrador** | Gestiona usuarios, roles, configuraci├│n global, cat├ílogo de productos y homologaciones. Accede a todos los m├│dulos. | Total |
+| **Encargado de Compras** | Carga cotizaciones, procesa requisiciones, administra proveedores y vendedores, genera ├│rdenes de compra y consulta reportes. | Alto |
+| **Supervisor / Operativo** | Consulta proyectos activos, registra gastos, revisa requisiciones y documentos asociados a su proyecto. | Medio (lectura en reportes, escritura en gastos y solicitudes) |
+
+> **Nota:** El Administrador puede asignar o revocar roles a cualquier usuario desde el panel de configuraci├│n. Los permisos de cada rol son configurables por el administrador para adaptarse a futuros ajustes operativos sin necesidad de intervenci├│n del equipo de desarrollo.
 
 ### 2.4 Entorno Operativo
 
@@ -80,7 +91,7 @@ ### 2.4 Entorno Operativo
 ### 2.5 Restricciones de Dise├▒o e Implementaci├│n
 
 - Primera etapa: sistema monol├¡tico con arquitectura MVC, sin microservicios.
-- Desarrollo progresivo: se implementar├ín primero los m├│dulos de mayor impacto operativo.
+- Desarrollo progresivo: se implementar├ín primero los m├│dulos de mayor impacto operativo (requisiciones y procesamiento de cotizaciones).
 - Sin integraciones externas en v1 (no se conecta a SAT, ERP contable ni bancos).
 - El sistema debe ser mantenible por un equipo peque├▒o (1ÔÇô3 desarrolladores).
 
@@ -88,7 +99,7 @@ ### 2.6 Suposiciones y Dependencias
 
 - La constructora cuenta con acceso a internet estable en sus oficinas principales.
 - Los documentos de cotizaci├│n son proporcionados en los formatos acordados (PDF, JPG, XLSX).
-- El cliente designar├í a un usuario administrador responsable de la configuraci├│n inicial.
+- El cliente designar├í a un usuario con rol Administrador responsable de la configuraci├│n inicial.
 
 ---
 
@@ -104,10 +115,10 @@ ### 3.1 M├│dulo: Autenticaci├│n y Gesti├│n de Usuarios
 El sistema deber├í autenticar a los usuarios mediante correo electr├│nico y contrase├▒a. Las contrase├▒as deber├ín almacenarse con hash bcrypt. Se implementar├í protecci├│n contra ataques de fuerza bruta mediante bloqueo temporal tras cinco intentos fallidos.
 
 **RF-AUTH-02 ÔÇö Gesti├│n de roles y permisos**  
-El administrador podr├í crear, editar y desactivar usuarios. Cada usuario tendr├í asignado un rol que determina las vistas y acciones disponibles. Los roles predefinidos son: Administrador, Gerente de Proyecto, Comprador, Supervisor de Campo y Contador.
+El administrador podr├í crear, editar y desactivar usuarios. Cada usuario tendr├í asignado un rol que determina las vistas y acciones disponibles. Los roles predefinidos son: Administrador, Encargado de Compras y Supervisor / Operativo.
 
 **RF-AUTH-03 ÔÇö Selecci├│n de proyecto activo**  
-Una vez autenticado, el usuario podr├í seleccionar el proyecto activo desde un selector global visible en la interfaz. Las operaciones de registro de gastos, requisiciones y compras quedar├ín asociadas al proyecto activo seleccionado.
+Una vez autenticado, el usuario seleccionar├í el proyecto activo mediante un selector global visible en la barra de navegaci├│n superior. Todas las operaciones de registro de gastos, carga de cotizaciones, requisiciones y compras quedar├ín autom├íticamente asociadas al proyecto activo. El usuario podr├í cambiar de proyecto activo en cualquier momento sin necesidad de cerrar sesi├│n.
 
 **RF-AUTH-04 ÔÇö Restablecimiento de contrase├▒a**  
 El sistema enviar├í un enlace de restablecimiento de contrase├▒a al correo del usuario mediante un token de un solo uso con vigencia de 60 minutos.
@@ -189,33 +200,83 @@ ### 3.5 M├│dulo de Administraci├│n de Proveedores y Compras
 
 ### 3.6 M├│dulo de Requisiciones
 
-**RF-REQ-01 ÔÇö Creaci├│n de requisiciones**  
-El usuario podr├í crear una requisici├│n de materiales indicando: proyecto asociado, descripci├│n general, lista de productos (nombre, cantidad, unidad de medida, precio estimado, proveedor sugerido) y fecha de necesidad.
+Este m├│dulo es el **n├║cleo funcional del sistema**. Su objetivo principal es transformar autom├íticamente las cotizaciones enviadas por proveedores en requisiciones estructuradas, editables y exportables, reduciendo al m├¡nimo la captura manual de informaci├│n.
+
+#### 3.6.1 Pipeline de Procesamiento de Cotizaciones
+
+**RF-REQ-01 ÔÇö Carga de archivos de cotizaci├│n**  
+El sistema permitir├í al usuario cargar archivos de cotizaci├│n directamente desde la pantalla de nueva requisici├│n. Se aceptar├ín los siguientes formatos:
+
+- **XLSX:** Los datos se leer├ín directamente de las celdas de la hoja de c├ílculo. El sistema permitir├í configurar el mapeo de columnas (ej. columna A = nombre del producto, columna B = cantidad) para adaptarse a distintos formatos de proveedores.
+- **PDF con texto digital:** El sistema detectar├í que el documento contiene texto seleccionable y extraer├í su contenido directamente, sin necesidad de OCR.
+- **PDF escaneado / JPG:** El sistema detectar├í la ausencia de texto digital y aplicar├í OCR (Reconocimiento ├ôptico de Caracteres) para extraer el contenido. Se utilizar├í Google Cloud Vision API o AWS Textract como servicio de OCR en la nube, con Tesseract como alternativa de c├│digo abierto.
+
+**RF-REQ-02 ÔÇö Detecci├│n autom├ítica del tipo de documento**  
+El sistema identificar├í autom├íticamente el tipo de procesamiento requerido seg├║n el formato y contenido del archivo cargado, sin que el usuario necesite seleccionarlo manualmente.
+
+**RF-REQ-03 ÔÇö Extracci├│n y estructuraci├│n de informaci├│n**  
+Tras el procesamiento del archivo, el sistema identificar├í y estructurar├í los siguientes campos:
+
+| Campo | Descripci├│n |
+|---|---|
+| Proveedor | Nombre del proveedor o empresa que emite la cotizaci├│n |
+| Tienda / Sucursal | Nombre de la sucursal o punto de venta, si aplica |
+| Proyecto asociado | Proyecto al cual se vincular├í la requisici├│n (tomado del proyecto activo o seleccionado manualmente) |
+| Productos | Lista de art├¡culos cotizados |
+| Cantidad | Cantidad solicitada por producto |
+| Unidad de medida | Unidad (piezas, metros, litros, etc.) |
+| Precio unitario | Precio por unidad de cada producto |
+| Precio total | Precio total por rengl├│n (cantidad ├ù precio unitario) |
+
+Si alg├║n campo no puede identificarse autom├íticamente, se dejar├í vac├¡o y el sistema notificar├í al usuario para que lo complete manualmente (ver RF-REQ-06).
+
+**RF-REQ-04 ÔÇö Indicador de progreso durante el procesamiento**  
+Durante el procesamiento del archivo (especialmente en OCR, que puede tomar hasta 30 segundos), el sistema mostrar├í un indicador de progreso visible, informando al usuario que el proceso est├í en curso.
 
-**RF-REQ-02 ÔÇö Flujo de aprobaci├│n**  
-Una requisici├│n reci├®n creada tendr├í estado "Pendiente". El Gerente de Proyecto o Administrador podr├í aprobarla o rechazarla. Una vez aprobada, podr├í convertirse en orden de compra.
+**RF-REQ-05 ÔÇö Formulario editable previo al guardado**  
+La informaci├│n extra├¡da se presentar├í en un formulario editable antes de guardarse como requisici├│n. El usuario podr├í:
 
-**RF-REQ-03 ÔÇö Carga de cotizaciones**  
-El sistema permitir├í adjuntar cotizaciones de proveedores en los siguientes formatos:
+- Modificar cualquier campo extra├¡do incorrectamente.
+- Agregar productos que no hayan sido detectados.
+- Eliminar productos duplicados o irrelevantes.
+- Asignar o cambiar el proveedor, tienda y proyecto asociado.
+- Confirmar o corregir cantidades y precios.
 
-- **XLSX:** Los datos se extraer├ín directamente de las celdas de la hoja de c├ílculo mediante mapeo configurable de columnas.
-- **PDF con texto digital:** El sistema extraer├í el texto directamente del documento y aplicar├í un parser para identificar productos, cantidades y precios.
-- **PDF escaneado / JPG:** El sistema aplicar├í OCR (basado en Tesseract.js en el cliente o una API de OCR en la nube) para extraer el texto del documento y luego procesarlo con el mismo parser.
+**RF-REQ-06 ÔÇö Notificaci├│n de campos incompletos**  
+Cuando una cotizaci├│n procesada tenga campos vac├¡os (proveedor no identificado, proyecto no asignado, productos sin precio, etc.), el sistema mostrar├í una alerta visible en la parte superior del formulario editable y listar├í los campos pendientes de completar, indicando el rengl├│n o campo espec├¡fico que requiere atenci├│n.
 
-**RF-REQ-04 ÔÇö Procesamiento y estructuraci├│n de cotizaciones**  
-Tras la extracci├│n del texto, el sistema identificar├í y estructurar├í la siguiente informaci├│n: nombre del proveedor, nombre de la tienda/sucursal, proyecto asociado, lista de productos (nombre, cantidad, precio unitario, precio total). Si alg├║n campo no puede identificarse, se dejar├í vac├¡o y se notificar├í al usuario para que lo complete manualmente.
+**RF-REQ-07 ÔÇö Homologaci├│n de productos**  
+El sistema contar├í con un cat├ílogo maestro de productos. Cuando un producto extra├¡do de la cotizaci├│n no coincida exactamente con un producto del cat├ílogo, el sistema ofrecer├í sugerencias de coincidencia por similitud de nombre. El usuario podr├í:
 
-**RF-REQ-05 ÔÇö Homologaci├│n de productos**  
-El sistema contar├í con un cat├ílogo maestro de productos. Cuando un producto de una cotizaci├│n no coincida exactamente con uno del cat├ílogo, el sistema ofrecer├í sugerencias de coincidencia por similitud de nombre. El usuario podr├í vincular el producto de la cotizaci├│n con el producto del cat├ílogo (homologaci├│n), de modo que ambos nombres apunten al mismo art├¡culo en reportes y gr├íficos consolidados.
+- Vincular el producto de la cotizaci├│n con un producto existente del cat├ílogo (homologaci├│n).
+- Crear un nuevo producto en el cat├ílogo directamente desde el formulario.
+- Dejar el producto sin homologar de forma temporal.
 
-**RF-REQ-06 ÔÇö Edici├│n manual de requisiciones procesadas**  
-La informaci├│n extra├¡da de las cotizaciones se presentar├í en un formulario editable antes de guardarse. El usuario podr├í modificar cualquier campo, agregar o eliminar productos y corregir datos incorrectos.
+La homologaci├│n es fundamental para la generaci├│n de reportes y gr├íficos consolidados, ya que un mismo art├¡culo puede aparecer con nombres distintos seg├║n el proveedor.
 
-**RF-REQ-07 ÔÇö Visualizaci├│n, edici├│n y exportaci├│n de requisiciones**  
-Las requisiciones guardadas podr├ín visualizarse en formato de tabla con todos sus productos y detalles. El sistema permitir├í exportar la requisici├│n en formato PDF (con dise├▒o de documento formal) y en XLSX (para procesamiento externo).
+#### 3.6.2 Gesti├│n de Requisiciones
 
-**RF-REQ-08 ÔÇö Notificaci├│n de campos incompletos**  
-Cuando una cotizaci├│n procesada tenga campos vac├¡os (proveedor no identificado, proyecto no asignado, etc.), el sistema mostrar├í una alerta visible en la interfaz y listar├í los campos pendientes de completar.
+**RF-REQ-08 ÔÇö Creaci├│n manual de requisiciones**  
+Adem├ís del flujo automatizado, el usuario podr├í crear requisiciones de forma manual ingresando directamente los campos: proyecto asociado, descripci├│n general, lista de productos (nombre, cantidad, unidad, precio estimado, proveedor sugerido) y fecha de necesidad.
+
+**RF-REQ-09 ÔÇö Flujo de aprobaci├│n simplificado**  
+Una requisici├│n reci├®n creada tendr├í estado **"Borrador"**. El flujo de estados es el siguiente:
+
+```
+Borrador ÔåÆ Pendiente de aprobaci├│n ÔåÆ Aprobada ÔåÆ Convertida en Orden de Compra
+                                   Ôåÿ Rechazada (con comentario obligatorio)
+```
+
+El Administrador o el Encargado de Compras podr├ín aprobar o rechazar una requisici├│n. Al rechazar, el sistema solicitar├í un comentario indicando el motivo.
+
+**RF-REQ-10 ÔÇö Visualizaci├│n de requisiciones**  
+Las requisiciones guardadas podr├ín visualizarse en formato de tabla con todos sus productos, cantidades, precios y estado actual. El usuario podr├í filtrar por proyecto, fecha, proveedor y estado.
+
+**RF-REQ-11 ÔÇö Exportaci├│n de requisiciones**  
+El sistema permitir├í exportar cualquier requisici├│n en:
+
+- **PDF:** Con dise├▒o de documento formal (logotipo, datos del proyecto, tabla de productos, totales).
+- **XLSX:** Para procesamiento externo o revisi├│n en Excel.
 
 ---
 
@@ -228,30 +289,107 @@ ### 3.7 M├│dulo de Reportes y Anal├¡tica
 Mostrar├í el total de compras agrupado por proveedor, vendedor, categor├¡a de producto y proyecto en un per├¡odo seleccionable.
 
 **RF-REP-03 ÔÇö Reporte de productos homologados**  
-Listar├í los productos del cat├ílogo maestro indicando cu├íntos nombres alternativos tiene cada uno y qu├® proveedores los comercializan, con el precio promedio registrado.
+Listar├í los productos del cat├ílogo maestro indicando cu├íntos nombres alternativos tiene cada uno, qu├® proveedores los comercializan y el precio promedio registrado.
 
 **RF-REP-04 ÔÇö Exportaci├│n de reportes**  
 Todos los reportes del sistema ser├ín exportables en formato PDF y XLSX.
 
 ---
 
-## 4. Requerimientos No Funcionales
+## 4. Flujo de Trabajo del Sistema
+
+Esta secci├│n describe los flujos de trabajo principales del sistema, dise├▒ados para ser lo m├ís intuitivos y eficientes posible para los usuarios.
+
+---
+
+### 4.1 Flujo Principal: De Cotizaci├│n a Requisici├│n
+
+Este es el flujo central del sistema y debe sentirse fluido y directo para el usuario:
+
+```
+1. Usuario selecciona el proyecto activo (selector en barra superior)
+       Ôöé
+       Ôû╝
+2. Accede al m├│dulo de Requisiciones ÔåÆ "Nueva Requisici├│n"
+       Ôöé
+       Ôû╝
+3. Arrastra o selecciona el archivo de cotizaci├│n (PDF / JPG / XLSX)
+       Ôöé
+       Ôû╝
+4. El sistema detecta autom├íticamente el tipo de archivo y procesa:
+   Ôö£ÔöÇÔöÇ XLSX      ÔåÆ Lee celdas directamente (PhpSpreadsheet)
+   Ôö£ÔöÇÔöÇ PDF digital ÔåÆ Extrae texto (PDFParser)
+   ÔööÔöÇÔöÇ PDF/JPG escaneado ÔåÆ Aplica OCR (Cloud Vision / Tesseract)
+       Ôöé
+       Ôû╝
+5. Se muestra el formulario pre-llenado con la informaci├│n extra├¡da:
+   - Alerta si hay campos vac├¡os o sin identificar
+   - Sugerencias de homologaci├│n para productos no reconocidos
+       Ôöé
+       Ôû╝
+6. El usuario revisa, corrige y completa los campos necesarios
+       Ôöé
+       Ôû╝
+7. El usuario guarda la requisici├│n (estado: "Pendiente de aprobaci├│n")
+       Ôöé
+       Ôû╝
+8. El Administrador / Encargado de Compras aprueba o rechaza
+       Ôöé
+       Ôû╝
+9. Requisici├│n aprobada ÔåÆ Se puede exportar (PDF / XLSX) o convertir en Orden de Compra
+```
+
+### 4.2 Flujo de Registro de Gastos
+
+```
+1. Usuario selecciona el proyecto activo
+       Ôöé
+       Ôû╝
+2. Accede al m├│dulo de Gastos ÔåÆ "Nuevo Gasto"
+       Ôöé
+       Ôû╝
+3. Completa: concepto, monto, fecha, categor├¡a, comprobante (opcional)
+       Ôöé
+       Ôû╝
+4. El gasto queda registrado y el presupuesto del proyecto se actualiza en tiempo real
+       Ôöé
+       Ôû╝
+5. Si el gasto acumulado supera el 70% / 90% / 100% del presupuesto:
+   ÔåÆ El sistema env├¡a notificaci├│n interna (y correo opcional)
+```
+
+### 4.3 Flujo de Gesti├│n de Proveedores
+
+```
+1. Acceder al m├│dulo de Proveedores
+       Ôöé
+       Ôû╝
+2. Registrar o buscar proveedor existente
+       Ôöé
+       Ôö£ÔöÇÔöÇ Agregar / editar vendedores del proveedor
+       Ôö£ÔöÇÔöÇ Consultar historial de compras
+       ÔööÔöÇÔöÇ Ver requisiciones y ├│rdenes de compra asociadas
+```
+
+---
+
+## 5. Requerimientos No Funcionales
 
 Los requerimientos no funcionales se identifican con el formato **RNF-[Categor├¡a]-[N├║mero]**.
 
 ---
 
-### 4.1 Rendimiento
+### 5.1 Rendimiento
 
 **RNF-REND-01** ÔÇö El sistema deber├í responder a las solicitudes HTTP en menos de 2 segundos para el 95% de las operaciones bajo carga normal (hasta 20 usuarios concurrentes).
 
-**RNF-REND-02** ÔÇö El procesamiento de un archivo XLSX o PDF con texto digital no deber├í exceder los 10 segundos. El procesamiento mediante OCR podr├í tomar hasta 30 segundos; durante este tiempo el sistema mostrar├í un indicador de progreso.
+**RNF-REND-02** ÔÇö El procesamiento de un archivo XLSX o PDF con texto digital no deber├í exceder los 10 segundos. El procesamiento mediante OCR podr├í tomar hasta 30 segundos; durante este tiempo el sistema mostrar├í un indicador de progreso visible al usuario.
 
 **RNF-REND-03** ÔÇö La interfaz de usuario deber├í cargar por primera vez en menos de 3 segundos en una conexi├│n de 10 Mbps.
 
 ---
 
-### 4.2 Seguridad
+### 5.2 Seguridad
 
 **RNF-SEG-01** ÔÇö Toda la comunicaci├│n entre el cliente y el servidor deber├í realizarse sobre HTTPS (TLS 1.2 o superior).
 
@@ -267,21 +405,25 @@ ### 4.2 Seguridad
 
 ---
 
-### 4.3 Usabilidad
+### 5.3 Usabilidad
 
 **RNF-USA-01** ÔÇö La interfaz deber├í ser responsiva y funcionar correctamente en resoluciones desde 375px (m├│vil) hasta 1920px (escritorio).
 
-**RNF-USA-02** ÔÇö El sistema deber├í guiar al usuario mediante mensajes de error descriptivos y sugerencias de correcci├│n, evitando mensajes gen├®ricos como "Error 500".
+**RNF-USA-02** ÔÇö El flujo de carga y procesamiento de cotizaciones deber├í completarse en no m├ís de 3 pasos visibles para el usuario: (1) seleccionar archivo, (2) revisar y corregir datos extra├¡dos, (3) guardar requisici├│n.
+
+**RNF-USA-03** ÔÇö El sistema deber├í guiar al usuario mediante mensajes de error descriptivos y sugerencias de correcci├│n, evitando mensajes gen├®ricos como "Error 500".
 
-**RNF-USA-03** ÔÇö Las acciones destructivas (eliminar proyectos, rechazar requisiciones) deber├ín requerir confirmaci├│n expl├¡cita del usuario antes de ejecutarse.
+**RNF-USA-04** ÔÇö Las acciones destructivas (eliminar proyectos, rechazar requisiciones) deber├ín requerir confirmaci├│n expl├¡cita del usuario antes de ejecutarse.
 
-**RNF-USA-04** ÔÇö El sistema deber├í mostrar estados de carga (skeleton loaders o spinners) para todas las operaciones que tarden m├ís de 500 ms.
+**RNF-USA-05** ÔÇö El sistema deber├í mostrar estados de carga (skeleton loaders o spinners) para todas las operaciones que tarden m├ís de 500 ms.
 
-**RNF-USA-05** ÔÇö La interfaz deber├í seguir principios de accesibilidad WCAG 2.1 nivel AA, incluyendo contraste de colores de al menos 4.5:1 para texto normal.
+**RNF-USA-06** ÔÇö La interfaz deber├í seguir principios de accesibilidad WCAG 2.1 nivel AA, incluyendo contraste de colores de al menos 4.5:1 para texto normal.
+
+**RNF-USA-07** ÔÇö El selector de proyecto activo deber├í estar siempre visible en la barra de navegaci├│n principal, permitiendo al usuario cambiar de proyecto sin perder el contexto de la pantalla actual.
 
 ---
 
-### 4.4 Confiabilidad y Disponibilidad
+### 5.4 Confiabilidad y Disponibilidad
 
 **RNF-CONF-01** ÔÇö El sistema deber├í tener una disponibilidad m├¡nima del 99% mensual, excluyendo ventanas de mantenimiento programado.
 
@@ -291,7 +433,7 @@ ### 4.4 Confiabilidad y Disponibilidad
 
 ---
 
-### 4.5 Mantenibilidad y Escalabilidad
+### 5.5 Mantenibilidad y Escalabilidad
 
 **RNF-MANT-01** ÔÇö El c├│digo fuente deber├í seguir los est├índares de codificaci├│n del framework seleccionado (PSR-12 para PHP/Laravel) y estar documentado con comentarios en funciones p├║blicas y rutas de la API.
 
@@ -303,7 +445,7 @@ ### 4.5 Mantenibilidad y Escalabilidad
 
 ---
 
-### 4.6 Portabilidad
+### 5.6 Portabilidad
 
 **RNF-PORT-01** ÔÇö El sistema deber├í funcionar correctamente en los navegadores Chrome 110+, Firefox 110+, Edge 110+ y Safari 16+.
 
@@ -311,65 +453,63 @@ ### 4.6 Portabilidad
 
 ---
 
-## 5. Stack Tecnol├│gico Propuesto
+## 6. Stack Tecnol├│gico Propuesto
 
 Se propone el siguiente stack tecnol├│gico orientado a la simplicidad de desarrollo, interfaz moderna y opciones de despliegue econ├│micas.
 
 ---
 
-### 5.1 Backend
+### 6.1 Backend
 
 | Componente | Tecnolog├¡a | Justificaci├│n |
 |---|---|---|
-| Framework principal | **Laravel 11** (PHP 8.3) | Ecosistema maduro, convenciones claras, ORM Eloquent, sistema de autenticaci├│n integrado (Laravel Sanctum), soporte oficial de largo plazo. Cumple con el requerimiento del cliente. |
+| Framework principal | **Laravel 11** (PHP 8.3) | Ecosistema maduro, convenciones claras, ORM Eloquent, sistema de autenticaci├│n integrado (Laravel Sanctum), soporte oficial de largo plazo. |
 | Autenticaci├│n | **Laravel Sanctum** | Tokens de sesi├│n para aplicaciones web, sin necesidad de JWT complejo en v1. |
-| Procesamiento de colas | **Laravel Queue + Redis** | Para el procesamiento as├¡ncrono de OCR y extracci├│n de cotizaciones sin bloquear la interfaz. |
+| Procesamiento de colas | **Laravel Queue + Redis** | Para el procesamiento as├¡ncrono de OCR y extracci├│n de cotizaciones sin bloquear la interfaz del usuario. |
 | Extracci├│n PDF con texto | **smalot/pdfparser** | Librer├¡a PHP para extraer texto de PDFs digitales sin dependencias externas. |
-| OCR de im├ígenes/PDFs escaneados | **Google Cloud Vision API** (o **AWS Textract** como alternativa) | Alta precisi├│n en OCR con soporte para espa├▒ol. Costo por uso, sin inversi├│n inicial. Alternativa open source: Tesseract a trav├®s de `thiagoalessio/tesseract_ocr` si se prefiere evitar costos de API. |
+| OCR de im├ígenes/PDFs escaneados | **Google Cloud Vision API** (o **AWS Textract** como alternativa) | Alta precisi├│n en OCR con soporte para espa├▒ol. Costo por uso, sin inversi├│n inicial. Alternativa open source: Tesseract a trav├®s de `thiagoalessio/tesseract_ocr`. |
 | Procesamiento XLSX | **PhpSpreadsheet** | Librer├¡a oficial de PHP para leer y escribir archivos Excel, sin dependencia de Office. |
 | Exportaci├│n a PDF | **DomPDF** (Barryvdh) | Genera PDFs a partir de vistas Blade, con soporte de estilos CSS b├ísicos. |
 
 ---
 
-### 5.2 Frontend
+### 6.2 Frontend
 
 | Componente | Tecnolog├¡a | Justificaci├│n |
 |---|---|---|
-| Framework UI | **Livewire 3** (con Alpine.js) | Permite construir interfaces reactivas y din├ímicas directamente con PHP/Blade, sin separar completamente el frontend. Ideal para equipos peque├▒os que ya usan Laravel. Reduce la complejidad de mantener una SPA separada. |
-| Estilos | **Tailwind CSS v4** | Framework utilitario con excelente soporte de dise├▒o moderno, responsivo y temas dark/light. Se integra nativamente con Laravel y Livewire. |
-| Componentes UI | **Flux UI** (componentes oficiales de Livewire) o **Mary UI** | Conjunto de componentes preconstruidos (tablas, modales, formularios, dashboards) que aceleran el desarrollo sin sacrificar personalizaci├│n. |
-| Gr├íficas | **Chart.js** (v├¡a Alpine.js) | Librer├¡a ligera para gr├íficas de barras, pastel y l├¡neas. Sin dependencias pesadas. |
+| Framework UI | **Livewire 3** (con Alpine.js) | Permite construir interfaces reactivas y din├ímicas directamente con PHP/Blade, sin separar completamente el frontend. Ideal para equipos peque├▒os que ya usan Laravel. |
+| Estilos | **Tailwind CSS v4** | Framework utilitario con excelente soporte de dise├▒o moderno, responsivo y temas dark/light. |
+| Componentes UI | **Flux UI** (componentes oficiales de Livewire) o **Mary UI** | Conjunto de componentes preconstruidos (tablas, modales, formularios, dashboards) que aceleran el desarrollo. |
+| Gr├íficas | **Chart.js** (v├¡a Alpine.js) | Librer├¡a ligera para gr├íficas de barras, pastel y l├¡neas. |
 | ├ìconos | **Lucide Icons** | Set de ├¡conos SVG consistente y moderno, disponible como paquete npm o CDN. |
 
-> **Alternativa a Livewire:** Si el equipo prefiere una SPA m├ís interactiva, se puede optar por **Inertia.js + Vue 3** manteniendo Laravel como backend. Esta opci├│n ofrece mayor fluidez en la navegaci├│n pero requiere gestionar el frontend por separado.
+> **Alternativa a Livewire:** Si el equipo prefiere una SPA m├ís interactiva, se puede optar por **Inertia.js + Vue 3** manteniendo Laravel como backend.
 
 ---
 
-### 5.3 Base de Datos
+### 6.3 Base de Datos
 
 | Componente | Tecnolog├¡a | Justificaci├│n |
 |---|---|---|
-| Motor principal | **MySQL 8** o **PostgreSQL 15** | MySQL es ampliamente soportado en todos los proveedores de hosting econ├│micos. PostgreSQL es recomendado si se planean consultas anal├¡ticas complejas o uso de tipos JSON avanzados. |
+| Motor principal | **MySQL 8** o **PostgreSQL 15** | MySQL es ampliamente soportado en todos los proveedores de hosting econ├│micos. PostgreSQL es recomendado para consultas anal├¡ticas complejas. |
 | Cache y colas | **Redis** | Cache de sesiones, resultados de reportes frecuentes y gesti├│n de colas de procesamiento. |
 
 ---
 
-### 5.4 Infraestructura y Despliegue
-
-Se priorizan opciones econ├│micas y de f├ícil gesti├│n:
+### 6.4 Infraestructura y Despliegue
 
 | Opci├│n | Proveedor | Costo Estimado | Adecuado Para |
 |---|---|---|---|
 | **Opci├│n A (Recomendada)** | **Railway.app** | ~$5ÔÇô15 USD/mes | Despliegue con Docker, base de datos incluida, sin configuraci├│n de servidor. Ideal para v1. |
 | **Opci├│n B** | **Render.com** | ~$7ÔÇô20 USD/mes | Similar a Railway, con plan gratuito limitado. Buen soporte para Laravel. |
 | **Opci├│n C** | **DigitalOcean Droplet** | ~$12ÔÇô24 USD/mes | VPS con mayor control, escalable. Requiere configuraci├│n de servidor (Nginx, PHP-FPM). |
-| **Opci├│n D** | **AWS EC2 t3.micro + RDS** | ~$15ÔÇô30 USD/mes | Mayor control y escalabilidad. M├ís configuraci├│n inicial. Recomendado si se planea escalar significativamente. |
+| **Opci├│n D** | **AWS EC2 t3.micro + RDS** | ~$15ÔÇô30 USD/mes | Mayor control y escalabilidad. Recomendado si se planea escalar significativamente. |
 
-**Recomendaci├│n para v1:** Railway.app o Render.com ofrecen la menor fricci├│n de despliegue para un equipo peque├▒o, con integraci├│n directa desde repositorios Git y soporte de Dockerfiles generados por Laravel Sail.
+**Recomendaci├│n para v1:** Railway.app o Render.com ofrecen la menor fricci├│n de despliegue para un equipo peque├▒o, con integraci├│n directa desde repositorios Git.
 
 ---
 
-### 5.5 Herramientas de Desarrollo
+### 6.5 Herramientas de Desarrollo
 
 | Herramienta | Uso |
 |---|---|
@@ -380,9 +520,9 @@ ### 5.5 Herramientas de Desarrollo
 
 ---
 
-## 6. Arquitectura General del Sistema
+## 7. Arquitectura General del Sistema
 
-### 6.1 Descripci├│n de la Arquitectura
+### 7.1 Descripci├│n de la Arquitectura
 
 El sistema seguir├í una arquitectura **Monol├¡tica MVC** en su primera versi├│n, utilizando el patr├│n **Model-View-Controller** de Laravel con la capa de presentaci├│n gestionada por **Livewire**. Esta decisi├│n prioriza la velocidad de desarrollo, la simplicidad de mantenimiento y la reducci├│n de complejidad operativa para un equipo peque├▒o.
 
@@ -431,13 +571,13 @@ ### 6.1 Descripci├│n de la Arquitectura
 ÔöîÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔû╝ÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÉ ÔöîÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔû╝ÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÉ ÔöîÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔû╝ÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÉ
 Ôöé   MySQL / PG   Ôöé Ôöé     Redis      Ôöé Ôöé  Almacenamiento  Ôöé
 Ôöé  (Base datos   Ôöé Ôöé  (Cache/Colas) Ôöé Ôöé  de archivos     Ôöé
-Ôöé   principal)   Ôöé Ôöé               Ôöé Ôöé  (S3/local)      Ôöé
+Ôöé   principal)   Ôöé Ôöé                Ôöé Ôöé  (S3/local)      Ôöé
 ÔööÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÿ ÔööÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÿ ÔööÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÿ
 ```
 
-### 6.2 Flujo de Procesamiento de Cotizaciones
+### 7.2 Flujo de Procesamiento de Cotizaciones
 
-El siguiente diagrama describe el flujo de procesamiento de un archivo de cotizaci├│n desde su carga hasta su presentaci├│n como requisici├│n editable:
+El siguiente diagrama describe el flujo completo desde la carga del archivo hasta la generaci├│n de la requisici├│n:
 
 ```
 USUARIO carga archivo (PDF / JPG / XLSX)
@@ -451,7 +591,7 @@ ### 6.2 Flujo de Procesamiento de Cotizaciones
           Ôöé V├ílido
           Ôû╝
 ÔöîÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÉ
-Ôöé         Detecci├│n de tipo de archivo       Ôöé
+Ôöé         Detecci├│n autom├ítica del tipo      Ôöé
 Ôö£ÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔö¼ÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔö¼ÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöñ
 Ôöé    XLSX      Ôöé   PDF digital  Ôöé PDF / JPG  Ôöé
 Ôöé              Ôöé  (con texto)   Ôöé escaneado  Ôöé
@@ -459,8 +599,8 @@ ### 6.2 Flujo de Procesamiento de Cotizaciones
        Ôöé               Ôöé              Ôöé
        Ôû╝               Ôû╝              Ôû╝
 PhpSpreadsheet    PDFParser      OCR Service
-(lectura de       (extracci├│n   (Tesseract /
- celdas)          de texto)     Cloud Vision)
+(lectura de       (extracci├│n   (Cloud Vision /
+ celdas)          de texto)      Tesseract)
        Ôöé               Ôöé              Ôöé
        ÔööÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔö┤ÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÿ
                        Ôöé
@@ -498,7 +638,7 @@ ### 6.2 Flujo de Procesamiento de Cotizaciones
             ÔööÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÿ
 ```
 
-### 6.3 Esquema Simplificado de Base de Datos
+### 7.3 Esquema Simplificado de Base de Datos
 
 Las entidades principales del sistema y sus relaciones son las siguientes:
 
@@ -530,6 +670,9 @@ ### 6.3 Esquema Simplificado de Base de Datos
 product_aliases ÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇ
   id, product_id, alias_name, supplier_id
 
+quotations ÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇ
+  id, requisition_id, supplier_id, file_path, file_type, processed_at
+
 requisitions ÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇ
   id, project_id, description, status, created_by, approved_by, date
 
@@ -538,14 +681,11 @@ ### 6.3 Esquema Simplificado de Base de Datos
 
 purchase_orders ÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇ
   id, requisition_id, supplier_id, project_id, total, status, date
-
-quotations ÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇ
-  id, requisition_id, supplier_id, file_path, file_type, processed_at
 ```
 
 ---
 
-## 7. Restricciones del Proyecto
+## 8. Restricciones del Proyecto
 
 - **Alcance de v1:** El sistema no incluir├í facturaci├│n electr├│nica (CFDI), integraci├│n con SAT, m├│dulo contable completo ni aplicaci├│n m├│vil nativa.
 - **Idioma:** La interfaz del sistema ser├í en espa├▒ol mexicano.
@@ -555,31 +695,32 @@ ## 7. Restricciones del Proyecto
 
 ---
 
-## 8. Criterios de Aceptaci├│n
+## 9. Criterios de Aceptaci├│n
 
 El sistema ser├í considerado aceptable para su entrega en v1 cuando:
 
 1. Los cinco m├│dulos principales sean funcionales y permitan operaciones CRUD completas.
 2. El procesamiento de cotizaciones en los tres formatos (XLSX, PDF digital, PDF/JPG escaneado) funcione correctamente para al menos el 90% de los documentos de prueba proporcionados por el cliente.
-3. La homologaci├│n de productos permita vincular al menos dos nombres alternativos a un producto del cat├ílogo maestro.
-4. El sistema de alertas de presupuesto env├¡e notificaciones correctamente al alcanzar los umbrales configurados.
-5. Los roles de usuario restrinjan el acceso seg├║n los permisos definidos en la secci├│n 2.3.
-6. Los reportes de compras por proveedor y vendedor sean correctamente exportados en PDF y XLSX.
-7. El sistema opere correctamente en Chrome, Firefox y Edge en versiones actuales.
-8. El tiempo de respuesta de las operaciones principales no exceda los 2 segundos bajo condiciones normales de uso.
+3. El flujo de carga de cotizaci├│n a requisici├│n editable se complete en no m├ís de 3 pasos visibles para el usuario.
+4. La homologaci├│n de productos permita vincular al menos dos nombres alternativos a un producto del cat├ílogo maestro.
+5. El sistema de alertas de presupuesto env├¡e notificaciones correctamente al alcanzar los umbrales configurados.
+6. Los roles de usuario (Administrador, Encargado de Compras, Supervisor / Operativo) restrinjan el acceso seg├║n los permisos definidos en la secci├│n 2.3.
+7. Los reportes de compras por proveedor y vendedor sean correctamente exportados en PDF y XLSX.
+8. El sistema opere correctamente en Chrome, Firefox y Edge en versiones actuales.
+9. El tiempo de respuesta de las operaciones principales no exceda los 2 segundos bajo condiciones normales de uso.
 
 ---
 
-## 9. Ap├®ndice ÔÇö Glosario Extendido
+## 10. Ap├®ndice ÔÇö Glosario Extendido
 
 | T├®rmino | Definici├│n |
 |---|---|
-| Requisici├│n | Documento interno que lista los materiales o servicios requeridos para un proyecto, previo a la generaci├│n de una orden de compra formal. |
+| Requisici├│n | Documento interno que lista los materiales o servicios requeridos para un proyecto, generado autom├íticamente a partir de una cotizaci├│n procesada o creado manualmente, previo a la generaci├│n de una orden de compra formal. |
 | Homologaci├│n | Proceso de vincular m├║ltiples nombres comerciales de un mismo producto bajo una denominaci├│n can├│nica en el cat├ílogo maestro, facilitando la comparaci├│n de precios y la generaci├│n de reportes consolidados. |
-| Cotizaci├│n | Documento enviado por un proveedor con los precios y condiciones para el suministro de productos o servicios solicitados. |
+| Cotizaci├│n | Documento enviado por un proveedor con los precios y condiciones para el suministro de productos o servicios solicitados. Puede estar en formato PDF (digital o escaneado), JPG o XLSX. |
 | Cat├ílogo maestro | Base de datos centralizada de productos reconocidos por el sistema, a la cual se vinculan los nombres alternativos provenientes de distintos proveedores. |
 | OCR | Tecnolog├¡a que permite extraer texto legible de im├ígenes digitales o documentos escaneados, convirtiendo contenido visual en texto procesable. |
-| Pipeline de procesamiento | Secuencia automatizada de pasos que transforma un archivo de cotizaci├│n crudo en una requisici├│n estructurada y editable. |
+| Pipeline de procesamiento | Secuencia automatizada de pasos que transforma un archivo de cotizaci├│n crudo en una requisici├│n estructurada y editable: validaci├│n ÔåÆ detecci├│n de tipo ÔåÆ extracci├│n ÔåÆ estructuraci├│n ÔåÆ homologaci├│n ÔåÆ formulario editable ÔåÆ guardado. |
+| Proyecto activo | Proyecto seleccionado globalmente por el usuario en la barra de navegaci├│n, al cual se asocian autom├íticamente todas las operaciones realizadas durante la sesi├│n. |
 | Per├¡odo cerrado | Mes o per├¡odo contable marcado como definitivo, en el que no se permiten modificaciones retroactivas de registros de gastos. |
 | Staging | Entorno de pruebas que replica la configuraci├│n de producci├│n, utilizado para validar cambios antes de su publicaci├│n al sistema en uso. |
-
