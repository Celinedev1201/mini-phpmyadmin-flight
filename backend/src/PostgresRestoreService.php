<?php

// Restaure un dump PostgreSQL dans une nouvelle base.

final class PostgresRestoreService
{
    public static function restore(
        string $database,
        string $filename,
        string $target
    ): array {
        PostgresValidation::database($target);

        $path = PostgresBackupCatalog::downloadPath($database, $filename);
        $pdo = PostgresDatabase::connect();

        // Refuse d’écraser une base existante.
        try {
            $pdo->exec(
                "CREATE DATABASE \"$target\" WITH TEMPLATE template0 ENCODING 'UTF8'"
            );
        } catch (PDOException $exception) {
            error_log('Création de restauration PostgreSQL ' . $target
                . ' : SQLSTATE ' . $exception->getCode());
            $alreadyExists = $exception->getCode() === '42P04';
            throw new HttpError(
                $alreadyExists
                    ? 'Une base porte déjà ce nom. Choisissez un autre nom.'
                    : 'Impossible de créer la base de restauration.',
                $alreadyExists ? 409 : 500
            );
        }

        $command = [
            'psql',
            '--host=' . (getenv('PG_HOST') ?: 'postgres'),
            '--port=' . (getenv('PG_PORT') ?: '5432'),
            '--username=' . (getenv('PG_USER') ?: ''),
            '--dbname=' . $target,
            '--no-password',
            '--no-psqlrc',
            '--set=ON_ERROR_STOP=1',
            '--single-transaction',
            '--file=' . $path,
        ];

        $environment = getenv();
        $environment['PGPASSWORD'] = getenv('PG_PASSWORD') ?: '';

        $descriptors = [
            0 => ['file', '/dev/null', 'r'],
            1 => ['file', '/dev/null', 'w'],
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
            throw new HttpError(
                'Restauration non démarrée. La nouvelle base est vide.',
                500
            );
        }

        $details = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        // L’import entier est annulé si une instruction SQL échoue.
        if ($exitCode !== 0) {
            error_log('Échec de restauration PostgreSQL ' . $database . '/' . $filename
                . ' vers ' . $target . ' : ' . $details);
            throw new HttpError(
                'Restauration échouée. La nouvelle base créée est restée vide.',
                500
            );
        }

        return [
            'message' => 'Sauvegarde PostgreSQL restaurée avec succès',
            'database' => $target,
        ];
    }
}
