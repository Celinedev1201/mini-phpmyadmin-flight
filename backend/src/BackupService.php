<?php

// Produit les dumps MySQL dans le dossier partagé avec l’ordinateur.
final class BackupService
{
    private const ROOT = '/var/backups/safebase/mysql';

    public static function create(string $database): array
    {
        SqlValidation::database($database);

        $directory = self::ROOT . '/' . $database;

        if (!is_dir($directory) && !mkdir($directory, 0755, true)) {
            throw new HttpError('Impossible de créer le dossier de sauvegarde.', 500);
        }

        // Le suffixe aléatoire évite les collisions entre deux sauvegardes.
        $filename = gmdate('Y-m-d_H-i-s') . '_' . bin2hex(random_bytes(4)) . '.sql';
        $destination = $directory . '/' . $filename;
        $temporary = $destination . '.partial';

        $command = [
            'mysqldump',
            '--host=' . (getenv('DB_HOST') ?: 'mysql'),
            '--port=' . (getenv('DB_PORT') ?: '3306'),
            '--user=' . (getenv('DB_USER') ?: 'root'),
            '--single-transaction',
            '--quick',
            '--no-tablespaces',
            '--set-gtid-purged=OFF',
            '--routines',
            '--events',
            '--triggers',
            '--hex-blob',
            '--default-character-set=utf8mb4',
            '--',
            $database,
        ];

        // Le mot de passe n’apparaît pas dans les arguments de la commande.
        $environment = getenv();
        $environment['MYSQL_PWD'] = getenv('DB_PASSWORD') ?: '';

        $descriptors = [
            0 => ['file', '/dev/null', 'r'],
            1 => ['file', $temporary, 'w'],
            2 => ['pipe', 'w'],
        ];
        
        //lance la commande mysqldump pour créer le dump de la base de données
        $process = proc_open($command, $descriptors, $pipes, null, $environment);

        if (!is_resource($process)) {
            @unlink($temporary);
            throw new HttpError('Impossible de démarrer la sauvegarde.', 500);
        }

        chmod($temporary, 0644);

        // Vide le flux d’erreur sans exposer ses détails au navigateur.
        stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        if ($exitCode !== 0 || !is_file($temporary) || filesize($temporary) === 0) {
            @unlink($temporary);
            throw new HttpError('Échec de la sauvegarde MySQL.', 500);
        }

        // Seul un export terminé avec succès devient une sauvegarde disponible.
        if (!rename($temporary, $destination)) {
            @unlink($temporary);
            throw new HttpError('Impossible de finaliser la sauvegarde.', 500);
        }

        return [
            'message' => 'Sauvegarde créée avec succès',
            'database' => $database,
            'filename' => $filename,
            'size' => filesize($destination),
        ];
    }
}
