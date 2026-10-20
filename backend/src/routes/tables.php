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

// Reçoit une demande de création de table
Flight::route(
    'POST /api/databases/@database/tables',
    function (string $database): void {
        $data = Flight::request()->data;
        $name = trim((string) ($data->name ?? ''));

$validName = '/^[a-zA-Z][a-zA-Z0-9_]{0,63}$/';

if (
    !preg_match($validName, $database)
    || !preg_match($validName, $name)
) {
    Flight::json([
        'message' => 'Noms invalides : lettres, chiffres et underscores, 64 caractères maximum. Commencer par une lettre.',
    ], 400);

    return;
}

if (in_array(strtolower($database), [
    'mysql',
    'sys',
    'information_schema',
    'performance_schema',
], true)) {
    Flight::json([
        'message' => 'Création interdite dans une base système.',
    ], 403);

    return;
}

try {
    $pdo = Database::connect();

    $pdo->exec(
        "CREATE TABLE `$database`.`$name` (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    Flight::json([
        'message' => 'Table créée avec succès',
        'database' => $database,
        'name' => $name,
    ], 201);
} catch (PDOException $exception) {
    $alreadyExists = ($exception->errorInfo[1] ?? null) === 1050;

    Flight::json([
        'message' => $alreadyExists
            ? 'Une table porte déjà ce nom'
            : 'Impossible de créer la table',
    ], $alreadyExists ? 409 : 500);
}
    }
);

// Liste les colonnes d’une table
Flight::route(
    'GET /api/databases/@database/tables/@table/columns',
    function (string $database, string $table): void {
        $pdo = Database::connect();

        $statement = $pdo->prepare(
            'SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE,
                    COLUMN_KEY, EXTRA
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = :database
               AND TABLE_NAME = :table
             ORDER BY ORDINAL_POSITION'
        );

        $statement->execute([
            'database' => $database,
            'table' => $table,
        ]);

        Flight::json([
            'columns' => $statement->fetchAll(PDO::FETCH_ASSOC),
        ]);
    }
);