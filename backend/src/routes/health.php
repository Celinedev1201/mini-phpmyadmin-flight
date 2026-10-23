<?php

// Vérifie que l’API fonctionne
Flight::route('GET /', function (): void {
    Flight::json([
        'message' => 'API mini phpMyAdmin opérationnelle',
    ]);
});

// Vérifie la connexion à MySQL
Flight::route('GET /api/database/status', function (): void {
    try {
        $pdo = Database::connect();

        $version = $pdo
            ->query('SELECT VERSION()')
            ->fetchColumn();

        Flight::json([
            'status' => 'success',
            'message' => 'Connexion MySQL réussie',
            'version' => $version,
        ]);
    } catch (PDOException $exception) {
        Flight::json([
            'status' => 'error',
            'message' => 'Connexion MySQL impossible',
        ], 500);
    }
});

// Vérifie que PHP peut se connecter à PostgreSQL.
Flight::route(
    'GET /api/postgres/status',
    function (): void {
        Api::run(function () {
            $pdo = PostgresDatabase::connect();

            return [
                'status' => 'success',
                'message' => 'Connexion PostgreSQL réussie',
                'version' => $pdo->query('SELECT version()')->fetchColumn(),
            ];
        });
    }
);