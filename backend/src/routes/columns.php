<?php

// Reçoit la demande de lecture des colonnes et renvoie le résultat en JSON.
// Flight associe une méthode HTTP et une adresse à la fonction ci-dessous.
// Les paramètres précédés de @ dans l’adresse sont transmis aux arguments de cette fonction.
Flight::route('GET /api/databases/@database/tables/@table/columns', function (string $database, string $table): void {
    Api::run(function () use ($database, $table): array {
        $service = new ColumnService($database, $table);
        return $service->list();
    });
});

// Lit le nom et le type envoyés puis demande l’ajout de la colonne.
// Flight associe une méthode HTTP et une adresse à la fonction ci-dessous.
// Les paramètres précédés de @ dans l’adresse sont transmis aux arguments de cette fonction.
Flight::route('POST /api/databases/@database/tables/@table/columns', function (string $database, string $table): void {
    Api::run(function () use ($database, $table): array {
        $service = new ColumnService($database, $table);
        $data = Api::body();
        return $service->create($data);
    }, 201);
});

// Lit les nouvelles valeurs pour modifier les colonnes concernées.
// Flight associe une méthode HTTP et une adresse à la fonction ci-dessous.
// Les paramètres précédés de @ dans l’adresse sont transmis aux arguments de cette fonction.
Flight::route('PATCH /api/databases/@database/tables/@table/columns/@column', function (string $database, string $table, string $column): void {
    Api::run(function () use ($database, $table, $column): array {
        $service = new ColumnService($database, $table);
        $data = Api::body();
        return $service->update($column, $data);
    });
});

// Transmet la demande de suppression des colonnes au service qui vérifie la confirmation.
// Flight associe une méthode HTTP et une adresse à la fonction ci-dessous.
// Les paramètres précédés de @ dans l’adresse sont transmis aux arguments de cette fonction.
Flight::route('DELETE /api/databases/@database/tables/@table/columns/@column', function (string $database, string $table, string $column): void {
    Api::run(function () use ($database, $table, $column): array {
        $service = new ColumnService($database, $table);
        $data = Api::body();
        return $service->delete($column, $data);
    });
});
