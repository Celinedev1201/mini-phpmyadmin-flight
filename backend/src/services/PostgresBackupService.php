<?php

// Exporte une base PostgreSQL dans le dossier partagé des sauvegardes.
final class PostgresBackupService
{
    // /var/backups/safebase est le dossier de sauvegardes partagé avec l’ordinateur par Docker.
    private const ROOT = '/var/backups/safebase/postgres';
    // Exporte la base PostgreSQL avec pg_dump et conserve seulement une sauvegarde terminée.
    // pg_dump exporte la base dans un fichier temporaire. Le mot de passe passe par l’environnement du processus. Si
    // la commande réussit et que le fichier contient des données, rename() rend la sauvegarde disponible.
    public static function create(string $database): array
    {
        PostgresValidation::database($database);

        $directory = self::ROOT . '/' . $database;

        if (!is_dir($directory) && !mkdir($directory, 0755, true)) {
            throw new HttpError('Impossible de créer le dossier de sauvegarde.', 500);
        }

        $filename = gmdate('Y-m-d_H-i-s') . '_';
        $filename .= bin2hex(random_bytes(4)) . '.sql';

        $destination = $directory . '/' . $filename;
        $temporary = $destination . '.partial';

        $command = [
            'pg_dump',
            '--host=' . (getenv('PG_HOST') ?: 'postgres'),
            '--port=' . (getenv('PG_PORT') ?: '5432'),
            '--username=' . (getenv('PG_USER') ?: ''),
            '--dbname=' . $database,
            '--format=plain',
            '--no-owner',
            '--no-privileges',
            '--no-password',
        ];

        // Le mot de passe ne figure pas dans les arguments.
        $environment = getenv();
        $environment['PGPASSWORD'] = getenv('PG_PASSWORD') ?: '';

        $descriptors = [
            0 => ['file', '/dev/null', 'r'],
            1 => ['file', $temporary, 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open(
            $command,
            $descriptors,
            $pipes,
            null,
            $environment
        );

        if (!is_resource($process)) {
            @unlink($temporary);
            throw new HttpError('Impossible de démarrer la sauvegarde.', 500);
        }

        chmod($temporary, 0644);

        $details = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        if ($exitCode !== 0 || !is_file($temporary) || filesize($temporary) === 0) {
            @unlink($temporary);
            error_log('Échec de pg_dump : ' . $details);

            throw new HttpError('Échec de la sauvegarde PostgreSQL.', 500);
        }

        // Un fichier incomplet ne doit jamais apparaître dans l’historique.
        if (!rename($temporary, $destination)) {
            @unlink($temporary);
            throw new HttpError('Impossible de finaliser la sauvegarde.', 500);
        }

        return [
            'message' => 'Sauvegarde PostgreSQL créée avec succès',
            'database' => $database,
            'filename' => $filename,
            'size' => filesize($destination),
        ];
    }
}