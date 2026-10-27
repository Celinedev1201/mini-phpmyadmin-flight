<?php

// Reçoit la demande de lecture des tables et renvoie le résultat en JSON.
// Flight associe une méthode HTTP et une adresse à la fonction ci-dessous.
// Les paramètres précédés de @ dans l’adresse sont transmis aux arguments de cette fonction.
Flight::route('GET /api/databases/@database/tables', function (string $database): void {
    Api::run(fn() => TableService::list($database));
});

// Lit le nom envoyé par le formulaire et demande la création de la table.
// Flight associe une méthode HTTP et une adresse à la fonction ci-dessous.
// Les paramètres précédés de @ dans l’adresse sont transmis aux arguments de cette fonction.
Flight::route('POST /api/databases/@database/tables', function (string $database): void {
    Api::run(fn() => TableService::create($database, Api::body()), 201);
});

// Lit les nouvelles valeurs pour modifier les tables concernées.
// Flight associe une méthode HTTP et une adresse à la fonction ci-dessous.
// Les paramètres précédés de @ dans l’adresse sont transmis aux arguments de cette fonction.
Flight::route('PATCH /api/databases/@database/tables/@table', function (string $database, string $table): void {
    Api::run(fn() => TableService::rename($database, $table, Api::body()));
});

// Transmet la demande de suppression des tables au service qui vérifie la confirmation.
// Flight associe une méthode HTTP et une adresse à la fonction ci-dessous.
// Les paramètres précédés de @ dans l’adresse sont transmis aux arguments de cette fonction.
Flight::route('DELETE /api/databases/@database/tables/@table', function (string $database, string $table): void {
    Api::run(fn() => TableService::delete($database, $table, Api::body()));
});
