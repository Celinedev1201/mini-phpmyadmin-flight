<?php

// Ce script s’exécute en ligne de commande, sans passer par l’API.
if (PHP_SAPI !== 'cli') {
    exit;
}

require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/src/http/Api.php';
require dirname(__DIR__) . '/src/validation/SqlValidation.php';
require dirname(__DIR__) . '/src/services/BackupService.php';

require dirname(__DIR__) . '/src/validation/PostgresValidation.php';
require dirname(__DIR__) . '/src/services/PostgresBackupService.php';

// Les arguments sont le nom de la base puis le moteur, MySQL étant le choix par défaut.
// argv contient les mots saisis après php bin/backup.php. Sans base, le script affiche l’aide et s’arrête.
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
// Le moteur choisit le service de sauvegarde. Aucun export n’est lancé avant les vérifications précédentes.
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