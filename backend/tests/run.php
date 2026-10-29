<?php

$tests = [
    ['MySQL vers PostgreSQL : copie complète de base', 'database-copy.php', []],
    [
        'API : liste des bases MySQL',
        'api.php',
        ['/api/databases', 'databases'],
    ],
    [
        'API : liste des bases PostgreSQL',
        'api.php',
        ['/api/postgres/databases', 'databases'],
    ],
    [
        'API : lecture des alertes',
        'api.php',
        ['/api/alerts', 'alerts'],
    ],
    [
        'MySQL : sauvegarde, restauration et comparaison des données',
        'mysql-backup-restore.php',
        [],
    ],
    [
        'PostgreSQL : sauvegarde, restauration et comparaison des données',
        'postgres-backup-restore.php',
        [],
    ],
];

$failures = 0;

echo "TESTS API ET SAUVEGARDE / RESTAURATION\n\n";

foreach ($tests as [$label, $file, $arguments]) {
    echo "Test : $label\n";

    $command = escapeshellarg(PHP_BINARY)
        . ' ' . escapeshellarg(__DIR__ . '/' . $file);

    foreach ($arguments as $argument) {
        $command .= ' ' . escapeshellarg($argument);
    }

    passthru($command, $exitCode);

    if ($exitCode !== 0) {
        $failures++;
    }

    echo "\n";
}

echo count($tests) . " tests exécutés, $failures échec(s).\n";

exit($failures > 0 ? 1 : 0);