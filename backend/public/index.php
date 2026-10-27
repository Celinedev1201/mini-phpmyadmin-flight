<?php

require dirname(__DIR__) . '/vendor/autoload.php';

// Charge les classes du projet, comme les routes ci-dessous.
require dirname(__DIR__) . '/src/database/Database.php';
require dirname(__DIR__) . '/src/http/Api.php';
require dirname(__DIR__) . '/src/validation/SqlValidation.php';
require dirname(__DIR__) . '/src/services/SchemaService.php';
require dirname(__DIR__) . '/src/services/DatabaseService.php';
require dirname(__DIR__) . '/src/services/TableService.php';
require dirname(__DIR__) . '/src/services/ColumnService.php';
require dirname(__DIR__) . '/src/services/RowService.php';
require dirname(__DIR__) . '/src/services/BackupService.php';
require dirname(__DIR__) . '/src/backups/BackupCatalog.php';
require dirname(__DIR__) . '/src/services/RestoreService.php';
require dirname(__DIR__) . '/src/database/PostgresDatabase.php';
require dirname(__DIR__) . '/src/validation/PostgresValidation.php';
require dirname(__DIR__) . '/src/services/PostgresDatabaseService.php';
require dirname(__DIR__) . '/src/services/PostgresBackupService.php';
require dirname(__DIR__) . '/src/backups/PostgresBackupCatalog.php';
require dirname(__DIR__) . '/src/services/PostgresRestoreService.php';
require dirname(__DIR__) . '/src/connections/ConnectionTest.php';

require dirname(__DIR__) . '/src/routes/health.php';
require dirname(__DIR__) . '/src/routes/databases.php';
require dirname(__DIR__) . '/src/routes/tables.php';
require dirname(__DIR__) . '/src/routes/columns.php';
require dirname(__DIR__) . '/src/routes/rows.php';
require dirname(__DIR__) . '/src/routes/backups.php';
require dirname(__DIR__) . '/src/routes/postgres.php';
require dirname(__DIR__) . '/src/routes/connections.php';

// Démarre Flight : il choisit la route correspondant à l’adresse et à l’action reçues.
Flight::start();
