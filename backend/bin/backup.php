<?php

// Ce script s’exécute en ligne de commande, sans passer par l’API.
if (PHP_SAPI !== 'cli') {
    exit;
}

require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/src/Api.php';
require dirname(__DIR__) . '/src/SqlValidation.php';
require dirname(__DIR__) . '/src/BackupService.php';

require dirname(__DIR__) . '/src/PostgresValidation.php';
require dirname(__DIR__) . '/src/PostgresBackupService.php';

$database = $argv[1] ?? '';
$engine = $argv[2] ?? 'mysql';

if ($database === '') {
    fwrite(STDERR, "Usage : php bin/backup.php nom_de_la_base [mysql|postgres]\n");
    exit(1);
}

try {
    if (!in_array($engine, ['mysql', 'postgres'], true)) {
        throw new InvalidArgumentException('Moteur inconnu.');
    }
    $backup = $engine === 'postgres'
        ? PostgresBackupService::create($database)
        : BackupService::create($database);

    // La date et le résultat serviront à suivre les sauvegardes automatiques.
    fwrite(
        STDOUT,
        sprintf(
            "[%s] Sauvegarde réussie [%s] : %s / %s\n",
            gmdate('c'),
            $engine,
            $database,
            $backup['filename']
        )
    );
} catch (Throwable $exception) {
    fwrite(
        STDERR,
        sprintf(
            "[%s] Échec [%s] pour %s : %s\n",
            gmdate('c'),
            $engine,
            $database,
            $exception->getMessage()
        )
    );

    exit(1);
}