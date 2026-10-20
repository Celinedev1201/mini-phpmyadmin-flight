<?php

// Liste les bases personnelles
Flight::route('GET /api/databases', function (): void {
    try {
        $pdo = Database::connect();

        $statement = $pdo->query('SHOW DATABASES');
        $databases = $statement->fetchAll(PDO::FETCH_COLUMN);

        $systemDatabases = [
            'information_schema',
            'mysql',
            'performance_schema',
            'sys',
        ];

        $databases = array_values(
            array_diff($databases, $systemDatabases)
        );

        Flight::json([
            'status' => 'success',
            'count' => count($databases),
            'databases' => $databases,
        ]);
    } catch (PDOException $exception) {
        Flight::json([
            'status' => 'error',
            'message' => 'Impossible de récupérer les bases de données',
        ], 500);
    }
});

// Reçoit le nom d’une base à créer, sans la créer pour l’instant
Flight::route('POST /api/databases', function (): void {
    $data = Flight::request()->data;
    $name = trim((string) ($data->name ?? ''));

if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_]{0,63}$/', $name)) {
    Flight::json([
        'message' => 'Nom invalide : 1 à 64 caractères, lettres, chiffres et underscores. Commencer par une lettre.',
    ], 400);

    return;
}

try {
    $pdo = Database::connect();

    $pdo->exec(
        "CREATE DATABASE `$name` CHARACTER SET utf8mb4"
    );

    Flight::json([
        'message' => 'Base créée avec succès',
        'name' => $name,
    ], 201);
} catch (PDOException $exception) {
    $alreadyExists = ($exception->errorInfo[1] ?? null) === 1007;

    Flight::json([
        'message' => $alreadyExists
            ? 'Une base porte déjà ce nom'
            : 'Impossible de créer la base',
    ], $alreadyExists ? 409 : 500);
}
});