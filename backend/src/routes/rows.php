<?php

// Reçoit la demande de lecture des lignes et renvoie le résultat en JSON.
Flight::route('GET /api/databases/@database/tables/@table/rows', function (string $database, string $table): void {
    Api::run(function () use ($database, $table): array {
        $service = new RowService($database, $table);
        return $service->list();
    });
});

// Lit les valeurs envoyées et demande l’ajout d’une ligne dans la table.
Flight::route('POST /api/databases/@database/tables/@table/rows', function (string $database, string $table): void {
    Api::run(function () use ($database, $table): array {
        $service = new RowService($database, $table);
        $data = Api::body();
        return $service->create($data);
    }, 201);
});

// Lit les nouvelles valeurs pour modifier les lignes concernées.
Flight::route('PATCH /api/databases/@database/tables/@table/rows', function (string $database, string $table): void {
    Api::run(function () use ($database, $table): array {
        $service = new RowService($database, $table);
        $data = Api::body();
        return $service->update($data);
    });
});

// Transmet la demande de suppression des lignes au service qui vérifie la confirmation.
Flight::route('DELETE /api/databases/@database/tables/@table/rows', function (string $database, string $table): void {
    Api::run(function () use ($database, $table): array {
        $service = new RowService($database, $table);
        $data = Api::body();
        return $service->delete($data);
    });
});
