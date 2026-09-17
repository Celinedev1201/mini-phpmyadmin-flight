<?php

// Chargement de FlightPHP et des dépendances Composer
require dirname(__DIR__) . '/vendor/autoload.php';

// Première route pour vérifier que l’API fonctionne
Flight::route('GET /', function (): void {
    Flight::json([
        'message' => 'API mini phpMyAdmin opérationnelle'
    ]);
});

// Démarrage de l’application
Flight::start();