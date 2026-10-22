<?php

Flight::route('GET /api/databases', function (): void {
    Api::run(fn() => DatabaseService::list());
});

Flight::route('POST /api/databases', function (): void {
    Api::run(fn() => DatabaseService::create(Api::body()), 201);
});

// Le nom doit être recopié, en plus de la confirmation explicite.
Flight::route('DELETE /api/databases/@database', function (string $database): void {
    Api::run(fn() => DatabaseService::delete($database, Api::body()));
});
