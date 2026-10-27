<?php

// Reçoit la demande de lecture des tables et renvoie le résultat en JSON.
Flight::route('GET /api/databases/@database/tables', function (string $database): void {
    Api::run(fn() => TableService::list($database));
});

// Lit le nom envoyé par le formulaire et demande la création de la table.
Flight::route('POST /api/databases/@database/tables', function (string $database): void {
    Api::run(fn() => TableService::create($database, Api::body()), 201);
});

// Lit les nouvelles valeurs pour modifier les tables concernées.
Flight::route('PATCH /api/databases/@database/tables/@table', function (string $database, string $table): void {
    Api::run(fn() => TableService::rename($database, $table, Api::body()));
});

// Transmet la demande de suppression des tables au service qui vérifie la confirmation.
Flight::route('DELETE /api/databases/@database/tables/@table', function (string $database, string $table): void {
    Api::run(fn() => TableService::delete($database, $table, Api::body()));
});
