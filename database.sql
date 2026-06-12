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
	"draft_state"	text,
	PRIMARY KEY("id" AUTOINCREMENT),
	FOREIGN KEY("project_id") REFERENCES "projects"("id") on delete set null,
	FOREIGN KEY("requisition_id") REFERENCES "requisitions"("id") on delete set null on update no action,
	FOREIGN KEY("supplier_id") REFERENCES "suppliers"("id") on delete set null on update no action,
	FOREIGN KEY("uploaded_by") REFERENCES "users"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "requisition_activities" (
	"id"	integer NOT NULL,
	"requisition_id"	integer NOT NULL,
	"user_id"	integer,
	"action"	varchar NOT NULL,
	"description"	text,
	"old_values"	text,
	"new_values"	text,
	"created_at"	datetime,
	"updated_at"	datetime,
	PRIMARY KEY("id" AUTOINCREMENT),
	FOREIGN KEY("requisition_id") REFERENCES "requisitions"("id") on delete cascade,
	FOREIGN KEY("user_id") REFERENCES "users"("id") on delete set null
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
	"discount"	numeric DEFAULT '0',
	"discount_percentage"	numeric,
	"discount_amount"	numeric,
	"discount_percent"	numeric,
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
	"discount"	numeric DEFAULT '0',
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
INSERT INTO "cache" VALUES ('muulsinik-erp-cache-setting_company_name','s:35:"Constructora Muulsinik S.A. de C.V.";',1781303134);
INSERT INTO "cache" VALUES ('muulsinik-erp-cache-setting_company_rfc','s:0:"";',1781303134);
INSERT INTO "cache" VALUES ('muulsinik-erp-cache-setting_company_address','s:0:"";',1781303134);
INSERT INTO "cache" VALUES ('muulsinik-erp-cache-setting_company_phone','s:0:"";',1781303134);
INSERT INTO "cache" VALUES ('muulsinik-erp-cache-setting_company_email','s:0:"";',1781303134);
INSERT INTO "cache" VALUES ('muulsinik-erp-cache-setting_company_logo','s:52:"company/qWzfxuoyhH5cMEfT7NKRAVLUnYDnrplkpJtAvXTZ.png";',1781303134);
INSERT INTO "cache" VALUES ('muulsinik-erp-cache-setting_req_prefix','s:4:"REQ-";',1781303134);
INSERT INTO "cache" VALUES ('muulsinik-erp-cache-setting_req_next_number','d:1;',1781303134);
INSERT INTO "cache" VALUES ('muulsinik-erp-cache-setting_currency_symbol','s:1:"$";',1781303134);
INSERT INTO "cache" VALUES ('muulsinik-erp-cache-setting_currency_position','s:6:"before";',1781303134);
INSERT INTO "cache" VALUES ('muulsinik-erp-cache-setting_decimal_places','d:2;',1781303134);
INSERT INTO "cache" VALUES ('muulsinik-erp-cache-setting_terms_conditions','s:134:"Precios sujetos a cambio sin previo aviso.
Vigencia de cotización: 15 días naturales.
Entrega sujeta a disponibilidad de inventario.";',1781303134);
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
INSERT INTO "categories" VALUES (14,'Ferretería','2026-05-23 04:35:53','2026-05-23 04:35:53');
INSERT INTO "expenses" VALUES (3,'Albañil',2000,'2026-06-07 00:00:00','mano_de_obra',1,1,NULL,'2026-06-07 03:12:33','2026-06-07 03:12:33',0);
INSERT INTO "expenses" VALUES (4,'dd44',34,'2026-06-11 00:00:00','materiales',1,1,NULL,'2026-06-12 19:24:47','2026-06-12 19:24:47',0);
INSERT INTO "expenses" VALUES (5,'4444',4,'2026-06-12 00:00:00','materiales',1,1,NULL,'2026-06-12 19:24:56','2026-06-12 19:24:56',0);
INSERT INTO "expenses" VALUES (6,'444',44,'2026-06-12 00:00:00','mano_de_obra',1,1,NULL,'2026-06-12 19:25:05','2026-06-12 19:25:05',0);
INSERT INTO "expenses" VALUES (7,'44444',44,'2026-06-12 00:00:00','mano_de_obra',1,1,NULL,'2026-06-12 19:25:17','2026-06-12 19:25:17',0);
INSERT INTO "expenses" VALUES (8,'444',44,'2026-06-12 00:00:00','materiales',1,1,NULL,'2026-06-12 19:25:26','2026-06-12 19:25:26',0);
INSERT INTO "expenses" VALUES (9,'44444',44,'2026-06-12 00:00:00','equipo',1,1,NULL,'2026-06-12 19:25:36','2026-06-12 19:25:36',0);
INSERT INTO "expenses" VALUES (10,'4444',444,'2026-06-12 00:00:00','materiales',3,1,NULL,'2026-06-12 19:25:45','2026-06-12 19:25:45',0);
INSERT INTO "expenses" VALUES (11,'44443',43,'2026-06-12 00:00:00','materiales',3,1,NULL,'2026-06-12 19:25:54','2026-06-12 19:25:54',0);
INSERT INTO "expenses" VALUES (12,'334',32,'2026-06-12 00:00:00','equipo',3,1,NULL,'2026-06-12 19:26:31','2026-06-12 19:26:31',0);
INSERT INTO "expenses" VALUES (13,'4255',54,'2026-06-12 00:00:00','mano_de_obra',3,1,NULL,'2026-06-12 19:26:45','2026-06-12 19:26:45',0);
INSERT INTO "expenses" VALUES (14,'3333',23,'2026-06-12 00:00:00','equipo',1,1,NULL,'2026-06-12 19:28:01','2026-06-12 19:28:01',0);
INSERT INTO "expenses" VALUES (15,'34343',2434,'2026-06-12 00:00:00','mano_de_obra',1,1,NULL,'2026-06-12 19:28:10','2026-06-12 19:28:10',0);
INSERT INTO "expenses" VALUES (16,'34343',4343,'2026-06-12 00:00:00','transporte',3,1,NULL,'2026-06-12 19:28:18','2026-06-12 19:28:18',0);
INSERT INTO "expenses" VALUES (17,'34343',4343,'2026-06-12 00:00:00','materiales',1,1,NULL,'2026-06-12 19:28:25','2026-06-12 19:28:25',0);
INSERT INTO "expenses" VALUES (18,'4343434',34343,'2026-06-12 00:00:00','mano_de_obra',1,1,NULL,'2026-06-12 19:28:36','2026-06-12 19:28:36',0);
INSERT INTO "failed_jobs" VALUES (1,'91cb76d5-ca5f-4faa-a20d-0dcc2f862f98','database','default','{"uuid":"91cb76d5-ca5f-4faa-a20d-0dcc2f862f98","displayName":"App\\Jobs\\ProcessQuotationJob","job":"Illuminate\\Queue\\CallQueuedHandler@call","maxTries":2,"maxExceptions":null,"failOnTimeout":false,"backoff":null,"timeout":120,"retryUntil":null,"deleteWhenMissingModels":false,"data":{"commandName":"App\\Jobs\\ProcessQuotationJob","command":"O:28:\"App\\Jobs\\ProcessQuotationJob\":1:{s:41:\"\u0000App\\Jobs\\ProcessQuotationJob\u0000quotationId\";i:39;}","batchId":null},"createdAt":1780296101,"delay":null}','Exception: La IA de Gemini está experimentando alta demanda o no pudo procesar el archivo en este momento. Por favor, intenta de nuevo o continúa manualmente. in C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\app\Services\DocumentParsers\VisionParserService.php:38
Stack trace:
#0 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\app\Jobs\ProcessQuotationJob.php(52): App\Services\DocumentParsers\VisionParserService->parse(''C:\\Users\\HP\\Doc...'')
#1 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Container\BoundMethod.php(36): App\Jobs\ProcessQuotationJob->handle(Object(App\Services\DocumentParsers\DocumentParserFactory))
#2 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Container\Util.php(43): Illuminate\Container\BoundMethod::Illuminate\Container\{closure}()
#3 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Container\BoundMethod.php(96): Illuminate\Container\Util::unwrapIfClosure(Object(Closure))
#4 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Container\BoundMethod.php(35): Illuminate\Container\BoundMethod::callBoundMethod(Object(Illuminate\Foundation\Application), Array, Object(Closure))
#5 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Container\Container.php(799): Illuminate\Container\BoundMethod::call(Object(Illuminate\Foundation\Application), Array, Array, NULL)
#6 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Bus\Dispatcher.php(135): Illuminate\Container\Container->call(Array)
#7 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php(180): Illuminate\Bus\Dispatcher->Illuminate\Bus\{closure}(Object(App\Jobs\ProcessQuotationJob))
#8 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php(137): Illuminate\Pipeline\Pipeline->Illuminate\Pipeline\{closure}(Object(App\Jobs\ProcessQuotationJob))
#9 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Bus\Dispatcher.php(139): Illuminate\Pipeline\Pipeline->then(Object(Closure))
#10 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Queue\CallQueuedHandler.php(134): Illuminate\Bus\Dispatcher->dispatchNow(Object(App\Jobs\ProcessQuotationJob), false)
#11 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php(180): Illuminate\Queue\CallQueuedHandler->Illuminate\Queue\{closure}(Object(App\Jobs\ProcessQuotationJob))
#12 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php(137): Illuminate\Pipeline\Pipeline->Illuminate\Pipeline\{closure}(Object(App\Jobs\ProcessQuotationJob))
#13 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Queue\CallQueuedHandler.php(127): Illuminate\Pipeline\Pipeline->then(Object(Closure))
#14 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Queue\CallQueuedHandler.php(68): Illuminate\Queue\CallQueuedHandler->dispatchThroughMiddleware(Object(Illuminate\Queue\Jobs\DatabaseJob), Object(App\Jobs\ProcessQuotationJob))
#15 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Queue\Jobs\Job.php(102): Illuminate\Queue\CallQueuedHandler->call(Object(Illuminate\Queue\Jobs\DatabaseJob), Array)
#16 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Queue\Worker.php(494): Illuminate\Queue\Jobs\Job->fire()
#17 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Queue\Worker.php(442): Illuminate\Queue\Worker->process(''database'', Object(Illuminate\Queue\Jobs\DatabaseJob), Object(Illuminate\Queue\WorkerOptions))
#18 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Queue\Worker.php(208): Illuminate\Queue\Worker->runJob(Object(Illuminate\Queue\Jobs\DatabaseJob), ''database'', Object(Illuminate\Queue\WorkerOptions))
#19 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Queue\Console\WorkCommand.php(148): Illuminate\Queue\Worker->daemon(''database'', ''default'', Object(Illuminate\Queue\WorkerOptions))
#20 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Queue\Console\WorkCommand.php(131): Illuminate\Queue\Console\WorkCommand->runWorker(''database'', ''default'')
#21 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Container\BoundMethod.php(36): Illuminate\Queue\Console\WorkCommand->handle()
#22 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Container\Util.php(43): Illuminate\Container\BoundMethod::Illuminate\Container\{closure}()
#23 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Container\BoundMethod.php(96): Illuminate\Container\Util::unwrapIfClosure(Object(Closure))
#24 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Container\BoundMethod.php(35): Illuminate\Container\BoundMethod::callBoundMethod(Object(Illuminate\Foundation\Application), Array, Object(Closure))
#25 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Container\Container.php(799): Illuminate\Container\BoundMethod::call(Object(Illuminate\Foundation\Application), Array, Array, NULL)
#26 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Console\Command.php(280): Illuminate\Container\Container->call(Array)
#27 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\symfony\console\Command\Command.php(341): Illuminate\Console\Command->execute(Object(Symfony\Component\Console\Input\ArgvInput), Object(Illuminate\Console\OutputStyle))
#28 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Console\Command.php(249): Symfony\Component\Console\Command\Command->run(Object(Symfony\Component\Console\Input\ArgvInput), Object(Illuminate\Console\OutputStyle))
#29 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\symfony\console\Application.php(1117): Illuminate\Console\Command->run(Object(Symfony\Component\Console\Input\ArgvInput), Object(Symfony\Component\Console\Output\ConsoleOutput))
#30 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\symfony\console\Application.php(356): Symfony\Component\Console\Application->doRunCommand(Object(Illuminate\Queue\Console\WorkCommand), Object(Symfony\Component\Console\Input\ArgvInput), Object(Symfony\Component\Console\Output\ConsoleOutput))
#31 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\symfony\console\Application.php(195): Symfony\Component\Console\Application->doRun(Object(Symfony\Component\Console\Input\ArgvInput), Object(Symfony\Component\Console\Output\ConsoleOutput))
#32 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Foundation\Console\Kernel.php(198): Symfony\Component\Console\Application->run(Object(Symfony\Component\Console\Input\ArgvInput), Object(Symfony\Component\Console\Output\ConsoleOutput))
#33 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Foundation\Application.php(1235): Illuminate\Foundation\Console\Kernel->handle(Object(Symfony\Component\Console\Input\ArgvInput), Object(Symfony\Component\Console\Output\ConsoleOutput))
#34 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\artisan(16): Illuminate\Foundation\Application->handleCommand(Object(Symfony\Component\Console\Input\ArgvInput))
#35 {main}','2026-06-01 06:42:36');
INSERT INTO "failed_jobs" VALUES (2,'2ecf5aaf-d869-4aac-a47e-270df9c8be5d','database','default','{"uuid":"2ecf5aaf-d869-4aac-a47e-270df9c8be5d","displayName":"App\\Jobs\\ProcessQuotationJob","job":"Illuminate\\Queue\\CallQueuedHandler@call","maxTries":2,"maxExceptions":null,"failOnTimeout":false,"backoff":null,"timeout":120,"retryUntil":null,"deleteWhenMissingModels":false,"data":{"commandName":"App\\Jobs\\ProcessQuotationJob","command":"O:28:\"App\\Jobs\\ProcessQuotationJob\":1:{s:41:\"\u0000App\\Jobs\\ProcessQuotationJob\u0000quotationId\";i:41;}","batchId":null},"createdAt":1780337332,"delay":null}','Exception: La IA de Gemini está experimentando alta demanda o no pudo procesar el archivo en este momento. Por favor, intenta de nuevo o continúa manualmente. in C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\app\Services\DocumentParsers\VisionParserService.php:38
Stack trace:
#0 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\app\Jobs\ProcessQuotationJob.php(52): App\Services\DocumentParsers\VisionParserService->parse(''C:\\Users\\HP\\Doc...'')
#1 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Container\BoundMethod.php(36): App\Jobs\ProcessQuotationJob->handle(Object(App\Services\DocumentParsers\DocumentParserFactory))
#2 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Container\Util.php(43): Illuminate\Container\BoundMethod::Illuminate\Container\{closure}()
#3 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Container\BoundMethod.php(96): Illuminate\Container\Util::unwrapIfClosure(Object(Closure))
#4 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Container\BoundMethod.php(35): Illuminate\Container\BoundMethod::callBoundMethod(Object(Illuminate\Foundation\Application), Array, Object(Closure))
#5 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Container\Container.php(799): Illuminate\Container\BoundMethod::call(Object(Illuminate\Foundation\Application), Array, Array, NULL)
#6 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Bus\Dispatcher.php(135): Illuminate\Container\Container->call(Array)
#7 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php(180): Illuminate\Bus\Dispatcher->Illuminate\Bus\{closure}(Object(App\Jobs\ProcessQuotationJob))
#8 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php(137): Illuminate\Pipeline\Pipeline->Illuminate\Pipeline\{closure}(Object(App\Jobs\ProcessQuotationJob))
#9 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Bus\Dispatcher.php(139): Illuminate\Pipeline\Pipeline->then(Object(Closure))
#10 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Queue\CallQueuedHandler.php(134): Illuminate\Bus\Dispatcher->dispatchNow(Object(App\Jobs\ProcessQuotationJob), false)
#11 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php(180): Illuminate\Queue\CallQueuedHandler->Illuminate\Queue\{closure}(Object(App\Jobs\ProcessQuotationJob))
#12 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php(137): Illuminate\Pipeline\Pipeline->Illuminate\Pipeline\{closure}(Object(App\Jobs\ProcessQuotationJob))
#13 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Queue\CallQueuedHandler.php(127): Illuminate\Pipeline\Pipeline->then(Object(Closure))
#14 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Queue\CallQueuedHandler.php(68): Illuminate\Queue\CallQueuedHandler->dispatchThroughMiddleware(Object(Illuminate\Queue\Jobs\DatabaseJob), Object(App\Jobs\ProcessQuotationJob))
#15 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Queue\Jobs\Job.php(102): Illuminate\Queue\CallQueuedHandler->call(Object(Illuminate\Queue\Jobs\DatabaseJob), Array)
#16 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Queue\Worker.php(494): Illuminate\Queue\Jobs\Job->fire()
#17 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Queue\Worker.php(442): Illuminate\Queue\Worker->process(''database'', Object(Illuminate\Queue\Jobs\DatabaseJob), Object(Illuminate\Queue\WorkerOptions))
#18 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Queue\Worker.php(208): Illuminate\Queue\Worker->runJob(Object(Illuminate\Queue\Jobs\DatabaseJob), ''database'', Object(Illuminate\Queue\WorkerOptions))
#19 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Queue\Console\WorkCommand.php(148): Illuminate\Queue\Worker->daemon(''database'', ''default'', Object(Illuminate\Queue\WorkerOptions))
#20 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Queue\Console\WorkCommand.php(131): Illuminate\Queue\Console\WorkCommand->runWorker(''database'', ''default'')
#21 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Container\BoundMethod.php(36): Illuminate\Queue\Console\WorkCommand->handle()
#22 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Container\Util.php(43): Illuminate\Container\BoundMethod::Illuminate\Container\{closure}()
#23 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Container\BoundMethod.php(96): Illuminate\Container\Util::unwrapIfClosure(Object(Closure))
#24 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Container\BoundMethod.php(35): Illuminate\Container\BoundMethod::callBoundMethod(Object(Illuminate\Foundation\Application), Array, Object(Closure))
#25 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Container\Container.php(799): Illuminate\Container\BoundMethod::call(Object(Illuminate\Foundation\Application), Array, Array, NULL)
#26 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Console\Command.php(280): Illuminate\Container\Container->call(Array)
#27 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\symfony\console\Command\Command.php(341): Illuminate\Console\Command->execute(Object(Symfony\Component\Console\Input\ArgvInput), Object(Illuminate\Console\OutputStyle))
#28 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Console\Command.php(249): Symfony\Component\Console\Command\Command->run(Object(Symfony\Component\Console\Input\ArgvInput), Object(Illuminate\Console\OutputStyle))
#29 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\symfony\console\Application.php(1117): Illuminate\Console\Command->run(Object(Symfony\Component\Console\Input\ArgvInput), Object(Symfony\Component\Console\Output\ConsoleOutput))
#30 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\symfony\console\Application.php(356): Symfony\Component\Console\Application->doRunCommand(Object(Illuminate\Queue\Console\WorkCommand), Object(Symfony\Component\Console\Input\ArgvInput), Object(Symfony\Component\Console\Output\ConsoleOutput))
#31 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\symfony\console\Application.php(195): Symfony\Component\Console\Application->doRun(Object(Symfony\Component\Console\Input\ArgvInput), Object(Symfony\Component\Console\Output\ConsoleOutput))
#32 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Foundation\Console\Kernel.php(198): Symfony\Component\Console\Application->run(Object(Symfony\Component\Console\Input\ArgvInput), Object(Symfony\Component\Console\Output\ConsoleOutput))
#33 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Foundation\Application.php(1235): Illuminate\Foundation\Console\Kernel->handle(Object(Symfony\Component\Console\Input\ArgvInput), Object(Symfony\Component\Console\Output\ConsoleOutput))
#34 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\artisan(16): Illuminate\Foundation\Application->handleCommand(Object(Symfony\Component\Console\Input\ArgvInput))
#35 {main}','2026-06-01 18:09:27');
INSERT INTO "failed_jobs" VALUES (3,'b76d3bf8-1a5f-4dac-a6d7-9f9aa7019a3d','database','default','{"uuid":"b76d3bf8-1a5f-4dac-a6d7-9f9aa7019a3d","displayName":"App\\Jobs\\ProcessQuotationJob","job":"Illuminate\\Queue\\CallQueuedHandler@call","maxTries":2,"maxExceptions":null,"failOnTimeout":false,"backoff":null,"timeout":120,"retryUntil":null,"deleteWhenMissingModels":false,"data":{"commandName":"App\\Jobs\\ProcessQuotationJob","command":"O:28:\"App\\Jobs\\ProcessQuotationJob\":1:{s:41:\"\u0000App\\Jobs\\ProcessQuotationJob\u0000quotationId\";i:41;}","batchId":null},"createdAt":1780337391,"delay":null}','Exception: La IA de Gemini está experimentando alta demanda o no pudo procesar el archivo en este momento. Por favor, intenta de nuevo o continúa manualmente. in C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\app\Services\DocumentParsers\VisionParserService.php:38
Stack trace:
#0 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\app\Jobs\ProcessQuotationJob.php(52): App\Services\DocumentParsers\VisionParserService->parse(''C:\\Users\\HP\\Doc...'')
#1 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Container\BoundMethod.php(36): App\Jobs\ProcessQuotationJob->handle(Object(App\Services\DocumentParsers\DocumentParserFactory))
#2 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Container\Util.php(43): Illuminate\Container\BoundMethod::Illuminate\Container\{closure}()
#3 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Container\BoundMethod.php(96): Illuminate\Container\Util::unwrapIfClosure(Object(Closure))
#4 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Container\BoundMethod.php(35): Illuminate\Container\BoundMethod::callBoundMethod(Object(Illuminate\Foundation\Application), Array, Object(Closure))
#5 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Container\Container.php(799): Illuminate\Container\BoundMethod::call(Object(Illuminate\Foundation\Application), Array, Array, NULL)
#6 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Bus\Dispatcher.php(135): Illuminate\Container\Container->call(Array)
#7 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php(180): Illuminate\Bus\Dispatcher->Illuminate\Bus\{closure}(Object(App\Jobs\ProcessQuotationJob))
#8 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php(137): Illuminate\Pipeline\Pipeline->Illuminate\Pipeline\{closure}(Object(App\Jobs\ProcessQuotationJob))
#9 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Bus\Dispatcher.php(139): Illuminate\Pipeline\Pipeline->then(Object(Closure))
#10 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Queue\CallQueuedHandler.php(134): Illuminate\Bus\Dispatcher->dispatchNow(Object(App\Jobs\ProcessQuotationJob), false)
#11 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php(180): Illuminate\Queue\CallQueuedHandler->Illuminate\Queue\{closure}(Object(App\Jobs\ProcessQuotationJob))
#12 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php(137): Illuminate\Pipeline\Pipeline->Illuminate\Pipeline\{closure}(Object(App\Jobs\ProcessQuotationJob))
#13 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Queue\CallQueuedHandler.php(127): Illuminate\Pipeline\Pipeline->then(Object(Closure))
#14 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Queue\CallQueuedHandler.php(68): Illuminate\Queue\CallQueuedHandler->dispatchThroughMiddleware(Object(Illuminate\Queue\Jobs\DatabaseJob), Object(App\Jobs\ProcessQuotationJob))
#15 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Queue\Jobs\Job.php(102): Illuminate\Queue\CallQueuedHandler->call(Object(Illuminate\Queue\Jobs\DatabaseJob), Array)
#16 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Queue\Worker.php(494): Illuminate\Queue\Jobs\Job->fire()
#17 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Queue\Worker.php(442): Illuminate\Queue\Worker->process(''database'', Object(Illuminate\Queue\Jobs\DatabaseJob), Object(Illuminate\Queue\WorkerOptions))
#18 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Queue\Worker.php(208): Illuminate\Queue\Worker->runJob(Object(Illuminate\Queue\Jobs\DatabaseJob), ''database'', Object(Illuminate\Queue\WorkerOptions))
#19 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Queue\Console\WorkCommand.php(148): Illuminate\Queue\Worker->daemon(''database'', ''default'', Object(Illuminate\Queue\WorkerOptions))
#20 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Queue\Console\WorkCommand.php(131): Illuminate\Queue\Console\WorkCommand->runWorker(''database'', ''default'')
#21 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Container\BoundMethod.php(36): Illuminate\Queue\Console\WorkCommand->handle()
#22 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Container\Util.php(43): Illuminate\Container\BoundMethod::Illuminate\Container\{closure}()
#23 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Container\BoundMethod.php(96): Illuminate\Container\Util::unwrapIfClosure(Object(Closure))
#24 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Container\BoundMethod.php(35): Illuminate\Container\BoundMethod::callBoundMethod(Object(Illuminate\Foundation\Application), Array, Object(Closure))
#25 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Container\Container.php(799): Illuminate\Container\BoundMethod::call(Object(Illuminate\Foundation\Application), Array, Array, NULL)
#26 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Console\Command.php(280): Illuminate\Container\Container->call(Array)
#27 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\symfony\console\Command\Command.php(341): Illuminate\Console\Command->execute(Object(Symfony\Component\Console\Input\ArgvInput), Object(Illuminate\Console\OutputStyle))
#28 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Console\Command.php(249): Symfony\Component\Console\Command\Command->run(Object(Symfony\Component\Console\Input\ArgvInput), Object(Illuminate\Console\OutputStyle))
#29 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\symfony\console\Application.php(1117): Illuminate\Console\Command->run(Object(Symfony\Component\Console\Input\ArgvInput), Object(Symfony\Component\Console\Output\ConsoleOutput))
#30 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\symfony\console\Application.php(356): Symfony\Component\Console\Application->doRunCommand(Object(Illuminate\Queue\Console\WorkCommand), Object(Symfony\Component\Console\Input\ArgvInput), Object(Symfony\Component\Console\Output\ConsoleOutput))
#31 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\symfony\console\Application.php(195): Symfony\Component\Console\Application->doRun(Object(Symfony\Component\Console\Input\ArgvInput), Object(Symfony\Component\Console\Output\ConsoleOutput))
#32 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Foundation\Console\Kernel.php(198): Symfony\Component\Console\Application->run(Object(Symfony\Component\Console\Input\ArgvInput), Object(Symfony\Component\Console\Output\ConsoleOutput))
#33 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Foundation\Application.php(1235): Illuminate\Foundation\Console\Kernel->handle(Object(Symfony\Component\Console\Input\ArgvInput), Object(Symfony\Component\Console\Output\ConsoleOutput))
#34 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\artisan(16): Illuminate\Foundation\Application->handleCommand(Object(Symfony\Component\Console\Input\ArgvInput))
#35 {main}','2026-06-01 18:11:55');
INSERT INTO "failed_jobs" VALUES (4,'ddfe9d4e-5f0b-4309-a824-393ec6749b20','database','default','{"uuid":"ddfe9d4e-5f0b-4309-a824-393ec6749b20","displayName":"App\\Jobs\\ProcessQuotationJob","job":"Illuminate\\Queue\\CallQueuedHandler@call","maxTries":2,"maxExceptions":null,"failOnTimeout":false,"backoff":null,"timeout":120,"retryUntil":null,"deleteWhenMissingModels":false,"data":{"commandName":"App\\Jobs\\ProcessQuotationJob","command":"O:28:\"App\\Jobs\\ProcessQuotationJob\":1:{s:41:\"\u0000App\\Jobs\\ProcessQuotationJob\u0000quotationId\";i:42;}","batchId":null},"createdAt":1780337740,"delay":null}','Exception: La IA de Gemini está experimentando alta demanda o no pudo procesar el archivo en este momento. Por favor, intenta de nuevo o continúa manualmente. in C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\app\Services\DocumentParsers\VisionParserService.php:38
Stack trace:
#0 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\app\Jobs\ProcessQuotationJob.php(52): App\Services\DocumentParsers\VisionParserService->parse(''C:\\Users\\HP\\Doc...'')
#1 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Container\BoundMethod.php(36): App\Jobs\ProcessQuotationJob->handle(Object(App\Services\DocumentParsers\DocumentParserFactory))
#2 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Container\Util.php(43): Illuminate\Container\BoundMethod::Illuminate\Container\{closure}()
#3 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Container\BoundMethod.php(96): Illuminate\Container\Util::unwrapIfClosure(Object(Closure))
#4 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Container\BoundMethod.php(35): Illuminate\Container\BoundMethod::callBoundMethod(Object(Illuminate\Foundation\Application), Array, Object(Closure))
#5 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Container\Container.php(799): Illuminate\Container\BoundMethod::call(Object(Illuminate\Foundation\Application), Array, Array, NULL)
#6 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Bus\Dispatcher.php(135): Illuminate\Container\Container->call(Array)
#7 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php(180): Illuminate\Bus\Dispatcher->Illuminate\Bus\{closure}(Object(App\Jobs\ProcessQuotationJob))
#8 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php(137): Illuminate\Pipeline\Pipeline->Illuminate\Pipeline\{closure}(Object(App\Jobs\ProcessQuotationJob))
#9 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Bus\Dispatcher.php(139): Illuminate\Pipeline\Pipeline->then(Object(Closure))
#10 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Queue\CallQueuedHandler.php(134): Illuminate\Bus\Dispatcher->dispatchNow(Object(App\Jobs\ProcessQuotationJob), false)
#11 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php(180): Illuminate\Queue\CallQueuedHandler->Illuminate\Queue\{closure}(Object(App\Jobs\ProcessQuotationJob))
#12 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php(137): Illuminate\Pipeline\Pipeline->Illuminate\Pipeline\{closure}(Object(App\Jobs\ProcessQuotationJob))
#13 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Queue\CallQueuedHandler.php(127): Illuminate\Pipeline\Pipeline->then(Object(Closure))
#14 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Queue\CallQueuedHandler.php(68): Illuminate\Queue\CallQueuedHandler->dispatchThroughMiddleware(Object(Illuminate\Queue\Jobs\DatabaseJob), Object(App\Jobs\ProcessQuotationJob))
#15 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Queue\Jobs\Job.php(102): Illuminate\Queue\CallQueuedHandler->call(Object(Illuminate\Queue\Jobs\DatabaseJob), Array)
#16 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Queue\Worker.php(494): Illuminate\Queue\Jobs\Job->fire()
#17 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Queue\Worker.php(442): Illuminate\Queue\Worker->process(''database'', Object(Illuminate\Queue\Jobs\DatabaseJob), Object(Illuminate\Queue\WorkerOptions))
#18 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Queue\Worker.php(208): Illuminate\Queue\Worker->runJob(Object(Illuminate\Queue\Jobs\DatabaseJob), ''database'', Object(Illuminate\Queue\WorkerOptions))
#19 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Queue\Console\WorkCommand.php(148): Illuminate\Queue\Worker->daemon(''database'', ''default'', Object(Illuminate\Queue\WorkerOptions))
#20 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Queue\Console\WorkCommand.php(131): Illuminate\Queue\Console\WorkCommand->runWorker(''database'', ''default'')
#21 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Container\BoundMethod.php(36): Illuminate\Queue\Console\WorkCommand->handle()
#22 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Container\Util.php(43): Illuminate\Container\BoundMethod::Illuminate\Container\{closure}()
#23 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Container\BoundMethod.php(96): Illuminate\Container\Util::unwrapIfClosure(Object(Closure))
#24 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Container\BoundMethod.php(35): Illuminate\Container\BoundMethod::callBoundMethod(Object(Illuminate\Foundation\Application), Array, Object(Closure))
#25 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Container\Container.php(799): Illuminate\Container\BoundMethod::call(Object(Illuminate\Foundation\Application), Array, Array, NULL)
#26 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Console\Command.php(280): Illuminate\Container\Container->call(Array)
#27 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\symfony\console\Command\Command.php(341): Illuminate\Console\Command->execute(Object(Symfony\Component\Console\Input\ArgvInput), Object(Illuminate\Console\OutputStyle))
#28 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Console\Command.php(249): Symfony\Component\Console\Command\Command->run(Object(Symfony\Component\Console\Input\ArgvInput), Object(Illuminate\Console\OutputStyle))
#29 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\symfony\console\Application.php(1117): Illuminate\Console\Command->run(Object(Symfony\Component\Console\Input\ArgvInput), Object(Symfony\Component\Console\Output\ConsoleOutput))
#30 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\symfony\console\Application.php(356): Symfony\Component\Console\Application->doRunCommand(Object(Illuminate\Queue\Console\WorkCommand), Object(Symfony\Component\Console\Input\ArgvInput), Object(Symfony\Component\Console\Output\ConsoleOutput))
#31 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\symfony\console\Application.php(195): Symfony\Component\Console\Application->doRun(Object(Symfony\Component\Console\Input\ArgvInput), Object(Symfony\Component\Console\Output\ConsoleOutput))
#32 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Foundation\Console\Kernel.php(198): Symfony\Component\Console\Application->run(Object(Symfony\Component\Console\Input\ArgvInput), Object(Symfony\Component\Console\Output\ConsoleOutput))
#33 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Foundation\Application.php(1235): Illuminate\Foundation\Console\Kernel->handle(Object(Symfony\Component\Console\Input\ArgvInput), Object(Symfony\Component\Console\Output\ConsoleOutput))
#34 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\artisan(16): Illuminate\Foundation\Application->handleCommand(Object(Symfony\Component\Console\Input\ArgvInput))
#35 {main}','2026-06-01 18:15:46');
INSERT INTO "failed_jobs" VALUES (5,'4827485d-80a2-4608-a70c-b64c6d329752','database','default','{"uuid":"4827485d-80a2-4608-a70c-b64c6d329752","displayName":"App\\Jobs\\ProcessQuotationJob","job":"Illuminate\\Queue\\CallQueuedHandler@call","maxTries":2,"maxExceptions":null,"failOnTimeout":false,"backoff":null,"timeout":120,"retryUntil":null,"deleteWhenMissingModels":false,"data":{"commandName":"App\\Jobs\\ProcessQuotationJob","command":"O:28:\"App\\Jobs\\ProcessQuotationJob\":1:{s:41:\"\u0000App\\Jobs\\ProcessQuotationJob\u0000quotationId\";i:44;}","batchId":null},"createdAt":1780337847,"delay":null}','Exception: La IA de Gemini está experimentando alta demanda o no pudo procesar el archivo en este momento. Por favor, intenta de nuevo o continúa manualmente. in C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\app\Services\DocumentParsers\VisionParserService.php:38
Stack trace:
#0 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\app\Jobs\ProcessQuotationJob.php(52): App\Services\DocumentParsers\VisionParserService->parse(''C:\\Users\\HP\\Doc...'')
#1 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Container\BoundMethod.php(36): App\Jobs\ProcessQuotationJob->handle(Object(App\Services\DocumentParsers\DocumentParserFactory))
#2 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Container\Util.php(43): Illuminate\Container\BoundMethod::Illuminate\Container\{closure}()
#3 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Container\BoundMethod.php(96): Illuminate\Container\Util::unwrapIfClosure(Object(Closure))
#4 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Container\BoundMethod.php(35): Illuminate\Container\BoundMethod::callBoundMethod(Object(Illuminate\Foundation\Application), Array, Object(Closure))
#5 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Container\Container.php(799): Illuminate\Container\BoundMethod::call(Object(Illuminate\Foundation\Application), Array, Array, NULL)
#6 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Bus\Dispatcher.php(135): Illuminate\Container\Container->call(Array)
#7 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php(180): Illuminate\Bus\Dispatcher->Illuminate\Bus\{closure}(Object(App\Jobs\ProcessQuotationJob))
#8 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php(137): Illuminate\Pipeline\Pipeline->Illuminate\Pipeline\{closure}(Object(App\Jobs\ProcessQuotationJob))
#9 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Bus\Dispatcher.php(139): Illuminate\Pipeline\Pipeline->then(Object(Closure))
#10 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Queue\CallQueuedHandler.php(134): Illuminate\Bus\Dispatcher->dispatchNow(Object(App\Jobs\ProcessQuotationJob), false)
#11 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php(180): Illuminate\Queue\CallQueuedHandler->Illuminate\Queue\{closure}(Object(App\Jobs\ProcessQuotationJob))
#12 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php(137): Illuminate\Pipeline\Pipeline->Illuminate\Pipeline\{closure}(Object(App\Jobs\ProcessQuotationJob))
#13 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Queue\CallQueuedHandler.php(127): Illuminate\Pipeline\Pipeline->then(Object(Closure))
#14 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Queue\CallQueuedHandler.php(68): Illuminate\Queue\CallQueuedHandler->dispatchThroughMiddleware(Object(Illuminate\Queue\Jobs\DatabaseJob), Object(App\Jobs\ProcessQuotationJob))
#15 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Queue\Jobs\Job.php(102): Illuminate\Queue\CallQueuedHandler->call(Object(Illuminate\Queue\Jobs\DatabaseJob), Array)
#16 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Queue\Worker.php(494): Illuminate\Queue\Jobs\Job->fire()
#17 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Queue\Worker.php(442): Illuminate\Queue\Worker->process(''database'', Object(Illuminate\Queue\Jobs\DatabaseJob), Object(Illuminate\Queue\WorkerOptions))
#18 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Queue\Worker.php(208): Illuminate\Queue\Worker->runJob(Object(Illuminate\Queue\Jobs\DatabaseJob), ''database'', Object(Illuminate\Queue\WorkerOptions))
#19 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Queue\Console\WorkCommand.php(148): Illuminate\Queue\Worker->daemon(''database'', ''default'', Object(Illuminate\Queue\WorkerOptions))
#20 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Queue\Console\WorkCommand.php(131): Illuminate\Queue\Console\WorkCommand->runWorker(''database'', ''default'')
#21 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Container\BoundMethod.php(36): Illuminate\Queue\Console\WorkCommand->handle()
#22 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Container\Util.php(43): Illuminate\Container\BoundMethod::Illuminate\Container\{closure}()
#23 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Container\BoundMethod.php(96): Illuminate\Container\Util::unwrapIfClosure(Object(Closure))
#24 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Container\BoundMethod.php(35): Illuminate\Container\BoundMethod::callBoundMethod(Object(Illuminate\Foundation\Application), Array, Object(Closure))
#25 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Container\Container.php(799): Illuminate\Container\BoundMethod::call(Object(Illuminate\Foundation\Application), Array, Array, NULL)
#26 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Console\Command.php(280): Illuminate\Container\Container->call(Array)
#27 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\symfony\console\Command\Command.php(341): Illuminate\Console\Command->execute(Object(Symfony\Component\Console\Input\ArgvInput), Object(Illuminate\Console\OutputStyle))
#28 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Console\Command.php(249): Symfony\Component\Console\Command\Command->run(Object(Symfony\Component\Console\Input\ArgvInput), Object(Illuminate\Console\OutputStyle))
#29 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\symfony\console\Application.php(1117): Illuminate\Console\Command->run(Object(Symfony\Component\Console\Input\ArgvInput), Object(Symfony\Component\Console\Output\ConsoleOutput))
#30 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\symfony\console\Application.php(356): Symfony\Component\Console\Application->doRunCommand(Object(Illuminate\Queue\Console\WorkCommand), Object(Symfony\Component\Console\Input\ArgvInput), Object(Symfony\Component\Console\Output\ConsoleOutput))
#31 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\symfony\console\Application.php(195): Symfony\Component\Console\Application->doRun(Object(Symfony\Component\Console\Input\ArgvInput), Object(Symfony\Component\Console\Output\ConsoleOutput))
#32 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Foundation\Console\Kernel.php(198): Symfony\Component\Console\Application->run(Object(Symfony\Component\Console\Input\ArgvInput), Object(Symfony\Component\Console\Output\ConsoleOutput))
#33 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Foundation\Application.php(1235): Illuminate\Foundation\Console\Kernel->handle(Object(Symfony\Component\Console\Input\ArgvInput), Object(Symfony\Component\Console\Output\ConsoleOutput))
#34 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\artisan(16): Illuminate\Foundation\Application->handleCommand(Object(Symfony\Component\Console\Input\ArgvInput))
#35 {main}','2026-06-01 18:17:43');
INSERT INTO "failed_jobs" VALUES (6,'f7875a2a-2689-44af-b62d-4e37694fa2d3','database','default','{"uuid":"f7875a2a-2689-44af-b62d-4e37694fa2d3","displayName":"App\\Jobs\\ProcessQuotationJob","job":"Illuminate\\Queue\\CallQueuedHandler@call","maxTries":2,"maxExceptions":null,"failOnTimeout":false,"backoff":null,"timeout":120,"retryUntil":null,"deleteWhenMissingModels":false,"data":{"commandName":"App\\Jobs\\ProcessQuotationJob","command":"O:28:\"App\\Jobs\\ProcessQuotationJob\":1:{s:41:\"\u0000App\\Jobs\\ProcessQuotationJob\u0000quotationId\";i:43;}","batchId":null},"createdAt":1780337812,"delay":null}','Exception: La IA de Gemini está experimentando alta demanda o no pudo procesar el archivo en este momento. Por favor, intenta de nuevo o continúa manualmente. in C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\app\Services\DocumentParsers\VisionParserService.php:38
Stack trace:
#0 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\app\Jobs\ProcessQuotationJob.php(52): App\Services\DocumentParsers\VisionParserService->parse(''C:\\Users\\HP\\Doc...'')
#1 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Container\BoundMethod.php(36): App\Jobs\ProcessQuotationJob->handle(Object(App\Services\DocumentParsers\DocumentParserFactory))
#2 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Container\Util.php(43): Illuminate\Container\BoundMethod::Illuminate\Container\{closure}()
#3 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Container\BoundMethod.php(96): Illuminate\Container\Util::unwrapIfClosure(Object(Closure))
#4 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Container\BoundMethod.php(35): Illuminate\Container\BoundMethod::callBoundMethod(Object(Illuminate\Foundation\Application), Array, Object(Closure))
#5 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Container\Container.php(799): Illuminate\Container\BoundMethod::call(Object(Illuminate\Foundation\Application), Array, Array, NULL)
#6 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Bus\Dispatcher.php(135): Illuminate\Container\Container->call(Array)
#7 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php(180): Illuminate\Bus\Dispatcher->Illuminate\Bus\{closure}(Object(App\Jobs\ProcessQuotationJob))
#8 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php(137): Illuminate\Pipeline\Pipeline->Illuminate\Pipeline\{closure}(Object(App\Jobs\ProcessQuotationJob))
#9 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Bus\Dispatcher.php(139): Illuminate\Pipeline\Pipeline->then(Object(Closure))
#10 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Queue\CallQueuedHandler.php(134): Illuminate\Bus\Dispatcher->dispatchNow(Object(App\Jobs\ProcessQuotationJob), false)
#11 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php(180): Illuminate\Queue\CallQueuedHandler->Illuminate\Queue\{closure}(Object(App\Jobs\ProcessQuotationJob))
#12 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php(137): Illuminate\Pipeline\Pipeline->Illuminate\Pipeline\{closure}(Object(App\Jobs\ProcessQuotationJob))
#13 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Queue\CallQueuedHandler.php(127): Illuminate\Pipeline\Pipeline->then(Object(Closure))
#14 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Queue\CallQueuedHandler.php(68): Illuminate\Queue\CallQueuedHandler->dispatchThroughMiddleware(Object(Illuminate\Queue\Jobs\DatabaseJob), Object(App\Jobs\ProcessQuotationJob))
#15 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Queue\Jobs\Job.php(102): Illuminate\Queue\CallQueuedHandler->call(Object(Illuminate\Queue\Jobs\DatabaseJob), Array)
#16 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Queue\Worker.php(494): Illuminate\Queue\Jobs\Job->fire()
#17 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Queue\Worker.php(442): Illuminate\Queue\Worker->process(''database'', Object(Illuminate\Queue\Jobs\DatabaseJob), Object(Illuminate\Queue\WorkerOptions))
#18 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Queue\Worker.php(208): Illuminate\Queue\Worker->runJob(Object(Illuminate\Queue\Jobs\DatabaseJob), ''database'', Object(Illuminate\Queue\WorkerOptions))
#19 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Queue\Console\WorkCommand.php(148): Illuminate\Queue\Worker->daemon(''database'', ''default'', Object(Illuminate\Queue\WorkerOptions))
#20 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Queue\Console\WorkCommand.php(131): Illuminate\Queue\Console\WorkCommand->runWorker(''database'', ''default'')
#21 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Container\BoundMethod.php(36): Illuminate\Queue\Console\WorkCommand->handle()
#22 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Container\Util.php(43): Illuminate\Container\BoundMethod::Illuminate\Container\{closure}()
#23 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Container\BoundMethod.php(96): Illuminate\Container\Util::unwrapIfClosure(Object(Closure))
#24 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Container\BoundMethod.php(35): Illuminate\Container\BoundMethod::callBoundMethod(Object(Illuminate\Foundation\Application), Array, Object(Closure))
#25 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Container\Container.php(799): Illuminate\Container\BoundMethod::call(Object(Illuminate\Foundation\Application), Array, Array, NULL)
#26 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Console\Command.php(280): Illuminate\Container\Container->call(Array)
#27 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\symfony\console\Command\Command.php(341): Illuminate\Console\Command->execute(Object(Symfony\Component\Console\Input\ArgvInput), Object(Illuminate\Console\OutputStyle))
#28 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Console\Command.php(249): Symfony\Component\Console\Command\Command->run(Object(Symfony\Component\Console\Input\ArgvInput), Object(Illuminate\Console\OutputStyle))
#29 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\symfony\console\Application.php(1117): Illuminate\Console\Command->run(Object(Symfony\Component\Console\Input\ArgvInput), Object(Symfony\Component\Console\Output\ConsoleOutput))
#30 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\symfony\console\Application.php(356): Symfony\Component\Console\Application->doRunCommand(Object(Illuminate\Queue\Console\WorkCommand), Object(Symfony\Component\Console\Input\ArgvInput), Object(Symfony\Component\Console\Output\ConsoleOutput))
#31 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\symfony\console\Application.php(195): Symfony\Component\Console\Application->doRun(Object(Symfony\Component\Console\Input\ArgvInput), Object(Symfony\Component\Console\Output\ConsoleOutput))
#32 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Foundation\Console\Kernel.php(198): Symfony\Component\Console\Application->run(Object(Symfony\Component\Console\Input\ArgvInput), Object(Symfony\Component\Console\Output\ConsoleOutput))
#33 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Foundation\Application.php(1235): Illuminate\Foundation\Console\Kernel->handle(Object(Symfony\Component\Console\Input\ArgvInput), Object(Symfony\Component\Console\Output\ConsoleOutput))
#34 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\artisan(16): Illuminate\Foundation\Application->handleCommand(Object(Symfony\Component\Console\Input\ArgvInput))
#35 {main}','2026-06-01 18:18:24');
INSERT INTO "failed_jobs" VALUES (7,'5ed68e87-d33d-4c99-8a28-a8b37b127907','database','default','{"uuid":"5ed68e87-d33d-4c99-8a28-a8b37b127907","displayName":"App\\Jobs\\ProcessQuotationJob","job":"Illuminate\\Queue\\CallQueuedHandler@call","maxTries":2,"maxExceptions":null,"failOnTimeout":false,"backoff":null,"timeout":120,"retryUntil":null,"deleteWhenMissingModels":false,"data":{"commandName":"App\\Jobs\\ProcessQuotationJob","command":"O:28:\"App\\Jobs\\ProcessQuotationJob\":1:{s:41:\"\u0000App\\Jobs\\ProcessQuotationJob\u0000quotationId\";i:45;}","batchId":null},"createdAt":1780338175,"delay":null}','Exception: La IA de Gemini está experimentando alta demanda o no pudo procesar el archivo en este momento. Por favor, intenta de nuevo o continúa manualmente. in C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\app\Services\DocumentParsers\VisionParserService.php:38
Stack trace:
#0 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\app\Jobs\ProcessQuotationJob.php(52): App\Services\DocumentParsers\VisionParserService->parse(''C:\\Users\\HP\\Doc...'')
#1 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Container\BoundMethod.php(36): App\Jobs\ProcessQuotationJob->handle(Object(App\Services\DocumentParsers\DocumentParserFactory))
#2 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Container\Util.php(43): Illuminate\Container\BoundMethod::Illuminate\Container\{closure}()
#3 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Container\BoundMethod.php(96): Illuminate\Container\Util::unwrapIfClosure(Object(Closure))
#4 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Container\BoundMethod.php(35): Illuminate\Container\BoundMethod::callBoundMethod(Object(Illuminate\Foundation\Application), Array, Object(Closure))
#5 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Container\Container.php(799): Illuminate\Container\BoundMethod::call(Object(Illuminate\Foundation\Application), Array, Array, NULL)
#6 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Bus\Dispatcher.php(135): Illuminate\Container\Container->call(Array)
#7 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php(180): Illuminate\Bus\Dispatcher->Illuminate\Bus\{closure}(Object(App\Jobs\ProcessQuotationJob))
#8 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php(137): Illuminate\Pipeline\Pipeline->Illuminate\Pipeline\{closure}(Object(App\Jobs\ProcessQuotationJob))
#9 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Bus\Dispatcher.php(139): Illuminate\Pipeline\Pipeline->then(Object(Closure))
#10 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Queue\CallQueuedHandler.php(134): Illuminate\Bus\Dispatcher->dispatchNow(Object(App\Jobs\ProcessQuotationJob), false)
#11 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php(180): Illuminate\Queue\CallQueuedHandler->Illuminate\Queue\{closure}(Object(App\Jobs\ProcessQuotationJob))
#12 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php(137): Illuminate\Pipeline\Pipeline->Illuminate\Pipeline\{closure}(Object(App\Jobs\ProcessQuotationJob))
#13 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Queue\CallQueuedHandler.php(127): Illuminate\Pipeline\Pipeline->then(Object(Closure))
#14 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Queue\CallQueuedHandler.php(68): Illuminate\Queue\CallQueuedHandler->dispatchThroughMiddleware(Object(Illuminate\Queue\Jobs\DatabaseJob), Object(App\Jobs\ProcessQuotationJob))
#15 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Queue\Jobs\Job.php(102): Illuminate\Queue\CallQueuedHandler->call(Object(Illuminate\Queue\Jobs\DatabaseJob), Array)
#16 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Queue\Worker.php(494): Illuminate\Queue\Jobs\Job->fire()
#17 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Queue\Worker.php(442): Illuminate\Queue\Worker->process(''database'', Object(Illuminate\Queue\Jobs\DatabaseJob), Object(Illuminate\Queue\WorkerOptions))
#18 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Queue\Worker.php(208): Illuminate\Queue\Worker->runJob(Object(Illuminate\Queue\Jobs\DatabaseJob), ''database'', Object(Illuminate\Queue\WorkerOptions))
#19 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Queue\Console\WorkCommand.php(148): Illuminate\Queue\Worker->daemon(''database'', ''default'', Object(Illuminate\Queue\WorkerOptions))
#20 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Queue\Console\WorkCommand.php(131): Illuminate\Queue\Console\WorkCommand->runWorker(''database'', ''default'')
#21 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Container\BoundMethod.php(36): Illuminate\Queue\Console\WorkCommand->handle()
#22 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Container\Util.php(43): Illuminate\Container\BoundMethod::Illuminate\Container\{closure}()
#23 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Container\BoundMethod.php(96): Illuminate\Container\Util::unwrapIfClosure(Object(Closure))
#24 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Container\BoundMethod.php(35): Illuminate\Container\BoundMethod::callBoundMethod(Object(Illuminate\Foundation\Application), Array, Object(Closure))
#25 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Container\Container.php(799): Illuminate\Container\BoundMethod::call(Object(Illuminate\Foundation\Application), Array, Array, NULL)
#26 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Console\Command.php(280): Illuminate\Container\Container->call(Array)
#27 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\symfony\console\Command\Command.php(341): Illuminate\Console\Command->execute(Object(Symfony\Component\Console\Input\ArgvInput), Object(Illuminate\Console\OutputStyle))
#28 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Console\Command.php(249): Symfony\Component\Console\Command\Command->run(Object(Symfony\Component\Console\Input\ArgvInput), Object(Illuminate\Console\OutputStyle))
#29 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\symfony\console\Application.php(1117): Illuminate\Console\Command->run(Object(Symfony\Component\Console\Input\ArgvInput), Object(Symfony\Component\Console\Output\ConsoleOutput))
#30 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\symfony\console\Application.php(356): Symfony\Component\Console\Application->doRunCommand(Object(Illuminate\Queue\Console\WorkCommand), Object(Symfony\Component\Console\Input\ArgvInput), Object(Symfony\Component\Console\Output\ConsoleOutput))
#31 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\symfony\console\Application.php(195): Symfony\Component\Console\Application->doRun(Object(Symfony\Component\Console\Input\ArgvInput), Object(Symfony\Component\Console\Output\ConsoleOutput))
#32 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Foundation\Console\Kernel.php(198): Symfony\Component\Console\Application->run(Object(Symfony\Component\Console\Input\ArgvInput), Object(Symfony\Component\Console\Output\ConsoleOutput))
#33 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Foundation\Application.php(1235): Illuminate\Foundation\Console\Kernel->handle(Object(Symfony\Component\Console\Input\ArgvInput), Object(Symfony\Component\Console\Output\ConsoleOutput))
#34 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\artisan(16): Illuminate\Foundation\Application->handleCommand(Object(Symfony\Component\Console\Input\ArgvInput))
#35 {main}','2026-06-01 18:24:25');
INSERT INTO "failed_jobs" VALUES (8,'db2751a0-d28e-4370-8aa6-18f7c05b7bbd','database','default','{"uuid":"db2751a0-d28e-4370-8aa6-18f7c05b7bbd","displayName":"App\\Jobs\\ProcessQuotationJob","job":"Illuminate\\Queue\\CallQueuedHandler@call","maxTries":2,"maxExceptions":null,"failOnTimeout":false,"backoff":null,"timeout":120,"retryUntil":null,"deleteWhenMissingModels":false,"data":{"commandName":"App\\Jobs\\ProcessQuotationJob","command":"O:28:\"App\\Jobs\\ProcessQuotationJob\":1:{s:41:\"\u0000App\\Jobs\\ProcessQuotationJob\u0000quotationId\";i:46;}","batchId":null},"createdAt":1780339265,"delay":null}','Exception: La IA de Gemini está experimentando alta demanda o no pudo procesar el archivo en este momento. Por favor, intenta de nuevo o continúa manualmente. in C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\app\Services\DocumentParsers\VisionParserService.php:38
Stack trace:
#0 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\app\Jobs\ProcessQuotationJob.php(52): App\Services\DocumentParsers\VisionParserService->parse(''C:\\Users\\HP\\Doc...'')
#1 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Container\BoundMethod.php(36): App\Jobs\ProcessQuotationJob->handle(Object(App\Services\DocumentParsers\DocumentParserFactory))
#2 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Container\Util.php(43): Illuminate\Container\BoundMethod::Illuminate\Container\{closure}()
#3 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Container\BoundMethod.php(96): Illuminate\Container\Util::unwrapIfClosure(Object(Closure))
#4 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Container\BoundMethod.php(35): Illuminate\Container\BoundMethod::callBoundMethod(Object(Illuminate\Foundation\Application), Array, Object(Closure))
#5 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Container\Container.php(799): Illuminate\Container\BoundMethod::call(Object(Illuminate\Foundation\Application), Array, Array, NULL)
#6 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Bus\Dispatcher.php(135): Illuminate\Container\Container->call(Array)
#7 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php(180): Illuminate\Bus\Dispatcher->Illuminate\Bus\{closure}(Object(App\Jobs\ProcessQuotationJob))
#8 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php(137): Illuminate\Pipeline\Pipeline->Illuminate\Pipeline\{closure}(Object(App\Jobs\ProcessQuotationJob))
#9 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Bus\Dispatcher.php(139): Illuminate\Pipeline\Pipeline->then(Object(Closure))
#10 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Queue\CallQueuedHandler.php(134): Illuminate\Bus\Dispatcher->dispatchNow(Object(App\Jobs\ProcessQuotationJob), false)
#11 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php(180): Illuminate\Queue\CallQueuedHandler->Illuminate\Queue\{closure}(Object(App\Jobs\ProcessQuotationJob))
#12 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php(137): Illuminate\Pipeline\Pipeline->Illuminate\Pipeline\{closure}(Object(App\Jobs\ProcessQuotationJob))
#13 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Queue\CallQueuedHandler.php(127): Illuminate\Pipeline\Pipeline->then(Object(Closure))
#14 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Queue\CallQueuedHandler.php(68): Illuminate\Queue\CallQueuedHandler->dispatchThroughMiddleware(Object(Illuminate\Queue\Jobs\DatabaseJob), Object(App\Jobs\ProcessQuotationJob))
#15 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Queue\Jobs\Job.php(102): Illuminate\Queue\CallQueuedHandler->call(Object(Illuminate\Queue\Jobs\DatabaseJob), Array)
#16 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Queue\Worker.php(494): Illuminate\Queue\Jobs\Job->fire()
#17 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Queue\Worker.php(442): Illuminate\Queue\Worker->process(''database'', Object(Illuminate\Queue\Jobs\DatabaseJob), Object(Illuminate\Queue\WorkerOptions))
#18 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Queue\Worker.php(208): Illuminate\Queue\Worker->runJob(Object(Illuminate\Queue\Jobs\DatabaseJob), ''database'', Object(Illuminate\Queue\WorkerOptions))
#19 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Queue\Console\WorkCommand.php(148): Illuminate\Queue\Worker->daemon(''database'', ''default'', Object(Illuminate\Queue\WorkerOptions))
#20 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Queue\Console\WorkCommand.php(131): Illuminate\Queue\Console\WorkCommand->runWorker(''database'', ''default'')
#21 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Container\BoundMethod.php(36): Illuminate\Queue\Console\WorkCommand->handle()
#22 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Container\Util.php(43): Illuminate\Container\BoundMethod::Illuminate\Container\{closure}()
#23 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Container\BoundMethod.php(96): Illuminate\Container\Util::unwrapIfClosure(Object(Closure))
#24 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Container\BoundMethod.php(35): Illuminate\Container\BoundMethod::callBoundMethod(Object(Illuminate\Foundation\Application), Array, Object(Closure))
#25 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Container\Container.php(799): Illuminate\Container\BoundMethod::call(Object(Illuminate\Foundation\Application), Array, Array, NULL)
#26 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Console\Command.php(280): Illuminate\Container\Container->call(Array)
#27 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\symfony\console\Command\Command.php(341): Illuminate\Console\Command->execute(Object(Symfony\Component\Console\Input\ArgvInput), Object(Illuminate\Console\OutputStyle))
#28 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Console\Command.php(249): Symfony\Component\Console\Command\Command->run(Object(Symfony\Component\Console\Input\ArgvInput), Object(Illuminate\Console\OutputStyle))
#29 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\symfony\console\Application.php(1117): Illuminate\Console\Command->run(Object(Symfony\Component\Console\Input\ArgvInput), Object(Symfony\Component\Console\Output\ConsoleOutput))
#30 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\symfony\console\Application.php(356): Symfony\Component\Console\Application->doRunCommand(Object(Illuminate\Queue\Console\WorkCommand), Object(Symfony\Component\Console\Input\ArgvInput), Object(Symfony\Component\Console\Output\ConsoleOutput))
#31 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\symfony\console\Application.php(195): Symfony\Component\Console\Application->doRun(Object(Symfony\Component\Console\Input\ArgvInput), Object(Symfony\Component\Console\Output\ConsoleOutput))
#32 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Foundation\Console\Kernel.php(198): Symfony\Component\Console\Application->run(Object(Symfony\Component\Console\Input\ArgvInput), Object(Symfony\Component\Console\Output\ConsoleOutput))
#33 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\vendor\laravel\framework\src\Illuminate\Foundation\Application.php(1235): Illuminate\Foundation\Console\Kernel->handle(Object(Symfony\Component\Console\Input\ArgvInput), Object(Symfony\Component\Console\Output\ConsoleOutput))
#34 C:\Users\HP\Documents\Sistemas\Sistemas-Muulsinik\artisan(16): Illuminate\Foundation\Application->handleCommand(Object(Symfony\Component\Console\Input\ArgvInput))
#35 {main}','2026-06-01 18:41:37');
INSERT INTO "measures" VALUES (1,'Pieza','pza','2026-05-14 07:11:04','2026-05-14 07:11:04');
INSERT INTO "measures" VALUES (2,'Metro Lineal','m','2026-05-14 07:11:04','2026-05-14 07:11:04');
INSERT INTO "measures" VALUES (3,'Tambor','tambor','2026-05-14 08:58:50','2026-05-14 08:58:50');
INSERT INTO "measures" VALUES (5,'Servicio','serv','2026-05-31 21:46:57','2026-05-31 21:46:57');
INSERT INTO "measures" VALUES (6,'Kilogramo','kg','2026-06-01 07:13:11','2026-06-01 07:13:11');
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
INSERT INTO "migrations" VALUES (33,'2026_05_19_000001_drop_orphaned_tables_and_columns',6);
INSERT INTO "migrations" VALUES (35,'2026_05_29_224100_add_discount_to_requisition_items',7);
INSERT INTO "migrations" VALUES (36,'2026_05_30_075700_add_discount_fields_to_requisition_items',8);
INSERT INTO "migrations" VALUES (37,'2026_05_30_000000_add_discount_fields',9);
INSERT INTO "migrations" VALUES (38,'2026_05_30_163900_add_discount_fields_to_requisition_items',10);
INSERT INTO "migrations" VALUES (39,'2026_06_05_020000_add_search_and_relationship_indexes',11);
INSERT INTO "migrations" VALUES (40,'2026_06_06_153511_add_draft_state_to_quotations_table',12);
INSERT INTO "migrations" VALUES (41,'2026_06_10_214203_create_requisition_activities_table',13);
INSERT INTO "notifications" VALUES ('5e25c033-ee7c-4d7e-9ea6-683ce1f7b14a','App\Notifications\QuotationProcessed','App\Models\User',1,'{"type":"quotation_processed","title":"Cotizaci\u00f3n procesada exitosamente","message":"El archivo ''WhatsApp Image 2026-04-17 at 4.29.57 PM.jpeg'' ha sido procesado","icon":"file-check","color":"success","action_url":"http:\/\/localhost\/requisiciones","action_text":"Ver","quotation_id":1,"quotation_filename":"WhatsApp Image 2026-04-17 at 4.29.57 PM.jpeg"}','2026-06-06 04:34:48','2026-06-06 04:33:29','2026-06-06 04:34:48');
INSERT INTO "notifications" VALUES ('a2b627df-4eee-4293-b642-1151668eaa99','App\Notifications\RequisitionPendingApproval','App\Models\User',1,'{"type":"requisition_pending","title":"Requisici\u00f3n pendiente de aprobaci\u00f3n","message":"La requisici\u00f3n CEN1-REQ0017 requiere tu aprobaci\u00f3n","icon":"clipboard-list","color":"primary","action_url":"http:\/\/127.0.0.1:8000\/requisiciones","action_text":"Ver requisiciones","requisition_id":17,"requisition_number":"CEN1-REQ0017"}','2026-06-06 04:35:37','2026-06-06 04:35:13','2026-06-06 04:35:37');
INSERT INTO "notifications" VALUES ('ac89051b-0a45-4d2c-8586-a2f05d6b3dfa','App\Notifications\RequisitionPendingApproval','App\Models\User',1,'{"type":"requisition_pending","title":"Requisici\u00f3n pendiente de aprobaci\u00f3n","message":"La requisici\u00f3n CEN1-REQ0019 requiere tu aprobaci\u00f3n","icon":"clipboard-list","color":"primary","action_url":"http:\/\/127.0.0.1:8000\/requisiciones","action_text":"Ver requisiciones","requisition_id":19,"requisition_number":"CEN1-REQ0019"}','2026-06-06 04:42:56','2026-06-06 04:36:33','2026-06-06 04:42:56');
INSERT INTO "notifications" VALUES ('c71f20bb-8306-4163-9391-f2d3e97b245b','App\Notifications\RequisitionPendingApproval','App\Models\User',1,'{"type":"requisition_pending","title":"Requisici\u00f3n pendiente de aprobaci\u00f3n","message":"La requisici\u00f3n COR3-REQ0016 requiere tu aprobaci\u00f3n","icon":"clipboard-list","color":"primary","action_url":"http:\/\/127.0.0.1:8000\/requisiciones","action_text":"Ver requisiciones","requisition_id":16,"requisition_number":"COR3-REQ0016"}','2026-06-06 04:42:56','2026-06-06 04:36:40','2026-06-06 04:42:56');
INSERT INTO "notifications" VALUES ('7158f9f9-0f9a-412e-bc9c-dfc9ad4ffa05','App\Notifications\RequisitionPendingApproval','App\Models\User',1,'{"type":"requisition_pending","title":"Requisici\u00f3n pendiente de aprobaci\u00f3n","message":"La requisici\u00f3n CEN1-REQ0015 requiere tu aprobaci\u00f3n","icon":"clipboard-list","color":"primary","action_url":"http:\/\/127.0.0.1:8000\/requisiciones","action_text":"Ver requisiciones","requisition_id":15,"requisition_number":"CEN1-REQ0015"}','2026-06-06 04:42:56','2026-06-06 04:36:44','2026-06-06 04:42:56');
INSERT INTO "notifications" VALUES ('48613001-e0fb-4f0f-a60c-07799d76fae2','App\Notifications\RequisitionPendingApproval','App\Models\User',1,'{"type":"requisition_pending","title":"Requisici\u00f3n pendiente de aprobaci\u00f3n","message":"La requisici\u00f3n COR3-REQ0014 requiere tu aprobaci\u00f3n","icon":"clipboard-list","color":"primary","action_url":"http:\/\/127.0.0.1:8000\/requisiciones","action_text":"Ver requisiciones","requisition_id":14,"requisition_number":"COR3-REQ0014"}','2026-06-06 04:42:56','2026-06-06 04:36:47','2026-06-06 04:42:56');
INSERT INTO "notifications" VALUES ('42aa7845-64c9-4ae1-a691-7f8717970d5d','App\Notifications\RequisitionStatusChanged','App\Models\User',1,'{"type":"requisition_status","title":"Requisici\u00f3n Rechazada","message":"Administrador cambi\u00f3 el estado de CEN1-REQ0019 a Rechazada","icon":"x-circle","color":"danger","action_url":"http:\/\/127.0.0.1:8000\/requisiciones","action_text":"Ver detalle","requisition_id":19,"requisition_number":"CEN1-REQ0019","old_status":"pendiente","new_status":"rechazada"}','2026-06-10 23:39:38','2026-06-07 15:07:55','2026-06-10 23:39:38');
INSERT INTO "notifications" VALUES ('18968267-353e-4219-9ab5-e54fee0f74fd','App\Notifications\RequisitionStatusChanged','App\Models\User',1,'{"type":"requisition_status","title":"Requisici\u00f3n Aprobada","message":"Administrador cambi\u00f3 el estado de COR3-REQ0014 a Aprobada","icon":"check-circle","color":"success","action_url":"http:\/\/127.0.0.1:8000\/requisiciones","action_text":"Ver detalle","requisition_id":14,"requisition_number":"COR3-REQ0014","old_status":"pendiente","new_status":"aprobada"}','2026-06-10 23:39:38','2026-06-07 17:32:41','2026-06-10 23:39:38');
INSERT INTO "notifications" VALUES ('b6375fad-6de5-49f6-b361-2834f2d3277f','App\Notifications\RequisitionStatusChanged','App\Models\User',1,'{"type":"requisition_status","title":"Requisici\u00f3n Aprobada","message":"Administrador cambi\u00f3 el estado de CEN1-REQ0015 a Aprobada","icon":"check-circle","color":"success","action_url":"http:\/\/127.0.0.1:8000\/requisiciones","action_text":"Ver detalle","requisition_id":15,"requisition_number":"CEN1-REQ0015","old_status":"pendiente","new_status":"aprobada"}','2026-06-10 23:39:38','2026-06-07 17:32:41','2026-06-10 23:39:38');
INSERT INTO "notifications" VALUES ('d7d7bde3-bddb-4b15-925b-12044b1bafc3','App\Notifications\RequisitionStatusChanged','App\Models\User',1,'{"type":"requisition_status","title":"Requisici\u00f3n Aprobada","message":"Administrador cambi\u00f3 el estado de COR3-REQ0016 a Aprobada","icon":"check-circle","color":"success","action_url":"http:\/\/127.0.0.1:8000\/requisiciones","action_text":"Ver detalle","requisition_id":16,"requisition_number":"COR3-REQ0016","old_status":"pendiente","new_status":"aprobada"}','2026-06-10 23:39:38','2026-06-07 17:32:41','2026-06-10 23:39:38');
INSERT INTO "notifications" VALUES ('916e3bc7-4020-4391-b2b1-00153ba7aee0','App\Notifications\RequisitionStatusChanged','App\Models\User',1,'{"type":"requisition_status","title":"Requisici\u00f3n Aprobada","message":"Administrador cambi\u00f3 el estado de CEN1-REQ0017 a Aprobada","icon":"check-circle","color":"success","action_url":"http:\/\/127.0.0.1:8000\/requisiciones","action_text":"Ver detalle","requisition_id":17,"requisition_number":"CEN1-REQ0017","old_status":"pendiente","new_status":"aprobada"}','2026-06-10 23:39:38','2026-06-07 17:32:41','2026-06-10 23:39:38');
INSERT INTO "notifications" VALUES ('5704bdcc-d914-4580-a55f-1a232033a3e0','App\Notifications\QuotationProcessed','App\Models\User',1,'{"type":"quotation_processed","title":"Cotizaci\u00f3n procesada exitosamente","message":"El archivo ''WhatsApp Image 2026-06-01 at 12.04.43 PM (1).jpeg'' ha sido procesado","icon":"file-check","color":"success","action_url":"http:\/\/localhost\/requisiciones\/subir-cotizacion?id=52","action_text":"Ver","quotation_id":52,"quotation_filename":"WhatsApp Image 2026-06-01 at 12.04.43 PM (1).jpeg"}','2026-06-10 23:39:38','2026-06-07 18:23:26','2026-06-10 23:39:38');
INSERT INTO "notifications" VALUES ('9fe9747e-298c-499d-8d2a-ef908dd3adaa','App\Notifications\ExportCompleted','App\Models\User',1,'{"type":"export_completed","title":"Exportaci\u00f3n finalizada","message":"Tus requisiciones en formato PDF est\u00e1n listas para descargar.","icon":"download","color":"success","action_url":"\/storage\/exports\/Requisiciones_Export_20260611_061704.zip","action_text":"Descargar","file_name":"Requisiciones_Export_20260611_061704.zip"}','2026-06-11 06:17:25','2026-06-11 06:17:13','2026-06-11 06:17:25');
INSERT INTO "notifications" VALUES ('1af9ae92-9752-4746-a655-7d4f4d363890','App\Notifications\ExportCompleted','App\Models\User',1,'{"type":"export_completed","title":"Exportaci\u00f3n finalizada","message":"Tus requisiciones en formato PDF est\u00e1n listas para descargar.","icon":"download","color":"success","action_url":"\/storage\/exports\/Requisiciones_Export_20260611_061928.zip","action_text":"Descargar","file_name":"Requisiciones_Export_20260611_061928.zip"}','2026-06-11 06:20:20','2026-06-11 06:19:31','2026-06-11 06:20:20');
INSERT INTO "notifications" VALUES ('b8a70935-6b95-48f4-9025-39f173833447','App\Notifications\ExportCompleted','App\Models\User',1,'{"type":"export_completed","title":"Exportaci\u00f3n finalizada","message":"Tus requisiciones en formato PDF est\u00e1n listas para descargar.","icon":"download","color":"success","action_url":"\/storage\/exports\/Requisiciones_Export_20260611_063915.zip","action_text":"Descargar","file_name":"Requisiciones_Export_20260611_063915.zip"}','2026-06-11 06:39:29','2026-06-11 06:39:23','2026-06-11 06:39:29');
INSERT INTO "notifications" VALUES ('0aa80963-3da9-41da-87e6-f55952fc2eeb','App\Notifications\QuotationProcessed','App\Models\User',1,'{"type":"quotation_processed","title":"Cotizaci\u00f3n procesada exitosamente","message":"El archivo ''WhatsApp Image 2026-04-17 at 3.56.44 PM.jpeg'' ha sido procesado","icon":"file-check","color":"success","action_url":"\/requisiciones\/subir-cotizacion?id=53","action_text":"Ver","quotation_id":53,"quotation_filename":"WhatsApp Image 2026-04-17 at 3.56.44 PM.jpeg"}','2026-06-11 10:05:38','2026-06-11 07:03:10','2026-06-11 10:05:38');
INSERT INTO "products" VALUES (1,'REMATE VENTILA SANIT 50',NULL,'2026-05-14 07:11:04','2026-05-30 01:16:18','remate ventila sanit 50',2,1);
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
INSERT INTO "products" VALUES (26,'ABRAZADERA UÑA GALV 200 013 UCR12 PARA CPVC/COBRE',NULL,'2026-05-23 04:35:53','2026-05-23 04:35:53','abrazadera una galv 200 013 ucr12 para cpvc/cobre',1,9);
INSERT INTO "products" VALUES (27,'VARILLA 3, DE 3/8" 12 MTS.',NULL,'2026-05-31 21:46:57','2026-05-31 21:46:57','varilla 3 de 3/8 12 mts',1,1);
INSERT INTO "products" VALUES (28,'SERVICIO DE ENTREGA DENTRO DE LA CD. DE MERIDA',NULL,'2026-05-31 21:46:57','2026-05-31 21:46:57','servicio de entrega dentro de la cd de merida',5,11);
INSERT INTO "products" VALUES (29,'REMA',NULL,'2026-06-01 07:13:11','2026-06-01 07:13:11','rema',2,1);
INSERT INTO "products" VALUES (30,'TUBO SAN NORMA 110 EXTRA',NULL,'2026-06-01 07:13:11','2026-06-01 07:13:11','tubo san norma 110 extra',2,6);
INSERT INTO "products" VALUES (31,'CLAVOS DE ACERO 2" NUEVOS',NULL,'2026-06-01 07:13:11','2026-06-01 07:13:11','clavos de acero 2 nuevos',6,3);
INSERT INTO "products" VALUES (32,'PEGAZULEJO PISOS Y MARMOL GRIS 20KG CEMIX',NULL,'2026-06-04 04:00:26','2026-06-04 04:00:26','pegazulejo pisos y marmol gris 20kg cemix',1,3);
INSERT INTO "products" VALUES (33,'AG 14 8 A TAQUETE DE PLASTICO 006',NULL,'2026-06-06 04:06:18','2026-06-06 04:06:18','ag 14 8 a taquete de plastico 006',1,11);
INSERT INTO "projects" VALUES (1,'Centrovisión','','Constructora Muulsinik',1000000,'2026-05-01 00:00:00','2026-07-29 00:00:00','activo','2026-05-14 07:09:39','2026-05-14 07:09:39');
INSERT INTO "projects" VALUES (3,'Coral','','Cliente Particular',100000,'2026-05-18 00:00:00','2026-06-18 00:00:00','activo','2026-05-19 00:50:06','2026-05-19 00:50:06');
INSERT INTO "projects" VALUES (4,'ggggggggggggggggggggggggggggggggggggggggggggggggggggggggggggggggggggggggggggggggggggggggggggggggggggggg','','ggg',555555,'2026-06-24 00:00:00','2026-06-25 00:00:00','activo','2026-06-12 20:18:12','2026-06-12 20:18:12');
INSERT INTO "quick_budget_items" VALUES (1,1,4,'CUELLO DE CERA C/GUIA EN CAJA',1,1.05,24.46,25.68,'2026-05-18 07:36:59','2026-05-18 07:36:59');
INSERT INTO "quick_budgets" VALUES (1,'Colado de losa','','Cliente Particular',25.68,0,25.68,10,28.25,'borrador',1,'2026-05-18 07:36:59','2026-05-18 07:36:59');
INSERT INTO "quotations" VALUES (1,NULL,NULL,'quotations/pvkvQOrfAdr0OhlbrQ8qtL1ndrVGymVO6UzlPVyi.jpg','','2026-05-14 07:08:41','2026-05-14 07:08:12','2026-05-14 07:08:41',NULL,'WhatsApp Image 2026-04-17 at 4.29.57 PM.jpeg','completed','[Extraído directamente por Gemini Vision]','{"supplier":"Boxito","store":"M\u00e9rida","seller":"Leticia Alejandra Dzul Uh","tax_info":{"tax_rate":0.16,"prices_include_tax":false,"tax_detected":true,"subtotal":22400.73,"tax_total":3584.11,"grand_total":25984.84},"items":[{"name":"REMATE VENTILA SANIT 50","category":"Plomer\u00eda","quantity":70,"unit":"pza","unit_name":"Pieza","unit_price":20.28,"discount":0,"tax_amount":227.14,"price_includes_tax":false,"line_subtotal":1419.6,"line_total":1646.74},{"name":"TUBO SAN NORMA 110","category":"Material Hidr\u00e1ulico","quantity":42,"unit":"m","unit_name":"Metro lineal","unit_price":39.96,"discount":0,"tax_amount":268.53,"price_includes_tax":false,"line_subtotal":1678.32,"line_total":1946.85},{"name":"PEGAMENTO PVC (4 OZ) 118 ML TRANSPAREN OATEY","category":"Plomer\u00eda","quantity":9,"unit":"pza","unit_name":"Pieza","unit_price":52.87,"discount":0,"tax_amount":76.13,"price_includes_tax":false,"line_subtotal":475.83,"line_total":551.96},{"name":"CUELLO DE CERA C\/GUIA EN CAJA PB-104","category":"Plomer\u00eda","quantity":18,"unit":"pza","unit_name":"Pieza","unit_price":24.46,"discount":0,"tax_amount":70.44,"price_includes_tax":false,"line_subtotal":440.28,"line_total":510.72},{"name":"LLAVE D\/CONTROL P\/GAS 1\/2PULGX3\/8PULG","category":"Material Hidr\u00e1ulico","quantity":60,"unit":"pza","unit_name":"Pieza","unit_price":61.09,"discount":0,"tax_amount":586.46,"price_includes_tax":false,"line_subtotal":3665.4,"line_total":4251.86},{"name":"ADAPTADOR MACHO GAS 1\/2PULG X 1\/2PULG RM","category":"Material Hidr\u00e1ulico","quantity":60,"unit":"pza","unit_name":"Pieza","unit_price":40.14,"discount":0,"tax_amount":385.34,"price_includes_tax":false,"line_subtotal":2408.4,"line_total":2793.74},{"name":"TEE GAS 1\/2PULG X 1\/2PULG X 1\/2PULG","category":"Material Hidr\u00e1ulico","quantity":30,"unit":"pza","unit_name":"Pieza","unit_price":89.42,"discount":0,"tax_amount":429.22,"price_includes_tax":false,"line_subtotal":2682.6,"line_total":3111.82},{"name":"CODO 90\u00b0 GAS 1\/2PULG X 1\/2PULG","category":"Material Hidr\u00e1ulico","quantity":105,"unit":"pza","unit_name":"Pieza","unit_price":62.56,"discount":0,"tax_amount":1051.01,"price_includes_tax":false,"line_subtotal":6568.8,"line_total":7619.81},{"name":"TUBO PEALPE GAS 1\/2PULG X 100M","category":"Material Hidr\u00e1ulico","quantity":105,"unit":"m","unit_name":"Metro lineal","unit_price":25.62,"discount":0,"tax_amount":430.42,"price_includes_tax":false,"line_subtotal":2690.1,"line_total":3120.52},{"name":"ABRAZADERA U\u00d1A GALV 200 013 UCR12 PARA CPVC\/COBRE","category":"Plomer\u00eda","quantity":120,"unit":"pza","unit_name":"Pieza","unit_price":1.45,"discount":0,"tax_amount":27.84,"price_includes_tax":false,"line_subtotal":174,"line_total":201.84},{"name":"PIJA COMBINADA 10MM GROSOR 1 LARG","category":"Otros","quantity":420,"unit":"pza","unit_name":"Pieza","unit_price":0.26,"discount":0,"tax_amount":17.47,"price_includes_tax":false,"line_subtotal":109.2,"line_total":126.67},{"name":"TAQUETE DE PLASTICO 006 (TP14)","category":"Otros","quantity":420,"unit":"pza","unit_name":"Pieza","unit_price":0.21,"discount":0,"tax_amount":14.11,"price_includes_tax":false,"line_subtotal":88.2,"line_total":102.31}],"raw_text":"[Extra\u00eddo directamente por Gemini Vision]"}',NULL,1,0,NULL);
INSERT INTO "quotations" VALUES (2,NULL,1,'quotations/uZQ7uGuap8kQeOzMtRSuJulRKYkzLaM4oYIQ1XXn.jpg','','2026-05-14 07:10:41','2026-05-14 07:10:19','2026-05-14 07:11:04',NULL,'WhatsApp Image 2026-04-17 at 4.29.57 PM.jpeg','completed','[Extraído directamente por Gemini Vision]','{"supplier":"Boxito","store":"M\u00e9rida Yuc","seller":"Leticia Alejandra Dzul Uh","tax_info":{"tax_rate":0.16,"prices_include_tax":false,"tax_detected":true,"subtotal":22400.73,"tax_total":3584.11,"grand_total":25984.84},"items":[{"name":"REMATE VENTILA SANIT 50","category":"Material Hidr\u00e1ulico","quantity":70,"unit":"pz","unit_name":"Pieza","unit_price":20.28,"discount":0,"tax_amount":227.14,"price_includes_tax":false,"line_subtotal":1419.6,"line_total":1646.74},{"name":"TUBO SAN NORMA 110","category":"Material Hidr\u00e1ulico","quantity":42,"unit":"ml","unit_name":"Metro Lineal","unit_price":39.96,"discount":0,"tax_amount":268.53,"price_includes_tax":false,"line_subtotal":1678.32,"line_total":1946.85},{"name":"PEGAMENTO PVC (4 OZ) 118 ML TRANSPAREN OATEY","category":"Plomer\u00eda","quantity":9,"unit":"pz","unit_name":"Pieza","unit_price":52.87,"discount":0,"tax_amount":76.13,"price_includes_tax":false,"line_subtotal":475.83,"line_total":551.96},{"name":"CUELLO DE CERA C\/GUIA EN CAJA","category":"Plomer\u00eda","quantity":18,"unit":"pz","unit_name":"Pieza","unit_price":24.46,"discount":0,"tax_amount":70.44,"price_includes_tax":false,"line_subtotal":440.28,"line_total":510.72},{"name":"LLAVE D\/CONTROL P\/GAS 1\/2PULGX3\/8PULG","category":"Plomer\u00eda","quantity":60,"unit":"pz","unit_name":"Pieza","unit_price":61.09,"discount":0,"tax_amount":586.46,"price_includes_tax":false,"line_subtotal":3665.4,"line_total":4251.86},{"name":"ADAPTADOR MACHO GAS 1\/2PULG X 1\/2PULG RM","category":"Plomer\u00eda","quantity":60,"unit":"pz","unit_name":"Pieza","unit_price":40.14,"discount":0,"tax_amount":385.34,"price_includes_tax":false,"line_subtotal":2408.4,"line_total":2793.74},{"name":"TEE GAS 1\/2PULG X 1\/2PULG X 1\/2PULG","category":"Plomer\u00eda","quantity":30,"unit":"pz","unit_name":"Pieza","unit_price":89.42,"discount":0,"tax_amount":429.22,"price_includes_tax":false,"line_subtotal":2682.6,"line_total":3111.82},{"name":"CODO 90\u00b0 GAS 1\/2PULG X 1\/2PULG","category":"Plomer\u00eda","quantity":105,"unit":"pz","unit_name":"Pieza","unit_price":62.56,"discount":0,"tax_amount":1051.01,"price_includes_tax":false,"line_subtotal":6568.8,"line_total":7619.81},{"name":"TUBO PEALPE GAS 1\/2PULG X 100M","category":"Plomer\u00eda","quantity":105,"unit":"ml","unit_name":"Metro Lineal","unit_price":25.62,"discount":0,"tax_amount":430.42,"price_includes_tax":false,"line_subtotal":2690.1,"line_total":3120.52},{"name":"ABRAZADERA U\u00d1A GALV 200 013 PARA CPVC\/COBRE","category":"Plomer\u00eda","quantity":120,"unit":"pz","unit_name":"Pieza","unit_price":1.45,"discount":0,"tax_amount":27.84,"price_includes_tax":false,"line_subtotal":174,"line_total":201.84},{"name":"PIJA COMBINADA 10MM GROSOR 1 LARG","category":"Otros","quantity":420,"unit":"pz","unit_name":"Pieza","unit_price":0.26,"discount":0,"tax_amount":17.47,"price_includes_tax":false,"line_subtotal":109.2,"line_total":126.67},{"name":"TAQUETE DE PLASTICO 006 (TP14)","category":"Otros","quantity":420,"unit":"pz","unit_name":"Pieza","unit_price":0.21,"discount":0,"tax_amount":14.11,"price_includes_tax":false,"line_subtotal":88.2,"line_total":102.31}],"raw_text":"[Extra\u00eddo directamente por Gemini Vision]"}',NULL,1,0,NULL);
INSERT INTO "quotations" VALUES (3,NULL,NULL,'quotations/wA6z0PDneMZvJefAkHmRyiX2ayEbbY1he1Tuc7jY.jpg','','2026-05-14 08:11:45','2026-05-14 08:11:33','2026-05-14 08:11:45',NULL,'WhatsApp Image 2026-04-17 at 4.29.31 PM.jpeg','completed','[Extraído directamente por Gemini Vision]','{"supplier":"G.e.s Aceros Y Mas","store":null,"seller":null,"tax_info":{"tax_rate":0.16,"prices_include_tax":false,"tax_detected":true,"subtotal":17942.85,"tax_total":2870.86,"grand_total":20813.71},"items":[{"name":"VARILLA # 3, DE 3\/8\" (12 MTS.)","category":"Acero \/ Herrer\u00eda","quantity":145,"unit":"pza","unit_name":"Pieza","unit_price":121.33,"discount":0,"tax_amount":null,"price_includes_tax":false,"line_subtotal":17592.85,"line_total":null},{"name":"SERVICIO DE ENTREGA DENTRO DE LA CD. DE MERIDA","category":"Otros","quantity":1,"unit":"pza","unit_name":"Pieza","unit_price":350,"discount":0,"tax_amount":null,"price_includes_tax":false,"line_subtotal":350,"line_total":null}],"raw_text":"[Extra\u00eddo directamente por Gemini Vision]"}',NULL,1,0,NULL);
INSERT INTO "quotations" VALUES (4,NULL,NULL,'quotations/DvP2hPz3qSyY0R7cae90e2UOcXdwAT0567VMsOoO.jpg','','2026-05-14 08:41:29','2026-05-14 08:41:23','2026-05-14 08:41:29',NULL,'WhatsApp Image 2026-04-17 at 4.29.31 PM.jpeg','completed','[No se pudo extraer texto del archivo]','{"supplier":null,"store":null,"seller":null,"tax_info":null,"items":[],"raw_text":"[No se pudo extraer texto del archivo]"}',NULL,1,0,NULL);
INSERT INTO "quotations" VALUES (5,NULL,NULL,'quotations/bfGRVBB5Hv36UMytYYkonchRqx8HEciH34aXQGzY.jpg','','2026-05-14 08:42:39','2026-05-14 08:42:22','2026-05-14 08:42:39',NULL,'WhatsApp Image 2026-04-17 at 4.29.31 PM.jpeg','completed','[Extraído directamente por Gemini Vision]','{"supplier":"G.e.s. Aceros Y Mas","store":null,"seller":null,"tax_info":{"tax_rate":0.16,"prices_include_tax":false,"tax_detected":true,"subtotal":17942.85,"tax_total":2870.86,"grand_total":20813.71},"items":[{"name":"VARILLA # 3, DE 3\/8\" (12 MTS.)","category":"Acero \/ Herrer\u00eda","quantity":145,"unit":"pza","unit_name":"Pieza","unit_price":121.33,"discount":0,"tax_amount":null,"price_includes_tax":false,"line_subtotal":17592.85,"line_total":null},{"name":"SERVICIO DE ENTREGA DENTRO DE LA CD. DE MERIDA","category":"Otros","quantity":1,"unit":"pza","unit_name":"Servicio","unit_price":350,"discount":0,"tax_amount":null,"price_includes_tax":false,"line_subtotal":350,"line_total":null}],"raw_text":"[Extra\u00eddo directamente por Gemini Vision]"}',NULL,1,0,NULL);
INSERT INTO "quotations" VALUES (6,NULL,NULL,'quotations/jw32Stzxxn52wcEs6n5iZZhTFiSeFzlO7a1qhCcG.jpg','','2026-05-14 08:52:26','2026-05-14 08:52:10','2026-05-14 08:52:26',NULL,'WhatsApp Image 2026-04-17 at 3.56.44 PM.jpeg','completed','[Extraído directamente por Gemini Vision]','{"supplier":"Productos Pennsylvania","store":"Merida","seller":"Jose Armando Canul","tax_info":{"tax_rate":0.16,"prices_include_tax":false,"tax_detected":true,"subtotal":7907.57,"tax_total":1265.21,"grand_total":9172.78},"items":[{"name":"DESMOLDANTE PRIME GLASST","category":"Otros","quantity":1,"unit":"tambor","unit_name":"Tambor","unit_price":7907.57,"discount":0,"tax_amount":1265.21,"price_includes_tax":false,"line_subtotal":7907.57,"line_total":9172.78}],"raw_text":"[Extra\u00eddo directamente por Gemini Vision]"}',NULL,1,0,NULL);
INSERT INTO "quotations" VALUES (7,2,2,'quotations/IWgKYvuyHq7FVzTRnOexUgnqyQ2LzFAYxXvTpu12.jpg','','2026-05-14 08:57:03','2026-05-14 08:56:46','2026-05-14 08:58:50',NULL,'WhatsApp Image 2026-04-17 at 3.56.44 PM.jpeg','completed','[Extraído directamente por Gemini Vision]','{"supplier":"Productos Pennsylvania","store":null,"seller":"Jose Armando Can\u00fal","tax_info":{"tax_rate":0.16,"prices_include_tax":false,"tax_detected":true,"subtotal":7907.57,"tax_total":1265.21,"grand_total":9172.78},"items":[{"name":"DESMOLDANTE PRIME GLASST","category":"Otros","quantity":1,"unit":"tambor","unit_name":"Tambor","unit_price":7907.57,"discount":0,"tax_amount":1265.21,"price_includes_tax":false,"line_subtotal":7907.57,"line_total":9172.78}],"raw_text":"[Extra\u00eddo directamente por Gemini Vision]"}',NULL,1,0,NULL);
INSERT INTO "quotations" VALUES (8,3,1,'quotations/14o2Fqbx0jHiA7S45uCeZyJn0qbRZNHr48TlLsug.jpg','','2026-05-14 09:08:31','2026-05-14 09:07:56','2026-05-14 09:10:21',NULL,'WhatsApp Image 2026-04-17 at 4.29.57 PM.jpeg','completed','[Extraído directamente por Gemini Vision]','{"supplier":"Boxito","store":"Boxito Merida Sambula","seller":"Leticia Alejandra Dzul Uh","tax_info":{"tax_rate":0.16,"prices_include_tax":false,"tax_detected":true,"subtotal":22400.73,"tax_total":3584.11,"grand_total":25984.84},"items":[{"name":"REMATE VENTILA SANIT 50","category":"Plomer\u00eda","quantity":70,"unit":"pza","unit_name":"Pieza","unit_price":20.28,"discount":0,"tax_amount":227.14,"price_includes_tax":false,"line_subtotal":1419.6,"line_total":1646.74},{"name":"TUBO SAN NORMA 110","category":"Material Hidr\u00e1ulico","quantity":42,"unit":"m","unit_name":"Metro Lineal","unit_price":39.96,"discount":0,"tax_amount":268.53,"price_includes_tax":false,"line_subtotal":1678.32,"line_total":1946.85},{"name":"PEGAMENTO PVC (4 OZ) 118 ML TRANSPAREN OATEY","category":"Plomer\u00eda","quantity":9,"unit":"pza","unit_name":"Pieza","unit_price":52.87,"discount":0,"tax_amount":76.13,"price_includes_tax":false,"line_subtotal":475.83,"line_total":551.96},{"name":"CUELLO DE CERA C\/GUIA EN CAJA PB-104","category":"Plomer\u00eda","quantity":18,"unit":"pza","unit_name":"Pieza","unit_price":24.46,"discount":0,"tax_amount":70.44,"price_includes_tax":false,"line_subtotal":440.28,"line_total":510.72},{"name":"LLAVE D\/CONTROL P\/GAS 1\/2PULGX3\/8PULG","category":"Plomer\u00eda","quantity":60,"unit":"pza","unit_name":"Pieza","unit_price":61.09,"discount":0,"tax_amount":586.46,"price_includes_tax":false,"line_subtotal":3665.4,"line_total":4251.86},{"name":"ADAPTADOR MACHO GAS 1\/2PULG X 1\/2PULG RM","category":"Plomer\u00eda","quantity":60,"unit":"pza","unit_name":"Pieza","unit_price":40.14,"discount":0,"tax_amount":385.34,"price_includes_tax":false,"line_subtotal":2408.4,"line_total":2793.74},{"name":"TEE GAS 1\/2PULG X 1\/2PULG X 1\/2PULG","category":"Plomer\u00eda","quantity":30,"unit":"pza","unit_name":"Pieza","unit_price":89.42,"discount":0,"tax_amount":429.22,"price_includes_tax":false,"line_subtotal":2682.6,"line_total":3111.82},{"name":"CODO 90* GAS 1\/2PULG X 1\/2PULG","category":"Plomer\u00eda","quantity":105,"unit":"pza","unit_name":"Pieza","unit_price":62.56,"discount":0,"tax_amount":1051.01,"price_includes_tax":false,"line_subtotal":6568.8,"line_total":7619.81},{"name":"TUBO PEALPE GAS 1\/2PULG X 100M","category":"Material Hidr\u00e1ulico","quantity":105,"unit":"m","unit_name":"Metro Lineal","unit_price":25.62,"discount":0,"tax_amount":430.42,"price_includes_tax":false,"line_subtotal":2690.1,"line_total":3120.52},{"name":"ABRAZADERA U\u00d1A GALV PARA CPVC\/COBRE","category":"Plomer\u00eda","quantity":120,"unit":"pza","unit_name":"Pieza","unit_price":1.45,"discount":0,"tax_amount":27.84,"price_includes_tax":false,"line_subtotal":174,"line_total":201.84},{"name":"PIJA COMBINADA 10MM GROSOR 1 LARG","category":"Herramientas","quantity":420,"unit":"pza","unit_name":"Pieza","unit_price":0.26,"discount":0,"tax_amount":17.47,"price_includes_tax":false,"line_subtotal":109.2,"line_total":126.67},{"name":"TAQUETE DE PLASTICO 006 (TP14)","category":"Herramientas","quantity":420,"unit":"pza","unit_name":"Pieza","unit_price":0.21,"discount":0,"tax_amount":14.11,"price_includes_tax":false,"line_subtotal":88.2,"line_total":102.31}],"raw_text":"[Extra\u00eddo directamente por Gemini Vision]"}',NULL,1,0,NULL);
INSERT INTO "quotations" VALUES (9,NULL,NULL,'quotations/ATs4k7VeZEmukWaSFQT6S7oGmQAtW4qbazE3z07h.jpg','','2026-05-18 00:30:59','2026-05-18 00:30:33','2026-05-18 00:30:59',NULL,'WhatsApp Image 2026-04-17 at 4.29.57 PM.jpeg','completed','[Extraído directamente por Gemini Vision]','{"supplier":"Boxito","store":"M\u00e9rida Anueva Sambula","seller":"Leticia Alejandra Dzul Uh","tax_info":{"tax_rate":0.16,"prices_include_tax":false,"tax_detected":true,"subtotal":22400.73,"tax_total":3584.11,"grand_total":25984.84},"items":[{"name":"REMATE VENTILA SANIT 50","category":"Plomer\u00eda","quantity":70,"unit":"pza","unit_name":"Pieza","unit_price":20.28,"discount":0,"tax_amount":227.14,"price_includes_tax":false,"line_subtotal":1419.6,"line_total":1646.74},{"name":"TUBO SAN NORMA 110","category":"Plomer\u00eda","quantity":42,"unit":"m","unit_name":"Metro Lineal","unit_price":39.96,"discount":0,"tax_amount":268.53,"price_includes_tax":false,"line_subtotal":1678.32,"line_total":1946.85},{"name":"PEGAMENTO PVC (4 OZ) 118 ML TRANSPAREN OATEY","category":"Plomer\u00eda","quantity":9,"unit":"pza","unit_name":"Pieza","unit_price":52.87,"discount":0,"tax_amount":76.13,"price_includes_tax":false,"line_subtotal":475.83,"line_total":551.96},{"name":"CUELLO DE CERA C\/GUIA EN CAJA","category":"Plomer\u00eda","quantity":18,"unit":"pza","unit_name":"Pieza","unit_price":24.46,"discount":0,"tax_amount":70.44,"price_includes_tax":false,"line_subtotal":440.28,"line_total":510.72},{"name":"LLAVE D\/CONTROL P\/GAS 1\/2PULGX3\/8PULG","category":"Plomer\u00eda","quantity":60,"unit":"pza","unit_name":"Pieza","unit_price":61.09,"discount":0,"tax_amount":586.46,"price_includes_tax":false,"line_subtotal":3665.4,"line_total":4251.86},{"name":"ADAPTADOR MACHO GAS 1\/2PULG X 1\/2PULG RM","category":"Plomer\u00eda","quantity":60,"unit":"pza","unit_name":"Pieza","unit_price":40.14,"discount":0,"tax_amount":385.34,"price_includes_tax":false,"line_subtotal":2408.4,"line_total":2793.74},{"name":"TEE GAS 1\/2PULG X 1\/2PULG X 1\/2PULG","category":"Plomer\u00eda","quantity":30,"unit":"pza","unit_name":"Pieza","unit_price":89.42,"discount":0,"tax_amount":429.22,"price_includes_tax":false,"line_subtotal":2682.6,"line_total":3111.82},{"name":"CODO 90\u00b0 GAS 1\/2PULG X 1\/2PULG","category":"Plomer\u00eda","quantity":105,"unit":"pza","unit_name":"Pieza","unit_price":62.56,"discount":0,"tax_amount":1051.01,"price_includes_tax":false,"line_subtotal":6568.8,"line_total":7619.81},{"name":"TUBO PEALPE GAS 1\/2PULG X 100M","category":"Plomer\u00eda","quantity":105,"unit":"m","unit_name":"Metro Lineal","unit_price":25.62,"discount":0,"tax_amount":430.42,"price_includes_tax":false,"line_subtotal":2690.1,"line_total":3120.52},{"name":"ABRAZADERA U\u00d1A GALV PARA CPVC\/COBRE","category":"Plomer\u00eda","quantity":120,"unit":"pza","unit_name":"Pieza","unit_price":1.45,"discount":0,"tax_amount":27.84,"price_includes_tax":false,"line_subtotal":174,"line_total":201.84},{"name":"PIJA COMBINADA 10MM GROSOR 1 LARG","category":"Otros","quantity":420,"unit":"pza","unit_name":"Pieza","unit_price":0.26,"discount":0,"tax_amount":17.47,"price_includes_tax":false,"line_subtotal":109.2,"line_total":126.67},{"name":"TAQUETE DE PLASTICO","category":"Otros","quantity":420,"unit":"pza","unit_name":"Pieza","unit_price":0.21,"discount":0,"tax_amount":14.11,"price_includes_tax":false,"line_subtotal":88.2,"line_total":102.31}],"raw_text":"[Extra\u00eddo directamente por Gemini Vision]"}',NULL,1,0,NULL);
INSERT INTO "quotations" VALUES (10,NULL,1,'quotations/gqCPqYQuAm8VrB4hg2z49O3kATq0HTT0HuuEfOW7.jpg','','2026-05-18 07:29:14','2026-05-18 07:28:46','2026-05-18 07:30:28',NULL,'WhatsApp Image 2026-04-17 at 4.29.57 PM.jpeg','completed','[Extraído directamente por Gemini Vision]','{"supplier":"Boxito","store":"Boxito","seller":"Leticia Alejandra Dzul Uh","tax_info":{"tax_rate":0.16,"prices_include_tax":false,"tax_detected":true,"subtotal":22400.73,"tax_total":3584.11,"grand_total":25984.84},"items":[{"name":"REMATE VENTILA SANIT 50","category":"Material Hidr\u00e1ulico","quantity":70,"unit":"pza","unit_name":"Pieza","unit_price":20.28,"discount":0,"tax_amount":227.14,"price_includes_tax":false,"line_subtotal":1419.6,"line_total":1646.74},{"name":"TUBO SAN NORMA 110","category":"Material Hidr\u00e1ulico","quantity":42,"unit":"m","unit_name":"Metro Lineal","unit_price":39.96,"discount":0,"tax_amount":268.53,"price_includes_tax":false,"line_subtotal":1678.32,"line_total":1946.85},{"name":"PEGAMENTO PVC (4 OZ) 118 ML TRANSPAREN OATEY","category":"Plomer\u00eda","quantity":9,"unit":"pza","unit_name":"Pieza","unit_price":52.87,"discount":0,"tax_amount":76.13,"price_includes_tax":false,"line_subtotal":475.83,"line_total":551.96},{"name":"CUELLO DE CERA C\/GUIA EN CAJA","category":"Plomer\u00eda","quantity":18,"unit":"pza","unit_name":"Pieza","unit_price":24.46,"discount":0,"tax_amount":70.44,"price_includes_tax":false,"line_subtotal":440.28,"line_total":510.72},{"name":"LLAVE D\/CONTROL P\/GAS 1\/2PULGX3\/8PULG","category":"Material Hidr\u00e1ulico","quantity":60,"unit":"pza","unit_name":"Pieza","unit_price":61.09,"discount":0,"tax_amount":586.46,"price_includes_tax":false,"line_subtotal":3665.4,"line_total":4251.86},{"name":"ADAPTADOR MACHO GAS 1\/2PULG X 1\/2PULG RM","category":"Material Hidr\u00e1ulico","quantity":60,"unit":"pza","unit_name":"Pieza","unit_price":40.14,"discount":0,"tax_amount":385.34,"price_includes_tax":false,"line_subtotal":2408.4,"line_total":2793.74},{"name":"TEE GAS 1\/2PULG X 1\/2PULG X 1\/2PULG","category":"Material Hidr\u00e1ulico","quantity":30,"unit":"pza","unit_name":"Pieza","unit_price":89.42,"discount":0,"tax_amount":429.22,"price_includes_tax":false,"line_subtotal":2682.6,"line_total":3111.82},{"name":"CODO 90\u00b0 GAS 1\/2PULG X 1\/2PULG","category":"Material Hidr\u00e1ulico","quantity":105,"unit":"pza","unit_name":"Pieza","unit_price":62.56,"discount":0,"tax_amount":1051.01,"price_includes_tax":false,"line_subtotal":6568.8,"line_total":7619.81},{"name":"TUBO PEALPE GAS 1\/2PULG X 100M","category":"Material Hidr\u00e1ulico","quantity":105,"unit":"m","unit_name":"Metro Lineal","unit_price":25.62,"discount":0,"tax_amount":430.42,"price_includes_tax":false,"line_subtotal":2690.1,"line_total":3120.52},{"name":"ABRAZADERA U\u00d1A GALV PARA CPVC\/COBRE","category":"Otros","quantity":120,"unit":"pza","unit_name":"Pieza","unit_price":1.45,"discount":0,"tax_amount":27.84,"price_includes_tax":false,"line_subtotal":174,"line_total":201.84},{"name":"PIJA COMBINADA 10MM GROSOR 1 LARG","category":"Otros","quantity":420,"unit":"pza","unit_name":"Pieza","unit_price":0.26,"discount":0,"tax_amount":17.47,"price_includes_tax":false,"line_subtotal":109.2,"line_total":126.67},{"name":"TAQUETE DE PLASTICO","category":"Otros","quantity":420,"unit":"pza","unit_name":"Pieza","unit_price":0.21,"discount":0,"tax_amount":14.11,"price_includes_tax":false,"line_subtotal":88.2,"line_total":102.31}],"raw_text":"[Extra\u00eddo directamente por Gemini Vision]"}',NULL,1,0,NULL);
INSERT INTO "quotations" VALUES (11,NULL,NULL,'quotations/KMOPie6DGF3O7nBZMBNFoVvv2sCdQ0fYjZbSXqnl.jpg','','2026-05-18 08:44:09','2026-05-18 08:43:43','2026-05-18 08:44:09',NULL,'WhatsApp Image 2026-04-17 at 4.29.31 PM.jpeg','completed','[No se pudo extraer texto del archivo]','{"supplier":null,"store":null,"seller":null,"tax_info":null,"items":[],"raw_text":"[No se pudo extraer texto del archivo]"}',NULL,1,0,NULL);
INSERT INTO "quotations" VALUES (12,14,1,'quotations/bb0A2kwa7N7xUY4uKbcOSzai4K0lyBVsIjCvt34f.jpg','','2026-05-23 04:33:47','2026-05-23 04:33:10','2026-05-23 04:35:53',NULL,'WhatsApp Image 2026-04-17 at 4.29.57 PM.jpeg','completed','[Extraído directamente por Gemini Vision]','{"supplier":"Boxito","store":"Sambul\u00e1","seller":"Leticia Alejandra Dzul Uh","tax_info":{"tax_rate":0.16,"prices_include_tax":false,"tax_detected":true,"subtotal":22400.73,"tax_total":3584.11,"grand_total":25984.84},"items":[{"name":"REMATE VENTILA SANIT 50","category":"Plomer\u00eda","quantity":70,"unit":"pza","unit_name":"Pieza","unit_price":20.28,"discount":0,"tax_amount":227.14,"price_includes_tax":false,"line_subtotal":1419.6,"line_total":1646.74},{"name":"TUBO SAN NORMA 110","category":"Plomer\u00eda","quantity":42,"unit":"m","unit_name":"Metro lineal","unit_price":39.96,"discount":0,"tax_amount":268.53,"price_includes_tax":false,"line_subtotal":1678.32,"line_total":1946.85},{"name":"PEGAMENTO PVC (4 OZ) 118 ML TRANSPAREN OATEY","category":"Plomer\u00eda","quantity":9,"unit":"pza","unit_name":"Pieza","unit_price":52.87,"discount":0,"tax_amount":76.13,"price_includes_tax":false,"line_subtotal":475.83,"line_total":551.96},{"name":"CUELLO DE CERA C\/GUIA EN CAJA PB-104","category":"Plomer\u00eda","quantity":18,"unit":"pza","unit_name":"Pieza","unit_price":24.46,"discount":0,"tax_amount":70.44,"price_includes_tax":false,"line_subtotal":440.28,"line_total":510.72},{"name":"LLAVE D\/CONTROL P\/GAS 1\/2PULGX3\/8PULG","category":"Plomer\u00eda","quantity":60,"unit":"pza","unit_name":"Pieza","unit_price":61.09,"discount":0,"tax_amount":586.46,"price_includes_tax":false,"line_subtotal":3665.4,"line_total":4251.86},{"name":"ADAPTADOR MACHO GAS 1\/2PULG X 1\/2PULG RM","category":"Plomer\u00eda","quantity":60,"unit":"pza","unit_name":"Pieza","unit_price":40.14,"discount":0,"tax_amount":385.34,"price_includes_tax":false,"line_subtotal":2408.4,"line_total":2793.74},{"name":"TEE GAS 1\/2PULG X 1\/2PULG X 1\/2PULG","category":"Plomer\u00eda","quantity":30,"unit":"pza","unit_name":"Pieza","unit_price":89.42,"discount":0,"tax_amount":429.22,"price_includes_tax":false,"line_subtotal":2682.6,"line_total":3111.82},{"name":"CODO 90\u00b0 GAS 1\/2PULG X 1\/2PULG","category":"Plomer\u00eda","quantity":105,"unit":"pza","unit_name":"Pieza","unit_price":62.56,"discount":0,"tax_amount":1051.01,"price_includes_tax":false,"line_subtotal":6568.8,"line_total":7619.81},{"name":"TUBO PEALPE GAS 1\/2PULG X 100M","category":"Plomer\u00eda","quantity":105,"unit":"m","unit_name":"Metro lineal","unit_price":25.62,"discount":0,"tax_amount":430.42,"price_includes_tax":false,"line_subtotal":2690.1,"line_total":3120.52},{"name":"ABRAZADERA U\u00d1A GALV 200 013 UCR12 PARA CPVC\/COBRE","category":"Plomer\u00eda","quantity":120,"unit":"pza","unit_name":"Pieza","unit_price":1.45,"discount":0,"tax_amount":27.84,"price_includes_tax":false,"line_subtotal":174,"line_total":201.84},{"name":"PIJA COMBINADA 10MM GROSOR 1 LARG","category":"Ferreter\u00eda","quantity":420,"unit":"pza","unit_name":"Pieza","unit_price":0.26,"discount":0,"tax_amount":17.47,"price_includes_tax":false,"line_subtotal":109.2,"line_total":126.67},{"name":"TAQUETE DE PLASTICO 006 (TP14)","category":"Ferreter\u00eda","quantity":420,"unit":"pza","unit_name":"Pieza","unit_price":0.21,"discount":0,"tax_amount":14.11,"price_includes_tax":false,"line_subtotal":88.2,"line_total":102.31}],"raw_text":"[Extra\u00eddo directamente por Gemini Vision]"}',NULL,1,0,NULL);
INSERT INTO "quotations" VALUES (13,15,1,'quotations/oZw0N9Oc7IvMam9mrMIpJHCtD2VzAPcmZjcwuOAF.jpg','','2026-05-27 07:51:55','2026-05-27 07:51:15','2026-05-27 07:53:26',NULL,'WhatsApp Image 2026-04-17 at 4.29.57 PM.jpeg','completed','[Extraído directamente por Gemini Vision]','{"supplier":"Boxito","store":"M\u00e9rida","seller":"Leticia Alejandra Dzul Uh","tax_info":{"tax_rate":0.16,"prices_include_tax":false,"tax_detected":true,"subtotal":22400.73,"tax_total":3584.11,"grand_total":25984.84},"items":[{"name":"REMATE VENTILA SANIT 50","category":"Plomer\u00eda","quantity":70,"unit":"pza","unit_name":"Pieza","unit_price":20.28,"discount":0,"tax_amount":227.14,"price_includes_tax":false,"line_subtotal":1419.6,"line_total":1646.74},{"name":"TUBO SAN NORMA 110","category":"Plomer\u00eda","quantity":42,"unit":"m","unit_name":"Metro lineal","unit_price":39.96,"discount":0,"tax_amount":268.53,"price_includes_tax":false,"line_subtotal":1678.32,"line_total":1946.85},{"name":"PEGAMENTO PVC (4 OZ) 118 ML TRANSPAREN OATEY","category":"Ferreter\u00eda","quantity":9,"unit":"pza","unit_name":"Pieza","unit_price":52.87,"discount":0,"tax_amount":76.13,"price_includes_tax":false,"line_subtotal":475.83,"line_total":551.96},{"name":"CUELLO DE CERA C\/GUIA EN CAJA","category":"Plomer\u00eda","quantity":18,"unit":"pza","unit_name":"Pieza","unit_price":24.46,"discount":0,"tax_amount":70.44,"price_includes_tax":false,"line_subtotal":440.28,"line_total":510.72},{"name":"LLAVE D\/CONTROL P\/GAS 1\/2PULGX3\/8PULG","category":"Plomer\u00eda","quantity":60,"unit":"pza","unit_name":"Pieza","unit_price":61.09,"discount":0,"tax_amount":586.46,"price_includes_tax":false,"line_subtotal":3665.4,"line_total":4251.86},{"name":"ADAPTADOR MACHO GAS 1\/2PULG X 1\/2PULG RM","category":"Plomer\u00eda","quantity":60,"unit":"pza","unit_name":"Pieza","unit_price":40.14,"discount":0,"tax_amount":385.34,"price_includes_tax":false,"line_subtotal":2408.4,"line_total":2793.74},{"name":"TEE GAS 1\/2PULG X 1\/2PULG X 1\/2PULG","category":"Plomer\u00eda","quantity":30,"unit":"pza","unit_name":"Pieza","unit_price":89.42,"discount":0,"tax_amount":429.22,"price_includes_tax":false,"line_subtotal":2682.6,"line_total":3111.82},{"name":"CODO 90\u00b0 GAS 1\/2PULG X 1\/2PULG","category":"Plomer\u00eda","quantity":105,"unit":"pza","unit_name":"Pieza","unit_price":62.56,"discount":0,"tax_amount":1051.01,"price_includes_tax":false,"line_subtotal":6568.8,"line_total":7619.81},{"name":"TUBO PEALPE GAS 1\/2PULG X 100M","category":"Plomer\u00eda","quantity":105,"unit":"m","unit_name":"Metro lineal","unit_price":25.62,"discount":0,"tax_amount":430.42,"price_includes_tax":false,"line_subtotal":2690.1,"line_total":3120.52},{"name":"ABRAZADERA U\u00d1A GALV 200 013 UCR12 PARA CPVC\/COBRE","category":"Ferreter\u00eda","quantity":120,"unit":"pza","unit_name":"Pieza","unit_price":1.45,"discount":0,"tax_amount":27.84,"price_includes_tax":false,"line_subtotal":174,"line_total":201.84},{"name":"PIJA COMBINADA 10MM GROSOR 1 LARG","category":"Ferreter\u00eda","quantity":420,"unit":"pza","unit_name":"Pieza","unit_price":0.26,"discount":0,"tax_amount":17.47,"price_includes_tax":false,"line_subtotal":109.2,"line_total":126.67},{"name":"TAQUETE DE PLASTICO 006 (TP14)","category":"Ferreter\u00eda","quantity":420,"unit":"pza","unit_name":"Pieza","unit_price":0.21,"discount":0,"tax_amount":14.11,"price_includes_tax":false,"line_subtotal":88.2,"line_total":102.31}],"raw_text":"[Extra\u00eddo directamente por Gemini Vision]"}',NULL,1,0,NULL);
INSERT INTO "quotations" VALUES (14,NULL,NULL,'quotations/wbpvPeBMxNXCjkkoTbk2lhu3ewjALika3gOZ5ktd.pdf','','2026-05-29 22:55:29','2026-05-29 22:55:05','2026-05-29 22:55:29',1,'280000120961.pdf','completed','EL NIPLITO DEL SURESTE S.A. DE C.V.    
DIR:CALLE 60 X 89 Y 89-A NO 709 COL CENTRO CP 97000 MERIDA YUCATAN MEXICO RFC: NSU9102113Y9 FECHA: 2026-04-17
CLIENTE: EKILATERO INGENIERIA Y CONSTRUCCION	PRESUPUESTO: 280000120961
CANTIDAD UM (UM-SAT) CODIGO COD-SAT DESCRIPCIONES	% DESCUENTO P. UNITARIOP. DESCUENTO IMPORTE
3 PZA (H87) MBTQCABCO 30181515 TANQUE JAZMIN CATO BLANCO	38.90 913.98 558.47 1,675.41
3 PZA (H87) MBTAACJBCO 30181511 TAZA ALARGADA CATO JAZMIN BLANCO	37.00 1259.58 793.52 2,380.56
 
TIPO
Recoger
PRECIOS SUJETOS A CAMBIO SIN PREVIO AVISO, PRECIOS EN PAGOS DE
CONTADO, VIGENCIA DE COTIZACION 5 DIAS VALIDA HASTA 2026-04-22
AGENTE: ISRAEL JESUS CARDENAS ESPARZA
 SUC: GRAN MONTEJO
 TEL: NA
    
SUBTOTAL:
DESCUENTO:
IVA:
TOTAL:
6,520.68
2,464.71
648.96
4,704.93
-Para deposito o transferencia referenciada se requiere capturar la referencia única en el apartado REFERENCIA DE PAGO o CONCEPTO DE PAGO según la banca
electrónica de la institución de su preferencia para que esta pueda proceder de manera exitosa.
Referencia unica: 28000012096145787231
Banco / Convenio 	Clave Interbancaria 
Bancomer convenio BBVA CIE/Contrato: 1697439	CLABE interbancaria: 012914002016974396
HSBC Clave de servicio RAP: 2555	CLABE interbancaria: 021180550300025558
Página 1/1','{"supplier":"El Niplito Del Sureste","store":"Gran Montejo","seller":"Israel Jesus Cardenas Esparza","tax_info":{"tax_rate":0.16,"prices_include_tax":false,"tax_detected":true,"subtotal":6520.68,"discount_total":2464.71,"discount_percentage":null,"tax_total":648.96,"grand_total":4704.93},"items":[{"name":"TANQUE JAZMIN CATO BLANCO","category":"Plomer\u00eda","quantity":3,"unit":"pza","unit_name":"Pieza","unit_price":558.47,"unit_price_before_discount":913.98,"discount":0,"tax_amount":null,"price_includes_tax":false,"line_subtotal":1675.41,"line_total":null},{"name":"TAZA ALARGADA CATO JAZMIN BLANCO","category":"Plomer\u00eda","quantity":3,"unit":"pza","unit_name":"Pieza","unit_price":793.52,"unit_price_before_discount":1259.58,"discount":0,"tax_amount":null,"price_includes_tax":false,"line_subtotal":2380.56,"line_total":null}],"raw_text":"EL NIPLITO DEL SURESTE S.A. DE C.V.    \nDIR:CALLE 60 X 89 Y 89-A NO 709 COL CENTRO CP 97000 MERIDA YUCATAN MEXICO RFC: NSU9102113Y9 FECHA: 2026-04-17\nCLIENTE: EKILATERO INGENIERIA Y CONSTRUCCION\tPRESUPUESTO: 280000120961\nCANTIDAD UM (UM-SAT) CODIGO COD-SAT DESCRIPCIONES\t% DESCUENTO P. UNITARIOP. DESCUENTO IMPORTE\n3 PZA (H87) MBTQCABCO 30181515 TANQUE JAZMIN CATO BLANCO\t38.90 913.98 558.47 1,675.41\n3 PZA (H87) MBTAACJBCO 30181511 TAZA ALARGADA CATO JAZMIN BLANCO\t37.00 1259.58 793.52 2,380.56\n \nTIPO\nRecoger\nPRECIOS SUJETOS A CAMBIO SIN PREVIO AVISO, PRECIOS EN PAGOS DE\nCONTADO, VIGENCIA DE COTIZACION 5 DIAS VALIDA HASTA 2026-04-22\nAGENTE: ISRAEL JESUS CARDENAS ESPARZA\n SUC: GRAN MONTEJO\n TEL: NA\n    \nSUBTOTAL:\nDESCUENTO:\nIVA:\nTOTAL:\n6,520.68\n2,464.71\n648.96\n4,704.93\n-Para deposito o transferencia referenciada se requiere capturar la referencia \u00fanica en el apartado REFERENCIA DE PAGO o CONCEPTO DE PAGO seg\u00fan la banca\nelectr\u00f3nica de la instituci\u00f3n de su preferencia para que esta pueda proceder de manera exitosa.\nReferencia unica: 28000012096145787231\nBanco \/ Convenio \tClave Interbancaria \nBancomer convenio BBVA CIE\/Contrato: 1697439\tCLABE interbancaria: 012914002016974396\nHSBC Clave de servicio RAP: 2555\tCLABE interbancaria: 021180550300025558\nP\u00e1gina 1\/1"}',NULL,1,0,NULL);
INSERT INTO "quotations" VALUES (15,NULL,NULL,'quotations/cZMVIUxCY8VjcBhCRoQX7erwmum8UqZ9Y0x5XaUg.pdf','','2026-05-29 23:17:36','2026-05-29 23:17:17','2026-05-29 23:17:36',NULL,'280000120961.pdf','completed','EL NIPLITO DEL SURESTE S.A. DE C.V.    
DIR:CALLE 60 X 89 Y 89-A NO 709 COL CENTRO CP 97000 MERIDA YUCATAN MEXICO RFC: NSU9102113Y9 FECHA: 2026-04-17
CLIENTE: EKILATERO INGENIERIA Y CONSTRUCCION	PRESUPUESTO: 280000120961
CANTIDAD UM (UM-SAT) CODIGO COD-SAT DESCRIPCIONES	% DESCUENTO P. UNITARIOP. DESCUENTO IMPORTE
3 PZA (H87) MBTQCABCO 30181515 TANQUE JAZMIN CATO BLANCO	38.90 913.98 558.47 1,675.41
3 PZA (H87) MBTAACJBCO 30181511 TAZA ALARGADA CATO JAZMIN BLANCO	37.00 1259.58 793.52 2,380.56
 
TIPO
Recoger
PRECIOS SUJETOS A CAMBIO SIN PREVIO AVISO, PRECIOS EN PAGOS DE
CONTADO, VIGENCIA DE COTIZACION 5 DIAS VALIDA HASTA 2026-04-22
AGENTE: ISRAEL JESUS CARDENAS ESPARZA
 SUC: GRAN MONTEJO
 TEL: NA
    
SUBTOTAL:
DESCUENTO:
IVA:
TOTAL:
6,520.68
2,464.71
648.96
4,704.93
-Para deposito o transferencia referenciada se requiere capturar la referencia única en el apartado REFERENCIA DE PAGO o CONCEPTO DE PAGO según la banca
electrónica de la institución de su preferencia para que esta pueda proceder de manera exitosa.
Referencia unica: 28000012096145787231
Banco / Convenio 	Clave Interbancaria 
Bancomer convenio BBVA CIE/Contrato: 1697439	CLABE interbancaria: 012914002016974396
HSBC Clave de servicio RAP: 2555	CLABE interbancaria: 021180550300025558
Página 1/1','{"supplier":"El Niplito Del Sureste S.A. De C.V.","store":"Gran Montejo","seller":"Israel Jesus Cardenas Esparza","tax_info":{"tax_rate":0.16,"prices_include_tax":false,"tax_detected":true,"subtotal":6520.68,"tax_total":648.96,"grand_total":4704.93},"items":[{"name":"TANQUE JAZMIN CATO BLANCO","category":"Plomer\u00eda","quantity":3,"unit":"pza","unit_name":"Pieza","unit_price":913.98,"discount":355.51,"tax_amount":null,"price_includes_tax":false,"line_subtotal":1675.41,"line_total":null},{"name":"TAZA ALARGADA CATO JAZMIN BLANCO","category":"Plomer\u00eda","quantity":3,"unit":"pza","unit_name":"Pieza","unit_price":1259.58,"discount":466.06,"tax_amount":null,"price_includes_tax":false,"line_subtotal":2380.56,"line_total":null}],"raw_text":"EL NIPLITO DEL SURESTE S.A. DE C.V.    \nDIR:CALLE 60 X 89 Y 89-A NO 709 COL CENTRO CP 97000 MERIDA YUCATAN MEXICO RFC: NSU9102113Y9 FECHA: 2026-04-17\nCLIENTE: EKILATERO INGENIERIA Y CONSTRUCCION\tPRESUPUESTO: 280000120961\nCANTIDAD UM (UM-SAT) CODIGO COD-SAT DESCRIPCIONES\t% DESCUENTO P. UNITARIOP. DESCUENTO IMPORTE\n3 PZA (H87) MBTQCABCO 30181515 TANQUE JAZMIN CATO BLANCO\t38.90 913.98 558.47 1,675.41\n3 PZA (H87) MBTAACJBCO 30181511 TAZA ALARGADA CATO JAZMIN BLANCO\t37.00 1259.58 793.52 2,380.56\n \nTIPO\nRecoger\nPRECIOS SUJETOS A CAMBIO SIN PREVIO AVISO, PRECIOS EN PAGOS DE\nCONTADO, VIGENCIA DE COTIZACION 5 DIAS VALIDA HASTA 2026-04-22\nAGENTE: ISRAEL JESUS CARDENAS ESPARZA\n SUC: GRAN MONTEJO\n TEL: NA\n    \nSUBTOTAL:\nDESCUENTO:\nIVA:\nTOTAL:\n6,520.68\n2,464.71\n648.96\n4,704.93\n-Para deposito o transferencia referenciada se requiere capturar la referencia \u00fanica en el apartado REFERENCIA DE PAGO o CONCEPTO DE PAGO seg\u00fan la banca\nelectr\u00f3nica de la instituci\u00f3n de su preferencia para que esta pueda proceder de manera exitosa.\nReferencia unica: 28000012096145787231\nBanco \/ Convenio \tClave Interbancaria \nBancomer convenio BBVA CIE\/Contrato: 1697439\tCLABE interbancaria: 012914002016974396\nHSBC Clave de servicio RAP: 2555\tCLABE interbancaria: 021180550300025558\nP\u00e1gina 1\/1"}',NULL,1,0,NULL);
INSERT INTO "quotations" VALUES (16,16,1,'quotations/3C7gqQQ5ByvhrHXgyaNk7bNIZybrzLF52dLdirCp.jpg','','2026-05-30 01:20:40','2026-05-30 01:20:06','2026-05-30 01:21:34',NULL,'WhatsApp Image 2026-04-17 at 4.29.57 PM.jpeg','completed','[Extraído directamente por Gemini Vision]','{"supplier":"Boxito","store":"M\u00e9rida","seller":"Leticia Alejandra Dzul Uh","tax_info":{"tax_rate":0.16,"prices_include_tax":false,"tax_detected":true,"subtotal":22400.73,"tax_total":3584.11,"grand_total":25984.84},"items":[{"name":"REMATE VENTILA SANIT 50","category":"Plomer\u00eda","quantity":70,"unit":"pza","unit_name":"Pieza","unit_price":20.28,"discount":0,"tax_amount":227.14,"price_includes_tax":false,"line_subtotal":1419.6,"line_total":1646.74},{"name":"TUBO SAN NORMA 110","category":"Plomer\u00eda","quantity":42,"unit":"m","unit_name":"Metro lineal","unit_price":39.96,"discount":0,"tax_amount":268.53,"price_includes_tax":false,"line_subtotal":1678.32,"line_total":1946.85},{"name":"PEGAMENTO PVC (4 OZ) 118 ML TRANSPAREN OATEY","category":"Plomer\u00eda","quantity":9,"unit":"pza","unit_name":"Pieza","unit_price":52.87,"discount":0,"tax_amount":76.13,"price_includes_tax":false,"line_subtotal":475.83,"line_total":551.96},{"name":"CUELLO DE CERA C\/GUIA EN CAJA PB-104","category":"Plomer\u00eda","quantity":18,"unit":"pza","unit_name":"Pieza","unit_price":24.46,"discount":0,"tax_amount":70.44,"price_includes_tax":false,"line_subtotal":440.28,"line_total":510.72},{"name":"LLAVE D\/CONTROL P\/GAS 1\/2PULGX3\/8PULG IP-200","category":"Plomer\u00eda","quantity":60,"unit":"pza","unit_name":"Pieza","unit_price":61.09,"discount":0,"tax_amount":586.46,"price_includes_tax":false,"line_subtotal":3665.4,"line_total":4251.86},{"name":"ADAPTADOR MACHO GAS 1\/2PULG X 1\/2PULG RM","category":"Plomer\u00eda","quantity":60,"unit":"pza","unit_name":"Pieza","unit_price":40.14,"discount":0,"tax_amount":385.34,"price_includes_tax":false,"line_subtotal":2408.4,"line_total":2793.74},{"name":"TEE GAS 1\/2PULG X 1\/2PULG X 1\/2PULG","category":"Plomer\u00eda","quantity":30,"unit":"pza","unit_name":"Pieza","unit_price":89.42,"discount":0,"tax_amount":429.22,"price_includes_tax":false,"line_subtotal":2682.6,"line_total":3111.82},{"name":"CODO 90\u00b0 GAS 1\/2PULG X 1\/2PULG","category":"Plomer\u00eda","quantity":105,"unit":"pza","unit_name":"Pieza","unit_price":62.56,"discount":0,"tax_amount":1051.01,"price_includes_tax":false,"line_subtotal":6568.8,"line_total":7619.81},{"name":"TUBO PEALPE GAS 1\/2PULG X 100M","category":"Plomer\u00eda","quantity":105,"unit":"m","unit_name":"Metro lineal","unit_price":25.62,"discount":0,"tax_amount":430.42,"price_includes_tax":false,"line_subtotal":2690.1,"line_total":3120.52},{"name":"ABRAZADERA U\u00d1A GALV 200 013 UCR12 PARA CPVC\/COBRE","category":"Plomer\u00eda","quantity":120,"unit":"pza","unit_name":"Pieza","unit_price":1.45,"discount":0,"tax_amount":27.84,"price_includes_tax":false,"line_subtotal":174,"line_total":201.84},{"name":"PIJA COMBINADA 10MM GROSOR 1 LARG","category":"Ferreter\u00eda","quantity":420,"unit":"pza","unit_name":"Pieza","unit_price":0.26,"discount":0,"tax_amount":17.47,"price_includes_tax":false,"line_subtotal":109.2,"line_total":126.67},{"name":"TAQUETE DE PLASTICO 006 (TP14)","category":"Ferreter\u00eda","quantity":420,"unit":"pza","unit_name":"Pieza","unit_price":0.21,"discount":0,"tax_amount":14.11,"price_includes_tax":false,"line_subtotal":88.2,"line_total":102.31}],"raw_text":"[Extra\u00eddo directamente por Gemini Vision]"}',NULL,1,0,NULL);
INSERT INTO "quotations" VALUES (17,NULL,NULL,'quotations/jlZB8Prkxc06z9PIXrmcpGyZEQkt0rHuQmiVrEuy.pdf','','2026-05-30 15:59:51','2026-05-30 15:59:28','2026-06-06 15:44:09',1,'280000120961.pdf','completed','EL NIPLITO DEL SURESTE S.A. DE C.V.    
DIR:CALLE 60 X 89 Y 89-A NO 709 COL CENTRO CP 97000 MERIDA YUCATAN MEXICO RFC: NSU9102113Y9 FECHA: 2026-04-17
CLIENTE: EKILATERO INGENIERIA Y CONSTRUCCION	PRESUPUESTO: 280000120961
CANTIDAD UM (UM-SAT) CODIGO COD-SAT DESCRIPCIONES	% DESCUENTO P. UNITARIOP. DESCUENTO IMPORTE
3 PZA (H87) MBTQCABCO 30181515 TANQUE JAZMIN CATO BLANCO	38.90 913.98 558.47 1,675.41
3 PZA (H87) MBTAACJBCO 30181511 TAZA ALARGADA CATO JAZMIN BLANCO	37.00 1259.58 793.52 2,380.56
 
TIPO
Recoger
PRECIOS SUJETOS A CAMBIO SIN PREVIO AVISO, PRECIOS EN PAGOS DE
CONTADO, VIGENCIA DE COTIZACION 5 DIAS VALIDA HASTA 2026-04-22
AGENTE: ISRAEL JESUS CARDENAS ESPARZA
 SUC: GRAN MONTEJO
 TEL: NA
    
SUBTOTAL:
DESCUENTO:
IVA:
TOTAL:
6,520.68
2,464.71
648.96
4,704.93
-Para deposito o transferencia referenciada se requiere capturar la referencia única en el apartado REFERENCIA DE PAGO o CONCEPTO DE PAGO según la banca
electrónica de la institución de su preferencia para que esta pueda proceder de manera exitosa.
Referencia unica: 28000012096145787231
Banco / Convenio 	Clave Interbancaria 
Bancomer convenio BBVA CIE/Contrato: 1697439	CLABE interbancaria: 012914002016974396
HSBC Clave de servicio RAP: 2555	CLABE interbancaria: 021180550300025558
Página 1/1','{"supplier":"El Niplito Del Sureste","store":"Gran Montejo","seller":"Israel Jesus Cardenas Esparza","tax_info":{"tax_rate":0.16,"prices_include_tax":false,"tax_detected":true,"subtotal":4055.97,"tax_total":648.96,"grand_total":4704.93},"items":[{"name":"TANQUE JAZMIN CATO BLANCO","category":"Plomer\u00eda","quantity":3,"unit":"pza","unit_name":"Pieza","unit_price":913.98,"discount":355.51,"tax_amount":null,"price_includes_tax":false,"line_subtotal":1675.41,"line_total":null},{"name":"TAZA ALARGADA CATO JAZMIN BLANCO","category":"Plomer\u00eda","quantity":3,"unit":"pza","unit_name":"Pieza","unit_price":1259.58,"discount":466.06,"tax_amount":null,"price_includes_tax":false,"line_subtotal":2380.56,"line_total":null}],"raw_text":"EL NIPLITO DEL SURESTE S.A. DE C.V.    \nDIR:CALLE 60 X 89 Y 89-A NO 709 COL CENTRO CP 97000 MERIDA YUCATAN MEXICO RFC: NSU9102113Y9 FECHA: 2026-04-17\nCLIENTE: EKILATERO INGENIERIA Y CONSTRUCCION\tPRESUPUESTO: 280000120961\nCANTIDAD UM (UM-SAT) CODIGO COD-SAT DESCRIPCIONES\t% DESCUENTO P. UNITARIOP. DESCUENTO IMPORTE\n3 PZA (H87) MBTQCABCO 30181515 TANQUE JAZMIN CATO BLANCO\t38.90 913.98 558.47 1,675.41\n3 PZA (H87) MBTAACJBCO 30181511 TAZA ALARGADA CATO JAZMIN BLANCO\t37.00 1259.58 793.52 2,380.56\n \nTIPO\nRecoger\nPRECIOS SUJETOS A CAMBIO SIN PREVIO AVISO, PRECIOS EN PAGOS DE\nCONTADO, VIGENCIA DE COTIZACION 5 DIAS VALIDA HASTA 2026-04-22\nAGENTE: ISRAEL JESUS CARDENAS ESPARZA\n SUC: GRAN MONTEJO\n TEL: NA\n    \nSUBTOTAL:\nDESCUENTO:\nIVA:\nTOTAL:\n6,520.68\n2,464.71\n648.96\n4,704.93\n-Para deposito o transferencia referenciada se requiere capturar la referencia \u00fanica en el apartado REFERENCIA DE PAGO o CONCEPTO DE PAGO seg\u00fan la banca\nelectr\u00f3nica de la instituci\u00f3n de su preferencia para que esta pueda proceder de manera exitosa.\nReferencia unica: 28000012096145787231\nBanco \/ Convenio \tClave Interbancaria \nBancomer convenio BBVA CIE\/Contrato: 1697439\tCLABE interbancaria: 012914002016974396\nHSBC Clave de servicio RAP: 2555\tCLABE interbancaria: 021180550300025558\nP\u00e1gina 1\/1"}',NULL,1,1,NULL);
INSERT INTO "quotations" VALUES (18,NULL,NULL,'quotations/WHKDGstrFJULJ08CcAijluPxGwdj90tlhZGtG1v5.pdf','','2026-05-30 16:53:54','2026-05-30 16:53:33','2026-06-06 15:44:12',NULL,'280000120961.pdf','completed','EL NIPLITO DEL SURESTE S.A. DE C.V.    
DIR:CALLE 60 X 89 Y 89-A NO 709 COL CENTRO CP 97000 MERIDA YUCATAN MEXICO RFC: NSU9102113Y9 FECHA: 2026-04-17
CLIENTE: EKILATERO INGENIERIA Y CONSTRUCCION	PRESUPUESTO: 280000120961
CANTIDAD UM (UM-SAT) CODIGO COD-SAT DESCRIPCIONES	% DESCUENTO P. UNITARIOP. DESCUENTO IMPORTE
3 PZA (H87) MBTQCABCO 30181515 TANQUE JAZMIN CATO BLANCO	38.90 913.98 558.47 1,675.41
3 PZA (H87) MBTAACJBCO 30181511 TAZA ALARGADA CATO JAZMIN BLANCO	37.00 1259.58 793.52 2,380.56
 
TIPO
Recoger
PRECIOS SUJETOS A CAMBIO SIN PREVIO AVISO, PRECIOS EN PAGOS DE
CONTADO, VIGENCIA DE COTIZACION 5 DIAS VALIDA HASTA 2026-04-22
AGENTE: ISRAEL JESUS CARDENAS ESPARZA
 SUC: GRAN MONTEJO
 TEL: NA
    
SUBTOTAL:
DESCUENTO:
IVA:
TOTAL:
6,520.68
2,464.71
648.96
4,704.93
-Para deposito o transferencia referenciada se requiere capturar la referencia única en el apartado REFERENCIA DE PAGO o CONCEPTO DE PAGO según la banca
electrónica de la institución de su preferencia para que esta pueda proceder de manera exitosa.
Referencia unica: 28000012096145787231
Banco / Convenio 	Clave Interbancaria 
Bancomer convenio BBVA CIE/Contrato: 1697439	CLABE interbancaria: 012914002016974396
HSBC Clave de servicio RAP: 2555	CLABE interbancaria: 021180550300025558
Página 1/1','{"supplier":"El Niplito Del Sureste S.A. De C.V.","store":"Gran Montejo","seller":"Israel Jesus Cardenas Esparza","tax_info":{"tax_rate":0.16,"prices_include_tax":false,"tax_detected":true,"subtotal":4055.97,"tax_total":648.96,"grand_total":4704.93},"items":[{"name":"TANQUE JAZMIN CATO BLANCO","category":"Plomer\u00eda","quantity":3,"unit":"pza","unit_name":"Pieza","unit_price":913.98,"discount":1066.53,"tax_amount":268.07,"price_includes_tax":false,"line_subtotal":1675.41,"line_total":1943.48},{"name":"TAZA ALARGADA CATO JAZMIN BLANCO","category":"Plomer\u00eda","quantity":3,"unit":"pza","unit_name":"Pieza","unit_price":1259.58,"discount":1398.18,"tax_amount":380.89,"price_includes_tax":false,"line_subtotal":2380.56,"line_total":2761.45}],"raw_text":"EL NIPLITO DEL SURESTE S.A. DE C.V.    \nDIR:CALLE 60 X 89 Y 89-A NO 709 COL CENTRO CP 97000 MERIDA YUCATAN MEXICO RFC: NSU9102113Y9 FECHA: 2026-04-17\nCLIENTE: EKILATERO INGENIERIA Y CONSTRUCCION\tPRESUPUESTO: 280000120961\nCANTIDAD UM (UM-SAT) CODIGO COD-SAT DESCRIPCIONES\t% DESCUENTO P. UNITARIOP. DESCUENTO IMPORTE\n3 PZA (H87) MBTQCABCO 30181515 TANQUE JAZMIN CATO BLANCO\t38.90 913.98 558.47 1,675.41\n3 PZA (H87) MBTAACJBCO 30181511 TAZA ALARGADA CATO JAZMIN BLANCO\t37.00 1259.58 793.52 2,380.56\n \nTIPO\nRecoger\nPRECIOS SUJETOS A CAMBIO SIN PREVIO AVISO, PRECIOS EN PAGOS DE\nCONTADO, VIGENCIA DE COTIZACION 5 DIAS VALIDA HASTA 2026-04-22\nAGENTE: ISRAEL JESUS CARDENAS ESPARZA\n SUC: GRAN MONTEJO\n TEL: NA\n    \nSUBTOTAL:\nDESCUENTO:\nIVA:\nTOTAL:\n6,520.68\n2,464.71\n648.96\n4,704.93\n-Para deposito o transferencia referenciada se requiere capturar la referencia \u00fanica en el apartado REFERENCIA DE PAGO o CONCEPTO DE PAGO seg\u00fan la banca\nelectr\u00f3nica de la instituci\u00f3n de su preferencia para que esta pueda proceder de manera exitosa.\nReferencia unica: 28000012096145787231\nBanco \/ Convenio \tClave Interbancaria \nBancomer convenio BBVA CIE\/Contrato: 1697439\tCLABE interbancaria: 012914002016974396\nHSBC Clave de servicio RAP: 2555\tCLABE interbancaria: 021180550300025558\nP\u00e1gina 1\/1"}',NULL,1,1,NULL);
INSERT INTO "quotations" VALUES (19,NULL,NULL,'quotations/tajJQDnccIstIkr0yJWPWOhLf7KmA1j8bSvm9C11.pdf','','2026-05-30 16:59:10','2026-05-30 16:58:52','2026-06-06 15:44:17',NULL,'280000120961.pdf','completed','EL NIPLITO DEL SURESTE S.A. DE C.V.    
DIR:CALLE 60 X 89 Y 89-A NO 709 COL CENTRO CP 97000 MERIDA YUCATAN MEXICO RFC: NSU9102113Y9 FECHA: 2026-04-17
CLIENTE: EKILATERO INGENIERIA Y CONSTRUCCION	PRESUPUESTO: 280000120961
CANTIDAD UM (UM-SAT) CODIGO COD-SAT DESCRIPCIONES	% DESCUENTO P. UNITARIOP. DESCUENTO IMPORTE
3 PZA (H87) MBTQCABCO 30181515 TANQUE JAZMIN CATO BLANCO	38.90 913.98 558.47 1,675.41
3 PZA (H87) MBTAACJBCO 30181511 TAZA ALARGADA CATO JAZMIN BLANCO	37.00 1259.58 793.52 2,380.56
 
TIPO
Recoger
PRECIOS SUJETOS A CAMBIO SIN PREVIO AVISO, PRECIOS EN PAGOS DE
CONTADO, VIGENCIA DE COTIZACION 5 DIAS VALIDA HASTA 2026-04-22
AGENTE: ISRAEL JESUS CARDENAS ESPARZA
 SUC: GRAN MONTEJO
 TEL: NA
    
SUBTOTAL:
DESCUENTO:
IVA:
TOTAL:
6,520.68
2,464.71
648.96
4,704.93
-Para deposito o transferencia referenciada se requiere capturar la referencia única en el apartado REFERENCIA DE PAGO o CONCEPTO DE PAGO según la banca
electrónica de la institución de su preferencia para que esta pueda proceder de manera exitosa.
Referencia unica: 28000012096145787231
Banco / Convenio 	Clave Interbancaria 
Bancomer convenio BBVA CIE/Contrato: 1697439	CLABE interbancaria: 012914002016974396
HSBC Clave de servicio RAP: 2555	CLABE interbancaria: 021180550300025558
Página 1/1','{"supplier":"El Niplito Del Sureste","store":"Gran Montejo","seller":"Israel Jesus Cardenas Esparza","tax_info":{"tax_rate":0.16,"prices_include_tax":false,"tax_detected":true,"subtotal":4055.97,"tax_total":648.96,"grand_total":4704.93},"items":[{"name":"TANQUE JAZMIN CATO BLANCO","category":"Plomer\u00eda","quantity":3,"unit":"pza","unit_name":"Pieza","unit_price":913.98,"discount_percent":38.9,"discount_amount":null,"unit_price_with_discount":558.47,"tax_amount":null,"price_includes_tax":false,"line_subtotal":1675.41,"line_total":null},{"name":"TAZA ALARGADA CATO JAZMIN BLANCO","category":"Plomer\u00eda","quantity":3,"unit":"pza","unit_name":"Pieza","unit_price":1259.58,"discount_percent":37,"discount_amount":null,"unit_price_with_discount":793.52,"tax_amount":null,"price_includes_tax":false,"line_subtotal":2380.56,"line_total":null}],"raw_text":"EL NIPLITO DEL SURESTE S.A. DE C.V.    \nDIR:CALLE 60 X 89 Y 89-A NO 709 COL CENTRO CP 97000 MERIDA YUCATAN MEXICO RFC: NSU9102113Y9 FECHA: 2026-04-17\nCLIENTE: EKILATERO INGENIERIA Y CONSTRUCCION\tPRESUPUESTO: 280000120961\nCANTIDAD UM (UM-SAT) CODIGO COD-SAT DESCRIPCIONES\t% DESCUENTO P. UNITARIOP. DESCUENTO IMPORTE\n3 PZA (H87) MBTQCABCO 30181515 TANQUE JAZMIN CATO BLANCO\t38.90 913.98 558.47 1,675.41\n3 PZA (H87) MBTAACJBCO 30181511 TAZA ALARGADA CATO JAZMIN BLANCO\t37.00 1259.58 793.52 2,380.56\n \nTIPO\nRecoger\nPRECIOS SUJETOS A CAMBIO SIN PREVIO AVISO, PRECIOS EN PAGOS DE\nCONTADO, VIGENCIA DE COTIZACION 5 DIAS VALIDA HASTA 2026-04-22\nAGENTE: ISRAEL JESUS CARDENAS ESPARZA\n SUC: GRAN MONTEJO\n TEL: NA\n    \nSUBTOTAL:\nDESCUENTO:\nIVA:\nTOTAL:\n6,520.68\n2,464.71\n648.96\n4,704.93\n-Para deposito o transferencia referenciada se requiere capturar la referencia \u00fanica en el apartado REFERENCIA DE PAGO o CONCEPTO DE PAGO seg\u00fan la banca\nelectr\u00f3nica de la instituci\u00f3n de su preferencia para que esta pueda proceder de manera exitosa.\nReferencia unica: 28000012096145787231\nBanco \/ Convenio \tClave Interbancaria \nBancomer convenio BBVA CIE\/Contrato: 1697439\tCLABE interbancaria: 012914002016974396\nHSBC Clave de servicio RAP: 2555\tCLABE interbancaria: 021180550300025558\nP\u00e1gina 1\/1"}',NULL,1,1,NULL);
INSERT INTO "quotations" VALUES (20,NULL,NULL,'quotations/Sw4J6q9UjttEf356c8IOwD0gzZI1uZmCzj91HBps.pdf','','2026-05-30 17:05:26','2026-05-30 17:05:06','2026-05-30 17:05:26',NULL,'280000120961.pdf','completed','EL NIPLITO DEL SURESTE S.A. DE C.V.    
DIR:CALLE 60 X 89 Y 89-A NO 709 COL CENTRO CP 97000 MERIDA YUCATAN MEXICO RFC: NSU9102113Y9 FECHA: 2026-04-17
CLIENTE: EKILATERO INGENIERIA Y CONSTRUCCION	PRESUPUESTO: 280000120961
CANTIDAD UM (UM-SAT) CODIGO COD-SAT DESCRIPCIONES	% DESCUENTO P. UNITARIOP. DESCUENTO IMPORTE
3 PZA (H87) MBTQCABCO 30181515 TANQUE JAZMIN CATO BLANCO	38.90 913.98 558.47 1,675.41
3 PZA (H87) MBTAACJBCO 30181511 TAZA ALARGADA CATO JAZMIN BLANCO	37.00 1259.58 793.52 2,380.56
 
TIPO
Recoger
PRECIOS SUJETOS A CAMBIO SIN PREVIO AVISO, PRECIOS EN PAGOS DE
CONTADO, VIGENCIA DE COTIZACION 5 DIAS VALIDA HASTA 2026-04-22
AGENTE: ISRAEL JESUS CARDENAS ESPARZA
 SUC: GRAN MONTEJO
 TEL: NA
    
SUBTOTAL:
DESCUENTO:
IVA:
TOTAL:
6,520.68
2,464.71
648.96
4,704.93
-Para deposito o transferencia referenciada se requiere capturar la referencia única en el apartado REFERENCIA DE PAGO o CONCEPTO DE PAGO según la banca
electrónica de la institución de su preferencia para que esta pueda proceder de manera exitosa.
Referencia unica: 28000012096145787231
Banco / Convenio 	Clave Interbancaria 
Bancomer convenio BBVA CIE/Contrato: 1697439	CLABE interbancaria: 012914002016974396
HSBC Clave de servicio RAP: 2555	CLABE interbancaria: 021180550300025558
Página 1/1','{"supplier":"El Niplito Del Sureste","store":"Gran Montejo","seller":"Israel Jesus Cardenas Esparza","tax_info":{"tax_rate":0.16,"prices_include_tax":false,"tax_detected":true,"subtotal":6520.68,"tax_total":648.96,"grand_total":4704.93},"items":[{"name":"TANQUE JAZMIN CATO BLANCO","category":"Plomer\u00eda","quantity":3,"unit":"pza","unit_name":"Pieza","unit_price":913.98,"discount_percent":38.9,"discount_amount":null,"unit_price_with_discount":558.47,"tax_amount":null,"price_includes_tax":false,"line_subtotal":1675.41,"line_total":null},{"name":"TAZA ALARGADA CATO JAZMIN BLANCO","category":"Plomer\u00eda","quantity":3,"unit":"pza","unit_name":"Pieza","unit_price":1259.58,"discount_percent":37,"discount_amount":null,"unit_price_with_discount":793.52,"tax_amount":null,"price_includes_tax":false,"line_subtotal":2380.56,"line_total":null}],"raw_text":"EL NIPLITO DEL SURESTE S.A. DE C.V.    \nDIR:CALLE 60 X 89 Y 89-A NO 709 COL CENTRO CP 97000 MERIDA YUCATAN MEXICO RFC: NSU9102113Y9 FECHA: 2026-04-17\nCLIENTE: EKILATERO INGENIERIA Y CONSTRUCCION\tPRESUPUESTO: 280000120961\nCANTIDAD UM (UM-SAT) CODIGO COD-SAT DESCRIPCIONES\t% DESCUENTO P. UNITARIOP. DESCUENTO IMPORTE\n3 PZA (H87) MBTQCABCO 30181515 TANQUE JAZMIN CATO BLANCO\t38.90 913.98 558.47 1,675.41\n3 PZA (H87) MBTAACJBCO 30181511 TAZA ALARGADA CATO JAZMIN BLANCO\t37.00 1259.58 793.52 2,380.56\n \nTIPO\nRecoger\nPRECIOS SUJETOS A CAMBIO SIN PREVIO AVISO, PRECIOS EN PAGOS DE\nCONTADO, VIGENCIA DE COTIZACION 5 DIAS VALIDA HASTA 2026-04-22\nAGENTE: ISRAEL JESUS CARDENAS ESPARZA\n SUC: GRAN MONTEJO\n TEL: NA\n    \nSUBTOTAL:\nDESCUENTO:\nIVA:\nTOTAL:\n6,520.68\n2,464.71\n648.96\n4,704.93\n-Para deposito o transferencia referenciada se requiere capturar la referencia \u00fanica en el apartado REFERENCIA DE PAGO o CONCEPTO DE PAGO seg\u00fan la banca\nelectr\u00f3nica de la instituci\u00f3n de su preferencia para que esta pueda proceder de manera exitosa.\nReferencia unica: 28000012096145787231\nBanco \/ Convenio \tClave Interbancaria \nBancomer convenio BBVA CIE\/Contrato: 1697439\tCLABE interbancaria: 012914002016974396\nHSBC Clave de servicio RAP: 2555\tCLABE interbancaria: 021180550300025558\nP\u00e1gina 1\/1"}',NULL,1,0,NULL);
INSERT INTO "quotations" VALUES (21,NULL,NULL,'quotations/kCGHferDUXnDMp3xX9SVWDVwBdSwhPpAbCI8xHj3.pdf','','2026-05-30 23:01:49','2026-05-30 23:01:33','2026-05-30 23:01:49',NULL,'280000120961.pdf','completed','EL NIPLITO DEL SURESTE S.A. DE C.V.    
DIR:CALLE 60 X 89 Y 89-A NO 709 COL CENTRO CP 97000 MERIDA YUCATAN MEXICO RFC: NSU9102113Y9 FECHA: 2026-04-17
CLIENTE: EKILATERO INGENIERIA Y CONSTRUCCION	PRESUPUESTO: 280000120961
CANTIDAD UM (UM-SAT) CODIGO COD-SAT DESCRIPCIONES	% DESCUENTO P. UNITARIOP. DESCUENTO IMPORTE
3 PZA (H87) MBTQCABCO 30181515 TANQUE JAZMIN CATO BLANCO	38.90 913.98 558.47 1,675.41
3 PZA (H87) MBTAACJBCO 30181511 TAZA ALARGADA CATO JAZMIN BLANCO	37.00 1259.58 793.52 2,380.56
 
TIPO
Recoger
PRECIOS SUJETOS A CAMBIO SIN PREVIO AVISO, PRECIOS EN PAGOS DE
CONTADO, VIGENCIA DE COTIZACION 5 DIAS VALIDA HASTA 2026-04-22
AGENTE: ISRAEL JESUS CARDENAS ESPARZA
 SUC: GRAN MONTEJO
 TEL: NA
    
SUBTOTAL:
DESCUENTO:
IVA:
TOTAL:
6,520.68
2,464.71
648.96
4,704.93
-Para deposito o transferencia referenciada se requiere capturar la referencia única en el apartado REFERENCIA DE PAGO o CONCEPTO DE PAGO según la banca
electrónica de la institución de su preferencia para que esta pueda proceder de manera exitosa.
Referencia unica: 28000012096145787231
Banco / Convenio 	Clave Interbancaria 
Bancomer convenio BBVA CIE/Contrato: 1697439	CLABE interbancaria: 012914002016974396
HSBC Clave de servicio RAP: 2555	CLABE interbancaria: 021180550300025558
Página 1/1','{"supplier":"El Niplito Del Sureste","store":"Gran Montejo","seller":"Israel Jesus Cardenas Esparza","tax_info":{"tax_rate":0.16,"prices_include_tax":false,"tax_detected":true,"subtotal":4055.97,"tax_total":648.96,"grand_total":4704.93},"items":[{"name":"TANQUE JAZMIN CATO BLANCO","category":"Plomer\u00eda","quantity":3,"unit":"pza","unit_name":"Pieza","unit_price":913.98,"discount_percent":38.9,"discount_amount":355.51,"unit_price_with_discount":558.47,"tax_amount":null,"price_includes_tax":false,"line_subtotal":1675.41,"line_total":null},{"name":"TAZA ALARGADA CATO JAZMIN BLANCO","category":"Plomer\u00eda","quantity":3,"unit":"pza","unit_name":"Pieza","unit_price":1259.58,"discount_percent":37,"discount_amount":466.06,"unit_price_with_discount":793.52,"tax_amount":null,"price_includes_tax":false,"line_subtotal":2380.56,"line_total":null}],"raw_text":"EL NIPLITO DEL SURESTE S.A. DE C.V.    \nDIR:CALLE 60 X 89 Y 89-A NO 709 COL CENTRO CP 97000 MERIDA YUCATAN MEXICO RFC: NSU9102113Y9 FECHA: 2026-04-17\nCLIENTE: EKILATERO INGENIERIA Y CONSTRUCCION\tPRESUPUESTO: 280000120961\nCANTIDAD UM (UM-SAT) CODIGO COD-SAT DESCRIPCIONES\t% DESCUENTO P. UNITARIOP. DESCUENTO IMPORTE\n3 PZA (H87) MBTQCABCO 30181515 TANQUE JAZMIN CATO BLANCO\t38.90 913.98 558.47 1,675.41\n3 PZA (H87) MBTAACJBCO 30181511 TAZA ALARGADA CATO JAZMIN BLANCO\t37.00 1259.58 793.52 2,380.56\n \nTIPO\nRecoger\nPRECIOS SUJETOS A CAMBIO SIN PREVIO AVISO, PRECIOS EN PAGOS DE\nCONTADO, VIGENCIA DE COTIZACION 5 DIAS VALIDA HASTA 2026-04-22\nAGENTE: ISRAEL JESUS CARDENAS ESPARZA\n SUC: GRAN MONTEJO\n TEL: NA\n    \nSUBTOTAL:\nDESCUENTO:\nIVA:\nTOTAL:\n6,520.68\n2,464.71\n648.96\n4,704.93\n-Para deposito o transferencia referenciada se requiere capturar la referencia \u00fanica en el apartado REFERENCIA DE PAGO o CONCEPTO DE PAGO seg\u00fan la banca\nelectr\u00f3nica de la instituci\u00f3n de su preferencia para que esta pueda proceder de manera exitosa.\nReferencia unica: 28000012096145787231\nBanco \/ Convenio \tClave Interbancaria \nBancomer convenio BBVA CIE\/Contrato: 1697439\tCLABE interbancaria: 012914002016974396\nHSBC Clave de servicio RAP: 2555\tCLABE interbancaria: 021180550300025558\nP\u00e1gina 1\/1"}',NULL,1,0,NULL);
INSERT INTO "quotations" VALUES (22,NULL,NULL,'quotations/VJee8ct886UOlImRfs9AKDVQIDMDc3tnBSUP5Beg.pdf','','2026-05-30 23:31:25','2026-05-30 23:31:07','2026-05-30 23:31:25',NULL,'280000120961.pdf','completed','EL NIPLITO DEL SURESTE S.A. DE C.V.    
DIR:CALLE 60 X 89 Y 89-A NO 709 COL CENTRO CP 97000 MERIDA YUCATAN MEXICO RFC: NSU9102113Y9 FECHA: 2026-04-17
CLIENTE: EKILATERO INGENIERIA Y CONSTRUCCION	PRESUPUESTO: 280000120961
CANTIDAD UM (UM-SAT) CODIGO COD-SAT DESCRIPCIONES	% DESCUENTO P. UNITARIOP. DESCUENTO IMPORTE
3 PZA (H87) MBTQCABCO 30181515 TANQUE JAZMIN CATO BLANCO	38.90 913.98 558.47 1,675.41
3 PZA (H87) MBTAACJBCO 30181511 TAZA ALARGADA CATO JAZMIN BLANCO	37.00 1259.58 793.52 2,380.56
 
TIPO
Recoger
PRECIOS SUJETOS A CAMBIO SIN PREVIO AVISO, PRECIOS EN PAGOS DE
CONTADO, VIGENCIA DE COTIZACION 5 DIAS VALIDA HASTA 2026-04-22
AGENTE: ISRAEL JESUS CARDENAS ESPARZA
 SUC: GRAN MONTEJO
 TEL: NA
    
SUBTOTAL:
DESCUENTO:
IVA:
TOTAL:
6,520.68
2,464.71
648.96
4,704.93
-Para deposito o transferencia referenciada se requiere capturar la referencia única en el apartado REFERENCIA DE PAGO o CONCEPTO DE PAGO según la banca
electrónica de la institución de su preferencia para que esta pueda proceder de manera exitosa.
Referencia unica: 28000012096145787231
Banco / Convenio 	Clave Interbancaria 
Bancomer convenio BBVA CIE/Contrato: 1697439	CLABE interbancaria: 012914002016974396
HSBC Clave de servicio RAP: 2555	CLABE interbancaria: 021180550300025558
Página 1/1','{"supplier":"El Niplito Del Sureste","store":"Gran Montejo","seller":"Israel Jesus Cardenas Esparza","tax_info":{"tax_rate":0.16,"prices_include_tax":false,"tax_detected":true,"subtotal":4055.97,"tax_total":648.96,"grand_total":4704.93},"items":[{"name":"TANQUE JAZMIN CATO BLANCO","category":"Plomer\u00eda","quantity":3,"unit":"pza","unit_name":"Pieza","unit_price":913.98,"discount_percent":38.9,"discount_amount":355.51,"unit_price_with_discount":558.47,"tax_amount":268.07,"price_includes_tax":false,"line_subtotal":1675.41,"line_total":1943.48},{"name":"TAZA ALARGADA CATO JAZMIN BLANCO","category":"Plomer\u00eda","quantity":3,"unit":"pza","unit_name":"Pieza","unit_price":1259.58,"discount_percent":37,"discount_amount":466.06,"unit_price_with_discount":793.52,"tax_amount":380.89,"price_includes_tax":false,"line_subtotal":2380.56,"line_total":2761.45}],"raw_text":"EL NIPLITO DEL SURESTE S.A. DE C.V.    \nDIR:CALLE 60 X 89 Y 89-A NO 709 COL CENTRO CP 97000 MERIDA YUCATAN MEXICO RFC: NSU9102113Y9 FECHA: 2026-04-17\nCLIENTE: EKILATERO INGENIERIA Y CONSTRUCCION\tPRESUPUESTO: 280000120961\nCANTIDAD UM (UM-SAT) CODIGO COD-SAT DESCRIPCIONES\t% DESCUENTO P. UNITARIOP. DESCUENTO IMPORTE\n3 PZA (H87) MBTQCABCO 30181515 TANQUE JAZMIN CATO BLANCO\t38.90 913.98 558.47 1,675.41\n3 PZA (H87) MBTAACJBCO 30181511 TAZA ALARGADA CATO JAZMIN BLANCO\t37.00 1259.58 793.52 2,380.56\n \nTIPO\nRecoger\nPRECIOS SUJETOS A CAMBIO SIN PREVIO AVISO, PRECIOS EN PAGOS DE\nCONTADO, VIGENCIA DE COTIZACION 5 DIAS VALIDA HASTA 2026-04-22\nAGENTE: ISRAEL JESUS CARDENAS ESPARZA\n SUC: GRAN MONTEJO\n TEL: NA\n    \nSUBTOTAL:\nDESCUENTO:\nIVA:\nTOTAL:\n6,520.68\n2,464.71\n648.96\n4,704.93\n-Para deposito o transferencia referenciada se requiere capturar la referencia \u00fanica en el apartado REFERENCIA DE PAGO o CONCEPTO DE PAGO seg\u00fan la banca\nelectr\u00f3nica de la instituci\u00f3n de su preferencia para que esta pueda proceder de manera exitosa.\nReferencia unica: 28000012096145787231\nBanco \/ Convenio \tClave Interbancaria \nBancomer convenio BBVA CIE\/Contrato: 1697439\tCLABE interbancaria: 012914002016974396\nHSBC Clave de servicio RAP: 2555\tCLABE interbancaria: 021180550300025558\nP\u00e1gina 1\/1"}',NULL,1,0,NULL);
INSERT INTO "quotations" VALUES (23,NULL,NULL,'quotations/0SWnLGFmI0J4zxHvfSiX3PGh18yko6kEtzazPBfX.pdf','','2026-05-30 23:34:23','2026-05-30 23:34:05','2026-05-30 23:34:23',NULL,'280000120961.pdf','completed','EL NIPLITO DEL SURESTE S.A. DE C.V.    
DIR:CALLE 60 X 89 Y 89-A NO 709 COL CENTRO CP 97000 MERIDA YUCATAN MEXICO RFC: NSU9102113Y9 FECHA: 2026-04-17
CLIENTE: EKILATERO INGENIERIA Y CONSTRUCCION	PRESUPUESTO: 280000120961
CANTIDAD UM (UM-SAT) CODIGO COD-SAT DESCRIPCIONES	% DESCUENTO P. UNITARIOP. DESCUENTO IMPORTE
3 PZA (H87) MBTQCABCO 30181515 TANQUE JAZMIN CATO BLANCO	38.90 913.98 558.47 1,675.41
3 PZA (H87) MBTAACJBCO 30181511 TAZA ALARGADA CATO JAZMIN BLANCO	37.00 1259.58 793.52 2,380.56
 
TIPO
Recoger
PRECIOS SUJETOS A CAMBIO SIN PREVIO AVISO, PRECIOS EN PAGOS DE
CONTADO, VIGENCIA DE COTIZACION 5 DIAS VALIDA HASTA 2026-04-22
AGENTE: ISRAEL JESUS CARDENAS ESPARZA
 SUC: GRAN MONTEJO
 TEL: NA
    
SUBTOTAL:
DESCUENTO:
IVA:
TOTAL:
6,520.68
2,464.71
648.96
4,704.93
-Para deposito o transferencia referenciada se requiere capturar la referencia única en el apartado REFERENCIA DE PAGO o CONCEPTO DE PAGO según la banca
electrónica de la institución de su preferencia para que esta pueda proceder de manera exitosa.
Referencia unica: 28000012096145787231
Banco / Convenio 	Clave Interbancaria 
Bancomer convenio BBVA CIE/Contrato: 1697439	CLABE interbancaria: 012914002016974396
HSBC Clave de servicio RAP: 2555	CLABE interbancaria: 021180550300025558
Página 1/1','{"supplier":"El Niplito Del Sureste S.A. De C.V.","store":"Gran Montejo","seller":"Israel Jesus Cardenas Esparza","tax_info":{"tax_rate":0.16,"prices_include_tax":false,"tax_detected":true,"subtotal":4055.97,"tax_total":648.96,"grand_total":4704.93},"items":[{"name":"TANQUE JAZMIN CATO BLANCO","category":"Plomer\u00eda","quantity":3,"unit":"pza","unit_name":"Pieza","unit_price":913.98,"discount_percent":38.9,"discount_amount":null,"unit_price_with_discount":558.47,"tax_amount":null,"price_includes_tax":false,"line_subtotal":1675.41,"line_total":null},{"name":"TAZA ALARGADA CATO JAZMIN BLANCO","category":"Plomer\u00eda","quantity":3,"unit":"pza","unit_name":"Pieza","unit_price":1259.58,"discount_percent":37,"discount_amount":null,"unit_price_with_discount":793.52,"tax_amount":null,"price_includes_tax":false,"line_subtotal":2380.56,"line_total":null}],"raw_text":"EL NIPLITO DEL SURESTE S.A. DE C.V.    \nDIR:CALLE 60 X 89 Y 89-A NO 709 COL CENTRO CP 97000 MERIDA YUCATAN MEXICO RFC: NSU9102113Y9 FECHA: 2026-04-17\nCLIENTE: EKILATERO INGENIERIA Y CONSTRUCCION\tPRESUPUESTO: 280000120961\nCANTIDAD UM (UM-SAT) CODIGO COD-SAT DESCRIPCIONES\t% DESCUENTO P. UNITARIOP. DESCUENTO IMPORTE\n3 PZA (H87) MBTQCABCO 30181515 TANQUE JAZMIN CATO BLANCO\t38.90 913.98 558.47 1,675.41\n3 PZA (H87) MBTAACJBCO 30181511 TAZA ALARGADA CATO JAZMIN BLANCO\t37.00 1259.58 793.52 2,380.56\n \nTIPO\nRecoger\nPRECIOS SUJETOS A CAMBIO SIN PREVIO AVISO, PRECIOS EN PAGOS DE\nCONTADO, VIGENCIA DE COTIZACION 5 DIAS VALIDA HASTA 2026-04-22\nAGENTE: ISRAEL JESUS CARDENAS ESPARZA\n SUC: GRAN MONTEJO\n TEL: NA\n    \nSUBTOTAL:\nDESCUENTO:\nIVA:\nTOTAL:\n6,520.68\n2,464.71\n648.96\n4,704.93\n-Para deposito o transferencia referenciada se requiere capturar la referencia \u00fanica en el apartado REFERENCIA DE PAGO o CONCEPTO DE PAGO seg\u00fan la banca\nelectr\u00f3nica de la instituci\u00f3n de su preferencia para que esta pueda proceder de manera exitosa.\nReferencia unica: 28000012096145787231\nBanco \/ Convenio \tClave Interbancaria \nBancomer convenio BBVA CIE\/Contrato: 1697439\tCLABE interbancaria: 012914002016974396\nHSBC Clave de servicio RAP: 2555\tCLABE interbancaria: 021180550300025558\nP\u00e1gina 1\/1"}',NULL,1,0,NULL);
INSERT INTO "quotations" VALUES (24,NULL,NULL,'quotations/dfeu5pLAFRJfBwsNvlCWGl8lcKEvozYHVwNMGsED.pdf','','2026-05-30 23:39:39','2026-05-30 23:39:21','2026-05-30 23:39:39',NULL,'280000120961.pdf','completed','EL NIPLITO DEL SURESTE S.A. DE C.V.    
DIR:CALLE 60 X 89 Y 89-A NO 709 COL CENTRO CP 97000 MERIDA YUCATAN MEXICO RFC: NSU9102113Y9 FECHA: 2026-04-17
CLIENTE: EKILATERO INGENIERIA Y CONSTRUCCION	PRESUPUESTO: 280000120961
CANTIDAD UM (UM-SAT) CODIGO COD-SAT DESCRIPCIONES	% DESCUENTO P. UNITARIOP. DESCUENTO IMPORTE
3 PZA (H87) MBTQCABCO 30181515 TANQUE JAZMIN CATO BLANCO	38.90 913.98 558.47 1,675.41
3 PZA (H87) MBTAACJBCO 30181511 TAZA ALARGADA CATO JAZMIN BLANCO	37.00 1259.58 793.52 2,380.56
 
TIPO
Recoger
PRECIOS SUJETOS A CAMBIO SIN PREVIO AVISO, PRECIOS EN PAGOS DE
CONTADO, VIGENCIA DE COTIZACION 5 DIAS VALIDA HASTA 2026-04-22
AGENTE: ISRAEL JESUS CARDENAS ESPARZA
 SUC: GRAN MONTEJO
 TEL: NA
    
SUBTOTAL:
DESCUENTO:
IVA:
TOTAL:
6,520.68
2,464.71
648.96
4,704.93
-Para deposito o transferencia referenciada se requiere capturar la referencia única en el apartado REFERENCIA DE PAGO o CONCEPTO DE PAGO según la banca
electrónica de la institución de su preferencia para que esta pueda proceder de manera exitosa.
Referencia unica: 28000012096145787231
Banco / Convenio 	Clave Interbancaria 
Bancomer convenio BBVA CIE/Contrato: 1697439	CLABE interbancaria: 012914002016974396
HSBC Clave de servicio RAP: 2555	CLABE interbancaria: 021180550300025558
Página 1/1','{"supplier":"El Niplito Del Sureste","store":"Gran Montejo","seller":"Israel Jesus Cardenas Esparza","tax_info":{"tax_rate":0.16,"prices_include_tax":false,"tax_detected":true,"subtotal":4055.97,"tax_total":648.96,"grand_total":4704.93},"items":[{"name":"TANQUE JAZMIN CATO BLANCO","category":"Plomer\u00eda","quantity":3,"unit":"pza","unit_name":"Pieza","unit_price":913.98,"discount_percent":38.9,"discount_amount":null,"unit_price_with_discount":558.47,"tax_amount":null,"price_includes_tax":false,"line_subtotal":1675.41,"line_total":null},{"name":"TAZA ALARGADA CATO JAZMIN BLANCO","category":"Plomer\u00eda","quantity":3,"unit":"pza","unit_name":"Pieza","unit_price":1259.58,"discount_percent":37,"discount_amount":null,"unit_price_with_discount":793.52,"tax_amount":null,"price_includes_tax":false,"line_subtotal":2380.56,"line_total":null}],"raw_text":"EL NIPLITO DEL SURESTE S.A. DE C.V.    \nDIR:CALLE 60 X 89 Y 89-A NO 709 COL CENTRO CP 97000 MERIDA YUCATAN MEXICO RFC: NSU9102113Y9 FECHA: 2026-04-17\nCLIENTE: EKILATERO INGENIERIA Y CONSTRUCCION\tPRESUPUESTO: 280000120961\nCANTIDAD UM (UM-SAT) CODIGO COD-SAT DESCRIPCIONES\t% DESCUENTO P. UNITARIOP. DESCUENTO IMPORTE\n3 PZA (H87) MBTQCABCO 30181515 TANQUE JAZMIN CATO BLANCO\t38.90 913.98 558.47 1,675.41\n3 PZA (H87) MBTAACJBCO 30181511 TAZA ALARGADA CATO JAZMIN BLANCO\t37.00 1259.58 793.52 2,380.56\n \nTIPO\nRecoger\nPRECIOS SUJETOS A CAMBIO SIN PREVIO AVISO, PRECIOS EN PAGOS DE\nCONTADO, VIGENCIA DE COTIZACION 5 DIAS VALIDA HASTA 2026-04-22\nAGENTE: ISRAEL JESUS CARDENAS ESPARZA\n SUC: GRAN MONTEJO\n TEL: NA\n    \nSUBTOTAL:\nDESCUENTO:\nIVA:\nTOTAL:\n6,520.68\n2,464.71\n648.96\n4,704.93\n-Para deposito o transferencia referenciada se requiere capturar la referencia \u00fanica en el apartado REFERENCIA DE PAGO o CONCEPTO DE PAGO seg\u00fan la banca\nelectr\u00f3nica de la instituci\u00f3n de su preferencia para que esta pueda proceder de manera exitosa.\nReferencia unica: 28000012096145787231\nBanco \/ Convenio \tClave Interbancaria \nBancomer convenio BBVA CIE\/Contrato: 1697439\tCLABE interbancaria: 012914002016974396\nHSBC Clave de servicio RAP: 2555\tCLABE interbancaria: 021180550300025558\nP\u00e1gina 1\/1"}',NULL,1,0,NULL);
INSERT INTO "quotations" VALUES (25,NULL,NULL,'quotations/rwzJTOZtkyDaSklwgo1ZmnrcZ6uiKd85WMFapwur.pdf','','2026-05-31 01:29:55','2026-05-31 01:29:35','2026-05-31 01:29:55',NULL,'280000120961.pdf','completed','EL NIPLITO DEL SURESTE S.A. DE C.V.    
DIR:CALLE 60 X 89 Y 89-A NO 709 COL CENTRO CP 97000 MERIDA YUCATAN MEXICO RFC: NSU9102113Y9 FECHA: 2026-04-17
CLIENTE: EKILATERO INGENIERIA Y CONSTRUCCION	PRESUPUESTO: 280000120961
CANTIDAD UM (UM-SAT) CODIGO COD-SAT DESCRIPCIONES	% DESCUENTO P. UNITARIOP. DESCUENTO IMPORTE
3 PZA (H87) MBTQCABCO 30181515 TANQUE JAZMIN CATO BLANCO	38.90 913.98 558.47 1,675.41
3 PZA (H87) MBTAACJBCO 30181511 TAZA ALARGADA CATO JAZMIN BLANCO	37.00 1259.58 793.52 2,380.56
 
TIPO
Recoger
PRECIOS SUJETOS A CAMBIO SIN PREVIO AVISO, PRECIOS EN PAGOS DE
CONTADO, VIGENCIA DE COTIZACION 5 DIAS VALIDA HASTA 2026-04-22
AGENTE: ISRAEL JESUS CARDENAS ESPARZA
 SUC: GRAN MONTEJO
 TEL: NA
    
SUBTOTAL:
DESCUENTO:
IVA:
TOTAL:
6,520.68
2,464.71
648.96
4,704.93
-Para deposito o transferencia referenciada se requiere capturar la referencia única en el apartado REFERENCIA DE PAGO o CONCEPTO DE PAGO según la banca
electrónica de la institución de su preferencia para que esta pueda proceder de manera exitosa.
Referencia unica: 28000012096145787231
Banco / Convenio 	Clave Interbancaria 
Bancomer convenio BBVA CIE/Contrato: 1697439	CLABE interbancaria: 012914002016974396
HSBC Clave de servicio RAP: 2555	CLABE interbancaria: 021180550300025558
Página 1/1','{"supplier":"El Niplito Del Sureste","store":"Gran Montejo","seller":"Israel Jesus Cardenas Esparza","tax_info":{"tax_rate":0.16,"prices_include_tax":false,"tax_detected":true,"subtotal":4055.97,"tax_total":648.96,"grand_total":4704.93},"items":[{"name":"TANQUE JAZMIN CATO BLANCO","category":"Plomer\u00eda","quantity":3,"unit":"pza","unit_name":"Pieza","unit_price":913.98,"discount_percent":38.9,"discount_amount":355.51,"unit_price_with_discount":558.47,"tax_amount":null,"price_includes_tax":false,"line_subtotal":1675.41,"line_total":null},{"name":"TAZA ALARGADA CATO JAZMIN BLANCO","category":"Plomer\u00eda","quantity":3,"unit":"pza","unit_name":"Pieza","unit_price":1259.58,"discount_percent":37,"discount_amount":466.06,"unit_price_with_discount":793.52,"tax_amount":null,"price_includes_tax":false,"line_subtotal":2380.56,"line_total":null}],"raw_text":"EL NIPLITO DEL SURESTE S.A. DE C.V.    \nDIR:CALLE 60 X 89 Y 89-A NO 709 COL CENTRO CP 97000 MERIDA YUCATAN MEXICO RFC: NSU9102113Y9 FECHA: 2026-04-17\nCLIENTE: EKILATERO INGENIERIA Y CONSTRUCCION\tPRESUPUESTO: 280000120961\nCANTIDAD UM (UM-SAT) CODIGO COD-SAT DESCRIPCIONES\t% DESCUENTO P. UNITARIOP. DESCUENTO IMPORTE\n3 PZA (H87) MBTQCABCO 30181515 TANQUE JAZMIN CATO BLANCO\t38.90 913.98 558.47 1,675.41\n3 PZA (H87) MBTAACJBCO 30181511 TAZA ALARGADA CATO JAZMIN BLANCO\t37.00 1259.58 793.52 2,380.56\n \nTIPO\nRecoger\nPRECIOS SUJETOS A CAMBIO SIN PREVIO AVISO, PRECIOS EN PAGOS DE\nCONTADO, VIGENCIA DE COTIZACION 5 DIAS VALIDA HASTA 2026-04-22\nAGENTE: ISRAEL JESUS CARDENAS ESPARZA\n SUC: GRAN MONTEJO\n TEL: NA\n    \nSUBTOTAL:\nDESCUENTO:\nIVA:\nTOTAL:\n6,520.68\n2,464.71\n648.96\n4,704.93\n-Para deposito o transferencia referenciada se requiere capturar la referencia \u00fanica en el apartado REFERENCIA DE PAGO o CONCEPTO DE PAGO seg\u00fan la banca\nelectr\u00f3nica de la instituci\u00f3n de su preferencia para que esta pueda proceder de manera exitosa.\nReferencia unica: 28000012096145787231\nBanco \/ Convenio \tClave Interbancaria \nBancomer convenio BBVA CIE\/Contrato: 1697439\tCLABE interbancaria: 012914002016974396\nHSBC Clave de servicio RAP: 2555\tCLABE interbancaria: 021180550300025558\nP\u00e1gina 1\/1"}',NULL,1,0,NULL);
INSERT INTO "quotations" VALUES (26,NULL,NULL,'quotations/16EXqykaHzILgbYSFt5yuf1bfJn6JNhY1q93Z3M3.jpg','','2026-05-31 21:32:32','2026-05-31 21:31:52','2026-05-31 21:32:32',NULL,'WhatsApp Image 2026-04-17 at 4.29.57 PM.jpeg','completed','[Extraído directamente por Gemini Vision]','{"supplier":"Boxito","store":null,"seller":"Leticia Alejandra Dzul Uh","tax_info":{"tax_rate":0.16,"prices_include_tax":false,"tax_detected":true,"subtotal":22400.73,"tax_total":3584.11,"grand_total":25984.84},"items":[{"name":"REMATE VENTILA SANIT 50","category":"Plomer\u00eda","quantity":70,"unit":"pza","unit_name":"Pieza","unit_price":20.28,"discount_percent":null,"discount_amount":null,"unit_price_with_discount":null,"tax_amount":227.14,"price_includes_tax":false,"line_subtotal":1419.6,"line_total":1646.74},{"name":"TUBO SAN NORMA 110","category":"Plomer\u00eda","quantity":42,"unit":"m","unit_name":"Metro lineal","unit_price":39.96,"discount_percent":null,"discount_amount":null,"unit_price_with_discount":null,"tax_amount":268.53,"price_includes_tax":false,"line_subtotal":1678.32,"line_total":1946.85},{"name":"PEGAMENTO PVC (4 OZ) 118 ML TRANSPAREN OATEY","category":"Plomer\u00eda","quantity":9,"unit":"pza","unit_name":"Pieza","unit_price":52.87,"discount_percent":null,"discount_amount":null,"unit_price_with_discount":null,"tax_amount":76.13,"price_includes_tax":false,"line_subtotal":475.83,"line_total":551.96},{"name":"CUELLO DE CERA C\/GUIA EN CAJA","category":"Plomer\u00eda","quantity":18,"unit":"pza","unit_name":"Pieza","unit_price":24.46,"discount_percent":null,"discount_amount":null,"unit_price_with_discount":null,"tax_amount":70.44,"price_includes_tax":false,"line_subtotal":440.28,"line_total":510.72},{"name":"LLAVE D\/CONTROL P\/GAS 1\/2PULGX3\/8PULG","category":"Plomer\u00eda","quantity":60,"unit":"pza","unit_name":"Pieza","unit_price":61.09,"discount_percent":null,"discount_amount":null,"unit_price_with_discount":null,"tax_amount":586.46,"price_includes_tax":false,"line_subtotal":3665.4,"line_total":4251.86},{"name":"ADAPTADOR MACHO GAS 1\/2PULG X 1\/2PULG RM","category":"Plomer\u00eda","quantity":60,"unit":"pza","unit_name":"Pieza","unit_price":40.14,"discount_percent":null,"discount_amount":null,"unit_price_with_discount":null,"tax_amount":385.34,"price_includes_tax":false,"line_subtotal":2408.4,"line_total":2793.74},{"name":"TEE GAS 1\/2PULG X 1\/2PULG X 1\/2PULG","category":"Plomer\u00eda","quantity":30,"unit":"pza","unit_name":"Pieza","unit_price":89.42,"discount_percent":null,"discount_amount":null,"unit_price_with_discount":null,"tax_amount":429.22,"price_includes_tax":false,"line_subtotal":2682.6,"line_total":3111.82},{"name":"CODO 90\u00b0 GAS 1\/2PULG X 1\/2PULG","category":"Plomer\u00eda","quantity":105,"unit":"pza","unit_name":"Pieza","unit_price":62.56,"discount_percent":null,"discount_amount":null,"unit_price_with_discount":null,"tax_amount":1051.01,"price_includes_tax":false,"line_subtotal":6568.8,"line_total":7619.81},{"name":"TUBO PEALPE GAS 1\/2PULG X 100M","category":"Plomer\u00eda","quantity":105,"unit":"m","unit_name":"Metro lineal","unit_price":25.62,"discount_percent":null,"discount_amount":null,"unit_price_with_discount":null,"tax_amount":430.42,"price_includes_tax":false,"line_subtotal":2690.1,"line_total":3120.52},{"name":"ABRAZADERA U\u00d1A GALV PARA CPVC\/COBRE","category":"Ferreter\u00eda","quantity":120,"unit":"pza","unit_name":"Pieza","unit_price":1.45,"discount_percent":null,"discount_amount":null,"unit_price_with_discount":null,"tax_amount":27.84,"price_includes_tax":false,"line_subtotal":174,"line_total":201.84},{"name":"PIJA COMBINADA 10MM GROSOR 1 LARG","category":"Ferreter\u00eda","quantity":420,"unit":"pza","unit_name":"Pieza","unit_price":0.26,"discount_percent":null,"discount_amount":null,"unit_price_with_discount":null,"tax_amount":17.47,"price_includes_tax":false,"line_subtotal":109.2,"line_total":126.67},{"name":"TAQUETE DE PLASTICO (TP14)","category":"Ferreter\u00eda","quantity":420,"unit":"pza","unit_name":"Pieza","unit_price":0.21,"discount_percent":null,"discount_amount":null,"unit_price_with_discount":null,"tax_amount":14.11,"price_includes_tax":false,"line_subtotal":88.2,"line_total":102.31}],"raw_text":"[Extra\u00eddo directamente por Gemini Vision]"}',NULL,1,0,NULL);
INSERT INTO "quotations" VALUES (27,17,NULL,'quotations/sdOYC903YOURTCR2Ut73nR202Bz9xts2DfuGQyJn.jpg','','2026-05-31 21:42:50','2026-05-31 21:42:25','2026-05-31 21:46:57',NULL,'WhatsApp Image 2026-04-17 at 4.29.31 PM.jpeg','completed','[Extraído directamente por Gemini Vision]','{"supplier":"G.E.S. Aceros Y Mas","store":null,"seller":null,"tax_info":{"tax_rate":0.16,"prices_include_tax":false,"tax_detected":true,"subtotal":17942.85,"tax_total":2870.86,"grand_total":20813.71},"items":[{"name":"VARILLA # 3, DE 3\/8\" (12 MTS.)","category":"Acero \/ Herrer\u00eda","quantity":145,"unit":"pza","unit_name":"Pieza","unit_price":121.33,"discount_percent":null,"discount_amount":null,"unit_price_with_discount":null,"tax_amount":null,"price_includes_tax":false,"line_subtotal":17592.85,"line_total":null},{"name":"SERVICIO DE ENTREGA DENTRO DE LA CD. DE MERIDA","category":"Otros","quantity":1,"unit":"serv","unit_name":"Servicio","unit_price":350,"discount_percent":null,"discount_amount":null,"unit_price_with_discount":null,"tax_amount":null,"price_includes_tax":false,"line_subtotal":350,"line_total":null}],"raw_text":"[Extra\u00eddo directamente por Gemini Vision]"}',NULL,1,0,NULL);
INSERT INTO "quotations" VALUES (28,NULL,NULL,'quotations/ZgNGQHiPfKqEX16eXM0XXEtUgBjOo2WDoE4nv4Pm.jpg','','2026-06-01 04:40:28','2026-06-01 04:39:55','2026-06-01 04:40:28',NULL,'WhatsApp Image 2026-04-17 at 4.29.57 PM.jpeg','completed','[Extraído directamente por Gemini Vision]','{"supplier":"Boxito","store":null,"seller":"Leticia Alejandra Dzul Uh","tax_info":{"tax_rate":0.16,"prices_include_tax":false,"tax_detected":true,"subtotal":22400.73,"tax_total":3584.11,"grand_total":25984.84},"items":[{"name":"REMATE VENTILA SANIT 50","category":"Plomer\u00eda","quantity":70,"unit":"pza","unit_name":"Pieza","unit_price":20.28,"discount_percent":null,"discount_amount":null,"unit_price_with_discount":null,"tax_amount":227.14,"price_includes_tax":false,"line_subtotal":1419.6,"line_total":1646.74},{"name":"TUBO SAN NORMA 110","category":"Material Hidr\u00e1ulico","quantity":42,"unit":"m","unit_name":"Metro lineal","unit_price":39.96,"discount_percent":null,"discount_amount":null,"unit_price_with_discount":null,"tax_amount":268.53,"price_includes_tax":false,"line_subtotal":1678.32,"line_total":1946.85},{"name":"PEGAMENTO PVC (4 OZ) 118 ML TRANSPAREN OATEY","category":"Plomer\u00eda","quantity":9,"unit":"pza","unit_name":"Pieza","unit_price":52.87,"discount_percent":null,"discount_amount":null,"unit_price_with_discount":null,"tax_amount":76.13,"price_includes_tax":false,"line_subtotal":475.83,"line_total":551.96},{"name":"CUELLO DE CERA C\/GUIA EN CAJA PB-104","category":"Plomer\u00eda","quantity":18,"unit":"pza","unit_name":"Pieza","unit_price":24.46,"discount_percent":null,"discount_amount":null,"unit_price_with_discount":null,"tax_amount":70.44,"price_includes_tax":false,"line_subtotal":440.28,"line_total":510.72},{"name":"LLAVE D\/CONTROL P\/GAS 1\/2PULGX3\/8PULG","category":"Plomer\u00eda","quantity":60,"unit":"pza","unit_name":"Pieza","unit_price":61.09,"discount_percent":null,"discount_amount":null,"unit_price_with_discount":null,"tax_amount":586.46,"price_includes_tax":false,"line_subtotal":3665.4,"line_total":4251.86},{"name":"ADAPTADOR MACHO GAS 1\/2PULG X 1\/2PULG RM","category":"Plomer\u00eda","quantity":60,"unit":"pza","unit_name":"Pieza","unit_price":40.14,"discount_percent":null,"discount_amount":null,"unit_price_with_discount":null,"tax_amount":385.34,"price_includes_tax":false,"line_subtotal":2408.4,"line_total":2793.74},{"name":"TEE GAS 1\/2PULG X 1\/2PULG X 1\/2PULG","category":"Plomer\u00eda","quantity":30,"unit":"pza","unit_name":"Pieza","unit_price":89.42,"discount_percent":null,"discount_amount":null,"unit_price_with_discount":null,"tax_amount":429.22,"price_includes_tax":false,"line_subtotal":2682.6,"line_total":3111.82},{"name":"CODO 90\u00b0 GAS 1\/2PULG X 1\/2PULG","category":"Plomer\u00eda","quantity":105,"unit":"pza","unit_name":"Pieza","unit_price":62.56,"discount_percent":null,"discount_amount":null,"unit_price_with_discount":null,"tax_amount":1051.01,"price_includes_tax":false,"line_subtotal":6568.8,"line_total":7619.81},{"name":"TUBO PEALPE GAS 1\/2PULG X 100M","category":"Material Hidr\u00e1ulico","quantity":105,"unit":"m","unit_name":"Metro lineal","unit_price":25.62,"discount_percent":null,"discount_amount":null,"unit_price_with_discount":null,"tax_amount":430.42,"price_includes_tax":false,"line_subtotal":2690.1,"line_total":3120.52},{"name":"ABRAZADERA U\u00d1A GALV 200 013 UCR12 PARA CPVC\/COBRE","category":"Ferreter\u00eda","quantity":120,"unit":"pza","unit_name":"Pieza","unit_price":1.45,"discount_percent":null,"discount_amount":null,"unit_price_with_discount":null,"tax_amount":27.84,"price_includes_tax":false,"line_subtotal":174,"line_total":201.84},{"name":"PIJA COMBINADA 10MM GROSOR 1 LARG","category":"Ferreter\u00eda","quantity":420,"unit":"pza","unit_name":"Pieza","unit_price":0.26,"discount_percent":null,"discount_amount":null,"unit_price_with_discount":null,"tax_amount":17.47,"price_includes_tax":false,"line_subtotal":109.2,"line_total":126.67},{"name":"TAQUETE DE PLASTICO 006 (TP14)","category":"Ferreter\u00eda","quantity":420,"unit":"pza","unit_name":"Pieza","unit_price":0.21,"discount_percent":null,"discount_amount":null,"unit_price_with_discount":null,"tax_amount":14.11,"price_includes_tax":false,"line_subtotal":88.2,"line_total":102.31}],"raw_text":"[Extra\u00eddo directamente por Gemini Vision]"}',NULL,1,0,NULL);
INSERT INTO "quotations" VALUES (29,NULL,NULL,'quotations/0cH1klGBSYblwazPEgEyaugIiumGR38oWcLff4a8.pdf','','2026-06-01 05:16:06','2026-06-01 05:16:02','2026-06-01 05:16:06',NULL,'280000120961.pdf','completed','EL NIPLITO DEL SURESTE S.A. DE C.V.    
DIR:CALLE 60 X 89 Y 89-A NO 709 COL CENTRO CP 97000 MERIDA YUCATAN MEXICO RFC: NSU9102113Y9 FECHA: 2026-04-17
CLIENTE: EKILATERO INGENIERIA Y CONSTRUCCION	PRESUPUESTO: 280000120961
CANTIDAD UM (UM-SAT) CODIGO COD-SAT DESCRIPCIONES	% DESCUENTO P. UNITARIOP. DESCUENTO IMPORTE
3 PZA (H87) MBTQCABCO 30181515 TANQUE JAZMIN CATO BLANCO	38.90 913.98 558.47 1,675.41
3 PZA (H87) MBTAACJBCO 30181511 TAZA ALARGADA CATO JAZMIN BLANCO	37.00 1259.58 793.52 2,380.56
 
TIPO
Recoger
PRECIOS SUJETOS A CAMBIO SIN PREVIO AVISO, PRECIOS EN PAGOS DE
CONTADO, VIGENCIA DE COTIZACION 5 DIAS VALIDA HASTA 2026-04-22
AGENTE: ISRAEL JESUS CARDENAS ESPARZA
 SUC: GRAN MONTEJO
 TEL: NA
    
SUBTOTAL:
DESCUENTO:
IVA:
TOTAL:
6,520.68
2,464.71
648.96
4,704.93
-Para deposito o transferencia referenciada se requiere capturar la referencia única en el apartado REFERENCIA DE PAGO o CONCEPTO DE PAGO según la banca
electrónica de la institución de su preferencia para que esta pueda proceder de manera exitosa.
Referencia unica: 28000012096145787231
Banco / Convenio 	Clave Interbancaria 
Bancomer convenio BBVA CIE/Contrato: 1697439	CLABE interbancaria: 012914002016974396
HSBC Clave de servicio RAP: 2555	CLABE interbancaria: 021180550300025558
Página 1/1','{"supplier":"EL NIPLITO DEL SURESTE S.A. DE C.V.","store":null,"seller":null,"tax_info":null,"items":[],"raw_text":"EL NIPLITO DEL SURESTE S.A. DE C.V.    \nDIR:CALLE 60 X 89 Y 89-A NO 709 COL CENTRO CP 97000 MERIDA YUCATAN MEXICO RFC: NSU9102113Y9 FECHA: 2026-04-17\nCLIENTE: EKILATERO INGENIERIA Y CONSTRUCCION\tPRESUPUESTO: 280000120961\nCANTIDAD UM (UM-SAT) CODIGO COD-SAT DESCRIPCIONES\t% DESCUENTO P. UNITARIOP. DESCUENTO IMPORTE\n3 PZA (H87) MBTQCABCO 30181515 TANQUE JAZMIN CATO BLANCO\t38.90 913.98 558.47 1,675.41\n3 PZA (H87) MBTAACJBCO 30181511 TAZA ALARGADA CATO JAZMIN BLANCO\t37.00 1259.58 793.52 2,380.56\n \nTIPO\nRecoger\nPRECIOS SUJETOS A CAMBIO SIN PREVIO AVISO, PRECIOS EN PAGOS DE\nCONTADO, VIGENCIA DE COTIZACION 5 DIAS VALIDA HASTA 2026-04-22\nAGENTE: ISRAEL JESUS CARDENAS ESPARZA\n SUC: GRAN MONTEJO\n TEL: NA\n    \nSUBTOTAL:\nDESCUENTO:\nIVA:\nTOTAL:\n6,520.68\n2,464.71\n648.96\n4,704.93\n-Para deposito o transferencia referenciada se requiere capturar la referencia \u00fanica en el apartado REFERENCIA DE PAGO o CONCEPTO DE PAGO seg\u00fan la banca\nelectr\u00f3nica de la instituci\u00f3n de su preferencia para que esta pueda proceder de manera exitosa.\nReferencia unica: 28000012096145787231\nBanco \/ Convenio \tClave Interbancaria \nBancomer convenio BBVA CIE\/Contrato: 1697439\tCLABE interbancaria: 012914002016974396\nHSBC Clave de servicio RAP: 2555\tCLABE interbancaria: 021180550300025558\nP\u00e1gina 1\/1"}',NULL,1,0,NULL);
INSERT INTO "quotations" VALUES (30,NULL,NULL,'quotations/SFwjdY5KlBw7zRTyX2LmNSMAQei0lVyserk2qiUK.pdf','','2026-06-01 05:18:25','2026-06-01 05:16:34','2026-06-01 05:18:25',NULL,'280000120961.pdf','completed','EL NIPLITO DEL SURESTE S.A. DE C.V.    
DIR:CALLE 60 X 89 Y 89-A NO 709 COL CENTRO CP 97000 MERIDA YUCATAN MEXICO RFC: NSU9102113Y9 FECHA: 2026-04-17
CLIENTE: EKILATERO INGENIERIA Y CONSTRUCCION	PRESUPUESTO: 280000120961
CANTIDAD UM (UM-SAT) CODIGO COD-SAT DESCRIPCIONES	% DESCUENTO P. UNITARIOP. DESCUENTO IMPORTE
3 PZA (H87) MBTQCABCO 30181515 TANQUE JAZMIN CATO BLANCO	38.90 913.98 558.47 1,675.41
3 PZA (H87) MBTAACJBCO 30181511 TAZA ALARGADA CATO JAZMIN BLANCO	37.00 1259.58 793.52 2,380.56
 
TIPO
Recoger
PRECIOS SUJETOS A CAMBIO SIN PREVIO AVISO, PRECIOS EN PAGOS DE
CONTADO, VIGENCIA DE COTIZACION 5 DIAS VALIDA HASTA 2026-04-22
AGENTE: ISRAEL JESUS CARDENAS ESPARZA
 SUC: GRAN MONTEJO
 TEL: NA
    
SUBTOTAL:
DESCUENTO:
IVA:
TOTAL:
6,520.68
2,464.71
648.96
4,704.93
-Para deposito o transferencia referenciada se requiere capturar la referencia única en el apartado REFERENCIA DE PAGO o CONCEPTO DE PAGO según la banca
electrónica de la institución de su preferencia para que esta pueda proceder de manera exitosa.
Referencia unica: 28000012096145787231
Banco / Convenio 	Clave Interbancaria 
Bancomer convenio BBVA CIE/Contrato: 1697439	CLABE interbancaria: 012914002016974396
HSBC Clave de servicio RAP: 2555	CLABE interbancaria: 021180550300025558
Página 1/1','{"supplier":"EL NIPLITO DEL SURESTE S.A. DE C.V.","store":null,"seller":null,"tax_info":null,"items":[],"raw_text":"EL NIPLITO DEL SURESTE S.A. DE C.V.    \nDIR:CALLE 60 X 89 Y 89-A NO 709 COL CENTRO CP 97000 MERIDA YUCATAN MEXICO RFC: NSU9102113Y9 FECHA: 2026-04-17\nCLIENTE: EKILATERO INGENIERIA Y CONSTRUCCION\tPRESUPUESTO: 280000120961\nCANTIDAD UM (UM-SAT) CODIGO COD-SAT DESCRIPCIONES\t% DESCUENTO P. UNITARIOP. DESCUENTO IMPORTE\n3 PZA (H87) MBTQCABCO 30181515 TANQUE JAZMIN CATO BLANCO\t38.90 913.98 558.47 1,675.41\n3 PZA (H87) MBTAACJBCO 30181511 TAZA ALARGADA CATO JAZMIN BLANCO\t37.00 1259.58 793.52 2,380.56\n \nTIPO\nRecoger\nPRECIOS SUJETOS A CAMBIO SIN PREVIO AVISO, PRECIOS EN PAGOS DE\nCONTADO, VIGENCIA DE COTIZACION 5 DIAS VALIDA HASTA 2026-04-22\nAGENTE: ISRAEL JESUS CARDENAS ESPARZA\n SUC: GRAN MONTEJO\n TEL: NA\n    \nSUBTOTAL:\nDESCUENTO:\nIVA:\nTOTAL:\n6,520.68\n2,464.71\n648.96\n4,704.93\n-Para deposito o transferencia referenciada se requiere capturar la referencia \u00fanica en el apartado REFERENCIA DE PAGO o CONCEPTO DE PAGO seg\u00fan la banca\nelectr\u00f3nica de la instituci\u00f3n de su preferencia para que esta pueda proceder de manera exitosa.\nReferencia unica: 28000012096145787231\nBanco \/ Convenio \tClave Interbancaria \nBancomer convenio BBVA CIE\/Contrato: 1697439\tCLABE interbancaria: 012914002016974396\nHSBC Clave de servicio RAP: 2555\tCLABE interbancaria: 021180550300025558\nP\u00e1gina 1\/1"}',NULL,1,0,NULL);
INSERT INTO "quotations" VALUES (31,NULL,NULL,'quotations/IlspkbqHZ0oQsUBE5C7UqHfuEMkhNvqgHdEnemiX.jpg','','2026-06-01 05:19:39','2026-06-01 05:18:19','2026-06-01 05:19:39',NULL,'WhatsApp Image 2026-04-17 at 3.56.44 PM.jpeg','completed','[No se pudo extraer texto del archivo]','{"supplier":null,"store":null,"seller":null,"tax_info":null,"items":[],"raw_text":"[No se pudo extraer texto del archivo]"}',NULL,1,0,NULL);
INSERT INTO "quotations" VALUES (32,NULL,NULL,'quotations/CUonUDl1IYJUOSjc75VeYfC038OyPqvrV52tNKE5.jpg','','2026-06-01 06:15:53','2026-06-01 06:15:42','2026-06-01 06:15:53',NULL,'WhatsApp Image 2026-04-17 at 4.29.57 PM.jpeg','completed','[No se pudo extraer texto del archivo]','{"supplier":null,"store":null,"seller":null,"tax_info":null,"items":[],"raw_text":"[No se pudo extraer texto del archivo]"}',NULL,1,0,NULL);
INSERT INTO "quotations" VALUES (33,NULL,NULL,'quotations/JdQZKVVSkfhwZl7gBuapunxotzEd0uyR2EQdkMol.jpg','','2026-06-01 06:18:10','2026-06-01 06:18:05','2026-06-01 06:18:10',NULL,'WhatsApp Image 2026-04-17 at 4.29.57 PM.jpeg','completed','[No se pudo extraer texto del archivo]','{"supplier":null,"store":null,"seller":null,"tax_info":null,"items":[],"raw_text":"[No se pudo extraer texto del archivo]"}',NULL,1,0,NULL);
INSERT INTO "quotations" VALUES (34,NULL,NULL,'quotations/D1Q5y6plPhUYzhGHIZiRGSUEOkePvP4fC82Vbta8.jpg','','2026-06-01 06:24:05','2026-06-01 06:23:47','2026-06-01 06:24:05',NULL,'WhatsApp Image 2026-04-17 at 4.29.57 PM.jpeg','completed','[No se pudo extraer texto del archivo]','{"supplier":null,"store":null,"seller":null,"tax_info":null,"items":[],"raw_text":"[No se pudo extraer texto del archivo]"}',NULL,1,0,NULL);
INSERT INTO "quotations" VALUES (35,NULL,NULL,'quotations/2H5nrjmFKWrQ0utOoybI3IvHpZMbGN0drmHZjyDw.jpg','','2026-06-01 06:26:40','2026-06-01 06:26:28','2026-06-01 06:26:40',NULL,'WhatsApp Image 2026-04-17 at 4.29.57 PM.jpeg','completed','[No se pudo extraer texto del archivo]','{"supplier":null,"store":null,"seller":null,"tax_info":null,"items":[],"raw_text":"[No se pudo extraer texto del archivo]"}',NULL,1,0,NULL);
INSERT INTO "quotations" VALUES (36,NULL,NULL,'quotations/brG5GuiNH52t6AWnNp3k65j96Ugd0EjDjZJTi4sW.jpg','','2026-06-01 06:27:34','2026-06-01 06:27:07','2026-06-01 06:27:34',NULL,'WhatsApp Image 2026-04-17 at 4.29.57 PM.jpeg','completed','[No se pudo extraer texto del archivo]','{"supplier":null,"store":null,"seller":null,"tax_info":null,"items":[],"raw_text":"[No se pudo extraer texto del archivo]"}',NULL,1,0,NULL);
INSERT INTO "quotations" VALUES (37,NULL,NULL,'quotations/v2ppXHGpecp8qhkg4g7IAySsszpJnFtq1VCqRdPt.jpg','','2026-06-01 06:34:28','2026-06-01 06:33:46','2026-06-01 06:34:28',NULL,'WhatsApp Image 2026-04-17 at 4.29.57 PM.jpeg','completed','[No se pudo extraer texto del archivo]','{"supplier":null,"store":null,"seller":null,"tax_info":null,"items":[],"raw_text":"[No se pudo extraer texto del archivo]"}',NULL,1,0,NULL);
INSERT INTO "quotations" VALUES (38,NULL,NULL,'quotations/oMj8ec7AHBjgL7lUGgqpp68lt6QnoUUFXAA88gbu.png','','2026-06-01 06:42:11','2026-06-01 06:39:46','2026-06-01 06:42:11',NULL,'Image_20260318_0001.png','completed','[Extraído directamente por Gemini Vision]','{"supplier":"Grupo Alcione Sa De Cv","store":"Merida","seller":"Reyna Cecilia Reyes Lopez","tax_info":{"tax_rate":0.16,"prices_include_tax":false,"tax_detected":true,"subtotal":78981.08,"tax_total":12636.98,"grand_total":91618.06},"items":[{"name":"ITM 120VCA 10 KACI-120VCA BT 1P 20A E","category":"Material El\u00e9ctrico","quantity":48,"unit":"pza","unit_name":"Pieza","unit_price":71.94,"discount_percent":null,"discount_amount":null,"unit_price_with_discount":null,"tax_amount":null,"price_includes_tax":false,"line_subtotal":3453.12,"line_total":null},{"name":"ITM 120VCA 10 KACI-120VCA BT 1P 15A E","category":"Material El\u00e9ctrico","quantity":48,"unit":"pza","unit_name":"Pieza","unit_price":71.94,"discount_percent":null,"discount_amount":null,"unit_price_with_discount":null,"tax_amount":null,"price_includes_tax":false,"line_subtotal":3453.12,"line_total":null},{"name":"PORTALAMPARA SOB TECHO\/MURO P\/FOCO DISENIO ITALIANO BCO","category":"Material El\u00e9ctrico","quantity":312,"unit":"pza","unit_name":"Pieza","unit_price":13.63,"discount_percent":null,"discount_amount":null,"unit_price_with_discount":null,"tax_amount":null,"price_includes_tax":false,"line_subtotal":4252.56,"line_total":null},{"name":"APAGADOR UNIPOLAR 1MOD 16AX 127-277V BLANCO","category":"Material El\u00e9ctrico","quantity":269,"unit":"pza","unit_name":"Pieza","unit_price":20.39,"discount_percent":null,"discount_amount":null,"unit_price_with_discount":null,"tax_amount":null,"price_includes_tax":false,"line_subtotal":5484.91,"line_total":null},{"name":"APAGADOR 1MOD 3VIAS 16AX 127-277V BLANCO","category":"Material El\u00e9ctrico","quantity":51,"unit":"pza","unit_name":"Pieza","unit_price":26.64,"discount_percent":null,"discount_amount":null,"unit_price_with_discount":null,"tax_amount":null,"price_includes_tax":false,"line_subtotal":1358.64,"line_total":null},{"name":"PULSADOR 1MOD 10A 127-277V MODUS PRO","category":"Material El\u00e9ctrico","quantity":27,"unit":"pza","unit_name":"Pieza","unit_price":23.55,"discount_percent":null,"discount_amount":null,"unit_price_with_discount":null,"tax_amount":null,"price_includes_tax":false,"line_subtotal":635.85,"line_total":null},{"name":"TOMACORRIENTE DUPLEX 2P+T 15A 127-250V BLANCO S\/P INF","category":"Material El\u00e9ctrico","quantity":360,"unit":"pza","unit_name":"Pieza","unit_price":39.93,"discount_percent":null,"discount_amount":null,"unit_price_with_discount":null,"tax_amount":null,"price_includes_tax":false,"line_subtotal":14374.8,"line_total":null},{"name":"CONTACTO DUPLEX ICFT 2P+T 15A 127-250V BLANCO","category":"Material El\u00e9ctrico","quantity":104,"unit":"pza","unit_name":"Pieza","unit_price":259.72,"discount_percent":null,"discount_amount":null,"unit_price_with_discount":null,"tax_amount":null,"price_includes_tax":false,"line_subtotal":27010.88,"line_total":null},{"name":"TOMA TELEFONICA 1MOD 4H BLANCO","category":"Material El\u00e9ctrico","quantity":51,"unit":"pza","unit_name":"Pieza","unit_price":21.68,"discount_percent":null,"discount_amount":null,"unit_price_with_discount":null,"tax_amount":null,"price_includes_tax":false,"line_subtotal":1105.68,"line_total":null},{"name":"TOMA TV 1 MOD BLANCO","category":"Material El\u00e9ctrico","quantity":99,"unit":"pza","unit_name":"Pieza","unit_price":29.84,"discount_percent":null,"discount_amount":null,"unit_price_with_discount":null,"tax_amount":null,"price_includes_tax":false,"line_subtotal":2954.16,"line_total":null},{"name":"ZUMBADOR 1MOD 127V BLANCO","category":"Material El\u00e9ctrico","quantity":27,"unit":"pza","unit_name":"Pieza","unit_price":56.32,"discount_percent":null,"discount_amount":null,"unit_price_with_discount":null,"tax_amount":null,"price_includes_tax":false,"line_subtotal":1520.64,"line_total":null},{"name":"PLACA 1MOD BLANCO C\/CHASIS RESINA ABS","category":"Material El\u00e9ctrico","quantity":339,"unit":"pza","unit_name":"Pieza","unit_price":20.56,"discount_percent":null,"discount_amount":null,"unit_price_with_discount":null,"tax_amount":null,"price_includes_tax":false,"line_subtotal":6969.84,"line_total":null},{"name":"PLACA 2MOD BLANCO C\/CHASIS RESINA ABS","category":"Material El\u00e9ctrico","quantity":98,"unit":"pza","unit_name":"Pieza","unit_price":20.56,"discount_percent":null,"discount_amount":null,"unit_price_with_discount":null,"tax_amount":null,"price_includes_tax":false,"line_subtotal":2014.88,"line_total":null},{"name":"CONTACTO DUPLEX POLARIZADO+T 15A BLANCO","category":"Material El\u00e9ctrico","quantity":48,"unit":"pza","unit_name":"Pieza","unit_price":15.88,"discount_percent":null,"discount_amount":null,"unit_price_with_discount":null,"tax_amount":null,"price_includes_tax":false,"line_subtotal":762.24,"line_total":null},{"name":"PLACA DUPLEX INTEMPERIE C\/TAPAS PLASTICO","category":"Material El\u00e9ctrico","quantity":48,"unit":"pza","unit_name":"Pieza","unit_price":75.62,"discount_percent":null,"discount_amount":null,"unit_price_with_discount":null,"tax_amount":null,"price_includes_tax":false,"line_subtotal":3629.76,"line_total":null}],"raw_text":"[Extra\u00eddo directamente por Gemini Vision]"}',NULL,1,0,NULL);
INSERT INTO "quotations" VALUES (39,NULL,NULL,'quotations/j7zmfX4DQAUB13snYOyKZ4AUyp4YEGaKDlE8HMC4.jpg','',NULL,'2026-06-01 06:41:41','2026-06-01 06:42:36',NULL,'WhatsApp Image 2026-04-17 at 4.29.57 PM.jpeg','failed',NULL,NULL,'La IA de Gemini está experimentando alta demanda o no pudo procesar el archivo en este momento. Por favor, intenta de nuevo o continúa manualmente.',1,0,NULL);
INSERT INTO "quotations" VALUES (40,NULL,NULL,'quotations/VGyI9Uf1AQLWmtX0etPZilMmB8eDqNZIermY7jMt.jpg','','2026-06-01 06:46:18','2026-06-01 06:46:03','2026-06-01 06:46:18',NULL,'WhatsApp Image 2026-04-17 at 4.29.31 PM.jpeg','completed','[Extraído directamente por Gemini Vision]','{"supplier":"Ges Aceros Y Mas","store":null,"seller":null,"tax_info":{"tax_rate":0.16,"prices_include_tax":false,"tax_detected":true,"subtotal":17942.85,"tax_total":2870.86,"grand_total":20813.71},"items":[{"name":"VARILLA # 3, DE 3\/8\" (12 MTS.)","category":"Acero \/ Herrer\u00eda","quantity":145,"unit":"pza","unit_name":"Pieza","unit_price":121.33,"discount_percent":null,"discount_amount":null,"unit_price_with_discount":null,"tax_amount":null,"price_includes_tax":false,"line_subtotal":17592.85,"line_total":null},{"name":"SERVICIO DE ENTREGA DENTRO DE LA CD. DE MERIDA","category":"Otros","quantity":1,"unit":"serv","unit_name":"Servicio","unit_price":350,"discount_percent":null,"discount_amount":null,"unit_price_with_discount":null,"tax_amount":null,"price_includes_tax":false,"line_subtotal":350,"line_total":null}],"raw_text":"[Extra\u00eddo directamente por Gemini Vision]"}',NULL,1,0,NULL);
INSERT INTO "quotations" VALUES (41,NULL,NULL,'quotations/7KiN0od4HCUoe8zQQTB8VvcPHYP1C2hx4AAXw0Am.jpg','',NULL,'2026-06-01 18:08:51','2026-06-01 18:11:55',NULL,'WhatsApp Image 2026-06-01 at 12.04.43 PM (1).jpeg','failed',NULL,NULL,'La IA de Gemini está experimentando alta demanda o no pudo procesar el archivo en este momento. Por favor, intenta de nuevo o continúa manualmente.',1,0,NULL);
INSERT INTO "quotations" VALUES (42,NULL,NULL,'quotations/gTTKcArX9vphK7A9fQA0PPktbxVw1KOnTq1QeyKq.jpg','',NULL,'2026-06-01 18:15:40','2026-06-01 18:15:46',NULL,'WhatsApp Image 2026-06-01 at 12.04.43 PM (1).jpeg','failed',NULL,NULL,'La IA de Gemini está experimentando alta demanda o no pudo procesar el archivo en este momento. Por favor, intenta de nuevo o continúa manualmente.',1,0,NULL);
INSERT INTO "quotations" VALUES (43,NULL,NULL,'quotations/cyW5kmZrijc5km5VuoH71K1UEZAZD1MPlLTK5rp4.jpg','',NULL,'2026-06-01 18:16:52','2026-06-01 18:18:24',NULL,'WhatsApp Image 2026-06-01 at 12.04.43 PM (1).jpeg','failed',NULL,NULL,'La IA de Gemini está experimentando alta demanda o no pudo procesar el archivo en este momento. Por favor, intenta de nuevo o continúa manualmente.',1,0,NULL);
INSERT INTO "quotations" VALUES (44,NULL,NULL,'quotations/aw5NX7c9fuYdsPi7brZ6WrIYhQMMA9SlwXwojmgS.jpg','',NULL,'2026-06-01 18:17:27','2026-06-01 18:17:43',NULL,'WhatsApp Image 2026-06-01 at 12.04.43 PM (1).jpeg','failed',NULL,NULL,'La IA de Gemini está experimentando alta demanda o no pudo procesar el archivo en este momento. Por favor, intenta de nuevo o continúa manualmente.',1,0,NULL);
INSERT INTO "quotations" VALUES (45,NULL,NULL,'quotations/DL3HAGlo1lf3ewHntDTWJsVQSrs0gz5Eu4jnXLjb.jpg','',NULL,'2026-06-01 18:22:55','2026-06-01 18:24:25',NULL,'WhatsApp Image 2026-06-01 at 12.04.43 PM (1).jpeg','failed',NULL,NULL,'La IA de Gemini está experimentando alta demanda o no pudo procesar el archivo en este momento. Por favor, intenta de nuevo o continúa manualmente.',1,0,NULL);
INSERT INTO "quotations" VALUES (46,NULL,NULL,'quotations/6MWRqpZTrN1tlNGtDOi97hjnCwVqJ8UA4Nskplr8.jpg','',NULL,'2026-06-01 18:41:05','2026-06-01 18:41:37',NULL,'WhatsApp Image 2026-06-01 at 12.04.43 PM (1).jpeg','failed',NULL,NULL,'La IA de Gemini está experimentando alta demanda o no pudo procesar el archivo en este momento. Por favor, intenta de nuevo o continúa manualmente.',1,0,NULL);
INSERT INTO "quotations" VALUES (47,NULL,NULL,'quotations/Fbt6PS15sR5h9XOpAsm3YkpahXREDEEOtEJC1hNU.jpg','','2026-06-03 23:46:02','2026-06-03 23:45:42','2026-06-03 23:46:02',NULL,'WhatsApp Image 2026-06-01 at 12.04.43 PM (1).jpeg','completed','[Extraído directamente por Gemini Vision]','{"supplier":"El Niplito Del Sureste","store":null,"seller":null,"tax_info":{"tax_rate":0.16,"prices_include_tax":false,"tax_detected":true,"subtotal":2030,"tax_total":324.8,"grand_total":2354.8},"items":[{"name":"PEGAZULEJO PISOS Y MARMOL GRIS 20KG CEMIX","category":"Cemento \/ Concreto","quantity":28,"unit":"pza","unit_name":"Pieza","unit_price":72.5,"discount_percent":null,"discount_amount":null,"unit_price_with_discount":null,"tax_amount":324.8,"price_includes_tax":false,"line_subtotal":2030,"line_total":2354.8}],"raw_text":"[Extra\u00eddo directamente por Gemini Vision]"}',NULL,1,0,NULL);
INSERT INTO "quotations" VALUES (48,20,NULL,'quotations/OnvukgUmJTeh8nKt7QgvtuWFFcE6LstL8HICb2g1.jpg','','2026-06-04 03:59:07','2026-06-04 03:58:49','2026-06-04 04:00:26',NULL,'WhatsApp Image 2026-06-01 at 12.04.43 PM (1).jpeg','completed','[Extraído directamente por Gemini Vision]','{"supplier":"El Niplito Del Sureste","store":null,"seller":null,"tax_info":{"tax_rate":0.16,"prices_include_tax":false,"tax_detected":true,"subtotal":2030,"tax_total":324.8,"grand_total":2354.8},"items":[{"name":"PEGAZULEJO PISOS Y MARMOL GRIS 20KG CEMIX","category":"Cemento \/ Concreto","quantity":28,"unit":"pza","unit_name":"Pieza","unit_price":72.5,"discount_percent":null,"discount_amount":null,"unit_price_with_discount":null,"tax_amount":null,"price_includes_tax":false,"line_subtotal":2030,"line_total":null}],"raw_text":"[Extra\u00eddo directamente por Gemini Vision]"}',NULL,1,0,NULL);
INSERT INTO "quotations" VALUES (49,21,NULL,'quotations/iD6n2hjzQykGxXT4tW1WkN87n9ecH79x6qNWhBOJ.jpg','','2026-06-04 10:21:34','2026-06-04 10:21:15','2026-06-04 10:22:00',NULL,'WhatsApp Image 2026-06-01 at 12.04.43 PM (1).jpeg','completed','[Extraído directamente por Gemini Vision]','{"supplier":"El Niplito Del Sureste","store":null,"seller":null,"tax_info":{"tax_rate":0.16,"prices_include_tax":false,"tax_detected":true,"subtotal":2030,"tax_total":324.8,"grand_total":2354.8},"items":[{"name":"PEGAZULEJO PISOS Y MARMOL GRIS 20KG CEMIX","category":"Cemento \/ Concreto","quantity":28,"unit":"pza","unit_name":"Pieza","unit_price":72.5,"discount_percent":null,"discount_amount":null,"unit_price_with_discount":null,"tax_amount":null,"price_includes_tax":false,"line_subtotal":2030,"line_total":null}],"raw_text":"[Extra\u00eddo directamente por Gemini Vision]"}',NULL,1,0,NULL);
INSERT INTO "quotations" VALUES (50,NULL,1,'quotations/tXu5tlk0UicUJdn0X7CgNAO0nygaapTj0BrYfdeh.jpg','','2026-06-06 04:03:56','2026-06-06 04:03:23','2026-06-06 04:06:18',NULL,'WhatsApp Image 2026-04-17 at 4.29.57 PM.jpeg','completed','[Extraído directamente por Gemini Vision]','{"supplier":"Boxito","store":null,"seller":"Leticia Alejandra Dzul Uh","tax_info":{"tax_rate":0.16,"prices_include_tax":false,"tax_detected":true,"subtotal":22400.73,"tax_total":3584.11,"grand_total":25984.84},"items":[{"name":"REMATE VENTILA SANIT 50","category":"Plomer\u00eda","quantity":70,"unit":"pza","unit_name":"Pieza","unit_price":20.28,"discount_percent":null,"discount_amount":null,"unit_price_with_discount":null,"tax_amount":227.14,"price_includes_tax":false,"line_subtotal":1419.6,"line_total":1646.74},{"name":"TUBO SAN NORMA 110","category":"Plomer\u00eda","quantity":42,"unit":"m","unit_name":"Metro lineal","unit_price":39.96,"discount_percent":null,"discount_amount":null,"unit_price_with_discount":null,"tax_amount":268.53,"price_includes_tax":false,"line_subtotal":1678.32,"line_total":1946.85},{"name":"PEGAMENTO PVC (4 OZ) 118 ML TRANSPAREN OATEY","category":"Plomer\u00eda","quantity":9,"unit":"pza","unit_name":"Pieza","unit_price":52.87,"discount_percent":null,"discount_amount":null,"unit_price_with_discount":null,"tax_amount":76.13,"price_includes_tax":false,"line_subtotal":475.83,"line_total":551.96},{"name":"CUELLO DE CERA C\/GUIA EN CAJA PB-104","category":"Plomer\u00eda","quantity":18,"unit":"pza","unit_name":"Pieza","unit_price":24.46,"discount_percent":null,"discount_amount":null,"unit_price_with_discount":null,"tax_amount":70.44,"price_includes_tax":false,"line_subtotal":440.28,"line_total":510.72},{"name":"LLAVE D\/CONTROL P\/GAS 1\/2PULGX3\/8PULG","category":"Plomer\u00eda","quantity":60,"unit":"pza","unit_name":"Pieza","unit_price":61.09,"discount_percent":null,"discount_amount":null,"unit_price_with_discount":null,"tax_amount":586.46,"price_includes_tax":false,"line_subtotal":3665.4,"line_total":4251.86},{"name":"ADAPTADOR MACHO GAS 1\/2PULG X 1\/2PULG RM","category":"Plomer\u00eda","quantity":60,"unit":"pza","unit_name":"Pieza","unit_price":40.14,"discount_percent":null,"discount_amount":null,"unit_price_with_discount":null,"tax_amount":385.34,"price_includes_tax":false,"line_subtotal":2408.4,"line_total":2793.74},{"name":"TEE GAS 1\/2PULG X 1\/2PULG X 1\/2PULG","category":"Plomer\u00eda","quantity":30,"unit":"pza","unit_name":"Pieza","unit_price":89.42,"discount_percent":null,"discount_amount":null,"unit_price_with_discount":null,"tax_amount":429.22,"price_includes_tax":false,"line_subtotal":2682.6,"line_total":3111.82},{"name":"CODO 90\u00b0 GAS 1\/2PULG X 1\/2PULG","category":"Plomer\u00eda","quantity":105,"unit":"pza","unit_name":"Pieza","unit_price":62.56,"discount_percent":null,"discount_amount":null,"unit_price_with_discount":null,"tax_amount":1051.01,"price_includes_tax":false,"line_subtotal":6568.8,"line_total":7619.81},{"name":"TUBO PEALPE GAS 1\/2PULG X 100M","category":"Plomer\u00eda","quantity":105,"unit":"m","unit_name":"Metro lineal","unit_price":25.62,"discount_percent":null,"discount_amount":null,"unit_price_with_discount":null,"tax_amount":430.42,"price_includes_tax":false,"line_subtotal":2690.1,"line_total":3120.52},{"name":"ABRAZADERA U\u00d1A GALV 200 013 UCR12 PARA CPVC\/COBRE","category":"Ferreter\u00eda","quantity":120,"unit":"pza","unit_name":"Pieza","unit_price":1.45,"discount_percent":null,"discount_amount":null,"unit_price_with_discount":null,"tax_amount":27.84,"price_includes_tax":false,"line_subtotal":174,"line_total":201.84},{"name":"PIJA COMBINADA 10MM GROSOR 1 LARG","category":"Ferreter\u00eda","quantity":420,"unit":"pza","unit_name":"Pieza","unit_price":0.26,"discount_percent":null,"discount_amount":null,"unit_price_with_discount":null,"tax_amount":17.47,"price_includes_tax":false,"line_subtotal":109.2,"line_total":126.67},{"name":"AG 14 8 A TAQUETE DE PLASTICO 006 (TP14)","category":"Ferreter\u00eda","quantity":420,"unit":"pza","unit_name":"Pieza","unit_price":0.21,"discount_percent":null,"discount_amount":null,"unit_price_with_discount":null,"tax_amount":14.11,"price_includes_tax":false,"line_subtotal":88.2,"line_total":102.31}],"raw_text":"[Extra\u00eddo directamente por Gemini Vision]"}',NULL,NULL,0,NULL);
INSERT INTO "quotations" VALUES (51,25,2,'quotations/DGAC6kyYjooSLsOa7EJwBWA1z5kNXuq2GE8QcHRj.jpg','','2026-06-06 15:39:51','2026-06-06 15:39:20','2026-06-11 07:01:19',NULL,'WhatsApp Image 2026-04-17 at 3.56.44 PM.jpeg','completed','[Extraído directamente por Gemini Vision]','{"supplier":"Productos Pennsylvania","store":null,"seller":"Jose Armando Can\u00fal","tax_info":{"tax_rate":0.16,"prices_include_tax":false,"tax_detected":true,"subtotal":7907.57,"tax_total":1265.21,"grand_total":9172.78},"items":[{"name":"DESMOLDANTE PRIME GLASST","category":"Cemento \/ Concreto","quantity":1,"unit":"tambor","unit_name":"Tambor","unit_price":7907.57,"discount_percent":null,"discount_amount":null,"unit_price_with_discount":null,"tax_amount":null,"price_includes_tax":false,"line_subtotal":7907.57,"line_total":null}],"raw_text":"[Extra\u00eddo directamente por Gemini Vision]"}',NULL,1,0,'{"projectId":"1","supplierName":"Productos Pennsylvania","supplierId":2,"storeName":"","vendorName":"Jose Armando Can\u00fal","annotations":"","date":"2026-06-07","items":[{"name":"DESMOLDANTE PRIME GLASST","quantity":1,"unit":"tambor","category_id":11,"category_name":"Otros","unit_price":7907.57,"unit_price_original":7907.57,"tax_amount":1265.21,"tax_source":"supplier_global","line_subtotal":7907.57,"line_total":9172.78,"discount_percent":null,"product_id":13,"conflict":{"category":{"registered":"Otros","registered_id":11,"suggested":"Cemento \/ Concreto","suggested_id":3}},"product_confirmed":true,"_match":{"product":{"status":"exact","confidence":1,"catalog_name":"DESMOLDANTE PRIME GLASST"},"category":{"status":"matched","suggested_name":"Cemento \/ Concreto"},"measure":{"status":"matched","canonical":"tambor","unit_name":"Tambor"},"suggested":{"category_id":3,"category_name":"Cemento \/ Concreto","unit":"tambor","name":"DESMOLDANTE PRIME GLASST"}}}],"supplierMatch":{"status":"exact","confidence":1,"id":2},"vendorMatch":{"status":"exact","confidence":1,"id":2}}');
INSERT INTO "quotations" VALUES (52,24,NULL,'quotations/c1INonjXiv0WY9smaDiEipak0RQHU6RZgwCe2lU0.jpg','','2026-06-07 18:23:26','2026-06-07 18:23:10','2026-06-07 18:23:46',NULL,'WhatsApp Image 2026-06-01 at 12.04.43 PM (1).jpeg','completed','[Extraído directamente por Gemini Vision]','{"supplier":"El Niplito Del Sureste","store":null,"seller":null,"tax_info":{"tax_rate":0.16,"prices_include_tax":false,"tax_detected":true,"subtotal":2030,"tax_total":324.8,"grand_total":2354.8},"items":[{"name":"PEGAZULEJO PISOS Y MARMOL GRIS 20KG CEMIX","category":"Cemento \/ Concreto","quantity":28,"unit":"pza","unit_name":"Pieza","unit_price":72.5,"discount_percent":null,"discount_amount":null,"unit_price_with_discount":null,"tax_amount":324.8,"price_includes_tax":false,"line_subtotal":2030,"line_total":2354.8}],"raw_text":"[Extra\u00eddo directamente por Gemini Vision]"}',NULL,1,0,'{"projectId":"1","supplierName":"El Niplito del Sureste","supplierId":10,"storeName":"","vendorName":"","annotations":"","date":"2026-06-07","items":[{"name":"PEGAZULEJO PISOS Y MARMOL GRIS 20KG CEMIX","quantity":28,"unit":"pza","category_id":3,"category_name":"Cemento \/ Concreto","unit_price":72.5,"unit_price_original":72.5,"tax_amount":324.8,"tax_source":"supplier_per_item","line_subtotal":2030,"line_total":2354.8,"discount_percent":null,"product_id":32,"conflict":null,"product_confirmed":true,"_match":{"product":{"status":"exact","confidence":1,"catalog_name":"PEGAZULEJO PISOS Y MARMOL GRIS 20KG CEMIX"},"category":{"status":"matched","suggested_name":"Cemento \/ Concreto"},"measure":{"status":"matched","canonical":"pza","unit_name":"Pieza"},"suggested":{"category_id":3,"category_name":"Cemento \/ Concreto","unit":"pza","name":"PEGAZULEJO PISOS Y MARMOL GRIS 20KG CEMIX"}}}],"supplierMatch":{"status":"exact","confidence":1,"id":10},"vendorMatch":[]}');
INSERT INTO "quotations" VALUES (53,NULL,NULL,'quotations/Vk61YbHNK2zHoGhrSMxN5xCfWnDJKccHoJE9IOD4.jpg','','2026-06-11 07:03:10','2026-06-11 07:02:58','2026-06-12 05:16:44',NULL,'WhatsApp Image 2026-04-17 at 3.56.44 PM.jpeg','completed','[Extraído directamente por Gemini Vision]','{"supplier":"Productos Pennsylvania","store":null,"seller":"Jose Armando Canul","tax_info":{"tax_rate":0.16,"prices_include_tax":false,"tax_detected":true,"subtotal":7907.57,"tax_total":1265.21,"grand_total":9172.78},"items":[{"name":"DESMOLDANTE PRIME GLASST","category":"Otros","quantity":1,"unit":"tambor","unit_name":"Tambor","unit_price":7907.57,"discount_percent":null,"discount_amount":null,"unit_price_with_discount":null,"tax_amount":null,"price_includes_tax":false,"line_subtotal":7907.57,"line_total":null}],"raw_text":"[Extra\u00eddo directamente por Gemini Vision]"}',NULL,1,0,'{"projectId":"","supplierName":"Productos Pennsylvania","supplierId":2,"storeName":"","vendorName":"Jose Armando Can\u00fal","annotations":"","date":"2026-06-12","items":[{"name":"DESMOLDANTE PRIME GLASST","quantity":"1","unit":"kg","category_id":11,"category_name":"Otros","unit_price":7907.57,"unit_price_original":7907.57,"tax_amount":1277.86,"tax_source":"supplier_global","line_subtotal":7986.65,"line_total":9264.51,"discount_percent":null,"product_id":13,"conflict":null,"product_confirmed":true,"_match":{"product":{"status":"exact","confidence":1,"catalog_name":"DESMOLDANTE PRIME GLASST"},"category":{"status":"matched","suggested_name":"Otros"},"measure":{"status":"matched","canonical":"tambor","unit_name":"Tambor"},"suggested":{"category_id":11,"category_name":"Otros","unit":"tambor","name":"DESMOLDANTE PRIME GLASST"}}}],"supplierMatch":{"status":"exact","confidence":1,"id":2},"vendorMatch":{"status":"exact","confidence":1,"id":2}}');
INSERT INTO "requisition_activities" VALUES (1,25,1,'created','Requisición creada.',NULL,NULL,'2026-06-11 07:01:19','2026-06-11 07:01:19');
INSERT INTO "requisition_items" VALUES (13,2,13,1,7907.57,2,'2026-05-14 08:58:50','2026-05-14 08:58:50',7907.57,1265.21,'supplier_per_item',7907.57,9172.78,3,0,NULL,NULL,NULL);
INSERT INTO "requisition_items" VALUES (14,3,1,70,20.28,1,'2026-05-14 09:10:21','2026-05-14 09:10:21',20.28,227.14,'supplier_per_item',1419.6,1646.74,1,0,NULL,NULL,NULL);
INSERT INTO "requisition_items" VALUES (15,3,2,42,39.96,1,'2026-05-14 09:10:21','2026-05-14 09:10:21',39.96,268.53,'supplier_per_item',1678.32,1946.85,2,0,NULL,NULL,NULL);
INSERT INTO "requisition_items" VALUES (16,3,3,9,52.87,1,'2026-05-14 09:10:21','2026-05-14 09:10:21',52.87,76.13,'supplier_per_item',475.83,551.96,1,0,NULL,NULL,NULL);
INSERT INTO "requisition_items" VALUES (17,3,4,18,24.46,1,'2026-05-14 09:10:21','2026-05-14 09:10:21',24.46,70.44,'supplier_per_item',440.28,510.72,1,0,NULL,NULL,NULL);
INSERT INTO "requisition_items" VALUES (18,3,5,60,61.09,1,'2026-05-14 09:10:21','2026-05-14 09:10:21',61.09,586.46,'supplier_per_item',3665.4,4251.86,1,0,NULL,NULL,NULL);
INSERT INTO "requisition_items" VALUES (19,3,6,60,40.14,1,'2026-05-14 09:10:21','2026-05-14 09:10:21',40.14,385.34,'supplier_per_item',2408.4,2793.74,1,0,NULL,NULL,NULL);
INSERT INTO "requisition_items" VALUES (20,3,7,30,89.42,1,'2026-05-14 09:10:21','2026-05-14 09:10:21',89.42,429.22,'supplier_per_item',2682.6,3111.82,1,0,NULL,NULL,NULL);
INSERT INTO "requisition_items" VALUES (21,3,8,105,62.56,1,'2026-05-14 09:10:21','2026-05-14 09:10:21',62.56,1051.01,'supplier_per_item',6568.8,7619.81,1,0,NULL,NULL,NULL);
INSERT INTO "requisition_items" VALUES (22,3,9,105,25.62,1,'2026-05-14 09:10:21','2026-05-14 09:10:21',25.62,430.42,'supplier_per_item',2690.1,3120.52,2,0,NULL,NULL,NULL);
INSERT INTO "requisition_items" VALUES (23,3,14,120,1.45,1,'2026-05-14 09:10:21','2026-05-14 09:10:21',1.45,27.84,'supplier_per_item',174,201.84,1,0,NULL,NULL,NULL);
INSERT INTO "requisition_items" VALUES (24,3,11,420,0.26,1,'2026-05-14 09:10:21','2026-05-14 09:10:21',0.26,17.47,'supplier_per_item',109.2,126.67,1,0,NULL,NULL,NULL);
INSERT INTO "requisition_items" VALUES (25,3,12,420,0.21,1,'2026-05-14 09:10:21','2026-05-14 09:10:21',0.21,14.11,'supplier_per_item',88.2,102.31,1,0,NULL,NULL,NULL);
INSERT INTO "requisition_items" VALUES (47,14,1,70,20.28,1,'2026-05-23 04:35:53','2026-05-23 04:35:53',20.28,227.14,'supplier_per_item',1419.6,1646.74,1,0,NULL,NULL,NULL);
INSERT INTO "requisition_items" VALUES (48,14,2,42,39.96,1,'2026-05-23 04:35:53','2026-05-23 04:35:53',39.96,268.53,'supplier_per_item',1678.32,1946.85,2,0,NULL,NULL,NULL);
INSERT INTO "requisition_items" VALUES (49,14,3,9,52.87,1,'2026-05-23 04:35:53','2026-05-23 04:35:53',52.87,76.13,'supplier_per_item',475.83,551.96,1,0,NULL,NULL,NULL);
INSERT INTO "requisition_items" VALUES (50,14,4,18,24.46,1,'2026-05-23 04:35:53','2026-05-23 04:35:53',24.46,70.44,'supplier_per_item',440.28,510.72,1,0,NULL,NULL,NULL);
INSERT INTO "requisition_items" VALUES (51,14,5,60,61.09,1,'2026-05-23 04:35:53','2026-05-23 04:35:53',61.09,586.46,'supplier_per_item',3665.4,4251.86,1,0,NULL,NULL,NULL);
INSERT INTO "requisition_items" VALUES (52,14,6,60,40.14,1,'2026-05-23 04:35:53','2026-05-23 04:35:53',40.14,385.34,'supplier_per_item',2408.4,2793.74,1,0,NULL,NULL,NULL);
INSERT INTO "requisition_items" VALUES (53,14,7,30,89.42,1,'2026-05-23 04:35:53','2026-05-23 04:35:53',89.42,429.22,'supplier_per_item',2682.6,3111.82,1,0,NULL,NULL,NULL);
INSERT INTO "requisition_items" VALUES (54,14,8,105,62.56,1,'2026-05-23 04:35:53','2026-05-23 04:35:53',62.56,1051.01,'supplier_per_item',6568.8,7619.81,1,0,NULL,NULL,NULL);
INSERT INTO "requisition_items" VALUES (55,14,9,105,25.62,1,'2026-05-23 04:35:53','2026-05-23 04:35:53',25.62,430.42,'supplier_per_item',2690.1,3120.52,2,0,NULL,NULL,NULL);
INSERT INTO "requisition_items" VALUES (56,14,26,120,1.45,1,'2026-05-23 04:35:53','2026-05-23 04:35:53',1.45,27.84,'supplier_per_item',174,201.84,1,0,NULL,NULL,NULL);
INSERT INTO "requisition_items" VALUES (57,14,11,420,0.26,1,'2026-05-23 04:35:53','2026-05-23 04:35:53',0.26,17.47,'supplier_per_item',109.2,126.67,1,0,NULL,NULL,NULL);
INSERT INTO "requisition_items" VALUES (58,14,12,420,0.21,1,'2026-05-23 04:35:53','2026-05-23 04:35:53',0.21,14.11,'supplier_per_item',88.2,102.31,1,0,NULL,NULL,NULL);
INSERT INTO "requisition_items" VALUES (59,15,1,70,20.28,1,'2026-05-27 07:53:26','2026-05-27 07:53:26',20.28,227.14,'supplier_per_item',1419.6,1646.74,1,0,NULL,NULL,NULL);
INSERT INTO "requisition_items" VALUES (60,15,2,42,39.96,1,'2026-05-27 07:53:26','2026-05-27 07:53:26',39.96,268.53,'supplier_per_item',1678.32,1946.85,2,0,NULL,NULL,NULL);
INSERT INTO "requisition_items" VALUES (61,15,3,9,52.87,1,'2026-05-27 07:53:26','2026-05-27 07:53:26',52.87,76.13,'supplier_per_item',475.83,551.96,1,0,NULL,NULL,NULL);
INSERT INTO "requisition_items" VALUES (62,15,4,18,24.46,1,'2026-05-27 07:53:26','2026-05-27 07:53:26',24.46,70.44,'supplier_per_item',440.28,510.72,1,0,NULL,NULL,NULL);
INSERT INTO "requisition_items" VALUES (63,15,5,60,61.09,1,'2026-05-27 07:53:26','2026-05-27 07:53:26',61.09,586.46,'supplier_per_item',3665.4,4251.86,1,0,NULL,NULL,NULL);
INSERT INTO "requisition_items" VALUES (64,15,6,60,40.14,1,'2026-05-27 07:53:26','2026-05-27 07:53:26',40.14,385.34,'supplier_per_item',2408.4,2793.74,1,0,NULL,NULL,NULL);
INSERT INTO "requisition_items" VALUES (65,15,7,30,89.42,1,'2026-05-27 07:53:26','2026-05-27 07:53:26',89.42,429.22,'supplier_per_item',2682.6,3111.82,1,0,NULL,NULL,NULL);
INSERT INTO "requisition_items" VALUES (66,15,8,105,62.56,1,'2026-05-27 07:53:26','2026-05-27 07:53:26',62.56,1051.01,'supplier_per_item',6568.8,7619.81,1,0,NULL,NULL,NULL);
INSERT INTO "requisition_items" VALUES (67,15,9,105,25.62,1,'2026-05-27 07:53:26','2026-05-27 07:53:26',25.62,430.42,'supplier_per_item',2690.1,3120.52,2,0,NULL,NULL,NULL);
INSERT INTO "requisition_items" VALUES (68,15,26,120,1.45,1,'2026-05-27 07:53:26','2026-05-27 07:53:26',1.45,27.84,'supplier_per_item',174,201.84,1,0,NULL,NULL,NULL);
INSERT INTO "requisition_items" VALUES (69,15,11,420,0.26,1,'2026-05-27 07:53:26','2026-05-27 07:53:26',0.26,17.47,'supplier_per_item',109.2,126.67,1,0,NULL,NULL,NULL);
INSERT INTO "requisition_items" VALUES (70,15,12,420,0.21,1,'2026-05-27 07:53:26','2026-05-27 07:53:26',0.21,14.11,'supplier_per_item',88.2,102.31,1,0,NULL,NULL,NULL);
INSERT INTO "requisition_items" VALUES (71,16,1,70,20.28,1,'2026-05-30 01:21:34','2026-05-30 01:21:34',20.28,227.14,'supplier_per_item',1419.6,1646.74,2,0,NULL,NULL,NULL);
INSERT INTO "requisition_items" VALUES (72,16,2,42,39.96,1,'2026-05-30 01:21:34','2026-05-30 01:21:34',39.96,268.53,'supplier_per_item',1678.32,1946.85,2,0,NULL,NULL,NULL);
INSERT INTO "requisition_items" VALUES (73,16,3,9,52.87,1,'2026-05-30 01:21:34','2026-05-30 01:21:34',52.87,76.13,'supplier_per_item',475.83,551.96,1,0,NULL,NULL,NULL);
INSERT INTO "requisition_items" VALUES (74,16,4,18,24.46,1,'2026-05-30 01:21:34','2026-05-30 01:21:34',24.46,70.44,'supplier_per_item',440.28,510.72,1,0,NULL,NULL,NULL);
INSERT INTO "requisition_items" VALUES (75,16,5,60,61.09,1,'2026-05-30 01:21:34','2026-05-30 01:21:34',61.09,586.46,'supplier_per_item',3665.4,4251.86,1,0,NULL,NULL,NULL);
INSERT INTO "requisition_items" VALUES (76,16,6,60,40.14,1,'2026-05-30 01:21:34','2026-05-30 01:21:34',40.14,385.34,'supplier_per_item',2408.4,2793.74,1,0,NULL,NULL,NULL);
INSERT INTO "requisition_items" VALUES (77,16,7,30,89.42,1,'2026-05-30 01:21:34','2026-05-30 01:21:34',89.42,429.22,'supplier_per_item',2682.6,3111.82,1,0,NULL,NULL,NULL);
INSERT INTO "requisition_items" VALUES (78,16,8,105,62.56,1,'2026-05-30 01:21:34','2026-05-30 01:21:34',62.56,1051.01,'supplier_per_item',6568.8,7619.81,1,0,NULL,NULL,NULL);
INSERT INTO "requisition_items" VALUES (79,16,9,105,25.62,1,'2026-05-30 01:21:34','2026-05-30 01:21:34',25.62,430.42,'supplier_per_item',2690.1,3120.52,2,0,NULL,NULL,NULL);
INSERT INTO "requisition_items" VALUES (80,16,26,120,1.45,1,'2026-05-30 01:21:34','2026-05-30 01:21:34',1.45,27.84,'supplier_per_item',174,201.84,1,0,NULL,NULL,NULL);
INSERT INTO "requisition_items" VALUES (81,16,11,420,0.26,1,'2026-05-30 01:21:34','2026-05-30 01:21:34',0.26,17.47,'supplier_per_item',109.2,126.67,1,0,NULL,NULL,NULL);
INSERT INTO "requisition_items" VALUES (82,16,12,420,0.21,1,'2026-05-30 01:21:34','2026-05-30 01:21:34',0.21,14.11,'supplier_per_item',88.2,102.31,1,0,NULL,NULL,NULL);
INSERT INTO "requisition_items" VALUES (83,17,27,145,121.33,7,'2026-05-31 21:46:57','2026-05-31 21:46:57',121.33,2814.86,'user_confirmed',17592.85,20407.71,1,0,NULL,NULL,NULL);
INSERT INTO "requisition_items" VALUES (84,17,28,1,350,7,'2026-05-31 21:46:57','2026-05-31 21:46:57',350,56,'user_confirmed',350,406,5,0,NULL,NULL,NULL);
INSERT INTO "requisition_items" VALUES (91,20,32,28,72.5,10,'2026-06-04 04:00:26','2026-06-04 04:00:26',72.5,324.8,'supplier_global',2030,2354.8,1,0,NULL,NULL,NULL);
INSERT INTO "requisition_items" VALUES (92,21,32,28,72.5,10,'2026-06-04 10:22:00','2026-06-04 10:22:00',72.5,324.8,'supplier_global',2030,2354.8,1,0,NULL,NULL,NULL);
INSERT INTO "requisition_items" VALUES (106,24,32,28,72.5,10,'2026-06-07 18:23:46','2026-06-07 18:23:46',72.5,324.8,'supplier_per_item',2030,2354.8,1,0,NULL,NULL,NULL);
INSERT INTO "requisition_items" VALUES (107,25,13,1,7907.57,2,'2026-06-11 07:01:19','2026-06-11 07:01:19',7907.57,1265.21,'supplier_global',7907.57,9172.78,3,0,NULL,NULL,NULL);
INSERT INTO "requisitions" VALUES (2,1,'','aprobada',1,1,NULL,'2026-05-14 00:00:00','2026-05-14 08:58:50','2026-05-18 06:56:40','CEN1-REQ0002',2,0);
INSERT INTO "requisitions" VALUES (3,1,'','aprobada',1,1,NULL,'2026-05-14 00:00:00','2026-05-14 09:10:21','2026-05-18 06:56:36','CEN1-REQ0003',1,0);
INSERT INTO "requisitions" VALUES (14,3,'','aprobada',1,1,NULL,'2026-05-23 00:00:00','2026-05-23 04:35:53','2026-06-07 17:32:41','COR3-REQ0014',1,0);
INSERT INTO "requisitions" VALUES (15,1,'','aprobada',1,1,NULL,'2026-05-27 00:00:00','2026-05-27 07:53:26','2026-06-07 17:32:41','CEN1-REQ0015',1,0);
INSERT INTO "requisitions" VALUES (16,3,'','aprobada',1,1,NULL,'2026-05-30 00:00:00','2026-05-30 01:21:34','2026-06-07 17:32:41','COR3-REQ0016',1,0);
INSERT INTO "requisitions" VALUES (17,1,'','aprobada',1,1,NULL,'2026-05-31 00:00:00','2026-05-31 21:46:57','2026-06-07 17:32:41','CEN1-REQ0017',NULL,0);
INSERT INTO "requisitions" VALUES (20,1,'','aprobada',1,1,NULL,'2026-06-04 00:00:00','2026-06-04 04:00:26','2026-06-06 04:29:28','CEN1-REQ0020',NULL,0);
INSERT INTO "requisitions" VALUES (21,1,'','aprobada',1,1,NULL,'2026-06-04 00:00:00','2026-06-04 10:22:00','2026-06-05 07:33:30','CEN1-REQ0021',NULL,0);
INSERT INTO "requisitions" VALUES (24,1,'','aprobada',1,1,NULL,'2026-06-07 00:00:00','2026-06-07 18:23:46','2026-06-10 19:56:41','CEN1-REQ0024',NULL,0);
INSERT INTO "requisitions" VALUES (25,1,'','borrador',1,NULL,NULL,'2026-06-07 00:00:00','2026-06-11 07:01:19','2026-06-11 07:01:19','CEN1-REQ0025',2,0);
INSERT INTO "roles" VALUES (1,'Administrador','2026-05-14 03:15:04','2026-05-14 03:15:04','["*"]');
INSERT INTO "roles" VALUES (2,'Encargado de Compras','2026-05-14 03:15:04','2026-05-14 03:15:04','["requisiciones.ver","requisiciones.crear","requisiciones.editar","requisiciones.aprobar","proveedores.ver","proveedores.crear","proveedores.editar","proveedores.eliminar","cotizaciones.cargar","productos.ver","productos.crear","productos.eliminar","catalogos.ver","catalogos.editar","reportes.ver","gastos.ver","proyectos.ver"]');
INSERT INTO "roles" VALUES (3,'Supervisor / Operativo','2026-05-14 03:15:04','2026-05-14 03:15:04','["proyectos.ver","gastos.ver","gastos.crear","requisiciones.ver","requisiciones.crear","reportes.ver"]');
INSERT INTO "sessions" VALUES ('eE7iHwehVt89NwfX1AGcUSXDnYFXvGONOvabtVBn',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJMbVBtR3BZaVNqNWhZUzJzRTROdmpvWVVtcGNDdEk1SVY1eWdTMlRaIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9tZWRpZGFzIiwicm91dGUiOiJtZWRpZGFzLmluZGV4In0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfSwibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiOjF9',1781299547);
INSERT INTO "settings" VALUES (1,'company_name','Constructora Muulsinik S.A. de C.V.','empresa','string','Nombre de la empresa','Razón social completa de la constructora','2026-05-14 03:15:04','2026-05-14 03:15:04');
INSERT INTO "settings" VALUES (2,'company_rfc','','empresa','string','RFC','Registro Federal de Contribuyentes','2026-05-14 03:15:04','2026-05-14 03:15:04');
INSERT INTO "settings" VALUES (3,'company_address','','empresa','string','Dirección fiscal','Dirección completa de la empresa','2026-05-14 03:15:04','2026-05-14 03:15:04');
INSERT INTO "settings" VALUES (4,'company_phone','','empresa','string','Teléfono','Teléfono principal de contacto','2026-05-14 03:15:04','2026-05-14 03:15:04');
INSERT INTO "settings" VALUES (5,'company_email','','empresa','string','Correo electrónico','Email de contacto de la empresa','2026-05-14 03:15:04','2026-05-14 03:15:04');
INSERT INTO "settings" VALUES (6,'company_logo','company/qWzfxuoyhH5cMEfT7NKRAVLUnYDnrplkpJtAvXTZ.png','empresa','string','Logo','Logo de la empresa para documentos','2026-05-14 03:15:04','2026-06-02 01:21:57');
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
INSERT INTO "suppliers" VALUES (7,'G.e.s. Aceros y Mas',NULL,NULL,NULL,NULL,'2026-05-31 21:46:57','2026-05-31 21:46:57','ges aceros y mas');
INSERT INTO "suppliers" VALUES (8,'Cemex S.a. de C.v.',NULL,NULL,NULL,NULL,'2026-06-01 07:13:11','2026-06-01 07:13:11','cemex');
INSERT INTO "suppliers" VALUES (9,'Bo',NULL,'B7DUDUDW','Materiales',NULL,'2026-06-01 07:13:45','2026-06-04 07:38:57','bo');
INSERT INTO "suppliers" VALUES (10,'El Niplito del Sureste',NULL,'B7DUDUDE','Materiales',NULL,'2026-06-04 04:00:26','2026-06-04 07:37:27','el niplito del sureste');
INSERT INTO "users" VALUES (1,'Administrador','admin@muulsinik.com',NULL,'$2y$12$PgWVdxipGNWufdaWt.zMo.I7J/EZnIX0GZ1cGJruKejltSxVGofJ.',1,1,NULL,'2026-05-14 03:15:04','2026-05-18 03:26:06');
INSERT INTO "users" VALUES (4,'Hazel Hoil','hazel@muulsinik.com',NULL,'$2y$12$eFCROyvRCu1vIOxjjOhqPeD7YtEhdVCk9namn.qSwF/coXYd5ff5G',2,1,NULL,'2026-06-11 05:33:48','2026-06-11 05:49:12');
INSERT INTO "vendors" VALUES (1,1,'Leticia Alejandra Dzul Uh',NULL,NULL,'2026-05-14 07:11:04','2026-05-14 07:11:04');
INSERT INTO "vendors" VALUES (2,2,'Jose Armando Canúl',NULL,NULL,'2026-05-14 08:58:50','2026-05-14 08:58:50');
INSERT INTO "vendors" VALUES (3,8,'Leticia Dzul',NULL,NULL,'2026-06-01 07:13:11','2026-06-01 07:13:11');
INSERT INTO "vendors" VALUES (4,9,'Leticia Dzul',NULL,NULL,'2026-06-01 07:13:45','2026-06-01 07:13:45');
CREATE INDEX IF NOT EXISTS "cache_expiration_index" ON "cache" (
	"expiration"
);
CREATE INDEX IF NOT EXISTS "cache_locks_expiration_index" ON "cache_locks" (
	"expiration"
);
CREATE UNIQUE INDEX IF NOT EXISTS "categories_name_unique" ON "categories" (
	"name"
);
CREATE INDEX IF NOT EXISTS "expenses_concept_index" ON "expenses" (
	"concept"
);
CREATE INDEX IF NOT EXISTS "expenses_project_id_index" ON "expenses" (
	"project_id"
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
CREATE INDEX IF NOT EXISTS "products_canonical_name_index" ON "products" (
	"canonical_name"
);
CREATE INDEX IF NOT EXISTS "products_normalized_name_index" ON "products" (
	"normalized_name"
);
CREATE INDEX IF NOT EXISTS "projects_name_index" ON "projects" (
	"name"
);
CREATE INDEX IF NOT EXISTS "quick_budgets_title_index" ON "quick_budgets" (
	"title"
);
CREATE INDEX IF NOT EXISTS "requisitions_number_index" ON "requisitions" (
	"number"
);
CREATE INDEX IF NOT EXISTS "requisitions_project_id_index" ON "requisitions" (
	"project_id"
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
CREATE INDEX IF NOT EXISTS "suppliers_trade_name_index" ON "suppliers" (
	"trade_name"
);
CREATE UNIQUE INDEX IF NOT EXISTS "users_email_unique" ON "users" (
	"email"
);
COMMIT;
