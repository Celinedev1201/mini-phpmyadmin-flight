<?php

// Consulte les sauvegardes PostgreSQL présentes sur le disque.
final class PostgresBackupCatalog
{
    private const ROOT = '/var/backups/safebase/postgres';
    // Lit les sauvegardes PostgreSQL sur le disque et les classe par date décroissante.
    public static function list(string $database): array
    {
        PostgresValidation::database($database);

        $directory = self::ROOT . '/' . $database;
        $backups = [];

        foreach (glob($directory . '/*.sql') ?: [] as $path) {
            if (!is_file($path) || is_link($path)) {
                continue;
            }

            $backups[] = [
                'filename' => basename($path),
                'size' => filesize($path),
                'createdAt' => gmdate('c', filemtime($path)),
            ];
        }

        // Les noms datés permettent de présenter les plus récents en premier.
        usort(
            $backups,
            fn (array $first, array $second): int =>
                strcmp($second['filename'], $first['filename'])
        );

        return ['backups' => $backups];
    }
    // Vérifie le fichier PostgreSQL demandé avant d’autoriser son téléchargement.
    public static function downloadPath(
        string $database,
        string $filename
    ): string {
        PostgresValidation::database($database);

        $pattern = '/^\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}_[a-f0-9]{8}\.sql$/D';

        if (!preg_match($pattern, $filename)) {
            throw new HttpError('Nom de sauvegarde invalide.', 400);
        }

        $path = self::ROOT . '/' . $database . '/' . $filename;

        if (!is_file($path) || is_link($path) || !is_readable($path)) {
            throw new HttpError('Sauvegarde introuvable ou inaccessible.', 404);
        }

        return $path;
    }
}