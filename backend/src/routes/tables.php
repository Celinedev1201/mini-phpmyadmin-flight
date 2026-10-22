<?php

Flight::route('GET /api/databases/@database/tables', function (string $database): void {
    Api::run(fn() => TableService::list($database));
});

Flight::route('POST /api/databases/@database/tables', function (string $database): void {
    Api::run(fn() => TableService::create($database, Api::body()), 201);
});

Flight::route('PATCH /api/databases/@database/tables/@table', function (string $database, string $table): void {
    Api::run(fn() => TableService::rename($database, $table, Api::body()));
});

Flight::route('DELETE /api/databases/@database/tables/@table', function (string $database, string $table): void {
    Api::run(fn() => TableService::delete($database, $table, Api::body()));
});
