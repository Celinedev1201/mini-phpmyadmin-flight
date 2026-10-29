<?php

Flight::route(
    'POST /api/databases/@database/copy-to-postgres',
    function (string $database): void {
        Api::run(function () use ($database): array {
            $data = Api::body();
            $target = $data['target'] ?? null;

            if (!is_string($target) || trim($target) === '') {
                throw new HttpError(
                    'Choisissez une base PostgreSQL cible.',
                    400
                );
            }

            return DatabaseCopyService::copy(
                $database,
                trim($target)
            );
        }, 201);
    }
);