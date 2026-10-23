<?php

require dirname(__DIR__) . '/vendor/autoload.php';

// Charge les classes du projet, comme les routes ci-dessous.
require dirname(__DIR__) . '/src/Database.php';
require dirname(__DIR__) . '/src/Api.php';
require dirname(__DIR__) . '/src/SqlValidation.php';
require dirname(__DIR__) . '/src/SchemaService.php';
require dirname(__DIR__) . '/src/DatabaseService.php';
require dirname(__DIR__) . '/src/TableService.php';
require dirname(__DIR__) . '/src/ColumnService.php';
require dirname(__DIR__) . '/src/RowService.php';
require dirname(__DIR__) . '/src/BackupService.php';
require dirname(__DIR__) . '/src/BackupCatalog.php';
require dirname(__DIR__) . '/src/RestoreService.php';
require dirname(__DIR__) . '/src/PostgresDatabase.php';
require dirname(__DIR__) . '/src/PostgresValidation.php';
require dirname(__DIR__) . '/src/PostgresBackupService.php';
require dirname(__DIR__) . '/src/PostgresBackupCatalog.php';
require dirname(__DIR__) . '/src/PostgresRestoreService.php';

require dirname(__DIR__) . '/src/routes/health.php';
require dirname(__DIR__) . '/src/routes/databases.php';
require dirname(__DIR__) . '/src/routes/tables.php';
require dirname(__DIR__) . '/src/routes/columns.php';
require dirname(__DIR__) . '/src/routes/rows.php';
require dirname(__DIR__) . '/src/routes/backups.php';
require dirname(__DIR__) . '/src/routes/postgres.php';

Flight::start();
