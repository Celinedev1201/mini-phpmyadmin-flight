<?php

// Charge FlightPHP
require dirname(__DIR__) . '/vendor/autoload.php';

// Charge la connexion MySQL
require dirname(__DIR__) . '/src/Database.php';

// Charge les routes
require dirname(__DIR__) . '/src/routes/health.php';
require dirname(__DIR__) . '/src/routes/databases.php';
require dirname(__DIR__) . '/src/routes/tables.php';

// Démarre l’application
Flight::start();