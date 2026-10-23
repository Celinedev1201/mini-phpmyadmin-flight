<?php

// Liste les bases accessibles, en excluant les bases internes PostgreSQL.
Flight::route(
    'GET /api/postgres/databases',
    function (): void {
        Api::run(function () {
            $pdo = PostgresDatabase::connect();

            $statement = $pdo->query(
                "SELECT datname
                 FROM pg_database
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
    }
);

// Crée une sauvegarde de la base PostgreSQL sélectionnée.
Flight::route(
    'POST /api/postgres/databases/@database/backups',
    function (string $database): void {
        Api::run(
            fn () => PostgresBackupService::create($database),
            201
        );
    }
);

// Liste les sauvegardes PostgreSQL.
Flight::route(
    'GET /api/postgres/databases/@database/backups',
    function (string $database): void {
        Api::run(fn () => PostgresBackupCatalog::list($database));
    }
);

// Télécharge un fichier SQL. Le nom dans l’URL est sans extension.
Flight::route(
    'GET /api/postgres/databases/@database/backups/@filename/download',
    function (string $database, string $filename): void {
        Api::run(function () use ($database, $filename) {
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

// Restaure une sauvegarde PostgreSQL dans une nouvelle base.
Flight::route(
    'POST /api/postgres/databases/@database/backups/@filename/restore',
    function (string $database, string $filename): void {
        Api::run(function () use ($database, $filename) {
            $data = Api::body();
            $target = $data['target'] ?? '';

            if (!is_string($target) || trim($target) === '') {
                throw new HttpError(
                    'Indiquez le nom de la nouvelle base.',
                    400
                );
            }

            return PostgresRestoreService::restore(
                $database,
                $filename . '.sql',
                trim($target)
            );
        }, 201);
    }
);