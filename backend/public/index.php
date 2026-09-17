<?php

// Chargement de FlightPHP et des dépendances Composer
require dirname(__DIR__) . '/vendor/autoload.php';

// Chargement de la classe de connexion MySQL
require dirname(__DIR__) . '/src/Database.php';

// Première route pour vérifier que l’API fonctionne
Flight::route('GET /', function (): void {
    Flight::json([
        'message' => 'API mini phpMyAdmin opérationnelle'
    ]);

});

// Vérifie la connexion entre FlightPHP et MySQL
Flight::route('GET /api/database/status', function (): void {
    try {
        $pdo = Database::connect();

        $version = $pdo
            ->query('SELECT VERSION()')
            ->fetchColumn();

        Flight::json([
            'status' => 'success',
            'message' => 'Connexion MySQL réussie',
            'version' => $version
        ]);
    } catch (PDOException $exception) {
        Flight::json([
            'status' => 'error',
            'message' => 'Connexion MySQL impossible'
        ], 500);
    }
});

// Liste les bases de données disponibles sur le serveur MySQL
Flight::route('GET /api/databases', function (): void {
    try {
        $pdo = Database::connect();

        $statement = $pdo->query('SHOW DATABASES');

        $databases = $statement->fetchAll(PDO::FETCH_COLUMN);

        Flight::json([
            'status' => 'success',
            'count' => count($databases),
            'databases' => $databases
        ]);
    } catch (PDOException $exception) {
        Flight::json([
            'status' => 'error',
            'message' => 'Impossible de récupérer les bases de données'
        ], 500);
    }
});

// Démarrage de l’application
Flight::start();