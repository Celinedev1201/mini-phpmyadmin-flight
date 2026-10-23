<?php

// Déclenche une sauvegarde de la base sélectionnée.
Flight::route(
    'POST /api/databases/@database/backups',
    function (string $database): void {
        Api::run(fn() => BackupService::create($database), 201);
    }
);

// Liste les sauvegardes disponibles pour cette base.
Flight::route(
    'GET /api/databases/@database/backups',
    function (string $database): void {
        Api::run(fn () => BackupCatalog::list($database));
    }
);

// Envoie la sauvegarde au navigateur sous forme de fichier SQL.
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