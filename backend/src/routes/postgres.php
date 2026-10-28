<?php

// Supprime une base après vérification de la confirmation.
Flight::route(
    'DELETE /api/postgres/databases/@database',
    function (string $database): void {
        Api::run(fn () => PostgresDatabaseService::delete($database, Api::body()));
    }
);

// Liste les bases accessibles, sans les bases internes.
Flight::route('GET /api/postgres/databases', function (): void {
    Api::run(function (): array {
        $pdo = PostgresDatabase::connect();
        $statement = $pdo->query(
            "SELECT datname FROM pg_database
             WHERE datistemplate = false
               AND datallowconn = true
               AND datname <> 'postgres'
               AND has_database_privilege(datname, 'CONNECT')
             ORDER BY datname"
        );
        $databases = $statement->fetchAll(PDO::FETCH_COLUMN);
        return [
            'status' => 'success',
            'count' => count($databases),
            'databases' => $databases,
        ];
    });
});

// Crée une sauvegarde et enregistre les échecs techniques.
Flight::route(
    'POST /api/postgres/databases/@database/backups',
    function (string $database): void {
        Api::run(function () use ($database): array {
            try {
                return PostgresBackupService::create($database);
            } catch (Throwable $exception) {
                if (!($exception instanceof HttpError) || $exception->status >= 500) {
                    AlertLogger::record(
                        'postgres', 'backup', $database,
                        'La sauvegarde manuelle PostgreSQL a échoué.'
                    );
                }
                throw $exception;
            }
        }, 201);
    }
);

// Liste les sauvegardes disponibles.
Flight::route(
    'GET /api/postgres/databases/@database/backups',
    function (string $database): void {
        Api::run(fn () => PostgresBackupCatalog::list($database));
    }
);

// Télécharge le fichier SQL choisi.
Flight::route(
    'GET /api/postgres/databases/@database/backups/@filename/download',
    function (string $database, string $filename): void {
        Api::run(function () use ($database, $filename): void {
            $filename .= '.sql';
            $path = PostgresBackupCatalog::downloadPath($database, $filename);
            header('Content-Type: application/sql');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . filesize($path));
            header('Cache-Control: no-store');
            header('X-Content-Type-Options: nosniff');
            readfile($path);
            exit;
        });
    }
);

// Restaure dans une nouvelle base et enregistre les échecs techniques.
Flight::route(
    'POST /api/postgres/databases/@database/backups/@filename/restore',
    function (string $database, string $filename): void {
        Api::run(function () use ($database, $filename): array {
            $data = Api::body();
            $target = $data['target'] ?? '';
            if (!is_string($target) || trim($target) === '') {
                throw new HttpError('Indiquez le nom de la nouvelle base.', 400);
            }
            $target = trim($target);
            try {
                return PostgresRestoreService::restore($database, $filename . '.sql', $target);
            } catch (Throwable $exception) {
                if (!($exception instanceof HttpError) || $exception->status >= 500) {
                    AlertLogger::record(
                        'postgres', 'restore', $target,
                        'La restauration PostgreSQL a échoué.'
                    );
                }
                throw $exception;
            }
        }, 201);
    }
);