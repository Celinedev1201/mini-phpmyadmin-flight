<?php

// Ce script s’exécute en ligne de commande, sans passer par l’API.
if (PHP_SAPI !== 'cli') {
    exit;
}

require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/src/Api.php';
require dirname(__DIR__) . '/src/SqlValidation.php';
require dirname(__DIR__) . '/src/BackupService.php';

$database = $argv[1] ?? '';

if ($database === '') {
    fwrite(STDERR, "Usage : php bin/backup.php nom_de_la_base\n");
    exit(1);
}

try {
    $backup = BackupService::create($database);

    // La date et le résultat serviront à suivre les sauvegardes automatiques.
    fwrite(
        STDOUT,
        sprintf(
            "[%s] Sauvegarde réussie : %s / %s\n",
            gmdate('c'),
            $database,
            $backup['filename']
        )
    );
} catch (Throwable $exception) {
    fwrite(
        STDERR,
        sprintf(
            "[%s] Échec pour %s : %s\n",
            gmdate('c'),
            $database,
            $exception->getMessage()
        )
    );

    exit(1);
}