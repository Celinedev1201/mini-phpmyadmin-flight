<?php

// Restaure un dump dans une nouvelle base pour préserver l’originale.
final class RestoreService
{
    public static function restore(
        string $database,
        string $filename,
        string $target
    ): array {
        SqlValidation::database($target);

        $path = BackupCatalog::downloadPath($database, $filename);
        $pdo = Database::connect();

        // CREATE DATABASE échoue si la base existe déjà.
        try {
            $pdo->exec(
                "CREATE DATABASE `$target` CHARACTER SET utf8mb4"
            );
        } catch (PDOException $exception) {
            $alreadyExists = (int) ($exception->errorInfo[1] ?? 0) === 1007;

            throw new HttpError(
                $alreadyExists
                    ? 'Une base porte déjà ce nom. Choisissez un autre nom.'
                    : 'Impossible de créer la base de restauration.',
                $alreadyExists ? 409 : 500
            );
        }

        $command = [
            'mysql',
            '--host=' . (getenv('DB_HOST') ?: 'mysql'),
            '--port=' . (getenv('DB_PORT') ?: '3306'),
            '--user=' . (getenv('DB_USER') ?: 'root'),
            '--default-character-set=utf8mb4',
            '--batch',
            '--binary-mode',
            '--database=' . $target,
        ];

        // Le client MySQL lit directement le fichier SQL.
        $descriptors = [
            0 => ['file', $path, 'r'],
            1 => ['file', '/dev/null', 'w'],
            2 => ['pipe', 'w'],
        ];

        $environment = getenv();
        $environment['MYSQL_PWD'] = getenv('DB_PASSWORD') ?: '';

        $process = proc_open(
            $command,
            $descriptors,
            $pipes,
            null,
            $environment
        );

        if (!is_resource($process)) {
            throw new HttpError(
                'Restauration non démarrée. La nouvelle base est vide.',
                500
            );
        }

        $details = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        // Une restauration SQL peut échouer après avoir importé une partie.
        if ($exitCode !== 0) {
            error_log('Échec de restauration MySQL : ' . $details);

            throw new HttpError(
                'Restauration échouée. La nouvelle base peut être incomplète.',
                500
            );
        }

        return [
            'message' => 'Sauvegarde restaurée avec succès',
            'database' => $target,
        ];
    }
}