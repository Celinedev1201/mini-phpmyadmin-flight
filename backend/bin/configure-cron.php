<?php

// Prépare les fichiers utilisés par cron au démarrage du conteneur.
if (PHP_SAPI !== 'cli') {
    exit;
}

require dirname(__DIR__) . '/src/Api.php';
require dirname(__DIR__) . '/src/SqlValidation.php';

$database = getenv('BACKUP_DATABASE') ?: 'atelier_theoa';
SqlValidation::database($database);

// Le script privé transmet les paramètres de connexion aux tâches cron.
umask(0077);

$script = "#!/bin/sh\n";
$script .= "export PATH='/usr/local/bin:/usr/bin:/bin'\n";

foreach (['DB_HOST', 'DB_PORT', 'DB_USER', 'DB_PASSWORD'] as $name) {
    $value = getenv($name);

    if ($value === false) {
        throw new RuntimeException("Variable manquante : $name");
    }

    $script .= 'export ' . $name . '=' . escapeshellarg($value) . "\n";
}

$script .= 'exec /usr/local/bin/php /var/www/html/bin/backup.php ';
$script .= escapeshellarg($database) . "\n";

if (file_put_contents('/run/safebase-backup.sh', $script) === false) {
    throw new RuntimeException('Impossible de préparer le script cron.');
}

chmod('/run/safebase-backup.sh', 0700);

// Le verrou empêche deux sauvegardes automatiques de se chevaucher.
$job = "0 12 * * * root /usr/bin/flock -n /run/safebase-backup.lock ";
$job .= "/run/safebase-backup.sh ";
$job .= ">> /var/backups/safebase/cron.log 2>&1\n";

if (file_put_contents('/etc/cron.d/safebase', $job) === false) {
    throw new RuntimeException('Impossible de préparer la planification.');
}

chmod('/etc/cron.d/safebase', 0644);

echo "Cron prêt : une sauvegarde quotidienne à 12 h UTC pour $database.\n";