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

    // Vérifie le nom du fichier et retrouve la sauvegarde demandée.
public static function downloadPath(string $database, string $filename): string
{
    SqlValidation::database($database);

    $validFilename = '/^\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}_[a-f0-9]{8}\.sql$/D';

    if (!preg_match($validFilename, $filename)) {
        throw new HttpError('Nom de sauvegarde invalide.', 400);
    }

    $path = self::ROOT . '/' . $database . '/' . $filename;

    if (!is_file($path) || is_link($path) || !is_readable($path)) {
        throw new HttpError('Sauvegarde introuvable ou inaccessible.', 404);
    }

    return $path;
}

}