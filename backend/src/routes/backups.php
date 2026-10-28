<?php

// Déclenche une sauvegarde de la base sélectionnée.
// Flight associe une méthode HTTP et une adresse à la fonction ci-dessous.
// Les paramètres précédés de @ dans l’adresse sont transmis aux arguments de cette fonction.
Flight::route(
    'POST /api/databases/@database/backups',
    function (string $database): void {
        Api::run(function () use ($database): array {
            try {
                return BackupService::create($database);
            } catch (Throwable $exception) {
                if (
                    !($exception instanceof HttpError)
                    || $exception->status >= 500
                ) {
                    AlertLogger::record(
                        'mysql',
                        'backup',
                        $database,
                        'La sauvegarde manuelle a échoué.'
                    );
                }

                throw $exception;
            }
        }, 201);
    }
);

// Liste les sauvegardes disponibles pour cette base.
// Flight associe une méthode HTTP et une adresse à la fonction ci-dessous.
// Les paramètres précédés de @ dans l’adresse sont transmis aux arguments de cette fonction.
Flight::route(
    'GET /api/databases/@database/backups',
    function (string $database): void {
        Api::run(fn() => BackupCatalog::list($database));
    }
);

// Envoie la sauvegarde au navigateur sous forme de fichier SQL.
// Flight associe une méthode HTTP et une adresse à la fonction ci-dessous.
// Les paramètres précédés de @ dans l’adresse sont transmis aux arguments de cette fonction.
Flight::route(
    'GET /api/databases/@database/backups/@filename/download',
    function (string $database, string $filename): void {
        Api::run(function () use ($database, $filename) {
            // Remet l’extension du fichier, retirée de l’URL de téléchargement.
            $filename .= '.sql';
            $path = BackupCatalog::downloadPath($database, $filename);


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

// Restaure une sauvegarde dans une nouvelle base.
// Flight associe une méthode HTTP et une adresse à la fonction ci-dessous.
// Les paramètres précédés de @ dans l’adresse sont transmis aux arguments de cette fonction.
Flight::route(
    'POST /api/databases/@database/backups/@filename/restore',
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

            // Le nom transmis dans l’URL ne contient pas l’extension.
            $filename .= '.sql';

            try {
                return RestoreService::restore(
                    $database,
                    $filename,
                    trim($target)
                );
            } catch (Throwable $exception) {
                if (
                    !($exception instanceof HttpError)
                    || $exception->status >= 500
                ) {
                    AlertLogger::record(
                        'mysql',
                        'restore',
                        trim($target),
                        'La restauration MySQL a échoué.'
                    );
                }

                throw $exception;
            }
        }, 201);
    }
);
