<?php

// Reçoit la demande de lecture des bases et renvoie le résultat en JSON.
Flight::route('GET /api/databases', function (): void {
    Api::run(fn() => DatabaseService::list());
});

// Lit le nom envoyé par le formulaire et demande la création de la base.
Flight::route('POST /api/databases', function (): void {
    Api::run(fn() => DatabaseService::create(Api::body()), 201);
});

// Le nom doit être recopié, en plus de la confirmation explicite.
// Transmet la demande de suppression des bases au service qui vérifie la confirmation.
Flight::route('DELETE /api/databases/@database', function (string $database): void {
    Api::run(fn() => DatabaseService::delete($database, Api::body()));
});
