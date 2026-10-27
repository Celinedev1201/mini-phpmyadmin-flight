<?php

// Prépare les fichiers utilisés par cron au démarrage du conteneur.
if (PHP_SAPI !== 'cli') {
    exit;
}

require dirname(__DIR__) . '/src/http/Api.php';
require dirname(__DIR__) . '/src/validation/SqlValidation.php';

require dirname(__DIR__) . '/src/validation/PostgresValidation.php';

$postgres = getenv('PG_BACKUP_DATABASE') ?: getenv('PG_DATABASE');
PostgresValidation::database($postgres ?: '');
$database = getenv('BACKUP_DATABASE') ?: 'atelier_theoa';
SqlValidation::database($database);

// Le script privé transmet les paramètres de connexion aux tâches cron.
// umask limite les permissions des fichiers créés pour protéger les paramètres de connexion.
umask(0077);

$script = "#!/bin/sh\n";
$script .= "export PATH='/usr/local/bin:/usr/bin:/bin'\n";

foreach (['DB_HOST', 'DB_PORT', 'DB_USER', 'DB_PASSWORD',
    'PG_HOST', 'PG_PORT', 'PG_DATABASE', 'PG_USER', 'PG_PASSWORD'] as $name) {
    $value = getenv($name);

    if ($value === false) {
        throw new RuntimeException("Variable manquante : $name");
    }

    $script .= 'export ' . $name . '=' . escapeshellarg($value) . "\n";
}

// Les deux moteurs sont tentés, même si le premier échoue.
$script .= "status=0\n";
foreach (['mysql' => $database, 'postgres' => $postgres] as $engine => $name) {
    $script .= '/usr/local/bin/php /var/www/html/bin/backup.php ';
    $script .= escapeshellarg($name) . ' ' . $engine . " || status=1\n";
}
$script .= 'exit "$status"' . "\n";

if (file_put_contents('/run/safebase-backup.sh', $script) === false) {
    throw new RuntimeException('Impossible de préparer le script cron.');
}

// 0700 réserve la lecture, l’écriture et l’exécution de ce script à son propriétaire.
chmod('/run/safebase-backup.sh', 0700);

// Le verrou empêche deux sauvegardes automatiques de se chevaucher.
// Les cinq valeurs indiquent une exécution chaque jour à 12 h, selon l’heure du conteneur.
$job = "0 12 * * * root /usr/bin/flock -n /run/safebase-backup.lock ";
$job .= "/run/safebase-backup.sh ";
$job .= ">> /var/backups/safebase/cron.log 2>&1\n";

if (file_put_contents('/etc/cron.d/safebase', $job) === false) {
    throw new RuntimeException('Impossible de préparer la planification.');
}

chmod('/etc/cron.d/safebase', 0644);

echo "Cron prêt : une sauvegarde quotidienne à 12 h UTC pour MySQL ($database) et PostgreSQL ($postgres).\n";