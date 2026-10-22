<?php

Flight::route('GET /api/databases/@database/tables/@table/rows', function (string $database, string $table): void {
    Api::run(function () use ($database, $table): array {
        $service = new RowService($database, $table);
        return $service->list();
    });
});

Flight::route('POST /api/databases/@database/tables/@table/rows', function (string $database, string $table): void {
    Api::run(function () use ($database, $table): array {
        $service = new RowService($database, $table);
        $data = Api::body();
        return $service->create($data);
    }, 201);
});

Flight::route('PATCH /api/databases/@database/tables/@table/rows', function (string $database, string $table): void {
    Api::run(function () use ($database, $table): array {
        $service = new RowService($database, $table);
        $data = Api::body();
        return $service->update($data);
    });
});

Flight::route('DELETE /api/databases/@database/tables/@table/rows', function (string $database, string $table): void {
    Api::run(function () use ($database, $table): array {
        $service = new RowService($database, $table);
        $data = Api::body();
        return $service->delete($data);
    });
});
