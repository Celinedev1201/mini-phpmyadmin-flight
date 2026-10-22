<?php

// Consulte les sauvegardes déjà présentes sur le disque.
final class BackupCatalog
{
    private const ROOT = '/var/backups/safebase/mysql';

    public static function list(string $database): array
    {
        SqlValidation::database($database);

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

        // Les noms commencent par la date : tri du plus récent au plus ancien.
        usort(
            $backups,
            fn(array $first, array $second): int =>
                strcmp($second['filename'], $first['filename'])
        );

        return ['backups' => $backups];
    }
}