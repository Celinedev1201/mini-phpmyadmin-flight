<?php

// Reçoit une demande d’ajout de colonne
Flight::route(
    'POST /api/databases/@database/tables/@table/columns',
    function (string $database, string $table): void {
        $data = Flight::request()->data;

        $name = trim((string) ($data->name ?? ''));
$type = (string) ($data->type ?? '');

$validName = '/^[a-zA-Z][a-zA-Z0-9_]{0,63}$/';

if (
    !preg_match($validName, $database)
    || !preg_match($validName, $table)
    || !preg_match($validName, $name)
) {
    Flight::json(['message' => 'Nom invalide.'], 400);
    return;
}

if (in_array(strtolower($database), [
    'mysql',
    'sys',
    'information_schema',
    'performance_schema',
], true)) {
    Flight::json(['message' => 'Base système protégée.'], 403);
    return;
}

$allowedTypes = [
    'VARCHAR' => 'VARCHAR(255)',
    'TEXT' => 'TEXT',
    'INT' => 'INT',
    'DECIMAL' => 'DECIMAL(10,2)',
    'DATE' => 'DATE',
];

if (!isset($allowedTypes[$type])) {
    Flight::json(['message' => 'Type non autorisé.'], 400);
    return;
}

$sqlType = $allowedTypes[$type];
try {
    $pdo = Database::connect();

    $pdo->exec(
        "ALTER TABLE `$database`.`$table`
         ADD COLUMN `$name` $sqlType NULL"
    );

    Flight::json([
        'message' => 'Colonne ajoutée avec succès',
        'name' => $name,
        'type' => $sqlType,
    ], 201);
} catch (PDOException $exception) {
    $alreadyExists = ($exception->errorInfo[1] ?? null) === 1060;

    Flight::json([
        'message' => $alreadyExists
            ? 'Une colonne porte déjà ce nom'
            : 'Impossible d’ajouter la colonne',
    ], $alreadyExists ? 409 : 500);
}
    }
);