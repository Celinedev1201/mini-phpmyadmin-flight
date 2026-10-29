<?php

require dirname(__DIR__) . '/src/database/PostgresDatabase.php';
require dirname(__DIR__) . '/src/http/Api.php';
require dirname(__DIR__) . '/src/validation/PostgresValidation.php';
require dirname(__DIR__) . '/src/services/PostgresDatabaseService.php';
require dirname(__DIR__) . '/src/services/PostgresBackupService.php';
require dirname(__DIR__) . '/src/backups/PostgresBackupCatalog.php';
require dirname(__DIR__) . '/src/services/PostgresRestoreService.php';

// Ouvre une connexion à la base de test indiquée.
function connectTestDatabase(string $database): PDO
{
    $host = getenv('PG_HOST') ?: 'postgres';
    $port = getenv('PG_PORT') ?: '5432';

    return new PDO(
        "pgsql:host=$host;port=$port;dbname=$database",
        getenv('PG_USER') ?: '',
        getenv('PG_PASSWORD') ?: '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
}

$suffix = bin2hex(random_bytes(4));
$source = 'safebase_test_pg_' . $suffix;
$target = 'safebase_test_restore_pg_' . $suffix;
$sourceCreated = false;
$targetRestored = false;
$restoreAttempted = false;
$backupPath = null;
$connection = null;
$exitCode = 0;

echo "TEST POSTGRESQL : sauvegarde, restauration et données\n\n";

try {
    $admin = PostgresDatabase::connect();
    $admin->exec("CREATE DATABASE \"$source\"");
    $sourceCreated = true;

    $connection = connectTestDatabase($source);
    $connection->exec(
        'CREATE TABLE produits (id INT PRIMARY KEY, nom VARCHAR(100) NOT NULL)'
    );
    $connection->exec(
        "INSERT INTO produits (id, nom) VALUES (1, 'Produit témoin')"
    );
    $connection = null;
    echo "RÉUSSI : base source et donnée témoin créées.\n";

    $backup = PostgresBackupService::create($source);
    $backupPath = '/var/backups/safebase/postgres/'
        . $source . '/' . $backup['filename'];

    if (!is_file($backupPath) || filesize($backupPath) === 0) {
        throw new RuntimeException('Sauvegarde absente ou vide.');
    }
    echo "RÉUSSI : sauvegarde SQL non vide créée.\n";

    $restoreAttempted = true;
    PostgresRestoreService::restore($source, $backup['filename'], $target);
    $targetRestored = true;

    $connection = connectTestDatabase($target);
    $rows = $connection->query(
        'SELECT id::text AS id, nom FROM produits ORDER BY id'
    )->fetchAll(PDO::FETCH_ASSOC);

    if ($rows !== [['id' => '1', 'nom' => 'Produit témoin']]) {
        throw new RuntimeException('Les données restaurées sont incorrectes.');
    }
    echo "RÉUSSI : table restaurée avec exactement la donnée attendue.\n";
} catch (Throwable $exception) {
    fwrite(STDERR, "ÉCHEC : " . $exception->getMessage() . "\n");
    $exitCode = 1;
} finally {
    // PostgreSQL exige de fermer la connexion avant de supprimer la base.
    $connection = null;
    foreach ([$source => $sourceCreated, $target => $targetRestored] as $base => $created) {
        if (!$created) continue;
        try {
            $admin->exec("DROP DATABASE \"$base\"");
            echo "Nettoyage : $base supprimée.\n";
        } catch (Throwable $exception) {
            fwrite(STDERR, "ÉCHEC du nettoyage : $base\n");
            $exitCode = 1;
        }
    }
    if ($restoreAttempted && !$targetRestored) {
        fwrite(STDERR, "À vérifier : une base cible peut subsister : $target\n");
    }
    if ($backupPath !== null && is_file($backupPath)) {
        if (!unlink($backupPath) || !rmdir(dirname($backupPath))) {
            fwrite(STDERR, "ÉCHEC du nettoyage du fichier ou dossier de test.\n");
            $exitCode = 1;
        } else {
            echo "Nettoyage : sauvegarde de test supprimée.\n";
        }
    }
}

exit($exitCode);