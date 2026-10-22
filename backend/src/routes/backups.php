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