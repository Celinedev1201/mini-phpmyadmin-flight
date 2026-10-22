<?php

Flight::route('GET /api/databases/@database/tables/@table/columns', function (string $database, string $table): void {
    Api::run(function () use ($database, $table): array {
        $service = new ColumnService($database, $table);
        return $service->list();
    });
});

Flight::route('POST /api/databases/@database/tables/@table/columns', function (string $database, string $table): void {
    Api::run(function () use ($database, $table): array {
        $service = new ColumnService($database, $table);
        $data = Api::body();
        return $service->create($data);
    }, 201);
});

Flight::route('PATCH /api/databases/@database/tables/@table/columns/@column', function (string $database, string $table, string $column): void {
    Api::run(function () use ($database, $table, $column): array {
        $service = new ColumnService($database, $table);
        $data = Api::body();
        return $service->update($column, $data);
    });
});

Flight::route('DELETE /api/databases/@database/tables/@table/columns/@column', function (string $database, string $table, string $column): void {
    Api::run(function () use ($database, $table, $column): array {
        $service = new ColumnService($database, $table);
        $data = Api::body();
        return $service->delete($column, $data);
    });
});
