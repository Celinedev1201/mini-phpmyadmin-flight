<?php

// Liste les tables et vues d’une base
Flight::route('GET /api/databases/@database/tables', function (string $database): void {
    $pdo = Database::connect();

    $statement = $pdo->prepare(
        'SELECT TABLE_NAME
         FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = :database
         ORDER BY TABLE_NAME'
    );

    $statement->execute(['database' => $database]);

    $tables = $statement->fetchAll(PDO::FETCH_COLUMN);

    Flight::json([
        'database' => $database,
        'tables' => $tables,
    ]);
});