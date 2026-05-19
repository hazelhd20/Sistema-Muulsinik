BEGIN TRANSACTION;
CREATE TABLE IF NOT EXISTS "cache" (
	"key"	varchar NOT NULL,
	"value"	text NOT NULL,
	"expiration"	integer NOT NULL,
	PRIMARY KEY("key")
);
CREATE TABLE IF NOT EXISTS "cache_locks" (
	"key"	varchar NOT NULL,
	"owner"	varchar NOT NULL,
	"expiration"	integer NOT NULL,
	PRIMARY KEY("key")
);
CREATE TABLE IF NOT EXISTS "categories" (
	"id"	integer NOT NULL,
	"name"	varchar NOT NULL,
	"created_at"	datetime,
	"updated_at"	datetime,
	PRIMARY KEY("id" AUTOINCREMENT)
);
CREATE TABLE IF NOT EXISTS "expense_allocations" (
	"id"	integer NOT NULL,
	"expense_id"	integer NOT NULL,
	"project_id"	integer NOT NULL,
	"amount"	numeric NOT NULL,
	"percentage"	numeric,
	"created_at"	datetime,
	"updated_at"	datetime,
	PRIMARY KEY("id" AUTOINCREMENT),
	FOREIGN KEY("expense_id") REFERENCES "expenses"("id") on delete cascade,
	FOREIGN KEY("project_id") REFERENCES "projects"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "expenses" (
	"id"	integer NOT NULL,
	"concept"	varchar NOT NULL,
	"amount"	numeric NOT NULL,
	"date"	date NOT NULL,
	"category"	varchar,
	"project_id"	integer,
	"user_id"	integer NOT NULL,
	"receipt_file"	varchar,
	"created_at"	datetime,
	"updated_at"	datetime,
	"is_distributed"	tinyint(1) NOT NULL DEFAULT '0',
	PRIMARY KEY("id" AUTOINCREMENT),
	FOREIGN KEY("project_id") REFERENCES "projects"("id") on delete cascade on update no action,
	FOREIGN KEY("user_id") REFERENCES "users"("id") on delete cascade on update no action
);
CREATE TABLE IF NOT EXISTS "failed_jobs" (
	"id"	integer NOT NULL,
	"uuid"	varchar NOT NULL,
	"connection"	text NOT NULL,
	"queue"	text NOT NULL,
	"payload"	text NOT NULL,
	"exception"	text NOT NULL,
	"failed_at"	datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY("id" AUTOINCREMENT)
);
CREATE TABLE IF NOT EXISTS "job_batches" (
	"id"	varchar NOT NULL,
	"name"	varchar NOT NULL,
	"total_jobs"	integer NOT NULL,
	"pending_jobs"	integer NOT NULL,
	"failed_jobs"	integer NOT NULL,
	"failed_job_ids"	text NOT NULL,
	"options"	text,
	"cancelled_at"	integer,
	"created_at"	integer NOT NULL,
	"finished_at"	integer,
	PRIMARY KEY("id")
);
CREATE TABLE IF NOT EXISTS "jobs" (
	"id"	integer NOT NULL,
	"queue"	varchar NOT NULL,
	"payload"	text NOT NULL,
	"attempts"	integer NOT NULL,
	"reserved_at"	integer,
	"available_at"	integer NOT NULL,
	"created_at"	integer NOT NULL,
	PRIMARY KEY("id" AUTOINCREMENT)
);
CREATE TABLE IF NOT EXISTS "measures" (
	"id"	integer NOT NULL,
	"name"	varchar NOT NULL,
	"abbreviation"	varchar,
	"created_at"	datetime,
	"updated_at"	datetime,
	PRIMARY KEY("id" AUTOINCREMENT)
);
CREATE TABLE IF NOT EXISTS "migrations" (
	"id"	integer NOT NULL,
	"migration"	varchar NOT NULL,
	"batch"	integer NOT NULL,
	PRIMARY KEY("id" AUTOINCREMENT)
);
CREATE TABLE IF NOT EXISTS "notifications" (
	"id"	varchar NOT NULL,
	"type"	varchar NOT NULL,
	"notifiable_type"	varchar NOT NULL,
	"notifiable_id"	integer NOT NULL,
	"data"	text NOT NULL,
	"read_at"	datetime,
	"created_at"	datetime,
	"updated_at"	datetime,
	PRIMARY KEY("id")
);
CREATE TABLE IF NOT EXISTS "password_reset_tokens" (
	"email"	varchar NOT NULL,
	"token"	varchar NOT NULL,
	"created_at"	datetime,
	PRIMARY KEY("email")
);
CREATE TABLE IF NOT EXISTS "product_aliases" (
	"id"	integer NOT NULL,
	"product_id"	integer NOT NULL,
	"alias_name"	varchar NOT NULL,
	"supplier_id"	integer,
	"created_at"	datetime,
	"updated_at"	datetime,
	PRIMARY KEY("id" AUTOINCREMENT),
	FOREIGN KEY("product_id") REFERENCES "products"("id") on delete cascade,
	FOREIGN KEY("supplier_id") REFERENCES "suppliers"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "products" (
	"id"	integer NOT NULL,
	"canonical_name"	varchar NOT NULL,
	"description"	text,
	"created_at"	datetime,
	"updated_at"	datetime,
	"normalized_name"	varchar,
	"measure_id"	integer,
	"category_id"	integer,
	PRIMARY KEY("id" AUTOINCREMENT),
	FOREIGN KEY("category_id") REFERENCES "categories"("id") on delete set null,
	FOREIGN KEY("measure_id") REFERENCES "measures"("id") on delete set null on update no action
);
CREATE TABLE IF NOT EXISTS "projects" (
	"id"	integer NOT NULL,
	"name"	varchar NOT NULL,
	"description"	text,
	"client"	varchar,
	"budget"	numeric NOT NULL DEFAULT '0',
	"start_date"	date,
	"end_date"	date,
	"status"	varchar NOT NULL DEFAULT 'activo' CHECK("status" IN ('activo', 'en_pausa', 'completado', 'cancelado')),
	"created_at"	datetime,
	"updated_at"	datetime,
	PRIMARY KEY("id" AUTOINCREMENT)
);
CREATE TABLE IF NOT EXISTS "quick_budget_items" (
	"id"	integer NOT NULL,
	"quick_budget_id"	integer NOT NULL,
	"product_id"	integer,
	"concept"	varchar NOT NULL,
	"measure_id"	integer,
	"quantity"	numeric NOT NULL,
	"unit_price"	numeric NOT NULL,
	"line_total"	numeric NOT NULL,
	"created_at"	datetime,
	"updated_at"	datetime,
	PRIMARY KEY("id" AUTOINCREMENT),
	FOREIGN KEY("measure_id") REFERENCES "measures"("id") on delete set null,
	FOREIGN KEY("product_id") REFERENCES "products"("id") on delete set null,
	FOREIGN KEY("quick_budget_id") REFERENCES "quick_budgets"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "quick_budgets" (
	"id"	integer NOT NULL,
	"title"	varchar NOT NULL,
	"description"	text,
	"client"	varchar,
	"subtotal"	numeric NOT NULL DEFAULT '0',
	"tax_amount"	numeric NOT NULL DEFAULT '0',
	"total"	numeric NOT NULL DEFAULT '0',
	"margin_percent"	numeric NOT NULL DEFAULT '0',
	"grand_total"	numeric NOT NULL DEFAULT '0',
	"status"	varchar NOT NULL DEFAULT 'borrador',
	"created_by"	integer NOT NULL,
	"created_at"	datetime,
	"updated_at"	datetime,
	PRIMARY KEY("id" AUTOINCREMENT),
	FOREIGN KEY("created_by") REFERENCES "users"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "quotations" (
	"id"	integer NOT NULL,
	"requisition_id"	integer,
	"supplier_id"	integer,
	"file_path"	varchar NOT NULL,
	"file_type"	varchar,
	"processed_at"	datetime,
	"created_at"	datetime,
	"updated_at"	datetime,
	"project_id"	integer,
	"original_filename"	varchar,
	"status"	varchar NOT NULL DEFAULT 'pending',
	"raw_text"	text,
	"raw_parsed_data"	text,
	"error_message"	text,
	"uploaded_by"	integer,
	"is_orphan"	tinyint(1) NOT NULL DEFAULT '0',
	PRIMARY KEY("id" AUTOINCREMENT),
	FOREIGN KEY("project_id") REFERENCES "projects"("id") on delete set null,
	FOREIGN KEY("requisition_id") REFERENCES "requisitions"("id") on delete set null on update no action,
	FOREIGN KEY("supplier_id") REFERENCES "suppliers"("id") on delete set null on update no action,
	FOREIGN KEY("uploaded_by") REFERENCES "users"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "requisition_items" (
	"id"	integer NOT NULL,
	"requisition_id"	integer NOT NULL,
	"product_id"	integer,
	"quantity"	numeric NOT NULL DEFAULT ('0'),
	"unit_price"	numeric NOT NULL DEFAULT ('0'),
	"supplier_id"	integer,
	"created_at"	datetime,
	"updated_at"	datetime,
	"unit_price_original"	numeric,
	"tax_amount"	numeric,
	"tax_source"	varchar,
	"line_subtotal"	numeric,
	"line_total"	numeric,
	"measure_id"	integer,
	PRIMARY KEY("id" AUTOINCREMENT),
	FOREIGN KEY("measure_id") REFERENCES "measures"("id") on delete set null,
	FOREIGN KEY("product_id") REFERENCES "products"("id") on delete set null on update no action,
	FOREIGN KEY("requisition_id") REFERENCES "requisitions"("id") on delete cascade on update no action,
	FOREIGN KEY("supplier_id") REFERENCES "suppliers"("id") on delete set null on update no action
);
CREATE TABLE IF NOT EXISTS "requisitions" (
	"id"	integer NOT NULL,
	"project_id"	integer NOT NULL,
	"annotations"	text,
	"status"	varchar NOT NULL DEFAULT ('borrador'),
	"created_by"	integer NOT NULL,
	"approved_by"	integer,
	"rejection_comment"	text,
	"date"	date,
	"created_at"	datetime,
	"updated_at"	datetime,
	"number"	varchar,
	"vendor_id"	integer,
	PRIMARY KEY("id" AUTOINCREMENT),
	FOREIGN KEY("approved_by") REFERENCES "users"("id") on delete set null on update no action,
	FOREIGN KEY("created_by") REFERENCES "users"("id") on delete cascade on update no action,
	FOREIGN KEY("project_id") REFERENCES "projects"("id") on delete cascade on update no action,
	FOREIGN KEY("vendor_id") REFERENCES "vendors"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "roles" (
	"id"	integer NOT NULL,
	"name"	varchar NOT NULL,
	"created_at"	datetime,
	"updated_at"	datetime,
	"permissions"	text,
	PRIMARY KEY("id" AUTOINCREMENT)
);
CREATE TABLE IF NOT EXISTS "sessions" (
	"id"	varchar NOT NULL,
	"user_id"	integer,
	"ip_address"	varchar,
	"user_agent"	text,
	"payload"	text NOT NULL,
	"last_activity"	integer NOT NULL,
	PRIMARY KEY("id")
);
CREATE TABLE IF NOT EXISTS "settings" (
	"id"	integer NOT NULL,
	"key"	varchar NOT NULL,
	"value"	text,
	"group"	varchar NOT NULL DEFAULT 'general',
	"type"	varchar NOT NULL DEFAULT 'string',
	"label"	varchar NOT NULL,
	"description"	text,
	"created_at"	datetime,
	"updated_at"	datetime,
	PRIMARY KEY("id" AUTOINCREMENT)
);
CREATE TABLE IF NOT EXISTS "suppliers" (
	"id"	integer NOT NULL,
	"trade_name"	varchar NOT NULL,
	"legal_name"	varchar,
	"rfc"	varchar,
	"category"	varchar,
	"notes"	text,
	"created_at"	datetime,
	"updated_at"	datetime,
	"normalized_name"	varchar,
	PRIMARY KEY("id" AUTOINCREMENT)
);
CREATE TABLE IF NOT EXISTS "users" (
	"id"	integer NOT NULL,
	"name"	varchar NOT NULL,
	"email"	varchar NOT NULL,
	"email_verified_at"	datetime,
	"password"	varchar NOT NULL,
	"role_id"	integer,
	"active"	tinyint(1) NOT NULL DEFAULT '1',
	"remember_token"	varchar,
	"created_at"	datetime,
	"updated_at"	datetime,
	"avatar"	varchar,
	PRIMARY KEY("id" AUTOINCREMENT),
	FOREIGN KEY("role_id") REFERENCES "roles"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "vendors" (
	"id"	integer NOT NULL,
	"supplier_id"	integer NOT NULL,
	"name"	varchar NOT NULL,
	"phone"	varchar,
	"email"	varchar,
	"created_at"	datetime,
	"updated_at"	datetime,
	PRIMARY KEY("id" AUTOINCREMENT),
	FOREIGN KEY("supplier_id") REFERENCES "suppliers"("id") on delete cascade
);
INSERT INTO "cache" VALUES ('muulsinik-erp-cache-setting_company_name','s:35:"Constructora Muulsinik S.A. de C.V.";',1779090962);
INSERT INTO "cache" VALUES ('muulsinik-erp-cache-setting_company_rfc','s:0:"";',1779090962);
INSERT INTO "cache" VALUES ('muulsinik-erp-cache-setting_company_address','s:0:"";',1779090962);
INSERT INTO "cache" VALUES ('muulsinik-erp-cache-setting_company_phone','s:0:"";',1779090962);
INSERT INTO "cache" VALUES ('muulsinik-erp-cache-setting_company_email','s:0:"";',1779090962);
INSERT INTO "cache" VALUES ('muulsinik-erp-cache-setting_company_logo','s:52:"company/D5xqiQozGPUj7MXqtwRliyraR9tFjYhTmEY5Zs11.png";',1779090962);
INSERT INTO "cache" VALUES ('muulsinik-erp-cache-setting_req_prefix','s:4:"REQ-";',1779090962);
INSERT INTO "cache" VALUES ('muulsinik-erp-cache-setting_req_next_number','d:1;',1779090962);
INSERT INTO "cache" VALUES ('muulsinik-erp-cache-setting_currency_symbol','s:1:"$";',1779090962);
INSERT INTO "cache" VALUES ('muulsinik-erp-cache-setting_currency_position','s:6:"before";',1779090962);
INSERT INTO "cache" VALUES ('muulsinik-erp-cache-setting_decimal_places','d:2;',1779090962);
INSERT INTO "cache" VALUES ('muulsinik-erp-cache-setting_terms_conditions','s:134:"Precios sujetos a cambio sin previo aviso.
Vigencia de cotización: 15 días naturales.
Entrega sujeta a disponibilidad de inventario.";',1779090962);
INSERT INTO "cache" VALUES ('muulsinik-erp-cache-356a192b7913b04c54574d18c28d46e6395428ab:timer','i:1779093881;',1779093881);
INSERT INTO "cache" VALUES ('muulsinik-erp-cache-356a192b7913b04c54574d18c28d46e6395428ab','i:1;',1779093881);
INSERT INTO "cache" VALUES ('muulsinik-erp-cache-extraction:categories','s:152:"Acero / Herrería, Agregados, Cemento / Concreto, Equipo de Seguridad, Herramientas, Madera, Material Eléctrico, Material Hidráulico, Otros, Plomería";',1779097425);
INSERT INTO "cache" VALUES ('muulsinik-erp-cache-extraction:units','s:14:"m, pza, tambor";',1779097425);
INSERT INTO "categories" VALUES (1,'Acero / Herrería','2026-05-14 03:13:20','2026-05-14 03:13:20');
INSERT INTO "categories" VALUES (2,'Agregados','2026-05-14 03:13:20','2026-05-14 03:13:20');
INSERT INTO "categories" VALUES (3,'Cemento / Concreto','2026-05-14 03:13:20','2026-05-14 03:13:20');
INSERT INTO "categories" VALUES (4,'Material Eléctrico','2026-05-14 03:13:20','2026-05-14 03:13:20');
INSERT INTO "categories" VALUES (5,'Herramientas','2026-05-14 03:13:20','2026-05-14 03:13:20');
INSERT INTO "categories" VALUES (6,'Material Hidráulico','2026-05-14 03:13:20','2026-05-14 03:13:20');
INSERT INTO "categories" VALUES (7,'Madera','2026-05-14 03:13:20','2026-05-14 03:13:20');
INSERT INTO "categories" VALUES (9,'Plomería','2026-05-14 03:13:20','2026-05-14 03:13:20');
INSERT INTO "categories" VALUES (10,'Equipo de Seguridad','2026-05-14 03:13:20','2026-05-14 03:13:20');
INSERT INTO "categories" VALUES (11,'Otros','2026-05-14 03:13:20','2026-05-14 03:13:20');
INSERT INTO "measures" VALUES (1,'Pieza','pza','2026-05-14 07:11:04','2026-05-14 07:11:04');
INSERT INTO "measures" VALUES (2,'Metro Lineal','m','2026-05-14 07:11:04','2026-05-14 07:11:04');
INSERT INTO "measures" VALUES (3,'Tambor','tambor','2026-05-14 08:58:50','2026-05-14 08:58:50');
INSERT INTO "migrations" VALUES (1,'0000_01_01_000000_create_roles_table',1);
INSERT INTO "migrations" VALUES (2,'0001_01_01_000000_create_users_table',1);
INSERT INTO "migrations" VALUES (3,'0001_01_01_000001_create_cache_table',1);
INSERT INTO "migrations" VALUES (4,'0001_01_01_000002_create_jobs_table',1);
INSERT INTO "migrations" VALUES (5,'2025_05_12_000000_create_settings_table',1);
INSERT INTO "migrations" VALUES (6,'2026_03_28_000001_create_projects_table',1);
INSERT INTO "migrations" VALUES (7,'2026_03_28_000002_create_expenses_table',1);
INSERT INTO "migrations" VALUES (8,'2026_03_28_000004_create_suppliers_table',1);
INSERT INTO "migrations" VALUES (9,'2026_03_28_000005_create_products_table',1);
INSERT INTO "migrations" VALUES (10,'2026_03_28_000006_create_requisitions_table',1);
INSERT INTO "migrations" VALUES (11,'2026_03_28_000007_create_purchase_orders_table',1);
INSERT INTO "migrations" VALUES (12,'2026_03_28_000008_create_quotations_table',1);
INSERT INTO "migrations" VALUES (13,'2026_03_28_000009_create_audit_logs_table',1);
INSERT INTO "migrations" VALUES (14,'2026_04_13_100000_add_quotation_pipeline_fields',1);
INSERT INTO "migrations" VALUES (15,'2026_04_17_000001_create_products_fts_table',1);
INSERT INTO "migrations" VALUES (16,'2026_04_25_194502_alter_requisitions_table_update_description_and_add_number',1);
INSERT INTO "migrations" VALUES (17,'2026_04_29_190104_add_tax_fields_to_requisition_items',1);
INSERT INTO "migrations" VALUES (18,'2026_04_29_231404_remove_homologation_status_from_requisition_items',1);
INSERT INTO "migrations" VALUES (19,'2026_05_02_171600_drop_unused_tables',1);
INSERT INTO "migrations" VALUES (20,'2026_05_02_172000_drop_unused_columns',1);
INSERT INTO "migrations" VALUES (21,'2026_05_02_180000_add_line_totals_to_requisition_items',1);
INSERT INTO "migrations" VALUES (22,'2026_05_02_235528_create_measures_table',1);
INSERT INTO "migrations" VALUES (23,'2026_05_05_045620_add_normalized_name_to_products_and_suppliers',1);
INSERT INTO "migrations" VALUES (24,'2026_05_06_081617_normalize_database_schema',1);
INSERT INTO "migrations" VALUES (25,'2026_05_06_184833_add_vendor_id_to_requisitions_table',1);
INSERT INTO "migrations" VALUES (26,'2026_05_06_212821_create_categories_table',1);
INSERT INTO "migrations" VALUES (27,'2026_05_08_000001_add_index_to_measures_abbreviation',1);
INSERT INTO "migrations" VALUES (28,'2026_05_08_180000_restore_permissions_to_roles',1);
INSERT INTO "migrations" VALUES (29,'2026_05_14_000001_create_notifications_table',2);
INSERT INTO "migrations" VALUES (30,'2026_05_18_032419_add_avatar_to_users_table',3);
INSERT INTO "migrations" VALUES (31,'2026_05_18_042636_create_expense_allocations_table',4);
INSERT INTO "migrations" VALUES (32,'2026_05_18_070251_create_quick_budgets_table',5);
INSERT INTO "products" VALUES (1,'REMATE VENTILA SANIT 50',NULL,'2026-05-14 07:11:04','2026-05-14 07:11:04','remate ventila sanit 50',1,6);
INSERT INTO "products" VALUES (2,'TUBO SAN NORMA 110',NULL,'2026-05-14 07:11:04','2026-05-14 07:11:04','tubo san norma 110',2,6);
INSERT INTO "products" VALUES (3,'PEGAMENTO PVC 4 OZ 118 ML TRANSPAREN OATEY',NULL,'2026-05-14 07:11:04','2026-05-14 07:11:04','pegamento pvc 4 oz 118 ml transparen oatey',1,9);
INSERT INTO "products" VALUES (4,'CUELLO DE CERA C/GUIA EN CAJA',NULL,'2026-05-14 07:11:04','2026-05-14 07:11:04','cuello de cera c/guia en caja',1,9);
INSERT INTO "products" VALUES (5,'LLAVE D/CONTROL P/GAS 1/2PULGX3/8PULG',NULL,'2026-05-14 07:11:04','2026-05-14 07:11:04','llave d/control p/gas 1/2pulgx3/8pulg',1,9);
INSERT INTO "products" VALUES (6,'ADAPTADOR MACHO GAS 1/2PULG X 1/2PULG RM',NULL,'2026-05-14 07:11:04','2026-05-14 07:11:04','adaptador macho gas 1/2pulg x 1/2pulg rm',1,9);
INSERT INTO "products" VALUES (7,'TEE GAS 1/2PULG X 1/2PULG X 1/2PULG',NULL,'2026-05-14 07:11:04','2026-05-14 07:11:04','tee gas 1/2pulg x 1/2pulg x 1/2pulg',1,9);
INSERT INTO "products" VALUES (8,'CODO 90° GAS 1/2PULG X 1/2PULG',NULL,'2026-05-14 07:11:04','2026-05-14 07:11:04','codo 90 gas 1/2pulg x 1/2pulg',1,9);
INSERT INTO "products" VALUES (9,'TUBO PEALPE GAS 1/2PULG X 100M',NULL,'2026-05-14 07:11:04','2026-05-14 07:11:04','tubo pealpe gas 1/2pulg x 100m',2,9);
INSERT INTO "products" VALUES (11,'PIJA COMBINADA 10MM GROSOR 1 LARG',NULL,'2026-05-14 07:11:04','2026-05-14 07:11:04','pija combinada 10mm grosor 1 larg',1,11);
INSERT INTO "products" VALUES (12,'TAQUETE DE PLASTICO 006',NULL,'2026-05-14 07:11:04','2026-05-14 07:11:04','taquete de plastico 006',1,11);
INSERT INTO "products" VALUES (13,'DESMOLDANTE PRIME GLASST',NULL,'2026-05-14 08:58:50','2026-05-14 08:58:50','desmoldante prime glasst',3,11);
INSERT INTO "products" VALUES (14,'ABRAZADERA UÑA GALV PARA CPVC/COBRE',NULL,'2026-05-14 09:10:21','2026-05-14 09:10:21','abrazadera una galv para cpvc/cobre',1,9);
INSERT INTO "projects" VALUES (1,'Centrovisión','','Hazel Hoil',1000000,'2026-05-01 00:00:00','2026-07-29 00:00:00','activo','2026-05-14 07:09:39','2026-05-14 07:09:39');
INSERT INTO "projects" VALUES (3,'Coral','','Hazel',100000,'2026-05-18 00:00:00','2026-06-18 00:00:00','activo','2026-05-19 00:50:06','2026-05-19 00:50:06');
INSERT INTO "quick_budget_items" VALUES (1,1,4,'CUELLO DE CERA C/GUIA EN CAJA',1,1.05,24.46,25.68,'2026-05-18 07:36:59','2026-05-18 07:36:59');
INSERT INTO "quick_budgets" VALUES (1,'Colado de losa','','Hazel',25.68,0,25.68,10,28.25,'borrador',1,'2026-05-18 07:36:59','2026-05-18 07:36:59');
INSERT INTO "quotations" VALUES (1,NULL,NULL,'quotations/pvkvQOrfAdr0OhlbrQ8qtL1ndrVGymVO6UzlPVyi.jpg','','2026-05-14 07:08:41','2026-05-14 07:08:12','2026-05-14 07:08:41',NULL,'WhatsApp Image 2026-04-17 at 4.29.57 PM.jpeg','completed','[Extraído directamente por Gemini Vision]','{"supplier":"Boxito","store":"M\u00e9rida","seller":"Leticia Alejandra Dzul Uh","tax_info":{"tax_rate":0.16,"prices_include_tax":false,"tax_detected":true,"subtotal":22400.73,"tax_total":3584.11,"grand_total":25984.84},"items":[{"name":"REMATE VENTILA SANIT 50","category":"Plomer\u00eda","quantity":70,"unit":"pza","unit_name":"Pieza","unit_price":20.28,"discount":0,"tax_amount":227.14,"price_includes_tax":false,"line_subtotal":1419.6,"line_total":1646.74},{"name":"TUBO SAN NORMA 110","category":"Material Hidr\u00e1ulico","quantity":42,"unit":"m","unit_name":"Metro lineal","unit_price":39.96,"discount":0,"tax_amount":268.53,"price_includes_tax":false,"line_subtotal":1678.32,"line_total":1946.85},{"name":"PEGAMENTO PVC (4 OZ) 118 ML TRANSPAREN OATEY","category":"Plomer\u00eda","quantity":9,"unit":"pza","unit_name":"Pieza","unit_price":52.87,"discount":0,"tax_amount":76.13,"price_includes_tax":false,"line_subtotal":475.83,"line_total":551.96},{"name":"CUELLO DE CERA C\/GUIA EN CAJA PB-104","category":"Plomer\u00eda","quantity":18,"unit":"pza","unit_name":"Pieza","unit_price":24.46,"discount":0,"tax_amount":70.44,"price_includes_tax":false,"line_subtotal":440.28,"line_total":510.72},{"name":"LLAVE D\/CONTROL P\/GAS 1\/2PULGX3\/8PULG","category":"Material Hidr\u00e1ulico","quantity":60,"unit":"pza","unit_name":"Pieza","unit_price":61.09,"discount":0,"tax_amount":586.46,"price_includes_tax":false,"line_subtotal":3665.4,"line_total":4251.86},{"name":"ADAPTADOR MACHO GAS 1\/2PULG X 1\/2PULG RM","category":"Material Hidr\u00e1ulico","quantity":60,"unit":"pza","unit_name":"Pieza","unit_price":40.14,"discount":0,"tax_amount":385.34,"price_includes_tax":false,"line_subtotal":2408.4,"line_total":2793.74},{"name":"TEE GAS 1\/2PULG X 1\/2PULG X 1\/2PULG","category":"Material Hidr\u00e1ulico","quantity":30,"unit":"pza","unit_name":"Pieza","unit_price":89.42,"discount":0,"tax_amount":429.22,"price_includes_tax":false,"line_subtotal":2682.6,"line_total":3111.82},{"name":"CODO 90\u00b0 GAS 1\/2PULG X 1\/2PULG","category":"Material Hidr\u00e1ulico","quantity":105,"unit":"pza","unit_name":"Pieza","unit_price":62.56,"discount":0,"tax_amount":1051.01,"price_includes_tax":false,"line_subtotal":6568.8,"line_total":7619.81},{"name":"TUBO PEALPE GAS 1\/2PULG X 100M","category":"Material Hidr\u00e1ulico","quantity":105,"unit":"m","unit_name":"Metro lineal","unit_price":25.62,"discount":0,"tax_amount":430.42,"price_includes_tax":false,"line_subtotal":2690.1,"line_total":3120.52},{"name":"ABRAZADERA U\u00d1A GALV 200 013 UCR12 PARA CPVC\/COBRE","category":"Plomer\u00eda","quantity":120,"unit":"pza","unit_name":"Pieza","unit_price":1.45,"discount":0,"tax_amount":27.84,"price_includes_tax":false,"line_subtotal":174,"line_total":201.84},{"name":"PIJA COMBINADA 10MM GROSOR 1 LARG","category":"Otros","quantity":420,"unit":"pza","unit_name":"Pieza","unit_price":0.26,"discount":0,"tax_amount":17.47,"price_includes_tax":false,"line_subtotal":109.2,"line_total":126.67},{"name":"TAQUETE DE PLASTICO 006 (TP14)","category":"Otros","quantity":420,"unit":"pza","unit_name":"Pieza","unit_price":0.21,"discount":0,"tax_amount":14.11,"price_includes_tax":false,"line_subtotal":88.2,"line_total":102.31}],"raw_text":"[Extra\u00eddo directamente por Gemini Vision]"}',NULL,1,0);
INSERT INTO "quotations" VALUES (2,NULL,1,'quotations/uZQ7uGuap8kQeOzMtRSuJulRKYkzLaM4oYIQ1XXn.jpg','','2026-05-14 07:10:41','2026-05-14 07:10:19','2026-05-14 07:11:04',NULL,'WhatsApp Image 2026-04-17 at 4.29.57 PM.jpeg','completed','[Extraído directamente por Gemini Vision]','{"supplier":"Boxito","store":"M\u00e9rida Yuc","seller":"Leticia Alejandra Dzul Uh","tax_info":{"tax_rate":0.16,"prices_include_tax":false,"tax_detected":true,"subtotal":22400.73,"tax_total":3584.11,"grand_total":25984.84},"items":[{"name":"REMATE VENTILA SANIT 50","category":"Material Hidr\u00e1ulico","quantity":70,"unit":"pz","unit_name":"Pieza","unit_price":20.28,"discount":0,"tax_amount":227.14,"price_includes_tax":false,"line_subtotal":1419.6,"line_total":1646.74},{"name":"TUBO SAN NORMA 110","category":"Material Hidr\u00e1ulico","quantity":42,"unit":"ml","unit_name":"Metro Lineal","unit_price":39.96,"discount":0,"tax_amount":268.53,"price_includes_tax":false,"line_subtotal":1678.32,"line_total":1946.85},{"name":"PEGAMENTO PVC (4 OZ) 118 ML TRANSPAREN OATEY","category":"Plomer\u00eda","quantity":9,"unit":"pz","unit_name":"Pieza","unit_price":52.87,"discount":0,"tax_amount":76.13,"price_includes_tax":false,"line_subtotal":475.83,"line_total":551.96},{"name":"CUELLO DE CERA C\/GUIA EN CAJA","category":"Plomer\u00eda","quantity":18,"unit":"pz","unit_name":"Pieza","unit_price":24.46,"discount":0,"tax_amount":70.44,"price_includes_tax":false,"line_subtotal":440.28,"line_total":510.72},{"name":"LLAVE D\/CONTROL P\/GAS 1\/2PULGX3\/8PULG","category":"Plomer\u00eda","quantity":60,"unit":"pz","unit_name":"Pieza","unit_price":61.09,"discount":0,"tax_amount":586.46,"price_includes_tax":false,"line_subtotal":3665.4,"line_total":4251.86},{"name":"ADAPTADOR MACHO GAS 1\/2PULG X 1\/2PULG RM","category":"Plomer\u00eda","quantity":60,"unit":"pz","unit_name":"Pieza","unit_price":40.14,"discount":0,"tax_amount":385.34,"price_includes_tax":false,"line_subtotal":2408.4,"line_total":2793.74},{"name":"TEE GAS 1\/2PULG X 1\/2PULG X 1\/2PULG","category":"Plomer\u00eda","quantity":30,"unit":"pz","unit_name":"Pieza","unit_price":89.42,"discount":0,"tax_amount":429.22,"price_includes_tax":false,"line_subtotal":2682.6,"line_total":3111.82},{"name":"CODO 90\u00b0 GAS 1\/2PULG X 1\/2PULG","category":"Plomer\u00eda","quantity":105,"unit":"pz","unit_name":"Pieza","unit_price":62.56,"discount":0,"tax_amount":1051.01,"price_includes_tax":false,"line_subtotal":6568.8,"line_total":7619.81},{"name":"TUBO PEALPE GAS 1\/2PULG X 100M","category":"Plomer\u00eda","quantity":105,"unit":"ml","unit_name":"Metro Lineal","unit_price":25.62,"discount":0,"tax_amount":430.42,"price_includes_tax":false,"line_subtotal":2690.1,"line_total":3120.52},{"name":"ABRAZADERA U\u00d1A GALV 200 013 PARA CPVC\/COBRE","category":"Plomer\u00eda","quantity":120,"unit":"pz","unit_name":"Pieza","unit_price":1.45,"discount":0,"tax_amount":27.84,"price_includes_tax":false,"line_subtotal":174,"line_total":201.84},{"name":"PIJA COMBINADA 10MM GROSOR 1 LARG","category":"Otros","quantity":420,"unit":"pz","unit_name":"Pieza","unit_price":0.26,"discount":0,"tax_amount":17.47,"price_includes_tax":false,"line_subtotal":109.2,"line_total":126.67},{"name":"TAQUETE DE PLASTICO 006 (TP14)","category":"Otros","quantity":420,"unit":"pz","unit_name":"Pieza","unit_price":0.21,"discount":0,"tax_amount":14.11,"price_includes_tax":false,"line_subtotal":88.2,"line_total":102.31}],"raw_text":"[Extra\u00eddo directamente por Gemini Vision]"}',NULL,1,0);
INSERT INTO "quotations" VALUES (3,NULL,NULL,'quotations/wA6z0PDneMZvJefAkHmRyiX2ayEbbY1he1Tuc7jY.jpg','','2026-05-14 08:11:45','2026-05-14 08:11:33','2026-05-14 08:11:45',NULL,'WhatsApp Image 2026-04-17 at 4.29.31 PM.jpeg','completed','[Extraído directamente por Gemini Vision]','{"supplier":"G.e.s Aceros Y Mas","store":null,"seller":null,"tax_info":{"tax_rate":0.16,"prices_include_tax":false,"tax_detected":true,"subtotal":17942.85,"tax_total":2870.86,"grand_total":20813.71},"items":[{"name":"VARILLA # 3, DE 3\/8\" (12 MTS.)","category":"Acero \/ Herrer\u00eda","quantity":145,"unit":"pza","unit_name":"Pieza","unit_price":121.33,"discount":0,"tax_amount":null,"price_includes_tax":false,"line_subtotal":17592.85,"line_total":null},{"name":"SERVICIO DE ENTREGA DENTRO DE LA CD. DE MERIDA","category":"Otros","quantity":1,"unit":"pza","unit_name":"Pieza","unit_price":350,"discount":0,"tax_amount":null,"price_includes_tax":false,"line_subtotal":350,"line_total":null}],"raw_text":"[Extra\u00eddo directamente por Gemini Vision]"}',NULL,1,0);
INSERT INTO "quotations" VALUES (4,NULL,NULL,'quotations/DvP2hPz3qSyY0R7cae90e2UOcXdwAT0567VMsOoO.jpg','','2026-05-14 08:41:29','2026-05-14 08:41:23','2026-05-14 08:41:29',NULL,'WhatsApp Image 2026-04-17 at 4.29.31 PM.jpeg','completed','[No se pudo extraer texto del archivo]','{"supplier":null,"store":null,"seller":null,"tax_info":null,"items":[],"raw_text":"[No se pudo extraer texto del archivo]"}',NULL,1,0);
INSERT INTO "quotations" VALUES (5,NULL,NULL,'quotations/bfGRVBB5Hv36UMytYYkonchRqx8HEciH34aXQGzY.jpg','','2026-05-14 08:42:39','2026-05-14 08:42:22','2026-05-14 08:42:39',NULL,'WhatsApp Image 2026-04-17 at 4.29.31 PM.jpeg','completed','[Extraído directamente por Gemini Vision]','{"supplier":"G.e.s. Aceros Y Mas","store":null,"seller":null,"tax_info":{"tax_rate":0.16,"prices_include_tax":false,"tax_detected":true,"subtotal":17942.85,"tax_total":2870.86,"grand_total":20813.71},"items":[{"name":"VARILLA # 3, DE 3\/8\" (12 MTS.)","category":"Acero \/ Herrer\u00eda","quantity":145,"unit":"pza","unit_name":"Pieza","unit_price":121.33,"discount":0,"tax_amount":null,"price_includes_tax":false,"line_subtotal":17592.85,"line_total":null},{"name":"SERVICIO DE ENTREGA DENTRO DE LA CD. DE MERIDA","category":"Otros","quantity":1,"unit":"pza","unit_name":"Servicio","unit_price":350,"discount":0,"tax_amount":null,"price_includes_tax":false,"line_subtotal":350,"line_total":null}],"raw_text":"[Extra\u00eddo directamente por Gemini Vision]"}',NULL,1,0);
INSERT INTO "quotations" VALUES (6,NULL,NULL,'quotations/jw32Stzxxn52wcEs6n5iZZhTFiSeFzlO7a1qhCcG.jpg','','2026-05-14 08:52:26','2026-05-14 08:52:10','2026-05-14 08:52:26',NULL,'WhatsApp Image 2026-04-17 at 3.56.44 PM.jpeg','completed','[Extraído directamente por Gemini Vision]','{"supplier":"Productos Pennsylvania","store":"Merida","seller":"Jose Armando Canul","tax_info":{"tax_rate":0.16,"prices_include_tax":false,"tax_detected":true,"subtotal":7907.57,"tax_total":1265.21,"grand_total":9172.78},"items":[{"name":"DESMOLDANTE PRIME GLASST","category":"Otros","quantity":1,"unit":"tambor","unit_name":"Tambor","unit_price":7907.57,"discount":0,"tax_amount":1265.21,"price_includes_tax":false,"line_subtotal":7907.57,"line_total":9172.78}],"raw_text":"[Extra\u00eddo directamente por Gemini Vision]"}',NULL,1,0);
INSERT INTO "quotations" VALUES (7,2,2,'quotations/IWgKYvuyHq7FVzTRnOexUgnqyQ2LzFAYxXvTpu12.jpg','','2026-05-14 08:57:03','2026-05-14 08:56:46','2026-05-14 08:58:50',NULL,'WhatsApp Image 2026-04-17 at 3.56.44 PM.jpeg','completed','[Extraído directamente por Gemini Vision]','{"supplier":"Productos Pennsylvania","store":null,"seller":"Jose Armando Can\u00fal","tax_info":{"tax_rate":0.16,"prices_include_tax":false,"tax_detected":true,"subtotal":7907.57,"tax_total":1265.21,"grand_total":9172.78},"items":[{"name":"DESMOLDANTE PRIME GLASST","category":"Otros","quantity":1,"unit":"tambor","unit_name":"Tambor","unit_price":7907.57,"discount":0,"tax_amount":1265.21,"price_includes_tax":false,"line_subtotal":7907.57,"line_total":9172.78}],"raw_text":"[Extra\u00eddo directamente por Gemini Vision]"}',NULL,1,0);
INSERT INTO "quotations" VALUES (8,3,1,'quotations/14o2Fqbx0jHiA7S45uCeZyJn0qbRZNHr48TlLsug.jpg','','2026-05-14 09:08:31','2026-05-14 09:07:56','2026-05-14 09:10:21',NULL,'WhatsApp Image 2026-04-17 at 4.29.57 PM.jpeg','completed','[Extraído directamente por Gemini Vision]','{"supplier":"Boxito","store":"Boxito Merida Sambula","seller":"Leticia Alejandra Dzul Uh","tax_info":{"tax_rate":0.16,"prices_include_tax":false,"tax_detected":true,"subtotal":22400.73,"tax_total":3584.11,"grand_total":25984.84},"items":[{"name":"REMATE VENTILA SANIT 50","category":"Plomer\u00eda","quantity":70,"unit":"pza","unit_name":"Pieza","unit_price":20.28,"discount":0,"tax_amount":227.14,"price_includes_tax":false,"line_subtotal":1419.6,"line_total":1646.74},{"name":"TUBO SAN NORMA 110","category":"Material Hidr\u00e1ulico","quantity":42,"unit":"m","unit_name":"Metro Lineal","unit_price":39.96,"discount":0,"tax_amount":268.53,"price_includes_tax":false,"line_subtotal":1678.32,"line_total":1946.85},{"name":"PEGAMENTO PVC (4 OZ) 118 ML TRANSPAREN OATEY","category":"Plomer\u00eda","quantity":9,"unit":"pza","unit_name":"Pieza","unit_price":52.87,"discount":0,"tax_amount":76.13,"price_includes_tax":false,"line_subtotal":475.83,"line_total":551.96},{"name":"CUELLO DE CERA C\/GUIA EN CAJA PB-104","category":"Plomer\u00eda","quantity":18,"unit":"pza","unit_name":"Pieza","unit_price":24.46,"discount":0,"tax_amount":70.44,"price_includes_tax":false,"line_subtotal":440.28,"line_total":510.72},{"name":"LLAVE D\/CONTROL P\/GAS 1\/2PULGX3\/8PULG","category":"Plomer\u00eda","quantity":60,"unit":"pza","unit_name":"Pieza","unit_price":61.09,"discount":0,"tax_amount":586.46,"price_includes_tax":false,"line_subtotal":3665.4,"line_total":4251.86},{"name":"ADAPTADOR MACHO GAS 1\/2PULG X 1\/2PULG RM","category":"Plomer\u00eda","quantity":60,"unit":"pza","unit_name":"Pieza","unit_price":40.14,"discount":0,"tax_amount":385.34,"price_includes_tax":false,"line_subtotal":2408.4,"line_total":2793.74},{"name":"TEE GAS 1\/2PULG X 1\/2PULG X 1\/2PULG","category":"Plomer\u00eda","quantity":30,"unit":"pza","unit_name":"Pieza","unit_price":89.42,"discount":0,"tax_amount":429.22,"price_includes_tax":false,"line_subtotal":2682.6,"line_total":3111.82},{"name":"CODO 90* GAS 1\/2PULG X 1\/2PULG","category":"Plomer\u00eda","quantity":105,"unit":"pza","unit_name":"Pieza","unit_price":62.56,"discount":0,"tax_amount":1051.01,"price_includes_tax":false,"line_subtotal":6568.8,"line_total":7619.81},{"name":"TUBO PEALPE GAS 1\/2PULG X 100M","category":"Material Hidr\u00e1ulico","quantity":105,"unit":"m","unit_name":"Metro Lineal","unit_price":25.62,"discount":0,"tax_amount":430.42,"price_includes_tax":false,"line_subtotal":2690.1,"line_total":3120.52},{"name":"ABRAZADERA U\u00d1A GALV PARA CPVC\/COBRE","category":"Plomer\u00eda","quantity":120,"unit":"pza","unit_name":"Pieza","unit_price":1.45,"discount":0,"tax_amount":27.84,"price_includes_tax":false,"line_subtotal":174,"line_total":201.84},{"name":"PIJA COMBINADA 10MM GROSOR 1 LARG","category":"Herramientas","quantity":420,"unit":"pza","unit_name":"Pieza","unit_price":0.26,"discount":0,"tax_amount":17.47,"price_includes_tax":false,"line_subtotal":109.2,"line_total":126.67},{"name":"TAQUETE DE PLASTICO 006 (TP14)","category":"Herramientas","quantity":420,"unit":"pza","unit_name":"Pieza","unit_price":0.21,"discount":0,"tax_amount":14.11,"price_includes_tax":false,"line_subtotal":88.2,"line_total":102.31}],"raw_text":"[Extra\u00eddo directamente por Gemini Vision]"}',NULL,1,0);
INSERT INTO "quotations" VALUES (9,NULL,NULL,'quotations/ATs4k7VeZEmukWaSFQT6S7oGmQAtW4qbazE3z07h.jpg','','2026-05-18 00:30:59','2026-05-18 00:30:33','2026-05-18 00:30:59',NULL,'WhatsApp Image 2026-04-17 at 4.29.57 PM.jpeg','completed','[Extraído directamente por Gemini Vision]','{"supplier":"Boxito","store":"M\u00e9rida Anueva Sambula","seller":"Leticia Alejandra Dzul Uh","tax_info":{"tax_rate":0.16,"prices_include_tax":false,"tax_detected":true,"subtotal":22400.73,"tax_total":3584.11,"grand_total":25984.84},"items":[{"name":"REMATE VENTILA SANIT 50","category":"Plomer\u00eda","quantity":70,"unit":"pza","unit_name":"Pieza","unit_price":20.28,"discount":0,"tax_amount":227.14,"price_includes_tax":false,"line_subtotal":1419.6,"line_total":1646.74},{"name":"TUBO SAN NORMA 110","category":"Plomer\u00eda","quantity":42,"unit":"m","unit_name":"Metro Lineal","unit_price":39.96,"discount":0,"tax_amount":268.53,"price_includes_tax":false,"line_subtotal":1678.32,"line_total":1946.85},{"name":"PEGAMENTO PVC (4 OZ) 118 ML TRANSPAREN OATEY","category":"Plomer\u00eda","quantity":9,"unit":"pza","unit_name":"Pieza","unit_price":52.87,"discount":0,"tax_amount":76.13,"price_includes_tax":false,"line_subtotal":475.83,"line_total":551.96},{"name":"CUELLO DE CERA C\/GUIA EN CAJA","category":"Plomer\u00eda","quantity":18,"unit":"pza","unit_name":"Pieza","unit_price":24.46,"discount":0,"tax_amount":70.44,"price_includes_tax":false,"line_subtotal":440.28,"line_total":510.72},{"name":"LLAVE D\/CONTROL P\/GAS 1\/2PULGX3\/8PULG","category":"Plomer\u00eda","quantity":60,"unit":"pza","unit_name":"Pieza","unit_price":61.09,"discount":0,"tax_amount":586.46,"price_includes_tax":false,"line_subtotal":3665.4,"line_total":4251.86},{"name":"ADAPTADOR MACHO GAS 1\/2PULG X 1\/2PULG RM","category":"Plomer\u00eda","quantity":60,"unit":"pza","unit_name":"Pieza","unit_price":40.14,"discount":0,"tax_amount":385.34,"price_includes_tax":false,"line_subtotal":2408.4,"line_total":2793.74},{"name":"TEE GAS 1\/2PULG X 1\/2PULG X 1\/2PULG","category":"Plomer\u00eda","quantity":30,"unit":"pza","unit_name":"Pieza","unit_price":89.42,"discount":0,"tax_amount":429.22,"price_includes_tax":false,"line_subtotal":2682.6,"line_total":3111.82},{"name":"CODO 90\u00b0 GAS 1\/2PULG X 1\/2PULG","category":"Plomer\u00eda","quantity":105,"unit":"pza","unit_name":"Pieza","unit_price":62.56,"discount":0,"tax_amount":1051.01,"price_includes_tax":false,"line_subtotal":6568.8,"line_total":7619.81},{"name":"TUBO PEALPE GAS 1\/2PULG X 100M","category":"Plomer\u00eda","quantity":105,"unit":"m","unit_name":"Metro Lineal","unit_price":25.62,"discount":0,"tax_amount":430.42,"price_includes_tax":false,"line_subtotal":2690.1,"line_total":3120.52},{"name":"ABRAZADERA U\u00d1A GALV PARA CPVC\/COBRE","category":"Plomer\u00eda","quantity":120,"unit":"pza","unit_name":"Pieza","unit_price":1.45,"discount":0,"tax_amount":27.84,"price_includes_tax":false,"line_subtotal":174,"line_total":201.84},{"name":"PIJA COMBINADA 10MM GROSOR 1 LARG","category":"Otros","quantity":420,"unit":"pza","unit_name":"Pieza","unit_price":0.26,"discount":0,"tax_amount":17.47,"price_includes_tax":false,"line_subtotal":109.2,"line_total":126.67},{"name":"TAQUETE DE PLASTICO","category":"Otros","quantity":420,"unit":"pza","unit_name":"Pieza","unit_price":0.21,"discount":0,"tax_amount":14.11,"price_includes_tax":false,"line_subtotal":88.2,"line_total":102.31}],"raw_text":"[Extra\u00eddo directamente por Gemini Vision]"}',NULL,1,0);
INSERT INTO "quotations" VALUES (10,NULL,1,'quotations/gqCPqYQuAm8VrB4hg2z49O3kATq0HTT0HuuEfOW7.jpg','','2026-05-18 07:29:14','2026-05-18 07:28:46','2026-05-18 07:30:28',NULL,'WhatsApp Image 2026-04-17 at 4.29.57 PM.jpeg','completed','[Extraído directamente por Gemini Vision]','{"supplier":"Boxito","store":"Boxito","seller":"Leticia Alejandra Dzul Uh","tax_info":{"tax_rate":0.16,"prices_include_tax":false,"tax_detected":true,"subtotal":22400.73,"tax_total":3584.11,"grand_total":25984.84},"items":[{"name":"REMATE VENTILA SANIT 50","category":"Material Hidr\u00e1ulico","quantity":70,"unit":"pza","unit_name":"Pieza","unit_price":20.28,"discount":0,"tax_amount":227.14,"price_includes_tax":false,"line_subtotal":1419.6,"line_total":1646.74},{"name":"TUBO SAN NORMA 110","category":"Material Hidr\u00e1ulico","quantity":42,"unit":"m","unit_name":"Metro Lineal","unit_price":39.96,"discount":0,"tax_amount":268.53,"price_includes_tax":false,"line_subtotal":1678.32,"line_total":1946.85},{"name":"PEGAMENTO PVC (4 OZ) 118 ML TRANSPAREN OATEY","category":"Plomer\u00eda","quantity":9,"unit":"pza","unit_name":"Pieza","unit_price":52.87,"discount":0,"tax_amount":76.13,"price_includes_tax":false,"line_subtotal":475.83,"line_total":551.96},{"name":"CUELLO DE CERA C\/GUIA EN CAJA","category":"Plomer\u00eda","quantity":18,"unit":"pza","unit_name":"Pieza","unit_price":24.46,"discount":0,"tax_amount":70.44,"price_includes_tax":false,"line_subtotal":440.28,"line_total":510.72},{"name":"LLAVE D\/CONTROL P\/GAS 1\/2PULGX3\/8PULG","category":"Material Hidr\u00e1ulico","quantity":60,"unit":"pza","unit_name":"Pieza","unit_price":61.09,"discount":0,"tax_amount":586.46,"price_includes_tax":false,"line_subtotal":3665.4,"line_total":4251.86},{"name":"ADAPTADOR MACHO GAS 1\/2PULG X 1\/2PULG RM","category":"Material Hidr\u00e1ulico","quantity":60,"unit":"pza","unit_name":"Pieza","unit_price":40.14,"discount":0,"tax_amount":385.34,"price_includes_tax":false,"line_subtotal":2408.4,"line_total":2793.74},{"name":"TEE GAS 1\/2PULG X 1\/2PULG X 1\/2PULG","category":"Material Hidr\u00e1ulico","quantity":30,"unit":"pza","unit_name":"Pieza","unit_price":89.42,"discount":0,"tax_amount":429.22,"price_includes_tax":false,"line_subtotal":2682.6,"line_total":3111.82},{"name":"CODO 90\u00b0 GAS 1\/2PULG X 1\/2PULG","category":"Material Hidr\u00e1ulico","quantity":105,"unit":"pza","unit_name":"Pieza","unit_price":62.56,"discount":0,"tax_amount":1051.01,"price_includes_tax":false,"line_subtotal":6568.8,"line_total":7619.81},{"name":"TUBO PEALPE GAS 1\/2PULG X 100M","category":"Material Hidr\u00e1ulico","quantity":105,"unit":"m","unit_name":"Metro Lineal","unit_price":25.62,"discount":0,"tax_amount":430.42,"price_includes_tax":false,"line_subtotal":2690.1,"line_total":3120.52},{"name":"ABRAZADERA U\u00d1A GALV PARA CPVC\/COBRE","category":"Otros","quantity":120,"unit":"pza","unit_name":"Pieza","unit_price":1.45,"discount":0,"tax_amount":27.84,"price_includes_tax":false,"line_subtotal":174,"line_total":201.84},{"name":"PIJA COMBINADA 10MM GROSOR 1 LARG","category":"Otros","quantity":420,"unit":"pza","unit_name":"Pieza","unit_price":0.26,"discount":0,"tax_amount":17.47,"price_includes_tax":false,"line_subtotal":109.2,"line_total":126.67},{"name":"TAQUETE DE PLASTICO","category":"Otros","quantity":420,"unit":"pza","unit_name":"Pieza","unit_price":0.21,"discount":0,"tax_amount":14.11,"price_includes_tax":false,"line_subtotal":88.2,"line_total":102.31}],"raw_text":"[Extra\u00eddo directamente por Gemini Vision]"}',NULL,1,0);
INSERT INTO "quotations" VALUES (11,NULL,NULL,'quotations/KMOPie6DGF3O7nBZMBNFoVvv2sCdQ0fYjZbSXqnl.jpg','','2026-05-18 08:44:09','2026-05-18 08:43:43','2026-05-18 08:44:09',NULL,'WhatsApp Image 2026-04-17 at 4.29.31 PM.jpeg','completed','[No se pudo extraer texto del archivo]','{"supplier":null,"store":null,"seller":null,"tax_info":null,"items":[],"raw_text":"[No se pudo extraer texto del archivo]"}',NULL,1,0);
INSERT INTO "requisition_items" VALUES (13,2,13,1,7907.57,2,'2026-05-14 08:58:50','2026-05-14 08:58:50',7907.57,1265.21,'supplier_per_item',7907.57,9172.78,3);
INSERT INTO "requisition_items" VALUES (14,3,1,70,20.28,1,'2026-05-14 09:10:21','2026-05-14 09:10:21',20.28,227.14,'supplier_per_item',1419.6,1646.74,1);
INSERT INTO "requisition_items" VALUES (15,3,2,42,39.96,1,'2026-05-14 09:10:21','2026-05-14 09:10:21',39.96,268.53,'supplier_per_item',1678.32,1946.85,2);
INSERT INTO "requisition_items" VALUES (16,3,3,9,52.87,1,'2026-05-14 09:10:21','2026-05-14 09:10:21',52.87,76.13,'supplier_per_item',475.83,551.96,1);
INSERT INTO "requisition_items" VALUES (17,3,4,18,24.46,1,'2026-05-14 09:10:21','2026-05-14 09:10:21',24.46,70.44,'supplier_per_item',440.28,510.72,1);
INSERT INTO "requisition_items" VALUES (18,3,5,60,61.09,1,'2026-05-14 09:10:21','2026-05-14 09:10:21',61.09,586.46,'supplier_per_item',3665.4,4251.86,1);
INSERT INTO "requisition_items" VALUES (19,3,6,60,40.14,1,'2026-05-14 09:10:21','2026-05-14 09:10:21',40.14,385.34,'supplier_per_item',2408.4,2793.74,1);
INSERT INTO "requisition_items" VALUES (20,3,7,30,89.42,1,'2026-05-14 09:10:21','2026-05-14 09:10:21',89.42,429.22,'supplier_per_item',2682.6,3111.82,1);
INSERT INTO "requisition_items" VALUES (21,3,8,105,62.56,1,'2026-05-14 09:10:21','2026-05-14 09:10:21',62.56,1051.01,'supplier_per_item',6568.8,7619.81,1);
INSERT INTO "requisition_items" VALUES (22,3,9,105,25.62,1,'2026-05-14 09:10:21','2026-05-14 09:10:21',25.62,430.42,'supplier_per_item',2690.1,3120.52,2);
INSERT INTO "requisition_items" VALUES (23,3,14,120,1.45,1,'2026-05-14 09:10:21','2026-05-14 09:10:21',1.45,27.84,'supplier_per_item',174,201.84,1);
INSERT INTO "requisition_items" VALUES (24,3,11,420,0.26,1,'2026-05-14 09:10:21','2026-05-14 09:10:21',0.26,17.47,'supplier_per_item',109.2,126.67,1);
INSERT INTO "requisition_items" VALUES (25,3,12,420,0.21,1,'2026-05-14 09:10:21','2026-05-14 09:10:21',0.21,14.11,'supplier_per_item',88.2,102.31,1);
INSERT INTO "requisitions" VALUES (2,1,'','aprobada',1,1,NULL,'2026-05-14 00:00:00','2026-05-14 08:58:50','2026-05-18 06:56:40','CEN1-REQ0002',2);
INSERT INTO "requisitions" VALUES (3,1,'','aprobada',1,1,NULL,'2026-05-14 00:00:00','2026-05-14 09:10:21','2026-05-18 06:56:36','CEN1-REQ0003',1);
INSERT INTO "roles" VALUES (1,'Administrador','2026-05-14 03:15:04','2026-05-14 03:15:04','["*"]');
INSERT INTO "roles" VALUES (2,'Encargado de Compras','2026-05-14 03:15:04','2026-05-14 03:15:04','["requisiciones.ver","requisiciones.crear","requisiciones.editar","requisiciones.aprobar","proveedores.ver","proveedores.crear","proveedores.editar","proveedores.eliminar","cotizaciones.cargar","productos.ver","productos.crear","productos.eliminar","catalogos.ver","catalogos.editar","reportes.ver","gastos.ver","proyectos.ver"]');
INSERT INTO "roles" VALUES (3,'Supervisor / Operativo','2026-05-14 03:15:04','2026-05-14 03:15:04','["proyectos.ver","gastos.ver","gastos.crear","requisiciones.ver","requisiciones.crear","reportes.ver"]');
INSERT INTO "sessions" VALUES ('wUMcKDSO9H0tuGXopy7MidV3g7hkTGbAh4A1RfhI',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJzNzd4NkQxcWQxYnBSZjMyR0dYdGhseGoxcTVFMGw1MmNtc3dxNVozIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC91c3VhcmlvcyIsInJvdXRlIjoidXN1YXJpb3MuaW5kZXgifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119LCJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI6MX0=',1779164057);
INSERT INTO "settings" VALUES (1,'company_name','Constructora Muulsinik S.A. de C.V.','empresa','string','Nombre de la empresa','Razón social completa de la constructora','2026-05-14 03:15:04','2026-05-14 03:15:04');
INSERT INTO "settings" VALUES (2,'company_rfc','','empresa','string','RFC','Registro Federal de Contribuyentes','2026-05-14 03:15:04','2026-05-14 03:15:04');
INSERT INTO "settings" VALUES (3,'company_address','','empresa','string','Dirección fiscal','Dirección completa de la empresa','2026-05-14 03:15:04','2026-05-14 03:15:04');
INSERT INTO "settings" VALUES (4,'company_phone','','empresa','string','Teléfono','Teléfono principal de contacto','2026-05-14 03:15:04','2026-05-14 03:15:04');
INSERT INTO "settings" VALUES (5,'company_email','','empresa','string','Correo electrónico','Email de contacto de la empresa','2026-05-14 03:15:04','2026-05-14 03:15:04');
INSERT INTO "settings" VALUES (6,'company_logo','company/D5xqiQozGPUj7MXqtwRliyraR9tFjYhTmEY5Zs11.png','empresa','string','Logo','Logo de la empresa para documentos','2026-05-14 03:15:04','2026-05-18 03:22:08');
INSERT INTO "settings" VALUES (7,'req_prefix','REQ-','documentos','string','Prefijo de requisiciones','Prefijo para números de requisición','2026-05-14 03:15:04','2026-05-14 03:15:04');
INSERT INTO "settings" VALUES (8,'req_next_number','1','documentos','number','Siguiente número de requisición','Número consecutivo para la próxima requisición','2026-05-14 03:15:04','2026-05-14 03:15:04');
INSERT INTO "settings" VALUES (9,'currency_symbol','$','documentos','string','Símbolo monetario','Símbolo de moneda para mostrar precios','2026-05-14 03:15:04','2026-05-14 03:15:04');
INSERT INTO "settings" VALUES (10,'currency_position','before','documentos','string','Posición del símbolo','before=Antes, after=Después del monto','2026-05-14 03:15:04','2026-05-14 03:15:04');
INSERT INTO "settings" VALUES (11,'decimal_places','2','documentos','number','Decimales','Cantidad de decimales para mostrar','2026-05-14 03:15:04','2026-05-14 03:15:04');
INSERT INTO "settings" VALUES (12,'terms_conditions','Precios sujetos a cambio sin previo aviso.
Vigencia de cotización: 15 días naturales.
Entrega sujeta a disponibilidad de inventario.','documentos','string','Términos y condiciones','Texto que aparece al final de cotizaciones','2026-05-14 03:15:04','2026-05-14 03:15:04');
INSERT INTO "settings" VALUES (13,'gemini_enabled','0','integraciones','boolean','Habilitar Gemini AI','Activar procesamiento de cotizaciones con IA','2026-05-14 03:15:04','2026-05-14 03:15:04');
INSERT INTO "settings" VALUES (14,'gemini_api_key','','integraciones','string','API Key de Gemini','Clave de API de Google AI Studio','2026-05-14 03:15:04','2026-05-14 03:15:04');
INSERT INTO "settings" VALUES (15,'gemini_model','gemini-1.5-flash','integraciones','string','Modelo Gemini','Versión del modelo a utilizar','2026-05-14 03:15:04','2026-05-14 03:15:04');
INSERT INTO "suppliers" VALUES (1,'Boxito',NULL,NULL,NULL,NULL,'2026-05-14 07:11:04','2026-05-14 07:11:04','boxito');
INSERT INTO "suppliers" VALUES (2,'Productos Pennsylvania',NULL,NULL,NULL,NULL,'2026-05-14 08:58:50','2026-05-14 08:58:50','productos pennsylvania');
INSERT INTO "users" VALUES (1,'Administrador','admin@muulsinik.com',NULL,'$2y$12$PgWVdxipGNWufdaWt.zMo.I7J/EZnIX0GZ1cGJruKejltSxVGofJ.',1,1,NULL,'2026-05-14 03:15:04','2026-05-18 03:26:06','avatars/x9yOQtoX6RB1Vkxqr6NuuiI9Z6x7hWNjJs9xTWmO.png');
INSERT INTO "vendors" VALUES (1,1,'Leticia Alejandra Dzul Uh',NULL,NULL,'2026-05-14 07:11:04','2026-05-14 07:11:04');
INSERT INTO "vendors" VALUES (2,2,'Jose Armando Canúl',NULL,NULL,'2026-05-14 08:58:50','2026-05-14 08:58:50');
CREATE INDEX IF NOT EXISTS "cache_expiration_index" ON "cache" (
	"expiration"
);
CREATE INDEX IF NOT EXISTS "cache_locks_expiration_index" ON "cache_locks" (
	"expiration"
);
CREATE UNIQUE INDEX IF NOT EXISTS "categories_name_unique" ON "categories" (
	"name"
);
CREATE UNIQUE INDEX IF NOT EXISTS "failed_jobs_uuid_unique" ON "failed_jobs" (
	"uuid"
);
CREATE INDEX IF NOT EXISTS "jobs_queue_index" ON "jobs" (
	"queue"
);
CREATE INDEX IF NOT EXISTS "measures_abbreviation_index" ON "measures" (
	"abbreviation"
);
CREATE UNIQUE INDEX IF NOT EXISTS "measures_name_unique" ON "measures" (
	"name"
);
CREATE INDEX IF NOT EXISTS "notifications_notifiable_type_notifiable_id_index" ON "notifications" (
	"notifiable_type",
	"notifiable_id"
);
CREATE INDEX IF NOT EXISTS "products_normalized_name_index" ON "products" (
	"normalized_name"
);
CREATE UNIQUE INDEX IF NOT EXISTS "roles_name_unique" ON "roles" (
	"name"
);
CREATE INDEX IF NOT EXISTS "sessions_last_activity_index" ON "sessions" (
	"last_activity"
);
CREATE INDEX IF NOT EXISTS "sessions_user_id_index" ON "sessions" (
	"user_id"
);
CREATE UNIQUE INDEX IF NOT EXISTS "settings_key_unique" ON "settings" (
	"key"
);
CREATE INDEX IF NOT EXISTS "suppliers_normalized_name_index" ON "suppliers" (
	"normalized_name"
);
CREATE UNIQUE INDEX IF NOT EXISTS "users_email_unique" ON "users" (
	"email"
);
COMMIT;
